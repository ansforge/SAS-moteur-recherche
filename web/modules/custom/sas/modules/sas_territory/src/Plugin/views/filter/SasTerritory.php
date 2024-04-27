<?php

namespace Drupal\sas_territory\Plugin\views\filter;

/**
 * @file
 * Definition of SAS Territoire field address filter.
 */

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters by given territoires options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("sas_territory_filter")
 */
class SasTerritory extends InOperator {

  use StringTranslationTrait;

  /**
   * Drupal TermStorage service.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->termStorage = $container->get('entity_type.manager')->getStorage('taxonomy_term');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('SAS Territoires');
    $this->definition['options callback'] = [$this, 'generateTerritoires'];
  }

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    if (empty($this->value) || empty($this->tableAlias) || empty($this->realField)) {
      return;
    }
    $this->ensureMyTable();
    $values = array_values($this->value);
    $group = $this->query->setWhereGroup('OR');
    foreach ($values as $value) {
      $snippet = '(SELECT field_sas_postal_codes_value FROM taxonomy_term__field_sas_postal_codes
      WHERE entity_id = :value) LIKE CONCAT(\'%\', ' . $this->tableAlias . '.' . $this->realField . ', \'%\')';
      $this->query->addWhereExpression($group, $snippet, [
        ':value' => $value,
      ]);
    }
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Helper function that generates the territoires.
   *
   * @return array
   */
  public function generateTerritoires() {
    $terms = $this->termStorage->loadTree('sas_territoire');
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return $options;
  }

}
