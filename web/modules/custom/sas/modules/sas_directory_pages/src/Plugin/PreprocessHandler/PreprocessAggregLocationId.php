<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Preprocessing query param location id.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_aggreg_location_id",
 *  label = @Translation("Preprocess aggreg location id"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = -210
 * )
 */
class PreprocessAggregLocationId extends PreprocessHandlerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!isset($this->context['is_interfaced_aggregator']) || $this->context['is_interfaced_aggregator'] !== TRUE) {
      // PS non interfacé,
      // location_id ne peut être pris en compte.
      return;
    }

    if (count($this->variables['items']) < 2) {
      // Un seul lieu, pas utile de gérer le lieu actif.
      return;
    }

    // Get the location_id from query params.
    $request = $this->requestStack->getCurrentRequest();
    $location_id = intval($request->query->get('location_id'));
    if ($location_id <= 0) {
      return;
    }

    // Sorting our items for the requested one
    // to be the first and thus the default active one.
    // Please note that the location_id is not the item "lieu" index,
    // but an identifier given by the aggregator API
    // and stored in item during PreprocessAggregAlterations.
    usort($this->variables['items'], static function ($a, $b) use ($location_id) {
      if (($a['aggregator_location_id'] ?? 0) == $location_id) {
        return -1;
      }
      if (($b['aggregator_location_id'] ?? 0) == $location_id) {
        return 1;
      }
      return 0;
    });

  }

}
