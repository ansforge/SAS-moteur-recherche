<?php

namespace Drupal\sas_user\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the base for SAS User Territory entity_autocomplete field widget.
 */
abstract class SasUserTerritoryAutocompleteBase extends EntityReferenceAutocompleteWidget {

  /**
   * SAS Territoires service.
   *
   * @var \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface
   */
  protected SasGetTermCodeCitiesInterface $sasTerritoires;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sasTerritoires = $container->get('term.territory');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        // Remove the added territoire select item.
        unset($values['add_more'], $values['territoire']);

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        foreach ($values as $delta => &$value) {
          $value['_original_delta'] = $delta;
        }

        usort($values, static function ($a, $b) {
          return SortArray::sortByKeyInt($a, $b, '_weight');
        });
      }

      // Let the widget massage the submitted values.
      $values = $this->massageFormValues($values, $form, $form_state);

      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $item->_original_delta ?? $delta;
        unset($item->_original_delta, $item->_weight);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $element = parent::formMultipleElements($items, $form, $form_state);
    $options = [];
    $territoires = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('sas_territoire');
    foreach ($territoires as $territoire) {
      $options[$territoire->tid] = $territoire->name;
    }
    $element['territoire'] = [
      '#id' => 'territoire-' . $this->fieldDefinition->getName(),
      '#title' => $this->t('Territoire autocomplétion'),
      '#type' => 'select',
      '#options' => $options,
      '#empty_option' => 'Tous les territoires',
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [static::class, 'updateTerritoires'],
        'wrapper' => 'sas_territoire_' . $element['#field_name'],
        'effect' => 'fade',
      ],
      '#wrapper_attributes' => ['class' => ['sas-territoire-autocomplete-select']],
    ];

    $element['#theme'] = 'sas_territoire_field_multiple_value_form';
    $element['#prefix'] = '<div class="sas-territoire-autocomplete-wrapper" id="sas_territoire_' . $element['#field_name'] . '">' . $element['#prefix'];
    $element['#suffix'] .= '</div>';
    return $element;
  }

  /**
   * Ajax callback for the "Territoires" select option.
   *
   * @return array
   *   The form updated element render array.
   */
  public static function updateTerritoires(array $form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement() && preg_match('/^territoire\-(.*)$/', $form_state->getTriggeringElement()['#id'], $matches)) {
      return $form[$matches[1]];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()
      && $form_state->getTriggeringElement()['#id'] === 'territoire-' . $this->fieldDefinition->getName()) {
      $form_state->set('territoire-' . $this->fieldDefinition->getName(), $form_state->getTriggeringElement()['#value'] ?? NULL);
    }
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

}
