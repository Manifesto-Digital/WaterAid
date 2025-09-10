<?php

namespace Drupal\paragraphs_colour\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Plugin implementation of the 'paragraphs_colour' behaviour.
 *
 * @ParagraphsBehavior(
 *   id = "paragraph_colour",
 *   label = @Translation("Paragraph colour"),
 *   description = @Translation("Colour for a whole paragraph."),
 *   weight = 0
 * )
 */
class ParagraphsColourBehaviour extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['default_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Color'),
      '#options' => $this->getColours(),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#default_value' => $this->configuration['default_color'],
      '#description' => $this->t("Colour for the paragraph."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['default_color'] = $form_state->getValue('default_color');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'default_color' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $form['paragraph_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color'),
      '#options' => $this->getColours(),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'paragraph_color', $this->configuration['default_color']),
      '#description' => $this->t("Color for the paragraph."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    if ($color = $paragraph->getBehaviorSetting($this->getPluginId(), 'paragraph_color')) {
      $build['paragraph_colour'] = [
        '#markup' => $color,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph): array {

    $text_color = $paragraph->getBehaviorSetting($this->pluginId, 'paragraph_color');

    return [$this->t('Text color: @color', ['@color' => $text_color])];
  }

  /**
   * Extends the paragraph render array with behavior.
   *
   * @return string[]
   *   An array of additional settings.
   */
  private function getColours(): array {
    return [
      'blue' => 'Blue',
      'navy' => 'Navy',
      'wa-white' => 'White',
      'orange' => 'Orange',
      'yellow' => 'Yellow',
      'light-green' => 'Light green',
      'dark-green' => 'Dark green',
      'pink' => 'Pink',
      'plum' => 'Plum',
    ];
  }

}
