<?php

namespace Drupal\wateraid_azure_storage\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\loqate\PcaAddressFieldMapping\PcaAddressElement;
use Drupal\webform\Entity\Webform as WebformEntity;

/**
 * Class WaterAidWebformColumns.
 *
 * A variation to the WebformExcludedColumns FormElement with extra features
 * specific to WaterAid.
 *
 * @FormElement("wateraid_webform_columns")
 */
class WaterAidWebformColumns extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#input' => TRUE,
      '#process' => [
        [self::class, 'processWebformIncluded'],
      ],
      '#webform_id' => NULL,
      '#theme_wrappers' => ['form_element'],
      '#default_value' => [],
    ];
  }

  /**
   * Processes a webform elements webform element.
   *
   * @param mixed[] $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $complete_form
   *   The complete form.
   *
   * @return mixed[]
   *   The updated element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function processWebformIncluded(array &$element, FormStateInterface $form_state, array &$complete_form): array {

    $header = static::getTableSelectHeader();
    $options = static::getTableSelectOptions($element);
    $element['#tree'] = TRUE;
    $element += ['#element_validate' => []];

    // Add validate callback.
    array_unshift($element['#element_validate'], [
      self::class, 'validateWebformInclude',
    ]);

    $element['table'] = [
      // Don't use 'tableselect' because it doesn't work with form elements.
      '#type' => 'table',
      '#header' => $header,
      '#empty' => new TranslatableMarkup('No elements are available.'),
    ];

    $element['table'] += $options;

    if (isset($element['#parents'])) {
      $element['table']['#parents'] = array_merge($element['#parents'], ['table']);
    }

    // Build tableselect element with selected properties.
    $properties = [
      '#title',
      '#title_display',
      '#description',
      '#description_display',
      '#ajax',
      '#states',
    ];
    $element['table'] += array_intersect_key($element, array_combine($properties, $properties));
    return $element;
  }

  /**
   * Get the table select header.
   *
   * @return mixed[]
   *   The header.
   */
  public static function getTableSelectHeader(): array {
    return [
      // Leave "enabled" header empty to save column width.
      'enabled' => '',
      'parent_key' => new TranslatableMarkup('Title'),
      'original_key' => new TranslatableMarkup('Original Name'),
      'new_key' => new TranslatableMarkup('Name'),
    ];
  }

  /**
   * Get the Table Select options.
   *
   * @param mixed[] $element
   *   The element.
   *
   * @return mixed[]
   *   The options array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getTableSelectOptions(array $element): array {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = WebformEntity::load($element['#webform_id']) ?: \Drupal::service('webform.request')->getCurrentWebform();
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $options = [];
    $field_definitions = $submission_storage->getFieldDefinitions();
    $field_definitions = $submission_storage->checkFieldDefinitionAccess($webform, $field_definitions);

    // Append field definitions with arbitrary Webform Submission Entity fields
    // that are also available in the CSV export.
    $field_definitions += self::getArbitraryFieldDefinitions();

    foreach ($field_definitions as $key => $field_definition) {

      $enabled = FALSE;
      $new_key = NULL;
      foreach ($element['#default_value'] as $i => $default_value) {
        if ($default_value['original_key'] === $key) {
          $enabled = TRUE;
          $new_key = $default_value['new_key'];
          unset($element['#default_value'][$i]);
          break;
        }
      }

      $options[$key] = [
        'enabled' => [
          'data' => [
            '#type' => 'checkbox',
            '#default_value' => $enabled,
          ],
        ],
        'parent_key' => [
          'data' => [
            '#type' => 'hidden',
            '#prefix' => $field_definition['title'],
            '#default_value' => $key,
          ],
        ],
        'original_key' => [
          'data' => [
            '#markup' => $key,
          ],
        ],
        'new_key' => [
          'data' => [
            '#type' => 'textfield',
            '#description' => new TranslatableMarkup('Type: @type', ['@type' => $field_definition['type']]),
            '#placeholder' => new TranslatableMarkup('@title', ['@title' => $key]),
            '#default_value' => !empty($new_key) && $new_key !== $key ? $new_key : NULL,
          ],
        ],
      ];
    }

    $elements = $webform->getElementsInitializedFlattenedAndHasValue();
    // Replace tokens which can be used in an element's #title.
    $elements = $token_manager->replace($elements, $webform);
    // Get Webform export options.
    $export_options = self::getExportOptions();

    foreach ($elements as $key => $el) {
      // We need a nested granularity for the mapping of the fields.
      $header = $element_manager->invokeMethod('buildExportHeader', $elements[$key], $export_options);

      foreach ($header as $header_key) {

        $enabled = FALSE;
        $new_key = NULL;
        foreach ($element['#default_value'] as $i => $default_value) {
          if ($default_value['original_key'] === $header_key) {
            $enabled = TRUE;
            $new_key = $default_value['new_key'];
            unset($element['#default_value'][$i]);
            break;
          }
        }

        $options[$header_key] = [
          'enabled' => [
            'data' => [
              '#type' => 'checkbox',
              '#default_value' => $enabled,
            ],
          ],
          'parent_key' => [
            'data' => [
              '#type' => 'hidden',
              '#prefix' => $el['#admin_title'] ?: $el['#title'] ?: $header_key,
              // Use the title field to carry over the parent key if any.
              '#default_value' => $key,
            ],
          ],
          'original_key' => [
            'data' => [
              '#markup' => $header_key,
            ],
          ],
          'new_key' => [
            'data' => [
              '#type' => 'textfield',
              '#description' => new TranslatableMarkup('Type: @type', ['@type' => $el['#type']]),
              '#placeholder' => new TranslatableMarkup('@title', ['@title' => $header_key]),
              '#default_value' => !empty($new_key) && $new_key !== $header_key ? $new_key : NULL,
            ],
          ],
        ];
      }
    }

    return $options;
  }

  /**
   * Processes a webform elements webform element.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $complete_form
   *   The complete form.
   */
  public static function validateWebformInclude(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $value = array_filter($element['table']['#value']);

    // Unset table and set the element's new values.
    $form_state->setValueForElement($element['table'], NULL);

    $processed_value = [];

    foreach ($value as $key => $val) {
      $enabled = (bool) ($val['enabled']['data'] ?? FALSE);
      // Ignore when not enabled.
      if ($enabled !== TRUE) {
        continue;
      }
      // Assign name value if given, if not default to key.
      $processed_value[] = [
        'original_key' => $key,
        'new_key' => $val['new_key']['data'] ?: $key,
        'parent_key' => $val['parent_key']['data'] === $key ? NULL : $val['parent_key']['data'],
      ];
    }

    $element['#value'] = $processed_value;
    $form_state->setValueForElement($element, $processed_value);
  }

  /**
   * Gives Webform export options as an array.
   *
   * @return mixed[]
   *   An array of export options as expected by Webform.
   */
  public static function getExportOptions(): array {
    // Make sure we have the correct settings. Similar output is attained from:
    // \Drupal::service('plugin.manager.webform.exporter')->createInstance('foo')->getConfiguration().
    return [
      'header_format' => 'key',
      'header_prefix_key_delimiter' => '_',
      'header_prefix' => FALSE,
      'delimiter' => ',',
      'composite_element_item_format' => NULL,
      'multiple_delimiter' => NULL,
      'options_single_format' => NULL,
      'options_item_format' => NULL,
      'address_columns' => [
        PcaAddressElement::LINE1 => PcaAddressElement::LINE1,
        PcaAddressElement::LINE2 => PcaAddressElement::LINE2,
        PcaAddressElement::LOCALITY => PcaAddressElement::LOCALITY,
        PcaAddressElement::ADMINISTRATIVE_AREA => PcaAddressElement::ADMINISTRATIVE_AREA,
        PcaAddressElement::POSTAL_CODE => PcaAddressElement::POSTAL_CODE,
        PcaAddressElement::COUNTRY_CODE => PcaAddressElement::COUNTRY_CODE,
        PcaAddressElement::PAF => PcaAddressElement::PAF,
      ],
      'capture_plus_address_options' => [
        'address' => 'address',
        'address_2' => 'address_2',
        'city' => 'city',
        'postal_code' => 'postal_code',
        'state_province' => 'state_province',
        'country' => 'country',
        'paf' => 'paf',
      ],
    ];
  }

  /**
   * Gives a set of custom arbitrary fields that need passing along.
   *
   * These fields are similarly exposed in the legacy CSV export to CRM.
   *
   * @return mixed[]
   *   An array of arbitrary fields as expected by CRM.
   */
  public static function getArbitraryFieldDefinitions(): array {
    return [
      'uid_title' => [
        'title' => new TranslatableMarkup('Submitted by Title'),
        'name' => 'uid_title',
        'type' => 'custom',
      ],
      'uid_url' => [
        'title' => new TranslatableMarkup('Submitted by URL'),
        'name' => 'uid_url',
        'type' => 'custom',
      ],
      'entity_title' => [
        'title' => new TranslatableMarkup('Submitted to: Entity Title'),
        'name' => 'entity_title',
        'type' => 'entity_title',
      ],
      'entity_url' => [
        'title' => new TranslatableMarkup('Submitted to: Entity URL'),
        'name' => 'entity_url',
        'type' => 'entity_url',
      ],
      'url_params_utm_campaign' => [
        'title' => new TranslatableMarkup('URL Params: UTM Campaign'),
        'name' => 'url_params_utm_campaign',
        'type' => 'custom',
      ],
      'url_params_utm_source' => [
        'title' => new TranslatableMarkup('URL Params: UTM Source'),
        'name' => 'url_params_utm_source',
        'type' => 'custom',
      ],
      'url_params_utm_content' => [
        'title' => new TranslatableMarkup('URL Params: UTM Content'),
        'name' => 'url_params_utm_content',
        'type' => 'custom',
      ],
      'url_params_utm_medium' => [
        'title' => new TranslatableMarkup('URL Params: UTM Medium'),
        'name' => 'url_params_utm_medium',
        'type' => 'custom',
      ],
      'url_params_fund_code' => [
        'title' => new TranslatableMarkup('URL Params: Fund Code'),
        'name' => 'url_params_fund_code',
        'type' => 'custom',
      ],
      'url_params_package_id' => [
        'title' => new TranslatableMarkup('URL Params: Package ID'),
        'name' => 'url_params_package_id',
        'type' => 'custom',
      ],
      'url_params_campaign' => [
        'title' => new TranslatableMarkup('URL Params: Campaign'),
        'name' => 'url_params_campaign',
        'type' => 'custom',
      ],
      'url_params_segment_code' => [
        'title' => new TranslatableMarkup('URL Params: Segment Code'),
        'name' => 'url_params_segment_code',
        'type' => 'custom',
      ],
    ];
  }

}
