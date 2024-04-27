<?php

namespace Drupal\sas_territory\Services;

use Drupal\node\Entity\Node;

/**
 * Interface SasGetTermCodeCitiesInterface.
 *
 * Define structure for class which provides geographical data.
 *
 * @package Drupal\sas_territory\Services
 */
interface SasGetTermCodeCitiesInterface {

  /**
   * Get term ids for given names.
   *
   * @param array $names
   *   Names to search.
   * @param array $vocabularies
   *   Vocabularies of terms to search.
   *
   * @return array|int[]|string[]
   *   Term ids.
   */
  public function sasGetTermIdByName(array $names, array $vocabularies = []);

  /**
   * Get term names for given ids.
   *
   * @param array $ids
   *   Ids of terms.
   *
   * @return array|string[]
   *   Term names.
   */
  public function sasGetTermNamesByIds(array $ids);

  /**
   * Get all iso codes defined in regions vocabulary.
   *
   * @return mixed
   *   List of iso codes.
   */
  public function sasGetRegionIsoCodes();

  /**
   * Get all county numbers of a given region.
   *
   * @param string $region_iso2
   *   Iso2 code of region like FR-* (FR-IDF, FR-ARA).
   *
   * @return mixed
   *   List of county numbers for the given region ISO2 code.
   */
  public function sasGetRegionCountyNumbers(string $region_iso2);

  /**
   * Get region term tid based on city INSEE code.
   *
   * @param string $insee_code
   *   City INSEE code.
   *
   * @return mixed
   *   Region taxonomy term tid if exist.
   */
  public function sasGetRegionTidByCityInseeCode(string $insee_code);

  /**
   * Get all departments referenced by territory.
   *
   * @param $territory
   *   A term object
   *
   * @return array
   *   Array of dpts as tid => CP.
   */
  public function sasGetDptsByTerritory($territory);

  /**
   * Get all cities postal codes where department number matches code_insee.
   *
   * @param $dpts
   *   Ids of departements.
   *
   * @return array
   *   Array of cities postal codes.
   */
  public function sasGetPostalCodesByDpt($dpts);

  /**
   * Get all territories linked to given postal code.
   *
   * @param $postal_code
   *   code_pastal
   *
   * @return array
   *   Array of tids from voc sas_territory.
   */
  public function sasGetTerritoriesFromPostalCode($postal_code);

  /**
   * Get all territories linked to given postal code of PS / Structure node.
   *
   * @param $node
   *   Node object.
   *
   * @return array
   *   Array of tids from voc sas_territory.
   */
  public function sasGetTerritoriesFromNode(Node $node);

  /**
   * Get a term department from city insee code.
   *
   * @param $insee_code
   *   The insee code value.
   *
   * @return int|null
   *   The tid if found or NULL.
   */
  public function getDeptByCityInseeCode(string $insee_code);

  /**
   * Get the number of departement from city insee code.
   *
   * @param string $insee_code
   *
   * @return mixed
   */
  public function getDeptNameByInseeCode(string $insee_code);

  /**
   * Get a term city from insee code.
   *
   * @param $code_insee
   *
   * @return string|null
   */
  public function sasGetCityFromInseeCode($code_insee): string|null;

}
