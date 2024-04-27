<?php

namespace Drupal\sas_user_settings\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Sas user settings entities.
 *
 * @ingroup sas_user_settings
 */
interface SasUserSettingsInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

}
