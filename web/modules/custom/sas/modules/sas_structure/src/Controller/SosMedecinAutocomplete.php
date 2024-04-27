<?php

namespace Drupal\sas_structure\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\sas_structure\Service\SosMedecinHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SosMedecinAutocomplete.
 *
 * Controller to provide autocomplete for SOS Médecin associations.
 *
 * @package Drupal\sas_structure\Controller
 */
class SosMedecinAutocomplete extends ControllerBase {

  /**
   * SOS Médecin helper service.
   *
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * SosMedecinAutocomplete constructor.
   *
   * @param \Drupal\sas_structure\Service\SosMedecinHelperInterface $sos_medecin_helper
   */
  public function __construct(SosMedecinHelperInterface $sos_medecin_helper) {
    $this->sosMedecinHelper = $sos_medecin_helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_structure.sos_medecin')
    );
  }

  /**
   * Controller action to get SOS médecin association list from searched text.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response of corresponding associations.
   */
  public function autocompleteAssociationList(Request $request): JsonResponse {

    $search = $request->query->get('q') ?? '';
    $search = Xss::filter($search);

    $matches = $this->sosMedecinHelper->getAssociationList($search);

    $list = [];
    if (!empty($matches)) {
      foreach ($matches as $id => $label) {
        $list[] = [
          'value' => sprintf('%s (%s)', $label, $id),
          'label' => sprintf('%s (%s)', $label, $id),
        ];
      }
    }

    return new JsonResponse($list);
  }

}
