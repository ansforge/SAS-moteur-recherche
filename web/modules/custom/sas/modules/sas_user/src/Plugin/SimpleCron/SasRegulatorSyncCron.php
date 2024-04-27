<?php

namespace Drupal\sas_user\Plugin\SimpleCron;

use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Utility\Error;
use Drupal\sas_user\Enum\SasRegulatorSync;
use Drupal\sas_user\Model\SasRegulatorSyncQueueItem;
use Drupal\simple_cron\Plugin\SimpleCronPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Single cron.
 *
 * @SimpleCron(
 *   id = "sas_regulator_sync_cron",
 *   label = @Translation("SAS - Regulator Sync", context = "Simple cron")
 * )
 */
class SasRegulatorSyncCron extends SimpleCronPluginBase {

  /**
   * SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The queue plugin manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sasCoreService = $container->get('sas_core.service');
    $instance->connection = $container->get('database');
    $instance->queueManager = $container->get('plugin.manager.queue_worker');
    $instance->queueFactory = $container->get('queue');
    $instance->time = $container->get('datetime.time');
    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->sasCoreService->isSasContext()) {
      $queue_name = SasRegulatorSync::QUEUE_NAME;
      $queue_worker = $this->queueManager->createInstance($queue_name);
      $queue = $this->queueFactory->get($queue_name);
      $currentTime = $this->time->getCurrentTime();

      foreach ($this->claimItem($currentTime) as $item) {
        $queue_item = unserialize($item->data, ['allowed_classes' => [SasRegulatorSyncQueueItem::class]]);

        try {
          $queue_worker->processItem($queue_item);
          $queue->deleteItem($item);
        }
        catch (\Exception $e) {
          Error::logException('sas_user', $e);
        }
      }
    }
  }

  /**
   * Override of DatabaseQueue::claimItem().
   * DatabaseQueue::claimItem update expired field after process and we can not
   * retrieve nex queue item anymore on next cron occurrences.
   */
  private function claimItem(int $created) {
    return $this->connection
      ->select(DatabaseQueue::TABLE_NAME, 'q')
      ->fields('q', ['data', 'created', 'item_id'])
      ->condition('name', SasRegulatorSync::QUEUE_NAME)
      ->condition('created', $created, '<')
      ->orderBy('created')
      ->orderBy('item_id')
      ->execute()
      ->fetchAll();
  }

}
