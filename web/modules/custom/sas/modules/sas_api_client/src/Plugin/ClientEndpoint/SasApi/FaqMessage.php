<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;
use GuzzleHttp\Psr7\Utils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ClientEndpointPlugin(
 *   id = "faq_message",
 *   label = @Translation("SAS-API Faq send message endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/faq/message",
 *   api_user = "write",
 *   method = "POST",
 *   exposed = TRUE,
 *   body = {
 *     "email_address": NULL,
 *     "firstname": NULL,
 *     "lastname": NULL,
 *     "message": NULL,
 *     "phone_number": NULL,
 *     "role": NULL,
 *     "territory": NULL,
 *     "topic": NULL
 *   }
 * )
 */
class FaqMessage extends AbstractSasClientPluginBase {

  /**
   * Drupal current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $currentRequest;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function addHeaders(array &$requestOptions) {
    parent::addHeaders($requestOptions);
    unset($requestOptions['headers']['Content-Type']);
  }

  /**
   * {@inheritdoc}
   *
   * Override default method to provide multipart form data support.
   */
  protected function buildBody(array &$requestOptions) {
    $body = $this->pluginDefinition['body'];
    $body = array_merge($body, $this->requestParams['body'] ?? []);
    $requestOptions['multipart'] = [];
    foreach ($body as $key => $value) {
      $requestOptions['multipart'][] = [
        'name' => $key,
        'contents' => $value,
      ];
    }

    $keys = ['attachment', 'attachment_1', 'attachment_2'];
    foreach ($keys as $key) {
      $file = $this->currentRequest->files->get($key);
      if ($file) {
        $requestOptions['multipart'][] = [
          'name' => $key,
          'contents' => Utils::tryFopen($file->getRealPath(), 'r'),
          'filename' => $file->getClientOriginalName(),
          'headers' => [
            'Content-Type' => 'application/octet-stream',
          ],
        ];
      }
    }
  }

}
