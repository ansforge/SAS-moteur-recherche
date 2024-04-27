<?php

namespace Drupal\sas_snp\Batch;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class batch UpdateSnpInterfacedBatch.
 */
class UpdateSnpInterfacedBatch {

  /**
   * Operation callback.
   *
   * @param array $nodes
   *   Array of entity node.
   * @param array|\DrushBatchContext $context
   *   Standard batch context.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public static function updateInterfacedSnp(array $nodes, array|\DrushBatchContext &$context) {

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['all'] = count($nodes);
      $context['sandbox']['node_ids'] = $nodes;
    }

    $max_items = 50;
    $slice = array_slice($context['sandbox']['node_ids'], $context['sandbox']['progress'], $max_items, TRUE);

    if (!empty($slice)) {

      foreach ($slice as $node) {
        try {
          $node->set('field_is_interfaced', TRUE);
          $node->setNewRevision(FALSE);
          $node->save();
          \Drupal::service('sas_search_index.helper')->indexSpecificItem($node->id());
        }
        catch (\Exception $e) {
          \Drupal::logger('sas_snp_batch')->error($e->getMessage());
        }

        $context['sandbox']['progress']++;
      }
    }

    $context['finished'] = 1;
    $context['results']['nb'] = $context['sandbox']['progress'];
    if ($context['sandbox']['progress'] < $context['sandbox']['all']) {
      $context['message'] = t(
        '@count/@all node.', [
          '@count' => $context['results']['nb'],
          '@all' => $context['sandbox']['all'],
        ]
      );
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['all'];
    }
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
    $type = MessengerInterface::TYPE_ERROR;
    $message = t('An error occurred during execution');
    if ($success) {
      $message = t('@count node processed.', [
        '@count' => $results['nb'],
      ]);
      $type = MessengerInterface::TYPE_STATUS;
    }
    \Drupal::messenger()
      ->addMessage($message, $type);
  }

}
