<?php

namespace Drupal\sas_api_client\Plugin\SimpleCron\Aggregator;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Url;
use Drupal\simple_cron\Plugin\SimpleCronPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns latest interfaced PS in last 5 minutes in aggregator to flag them.
 *
 * @SimpleCron(
 *   id = "sas_interfaced_cron",
 *   label = @Translation("SAS - Is Interfaced", context = "Simple cron")
 * )
 */
class InterfacedCron extends SimpleCronPluginBase {

  /**
   * The SAS API client manager.
   *
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected $sasApiClient;

  /**
   * The SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreService
   */
  protected $sasCoreService;

  /**
   * The SAS Interfaced Service.
   *
   * @var \Drupal\sas_snp\Service\InterfacedHelper
   */
  private $interfacedHelper;

  /**
   * BATCH COUNT.
   */
  const BATCH_ITEM_COUNT = '100';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sasApiClient = $container->get('sas_api_client.service');
    $instance->sasCoreService = $container->get('sas_core.service');
    $instance->interfacedHelper = $container->get('sas_snp.interfaced_helper');

    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function process(array $params = []): void {
    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Cette commande doit être lancé dans le context SAS.');
    }

    $start_date = $params['start_date'] ?? NULL;
    $end_date = $params['end_date'] ?? NULL;

    if (empty($start_date) || empty($end_date)) {
      try {
        $end_date = new \DateTimeImmutable();
        $start_date = $end_date->modify('-5 minutes')
          ->format(\DateTimeInterface::ATOM);
        $end_date = $end_date->format(\DateTimeInterface::ATOM);
      }
      catch (\Exception $e) {
        return;
      }
    }

    try {
      $response = $this->sasApiClient->aggregator('interfaced', [
        'query' => [
          'start' => $start_date,
          'end' => $end_date,
        ],
      ]);
    }
    catch (PluginException $e) {
      return;
    }

    $adeli_ids = $response['adeli'] ?? [];
    $rpps_ids = $response['rpps'] ?? [];
    $nat_ids = array_merge($adeli_ids, $rpps_ids);

    if (empty($nat_ids)) {
      return;
    }

    // Get the list of RPPS from the is_interfaced table.
    $existing_rpps_list = $this->interfacedHelper->getAllRpps();

    // Generate a list of new RPPS IDs that are not already present in the existing list.
    $new_rpps_list = array_diff($nat_ids, $existing_rpps_list);

    // Save the new RPPS IDs using the batch process.
    if (!empty($new_rpps_list)) {

      $max_items = self::BATCH_ITEM_COUNT;
      $operations = array_chunk($new_rpps_list, $max_items);
      $batch = [
        'title' => 'Mise à jour des professionnels de santé interfacés',
        'operations' => [],
        'finished' => 'Drupal\sas_api_client\Batch\UpdateInterfacedBatch::finished',
      ];

      foreach ($operations as $operation) {
        $batch['operations'][] = [
          'Drupal\sas_api_client\Batch\UpdateInterfacedBatch::updateInterfaced',
          [$operation],
        ];
      }

      batch_set($batch);
      if (PHP_SAPI !== 'cli') {
        $redirectResponse = batch_process(Url::fromRoute('entity.simple_cron_job.collection'));
        $redirectResponse->send();
      }
      else {
        $batch['progressive'] = FALSE;
        drush_backend_batch_process();
      }
    }

  }

}
