<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Drupal\sas_snp\Enum\SnpConstant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocessing PS MS Santé email display.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_ps_email_ms_sante",
 *  label = @Translation("Preprocess PS MS Santé email display"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = -250
 * )
 */
class PreprocessEmailMsSante extends PreprocessHandlerBase {

  const MS_SANTE_EMAIL_DISPLAY_ROLES = [
    SnpConstant::SAS_ADMINISTRATEUR,
    SnpConstant::SAS_ADMINISTRATEUR_NATIONAL,
    SnpConstant::SAS_IOA,
    SnpConstant::SAS_REGULATEUR_OSNP,
  ];

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->accountProxy = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->node->addCacheContexts(['user']);
    if (
      array_intersect(self::MS_SANTE_EMAIL_DISPLAY_ROLES, $this->accountProxy->getRoles())
      && isset($this->context['nodeItemsByNid'])
    ) {
      foreach ($this->variables['items'] as $key => $item) {
        if (
          isset($item['nid'])
          && isset($this->context['nodeItemsByNid'][$item['nid']])
        ) {
          $node = $this->context['nodeItemsByNid'][$item['nid']];
          $field_email_mssante = $node->get('field_email_mssante')->first();
          if ($field_email_mssante) {
            $emails = explode('|', $field_email_mssante->getString());
            $this->variables['items'][$key]['emails_mssante'] = $emails;
          }
        }
      }
    }
  }

}
