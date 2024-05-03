<?php

namespace Drupal\sas_structure\Normalizer;

use Drupal\sas_structure\Entity\SasStructureSettings;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * SasSnpUserDataNormalizer class.
 */
class SasStructureSettingsNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected string $supportedInterfaceOrClass = SasStructureSettings::class;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, string $format = NULL, array $context = []): bool {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }

    // This custom normalizer should be supported for "Article" nodes.
    if ($data instanceof SasStructureSettings) {
      return TRUE;
    }

    // Otherwise, this normalizer does not support the $data object.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    $attributes = parent::normalize($entity, $format, $context);

    foreach ($attributes as $key => $attribute) {
      $attributes[$key] = match($key) {
        'uid' => $attribute[0]['target_id'] ?? NULL,
        default => $attribute[0]['value'] ?? NULL,
      };
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function supportsDenormalization($data, $type, string $format = NULL, array $context = []): bool {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!$this->checkFormat($format)) {
      return FALSE;
    }

    // This custom normalizer should be supported for "Article" nodes.
    if ($type === SasStructureSettings::class) {
      return TRUE;
    }

    // Otherwise, this normalizer does not support the $data object.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {
    return SasStructureSettings::create($data);
  }

}
