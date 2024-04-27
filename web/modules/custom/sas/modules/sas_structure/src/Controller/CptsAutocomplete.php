<?php

namespace Drupal\sas_structure\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\sas_structure\Service\FinessStructureHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CptsAutocomplete.
 *
 * Controller to provide autocomplete for CPTS.
 *
 * @package Drupal\sas_structure\Controller
 */
class CptsAutocomplete extends ControllerBase {

  /**
   * Finess Structeure service.
   *
   * @var \Drupal\sas_structure\Service\FinessStructureHelperInterface
   */
  protected FinessStructureHelperInterface $finessStructureHelper;

  /**
   * CptsAutocomplete constructor.
   *
   * @param \Drupal\sas_structure\Service\FinessStructureHelperInterface $finess_StructureHelper
   */
  public function __construct(FinessStructureHelperInterface $finess_StructureHelper) {
    $this->finessStructureHelper = $finess_StructureHelper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_structure.finess_structure_helper')
    );
  }

  /**
   * Controller action to get CPTS list from searched text.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response of corresponding associations.
   */
  public function autocompleteCptsList(Request $request): JsonResponse {

    $search = $request->query->get('q') ?? '';
    $search = Xss::filter($search);

    $structures = $this->finessStructureHelper->searchStructures('cpts', $search);
    $list = [];
    if (!empty($structures)) {
      foreach ($structures as $structure) {
        $list[] = [
          'value' => sprintf('%s (%s)', $structure['title'], $structure['finess']),
          'label' => sprintf('%s (%s)', $structure['title'], $structure['finess']),
        ];
      }
    }

    return new JsonResponse($list);
  }

}
