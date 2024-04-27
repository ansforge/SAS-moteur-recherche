<?php

namespace Drupal\sas_user\Plugin\SimpleCron;

use Drupal\simple_cron\Plugin\SimpleCronPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Single cron.
 *
 * @SimpleCron(
 *   id = "sas_user_account_expiration",
 *   label = @Translation("SAS - account expiration", context = "Simple cron")
 * )
 */
class SasUserCronAccountExpiration extends SimpleCronPluginBase {

  const SAS_USER_EXPIRATION_FIRST_SUBJECT_MAIL = "Attention : Désactivation de votre compte SAS";

  const SAS_USER_EXPIRATION_FIRST_MESSAGE_MAIL = "
          <p>Bonjour,<br />
          <br />
          Pour votre information, votre compte n'a pas été utilisé depuis 8 mois.<br />
          <br />
          Afin d'éviter sa suppression, merci de vous connecter dans les 30 jours avec le lien ci-dessous :<br />
          <br />
          [sas_token:sas_base_url]<br />
          <br />
          Bien cordialement,<br />
          <br />
          L'équipe SAS</p>
          ";

  const SAS_USER_EXPIRATION_SECOND_SUBJECT_MAIL = "Attention : Désactivation de votre compte SAS dans une semaine";

  const SAS_USER_EXPIRATION_SECOND_MESSAGE_MAIL = "
          <p>Bonjour,<br />
          <br />
          Pour votre information, votre compte n'a pas été utilisé depuis 9 mois.<br />
          <br />
          Afin d'éviter sa suppression, merci de vous connecter dans les 7 jours avec le lien ci-dessous :<br />
          <br />
          [sas_token:sas_base_url]<br />
          <br />
          Bien cordialement,<br />
          <br />
          L'équipe SAS</p>
          ";

  const SAS_USER_EXPIRATION_THIRD_SUBJECT_MAIL = "Attention : Suppression de votre compte SAS";

  const SAS_USER_EXPIRATION_THIRD_MESSAGE_MAIL = "
          <p>Bonjour,<br />
          <br />
          Pour votre information, votre compte n'a pas été utilisé depuis plus de 9 mois.<br />
          <br />
          Votre compte SAS a été supprimé, vous pouvez vous rendre sur le site via ce lien :<br />
          <br />
          [sas_token:sas_base_url]<br />
          <br />
          Bien cordialement,<br />
          <br />
          L'équipe SAS</p>
          ";

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The token manager.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|object|null
   */
  protected $configFactory;

  /**
   * SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->configFactory = $container->get('config.factory');
    $instance->token = $container->get('token');
    $instance->sasCoreService = $container->get('sas_core.service');
    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->sasCoreService->isSasContext()) {
      $min8M = strtotime('-8 months  0 hours 0 seconds', strtotime('today'));
      $endMin8M = strtotime('-8 months 24 hours -1 seconds', strtotime('today'));
      $min9M = strtotime('-9 months 0 hours 0 seconds', strtotime('today'));
      $endMin9M = strtotime('-9 months 24 hours -1 seconds', strtotime('today'));
      $min9MPlus7d = strtotime('-9 months -7 days 0 hours 0 second', strtotime('today'));
      $endMin9MPlus7d = strtotime('-9 months -7 days 24 hours -1 second', strtotime('today'));
      $this->accountsToDisableSendEmail($this->getSasAccountsToDisable($min8M, $endMin8M, 'first'), 'first');
      $this->accountsToDisableSendEmail($this->getSasAccountsToDisable($min9M, $endMin9M, 'second'), 'second');
      $this->accountsToDisableSendEmail($this->getSasAccountsToDisable($min9MPlus7d, $endMin9MPlus7d, 'third'), 'third');
    }
  }

  /**
   * Check accounts that has not been connected
   * withing 8 months ou later.
   *
   * @retun array
   * Array of users.
   * Containing :
   *  - uid : the user ID.
   *  - mail : account e-mail.
   */
  public function getSasAccountsToDisable($start, $end, $step) {
    // Get users to send mail.
    switch ($step) {
      case 'second':
        $compteur_inactivite = 1;
        break;

      case 'third':
        $compteur_inactivite = 2;
        break;

      default:
        $compteur_inactivite = 0;
        break;
    }
    $results = $this->entityTypeManager->getStorage('user')
      ->getQuery()->accessCheck()
      ->condition('login', [$start, $end], 'BETWEEN')
      ->condition('roles', 'sas_%', 'LIKE')
      ->condition('field_sas_compteur_inactivite', $compteur_inactivite)
      ->execute();
    return !empty($results) ? $this->entityTypeManager->getStorage('user')->loadMultiple($results) : [];
  }

  /**
   * Send E-mail to inform about cancellation by step.
   *
   * @param array $users
   *   Array of users.
   * @param string $step
   *   Step order.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function accountsToDisableSendEmail(array $users, string $step) {
    $body_format = filter_default_format();
    if (empty($users)) {
      return;
    }
    $config_deactivation = $this->configFactory->get('sas_config.user_account')
      ->get('texts')['deactivation_mail'];
    $from = $this->configFactory->get('system.site')->get('mail') ?? 'nepasrepondre@sante.fr';
    foreach ($users as $user) {
      /** @var \Drupal\user\UserInterface $user */
      $to = $user->getEmail();
      if ($step === 'first') {
        // @todo These 3 x 4 lines could be factorized.
        $cfg_first_mail = $config_deactivation['first_mail'];
        $subject = empty($cfg_first_mail['mail_subject']) ? self::SAS_USER_EXPIRATION_FIRST_SUBJECT_MAIL : $cfg_first_mail['mail_subject'];
        $body = empty($cfg_first_mail['mail_content']['value']) ? self::SAS_USER_EXPIRATION_FIRST_MESSAGE_MAIL : $cfg_first_mail['mail_content']['value'];
        $body_format = empty($cfg_first_mail['mail_content']['format']) ? $body_format : $cfg_first_mail['mail_content']['format'];
        $user->set('field_sas_compteur_inactivite', 1);
        $user->save();
      }
      if ($step === 'second') {
        $cfg_second_mail = $config_deactivation['second_mail'];
        $subject = empty($cfg_second_mail['mail_subject']) ? self::SAS_USER_EXPIRATION_SECOND_SUBJECT_MAIL : $cfg_second_mail['mail_subject'];
        $body = empty($cfg_second_mail['mail_content']['value']) ? self::SAS_USER_EXPIRATION_SECOND_MESSAGE_MAIL : $cfg_second_mail['mail_content']['value'];
        $body_format = empty($cfg_second_mail['mail_content']['format']) ? $body_format : $cfg_second_mail['mail_content']['format'];
        $user->set('field_sas_compteur_inactivite', 2);
        $user->save();
      }
      if ($step === 'third') {
        $cfg_third_mail = $config_deactivation['third_mail'];
        $subject = empty($cfg_third_mail['mail_subject']) ? self::SAS_USER_EXPIRATION_THIRD_SUBJECT_MAIL : $cfg_third_mail['mail_subject'];
        $body = empty($cfg_third_mail['mail_content']['value']) ? self::SAS_USER_EXPIRATION_THIRD_MESSAGE_MAIL : $cfg_third_mail['mail_content']['value'];
        $body_format = empty($cfg_third_mail['mail_content']['format']) ? $body_format : $cfg_third_mail['mail_content']['format'];

        if ($user->get('field_sas_compteur_inactivite')->value == 2) {
          foreach ($user->getRoles() as $user_role) {
            // Remove sas roles for this user.
            if (str_starts_with($user_role, 'sas_')) {
              $user->removeRole($user_role);
            }
          }
        }
        $user->save();
      }
      // Mail parameters.
      $body = $this->token->replace($body, [], ['clear' => TRUE]);
      $params = [
        'body' => check_markup($body, $body_format),
        'subject' => $subject,
      ];
      $mailManager = $this->mailManager;
      $module = 'sas_user';
      $key = 'sas_user_account_expiration';
      $langCode = $user->getPreferredLangcode();
      // Send mail.
      $mailManager->mail($module, $key, $to, $langCode, $params, $from, TRUE);
    }
  }

}
