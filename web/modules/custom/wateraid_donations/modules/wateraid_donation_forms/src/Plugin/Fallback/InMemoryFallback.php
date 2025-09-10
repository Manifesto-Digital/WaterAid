<?php

namespace Drupal\wateraid_donation_forms\Plugin\Fallback;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wateraid_donation_forms\DonationServiceInterface;
use Drupal\wateraid_donation_forms\FallbackBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * In Memory Fallback Plugin.
 *
 * @Fallback(
 *   id = "in_memory",
 *   label = @Translation("In Memory Fallback"),
 *   description = @Translation("This Fallback triggers when a donation is made with the 'In Memory' fields left empty or 'Other Relative' is given as relationship.")
 * )
 *
 * @package \Drupal\wateraid_donation_forms\Plugin\Fallback
 */
class InMemoryFallback extends FallbackBase implements ContainerFactoryPluginInterface {

  /**
   * The Donation service.
   */
  protected DonationServiceInterface $donationService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->donationService = $container->get('wateraid_donation_forms.donation');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(EntityInterface $entity): bool {

    // We only process the fallback check on a Webform Submission.
    if ($entity instanceof WebformSubmissionInterface === FALSE) {
      throw new \RuntimeException('Invalid entity provided');
    }

    $data = $this->donationService->getInMemoryData($entity);

    if ($data === FALSE) {
      return FALSE;
    }

    // Check if relationship other than 'Other Relative' and first or last name
    // missing then fallback base on condition for loved one,
    // check wateraid_donation_forms_tokens line 258.
    $title = $data['in_memory_title'] ?? NULL;
    $first_name = $data['in_memory_firstname'] ?? NULL;
    $last_name = $data['in_memory_lastname'] ?? NULL;
    $fallBackEmptyName = TRUE;
    if (!empty($first_name) && !empty($last_name)) {
      $fallBackEmptyName = FALSE;
    }
    elseif (!empty($title) && !empty($last_name)) {
      $fallBackEmptyName = FALSE;
    }
    elseif (!empty($first_name)) {
      $fallBackEmptyName = FALSE;
    }

    // Fallback applies if the relationship field is not provided or is provided
    // but is set to "Other Relative".
    return empty($data['in_memory_relationship']) || $data['in_memory_relationship'] === 'Other Relative' || $fallBackEmptyName;
  }

}
