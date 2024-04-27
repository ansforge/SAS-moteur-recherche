<?php

namespace Drupal\sas_directory_pages\Entity;

use Drupal\sante_directory_pages\Entity\ServiceDeSante;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;

/**
 * ServiceDeSanteSas class.
 */
class ServiceDeSanteSas extends ServiceDeSante implements SasSnpHelperInterface {

  use SasSnpHelperTrait;

}
