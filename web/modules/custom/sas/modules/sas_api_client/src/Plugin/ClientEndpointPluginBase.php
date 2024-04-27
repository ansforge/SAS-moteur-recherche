<?php

namespace Drupal\sas_api_client\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Sas api endpoint plugin plugins.
 */
abstract class ClientEndpointPluginBase extends PluginBase implements ClientEndpointPluginInterface {

  use StringTranslationTrait;

  protected const SAS_API_ERROR_CODES = [
    'default' => "Erreur inconnue. veuillez contacter l'administrateur du site.",
    401 => "Accès refusé, veuillez contacter l'administrateur du site.",
    500 => "Erreur système... veuillez contacter l'administrateur du site.",
    404 => "Endpoint non trouvé... veuillez contacter l'administrateur du site.",
  ];

  /**
   * The current SAS api endpoint uri.
   *
   * @var string
   */
  protected $sasApiEndpoint;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Logger\LoggerChannelInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Guzzle http client object.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The current request method.
   *
   * @var string
   */
  protected $requestMethod;

  /**
   * The sas_api settings object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $sasApiSettings;

  /**
   * Drupal settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The current environment settings.
   *
   * @var array
   */
  protected $sasApiEnvironment;

  /**
   * The current request params.
   *
   * @var array
   */
  protected array $requestParams;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new ApiEndpointPluginBase object.
   *
   * @param array $configuration
   *   The currentplugin configuration.
   * @param $plugin_id
   *   The current plugin ID.
   * @param $plugin_definition
   *   The current plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal configFactory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Drupal loggerChannelFactory service.
   * @param \GuzzleHttp\Client $http_client
   *   Guzzle HTTP client.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal site Settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   Drupal current user.
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface $pscUser
   *   PSC current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    Client $http_client,
    Settings $settings,
    AccountProxyInterface $accountProxy,
    SasKeycloakPscUserInterface $pscUser,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('sas_api.' . $plugin_id);
    $this->httpClient = $http_client;
    $this->settings = $settings;
    $this->currentUser = $accountProxy;
    $this->sasApiSettings = $this->configFactory->get('sas_config.api_settings');
    $this->pscUser = $pscUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->setEnvironment();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('http_client'),
      $container->get('settings'),
      $container->get('current_user'),
      $container->get('sas_keycloak.psc_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Replace custom endpoint tokens with config or provided values.
   *
   * @return string
   *   The endpoint uri built.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildEndpointUri() {
    $this->requestParams['tokens']['version'] = static::API_VERSION;
    $sasApiEndpoint = $this->getPluginDefinition()['endpoint'];
    // Grab all unreplaced tokens.
    $pattern = '/(?=\{((?:[^{}]++|\{(?1)\})++)\})/';
    preg_match_all($pattern, $sasApiEndpoint, $tokens);
    // Replace them with provided values.
    foreach ($tokens[1] as $token) {
      if (empty($this->requestParams['tokens'][$token] ?? NULL)) {
        $message = $this->t('Missing required token in SAS-API endpoint :<br>Plugin : @plugin_id<br>Token : @token', [
          '@plugin_id' => $this->getPluginId(),
          '@token' => '{' . $token . '}',
        ]);
        throw new PluginException($message);
      }
      $sasApiEndpoint = str_replace('{' . $token . '}', $this->requestParams['tokens'][$token], $sasApiEndpoint);
    }

    return $sasApiEndpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function request(array $params = []) {
    $this->requestParams = $params;

    $this->sasApiEndpoint = $this->sasApiEnvironment['api_url'] . $this->buildEndpointUri();
    $method = $this->getPluginDefinition()['method'];
    $query = $this->buildQueryString();
    $url = Url::fromUri($this->sasApiEndpoint);
    $curlOptions = [CURLOPT_RETURNTRANSFER => 1];

    $requestOptions = [
      'curl' => $curlOptions,
      'query' => $query,
      'headers' => [],
      'timeout' => 30,
    ];

    if ($this->sasApiEnvironment['htaccess'] ?? NULL) {
      $requestOptions['headers']['Authorization'] = 'Basic ' . base64_encode($this->sasApiEnvironment['htaccess']);
    }

    $this->addAuthentication($requestOptions);
    $requestOptions['headers']['Content-Type'] = 'application/json';
    $this->addHeaders($requestOptions);
    $bodyMethods = ['POST', 'PUT', 'PATCH'];
    if (in_array($method, $bodyMethods)) {
      $this->buildBody($requestOptions);
    }

    try {
      $result = $this->httpClient->request(
        $method,
        $url->toString(),
        $requestOptions
      );

      if (!preg_match('/^2([0-9]{2})$/', $result->getStatusCode())) {
        $error = $this->t('SAS Api WS Error - HTTP Code @http_code : @output', [
          '@http_code' => $result->getStatusCode() ?? 0,
          '@output' => $result->getReasonPhrase(),
        ]);

        $this->logger->error($error);
        throw new PluginException($result->getReasonPhrase(), $result->getStatusCode());
      }

      $content = $result->getBody()->getContents();
      if (($this->requestParams['json'] ?? FALSE) !== TRUE) {
        $content = Json::decode($content ?? '{}');
        return $content;
      }
      return $content;
    }
    catch (GuzzleException $e) {
      $message = Message::toString($e->getRequest());
      $error = $this->t('SAS Api WS Error - HTTP Code @http_code : @output', [
        '@http_code' => $e->getCode(),
        '@output' => $message,
      ]);

      $this->logger->error($error);

      throw new PluginException($e->getMessage(), $e->getCode(), $e);
    }
    catch (\Exception $e) {
      $error = $this->t('SAS Api WS Error - HTTP Code @http_code : @output', [
        '@http_code' => $e->getCode(),
        '@output' => $e->getMessage(),
      ]);

      $this->logger->error($error);

      throw new PluginException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Add the request option body according to the endpoint definition.
   *
   * @param array $requestOptions
   *   The request options array.
   */
  protected function buildBody(array &$requestOptions) {
    $body = $this->pluginDefinition['body'];
    $body = array_merge($body, $this->requestParams['body'] ?? []);
    $requestOptions['body'] = Json::encode($body);
  }

  /**
   * Build the request query_string according to the endpoint definition.
   *
   * @return array
   *   Return the query array if any.
   */
  protected function buildQueryString() {
    $query = $this->getPluginDefinition()['query'] ?? [];
    return array_merge($query, $this->requestParams['query'] ?? []);
  }

  /**
   * Set the authentication headers.
   *
   * @param array $requestOptions
   *   The request options array.
   */
  protected function addAuthentication(array &$requestOptions) {
    $requestOptions['headers']['Authorization'] = sprintf('Bearer %s', $this->requestParams['api_token'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSnpByScheduleId(int $schedule_id) {
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'type' => SnpConstant::SAS_TIME_SLOTS,
        'field_sas_time_slot_schedule_id' => $schedule_id,
      ]);
    $node = FALSE;
    if (!empty($nodes)) {
      $node = reset($nodes);
    }

    return $node;
  }

  /**
   * Set the current SAS Api environment.
   */
  abstract protected function setEnvironment();

  /**
   * Set the authentication headers.
   *
   * @param array $requestOptions
   *   The request options array.
   */
  abstract protected function addHeaders(array &$requestOptions);

}
