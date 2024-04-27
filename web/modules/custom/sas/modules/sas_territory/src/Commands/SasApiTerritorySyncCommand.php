<?php

namespace Drupal\sas_territory\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Class SasApiTerritorySyncCommand.
 *
 * Provide a Drush command to manage mass synchronisation of territory terms
 * with SAS-API.
 *
 * @package Drupal\sas_territory\Commands
 */
class SasApiTerritorySyncCommand extends DrushCommands {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Drush command to synchronize SAS - Territoire terms with SAS-API.
   *
   * This command will also filled SAS-API Id field in each Drupal term.
   *
   * @command sas_territory:sas-api-sync
   * @usage sas_territory:sas-api-sync
   */
  public function synchronizeTerritories() {

    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'sas_territoire']);
    if (!empty($terms)) {
      $countTerm = count($terms);
      $counter = 0;
      foreach ($terms as $term) {
        // Just save existing term to force synchro with sas-api.
        $term->save();
        $counter += 1;
        $this->output()->writeln('Territoires synchronisés : ' . $counter . '/' . $countTerm);
      }
    }
    else {
      $this->output()->writeln('Aucun territoire à synchroniser.');
    }
    $this->output()->writeln('Opération terminée.');
  }

}
