<?php

namespace Drupal\sas_api_client\Service;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\sas_api_client\Enum\SasAnalitycsLogConstant;
use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;

/**
 * Class SasAnalyticsLogService.
 *
 * Provides service to log data to SAS ELK.
 *
 * @package Drupal\sas_api_client\Service
 */
class SasAnalyticsLogService implements SasAnalyticsLogServiceInterface {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasApiClientManager;

  public function __construct(
    LoggerChannelFactoryInterface $logger,
    ClientEndpointPluginManager $sas_api_client_manager
  ) {
    $this->logger = $logger;
    $this->sasApiClientManager = $sas_api_client_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function pushLog(string $log_name, mixed $data): void {
    try {
      $this->sasApiClientManager->analytics(
        'log_create',
        [
          'body' => [
            'logName' => $log_name,
            'date' => date(SasAnalitycsLogConstant::LOG_DATE_FORMAT),
            'origin' => SasAnalitycsLogConstant::LOG_ORIGIN_BACK,
            'content' => $data,
          ],
        ]
      );
    }
    catch (PluginException $e) {
      $this->logger->get('SAS Aggregator Client')
        ->error('Error when trying to log LRM search analytics data.');
    }
  }

}
