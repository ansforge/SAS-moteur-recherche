<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_user\Enum\SasUserConstants;

/**
 * SAS users validate form helpers.
 *
 * This class should contains only methods with arguments :
 * (array &$form, FormStateInterface $form_state)
 */
trait SasUserValidateFormTrait {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getLimitValidationErrors())) {
      return parent::validateForm($form, $form_state);
    }

    $this->validateSasRequiredFields($form, $form_state);
    $this->validateSasReferences($form, $form_state);
    $this->validateRppsAdeli($form, $form_state);
    $this->validateDuplicatesReferences($form, $form_state);
    $this->validateSasTerritoires($form, $form_state);

    return parent::validateForm($form, $form_state);
  }

  /**
   * Validate SAS roles.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form_state object.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function validateSasReferences(array &$form, FormStateInterface $form_state) {
    $roles = array_column($form_state->getValue('role_change', []), 'target_id');
    if (in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $roles, TRUE)) {
      $attach_structures = array_filter(array_column($form_state->getValue('field_sas_attach_structures'), 'target_id'));
      $sos_medecin_assos = array_filter(array_column($form_state->getValue(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME), 'value'));
      $sas_cpt = array_filter(array_column($form_state->getValue(StructureConstant::CPTS_USER_FIELD_NAME), 'value'));

      if (empty($attach_structures) && empty($sos_medecin_assos) && empty($sas_cpt)) {
        $form_state->setError(
          $form['field_sas_attach_structures']['widget'][0]['target_id'],
          'Veuillez sélectionner au moins une "structure rattachée" ou une "association SOS Médecin" ou une "CPTS".'
        );
        $form_state->setError(
          $form[StructureConstant::SOS_MEDECIN_USER_FIELD_NAME]['widget'][0]['value'],
          'Veuillez sélectionner au moins une "structure rattachée" ou une "association SOS Médecin" ou une "CPTS".'
        );
        $form_state->setError(
          $form[StructureConstant::CPTS_USER_FIELD_NAME]['widget'][0]['value'],
          'Veuillez sélectionner au moins une "structure rattachée" ou une "association SOS Médecin" ou une "CPTS".'
        );
      }
    }
    else {
      $form_state->setValue('field_sas_attach_structures', []);
    }

    if (in_array(SnpConstant::SAS_DELEGATAIRE, $roles, TRUE)) {
      $related_pro = array_filter(array_column($form_state->getValue('field_sas_related_pro'), 'target_id'));
      $rel_structure_manager = array_filter(array_column($form_state->getValue('field_sas_rel_structure_manager'), 'target_id'));
      if (empty($related_pro) && empty($rel_structure_manager)) {
        $message = 'Saisissez un Professionnel de Santé lié et/ou un Gestionnaire de Structure lié '
          . 'dans au moins un des deux champs SAS - Professionnel(s) lié(s) ou SAS - Gestionnaire(s) de structure lié(s)';
        $form_state->setError($form['field_sas_related_pro']['widget'][0]['target_id'],
          $message);
        $form_state->setError($form['field_sas_rel_structure_manager']['widget'][0]['target_id'],
          $message);
      }
    }
    else {
      $form_state->setValue('field_sas_related_pro', []);
      $form_state->setValue('field_sas_rel_structure_manager', []);
    }
  }

  /**
   * Validate required fields.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form_state object.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  protected function validateSasRequiredFields(array &$form, FormStateInterface $form_state) {
    $roles = array_column($form_state->getValue('role_change', []), 'target_id');
    $roles_territoires = ['sas_ioa', 'sas_regulateur_osnp', 'sas_gestionnaire_de_comptes'];
    if (!empty(array_intersect($roles_territoires, $roles))) {
      if (empty($form_state->getValue('field_sas_territoire'))) {
        $form_state->setErrorByName('field_sas_territoire', 'Veuillez renseigner un territoire.');
      }
    }
    $rpps_adeli = $form_state->getValue('field_sas_rpps_adeli');
    if (empty($rpps_adeli[0]['value'])) {
      if (in_array(SnpConstant::SAS_EFFECTEUR, $roles, TRUE)) {
        $form_state->setErrorByName('field_sas_rpps_adeli', $this->t('Veuillez saisir un numéro RPPS/ADELI'));
      }
      else {
        if (empty($form_state->getValue('field_sas_prenom')[0]['value'])) {
          $form_state->setErrorByName('field_sas_prenom', $this->t('Veuillez saisir un prénom'));
        }
        if (empty($form_state->getValue('field_sas_nom')[0]['value'])) {
          $form_state->setErrorByName('field_sas_nom', $this->t('Veuillez saisir un nom'));
        }
      }
    }
  }

  /**
   * Validate duplicate references.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form_state object.
   */
  protected function validateDuplicatesReferences(array &$form, FormStateInterface $form_state) {
    $duplicate_message = 'Des valeurs identiques de "@label" ont été saisies.';
    $roles = array_column($form_state->getValue('role_change', []), 'target_id');

    if (in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $roles, TRUE)) {
      $fiches_structures = array_filter(array_column($form_state->getValue('field_sas_attach_structures'), 'target_id'));
      if (!empty($fiches_structures) && array_unique($fiches_structures) !== $fiches_structures) {
        $duplicates = array_diff_key($fiches_structures, array_unique($fiches_structures));
        foreach ($fiches_structures as $key => $fiche) {
          if (in_array($fiche, $duplicates)) {
            $form_state->setError($form['field_sas_attach_structures']['widget'][$key]['target_id'],
              $this->t($duplicate_message, ['@label' => 'Structure(s) rattachée(s)']));
          }
        }
      }
    }

    if (in_array(SnpConstant::SAS_DELEGATAIRE, $roles, TRUE)) {
      $fiches_related_pro = array_filter(array_column($form_state->getValue('field_sas_related_pro'), 'target_id'));
      if (!empty($fiches_related_pro) && array_unique($fiches_related_pro) !== $fiches_related_pro) {
        $duplicates = array_diff_key($fiches_related_pro, array_unique($fiches_related_pro));
        foreach ($fiches_related_pro as $key => $fiche) {
          if (in_array($fiche, $duplicates)) {
            $form_state->setError($form['field_sas_related_pro']['widget'][$key]['target_id'],
              $this->t($duplicate_message, ['@label' => 'Professionnel(s) lié(s)']));
          }
        }
      }

      $fiches_related_structures = array_filter(array_column($form_state->getValue('field_sas_rel_structure_manager'), 'target_id'));
      if (!empty($fiches_related_structures) && array_unique($fiches_related_structures) !== $fiches_related_structures) {
        $duplicates = array_diff_key($fiches_related_structures, array_unique($fiches_related_structures));
        foreach ($fiches_related_structures as $key => $fiche) {
          if (in_array($fiche, $duplicates)) {
            $form_state->setError($form['field_sas_rel_structure_manager']['widget'][$key]['target_id'],
              $this->t($duplicate_message, ['@label' => 'Gestionnaire(s) de structure lié(s)']));
          }
        }
      }
    }
  }

  /**
   * Validate territoires.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form_state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  protected function validateSasTerritoires(array &$form, FormStateInterface $form_state) {
    // phpcs:disable
    $unknow_error = $this->t('Les départements des éléments ciblés ne sont pas associable à un territoire. Veuillez contactez votre administrateur.'); //NOSONAR
    $forbidden_error = $this->t("L'utilisateur que vous souhaitez créer exerce en dehors de votre territoire de compétences. Merci de renseigner une localisation faisant partie de votre territoire."); //NOSONAR
    // phpcs:enable
    $roles = array_column($form_state->getValue('role_change', []), 'target_id');
    /** @var \Drupal\user\UserInterface $currentUserEntity */
    $currentUserEntity = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    $current_user_roles = $currentUserEntity->getRoles(TRUE);
    $compiled_territoires = array_column($this->compileTerritoires($form_state), 'target_id');

    $sas_admin_roles = array_intersect(SasUserConstants::SAS_ADMIN_ROLES, $current_user_roles);

    $current_user_territoires = array_values($this->termStorage->getQuery()->accessCheck()->condition('vid', 'sas_territoire')->execute());
    if (empty($sas_admin_roles)) {
      $current_user_territoires = array_column($currentUserEntity->get('field_sas_territoire')
        ->getValue(), 'target_id');
    }

    if (in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $roles, TRUE)) {
      $structures_values = array_column($form_state->getValue('field_sas_attach_structures'), 'target_id');
      if (!empty(array_filter($structures_values))) {
        $fiches_structures = $this->nodeStorage->loadMultiple(array_filter($structures_values));
        foreach ($fiches_structures as $structure) {
          $territoire = $this->sasTerritories->sasGetTerritoriesFromNode($structure);
          $error = NULL;
          if (empty($territoire)) {
            $error = $unknow_error;
          }
          elseif (empty(array_intersect($territoire, $current_user_territoires)) && empty(array_intersect($current_user_territoires, $compiled_territoires))) {
            $error = $forbidden_error;
          }

          if (!empty($error)) {
            $form_state->setErrorByName(
              'field_sas_attach_structures][' . (int) current(array_keys($structures_values, $structure->id())) . '][target_id',
              $error
            );
          }
        }
      }
    }

    if (in_array(SnpConstant::SAS_DELEGATAIRE, $roles, TRUE)) {
      $related_pro_values = array_column($form_state->getValue('field_sas_related_pro'), 'target_id');
      if (!empty(array_filter($related_pro_values))) {
        $fiches_related_pro = $this->entityTypeManager->getStorage('user')->loadMultiple(array_filter($related_pro_values));
        foreach ($fiches_related_pro as $related_pro) {
          $territoire = array_column($related_pro->get('field_sas_territoire')->getValue(), 'target_id');
          $error = NULL;
          if ($related_pro->get('field_sas_territoire')->isEmpty()) {
            $error = $unknow_error;
          }
          elseif (empty(array_intersect($territoire, $current_user_territoires)) && empty(array_intersect($current_user_territoires, $compiled_territoires))) {
            $error = $forbidden_error;
          }

          if (!empty($error)) {
            $form_state->setErrorByName(
              'field_sas_related_pro][' . (int) current(array_keys($related_pro_values, $related_pro->id())) . '][target_id',
              $error
            );
          }
        }
      }

      $related_structures_values = array_column($form_state->getValue('field_sas_rel_structure_manager'), 'target_id');
      if (!empty(array_filter($related_structures_values))) {
        $fiches_related_structures = $this->entityTypeManager->getStorage('user')->loadMultiple(array_filter($related_structures_values));
        foreach ($fiches_related_structures as $related_structures) {
          $territoire = array_column($related_structures->get('field_sas_territoire')->getValue(), 'target_id');
          $error = NULL;
          if ($related_structures->get('field_sas_territoire')->isEmpty()) {
            $error = $unknow_error;
          }
          elseif (empty(array_intersect($territoire, $current_user_territoires)) && empty(array_intersect($current_user_territoires, $compiled_territoires))) {
            $error = $forbidden_error;
          }

          if (!empty($error)) {
            $form_state->setErrorByName(
              'field_sas_rel_structure_manager][' . (int) current(array_keys($related_structures_values, $related_structures->id())) . '][target_id',
              $error
            );
          }
        }
      }
    }

  }

  /**
   * Validate rpps/adeli.
   *
   * @param array $form
   *   The id to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form_state object.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  protected function validateRppsAdeli(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getEntity();
    $roles = array_column($form_state->getValue('role_change', []), 'target_id');

    if (in_array(SnpConstant::SAS_EFFECTEUR, $roles, TRUE)) {
      $rpps_adeli = $form_state->getValue('field_sas_rpps_adeli');

      if (!empty($rpps_adeli[0]['value']) && $user->get('field_sas_rpps_adeli')->value != $rpps_adeli[0]['value']) {
        /** @var \Drupal\sas_user\Service\SasEffectorHelperInterface $user_helper */
        $effector_helper = \Drupal::service('sas_user.effector_helper');
        $user_id = $effector_helper->getEffectorIdParts($rpps_adeli[0]['value']);
        $prefix = $user_id['prefix'];
        $rpps_adeli = $user_id['id'];

        if (!$effector_helper->isExistingContentByRppsAdeli($rpps_adeli, $prefix)) {
          $form_state->setErrorByName('field_sas_rpps_adeli',
            $this->t("Veuillez saisir un rpps/adeli valide ou choisir un élément dans la liste."));
        }

        if ($effector_helper->userRppsAdeliExists($rpps_adeli)) {
          $form_state->setErrorByName('field_sas_rpps_adeli',
            $this->t("Le numéro RPPS/ADELI saisi est déjà utilisé par un autre compte effecteur sur le SAS"));
        }
      }
    }
  }

}
