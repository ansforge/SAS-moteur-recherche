<?php

namespace Drupal\sas_user\Model;

/**
 * Class SasRegulatorSyncQueueItem.
 *
 * Model to manage item into sas_regulator_sync_queue_worker queue.
 *
 * @package Drupal\sas_user\Model
 */
class SasRegulatorSyncQueueItem {

  /**
   * @var string|null
   */
  protected ?string $uuid;

  /**
   * @var string|null
   */
  protected ?string $lastName;

  /**
   * @var string|null
   */
  protected ?string $firstName;

  /**
   * @var string|null
   */
  protected ?string $email;

  /**
   * @var string|null
   */
  protected ?string $nationalId;

  /**
   * @var bool
   */
  protected bool $habilitation;

  /**
   * @var string|null
   */
  protected ?string $oldEmail;

  /**
   * @var string
   */
  protected string $endpointPlugin;

  /**
   * Number of try already done.
   *
   * @var int
   */
  protected int $tryCount;

  /**
   * Create item by passing data as array.
   *
   * @param array $data
   *   Data to populate new object.
   * @param string $endpoint_plugin
   *   Endpoint plugin used to make failed call and to retry sync.
   *
   * @return \Drupal\sas_user\Model\SasRegulatorSyncQueueItem
   *   Item to store in sas_regulator_sync_queue_worker.
   */
  public static function createByData(array $data, string $endpoint_plugin): SasRegulatorSyncQueueItem {
    $queue_item = new self();
    $queue_item->uuid = $data['uuid'] ?? '';
    $queue_item->lastName = $data['lastName'] ?? '';
    $queue_item->firstName = $data['firstName'] ?? '';
    $queue_item->email = $data['email'] ?? '';
    $queue_item->nationalId = $data['nationalId'] ?? '';
    $queue_item->habilitation = $data['habilitation'] ?? TRUE;
    $queue_item->tryCount = $data['tryCount'] ?? 0;
    $queue_item->endpointPlugin = $endpoint_plugin;

    return $queue_item;
  }

  /**
   * Get number of try already done.
   *
   * @return int
   *   Number of try.
   */
  public function getTryCount(): int {
    return $this->tryCount ?? 0;
  }

  public function getEndpointPlugin(): string {
    return $this->endpointPlugin;
  }

  /**
   * Get data ready to be send into regulator synchronisation request.
   *
   * @return array
   *   Data to send in request payload.
   */
  public function getRegulatorSyncPayload(): array {

    if (empty($this->uuid) || empty($this->lastName) || empty($this->firstName) || empty($this->email)) {
      return [];
    }

    return [
      'uuid' => $this->uuid,
      'lastName' => $this->lastName ?? '',
      'firstName' => $this->firstName ?? '',
      'email' => $this->email ?? '',
      'nationalId' => $this->nationalId ?? '',
      'habilitation' => $this->habilitation,
      'emailBeforeUpdate' => $this->old_email ?? NULL,
    ];
  }

}
