<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a webform element for payment logos.
 *
 * @FormElement("payment_logos")
 */
class PaymentLogos extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderPaymentLogos'],
      ],
    ];
  }

  /**
   * Create webform markup for rendering Payment Logo blocks.
   *
   * @param mixed[] $element
   *   An associative array containing the properties and children of the
   *    element.
   *
   * @return mixed[]
   *   The modified element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function preRenderPaymentLogos(array $element): array {
    if (!isset($element['#block_reference'])) {
      return $element;
    }

    $entity_type_manager = \Drupal::entityTypeManager();

    /** @var \Drupal\block_content\Entity\BlockContent $block */
    $block = $entity_type_manager->getStorage('block_content')->load($element['#block_reference']);

    if ($block) {
      $element = $entity_type_manager->getViewBuilder('block_content')->view($block);
    }

    return $element;
  }

}
