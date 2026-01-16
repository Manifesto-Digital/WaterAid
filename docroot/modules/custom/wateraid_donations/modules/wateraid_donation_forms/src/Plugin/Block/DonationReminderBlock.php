<?php

declare(strict_types=1);

namespace Drupal\wateraid_donation_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a donation reminder block.
 *
 * @Block(
 *   id = "wateraid_donation_forms_donation_reminder",
 *   admin_label = @Translation("Donation Reminder"),
 *   category = @Translation("Donations"),
 * )
 */
final class DonationReminderBlock extends BlockBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#lazy_builder' => [
        $this::class . '::lazyBuilder',
        [],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'lazyBuilder'
    ];
  }

  /**
   * Generate the appropriate message.
   *
   * @return array
   */
  public static function lazyBuilder(): array {
    $build = [
      'content' => [
        '#markup' => '',
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $show_message = $entity = FALSE;

    $params = \Drupal::routeMatch()->getParameters()->all();

    // If we have a relationship, we should get the entity from there.
    if (isset($params['group_relationship'])) {
      $entity = $params['group_relationship']->getEntity();
    }
    elseif (isset($params['node'])) {

      // If we have a node, use that.
      $entity = $params['node'];
    }
    elseif (isset($params['group']) && count($params) == 1) {

      // If we have a group and no other params, we're looking directly at a
      // group and should check that.
      $entity = $params['group'];
    }

    if ($entity && $entity->hasField('field_show_donation_reminder')) {
      if ($entity->get('field_show_donation_reminder')->getString()) {
        $show_message = TRUE;
      }
    }

    if ($show_message) {
      if ($session = \Drupal::request()->getSession()) {
        if ($data = $session->get('wa_donation')) {
          $build['content'] = [
            '#theme' => 'wateraid_donation_forms_banner',
            '#amount' => $data['amount'],
            '#uri' => $data['url'],
            '#token' => \Drupal::service('anonymous_token.csrf_token')->get('wateraid-donation-forms/reminder'),
          ];
        }
      }
    }

    return $build;
  }

}
