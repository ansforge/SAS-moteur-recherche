<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\node\NodeInterface;
use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocessing PS options from effecteur dashboard.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_ps_options",
 *  label = @Translation("Preprocess ps options from effecteur"),
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
class PreprocessPsOptions extends PreprocessHandlerBase {

  use SasSnpHelperTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\sas_config\SasApiConfigManagerInterface
   */
  protected $sasConfigManager;

  /**
   * Drupal date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected DateFormatter $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sasConfigManager = $container->get('sas_config.service');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Get labels from configuration stored in SAS API.
    $services_configs = $this->sasConfigManager->getConfigByGroup('services');
    $services_label = [];
    $haystack = ['reorientation', 'sas_participation'];
    if (!empty($services_configs)) {
      foreach ($services_configs as $services_config) {
        if (!empty($services_config['name']) && in_array($services_config['name'], $haystack)) {
          $services_label[$services_config['name']] = $services_config['value'];
        }
      }
    }

    $nodeItemsByNid = $this->context['nodeItemsByNidForPsPage'] ?: NULL;
    if (isset($this->context['nodeItemsByNid'])) {
      $nodeItemsByNid = $this->context['nodeItemsByNid'];
    }
    if (isset($nodeItemsByNid)) {
      foreach ($this->variables['items'] as $key => $item) {
        if (isset($item['nid']) && isset($nodeItemsByNid[$item['nid']])) {
          $node = $nodeItemsByNid[$item['nid']];
          // "Field Informations complémentaires sas_time_slots"
          if ($node instanceof SasSnpHelperInterface) {
            $this->variables['items'][$key]['snp_edit_information'] = $node->getInfoEdit() ?? NULL;
          }

          // "J’accepte de prendre des patients en surnuméraire"
          $forfait_reo_enabled = $node->getEffecteurData('forfait_reo_enabled');
          if ($forfait_reo_enabled === "1" && isset($services_label["reorientation"]["pictogram_label"])) {
            $this->variables['items'][$key]['option_forfait_reorientation'] = $services_label["reorientation"]["pictogram_label"];
          }

          if ($node->getSasTimeSlots() instanceof NodeInterface) {
            $date = $this->dateFormatter->format($node->getSasTimeSlots()->getChangedTime(), 'custom', 'd/m/Y');
            $this->variables['items'][$key]['publication_date'] = [
              '#markup' => "<p> Dernière mise à jour : $date </p>",
              '#cache' => [
                'tags' => $node->getSasTimeSlots()->getCacheTagsToInvalidate(),
              ],
            ];
          }
          // "Je participe au forfait de réorientation des urgences"
          $participation_sas = $node->getEffecteurData('participation_sas');
          if ($participation_sas === "1" && isset($services_label["sas_participation"]["pictogram_label"])) {
            $finess = $item["finess_value"];
            $node = !empty($finess) ? $this->getContentByFiness($finess) : NULL;
            $isNotCdsNode = !empty($node) ? !\Drupal::service('sas_structure.helper')->isCds($node) : TRUE;
            $this->variables['items'][$key]['option_participation_sas'] = $isNotCdsNode ? $services_label["sas_participation"]["pictogram_label"] : NULL;

          }
        }
      }
    }

  }

}
