<?php

declare(strict_types = 1);

namespace Drupal\sas_search\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\sas_search\Service\SasCptsSearchManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a SAS search cpts Resource.
 *
 * @RestResource(
 *   id = "sas_search_cpts_effector",
 *   label = @Translation("SAS search - CPTS Effectors"),
 *   description = @Translation("Get effector for a given CPTS"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/search/cpts/effectors"
 *   }
 * )
 */
class SasCptsEffectorsSearch extends ResourceBase {

  const QUERY_PARAM_CACHE_CONTEXT = [
    'url.query_args:finess',
    'url.query_args:qty',
    'url.query_args:page',
    'url.query_args:sort',
    'url.query_args:center_lat',
    'url.query_args:center_lon',
    'url.query_args:rand_id',
  ];

  const CACHE_LIFETIME = 3600;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request;

  /**
   * Sas Search manager.
   *
   * @var \Drupal\sas_search\Service\SasCptsSearchManager
   */
  protected SasCptsSearchManager $sasCptsSearchManager;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\sas_search\Service\SasCptsSearchManager $sas_cpts_search_manager
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    RequestStack $request_stack,
    SasCptsSearchManager $sas_cpts_search_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );
    $this->request = $request_stack->getCurrentRequest();
    $this->sasCptsSearchManager = $sas_cpts_search_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('request_stack'),
      $container->get('sas_search.cpts.manager')
    );
  }

  /**
   * Responds GET requests.
   *
   * Get CPTS effector's list.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function get(): JsonResponse {
    return (new JsonResponse())
      ->setData($this->sasCptsSearchManager->makeCptsEffectorsQuery());
  }

}
