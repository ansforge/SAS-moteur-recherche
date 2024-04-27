<?php

namespace Drupal\sas_snp\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_orientation\Enum\OrientationStrategy;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_snp\Service\SnpUnavailabilityHelper;
use Drupal\sas_user_dashboard\Services\DashboardUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The Class SasSnpController.
 */
class SasSnpController extends ControllerBase {

  /**
   * @const int max lenght
   */
  const MAX_LENGTH = 1000;

  /**
   * The sas_api_client.service service.
   *
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected $sasApiClientService;

  /**
   * SAS Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  /**
   * The Snp Service.
   *
   * @var \Drupal\sas_snp\SnpService
   */
  protected $sasSnpManager;

  /**
   * Search results formatter.
   *
   * @var \Drupal\sas_snp\Service\SnpSlotsFormatter
   */
  protected $snpSlotsFormatter;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface
   */
  protected DashboardUserInterface $dashboard;

  /**
   * SnpUnavailabilityHelper service. service.
   *
   * @var \Drupal\sas_snp\Service\SnpUnavailabilityHelper
   */
  protected SnpUnavailabilityHelper $sasSnpUnavailabilityHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->sasCoreService = $container->get('sas_core.service');
    $instance->sasApiClientService = $container->get('sas_api_client.service');
    $instance->sasSnpManager = $container->get('sas_snp.manager');
    $instance->snpSlotsFormatter = $container->get('sas_snp.slots_formatter');
    $instance->pscUser = $container->get('sas_keycloak.psc_user');
    $instance->dashboard = $container->get('sas_user_dashboard.dashboard');
    $instance->sasSnpUnavailabilityHelper = $container->get('sas_snp.unavailability_helper');

    return $instance;
  }

  /**
   * Return url redirect snp page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Entity Node.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request represents an HTTP request.
   *
   * @return \Drupal\Core\Access\AccessResultForbidden|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Return redirect.
   */
  public function snpPage(Node $node, Request $request) {

    $account = $this->currentUser();
    $node_storage = $this->entityTypeManager()->getStorage('node');

    if (!in_array($node->bundle(), SnpConstant::getSasBunles())) {
      throw new AccessDeniedHttpException();
    }

    $node_ids = [];
    $has_role_admin = FALSE;
    if ($account->isAuthenticated()) {
      if (in_array(SnpConstant::SAS_ADMINISTRATEUR, $account->getRoles())
        || in_array(SnpConstant::SAS_ADMINISTRATEUR_NATIONAL, $account->getRoles())) {
        $has_role_admin = TRUE;
      }

      if (!$has_role_admin) {
        // @todo gerer les permissions du create node dans le CustomNodeAccess
        $node_ids = $this->sasSnpManager->getSnpNodesIds($account);

        if (!in_array($node->id(), $node_ids)) {
          throw new AccessDeniedHttpException();
        }
      }
    }

    $time_slots_ids_nodes = $node_storage->getQuery()->accessCheck()
      ->condition('type', SnpConstant::SAS_TIME_SLOTS)
      ->condition('field_sas_time_slot_ref', $node->id())
      ->execute();

    if (!empty($time_slots_ids_nodes)) {
      $time_slots_id_node = reset($time_slots_ids_nodes);
    }
    else {

      if (!$has_role_admin && !$this->pscUser->isValid()) {
        // @todo gerer les permissions du create node dans le CustomNodeAccess
        $node_ids = $this->sasSnpManager->getSnpNodesIds($account);
      }

      if (in_array($node->id(), $node_ids) || $has_role_admin || $this->pscUser->isValid()) {
        $time_slots_node = $node_storage->create([
          'type' => SnpConstant::SAS_TIME_SLOTS,
          'title' => sprintf('sas_snp_%s', $node->id()),
          'field_sas_time_slot_ref' => $node->id(),
          'uid' => 0,
          'moderation_state' => 'published',
        ]);
        $time_slots_node->save();
        $time_slots_id_node = $time_slots_node->id();

        if ($time_slots_id_node) {
          // The target PS/Etab need to be invalidated on creation so that
          // it can refresh with a cache tag on this sas_time_slots and
          // then update its rendering when a schedule_id is created.
          Cache::invalidateTags(['node:' . $node->id()]);
        }
      }
    }

    if (empty($time_slots_id_node)) {
      throw new AccessDeniedHttpException();
    }

    return $this->redirect(
      'entity.node.canonical',
      ['node' => $time_slots_id_node, 'back_url' => $request->query->get('back_url')]
    );

  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   * @SuppressWarnings(PHPMD.MissingImport)
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function deleteSlot(Node $node, Request $request) {

    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Accès refusé.');
    }

    $data_slot = Json::decode($request->getContent());
    if ($request->getMethod() == 'POST') {
      if (isset($data_slot['snp_delete_slot'])
        && !empty($data_slot['snp_delete_slot'])
        && !is_bool($data_slot['snp_delete_slot'])) {
        throw new BadRequestHttpException('Le paramètre snp_delete_slot doit être boolean');
      }
      if (!in_array($data_slot['slot_type'], SnpConstant::SAS_SLOT_TYPE)) {
        throw new BadRequestHttpException('Le paramètre slot_type n\'a pas la bonne valeur');
      }
      if (!is_int($data_slot['slot_id'])) {
        throw new BadRequestHttpException('Le paramètre slot_id doit être un entier');
      }

      if (isset($data_slot['date']) && !empty($data_slot['date'])) {
        $date = \DateTime::createFromFormat("Y-m-d\TH:i:sP", $data_slot['date']);
        if (FALSE === $date) {
          throw new BadRequestHttpException(sprintf('Impossible de parser la date %s', $data_slot['date']));
        }

        $formatted_date = $date->format('Y-m-d\TH:i:sP');
        if ($formatted_date != $data_slot['date']) {
          throw new BadRequestHttpException(sprintf('Format date non valide %s', $data_slot['date']));
        }
      }

      if (!empty($data_slot['slot_id']) && !empty($data_slot['slot_type'])) {
        switch ($data_slot['slot_type']) {
          case 'dated':
            $this->sasApiClientService->sas_api('delete_slot', [
              'tokens' => [
                'id' => $data_slot['slot_id'],
              ],
            ]);
            $this->sasSnpManager->updateSnpAvailability($node);
            break;

          case 'recurring':
            $result = $this->sasApiClientService->sas_api('create_slot_disabled', [
              'body' => [
                "slot" => [
                  "id" => $data_slot['slot_id'],
                ],
                'date' => $data_slot['date'],
                'recurring' => !$data_slot['snp_delete_slot'],
              ],
            ]);
            $this->sasSnpManager->updateSnpAvailability($node);
            return new JsonResponse([$result]);
        }
      }
      else {
        throw new BadRequestHttpException('Slot ID is NULL.');
      }
    }
    $node->setChangedTime(time());
    $node->save();
    return new JsonResponse(['Slot supprimé']);
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function slot(Node $node, Request $request) {
    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Accès refusé.');
    }

    $data_slot = Json::decode($request->getContent());

    switch ($request->getMethod()) {
      case 'POST':
        $first_slot = FALSE;
        // Champs Obligatoire.
        $date = \DateTime::createFromFormat("Y-m-d\TH:i:sP", $data_slot['date']);
        if (FALSE === $date) {
          throw new BadRequestHttpException(sprintf('Impossible de parser la date %s', $data_slot['date']));
        }

        $formatted_date = $date->format('Y-m-d\TH:i:sP');
        if ($formatted_date != $data_slot['date']) {
          throw new BadRequestHttpException(sprintf('Format date non valide %s', $data_slot['date']));
        }

        $this->validateHours($data_slot);

        // Champs Facultatif.
        if (isset($data_slot['modalities']) && !empty($data_slot['modalities'])) {
          foreach ($data_slot['modalities'] as $modality) {
            if (!in_array($modality, SnpConstant::SAS_MODALITIES)) {
              throw new BadRequestHttpException('Modalité est incorrect');
            }
          }
        }

        if (isset($data_slot['max_patients'])
          && !empty($data_slot['max_patients'])
          && !is_int($data_slot['max_patients'])) {
          throw new BadRequestHttpException('Max Patients doit être un entier');
        }

        if (isset($data_slot['repeat'])
          && !empty($data_slot['repeat'])) {
          foreach ($data_slot['repeat'] as $repeat) {
            if (!preg_match('/^([1-7]|1)$/', $repeat)) {
              throw new BadRequestHttpException(sprintf('La valeur %s correspond pas a un jour de la semaine', $repeat));
            }
          }
        }

        $day = (int) $date->format('N');
        if (
          isset($data_slot['schedule']['id']) &&
          !empty($data_slot['schedule']['id'])
        ) {
          $schedule = $data_slot['schedule'];
        }
        else {
          $node_ps = current($node->get('field_sas_time_slot_ref')->referencedEntities());
          $first_slot = TRUE;
          $organization_data = [];
          foreach (SnpConstant::SAS_STRUCTURE_ID_FIELDS_MAPPING as $field_name => $field_api_name) {
            if ($node_ps->hasField($field_name) && !$node_ps->get($field_name)->isEmpty()) {
              $organization_data[$field_api_name] = $node_ps->get($field_name)->first()->value;
            }
          }
          $field_type_item = $node_ps->type->entity->get('type');
          if (!empty($field_type_item) && $field_type_item == 'entite_geographique' && !empty($node_ps->get('field_identifiant')->first()->value)) {
            $organization_data['guid'] = $node_ps->get('field_identifiant')->first()->value;
          }

          $schedule = [
            'organization' => $organization_data,
          ];

          if (!empty($node_ps) && $node_ps->hasField('field_personne_adeli_num') && !$node_ps->get('field_personne_adeli_num')->isEmpty()) {
            $schedule['practitioner']['pro_id'] = $node_ps->get('field_personne_adeli_num')->first()->getValue()['value'];
          }
          if (!empty($node_ps) && $node_ps->hasField('field_identifiant_rpps') && !$node_ps->get('field_identifiant_rpps')->isEmpty()) {
            $schedule['practitioner']['pro_id'] = $node_ps->get('field_identifiant_rpps')->first()->getValue()['value'];
          }
        }

        if (empty($schedule['timezone']) && !empty($data_slot['schedule']['timezone'])) {
          $schedule['timezone'] = $data_slot['schedule']['timezone'];
        }

        $recurrences = [];
        if (isset($data_slot['repeat'])) {
          $recurrences = array_filter($data_slot['repeat']);
        }

        $type = 'dated';
        if (in_array($day, $recurrences)) {
          $type = 'recurring';
          if (($key = array_search($day, $recurrences)) !== FALSE) {
            unset($recurrences[$key]);
          }
        }

        unset($data_slot['repeat']);
        $data = [
          'schedule' => $schedule,
          'date' => $data_slot['date'],
          'day' => $day,
          'type' => $type,
          'start_hours' => $data_slot['start_hours'],
          'end_hours' => $data_slot['end_hours'],
          'modalities' => $data_slot['modalities'],
          'max_patients' => $data_slot['max_patients'],
        ];

        $slots[] = $data;
        foreach ($recurrences as $dayId) {
          $recurring_start = clone $date;
          if ($dayId !== $day) {
            $slot = $data;
            $slot['day'] = $dayId;
            $interval = $dayId - $day;
            $interval = $interval < 0 ? 7 + $interval : $interval;
            // IMPORTANT start date must correspond to the recurring day.
            $slot['date'] = $recurring_start->add(new \DateInterval('P' . $interval . 'D'))->format(\DateTimeInterface::ATOM);
            $slot['type'] = 'recurring';
            $slots[] = $slot;
          }
        }

        $schedule_id = $schedule['id'] ?? '';
        foreach ($slots as $slot) {
          if (!empty($schedule_id)) {
            $slot['schedule'] = ['id' => $schedule_id];
          }

          $response = $this->sasApiClientService->sas_api('create_slot', [
            'body' => $slot,
          ]);

          if (empty($schedule_id) && !empty($response)) {
            if (empty($schedule['organization'])) {
              return new JsonResponse('La valeur organization ne peut etre vide');
            }
            $schedule_id = $response['schedule']['id'];
          }
        }
        if (!empty($schedule_id) && $first_slot) {
          $node->set('field_sas_time_slot_schedule_id', $schedule_id);
          $node->setChangedTime(time());
          $node->save();
          $this->sasSnpManager->updateSnpAvailability($node);
          return new JsonResponse(['schedule_id' => "$schedule_id"]);
        }
        $node->setChangedTime(time());
        $node->save();
        break;

      case 'PUT':

        // Champs Obligatoire.
        $date = \DateTime::createFromFormat(\DATE_ATOM, $data_slot['date']);
        if (FALSE === $date) {
          throw new BadRequestHttpException(sprintf('Impossible de parser la date %s', $data_slot['date']));
        }

        $formatted_date = $date->format(\DATE_ATOM);
        if ($formatted_date != $data_slot['date']) {
          throw new BadRequestHttpException(sprintf('Format date non valide %s', $data_slot['date']));
        }

        if (!is_bool($data_slot['item_in_recurrence']) || !isset($data_slot['item_in_recurrence'])) {
          throw new BadRequestHttpException(sprintf('Format non valide', $data_slot['item_in_recurrence']));
        }

        $this->validateHours($data_slot);

        // Champs Facultatif.
        if (isset($data_slot['slot']['id'])
          && !empty($data_slot['slot']['id'])
          && !is_int($data_slot['slot']['id'])) {
          throw new BadRequestHttpException('Le slot id doit être un entier');
        }

        if (isset($data_slot['schedule']['id'])
          && !empty($data_slot['schedule']['id'])
          && !is_int($data_slot['schedule']['id'])) {
          throw new BadRequestHttpException('Le schedule id doit être un entier');
        }

        if (isset($data_slot['max_patients'])
          && !empty($data_slot['max_patients'])
          && !is_int($data_slot['max_patients'])) {
          throw new BadRequestHttpException('Max Patients doit être un entier');
        }

        if (isset($data_slot['day'])
          && !empty($data_slot['day'])
          && !is_int($data_slot['day'])) {
          throw new BadRequestHttpException('Day doit être un entier');
        }

        if (isset($data_slot['modalities']) && !empty($data_slot['modalities'])) {
          foreach ($data_slot['modalities'] as $modality) {
            if (!in_array($modality, SnpConstant::SAS_MODALITIES)) {
              throw new BadRequestHttpException('Modalité est incorrect');
            }
          }
        }

        $data_slot_disabled = [
          'slot' => [
            'id' => '',
          ],
          'date' => '',
        ];

        switch ($data_slot['type']) {

          case 'dated':
          case 'recurring':
            if (!$data_slot['item_in_recurrence']) {
              $start_day = $date->format('N');
              $day_to_move = $data_slot['day'] >= $start_day ? $data_slot['day'] - $start_day : 7 + $data_slot['day'] - $start_day;
              $data_slot['date'] = $date->modify('+' . $day_to_move . ' days')->format(\DATE_ATOM);
              unset($data_slot['item_in_recurrence']);
              $slot_id = $data_slot['slot']['id'];
              $response = $this->sasApiClientService->sas_api('update_slot', [
                'tokens' => [
                  'id' => $slot_id,
                ],
                'body' => $data_slot,
              ]);
              $this->sasSnpManager->updateSnpAvailability($node);
              $node->setChangedTime(time());
              $node->save();
              return new JsonResponse([$response]);
            }
            elseif ($data_slot['item_in_recurrence'] && $data_slot['type'] == 'recurring') {
              unset($data_slot['item_in_recurrence']);
              $data_slot['type'] = 'dated';
              $data_slot_result = array_intersect_key($data_slot, $data_slot_disabled);
              $this->sasApiClientService->sas_api('create_slot_disabled', [
                'body' => $data_slot_result,
              ]);
              unset($data_slot['slot']);
              $response = $this->sasApiClientService->sas_api('create_slot', [
                'body' => $data_slot,
              ]);
              $this->sasSnpManager->updateSnpAvailability($node);
              $node->setChangedTime(time());
              $node->save();
              return new JsonResponse([$response]);
            }

            break;
        }
        break;

      default:
        throw new BadRequestHttpException('La méthode n\'est pas autorisée.');
    }
    $this->sasSnpManager->updateSnpAvailability($node);
    $node->setChangedTime(time());
    $node->save();
    return new JsonResponse(['Slot enregistré']);
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function unavailability(Node $node, Request $request) {
    if (!$this->sasCoreService->isSasContext() ||
      $node->bundle() != SnpConstant::SAS_TIME_SLOTS
    ) {
      throw new AccessDeniedHttpException('Accès refusé.');
    }

    switch ($request->getMethod()) {
      case 'POST':
        $unavailability = Json::decode($request->getContent());
        $results = [];
        if (isset($unavailability['dates']) && !empty($unavailability['dates'])) {
          foreach ($unavailability['dates'] as $date) {
            $begin_date = \DateTime::createFromFormat("Y-m-d\TH:i:s", $date['value']);
            if (FALSE === $begin_date) {
              throw new BadRequestHttpException(sprintf('Impossible de parser la date %s', $date['value']));
            }

            $formatted_begin_date = $begin_date->format('Y-m-d\TH:i:s');
            if ($formatted_begin_date != $date['value']) {
              throw new BadRequestHttpException(sprintf('Format date non valide %s', $date['value']));
            }

            $end_date = \DateTime::createFromFormat("Y-m-d\TH:i:s", $date['end_value']);
            if (FALSE === $end_date) {
              throw new BadRequestHttpException(sprintf('Failed to parse mtime date from %s', $date['end_value']));
            }
            $formatted_end_date = $end_date->format('Y-m-d\TH:i:s');

            if ($formatted_end_date != $date['end_value']) {
              throw new BadRequestHttpException(sprintf('Format date non valide %s', $date['end_value']));
            }

            $results[] = [
              'value' => $formatted_begin_date,
              'end_value' => $formatted_end_date,
            ];
          }
        }

        if (!is_bool($unavailability['vacation_mode']) || !isset($unavailability['vacation_mode'])) {
          throw new BadRequestHttpException(sprintf('Format non valide', $unavailability['vacation_mode']));
        }

        $node->set('field_sas_time_slot_vacations', $results);
        $node->set('field_sas_time_snp_active', $unavailability['vacation_mode']);
        $node->setChangedTime(time());
        $node->save();
        $this->sasSnpManager->updateSnpAvailability($node);
        $response = 'Indisponibilite mise a jours';
        break;

      case 'GET':
        // @todo Doit être re-travailler par un dev back. Vu avec Hocine.
        $vacation_mode = FALSE;
        if (isset($node->get('field_sas_time_snp_active')->getValue()[0]['value'])
        && $node->get('field_sas_time_snp_active')->getValue()[0]['value']) {
          $vacation_mode = TRUE;
        }

        $response = [
          'vacation_mode' => $vacation_mode,
          'dates' => $node->get('field_sas_time_slot_vacations')->getValue(),
        ];
        break;

      default:
        throw new BadRequestHttpException('La méthode n\'est pas autorisée.');

    }

    return new JsonResponse($response);
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function additionalInformation(Node $node, Request $request) {
    if (!$this->sasCoreService->isSasContext() ||
      $node->bundle() != SnpConstant::SAS_TIME_SLOTS
    ) {
      throw new AccessDeniedHttpException('Accès refusé.');
    }

    switch ($request->getMethod()) {
      case 'POST':
        $datas = Json::decode($request->getContent());
        $safe_result = '';
        if (isset($datas['additional_information']) && !empty($datas['additional_information'])) {
          $filter_result = Xss::filter($datas['additional_information']);
          $safe_result = Unicode::truncate($filter_result, self::MAX_LENGTH);
          if ($safe_result != $filter_result) {
            $this->getLogger('sas_snp.info')->info(
              $this->t('Truncate text information complementaire : @safe_result', [
                '@safe_result' => $safe_result,
              ])
            );
          }
        }

        $node->set('field_sas_time_info', $safe_result);
        $node->setChangedTime(time());
        $node->save();
        $response = 'Informations complémentaires mis a jours';
        break;

      case 'GET':
        // @todo Doit être re-travailler par un dev back. Vu avec Hocine.
        $additional_information = '';
        if (isset($node->get('field_sas_time_info')->getValue()[0]['value'])
        && $node->get('field_sas_time_info')->getValue()[0]['value']) {
          $additional_information = $node->get('field_sas_time_info')->getValue()[0]['value'];
        }

        $response = [
          'additional_information' => $additional_information,
        ];
        break;

      default:
        throw new BadRequestHttpException('La méthode n\'est pas autorisée.');

    }

    return new JsonResponse($response);
  }

  /**
   * Return SAS-API slots for search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JSON response.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function getSlotsByPs(Request $request) {
    $slots = [];
    $practitioners = json_decode($request->getContent(), TRUE);

    if (!empty($practitioners)) {
      $response = $this->sasApiClientService->sas_api('get_slots_by_ps', [
        'query' => [
          'start_date' => $request->query->get('start_date'),
          'end_date' => $request->query->get('end_date'),
          'orientationStrategy' => OrientationStrategy::ORIENTATION_STRATEGY_DATA,
        ],
        'body' => $practitioners,
      ]);

      if (!empty($response)) {
        $formatedResponse = [];
        foreach ($response as $value) {
          $formatedResponse[$value['nid']] = $value;
        }
        unset($response);
        $response = $this->sasSnpUnavailabilityHelper->getUnavalaibilities($formatedResponse);
        $slots = $this->snpSlotsFormatter->orderByTimestamp($response);
      }
    }

    return new JsonResponse($slots);
  }

  /**
   * Returns response for page additionnal information.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the configuration Consign.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function additionalInformationConfig(Node $node) {
    $config = $this->config('sas_config.general_settings')->get('config');
    $additional_info_consign = '';
    if (!empty($config['snp']['info']['value'])) {
      $additional_info_consign = $config['snp']['info']['value'];
    }

    return new JsonResponse($additional_info_consign);
  }

  /**
   * ValidateHours.
   */
  private function validateHours($data_slot) {
    $start_hours = \DateTime::createFromFormat("Hi", $data_slot['start_hours']);

    if (FALSE === $start_hours) {
      throw new BadRequestHttpException(sprintf('Impossible de parser l\'heure %s', $data_slot['start_hours']));
    }

    $formatted_start_hours = $start_hours->format('Hi');

    if ($formatted_start_hours != $data_slot['start_hours']) {
      throw new BadRequestHttpException(sprintf('Format heur non valide %s', $data_slot['start_hours']));
    }

    $end_hours = \DateTime::createFromFormat("Hi", $data_slot['end_hours']);

    if (FALSE === $end_hours) {
      throw new BadRequestHttpException(sprintf('Impossible de parser l\'heure %s', $data_slot['end_hours']));
    }

    $formatted_end_hours = $end_hours->format('Hi');
    if ($formatted_end_hours != $data_slot['end_hours']) {
      throw new BadRequestHttpException(sprintf('Format heur non valideeer %s', $data_slot['end_hours']));
    }
  }

}
