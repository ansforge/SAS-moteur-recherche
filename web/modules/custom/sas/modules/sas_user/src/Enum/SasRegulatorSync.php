<?php

namespace Drupal\sas_user\Enum;

/**
 * Class SasRegulatorSync.
 *
 * Provide constants for regulator synchronisation feature.
 *
 * @package Drupal\sas_user\Enum
 */
final class SasRegulatorSync {

  /**
   * Max number of try before writing error.
   */
  const MAX_TRY_COUNT = 2;

  /**
   * Queue name to store new synchronisation try to do.
   */
  const QUEUE_NAME = 'sas_regulator_sync_queue_worker';

  /**
   * Plugin name for regulator sync on login.
   */
  const LOGIN_ENDPOINT = 'login_regulator';

  /**
   * Plugin name for regulaton sync on crud action.
   */
  const ACCOUNT_CRUD_ENDPOINT = 'create_regulator';

}
