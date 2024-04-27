<?php

namespace Drupal\sas_user\Service;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\sas_keycloak\Service\SasKeycloakUserInfo;
use Drupal\sas_user\Enum\SasRegulatorSync;
use Drupal\sas_user\Model\SasRegulatorSyncQueueItem;
use Drupal\user\UserInterface;

/**
 * Class SasRegulatorSyncHelper.
 *
 * Helper to make synchronisation action with aggregator on regulator account.
 *
 * @package Drupal\sas_user\Service
 */
class AggregRegulatorSyncHelper implements AggregRegulatorSyncInterface {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queue;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasApiClient;

  /**
   * @var \Drupal\sas_keycloak\Service\SasKeycloakUserInfo
   */
  protected SasKeycloakUserInfo $keycloakUserInfo;

  public function __construct(
    QueueFactory $queue,
    LoggerChannelFactoryInterface $logger,
    ClientEndpointPluginManager $sas_api_client,
    SasKeycloakUserInfo $keycloak_user_info
  ) {
    $this->queue = $queue;
    $this->logger = $logger;
    $this->sasApiClient = $sas_api_client;
    $this->keycloakUserInfo = $keycloak_user_info;
  }

  /**
   * {@inheritDoc}
   */
  public function buildRegulatorPayload(UserInterface $user, $habilitation = TRUE, string $old_email = NULL): array {

    $uuid = $this->keycloakUserInfo->getKeycloakUid($user, FALSE);
    $lastname = $user->get('field_sas_nom')->value;
    $firstname = $user->get('field_sas_prenom')->value;
    $email = $user->get('mail')->value;
    $nationalId = $user->get('field_sas_numero_cpx')->value;
    $fiedTerritoires = $user->get('field_sas_territoire')->referencedEntities();

    $sasTerritories = [];
    if (!empty($fiedTerritoires)) {
      foreach ($fiedTerritoires as $fiedTerritoire) {
        $sasTerritories[] = $fiedTerritoire->get('name')->value;
      }
    }

    if (empty($uuid) || empty($lastname) || empty($firstname) || empty($email)) {
      return [];
    }

    return [
      'uuid' => $uuid,
      'lastName' => $lastname ?? '',
      'firstName' => $firstname ?? '',
      'email' => $email ?? '',
      'nationalId' => $nationalId ?? '',
      'habilitation' => $habilitation,
      'emailBeforeUpdate' => $old_email ?? NULL,
      'sasTerritories' => $sasTerritories,
    ];

  }

  /**
   * {@inheritDoc}
   */
  public function makeRegulatorSync(string $endpoint_name, array $regulator_data): mixed {
    $response = NULL;

    try {
      $response = $this->sasApiClient->aggregator($endpoint_name, [
        'body' => $regulator_data,
      ]);
    }
    catch (PluginException $e) {
      $queue_item = SasRegulatorSyncQueueItem::createByData($regulator_data, $endpoint_name);
      $regulator_sync_queue = $this->queue->get(SasRegulatorSync::QUEUE_NAME);
      $regulator_sync_queue->createItem($queue_item);
      $this->logger->get('SAS Aggregator Client')->error('Error when trying to update a regulator on user account action (create/update/delete/login).');
    }

    return $response;
  }

}
