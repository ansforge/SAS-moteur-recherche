<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Drupal\sas_directory_pages\Entity\Feature\AggregatorLinkInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocessing PS to check if in the aggregator database.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_aggreg_perimeter",
 *  label = @Translation("Preprocess aggreg perimeter"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = 1000
 * )
 */
class PreprocessAggregPerimeter extends PreprocessHandlerBase {

  use StringTranslationTrait;

  /**
   * Roles allowed to see aggreg warning messages.
   */
  const AGGREG_ERROR_DISPLAY_ROLES = [
    SnpConstant::SAS_ADMINISTRATEUR,
  ];

  /**
   * Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $accountProxy;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->messenger = $container->get('messenger');
    $instance->accountProxy = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Default values.
    $this->context['aggreg_pro_id'] = NULL;
    $this->context['is_interfaced_aggregator'] = NULL;

    // First check if the aggreg/editor data display is disabled on the page.
    if ($this->node instanceof SasSnpHelperInterface) {
      $editor_display_disabled = $this->node->isEditorDisplayDisabledOnPage();
      if ($editor_display_disabled === TRUE) {
        return;
      }
    }

    if ($this->node instanceof AggregatorLinkInterface) {
      // When dealing with the aggregator API
      // PS' SAS directory content build cannot be cached,
      // data may change at any time in the aggregator API
      // (Interfaced or not, calendar data...)
      $this->variables['#cache']['max-age'] = 0;
      // We may later disable dynamic page cache too
      // \Drupal::service('page_cache_kill_switch')->trigger();
      // Getting status from Aggreg API.
      $this->context['aggreg_pro_id'] = $this->node->getAggregPsProId();
      $this->context['is_interfaced_aggregator'] = $this->node->isAggregPractitionerExist();
      // Warn if could not determine if the PS is interfaced or not.
      if ($this->context['is_interfaced_aggregator'] === NULL) {
        if (array_intersect(self::AGGREG_ERROR_DISPLAY_ROLES, $this->accountProxy->getRoles())) {
          $this->messenger->addWarning($this->t('We could not determine if the professional is interfaced with an editor, data may be missing.'));
        }
      }
    }
  }

}
