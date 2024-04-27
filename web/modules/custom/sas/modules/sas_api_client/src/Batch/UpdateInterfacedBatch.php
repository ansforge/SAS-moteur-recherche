<?php

namespace Drupal\sas_api_client\Batch;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class UpdateInterfacedPsBatch.
 */
class UpdateInterfacedBatch {

  /**
   * Operation callback.
   *
   * @param array $nat_ids
   *   Array of RPPS IDs to update in the is_interfaced table.
   * @param array|\DrushBatchContext $context
   *   Standard batch context.
   */
  public static function updateInterfaced(array $nat_ids, array|\DrushBatchContext &$context) {
    if (empty($nat_ids)) {
      return;
    }

    /** @var \Drupal\sas_snp\Service\InterfacedHelperInterface $interfaced_helper */
    $interfaced_helper = \Drupal::service('sas_snp.interfaced_helper');
    /** @var \Drupal\sas_user\Service\SasEffectorHelper $effector_helper */
    $effector_helper = \Drupal::service('sas_user.effector_helper');
    /** @var \Drupal\sas_search_index\Service\SasSearchIndexHelper $search_index_helper */
    $search_index_helper = \Drupal::service('sas_search_index.helper');

    foreach ($nat_ids as $nat_id) {
      $interfaced_helper->save($nat_id);
      $id_parts = $effector_helper->getEffectorIdParts($nat_id);
      if (empty($id_parts)) {
        continue;
      }

      $nids = $effector_helper->getContentByRppsAdeli(
        rpps_adeli_num: $id_parts['id'],
        prefix: $id_parts['prefix']
      );

      if (!empty($nids)) {
        foreach ($nids as $nid) {
          // Force indexation of professional sheet.
          $search_index_helper->indexSpecificItem($nid);
        }
      }
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }

    $context['results']['count'] += count($nat_ids);
  }

  /**
   * Finish Callback.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   */
  public static function finished(bool $success, array $results) {
    $message = t('An error occurred while processing');
    $type = MessengerInterface::TYPE_ERROR;
    if ($success) {
      $message = t('@count professionals updated.', [
        '@count' => $results['count'],
      ]);
      $type = MessengerInterface::TYPE_STATUS;
    }
    \Drupal::messenger()
      ->addMessage($message, $type);
  }

}
