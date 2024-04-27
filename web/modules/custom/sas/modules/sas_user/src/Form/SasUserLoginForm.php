<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sante_user\Form\SanteUserLoginForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for SAS User Login.
 */
class SasUserLoginForm extends SanteUserLoginForm {

  /**
   * SAS Core service.
   *
   * @var Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->sasCoreService = $container->get('sas_core.service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    if ($this->routeMatch->getRouteName() == 'user.login' && $this->sasCoreService->isSasContext()) {
      $cacheMetadata = new CacheableMetadata();
      $config = \Drupal::config('sas_config.user_account');
      $cacheMetadata->addCacheableDependency($config);
      $form['#prefix'] = $config->get('texts.login.info_text')
        ? '<div id="user-login-form-ajax-wrapper" class="block-login wrapper-user-login-form"><div class="markup">' .
        $config->get('texts.login.info_text') . '</div>'
        : '<div id="user-login-form-ajax-wrapper" class="block-login wrapper-user-login-form">';
      $cacheMetadata->merge(CacheableMetadata::createFromRenderArray($form))->applyTo($form);
    }

    return $form;
  }

}
