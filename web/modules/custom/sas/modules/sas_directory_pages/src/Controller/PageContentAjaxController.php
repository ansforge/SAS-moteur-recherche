<?php

namespace Drupal\sas_directory_pages\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Controller returning async directory page content to mitigate slow data UX.
 */
class PageContentAjaxController extends ControllerBase {

  /**
   * Builds AjaxResponse.
   */
  public function pageContentAjax(NodeInterface $node) {

    $view_builder = $this->entityTypeManager()->getViewBuilder('node');
    $render_array = $view_builder->view($node, 'full');

    // We pass info about the rendering context.
    $render_array['directory_page_content_ajax'] = TRUE;

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#sas-directory-page-content-ajax-placeholder', $render_array));

    return $response;
  }

}
