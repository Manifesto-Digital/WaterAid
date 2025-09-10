<?php

namespace Drupal\wateraid_base_core\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a 'Wateraid donation CTA widget' block.
 *
 * @Block(
 *   id = "wateraid_donation_cta",
 *   admin_label = @Translation("Wateraid donation CTA widget")
 * )
 */
class DonationCTABlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('Set the donation link text.'),
      '#default_value' => $this->configuration['link_text'],
    ];

    $form['link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#description' => $this->t('Set the donation link url. For internal links, remove preceding slash ("/") to the donation form path, external links enter the full URL including scheme ("https:)"'),
      '#default_value' => $this->configuration['link_url'],
    ];

    $form['link_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link opens in a new window'),
      '#default_value' => (!empty($this->configuration['link_target']) ? $this->configuration['link_target'] : FALSE),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['link_text'] = $form_state->getValue('link_text');
    $this->configuration['link_url'] = $form_state->getValue('link_url');
    $this->configuration['link_target'] = $form_state->getValue('link_target');
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {

    if (str_contains($form_state->getValue('link_url'), 'http')) {
      if (!UrlHelper::isValid($form_state->getValue('link_url'), TRUE)) {
        $form_state->setErrorByName('link_url', $this->t('Invalid external Link url'));
      }
    }
    else {
      if (str_starts_with($form_state->getValue('link_url'), '/')) {
        $form_state->setErrorByName('link_url', $this->t('Invalid internal Link url'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    if (!empty($this->configuration['link_url'])) {
      $href = $this->configuration['link_url'];
      if (str_contains($href, 'http')) {
        // External URL.
        $url = Url::fromUri($href);
      }
      else {
        $url = Url::fromUri('internal:/' . $href);
      }

      $attributes = ['class' => ['button button__primary']];
      if (!empty($this->configuration['link_target']) && $this->configuration['link_target']) {
        $attributes['target'] = '_blank';
      }

      $url->setOption('attributes', $attributes);
      return [
        '#type' => 'link',
        '#title' => $this->configuration['link_text'],
        '#url' => $url,
        '#attributes' => [
          'class' => ['button__primary__wrapper'],
        ],
      ];
    }
    else {
      return [];
    }
  }

}
