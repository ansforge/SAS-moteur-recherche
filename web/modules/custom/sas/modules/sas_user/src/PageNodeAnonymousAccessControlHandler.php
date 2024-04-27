<?php

namespace Drupal\sas_user;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sas_snp\SnpNodeAccessControlHandler;
use Drupal\sas_user\Enum\SasUserConstants;

/**
 * Class CustomVocabularyAccessControlHandler.
 *
 * @SuppressWarnings(PHPMD)
 */
class PageNodeAnonymousAccessControlHandler extends SnpNodeAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();
    $account = $this->prepareUser($account);

    // Access menu per user.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity instanceof EntityInterface
      && $entity->bundle() == SasUserConstants::SAS_PAGE
      && $account->isAnonymous()
      && $entity->get('field_visible_only_logged_user')->value
      && !$this->pscUser->isValid()) {
      $result = AccessResult::forbidden()->addCacheableDependency($entity);
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

}
