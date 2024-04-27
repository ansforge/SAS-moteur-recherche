<?php

namespace Drupal\sas_entity_snp_user\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_structure\Service\StructureHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EffectorSettingsValid constraint.
 */
class EffectorSettingsValidConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Structure helper.
   *
   * @var \Drupal\sas_structure\Service\StructureHelper
   */
  protected StructureHelper $structureHelper;

  /**
   * FinessIsValidConstraintValidator constructor.
   *
   * @param \Drupal\sas_structure\Service\StructureHelper $structureHelper
   *   Structure helper.
   */
  public function __construct(StructureHelper $structureHelper) {
    $this->structureHelper = $structureHelper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_structure.helper')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    /** @var \Drupal\sas_entity_snp_user\Entity\SasSnpUserData $value */
    if ($value->participation_sas->value) {
      if (empty($value->participation_sas_via->value)) {
        $this->context->addViolation($constraint->emptySasParticipationVia);
      }

      if (
        $value->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_CPTS ||
        $value->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_MSP
      ) {
        if ($value->get('structure_finess')->isEmpty()) {
          $this->context->addViolation($constraint->emptyFiness);
        }
        else {
          if (empty($this->structureHelper->getStructureDataByFiness($value->structure_finess->value))) {
            $this->context->addViolation($constraint->notExistingFiness);
          }
        }
      }

      if (
        $value->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_CPTS &&
        $value->get('cpts_locations')->isEmpty()
      ) {
        $this->context->addViolation($constraint->emptyCptsLocations);
      }

      if (($value->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_OFFICE ||
          $value->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_CPTS ||
          $value->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_MSP)
        && $value->get('has_software')->isEmpty()) {
        $this->context->addViolation($constraint->emptyHasSoftware);
      }

      if (
        $value->has_software->value == "0" &&
        $value->hours_available->value == "0" &&
        $value->participation_sas_via->value != SnpUserDataConstant::SAS_PARTICIPATION_MY_SOS_MEDECIN
      ) {
        $this->context->addViolation($constraint->invalidHoursAvailable);
      }

      if (
        $value->has_software->value == "1" &&
        $value->hours_available->value == "0" &&
        $value->editor_disabled->value == "1" &&
        $value->participation_sas_via->value != SnpUserDataConstant::SAS_PARTICIPATION_MY_SOS_MEDECIN
      ) {
        $this->context->addViolation($constraint->invalidHoursAvailable);
      }
    }
  }

}
