<?php

namespace Drupal\sas_snp\Service;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

/**
 * Class AvailabilityBlockProvider.
 *
 * Helper providing availibility block.
 *
 * @package Drupal\sas_snp\Service
 */
class AvailabilityBlockProvider implements AvailabilityBlockProviderInterface {

  /**
   * AccountProxy object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $accountProxy;

  /**
   * The block manager.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   */
  protected BlockManagerInterface $blockManager;

  /**
   * Undocumented function.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The current account.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager.
   */
  public function __construct(
      AccountProxyInterface $accountProxy,
      BlockManagerInterface $blockManager
    ) {
    $this->accountProxy = $accountProxy;
    $this->blockManager = $blockManager;
  }

  /**
   * {@inheritDoc}
   */
  public function getAvailabilityBlock(NodeInterface $node): array|NULL {
    $block_instance = $this->blockManager->createInstance('availability_link_block', ['node' => $node]);
    if (!$block_instance->access($this->accountProxy)) {
      return NULL;
    }
    return $block_instance->build();
  }

}
