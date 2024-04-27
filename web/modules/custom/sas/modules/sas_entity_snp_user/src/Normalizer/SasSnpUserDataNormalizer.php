<?php

namespace Drupal\sas_entity_snp_user\Normalizer;

use Drupal\sas_entity_snp_user\Entity\SasSnpUserData;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * SasSnpUserDataNormalizer class.
 */
class SasSnpUserDataNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = SasSnpUserData::class;

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
    if ($data instanceof SasSnpUserData) {
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
      $attributes[$key] = match ($key) {
        'cpts_locations' => isset($attribute[0]['value']) ? unserialize($attribute[0]['value'], ['allowed_classes' => FALSE]) : [],
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
    if ($type === '\Drupal\sas_entity_snp_user\Entity\SasSnpUserData') {
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
    foreach ($data as $key => $value) {
      $data[$key] = $key === 'cpts_locations' ? serialize(array_values(array_filter($value))) : $value;
    }

    if ($context['request_method'] === 'post') {
      return SasSnpUserData::create($data);
    }

    return $data;
  }

}
