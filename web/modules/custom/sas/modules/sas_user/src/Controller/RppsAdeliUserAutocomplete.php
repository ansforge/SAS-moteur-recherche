<?php

namespace Drupal\sas_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\node\NodeInterface;
use Drupal\sas_user\Enum\SasUserConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RppsAdeliUserAutocomplete.
 *
 * Controller to provide autocomplete for rpps/adeli PS.
 *
 * @package Drupal\sas_user\Controller
 */
class RppsAdeliUserAutocomplete extends ControllerBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Controller action to get rpps/adeli list from searched text.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response of corresponding associations.
   */
  public function autocompleteUserList(Request $request): JsonResponse {

    $text = $request->query->get('q');

    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('node__field_identifiant_rpps', 'rpps', 'rpps.entity_id = n.nid');
    $query->leftJoin('node__field_personne_adeli_num', 'adeli', 'adeli.entity_id = n.nid');
    $null_or_group = $query->orConditionGroup()
      ->isNotNull('rpps.field_identifiant_rpps_value')
      ->isNotNull('adeli.field_personne_adeli_num_value');
    $like_or_group = $query->orConditionGroup()
      ->condition('rpps.field_identifiant_rpps_value', "%{$text}%", 'LIKE')
      ->condition('adeli.field_personne_adeli_num_value', "%{$text}%", 'LIKE')
      ->condition('n.title', "%{$text}%", 'LIKE');
    $query->fields('n', ['title']);
    $query->addExpression(sprintf(
      'IF(ISNULL(rpps.field_identifiant_rpps_value), CONCAT(%s, adeli.field_personne_adeli_num_value), CONCAT(%s, rpps.field_identifiant_rpps_value))',
      SasUserConstants::PREFIX_ID_ADELI,
      SasUserConstants::PREFIX_ID_RPPS
    ), 'id_nat');
    $query->condition('n.status', NodeInterface::PUBLISHED)
      ->condition('n.type', 'professionnel_de_sante')
      ->condition($null_or_group)
      ->condition($like_or_group)
      ->range(0, 10);
    $results = $query->execute()->fetchAllAssoc('id_nat');

    if (empty($results)) {
      return new JsonResponse([]);
    }

    $matches = [];
    foreach ($results as $result) {
      $matches[] = [
        'value' => $result->id_nat,
        'label' => sprintf('%s - %s', $result->id_nat, $result->title),
      ];
    }

    return new JsonResponse($matches);

  }

}
