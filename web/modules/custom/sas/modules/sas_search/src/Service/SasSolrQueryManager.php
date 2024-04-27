<?php

namespace Drupal\sas_search\Service;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\sas_core\Enum\SasContent;
use Drupal\sas_search\Enum\SasSearchConstant;
use Drupal\sas_search\Enum\SasSolrQueryConstant;
use Drupal\sas_search\SasSolrQueryBase;
use Drupal\sas_snp\Enum\SnpConstant;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SasSolrQueryManager.
 *
 * Provide manager to build and execute solr queries on health_offer index.
 *
 * @package Drupal\sas_search\service
 */
class SasSolrQueryManager extends SasSolrQueryBase implements SasSolrQueryManagerInterface {

  /**
   * @const List of mandatory query parameters in search endpoint.
   */
  public const MANDATORY_PARAMS = [
    'what',
    'center_lat',
    'center_lon',
    'radius',
    'sort',
    'rand_id',
  ];

  /**
   * @const List of numeric query parameters in search endpoint.
   */
  public const NUMERIC_PARAMS = [
    'center_lat',
    'center_lon',
    'radius',
    'rand_id',
  ];

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request;

  /**
   * Pagination data.
   *
   * @var array
   */
  protected array $pagination;

  /**
   * Search radius to apply.
   *
   * @var float
   */
  protected float $radius;

  /**
   * Search type.
   *
   * @var string
   */
  protected string $searchType;

  /**
   * Sas Search helper.
   *
   * @var \Drupal\sas_search\Service\SasSearchHelperInterface
   */
  protected SasSearchHelperInterface $sasSearchHelper;

  /**
   * SasSolrQueryManager constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   * @param \Drupal\sas_search\Service\SasSearchHelperInterface $sas_search_helper
   */
  public function __construct(
    Client $http_client,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    TransliterationInterface $transliteration,
    SasSearchHelperInterface $sas_search_helper
  ) {
    parent::__construct(
      http_client: $http_client,
      config_factory: $config_factory,
      cache: $cache,
      logger_factory: $logger_factory,
      transliteration: $transliteration);
    $this->request = $request_stack->getCurrentRequest();
    $this->sasSearchHelper = $sas_search_helper;
  }

  /**
   * Build solr query data based on request parameters.
   */
  public function buildQuery(): array {

    $this->initQuery();
    $this->setFieldList();
    $this->setQueryBoosters();
    $this->setFacetAndFilters();

    if (empty($this->request->get('vacation'))) {
      $this->getVacationFilter();
    }

    if (!empty($this->request->get('has_slot'))) {
      $this->getHasSlotFilter();
    }

    // has_slot filter disabled and opened_hours filters is given.
    if (!empty($this->request->get('opened_hours')) && empty($this->request->get('has_slot'))) {
      $this->getOpenedHoursFilter();
    }

    $this->getGeolocationFilterAndSort();

    return $this->query;
  }

  /**
   * {@inheritDoc}
   */
  public function checkQueryParameters(): ?array {

    foreach (self::MANDATORY_PARAMS as $name) {
      if (empty($this->request->get($name))) {
        return $this->getErrorResult(400, sprintf('Parameter "%s" is missing.', $name));
      }
    }

    foreach (self::NUMERIC_PARAMS as $name) {
      if (!is_numeric($this->request->get($name))) {
        return $this->getErrorResult(400, sprintf('Parameter "%s" must be numeric.', $name));
      }
    }

    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getPagination() {
    if (!empty($this->pagination)) {
      return $this->pagination;
    }

    $item_per_page = $this->request->get('qty') ?? SasSolrQueryConstant::DEFAULT_ITEM_PER_PAGE;
    $page = $this->request->get('page') ?? SasSolrQueryConstant::DEFAULT_PAGE;
    $offset = ($page - 1) * $item_per_page;

    $pagination = [
      'page' => $page,
      'item_per_page' => $item_per_page,
      'offset' => $offset,
    ];

    $this->pagination = $pagination;
    return $this->pagination;
  }

  /**
   * {@inheritDoc}
   */
  public function getSearchType() {
    if (!empty($this->searchType)) {
      return $this->searchType;
    }

    // Default type.
    $type = SasSolrQueryConstant::SEARCH_TYPE_DEFAULT;
    // Search text transliterated.
    $text = $this->getQueryParameterTransliterated('what');

    if (str_contains($text, 'urgence')) {
      $type = SasSolrQueryConstant::SEARCH_TYPE_EMERGENCY;
    }

    if (str_contains($text, 'maternite')) {
      $type = SasSolrQueryConstant::SEARCH_TYPE_MATERNITY;
    }

    $this->searchType = $type;
    return $this->searchType;
  }

  /**
   * Initialise solr query with base elements.
   */
  protected function initQuery(): void {

    $this->setQuery([
      sprintf('fq=index_id:%s', SasSolrQueryConstant::SOLR_INDEX),
      'fq=its_address_exists:"1"',
      'lowercaseOperators=false',
      'defType=edismax',
      'sow=false',
      'ps=10',
      'wt=json',
      'json.nl=map',
      'group=true',
      'group.field=ss_field_custom_group',
      'group.ngroups=true',
      'group.limit=200',
      'group.facet=true',
      'group.sort=ss_type desc,sm_os_type_prise_charge_ref asc,tus_title asc',
      'q.op=AND',
    ]);

    // Manage main search parameter depending if matching suggested search.
    $this->setBaseQueryParam();

    // Manage pagination.
    $pagination = $this->getPagination();
    $this->setQueryItem(sprintf('start=%d', $pagination['offset']));
    $this->setQueryItem(sprintf('rows=%d', $pagination['item_per_page']));

    // Highlighting.
    if ($this->request->get('etb') === 'treat') {
      $this->setQueryItem('hl=on');
      $this->setQueryItem('hl.fl=tm_X3b_und_field_custom_label_temporaire,tm_X3b_und_field_custom_label_permanent');
    }

    // Debug mode.
    if ($this->request->get('debug') === '1') {
      $this->setQueryItem('debugQuery=on');
    }
  }

  /**
   * Build and set base query "q" parameter.
   */
  protected function setBaseQueryParam(): void {
    $q_param = $this->request->get('what');
    $suggested = $this->getSuggestedSearchQuery();

    if ($suggested) {
      $q_param = '*:*';
      $this->setQueryItem($suggested);
    }

    // If pref doctor given, exclude it from results.
    if (!empty($this->request->get('pref_doctor'))) {
      $pref_doctor_exclusion = sprintf(
        '(-tm_X3b_und_title:(%s) OR -ss_field_identifiant_rpps:(%s))',
        $this->request->query->get('pref_doctor'),
        $this->request->query->get('pref_doctor')
      );
      $q_param = $suggested ? $pref_doctor_exclusion : sprintf('%s AND %s', $q_param, $pref_doctor_exclusion);
    }

    $this->setQueryItem(sprintf('q=%s', $q_param));
  }

  /**
   * Set solr query field list.
   */
  protected function setFieldList(): void {
    // Always set self::SOLR_RESPONSE_FIELD_BASE_LIST at end of list
    // because of geodist();
    // If before other properties, they are not present in solr response.
    $field_list = array_merge(SasSolrQueryConstant::FIELD_SAS_LIST, SasSolrQueryConstant::FIELD_BASE_LIST);

    if (!empty($this->request->get('tags'))) {
      $field_list = array_merge(SasSolrQueryConstant::FIELD_TAGS_LIST, $field_list);
    }

    $this->setQueryItem(sprintf("fl=%s", implode(',', $field_list)));
  }

  /**
   * Set solr query fields and boosters.
   */
  protected function setQueryBoosters(): void {
    // Field boosters.
    foreach (SasSolrQueryConstant::FIELD_BOOST as $name => $boost) {
      $this->setQueryItem(sprintf('qf=%s^%s', $name, $boost));
    }

    // Phrase field boosters.
    foreach (SasSolrQueryConstant::PHRASE_FIELD_BOOST as $name => $boost) {
      $this->setQueryItem(sprintf('pf=%s^%s', $name, $boost));
    }
  }

  /**
   * Set solr query facets.
   */
  protected function setFacetAndFilters(): void {
    $this->setQueryItem('facet=true');
    $this->setQueryItem('facet.limit=-1');
    $this->setQueryItem('facet.mincount=1');
    $this->setQueryItem('facet.missing=false');

    $filters = $this->request->get('filters');
    foreach (SasSolrQueryConstant::FACET_FIELD_LIST as $facetType) {
      if (!empty($filters) && !empty($filters->$facetType)) {
        $this->setQueryItem(sprintf('facet.field={!ex=f:%s}%s', $facetType, $facetType));
      }
      else {
        $this->setQueryItem(sprintf('facet.field=%s', $facetType));
      }
    }

    if (!empty($filters)) {
      $filters_param = json_decode($filters);
      foreach ($filters_param as $key => $filters) {
        if (!empty($filters) && is_array($filters)) {
          $this->setQueryItem(sprintf('fq={!tag=f:%s}%s:("%s")', $key, $key, implode('" OR "', $filters)));
        }
      }
    }

    // For "maternité" search type.
    if ($this->getSearchType() === SasSolrQueryConstant::SEARCH_TYPE_MATERNITY) {
      $this->setQueryItem('fq=sm_field_maternite_level:[* TO *]');
    }
  }

  /**
   * Get Vacation filters to remove items that are not available.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  protected function getVacationFilter(): void {
    $this->setQueryItem('fq=-(bs_sas_vacation:"true")');

    $time_start = new DrupalDateTime('now');
    $time_start = $time_start->getTimestamp();
    $time_end = (new DrupalDateTime('now'))->add(new \DateInterval('P2D'));
    $time_end = $time_end->getTimestamp();
    for ($i = 0; $i < SnpConstant::SAS_MAX_VACATION_SLOT_NB; $i++) {
      $this->setQueryItem(sprintf(
        'fq=-(its_sas_vacation_slot_start_%d:[* TO "%s"] AND its_sas_vacation_slot_end_%d:["%s" TO *])',
        $i, $time_start, $i, $time_end
      ));
    }
  }

  /**
   * Add opened hour filters.
   */
  protected function getOpenedHoursFilter(): void {
    $opened_hours = $this->request->get('opened_hours');
    // Make sure to have an array.
    if (!is_array($opened_hours)) {
      $opened_hours = [$opened_hours];
    }

    $hours = [];
    foreach ($opened_hours as $opened_hour) {
      if (!empty(SasSolrQueryConstant::OPENED_HOURS[$opened_hour])) {
        $hours = array_merge($hours, SasSolrQueryConstant::OPENED_HOURS[$opened_hour]);
      }
    }

    if (empty($hours)) {
      return;
    }

    $currentDay = $this->sasSearchHelper->getCurrentDay();

    $filter_parts = [];
    foreach ($hours as $hour) {
      $filter_parts[] = sprintf('(itm_working_hours:"%d%s")', $currentDay, $hour);
      // To manage pharmacy?
      $filter_parts[] = sprintf('(itm_working_hours:"1%d%s")', $currentDay, $hour);
    }

    if (empty($filter_parts)) {
      return;
    }

    $this->setQueryItem(sprintf('fq=(%s)', implode(' OR ', $filter_parts)));
  }

  /**
   * Get has slot filter (editor, snp sas or sans-rdv).
   */
  protected function getHasSlotFilter(): void {
    $this->setQueryItem(sprintf(
      'fq=((bs_sas_has_snp:"%s") OR ((bs_sas_is_interfaced:"%s") AND -bs_sas_editor_disabled:"%s"))',
      "true",
      "true",
      "true"
    ));
  }

  /**
   * Get Geolocation filter and sort based on geolocation data passed in url.
   */
  protected function getGeolocationFilterAndSort(): void {

    $center_lat = $this->request->get('center_lat') ?? '';
    $center_lon = $this->request->get('center_lon') ?? '';
    $radius = $this->request->get('radius') ?? '';
    $sort = $this->request->get('sort') ?? '';

    // Define result sort.
    switch ($sort) {
      case SasSolrQueryConstant::SEARCH_SORT_DISTANCE:
        $this->setQueryItem(sprintf(
          'sort=bs_sas_participation desc, geodist(locs_field_geolocalisation_latlon,%s,%s) asc, its_nid desc',
          $center_lat,
          $center_lon
        ));
        break;

      case SasSolrQueryConstant::SEARCH_SORT_RANDOM:
        $this->setQueryItem(sprintf(
          'sort=bs_sas_participation desc, random_%s desc, ds_date_common_for_sort desc, its_nid desc', $this->request->get('rand_id')
        ));
        break;

      default:
        break;
    }

    // Add concentric localisation filter.
    $this->setQueryItem(sprintf(
      'pt=%s,%s',
      $center_lat,
      $center_lon
    ));
    $this->setQueryItem('fq={!geofilt}');
    $this->setQueryItem(sprintf('d=%f', $radius));
    $this->setQueryItem('sfield=locs_field_geolocalisation_latlon');
  }

  /**
   * Get suggested search solr query filter based on searched text.
   *
   * @return string|null
   *   If search text matching suggested search, return solr query filter.
   *   Else return NULL.
   */
  protected function getSuggestedSearchQuery(): bool {
    $hasSuggestedQuery = FALSE;

    $suggestion = $this->sasSearchHelper->getSuggestedSearch($this->request->get('what'));

    if (empty($suggestion) || empty($suggestion['filters'])) {
      return FALSE;
    }

    $filters = $suggestion['filters'];
    if (empty($filters[SasSearchConstant::SUGGESTIONS_STRUCTURE_SOLR_FIELD]) && empty($filters[SasSearchConstant::SUGGESTIONS_PROFESSIONAL_SOLR_FIELD])) {
      return FALSE;
    }

    $solr_filters = [];
    $solr_filters_properties = [
      SasSearchConstant::SUGGESTIONS_PROFESSIONAL_SOLR_FIELD,
      SasSearchConstant::SUGGESTIONS_STRUCTURE_SOLR_FIELD,
    ];
    foreach ($solr_filters_properties as $filter_name) {
      if (!empty($filters[$filter_name])) {
        $filter_values = [];
        foreach ($filters[$filter_name] as $profession) {
          $filter_values[] = sprintf('(%s:"%s")',
            $filter_name,
            $profession);
        }

        if (!empty($filter_values)) {
          $solr_filters[] = implode(' OR ', $filter_values);
        }
      }
    }

    if (!empty($solr_filters)) {
      $this->setQueryItem(sprintf('fq=(%s)', implode(' OR ', $solr_filters)));
      $hasSuggestedQuery = TRUE;
    }

    if (!empty($suggestion['specialities'])) {
      $this->setQueryItem(sprintf(
        'fq=(-ss_type:"%s" AND bs_sas_participation:*) OR ((ss_type:"%s") AND (bs_sas_participation:true)) OR (ss_sas_specialities:"%s")',
        SasContent::CT_PRO_SHEET,
        SasContent::CT_PRO_SHEET,
        $suggestion['specialities']
      ));
      $hasSuggestedQuery = TRUE;
    }

    return $hasSuggestedQuery;
  }

}
