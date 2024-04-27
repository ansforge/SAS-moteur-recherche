<?php

namespace Drupal\sas_api_client\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SAS API endpoint plugin item annotation object.
 *
 * @see \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ClientEndpointPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The endpoint uri with token replacement if necessary.
   *
   * @var string
   */
  public $endpoint;

  /**
   * The endpoint category.
   *
   * @var string
   */
  public $category;

  /**
   * The endpoint method.
   *
   * @var string
   */
  public $method = 'GET';

  /**
   * The endpoint tokens default values array.
   *
   * @var array
   */
  public $default_tokens = [];

  /**
   * The endpoint query params array.
   *
   * @var array
   */
  public $query = [];

  /**
   * The endpoint postData params array.
   *
   * @var array
   */
  public $body = [];

  /**
   * The endpoint api user type (read, write).
   *
   * @var string|null
   */
  public $api_user = 'read';

  /**
   * The endpoint exposed flag.
   *
   * @var bool
   */
  public $exposed = FALSE;

}
