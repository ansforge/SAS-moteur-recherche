<?php

namespace Drupal\sas_structure\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_structure\Service\StructureHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the StructureSettingsValid constraint.
 */
class StructureSettingsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $value */
    if (!empty($value->sas_participation->value)) {

      if (empty($value->hours_available->value)) {
        $this->context->addViolation($constraint->invalidHoursAvailable);
      }

      if (
        $value->id_type->value == StructureConstant::ID_TYPE_FINESS
        && empty($value->practitioner_count->value)
      ) {
        $this->context->addViolation($constraint->invalidPractitionerCount);
      }
    }
  }

}
