<?php

namespace Drupal\sas_user\Form;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * SAS users profile form helpers.
 */
trait SasUserProfileFormTrait {

  /**
   * Check if the current entity assumed of type user is a SAS user.
   *
   * @return bool
   *   True if it is a SAS user.
   */
  protected function isSasUser() {
    return ($this->entity->hasField('field_sas_user_sas') && $this->entity->get('field_sas_user_sas')->value == 1);
  }

  /**
   * Deny form access to non SAS users.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  protected function denySasFormAccess() {
    if (!$this->isSasUser()) {
      throw new AccessDeniedHttpException("Cet utilisateur ne fait pas partie des utilisateurs SAS.");
    }
  }

}
