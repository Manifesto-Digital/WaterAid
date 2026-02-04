<?php

namespace Drupal\wateraid_webform_encrypt;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Error;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter webform submission storage definitions.
 */
class WateraidWebformEncryptSubmissionStorage extends WebformSubmissionStorage {

  /**
   * The encryption service.
   */
  protected EncryptServiceInterface $encryptionService;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = parent::createInstance($container, $entity_type);
    $instance->encryptionService = $container->get('encryption');
    return $instance;
  }

  /**
   * Decrypts a string.
   *
   * @param string $string
   *   The string to be decrypted.
   * @param \Drupal\encrypt\Entity\EncryptionProfile $encryption_profile
   *   The encryption profile to be used to decrypt the string.
   *
   * @return string
   *   The decrypted value.
   */
  protected function decrypt(string $string, EncryptionProfile $encryption_profile): string {

    try {
      $decrypted_value = $this->encryptionService->decrypt($string, $encryption_profile);
    }
    catch (\Exception $exception) {
      return $string;
    }

    // A CryptoException results in the value being "FALSE".
    if ($decrypted_value === FALSE) {
      return $string;
    }

    return $decrypted_value;
  }

  /**
   * Returns the Webform Submission data decrypted.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission entity object.
   * @param \Drupal\encrypt\Entity\EncryptionProfile $encryption_profile
   *   The encryption profile object.
   *
   * @return mixed[]
   *   An array containing the Webform Submission decrypted data.
   */
  public function getDecryptedData(WebformSubmissionInterface $webform_submission, EncryptionProfile $encryption_profile): array {
    $data = $webform_submission->getData();

    foreach ($data as $element_name => $element) {
      // Checks whether is an element with multiple values.
      if (is_array($element)) {
        foreach ($element as $element_value_key => $element_value) {
          $data[$element_name][$element_value_key] = $this->decrypt($element_value, $encryption_profile);
        }
      }
      else {
        // Single element value.
        $data[$element_name] = $this->decrypt($element, $encryption_profile);
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function saveData(WebformSubmissionInterface $webform_submission, $delete_first = TRUE) {
    if ($webform_submission instanceof WebformSubmission && $encryption_profile = wateraid_webform_encrypt_get_encryption_profile()) {
      $data = $webform_submission->getData();
      $this->encryptEntity($webform_submission, $encryption_profile);
      parent::saveData($webform_submission, $delete_first);
      $webform_submission->setData($data);
    }
    else {
      parent::saveData($webform_submission, $delete_first);
    }
  }

  /**
   * Encrypts a webform submission.
   *
   * @param \Drupal\Webform\entity\WebformSubmission $entity
   *   The webform submission entity.
   * @param \Drupal\encrypt\Entity\EncryptionProfile $encryption_profile
   *   The encryption profile.
   */
  private function encryptEntity(WebformSubmission $entity, EncryptionProfile $encryption_profile): void {
    $data_original = $entity->getData();
    $data = [];
    foreach ($data_original as $key => $value) {
      // Checks whether is an element with multiple values.
      if (is_array($value)) {
        $multiple_values = [];
        foreach ($value as $multiple_values_key => $multiple_values_value) {
          try {
            $multiple_values[$multiple_values_key] = $this->encryptionService->encrypt($multiple_values_value, $encryption_profile);
          }
          catch (\Exception $exception) {
            Error::logException(
              $this->loggerFactory->get('wateraid_webform_encrypt'),
              $exception,
            );
            // Save decrypted value if an exception occurs.
            $multiple_values[$multiple_values_key] = $multiple_values_value;
          }
        }
        $data[$key] = $multiple_values;

        continue;
      }

      // Single element value.
      try {
        $data[$key] = $this->encryptionService->encrypt($value, $encryption_profile);
      }
      catch (\Exception $exception) {
        Error::logException(
          $this->loggerFactory->get('wateraid_webform_encrypt'),
          $exception,
        );
        // Save decrypted value if an exception occurs.
        $data[$key] = $value;
      }
    }

    $entity->set('encrypted', TRUE);
    $entity->setData($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function loadData(array &$webform_submissions): void {
    parent::loadData($webform_submissions);

    if ($encryption_profile = wateraid_webform_encrypt_get_encryption_profile()) {
      foreach ($webform_submissions as &$webform_submission) {

        $data = $this->getDecryptedData($webform_submission, $encryption_profile);
        $webform_submission->setData($data);
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function getTotal(?WebformInterface $webform = NULL, ?EntityInterface $source_entity = NULL, ?AccountInterface $account = NULL, array $options = []): int|array {
    if (!$webform) {
      if ($source_entity instanceof ContentEntityInterface) {
        if ($source_entity->hasField('webform')) {
          if ($entities = $source_entity->get('webform')->referencedEntities()) {
            $webform = reset($entities);
          }
        }
      }
    }

    // If we don't have a webform - or the webform we have doesn't use the blob
    // storage - we can't do anything to get the settings. Instead, we'll let
    // the parent handle it.
    if (!$webform || !$webform->getHandler('azure_blob_storage')) {
      return parent::getTotal($webform, $source_entity, $account, $options);
    }

    if ($submissions = $webform->getThirdPartySetting('wateraid_forms', 'submissions')) {
      if ($account || $source_entity) {
        if (!$account) {

          // This is total per source entity only.
          return isset($submissions['per_entity'][$source_entity->id()]) ? count($submissions['per_entity'][$source_entity->id()]) : 0;
        }
        elseif (!$source_entity) {

          // This is total per user only.
          return isset($submissions['per_user'][$account->id()]) ? count($submissions['per_user'][$account->id()]) : 0;
        }

        // If we're here, this has to be total per user per source.
        return isset($submissions['per_user_per_entity'][$account->id()][$source_entity->id()]) ? count($submissions['per_user_per_entity'][$account->id()][$source_entity->id()]) : 0;
      }
      else {

        // We just want the total here.
        return count($submissions['total']);
      }
    }

    // At this point, we have no settings we can return.
    return 0;
  }

}
