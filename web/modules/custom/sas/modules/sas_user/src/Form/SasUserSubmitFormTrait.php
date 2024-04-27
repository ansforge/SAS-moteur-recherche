<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sas_snp\Enum\SnpConstant;

/**
 * SAS users submit form helpers.
 */
trait SasUserSubmitFormTrait {

  /**
   * Set firstname and lastname for effector account based on RPPS/ADELI.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function setEffectorData(FormStateInterface $form_state) {
    $roles = array_column($form_state->getValue('role_change', []), 'target_id');
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getEntity();

    if (in_array(SnpConstant::SAS_EFFECTEUR, $roles, TRUE)) {
      $rpps_adeli = $form_state->getValue('field_sas_rpps_adeli');

      if (!empty($rpps_adeli[0]['value']) && $user->get('field_sas_rpps_adeli')->value != $rpps_adeli[0]['value']) {
        /** @var \Drupal\sas_user\Service\SasEffectorHelperInterface $user_helper */
        $user_helper = \Drupal::service('sas_user.effector_helper');
        $id_parts = $user_helper->getEffectorIdParts($rpps_adeli[0]['value']);
        $node_ids = $user_helper->getContentByRppsAdeli($id_parts['id'], $id_parts['prefix']);

        if (!empty($node_ids)) {
          /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
          $entity_type_manager = \Drupal::service('entity_type.manager');
          $nodes_ps = $entity_type_manager->getStorage('node')
            ->loadMultiple($node_ids);
        }

        if (!empty($nodes_ps)) {
          $this->setAccountFieldValue($form_state, $nodes_ps);
        }

      }
    }
  }

  /**
   * Set account field value base on list of "Professionnel de sante" nodes.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State to add data to.
   * @param \Drupal\node\NodeInterface[] $sheets
   *   Sheets to get data from.
   */
  protected function setAccountFieldValue(FormStateInterface $form_state, array $sheets) {
    // Set data into field_sas_fiche_professionnel.
    // @todo Remove this part when migration to RPSS/ADELI done.
    $fiches_ps = [];
    foreach ($sheets as $sheet) {
      $fiches_ps[]['target_id'] = $sheet->get('nid')
        ->getValue()[0]['value'];
    }
    $form_state->setValue('field_sas_fiche_professionnel', $fiches_ps);

    // Set firstname and lastname.
    /** @var \Drupal\node\NodeInterface $node */
    $node = reset($sheets);
    if ($node->hasField('field_nom') && !$node->get('field_nom')->isEmpty()) {
      $form_state->setValue('field_sas_nom', $node->get('field_nom')->getValue());
    }
    if ($node->hasField('field_prenom') && !$node->get('field_prenom')->isEmpty()) {
      $form_state->setValue('field_sas_prenom', $node->get('field_prenom')
        ->getValue());
    }
  }

}
