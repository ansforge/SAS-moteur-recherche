<?php

namespace Drupal\sas_snp\Plugin\SimpleCron;

use Drupal\simple_cron\Plugin\SimpleCronPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Check if has SNP slots for next three days.
 *
 * @SimpleCron(
 *   id = "sas_has_snp_cron",
 *   label = @Translation("SAS - Has SNP", context = "Simple cron")
 * )
 */
class SasSnpCron extends SimpleCronPluginBase {

  /**
   * The SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreService
   */
  protected $sasCoreService;

  /**
   * The SAS core service.
   *
   * @var \Drupal\sas_snp\Commands\SasUpdateSnpCommands
   */
  protected $sasUpdateSnpCommands;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sasCoreService = $container->get('sas_core.service');
    $instance->sasUpdateSnpCommands = $container->get('sas_snp.cli.update_snp');

    return $instance;
  }

  public function process(): void {
    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Cette tâche doit être lancée dans le context SAS.');
    }

    $this->sasUpdateSnpCommands->processItems();
  }

}
