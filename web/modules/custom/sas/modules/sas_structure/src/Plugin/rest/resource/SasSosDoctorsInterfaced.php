<?php

namespace Drupal\sas_structure\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\sas_core\Plugin\SasResourceBase;
use Drupal\sas_search_index\Service\SasSearchIndexHelperInterface;
use Drupal\sas_structure\Service\SosDoctorsIsInterfacedHelper;
use Drupal\sas_structure\Service\SosMedecinHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides Sas sos doctors interfaced.
 *
 * @RestResource(
 *   id = "sas_sos_doctors_interfaced",
 *   label = @Translation("SAS Structure - Sas sos doctors interfaced"),
 *   uri_paths = {
 *     "create" = "/sas/api/drupal/sos-doctors/interfaced"
 *   }
 * )
 */
class SasSosDoctorsInterfaced extends SasResourceBase {

  use LoggerChannelTrait;

  /**
   * @var \Drupal\sas_structure\Service\SosDoctorsIsInterfacedHelper
   */
  private SosDoctorsIsInterfacedHelper $sosDoctorsIsInterfacedHelper;

  /**
   * @var \Drupal\sas_structure\Service\SosMedecinHelper
   */
  private SosMedecinHelper $sosMedecinHelper;

  /**
   * @var \Drupal\sas_search_index\Service\SasSearchIndexHelperInterface
   */
  protected SasSearchIndexHelperInterface $sasSearchIndexHelper;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    SosDoctorsIsInterfacedHelper $sosDoctorsIsInterfacedHelper,
    SosMedecinHelper $sosMedecinHelper,
    SasSearchIndexHelperInterface $sasSearchIndexHelper
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );
    $this->sosDoctorsIsInterfacedHelper = $sosDoctorsIsInterfacedHelper;
    $this->sosMedecinHelper = $sosMedecinHelper;
    $this->sasSearchIndexHelper = $sasSearchIndexHelper;

  }

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
      $container->get('sas_structure.sos_doctors_interfaced_helper'),
      $container->get('sas_structure.sos_medecin'),
      $container->get('sas_search_index.helper')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns SAS effector settings data.
   *
   * @throws \Exception
   */
  public function post(Request $request): Response {
    $data = Json::decode($request->getContent());
    if (empty($data['siret'])) {
      return new JsonResponse(
        [
          'code' => Response::HTTP_BAD_REQUEST,
          'message' => 'the siret number is not valid',
        ],
        Response::HTTP_BAD_REQUEST
      );
    }
    return $this->saveSiret($data['siret']);
  }

  /**
   * Process POST request.
   *
   * @param string $siret
   *   Siret number.
   *   HTTP request code.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns HTTP response.
   *
   * @throws \Exception
   */
  private function saveSiret(string $siret): Response {

    // Save siret in sas_siret_interfaced table.
    try {
      $this->sosDoctorsIsInterfacedHelper->save($siret);
    }
    catch (\Exception $e) {
      return new JsonResponse(
        [
          'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
          'message' => 'the siret number exists',
        ],
        Response::HTTP_INTERNAL_SERVER_ERROR
          );
    }

    // Force indexing pfg is_interfaced.
    $nodes_pfg = $this->sosMedecinHelper->getAssociationPfg($siret, FALSE);
    if ($nodes_pfg) {
      foreach ($nodes_pfg as $id) {
        try {
          $this->sasSearchIndexHelper->indexSpecificItem($id);
        }
        catch (\Exception $e) {
          $this->getLogger('sas_structure.pfd-indexing')
            ->error('Error while indexing structure pfd.');
        }
      }
    }

    $get_siret = $this->sosDoctorsIsInterfacedHelper->get($siret);
    return new ModifiedResourceResponse($get_siret, Response::HTTP_CREATED);
  }

}
