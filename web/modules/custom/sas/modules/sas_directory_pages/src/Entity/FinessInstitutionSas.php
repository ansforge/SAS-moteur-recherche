<?php

namespace Drupal\sas_directory_pages\Entity;

use Drupal\sante_directory_pages\Entity\FinessInstitution;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;

/**
 * FinessInstitutionSas class.
 */
class FinessInstitutionSas extends FinessInstitution implements SasSnpHelperInterface {

  use SasSnpHelperTrait;

}
