<?php

namespace Drupal\sas_user\Plugin\SimpleCron;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\simple_cron\Plugin\SimpleCronPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for ANS Password Expiration Single cron.
 */
abstract class AbstractCronPasswordExpiration extends SimpleCronPluginBase {

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
   * Drupal time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->configFactory = $container->get('config.factory');
    $instance->token = $container->get('token');
    $instance->time = $container->get('datetime.time');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $accounts = $this->getAccountsToNotify();
    foreach ($accounts as $rule => $steps) {
      foreach ($steps as $step => $users) {
        $this->notifyExpiringAccounts($users, $step, $rule);
      }
    }

    $this->flagExpiredAccounts();
  }

  /**
   * @param $rule
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getQuery($rule) {
    return $this->entityTypeManager->getStorage('user')
      ->getQuery()->accessCheck()
      ->condition('status', TRUE)
      ->condition('roles', $rule, 'IN');
  }

  /**
   * Check accounts that has not changed password.
   * According to selection rules.
   *
   * @retun array
   *   Array of users uid.
   */
  public function getAccountsToNotify() {
    $users = [];
    $allRules = $this->rule();
    $rules = array_keys($allRules);
    sort($rules, SORT_NUMERIC | SORT_ASC);
    $results = array_fill_keys($rules, $users);
    foreach ($rules as $rule) {
      $periods = [
        'first' => ($rule - 1) . ' month ago',
        'second' => ($rule - 1) . ' month ago -3 weeks',
      ];

      foreach ($periods as $warning => $period) {
        $min = new DrupalDateTime($period, date_default_timezone_get());
        $min->setTime(0, 0, 0);
        $max = new DrupalDateTime($period, date_default_timezone_get());
        $max->setTime(23, 59, 59);
        $query = $this->getQuery($allRules[$rule])->accessCheck();

        $orCondition = $query->orConditionGroup();
        $orCondition->condition('field_password_expiration', 0);
        $orCondition->condition('field_password_expiration', NULL, 'IS NULL');
        $query->condition($orCondition);

        $query->condition(
          'field_last_password_reset',
          [
            $min->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
            $max->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
          ],
          'BETWEEN');

        if (!empty($users)) {
          $query->condition('uid', $users, 'NOT IN');
        }

        $uids = $query->execute();

        $users = array_unique(array_merge($users, $uids));
        $results[$rule][$warning] = $uids;
      }
    }

    return $results;
  }

  /**
   * Send E-mail to notify user about password expiration by step.
   *
   * @param array $users
   *   Array of users.
   * @param string $step
   *   Step order.
   * @param int $expired
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function notifyExpiringAccounts(array $users, string $step, int $expired) {
    foreach ($users as $user) {
      $user = $this->entityTypeManager->getStorage('user')->load($user);
      $mailManager = $this->mailManager;
      $module = static::MAIL_MODULE;
      $key = static::MAIL_KEY . $step;
      $langCode = $user->getPreferredLangcode();
      // Send mail.
      $mailManager->mail($module, $key, $user->getEmail(), $langCode, ['user' => $user, 'expiration' => $expired]);
    }
  }

  /**
   * Flag expired password accounts.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function flagExpiredAccounts() {
    $users = $this->getExpiredAccounts();
    if (!empty($users)) {
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($users);
      foreach ($users as $user) {
        $user->set('field_password_expiration', TRUE)
          ->save();
      }
    }

  }

  /**
   * Check accounts that has not changed password.
   * According to selection rules.
   *
   * @retun array
   * Array of users uid.
   */
  public function getExpiredAccounts() {
    $results = [];
    $allRules = $this->rule();
    $rules = array_keys($allRules);
    sort($rules, SORT_NUMERIC | SORT_ASC);
    foreach ($rules as $rule) {
      $min = new DrupalDateTime($rule . ' month ago', date_default_timezone_get());
      $min->setTime(0, 0, 0);

      $query = $this->getQuery($allRules[$rule])->accessCheck();
      $query->condition('field_last_password_reset', $min->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<');
      $orCondition = $query->orConditionGroup();
      $orCondition->condition('field_password_expiration', FALSE);
      $orCondition->condition('field_password_expiration', NULL, 'IS NULL');
      $query->condition($orCondition);

      if (!empty($results)) {
        $query->condition('uid', $results, 'NOT IN');
      }

      $uids = $query->execute();

      $results = array_unique(array_merge($results, $uids));
    }

    return $results;
  }

}
