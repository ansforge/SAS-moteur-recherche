<?php

namespace Drupal\sas_user\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SasDelegataireHelper.
 *
 * Specific helper for "SAS - Délégataire" user accounts.
 *
 * @package Drupal\sas_user\Service
 */
class SasDelegataireHelper implements SasDelegataireHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public function getEffectorDelegations(int $user_id): array {
    $ids = [];

    try {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($user_id);
    }
    catch (\Exception $e) {
      return [];
    }

    if ($user->hasField('field_sas_related_pro')) {
      foreach ($user->get('field_sas_related_pro')->referencedEntities() as $delegation) {
        $ids[] = $delegation->field_sas_rpps_adeli->value;
      }
    }

    return $ids;
  }

}
