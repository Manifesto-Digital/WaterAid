<?php

namespace Drupal\wateraid_tmgmt\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Form\SourceOverviewForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkSourceOverviewForm.
 *
 * This Form allows bulk translation of a specific set of media entitiy ids via
 * the TMGMT module, which is needed for Smartling.
 *
 * @package Drupal\wateraid_tmgmt\Form
 */
class BulkSourceOverviewForm extends SourceOverviewForm {

  /**
   * Active database connection.
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tmgmt_overview_form_wateraid';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin = NULL, $item_type = NULL): array {

    $form = parent::buildForm($form, $form_state, $plugin, $item_type);

    // Override the "items" tableselect form element.
    $form['items'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Entity Ids'),
      '#description' => $this->t('Comma separated entity ids that you wish to translate in bulk.'),
      '#required' => TRUE,
    ];

    // Unset various form element from the base class that we don't use in this
    // extended interface.
    unset($form['source_type'], $form['search_wrapper'], $form['pager'], $form['legend']);

    // Specify a new form element to allow choice of entity type.
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose source'),
      '#options' => [
        'node' => $this->t('Content'),
        'media' => $this->t('Media'),
      ],
      '#weight' => -100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Fix plugin to "content".
    $form_state->set('plugin', 'content');
    // Fix type to "media" or "node".
    $form_state->set('item_type', $form_state->getValue('entity_type'));
    // Call parent now.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateItemsSelected(array $form, FormStateInterface $form_state): void {
    $entity_type = $form_state->getValue('entity_type');
    // Transform items to the expected output.
    $entity_ids = $form_state->getValue('items');
    // Clear whitespace.
    $entity_ids = str_replace(' ', '', $entity_ids);
    // Clear empty values and retain unique values.
    $entity_ids = array_unique(array_filter(explode(',', $entity_ids)));
    // Fetch only existing entity items and ensure expected keyed array
    // values too.
    switch ($entity_type) {
      case 'node':
        $items = $this->database
          ->select('node', 'n')
          ->fields('n', ['nid'])
          ->condition('n.nid', $entity_ids, 'IN')
          ->execute()
          ->fetchAllKeyed(0, 0);
        break;

      case 'media':
        $items = $this->database
          ->select('media', 'm')
          ->fields('m', ['mid'])
          ->condition('m.mid', $entity_ids, 'IN')
          ->execute()
          ->fetchAllKeyed(0, 0);
        break;

      default:
        throw new \RuntimeException('Invalid translation type.');
    }
    // Set form state value.
    $form_state->setValue('items', $items);
    // Call parent validation upon the reformatted output.
    parent::validateItemsSelected($form, $form_state);
    // Validate lookup.
    $diff = array_diff($entity_ids, $items);
    if (!empty($diff)) {
      $diff_ids = implode(', ', $diff);
      $form_state->setError($form, $this->t('You selected non-existing @entity_type Items IDs: @diff_ids', [
        '@entity_type' => ucfirst($entity_type),
        '@diff_ids' => $diff_ids,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Fix plugin to "content".
    $form_state->set('plugin', 'content');
    // Fix type to "media" or "node".
    $form_state->set('item_type', $form_state->getValue('entity_type'));
    // Call parent now.
    parent::submitForm($form, $form_state);
  }

}
