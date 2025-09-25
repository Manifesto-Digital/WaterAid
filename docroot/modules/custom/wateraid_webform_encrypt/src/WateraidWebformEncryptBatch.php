<?php

namespace Drupal\wateraid_webform_encrypt;

use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contains batch operations.
 */
class WateraidWebformEncryptBatch {

  /**
   * The encryption objects, as provided by the EncryptionProfileManager.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   Result of batch_process().
   */
  public static function batchStart(): RedirectResponse|null {
    $batch = [
      'title' => t('Encrypting webform submission...'),
      'operations' => [
        [
          '\Drupal\wateraid_webform_encrypt\WateraidWebformEncryptBatch::batchProcess',
          [],
        ],
      ],
      'finished' => '\Drupal\wateraid_webform_encrypt\WateraidWebformEncryptBatch::batchFinish',
    ];
    batch_set($batch);
    return batch_process();
  }

  /**
   * Batch process callback.
   *
   * @param mixed[] $context
   *   Batch process context array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchProcess(array &$context): void {
    $batch_size = 20;
    // Initialize batch.
    if (empty($context['sandbox'])) {
      $entity_ids = \Drupal::entityQuery('webform_submission')
        ->accessCheck(FALSE)
        ->sort('created', 'DESC')
        ->execute();
      $context['sandbox']['processed'] = 0;
      $context['results'] = [];

      $context['sandbox']['entity_ids'] = $entity_ids;
    }

    $entities_loaded = WebformSubmission::loadMultiple(array_slice($context['sandbox']['entity_ids'], $context['sandbox']['processed'], $batch_size));

    /** @var \Drupal\webform\Entity\WebformSubmission $entity */
    foreach ($entities_loaded as $entity) {
      // @todo wateraid_webform_encrypt_encrypt_entity() has been removed and
      // the replacement is a private method. Presumably this whole batch
      // functionality needs removing, but we will save the submission and hope
      // that triggers the encryption.
      if ($entity->save()) {
        $context['results'][] = $entity->id();
        $entity->save();
      }
    }

    $message = t('Encrypting webform submissions @current of @total',
      [
        '@current' => $context['sandbox']['processed'],
        '@total' => count($context['sandbox']['entity_ids']),
      ]
    );
    $context['message'] = $message;
    $context['finished'] = $context['sandbox']['processed'] / count($context['sandbox']['entity_ids']);
    $context['sandbox']['processed'] += $batch_size;
  }

  /**
   * Batch finish callback.
   *
   * @param bool $success
   *   Whether the batch succeeded.
   * @param mixed[] $results
   *   The results.
   * @param mixed[] $operations
   *   The completed operations.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the success page.
   */
  public static function batchFinish(bool $success, array $results, array $operations): RedirectResponse {
    if ($success) {
      \Drupal::service('messenger')->addMessage(t('Encrypted @count webform submissions successfuly', ['@count' => count($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $args = [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ];
      \Drupal::service('messenger')->addError(t('An error occurred while processing @operation with arguments : @args', $args));
    }
    $url = Url::fromRoute('wateraid_webform_encrypt.settings');
    return new RedirectResponse($url->toString());
  }

}
