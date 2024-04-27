<?php

namespace Drupal\sas_user\Enum;

use Drupal\user\Entity\Role;

/**
 * SasUserConstants class.
 */
final class SasUserConstants {

  /**
   * @const string page
   */
  const SAS_PAGE = 'page';

  const SAS_ADMIN_ROLE = 'sas_administrateur';

  const SAS_ADMIN_NAT_ROLE = 'sas_administrateur_national';

  const SAS_EFFECTOR_ROLE = 'sas_effecteur';

  const SAS_REGULATOR_OSNP_ROLE = 'sas_regulateur_osnp';

  const SAS_IOA_ROLE = 'sas_ioa';

  const SAS_STRUCT_MANAGER_ROLE = 'sas_gestionnaire_de_structure';

  const SAS_ACCOUNT_MANAGER_ROLE = 'sas_gestionnaire_de_comptes';

  const SAS_DELEGATE_ROLE = 'sas_delegataire';

  const SAS_REFERENT_TERRITORIAL_ROLE = 'sas_referent_territorial';

  const SAS_TEST_EDITEUR_LRM = 'sas_test_editeur_lrm';

  const SAS_DRUPAL_API = 'sas_drupal_api';


  /**
   * SAS Admin roles rid array.
   *
   * @const array
   */
  const SAS_ADMIN_ROLES = [
    self::SAS_ADMIN_ROLE,
    self::SAS_ADMIN_NAT_ROLE,
  ];

  /**
   * SAS user edit allowed roles.
   *
   * @const array
   */
  const SAS_ADMIN_USER_ROLES = [
    self::SAS_ADMIN_ROLE,
    self::SAS_ADMIN_NAT_ROLE,
    self::SAS_ACCOUNT_MANAGER_ROLE,
  ];

  /**
   * SAS user roles with user update territory.
   *
   * @const array
   */
  const SAS_USER_UPDATE_TERRITORY = [
    self::SAS_STRUCT_MANAGER_ROLE,
    self::SAS_DELEGATE_ROLE,
    self::SAS_EFFECTOR_ROLE,
  ];

  /**
   * Get regulator roles.
   *
   * @return string[]
   *   List of regulator roles.
   */
  public static function getRegulatorRoles(): array {
    return [
      self::SAS_REGULATOR_OSNP_ROLE,
      self::SAS_IOA_ROLE,
    ];
  }

  /**
   * RPPS ID prefix.
   */
  const PREFIX_ID_RPPS = '8';

  /**
   * ADELI ID prefix.
   */
  const PREFIX_ID_ADELI = '0';

  const SAS_USER_REGISTRATION_MAIL_DEFAULT = "
    <p>Bonjour [user:field_sas_nom] [user:field_sas_prenom],<br />
    <br />
    Votre territoire bénéficie du Service d'Accès aux Soins.
    Cliquez <a href=\"[user:pass_reset_link]\">ici</a> pour réaliser votre première connexion.<br />
    <br />
    Cordialement,<br />
    <br />
    L'équipe SAS</p>
    ";

  const TIMEZONE_REGION_MAPPING = [
    'FR-PF' => '-1000',
    'FR-CP' => '-0800',
    'FR-GP' => '-0400',
    'FR-BL' => '-0400',
    'FR-MF' => '-0400',
    'FR-MQ' => '-0400',
    'FR-GF' => '-0300',
    'FR-PM' => '-0300',
    'FR-YT' => '+0300',
    'FR-RE' => '+0400',
    'FR-TF' => '+0500',
    'FR-NC' => '+1100',
    'FR-WF' => '+1200',
  ];

  const TIMEZONE_REGION_MAPPING_TEXT = [
    'FR-PF' => 'Pacific/Tahiti',
    'FR-CP' => 'Pacific/Marquesas',
    'FR-GP' => 'America/Guadeloupe',
    'FR-BL' => 'America/St_Barthelemy',
    'FR-MF' => 'America/Marigot',
    'FR-MQ' => 'America/Martinique',
    'FR-GF' => 'America/Cayenne',
    'FR-PM' => 'America/Miquelon',
    'FR-YT' => 'Indian/Mayotte',
    'FR-RE' => 'Indian/Reunion',
    'FR-TF' => 'Indian/Kerguelen',
    'FR-NC' => 'Pacific/Noumea',
    'FR-WF' => 'Pacific/Wallis',
  ];

  /**
   * Get the SAS roles list.
   *
   * @return array
   */
  public static function getSasRoles(): array {
    $rolesEntities = Role::loadMultiple();
    $roles = [];

    /** @var \Drupal\user\RoleInterface $role */
    foreach ($rolesEntities as $role) {
      if (str_contains($role->id(), 'sas_')) {
        $roles[] = $role->id();
      }
    }

    return $roles;
  }

}
