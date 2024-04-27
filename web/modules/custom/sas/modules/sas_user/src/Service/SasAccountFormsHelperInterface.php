<?php

namespace Drupal\sas_user\Service;

/**
 * Provides account form helpers.
 */
interface SasAccountFormsHelperInterface {

  /**
   * Get form render array for password renew button if necessary.
   *
   * @return array|null
   *   Render array for renew button if necessary.
   */
  public function getRenewButtonOverride(): ?array;

  /**
   * Get necessary data and ask keycloak to send password renew email.
   */
  public function makeRenewEmailSendAction(): void;

}
