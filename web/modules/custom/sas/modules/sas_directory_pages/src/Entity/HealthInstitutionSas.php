<?php

namespace Drupal\sas_directory_pages\Entity;

use Drupal\sante_directory_pages\Entity\HealthInstitution;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;

/**
 * HealthInstitutionSas class.
 */
class HealthInstitutionSas extends HealthInstitution implements SasSnpHelperInterface {

  use SasSnpHelperTrait;

}
