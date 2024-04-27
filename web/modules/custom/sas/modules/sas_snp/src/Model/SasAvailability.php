<?php

namespace Drupal\sas_snp\Model;

/**
 * Represents the SasAvailability entity.
 */
class SasAvailability {

  /**
   * The Nid node.
   */
  private int $nid;

  /**
   * Field has snp.
   */
  private bool $hasSnp;

  /**
   * Field is_interfaced.
   */
  private bool $isInterfaced;

  /**
   * Constructor for SasAvailability.
   *
   * @param int $nid
   *   The Nid node.
   * @param bool $hasSnp
   *   The field has_snp in SAS API.
   * @param bool $isInterfaced
   *   The field is_interfaced in aggreg.
   */
  public function __construct(int $nid, bool $hasSnp, bool $isInterfaced) {
    $this->nid = $nid;
    $this->hasSnp = $hasSnp;
    $this->isInterfaced = $isInterfaced;
  }

  /**
   * Get the Nid node.
   *
   * @return int
   *   The Nid node.
   */
  public function getNid() {
    return $this->nid;
  }

  /**
   * Get the field has_snp in SAS API.
   *
   * @return bool
   *   The field has_snp in SAS API.
   */
  public function isHasSnp(): bool {
    return $this->hasSnp;
  }

  /**
   * Get the field is_interfaced in aggreg.
   *
   * @return bool
   *   The field is_interfaced in aggreg.
   */
  public function isInterfaced(): bool {
    return $this->isInterfaced;
  }

  /**
   * Create a new SasAvailability object.
   *
   * @param int $nid
   *   The Nid node.
   * @param bool $hasSnp
   *   The field has_snp in SAS API.
   * @param bool $isInterfaced
   *   The field is_interfaced in aggreg.
   *
   * @return SASAvailability
   *   A new SASAvailability object.
   */
  public static function create(int $nid, bool $hasSnp, bool $isInterfaced): SasAvailability {
    return new self($nid, $hasSnp, $isInterfaced);
  }

  public function setHasSnp($hasSnp): void {
    $this->hasSnp = $hasSnp;
  }

  public function setIsInterfaced($isInterfaced): void {
    $this->isInterfaced = $isInterfaced;
  }

}
