<?php

namespace Drupal\sas_user\Commands;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_export\SasCsvFileInterface;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\user\UserInterface;
use Drush\Commands\DrushCommands;

/**
 * Class SettingsRppsAdeliMigration.
 *
 * Drush command that runs user_id migration into RPPS/ADELI.
 *
 * @package Drupal\sas_user\Commands
 */
class SettingsRppsAdeliMigration extends DrushCommands {

  /**
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $effectorHelper;

  /**
   * @var \Drupal\sas_export\SasCsvFileInterface
   */
  protected SasCsvFileInterface $csvFile;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * SAS Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasCoreService;

  /**
   * The IsInterfacedCommands constructor.
   */
  public function __construct(
    SasEffectorHelperInterface $effector_helper,
    SasCsvFileInterface $csv_file,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    SasCoreServiceInterface $sasCoreService
  ) {
    parent::__construct();
    $this->effectorHelper = $effector_helper;
    $this->csvFile = $csv_file;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->sasCoreService = $sasCoreService;
  }

  /**
   * Drush command that runs user_id migration into RPPS/ADELI id.
   *
   * @command sas_user:rpps_adeli_migration_settings*
   * @usage sas_user:rpps_adeli_migration_settingss
   *   migration and build error files but do not persits changes.
   */
  public function migrateSettingsRppsAdeli() {
    if (!$this->sasCoreService->isSasContext()) {
      throw new PluginException('Cette commande doit être lancé dans le context SAS.');
    }

    $settings_users = $this->getAllUserId();
    $errors = [];

    if (!empty($settings_users)) {
      foreach ($settings_users as $settings_user) {
        $user_id = $settings_user->uid;
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->entityTypeManager->getStorage('user')
          ->load($user_id);

        if (empty($user)) {
          continue;
        }

        $rpps_adeli = $this->effectorHelper->getRppsAdeliInUserId($user_id);
        $error = $this->checkSettingsRppsAdeliError($user, $rpps_adeli);

        if (!empty($error)) {
          $errors[] = $error;
          continue;
        }

        $sas_snp_user_data = $this->entityTypeManager->getStorage('sas_snp_user_data')
          ->loadByProperties([
            'user_id' => $user_id,
          ]);
        $sas_snp_user_data = reset($sas_snp_user_data);
        // Update user_id field.
        $sas_snp_user_data->set('user_id', $rpps_adeli);
        $sas_snp_user_data->save();
      }

      if (!empty($errors)) {
        $this->csvFile->buildCsvFile($errors);
        $this->writeln(sprintf('%d error(s) logged. See error file at %s', count($errors), $this->csvFile->getFilePath()));
      }
    }

    $this->writeln(sprintf('Migration %d users finished!', count($settings_users)));
  }

  /**
   * Check RPPS/ADELI and get error when error found.
   *
   * @param \Drupal\user\UserInterface $user
   *   Effector account to check.
   * @param string $rpps_adeli
   *   RPPS/ADELI to check.
   *
   * @return array|null
   *   Return an array representing the error or NULL if no error found.
   */
  protected function checkSettingsRppsAdeliError(UserInterface $user, string $rpps_adeli): ?array {

    // An RPPS/ADELI not empty or must have only one in sas_snp_user_data.
    if (empty($rpps_adeli) || $this->effectorHelper->isUserIdSettingsExists($rpps_adeli)) {
      return $this->buildStructureSettingsMigrationError($user, $rpps_adeli);
    }

    return NULL;
  }

  /**
   * Build an user RPPS/ADELI migration error array.
   *
   * @param \Drupal\user\UserInterface $user
   *   User to build the error for.
   * @param string|null $rpps_adeli
   *   List of RPPS/ADELI empty for this user Where exist in table sas_snp_user_data.
   *
   * @return array
   *   Error as an array with following properties :
   *    - uid
   *    - mail
   *    - lastname
   *    - firstname
   *    - rpps_adeli
   */
  protected function buildStructureSettingsMigrationError(UserInterface $user, string $rpps_adeli = NULL): array {
    return [
      'uid' => $user->id(),
      'mail' => $user->getEmail(),
      'lastname' => $user->hasField('field_sas_nom') && !$user->get('field_sas_nom')->isEmpty()
        ? $user->get('field_sas_nom')->value
        : '',
      'firstname' => $user->hasField('field_sas_prenom') && !$user->get('field_sas_prenom')->isEmpty()
        ? $user->get('field_sas_prenom')->value
        : '',
      'rpps_adeli' => !empty($rpps_adeli) ? $rpps_adeli : '',
    ];
  }

  /**
   * Build All user_id type 1 and role effector.
   *
   * @return array
   */
  protected function getAllUserId() {
    $query = $this->database->select('sas_snp_user_data', 'sas_settings');
    $query->addField('sas_settings', 'user_id', 'uid');
    $query->join('user__roles', 'roles', 'roles.entity_id = sas_settings.user_id');
    $query->condition('roles.roles_target_id', 'sas_effecteur');
    $query->condition('user_type', 1);
    return $query->execute()->fetchAll();
  }

}
