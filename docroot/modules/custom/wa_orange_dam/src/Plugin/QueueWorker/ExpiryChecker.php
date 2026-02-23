<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\QueueWorker;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'wa_orange_dam_expiry_checker' queue worker.
 *
 * @QueueWorker(
 *   id = "wa_orange_dam_expiry_checker",
 *   title = @Translation("Expiry Checker"),
 *   cron = {"time" = 60},
 * )
 */
final class ExpiryChecker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new ExpiryChecker instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Api $waOrangeDamApi,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('wa_orange_dam.api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $mid = ($data->mid) ?? $data;

    if ($mid) {
      /** @var \Drupal\media\MediaInterface $media */
      if ($media = $this->entityTypeManager->getStorage('media')->load($mid)) {
        if (!in_array($media->bundle(), [
          'dam_file',
          'dam_image',
          'dam_video',
        ])) {
          return;
        }

        // Set the last checked date now so if anything goes wrong we don't end up
        // checking this repeatedly.
        $media->set('field_dam_last_checked', time());

        $field_name = 'field_media_' . $media->bundle();

        if ($values = $media->get($field_name)->getValue()) {
          if (isset($values[0]['system_identifier']) && $system_id = $values[0]['system_identifier']) {
            if ($api_result = $this->waOrangeDamApi->search([
              'query' => 'SystemIdentifier:' . $system_id,
            ])) {
              if (isset($api_result['APIResponse']['Items'][0])) {
                // While we're here, we'll check the captions/credits.
                foreach ([
                  'field_caption' => 'CustomField.Caption',
                  'field_credit' => 'customfield.Credit',
                ] as $field => $key) {
                  if ($media->hasField($field) && $media->get($field)
                      ->isEmpty()) {
                    if ($key == 'customfield.Credit') {
                      $value = $api_result['APIResponse']['Items'][0]['customfield.Credit']['Value'] ?? NULL;
                    }
                    else {
                      $value = $api_result['APIResponse']['Items'][0]['CustomField.Caption'] ?? NULL;
                    }

                    if ($value) {
                      // Only set the caption if it doesn't contain non-ASCII characters.
                      if (!preg_match('/[^\x20-\x7e]/', $value)) {
                        $media->set($field, substr(strip_tags($value), 0, 250));
                      }
                    }
                  }
                }

                $date_value = $api_result['APIResponse']['Items'][0]['customfield.Expiry-Date'];

                // Now check expiry dates.
                if ($date_value) {
                  // Set the updated expiry date whatever it is.
                  $date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $date_value);
                  $media->set('field_dam_expiry_date', $date->getTimestamp());

                  $now = new DrupalDateTime();

                  // And if the date has passed, mark this as expired.
                  if ($date <= $now) {
                    $media->set('field_dam_expired', TRUE);
                  }
                }
              }
            }
          }
        }

        // Save any changes to the media settings.
        $media->save();
      }
    }
  }

}
