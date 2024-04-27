<?php

namespace Drupal\sas_geolocation\Service;

use Drupal\node\Entity\Node;
use Drupal\sas_snp\Service\SnpContentHelperInterface;
use Drupal\sas_user\Enum\SasUserConstants;

/**
 * Class SasTimezoneHelper.
 *
 * Define timezone helper service.
 *
 * @package Drupal\sas_geolocation\Service
 */
class SasTimezoneHelper implements SasTimezoneHelperInterface {

  /**
   * If content type allowed for SNP.
   *
   * @var \Drupal\sas_snp\Service\SnpContentHelperInterface
   */
  protected SnpContentHelperInterface $snpContentHelper;

  /**
   * SasTimezoneHelper constructor.
   *
   * @param \Drupal\sas_snp\Service\SnpContentHelperInterface $snpContentHelper
   *   SNP content helper.
   */
  public function __construct(SnpContentHelperInterface $snpContentHelper) {
    $this->snpContentHelper = $snpContentHelper;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceTimezone(Node $node): string {
    $timezone = "Europe/Paris";

    if ($this->snpContentHelper->isSupportSasSnpEntity($node) &&
      $node->hasField('field_region_permission') &&
      !$node->get('field_region_permission')->isEmpty()
    ) {
      $region_iso_code = $node->get('field_region_permission')
        ->referencedEntities()[0]
        ->get('field_iso_code')
        ->value;

      if (!empty($region_iso_code) && !empty(SasUserConstants::TIMEZONE_REGION_MAPPING_TEXT[$region_iso_code])) {
        $timezone = SasUserConstants::TIMEZONE_REGION_MAPPING_TEXT[$region_iso_code];
      }
    }

    return $timezone;
  }

}
