<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Analytics;

use Drupal\sas_api_client\Plugin\AbstractAnalyticsClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "log_create",
 *   label = @Translation("Analytics - Create log endpoint"),
 *   category = "analytics",
 *   endpoint = "/{version}/logging",
 *   method = "POST",
 *   get_exception = TRUE,
 *   exposed = TRUE,
 *   body = {
 *    "logName": NULL,
 *    "date": NULL,
 *    "origin": NULL,
 *    "content": NULL,
 *   }
 * )
 */
class LogCreate extends AbstractAnalyticsClientPluginBase {

}
