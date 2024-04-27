<?php

namespace Drupal\sas_api_server\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Sas Snp routes.
 */
class SasApiServerController extends ControllerBase {

  /**
   * The sas_api_client service.
   *
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected $sasApiClientService;

  /**
   * SAS Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->sasApiClientService = $container->get('sas_api_client.service');
    $instance->sasCoreService = $container->get('sas_core.service');

    return $instance;
  }

  /**
   * Builds the response.
   */
  public function call(Request $request, string $api_type, string $endpoint, string $id = NULL) {
    $api_type = str_replace('-', '_', $api_type);
    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Accès refusé.');
    }

    $definitions = $this->sasApiClientService->getGroupedDefinitions()[$api_type] ?? [];

    if (!isset($definitions[$endpoint])) {
      $this->getLogger('sas_api.error')->error(
        $this->t('Endpoint not found for SAS-API plugin : @api_type - @plugin', [
          '@api_type' => $api_type,
          '@plugin' => $endpoint,
        ])
      );
      throw new NotFoundHttpException('Endpoint not found.');
    }

    if ($request->getMethod() !== $definitions[$endpoint]['method']) {
      $this->getLogger('sas_api.error')->error(
        $this->t('Bad request method for SAS-API plugin : @api_type - @plugin', [
          '@api_type' => $api_type,
          '@plugin' => $endpoint,
        ])
      );
      throw new BadRequestHttpException('Bad request endpoint method.');
    }

    if ($definitions[$endpoint]['exposed'] !== TRUE) {
      $this->getLogger('sas_api.error')->error(
        $this->t('Access denied for SAS-API plugin (not exposed) : @api_type - @plugin', [
          '@api_type' => $api_type,
          '@plugin' => $endpoint,
        ])
      );
      throw new AccessDeniedHttpException('Access denied.');
    }

    // All query_string & post data is forwarded to the SAS API client endpoint.
    $params = [
      'query' => $request->query->all(),
      'body' => str_contains($request->headers->get('content-type'), 'multipart/form-data')
        ? $request->request->all()
        : json_decode($request->getContent(), TRUE),
      'tokens' => [
        'id' => $id,
      ],
      'access_check' => TRUE,
    ];

    $data = $this->sasApiClientService->$api_type($endpoint, $params);
    $output = [
      'message' => 'ok',
      'data' => $data,
    ];
    return new JsonResponse($output);
  }

}
