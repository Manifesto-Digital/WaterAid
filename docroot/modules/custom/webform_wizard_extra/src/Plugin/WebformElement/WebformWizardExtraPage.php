<?php

namespace Drupal\webform_wizard_extra\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformWizardPage;

/**
 * Provides a 'webform_wizard_page' element.
 *
 * Adds extra functionality to the Webform Wziard pages:
 * - Allows hide / show control of the Webform page title.
 * - Embeddable Security Icons blocks below the Wizard page.
 * - HTML markup below the Wziard page.
 * - Help text below the page but above the submit buttons.
 *
 * @WebformElement(
 *   id = "webform_wizard_extra_page",
 *   label = @Translation("Wizard page (extra)"),
 *   description = @Translation("Provides an element to display multiple form
 *   elements as a page in a multistep form wizard."),
 *   category = @Translation("Wizard"),
 * )
 */
class WebformWizardExtraPage extends WebformWizardPage {

  public const ELEMENT_KEY_HELP = 'help';

  public const ELEMENT_KEY_HIDE_TITLE = 'hide_title';

  public const ELEMENT_KEY_SHOW_SECURITY_ICONS = 'show_security_icons';

  public const ELEMENT_KEY_SELECT_SECURITY_ICONS_BLOCK = 'select_security_icons_block';

  public const ELEMENT_KEY_SELECT_TESTIMONIAL_BLOCK = 'select_testimonial_block';

  public const ELEMENT_KEY_SECURE_DONATION_BUTTON = 'secure_donate_button';

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    return [
      self::ELEMENT_KEY_HELP => '',
      self::ELEMENT_KEY_HIDE_TITLE => FALSE,
      self::ELEMENT_KEY_SHOW_SECURITY_ICONS => FALSE,
      self::ELEMENT_KEY_SELECT_SECURITY_ICONS_BLOCK => '',
      self::ELEMENT_KEY_SELECT_TESTIMONIAL_BLOCK => '',
      self::ELEMENT_KEY_SECURE_DONATION_BUTTON => FALSE,
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties(): array {
    return array_merge(parent::getTranslatableProperties(), [self::ELEMENT_KEY_HELP]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['wizard_page'][self::ELEMENT_KEY_HELP] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('Help text map appear outside the container.'),
    ];

    $form['wizard_page'][self::ELEMENT_KEY_HIDE_TITLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide title'),
      '#description' => $this->t('Check to hide the page title.'),
    ];

    $form['wizard_page'][self::ELEMENT_KEY_SHOW_SECURITY_ICONS] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display default security icons'),
      '#description' => $this->t('Check to display the default security icons below form.'),
    ];

    $form['wizard_page'][self::ELEMENT_KEY_SECURE_DONATION_BUTTON] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display security icon on button'),
      '#description' => $this->t('Check to display the security logos on the next step button.'),
    ];

    // Ask the user to choose a custom block with appropriate security icons.
    $custom_blocks = [];
    $query = $this->entityTypeManager->getStorage('block_content')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'security_icons');
    $block_ids = $query->execute();
    $blocks = $this->entityTypeManager->getStorage('block_content')->loadMultiple($block_ids);
    foreach ($blocks as $security_icon_block) {
      $custom_blocks[$security_icon_block->id()] = $security_icon_block->label();
    }

    $form['wizard_page'][self::ELEMENT_KEY_SELECT_SECURITY_ICONS_BLOCK] = [
      '#type' => 'select',
      '#title' => $this->t('Select block to display customized security icons'),
      '#description' => $this->t('If the "Display default security icons" is checked, this value is ignored. You can <a title="Create a new custom block" href="/admin/structure/block/block-content">create new custom blocks of type Security Icons</a> with the security icons you want to display and select here.'),
      '#options' => $custom_blocks,
      '#empty_option' => $this->t('None'),
    ];

    // Select the testimonials to be used.
    $testimonial_blocks = [];
    $query = $this->entityTypeManager->getStorage('block_content')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'testimonial');
    $block_ids = $query->execute();
    $blocks = $this->entityTypeManager->getStorage('block_content')->loadMultiple($block_ids);
    foreach ($blocks as $testimonial) {
      $testimonial_blocks[$testimonial->id()] = $testimonial->label();
    }

    $form['wizard_page'][self::ELEMENT_KEY_SELECT_TESTIMONIAL_BLOCK] = [
      '#type' => 'select',
      '#title' => $this->t('Select testimonial block'),
      '#description' => $this->t('Choose the testimonial you wish to show. You can <a title="Create a new custom block" href="/admin/structure/block/block-content">create new custom blocks of type Testimonial</a> with the testimonial you want to display and select here.'),
      '#options' => $testimonial_blocks,
      '#empty_option' => $this->t('None'),
    ];

    return $form;
  }

}
