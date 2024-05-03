<?php

declare(strict_types = 1);

namespace Drupal\sas_search\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Search manager specific to CPTS.
 */
final class SasCptsSearchManager {

  const CPTS_NO_RESULT_MESSAGE = 'CPTS cannot be found.';
  const CPTS_EFFECTOR_NO_RESULT_MESSAGE = 'CPTS effectors cannot be found.';

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private Request $request;

  /**
   * @var \Drupal\sas_search\Service\SasSolrCptsQueryManager
   */
  private SasSolrCptsQueryManager $solrCptsQuery;

  /**
   * @var \Drupal\sas_search\Service\SasSolrCptsEffectorQueryManager
   */
  private SasSolrCptsEffectorQueryManager $solrCptsEffectorQuery;

  public function __construct(
    RequestStack $request_stack,
    SasSolrCptsQueryManager $solr_cpts_query,
    SasSolrCptsEffectorQueryManager $solr_cpts_effector_query
  ) {
    $this->request = $request_stack->getCurrentRequest();
    $this->solrCptsQuery = $solr_cpts_query;
    $this->solrCptsEffectorQuery = $solr_cpts_effector_query;
  }

  /**
   * Query Cpts search.
   *
   * @return array
   *   List of places for Cpts OR error message.
   */
  public function makeCptsQuery(): array {
    $error = $this->solrCptsQuery->checkQueryParameters();
    if (!empty($error)) {
      return $error;
    }

    $this->solrCptsQuery->buildQuery();
    $search_cpts_results = json_decode($this->solrCptsQuery->executeQuery());

    if (empty($search_cpts_results->response->docs)) {
      return $this->solrCptsQuery->getErrorResult(
        Response::HTTP_NOT_FOUND,
        self::CPTS_NO_RESULT_MESSAGE
      );
    }
    return $search_cpts_results->response->docs;
  }

  /**
   * Query Cpts search.
   *
   * @return array
   *   List of places for Cpts OR error message.
   */
  public function makeCptsEffectorsQuery(): array {
    // Check query parameters.
    $error = $this->solrCptsEffectorQuery->checkQueryParameters();
    if (!empty($error)) {
      return $error;
    }

    $this->solrCptsEffectorQuery->buildQuery();
    $cpts_effectors_results = json_decode($this->solrCptsEffectorQuery->executeQuery());

    if (empty($cpts_effectors_results->response->docs)) {
      return $this->solrCptsEffectorQuery->getErrorResult(
        Response::HTTP_NOT_FOUND,
        self::CPTS_EFFECTOR_NO_RESULT_MESSAGE
      );
    }

    $result['data'] = $cpts_effectors_results->response->docs;
    $pagination = $this->solrCptsEffectorQuery->getPagination();
    $result['infos']['start'] = $pagination['offset'] + 1;
    $result['infos']['page'] = $pagination['page'];
    $result['infos']['length'] = $cpts_effectors_results->response->numFound ?? 0;
    $result['infos']['end'] = $pagination['offset'] + $result['infos']['length'];

    return $result;
  }

}
