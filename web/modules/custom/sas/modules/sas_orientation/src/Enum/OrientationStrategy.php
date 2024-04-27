<?php

namespace Drupal\sas_orientation\Enum;

/**
 * Class OrientationStrategy.
 *
 * Defines constants for orientation strategy.
 *
 * @package Drupal\sas_orientation\Enum
 */
final class OrientationStrategy {

  /**
   * Orientation strategy none to ignore orientations.
   */
  public const ORIENTATION_STRATEGY_NONE = 0;

  /**
   * Orientation strategy data to only get orientation data in slots.
   */
  public const ORIENTATION_STRATEGY_DATA = 1;

  /**
   * Orientation strategy full to get all slot even if max patient number reached.
   */
  public const ORIENTATION_STRATEGY_FULL = 2;

  /**
   * Orientation strategy both to get all slot even if full and with orientation data.
   */
  public const ORIENTATION_STRATEGY_BOTH = 3;

}
