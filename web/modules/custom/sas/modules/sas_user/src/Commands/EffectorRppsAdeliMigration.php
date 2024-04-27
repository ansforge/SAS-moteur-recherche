<?php

namespace Drupal\sas_user\Commands;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_export\SasCsvFileInterface;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\user\UserInterface;
use Drush\Commands\DrushCommands;

/**
 * Class EffectorRppsAdeliMigration.
 *
 * Drush command to manage migration to RPPS/ADELI in effector account.
 *
 * @package Drupal\sas_user\Commands
 */
class EffectorRppsAdeliMigration extends DrushCommands {

  /**
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $effectorHelper;

  /**
   * @var \Drupal\sas_export\SasCsvFileInterface
   */
  protected SasCsvFileInterface $csvFile;

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
    SasCoreServiceInterface $sasCoreService
  ) {
    parent::__construct();
    $this->effectorHelper = $effector_helper;
    $this->csvFile = $csv_file;
    $this->sasCoreService = $sasCoreService;
  }

  /**
   * Drush command that runs RPPS/ADELI migration into effector accounts.
   *
   * @command sas_user:rpps_adeli_migration
   * @option save_changes
   *   Pass this options to persist RPPS/ADELI migration.
   *
   * @usage sas_user:rpps_adeli_migration
   *   Simulate migration and build error files but do not persits changes.
   */
  public function migrateRppsAdeli() {
    if (!$this->sasCoreService->isSasContext()) {
      throw new PluginException('Cette commande doit être lancé dans le context SAS.');
    }

    /** @var \Drupal\user\UserInterface[] $effectors */
    $effectors = $this->effectorHelper->getAllEffectors();

    $errors = [];

    if (!empty($effectors)) {
      foreach ($effectors as $effector) {
        // Update RPPS/ADELI field only if not already filled.
        if ($effector->hasField('field_sas_rpps_adeli') && $effector->get('field_sas_rpps_adeli')->isEmpty()) {
          // Get all RPPS/ADELI corresponding to its professional sheets.
          $rpps_adeli = $this->effectorHelper->getEffectorRppsAdeliBySheets($effector);
          $error = $this->checkRppsAdeliError($effector, $rpps_adeli);

          if (!empty($error)) {
            $errors[] = $error;
            continue;
          }

          $rpps_adeli = reset($rpps_adeli);
          // Update RPPS/ADELI account field.
          $effector->set('field_sas_rpps_adeli', $rpps_adeli['prefix'] . $rpps_adeli['id']);
          $effector->save();
        }
      }

      if (!empty($errors)) {
        $this->csvFile->buildCsvFile($errors);
        $this->writeln(sprintf('%d error(s) logged. See error file at %s', count($errors), $this->csvFile->getFilePath()));
      }
    }

    $this->writeln('Migration finished!');
  }

  /**
   * Check RPPS/ADELI and get error when error found.
   *
   * @param \Drupal\user\UserInterface $effector
   *   Effector account to check.
   * @param array $rpps_adeli
   *   RPPS/ADELI to check.
   *
   * @return array|null
   *   Return an array representing the error or NULL if no error found.
   */
  protected function checkRppsAdeliError(UserInterface $effector, array $rpps_adeli): ?array {
    // An effector must have only one RPPS/ADELI to be migrate.
    if (empty($rpps_adeli) || count($rpps_adeli) > 1) {
      return $this->buildUserMigrationError($effector, $rpps_adeli);
    }

    // An effector must have a unique RPPS/ADELI to be migrate.
    if ($this->effectorHelper->userRppsAdeliExists(reset($rpps_adeli)['id'])) {
      return $this->buildUserMigrationError($effector, $rpps_adeli);
    }

    return NULL;
  }

  /**
   * Build an user RPPS/ADELI migration error array.
   *
   * @param \Drupal\user\UserInterface $user
   *   User to build the error for.
   * @param array|null $rpps_adeli
   *   List of RPPS/ADELI foudn for this user.
   *
   * @return array
   *   Error as an array with following properties :
   *    - uid
   *    - mail
   *    - lastname
   *    - firstname
   *    - rpps_adeli
   */
  protected function buildUserMigrationError(UserInterface $user, array $rpps_adeli = NULL): array {
    return [
      'uid' => $user->id(),
      'mail' => $user->getEmail(),
      'lastname' => $user->hasField('field_sas_nom') && !$user->get('field_sas_nom')->isEmpty()
        ? $user->get('field_sas_nom')->value
        : '',
      'firstname' => $user->hasField('field_sas_prenom') && !$user->get('field_sas_prenom')->isEmpty()
        ? $user->get('field_sas_prenom')->value :
      '',
      'rpps_adeli' => !empty($rpps_adeli)
        ? implode(',', array_column($rpps_adeli, 'id'))
        : '',
    ];
  }

}
