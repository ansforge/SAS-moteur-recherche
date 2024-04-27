<?php

namespace Drupal\sas_user\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trait privacy policy form.
 */
trait PrivacyPolicyFormTrait {

  /**
   * Privacy policy form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  protected function buildPrivacyPolicyForm(array &$form, FormStateInterface $form_state) {
    if ($this->sasCoreService->isSasContext()) {
      $cacheableMetadata = new CacheableMetadata();
      $config = $this->config('sas_config.user_account')->get('texts') ?? [];
      $privacy_policy = '';
      if (is_array($config)) {
        $cacheableMetadata->addCacheableDependency($config);
        if (isset($config['privacy_notice'])) {
          $privacy_policy = check_markup(
            $config['privacy_notice']['value'] ?? '',
            $config['privacy_notice']['format'] ?? filter_default_format()
          );
        }
      }

      $form['privacy_policy'] = [
        '#markup' => new FormattableMarkup('<div class="privacy-policy">@privacy_policy</div>', [
          '@privacy_policy' => $privacy_policy,
        ]),
        '#weight' => 100,
      ];
      $cacheableMetadata->applyTo($form['privacy_policy']);
    }
  }

}
