<?php

namespace Drupal\sas_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * This block is used to display SAS Searchbar block.
 *
 * @Block(
 *  id = "sas_searchbar",
 *  admin_label = @Translation("SAS Searchbar"),
 *  category = @Translation("SAS")
 * )
 */
class SearchBar extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '<div id="sas-header-searchbar"></div>',
      '#attached' => ['library' => ['sas_vuejs/header-searchbar']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access search');
  }

}
