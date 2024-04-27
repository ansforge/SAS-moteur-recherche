<?php

namespace Drupal\sas_user\Plugin\QueueWorker;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\sas_user\Entity\SasRegulatorSyncError;
use Drupal\sas_user\Enum\SasRegulatorSync;
use Drupal\sas_user\Model\SasRegulatorSyncQueueItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'sas_regulator_sync_queue_worker' queue worker.
 *
 * @QueueWorker(
 *   id = "sas_regulator_sync_queue_worker",
 *   title = @Translation("Regulator Sync Queue Worker")
 * )
 */
class SasRegulatorSyncQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queue;

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasApiClient;

  /**
   * SasRegulatorSyncQueueWorker constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Queue\QueueFactory $queue
   * @param \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager $sas_api_client
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueueFactory $queue,
    ClientEndpointPluginManager $sas_api_client
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queue = $queue;
    $this->sasApiClient = $sas_api_client;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue'),
      $container->get('sas_api_client.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    if (!$data instanceof SasRegulatorSyncQueueItem) {
      return;
    }

    $regulator_data = $data->getRegulatorSyncPayload();

    try {
      $this->sasApiClient->aggregator($data->getEndpointPlugin(), [
        'body' => $regulator_data,
      ]);
    }
    catch (PluginException $e) {
      if ($data->getTryCount() < SasRegulatorSync::MAX_TRY_COUNT) {
        $regulator_data['tryCount'] = $data->getTryCount() + 1;
        $queue_item = SasRegulatorSyncQueueItem::createByData($regulator_data, $data->getEndpointPlugin());
        $regulator_sync_queue = $this->queue->get(SasRegulatorSync::QUEUE_NAME);
        $regulator_sync_queue->createItem($queue_item);
      }
      else {
        $sync_error = SasRegulatorSyncError::create([
          'payload' => json_encode($regulator_data),
          'error_code' => $e->getCode(),
          'error_message' => $e->getMessage(),
          'label' => sprintf('Sync error to aggregator for user %s', $regulator_data['uuid'] ?? ''),
        ]);
        $sync_error->save();
        \Drupal::logger('sas_regulator_queue')->error('Max count passed, create SasRegulatorSyncError entity.');
      }
    }
  }

}
