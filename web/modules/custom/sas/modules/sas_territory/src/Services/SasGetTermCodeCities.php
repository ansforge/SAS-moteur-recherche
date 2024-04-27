<?php

namespace Drupal\sas_territory\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\sas_snp\Enum\SnpConstant;

/**
 * Class SasGetTermCodeCities.
 *
 * Provides method to get geographical data.
 *
 * @package Drupal\sas_territory\Services
 * @SuppressWarnings(PHPMD)
 */
class SasGetTermCodeCities implements SasGetTermCodeCitiesInterface {

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructs a database object.
   *
   *   Entity Type Manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $database, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->cache = $cache;
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetTermIdByName(array $names, array $vocabularies = []) {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $storage->getQuery()->accessCheck();

    if ($vocabularies) {
      $query->condition('vid', $vocabularies, 'IN');
    }
    $query->condition('name', $names, 'IN');
    $results = $query->execute();

    if (empty($results)) {
      return [];
    }

    return $results;
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetTermNamesByIds(array $ids) {
    return $this->database->select('taxonomy_term_data', 'term')
      ->fields('term', ['name'])
      ->condition('tid', $ids, 'IN')
      ->execute()->fetchCol();
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetRegionIsoCodes() {

    if ($cached = $this->cache->get('sas:region:iso_codes')) {
      $codes = $cached->data;
    }
    if (empty($codes)) {
      $codes = $this->database->select('taxonomy_term__field_iso_code', 'iso')
        ->fields('iso', ['field_iso_code_value'])
        ->distinct()
        ->execute()->fetchCol();
      $this->cache->set('sas:region:iso_codes', $codes, CacheBackendInterface::CACHE_PERMANENT, ['sas_region_iso_codes']);
    }

    return $codes;
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetRegionCountyNumbers(string $region_iso2) {

    $cid = sprintf('sas:region:%s:county_numbers', $region_iso2);
    if ($cached = $this->cache->get($cid)) {
      $county_numbers = $cached->data;
    }

    if (empty($county_numbers)) {
      $query = $this->database->select('taxonomy_term__field_iso_code', 'iso');
      $query->join('taxonomy_term__field_department_region', 'dept_region', 'iso.entity_id = dept_region.field_department_region_target_id');
      $query->join('taxonomy_term__field_department_id', 'dept_id', 'dept_id.entity_id = dept_region.entity_id');
      $county_numbers = $query->fields('dept_id', ['field_department_id_value'])
        ->condition('field_iso_code_value', $region_iso2)
        ->distinct()
        ->execute()->fetchCol();
      $this->cache->set($cid, $county_numbers, CacheBackendInterface::CACHE_PERMANENT, [
        'county_numbers_iso_code',
        'iso_code_department_id',
      ]);
    }

    return $county_numbers;
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetRegionTidByCityInseeCode(string $insee_code) {
    // Ensure that insee code have 5 characters and add 0 if missing before.
    $insee_code = str_pad($insee_code, 5, '0', STR_PAD_LEFT);
    $length = preg_match('/^(97|98)[0-9]{3}$/', $insee_code) ? 3 : 2;
    $dept_id = substr($insee_code, 0, $length);

    if (!is_numeric($dept_id)) {
      return FALSE;
    }

    $query = $this->database->select('taxonomy_term__field_department_id', 'dept_id');
    $query->join('taxonomy_term__field_department_region', 'region', 'dept_id.entity_id = region.entity_id');
    $region_tid = $query->fields('region', ['field_department_region_target_id'])
      ->condition('field_department_id_value', $dept_id)
      ->execute()->fetchField(0);

    return $region_tid;
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetDptsByTerritory($territory) {
    $dpts_tids = [];
    $dpt_list = [];

    if (!empty($territory->get('field_sas_departements'))) {
      foreach ($territory->get('field_sas_departements')->getValue() as $dpt) {
        $dpts_tids[] = $dpt['target_id'];
      }
    }

    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadMultiple($dpts_tids);

    foreach ($terms as $term) {
      $dpt_list[$term->get('tid')->value] = $term->get('field_department_id')
        ->getValue()[0]['value'];
    }

    return $dpt_list;
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetPostalCodesByDpt($dpts) {
    $postal_codes = [];

    if ($dpts) {

      $query = $this->database->select('taxonomy_term__field_postal_code', 'cp');
      $query->fields('cp', ['field_postal_code_value']);
      $query->condition('cp.bundle', 'cities', '=');
      $query->addJoin('left', 'taxonomy_term__field_insee', 'i', 'i.entity_id = cp.entity_id');

      $orGroup = $query->orConditionGroup();
      foreach ($dpts as $dpt_cp) {
        $orGroup->condition('i.field_insee_value', $dpt_cp . '%', 'LIKE');
      }
      $query->condition($orGroup);
      $postal_codes = $query->execute()->fetchCol();
    }

    return $postal_codes;
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetTerritoriesFromPostalCode($postal_code) {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $storage
      ->getQuery()->accessCheck()
      ->condition('vid', 'sas_territoire')
      ->condition('field_sas_postal_codes', $postal_code, 'CONTAINS');

    return $query->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function sasGetTerritoriesFromNode(Node $node) {
    $territories = [];

    if (!empty($node) && in_array($node->getType(), SnpConstant::getSasBunles()) && !empty($node->get('field_address')
      ->getValue()[0]['postal_code'])) {
      $territories = $this->sasGetTerritoriesFromPostalCode($node->get('field_address')
        ->getValue()[0]['postal_code']);
    }

    return $territories;
  }

  private function getDepartmentTermFromInseeCode(string $insee_code) {
    $insee_code = str_pad($insee_code, 5, '0', STR_PAD_LEFT);
    $length = preg_match('/^(97|98)[0-9]{3}$/', $insee_code) ? 3 : 2;
    $departmentID = substr($insee_code, 0, $length);

    // Get the department taxonomy based on the city insee code.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()->accessCheck()
      ->condition('field_department_id', $departmentID)
      ->execute();

    return !empty($terms) ? $this->entityTypeManager->getStorage('taxonomy_term')->load(reset($terms)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeptByCityInseeCode(string $insee_code) {
    $term = $this->getDepartmentTermFromInseeCode($insee_code);
    return $term ? $term->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeptNameByInseeCode(string $insee_code): ?string {
    $term = $this->getDepartmentTermFromInseeCode($insee_code);
    return $term ? $term->get('name')->value : NULL;
  }

  /**
   * @param mixed $code_insee
   * @return string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function sasGetCityFromInseeCode(mixed $code_insee): string|NULL {

    if (empty($code_insee)) {
      return NULL;
    }

    $city_name = '';
    $code_insee = str_pad($code_insee, 5, '0', STR_PAD_LEFT);

    // Get the city name based on the insee code from the cities taxonomy.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()->accessCheck()
      ->condition('field_insee', $code_insee)
      ->execute();

    if (!empty($terms)) {
      $tid = reset($terms);
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
      $city_name = $term->getName();
    }

    return $city_name;
  }

}
