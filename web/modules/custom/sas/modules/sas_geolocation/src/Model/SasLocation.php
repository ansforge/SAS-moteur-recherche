<?php

declare(strict_types = 1);

namespace Drupal\sas_geolocation\Model;

/**
 * SasLocation class.
 */
final class SasLocation {

  /**
   * Location street number.
   *
   * @var string
   */
  private string $houseNumber = '';

  /**
   * Location street name.
   *
   * @var string
   */
  private string $street = '';

  /**
   * Location postal code.
   *
   * @var string
   */
  private string $postCode = '';

  /**
   * Location city.
   *
   * @var string
   */
  private string $city = '';

  /**
   * County code.
   *
   * @var string
   */
  private string $countyCode = '';

  /**
   * County name.
   *
   * @var string
   */
  private string $countyName = '';

  /**
   * Formatted full address.
   *
   * @var string
   */
  private string $fullAddress;

  /**
   * Search center latitude.
   *
   * @var float
   */
  private float $latitude;

  /**
   * Search center longitude.
   *
   * @var float
   */
  private float $longitude;

  /**
   * Default search radius (km).
   *
   * @var float
   */
  private float $defaultRadius;

  /**
   * Search enlargement value (km).
   *
   * @var float
   */
  private float $enlargementValue;

  /**
   * Location score, only for external provider (France BAN or Mapbox).
   *
   * @var float|null
   */
  private ?float $score;

  /**
   * Location data source (france_ban, mapbox, sas_api_county)
   *
   * @var string
   */
  private string $source;

  /**
   * Type of location (county, city, address).
   *
   * @var string
   */
  private string $type;

  /**
   * Create new location based on array corresponding to SasLocation properties.
   *
   * @param array $data
   *
   * @return \Drupal\sas_geolocation\Model\SasLocation
   */
  public static function create(array $data): SasLocation {
    $location = new self();
    foreach ($data as $name => $value) {
      $location->$name = $value;
    }
    return $location;
  }

  public function getHouseNumber(): ?string {
    return $this->houseNumber;
  }

  public function setHouseNumber(?string $houseNumber): SasLocation {
    $this->houseNumber = $houseNumber;
    return $this;
  }

  public function getStreet(): ?string {
    return $this->street;
  }

  public function setStreet(?string $street): SasLocation {
    $this->street = $street;
    return $this;
  }

  public function getPostCode(): ?string {
    return $this->postCode;
  }

  public function setPostCode(?string $postCode): SasLocation {
    $this->postCode = $postCode;
    return $this;
  }

  public function getInseeCode(): ?string {
    return $this->inseeCode;
  }

  public function setInseeCode(?string $inseeCode): SasLocation {
    $this->inseeCode = $inseeCode;
    return $this;
  }

  public function getCity(): ?string {
    return $this->city;
  }

  public function setCity(?string $city): SasLocation {
    $this->city = $city;
    return $this;
  }

  public function getCountyCode(): ?string {
    return $this->countyCode;
  }

  public function setCountyCode(?string $countyCode): SasLocation {
    $this->countyCode = $countyCode;
    return $this;
  }

  public function getCountyName(): ?string {
    return $this->countyName;
  }

  public function setCountyName(?string $countyName): SasLocation {
    $this->countyName = $countyName;
    return $this;
  }

  public function getFullAddress(): string {
    return $this->fullAddress;
  }

  public function setFullAddress(string $fullAddress): SasLocation {
    $this->fullAddress = $fullAddress;
    return $this;
  }

  public function getLatitude(): ?float {
    return $this->latitude;
  }

  public function setLatitude(float $latitude): SasLocation {
    $this->latitude = $latitude;
    return $this;
  }

  public function getLongitude(): ?float {
    return $this->longitude;
  }

  public function setLongitude(float $longitude): SasLocation {
    $this->longitude = $longitude;
    return $this;
  }

  public function getDefaultRadius(): ?float {
    return $this->defaultRadius;
  }

  public function setDefaultRadius(float $defaultRadius): SasLocation {
    $this->defaultRadius = $defaultRadius;

    return $this;
  }

  public function getEnlargementValue(): ?float {
    return $this->enlargementValue;
  }

  public function setEnlargementValue(?float $enlargementValue): SasLocation {
    $this->enlargementValue = $enlargementValue;

    return $this;
  }

  public function getScore(): ?float {
    return $this->score;
  }

  public function setScore(?float $score): SasLocation {
    $this->score = $score;

    return $this;
  }

  public function getSource(): ?string {
    return $this->source;
  }

  public function setSource(string $source): SasLocation {
    $this->source = $source;

    return $this;
  }

  public function getType(): ?string {
    return $this->type;
  }

  public function setType(string $type): SasLocation {
    $this->type = $type;

    return $this;
  }

  /**
   * Get SasLocation object as stdClass object.
   *
   * @return \stdClass
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function getObject(): \stdClass {
    $object = new \stdClass();
    foreach ($this as $key => $value) {
      $object->$key = $value;
    }
    return $object;
  }

}
