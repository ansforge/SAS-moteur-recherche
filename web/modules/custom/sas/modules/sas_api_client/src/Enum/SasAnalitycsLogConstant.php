<?php

namespace Drupal\sas_api_client\Enum;

/**
 * Class SasAnalitycsLogConstant.
 *
 * Provides constants for analytics logs.
 *
 * @package Drupal\sas_api_client\Enum
 */
final class SasAnalitycsLogConstant {

  /**
   * Date format for log date.
   */
  public const LOG_DATE_FORMAT = 'Y-m-d H:i:s';

  /**
   * Log origin for back-end log.
   */
  public const LOG_ORIGIN_BACK = 'sas-back';

  /**
   * Log search redirection to error page.
   */
  public const LOG_SEARCH_REDIRECT_ERROR = 'search_error';

  /**
   * Log search redirection to homepage.
   */
  public const LOG_SEARCH_REDIRECT_HOMEPAGE = 'search_homepage';

}
