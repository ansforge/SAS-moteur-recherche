<?php

namespace Drupal\sas_api_client\Commands;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\simple_cron\Plugin\SimpleCronPluginManager;
use Drush\Commands\DrushCommands;

/**
 * A drush command to run manually cron that update
 * "SAS - Interfacé éditeur" field in "Professionnel de santé" bundle.
 *
 * @package Drupal\sas_api_client\Commands
 */
class IsInterfacedCommands extends DrushCommands {

  /**
   * The plugin manager.
   *
   * @var \Drupal\simple_cron\Plugin\SimpleCronPluginManager
   */
  protected SimpleCronPluginManager $cronPluginManager;

  /**
   * The IsInterfacedCommands constructor.
   *
   * @param \Drupal\simple_cron\Plugin\SimpleCronPluginManager $cronPluginManager
   *   The plugin manager.
   */
  public function __construct(SimpleCronPluginManager $cronPluginManager) {
    parent::__construct();
    $this->cronPluginManager = $cronPluginManager;
  }

  /**
   * Drush command that runs InterfacedCron to update "SAS - Interfacé éditeur"
   * field inside "Professionnel de santé" bundle.
   *
   * @command sas_api_client:is_interfaced
   * @option start_date
   *   Uppercase the message.
   * @option end_date
   *   Reverse the message.
   * @usage sas_api_client:is_interfaced --start_date="2022-09-01T10:00:00+02:00" --end_date="2022-11-25T10:00:00+02:00"
   *   update table sas_is_interfaced.
   */
  public function isInterfaced($options = ['start_date' => NULL, 'end_date' => NULL]) {
    try {
      $this->cronPluginManager
        ->createInstance('sas_interfaced_cron')
        ->process([
          'start_date' => $options['start_date'],
          'end_date' => $options['end_date'],
        ]);
    }
    catch (PluginException $e) {
      return;
    }
  }

}
