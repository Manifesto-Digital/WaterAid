<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'wa_orange_dam' field widget.
 *
 * @FieldWidget(
 *   id = "wa_orange_dam",
 *   label = @Translation("DAM Asset"),
 *   field_types = {"wa_orange_dam_image"},
 * )
 */
final class DamWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a AddressDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\wa_orange_dam\Service\Api $waOrangeDamApi
   *   The Orange DAM API.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    private readonly Api $waOrangeDamApi,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('wa_orange_dam.api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    switch ($this->fieldDefinition->getTargetBundle()) {

      case 'dam_image':
        $types = ['Images*'];
        break;

      default:
        $types = [];
        break;

    }

    $element['system_identifier'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->system_identifier ?? NULL,
      '#element_validate' => [
        [$this, 'validateElement'],
      ],
      '#attributes' => [
        'id' => 'orange-dam-identifier',
      ],
    ];

    $element['dam_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Open the Orange DAM'),
      '#attributes' => [
        'id' => 'orange-dam-open',
      ],
      '#attached' => [
        'library' => [
          'wa_orange_dam/content_browser',
        ],
        'drupalSettings' => [
          'wa_orange_dam' => [
            'types' => $types,
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Validate the element.
   *
   * @param $form
   *   The element form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return void
   */
  public function validateElement(&$form, FormStateInterface $form_state): void {
    $valid = FALSE;

    $field = $this->fieldDefinition->getName();

    if ($value = $form_state->getValue($field)) {
      if (isset($value[0]['system_identifier'])) {
        if ($api_result = $this->waOrangeDamApi->search([
          'query' => 'SystemIdentifier:' . $value[0]['system_identifier'],
        ])) {
          if (!empty($api_result['APIResponse']['Items'][0])) {

            // The id works, so this is now valid.
            $valid = TRUE;

            // Add the width and height properties.
            foreach (['Width', 'Height'] as $key) {
              if (isset($api_result['APIResponse']['Items'][0]['path_TR1'][$key])) {
                $value[0][strtolower($key)] = $api_result['APIResponse']['Items'][0]['path_TR1'][$key];
              }
            }

            // Store them in the form state so they are saved with the field.
            $form_state->setValue($field, $value);
          }
        }


      }
    }

    if (!$valid) {
      $form_state->setError($form, 'This ID does not exist on the Orange DAM.');
    }
  }

}
