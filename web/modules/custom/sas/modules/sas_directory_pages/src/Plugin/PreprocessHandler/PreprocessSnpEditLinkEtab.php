<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Drupal\sas_snp\Service\AvailabilityBlockProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocessing SNP Edit Link on Etab.
 * No longer done in sas_snp_entity_view()
 * for uniformity with PreprocessSnpEditLinkPS.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_snp_edit_link_etab",
 *  label = @Translation("Preprocess SNP edit link on Etab"),
 *  bundles = {
 *    "health_institution",
 *    "finess_institution",
 *    "service_de_sante"
 *  },
 *  themes = {
 *    "annuaire_etablissement_service",
 *    "annuaire_etablissement_simple",
 *  },
 *  context = "sas",
 *  priority = -220
 * )
 */
class PreprocessSnpEditLinkEtab extends PreprocessHandlerBase {

  /**
   * AvailabilityBlockProvider object.
   *
   * @var \Drupal\sas_snp\Service\AvailabilityBlockProviderInterface
   */
  protected AvailabilityBlockProviderInterface $availabilityBlockProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->availabilityBlockProvider = $container->get('sas_snp.availability_block_provider');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $block_build = $this->availabilityBlockProvider->getAvailabilityBlock($this->context['node']);
    if ($block_build && !str_contains($block_build['#class'], 'is-disabled')) {
      $this->variables['link_availability_page'] = $block_build;
    }
  }

}
