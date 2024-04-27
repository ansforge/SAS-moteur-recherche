<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\user\UserInterface;

/**
 * Form trait for the The sas user form.
 *
 * @internal
 */
trait SasUserFieldsFormTrait {

  /**
   * Provide some form alter for SAS user form.
   *
   * @param array $element
   *   THe current element structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function afterBuildSasUserForm(array $element, FormStateInterface $form_state) {
    $element['field_ville']['widget'][0]['target_id']['#title'] = $this->t("Ville d'exercice");
    $element['field_ville']['widget'][0]['target_id']['#required'] = FALSE;

    $territorial_states = [
      'enabled' => [
        [':input[id="edit-role-change-sas-administrateur"]' => ['checked' => TRUE]],
        [':input[id="edit-role-change-sas-administrateur-national"]' => ['checked' => TRUE]],
        [':input[id="edit-role-change-sas-gestionnaire-de-comptes"]' => ['checked' => TRUE]],
        [':input[id="edit-role-change-sas-effecteur"]' => ['checked' => TRUE]],
        [':input[id="edit-role-change-sas-gestionnaire-de-structure"]' => ['checked' => TRUE]],
        [':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => TRUE]],
        [':input[id="edit-role-change-sas-ioa"]' => ['checked' => TRUE]],
        [':input[id="edit-role-change-sas-delegataire"]' => ['checked' => TRUE]],
      ],
    ];

    if ($element['role_change']['widget']['sas_referent_territorial'] ?? NULL) {
      $element['role_change']['widget']['sas_referent_territorial']['#states'] = $territorial_states;
    }

    return $element;
  }

  /**
   * Generate the #states elements for SAS user form fields.
   *
   * @param array $form
   *   THe current form structure array.
   */
  public function buildSasUserFieldReferences(array &$form) {

    $userReferences = ['field_sas_related_pro', 'field_sas_rel_structure_manager'];
    foreach ($userReferences as $reference) {
      if (!isset($form[$reference])) {
        continue;
      }
      $childrens = Element::children($form[$reference]['widget']);
      foreach ($childrens as $key) {
        if ($key !== 'add_more' && $key !== 'territoire' && $form[$reference]['widget'][$key]['target_id']['#default_value']) {
          $user = $form[$reference]['widget'][$key]['target_id']['#default_value'];
          if ($user instanceof UserInterface) {
            $ville = current($user->get('field_ville')->referencedEntities());
            $value = t('@nom @prenom @ville (@uid)', [
              '@nom' => $user->get('field_sas_nom')->value,
              '@prenom' => $user->get('field_sas_prenom')->value,
              '@ville' => $ville ? '- ' . $ville->label() : '',
              '@uid' => $user->id(),
            ]);
            $form[$reference]['widget'][$key]['target_id']['#default_value'] = $value;
            $form[$reference]['widget'][$key]['target_id']['#process_default_value'] = FALSE;
          }

        }
      }
    }

    $nodeReferences = ['field_sas_fiche_professionnel', 'field_sas_attach_structures'];
    foreach ($nodeReferences as $reference) {
      if (!isset($form[$reference])) {
        continue;
      }
      $childrens = Element::children($form[$reference]['widget']);
      foreach ($childrens as $key) {
        if ($key !== 'add_more' && $key !== 'territoire' && $form[$reference]['widget'][$key]['target_id']['#default_value']) {
          $node = $form[$reference]['widget'][$key]['target_id']['#default_value'];
          if ($node instanceof NodeInterface) {
            $value = t('@label @ville (@uid)', [
              '@label' => $node->label(),
              '@ville' => '- ' . $node->get('field_address')->locality,
              '@uid' => $node->id(),
            ]);
            $form[$reference]['widget'][$key]['target_id']['#default_value'] = $value;
            $form[$reference]['widget'][$key]['target_id']['#process_default_value'] = FALSE;
          }

        }
      }
    }
  }

  /**
   * Generate the #states elements for SAS user form fields.
   *
   * @param array $form
   *   THe current form structure array.
   */
  public function buildSasUserFieldStates(array &$form) {
    if ($form['field_sas_territoire'] ?? NULL) {
      $form['field_sas_territoire']['#states'] = [
        'visible' => [
          [':input[id="edit-role-change-sas-gestionnaire-de-comptes"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-effecteur"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-gestionnaire-de-structure"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-ioa"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-delegataire"]' => ['checked' => TRUE]],
        ],
        'disabled' => [
          ':input[id="edit-role-change-sas-gestionnaire-de-comptes"]' => ['checked' => FALSE],
          ':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => FALSE],
          ':input[id="edit-role-change-sas-ioa"]' => ['checked' => FALSE],
        ],
        'enabled' => [
          [':input[id="edit-role-change-sas-gestionnaire-de-comptes"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-ioa"]' => ['checked' => TRUE]],
        ],
        'required' => [
          [':input[id="edit-role-change-sas-gestionnaire-de-comptes"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-ioa"]' => ['checked' => TRUE]],
        ],
      ];
    }

    if ($form['field_sas_departement'] ?? NULL) {
      $form['field_sas_departement']['#states'] = [
        'visible' => [
          [':input[id="edit-role-change-sas-effecteur"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-gestionnaire-de-structure"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-ioa"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-delegataire"]' => ['checked' => TRUE]],
        ],
        'disabled' => [
          ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => FALSE],
          ':input[id="edit-role-change-sas-gestionnaire-de-structure"]' => ['checked' => FALSE],
          ':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => FALSE],
          ':input[id="edit-role-change-sas-ioa"]' => ['checked' => FALSE],
          ':input[id="edit-role-change-sas-delegataire"]' => ['checked' => FALSE],
        ],
      ];
    }

    if ($form['field_sas_numero_cpx']['widget'][0]['value'] ?? NULL) {
      $form['field_sas_numero_cpx']['widget'][0]['value']['#states'] = [
        'visible' => [
          [':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => TRUE]],
          [':input[id="edit-role-change-sas-ioa"]' => ['checked' => TRUE]],
        ],
        'disabled' => [
          ':input[id="edit-role-change-sas-regulateur-osnp"]' => ['checked' => FALSE],
          ':input[id="edit-role-change-sas-ioa"]' => ['checked' => FALSE],
        ],
      ];
    }

    if ($form['field_sas_related_pro'] ?? NULL) {
      $form['field_sas_related_pro']['#states'] = [
        'visible' => [
          [':input[id="edit-role-change-sas-delegataire"]' => ['checked' => TRUE]],
        ],
        'disabled' => [
          ':input[id="edit-role-change-sas-delegataire"]' => ['checked' => FALSE],
        ],
        'required' => [
          [':input[id="edit-role-change-sas-delegataire"]' => ['checked' => TRUE]],
        ],
      ];
    }

    if ($form['field_sas_rpps_adeli'] ?? NULL) {
      $form['field_sas_rpps_adeli']['#states'] = [
        'required' => [
          ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => TRUE],
        ],
        'enabled' => [
          ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => TRUE],
        ],
      ];
    }

    if ($form['field_sas_nom'] ?? NULL) {
      $form['field_sas_nom']['#states'] = [
        'disabled' => [
          ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => FALSE],
        ],
      ];
    }

    if ($form['field_sas_prenom'] ?? NULL) {
      $form['field_sas_prenom']['#states'] = [
        'disabled' => [
          ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => FALSE],
        ],
      ];
    }

    $structure_states = [
      'visible' => [
        ':input[id="edit-role-change-sas-gestionnaire-de-structure"]' => ['checked' => TRUE],
      ],
      'disabled' => [
        ':input[id="edit-role-change-sas-gestionnaire-de-structure"]' => ['checked' => FALSE],
      ],
    ];

    if ($form['field_sas_attach_structures'] ?? NULL) {
      $form['field_sas_attach_structures']['#states'] = $structure_states;
    }
    if ($form[StructureConstant::SOS_MEDECIN_USER_FIELD_NAME] ?? NULL) {
      $form[StructureConstant::SOS_MEDECIN_USER_FIELD_NAME]['#states'] = $structure_states;
    }
    if ($form[StructureConstant::CPTS_USER_FIELD_NAME] ?? NULL) {
      $form[StructureConstant::CPTS_USER_FIELD_NAME]['#states'] = $structure_states;
    }

    if ($form['field_sas_rel_structure_manager'] ?? NULL) {
      $form['field_sas_rel_structure_manager']['#states'] = [
        'visible' => [
          ':input[id="edit-role-change-sas-delegataire"]' => ['checked' => TRUE],
        ],
        'disabled' => [
          ':input[id="edit-role-change-sas-delegataire"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[id="edit-role-change-sas-delegataire"]' => ['checked' => TRUE],
        ],
      ];
    }
  }

  /**
   * Retrieve territories from user object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @return array
   *   The user territories.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function compileTerritoires(FormStateInterface $form_state) {
    $territoires = array_filter(array_column($form_state->getValue('field_sas_territoire', []), 'target_id'));
    $roles = array_column($form_state->getValue('role_change', []), 'target_id');
    if (in_array(SnpConstant::SAS_EFFECTEUR, $roles)) {
      $ps_values = array_filter(array_column($form_state->getValue('field_sas_fiche_professionnel', []), 'target_id'));
      if (!empty($ps_values)) {
        $fiches_ps = $this->nodeStorage->loadMultiple($ps_values);
        foreach ($fiches_ps as $ps) {
          $territoires[] = current($this->sasTerritories->sasGetTerritoriesFromNode($ps));
        }
      }
    }

    if (in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $roles)) {
      $structures_values = array_filter(array_column($form_state->getValue('field_sas_attach_structures', []), 'target_id'));
      if (!empty($structures_values)) {
        $fiches_structures = $this->nodeStorage->loadMultiple($structures_values);
        foreach ($fiches_structures as $structure) {
          $territoires[] = current($this->sasTerritories->sasGetTerritoriesFromNode($structure));
        }
      }
    }

    if (in_array(SnpConstant::SAS_DELEGATAIRE, $roles)) {
      $related_pro_values = array_filter(array_column($form_state->getValue('field_sas_related_pro', []), 'target_id'));
      if (!empty($related_pro_values)) {
        $fiches_related_pro = $this->entityTypeManager->getStorage('user')->loadMultiple($related_pro_values);
        foreach ($fiches_related_pro as $related_pro) {
          $territoires = array_merge($territoires,
            array_column($related_pro->get('field_sas_territoire')->getValue(), 'target_id'));
        }
      }

      $related_structures_values = array_filter(array_column($form_state->getValue('field_sas_rel_structure_manager', []), 'target_id'));
      if (!empty($related_structures_values)) {
        $fiches_related_structures = $this->entityTypeManager->getStorage('user')->loadMultiple($related_structures_values);
        foreach ($fiches_related_structures as $related_structures) {
          $territoires = array_merge($territoires,
            array_column($related_structures->get('field_sas_territoire')->getValue(), 'target_id'));
        }
      }
    }

    $territoires = array_filter(array_unique($territoires));

    return array_map(static fn ($value) => ['target_id' => $value], $territoires);
  }

}
