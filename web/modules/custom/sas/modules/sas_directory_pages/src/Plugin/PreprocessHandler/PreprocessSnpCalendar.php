<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocessing SNP Calendar.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_snp_calendar",
 *  label = @Translation("Preprocess PS aggregator calendar"),
 *  bundles = {
 *    "finess_institution",
 *    "health_institution",
 *    "professionnel_de_sante",
 *    "service_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante",
 *    "annuaire_etablissement_service",
 *    "annuaire_etablissement_simple",
 *  },
 *  context = "sas",
 *  priority = -210
 * )
 */
class PreprocessSnpCalendar extends PreprocessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    // $instance->payloadHelper = $container->get('sas_directory_pages.payload_helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Si interfaçage editeur actif et non désactivé, pas de calendrier SNP.
    if (isset($this->context['is_interfaced_aggregator']) && $this->context['is_interfaced_aggregator'] === TRUE) {
      return;
    }

    if (!$this->node instanceof SasSnpHelperInterface) {
      return;
    }

    /*
     * Even if NULL, the existence of snp_calendar_schedule_id is important,
     * it will display an empty SNP calendar.
     * We need a cache dependency on the sas_time_slots because at the first step it may not have a schedule_id yet,
     * the schedule_id will exist after the first slot creation.
     */
    if (isset($this->context['nodeItemsByNid'])) {
      foreach ($this->variables['items'] as $key => $item) {
        if (isset($item['nid']) && isset($this->context['nodeItemsByNid'][$item['nid']])) {
          $node = $this->context['nodeItemsByNid'][$item['nid']];
          if ($node instanceof SasSnpHelperInterface) {
            $this->variables['items'][$key]['snp_calendar_schedule_id'] = $node->getScheduleId() ?? NULL;
          }
          // $payload = $this->payloadHelper->payload($node);
          // $this->variables['#attached']['drupalSettings']['sas_vuejs']['parameters'][$item['nid']] = [
          // 'recipient' => $payload ?? [],
          // ];
        }
      }
    }
    else {
      $this->variables['snp_calendar_schedule_id'] = $this->node->getScheduleId() ?? NULL;
      // $payload = $this->payloadHelper->payload($this->node);
      // $this->variables['#attached']['drupalSettings']['sas_vuejs']['parameters'] = [
      // 'recipient' => $payload ?? [],
      // ];
    }

    // $this->variables['#attached']['library'][] = 'sas_vuejs/directory-snp-calendar';
  }

}
