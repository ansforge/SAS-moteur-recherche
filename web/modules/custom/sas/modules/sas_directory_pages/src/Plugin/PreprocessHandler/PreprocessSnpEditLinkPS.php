<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Drupal\sas_snp\Service\AvailabilityBlockProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocessing SNP Edit Link on PS
 * Needed here because PS may have multiple places,
 * no longer done sas_snp_entity_view().
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_snp_edit_link_ps",
 *  label = @Translation("Preprocess SNP edit link on PS"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = -220
 * )
 */
class PreprocessSnpEditLinkPS extends PreprocessHandlerBase {

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
    if (isset($this->context["is_interfaced_aggregator"]) && $this->context["is_interfaced_aggregator"] === TRUE) {
      return;
    }
    // When the PS is not interfaced with an editor,
    // we still have nodes corresponding
    // to each item/place in context's nodeItemsByNid.
    $places_nodes = array_values($this->context["nodeItemsByNid"]);
    foreach ($places_nodes as $key => $node) {
      $block_build = $this->availabilityBlockProvider->getAvailabilityBlock($node);
      if ($block_build && !str_contains($block_build['#class'], 'is-disabled')) {
        $this->variables['items'][$key]['item_link_availability_page'] = $block_build;
      }
    }
  }

}
