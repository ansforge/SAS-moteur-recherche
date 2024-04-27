<?php

namespace Drupal\sas_user\Plugin\SimpleCron;

use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Single cron.
 *
 * @SimpleCron(
 *   id = "sas_user_password_expiration",
 *   label = @Translation("SAS - password expiration", context = "Simple cron")
 * )
 */
class SasUserCronPasswordExpiration extends AbstractCronPasswordExpiration {

  protected const PASSWORD_EXPIRED_RULES = [
    '6' => [
      SnpConstant::SAS_ADMINISTRATEUR,
      SnpConstant::SAS_ADMINISTRATEUR_NATIONAL,
      SnpConstant::SAS_GESTIONNAIRE_DE_COMPTES,
      SnpConstant::SAS_REGULATEUR_OSNP,
      SnpConstant::SAS_EFFECTEUR,
      SnpConstant::SAS_IOA,
      SnpConstant::SAS_GESTIONNAIRE_STRUCTURE,
      SnpConstant::SAS_DELEGATAIRE,
    ],
  ];

  protected const MAIL_MODULE = 'sas_user';
  protected const MAIL_KEY = 'sas_user_password_expired_';

  /**
   * SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasCoreService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sasCoreService = $container->get('sas_core.service');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($rule) {
    $query = parent::getQuery($rule);
    $query->condition('field_sas_user_sas', TRUE);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->sasCoreService->isSasContext()) {
      parent::process();
    }
  }

  public function rule(): array {
    $rule = static::PASSWORD_EXPIRED_RULES;
    $config_psc = $this->configFactory->get('sas_config.prosante_connect');
    $checkbox_login = $config_psc->get('config')['psc_Intermediate_page']['enabled'];
    if ($checkbox_login) {
      $new_rule_regulators = static::PASSWORD_EXPIRED_RULES['6'];
      $new_rule_regulators = array_diff($new_rule_regulators, [SnpConstant::SAS_REGULATEUR_OSNP]);
      $new_rule_regulators = array_diff($new_rule_regulators, [SnpConstant::SAS_IOA]);
      $rule['6'] = $new_rule_regulators;
    }
    return $rule;
  }

}
