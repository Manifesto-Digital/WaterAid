<?php

namespace Drupal\webform_wizard_extra;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\block_content\Entity\BlockContent;
use Drupal\currency\Entity\Currency;
use Drupal\node\Entity\Node;
use Drupal\wateraid_donation_forms\Donation;
use Drupal\wateraid_donation_forms\DonationService;
use Drupal\wateraid_donation_forms\DonationsWebformHandlerTrait;
use Drupal\wateraid_donation_forms\Element\DonationsWebformAmount;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform_wizard_extra\Plugin\WebformElement\WebformWizardExtraPage;

/**
 * Webform Wizard Extra Submission Form.
 *
 * @package Drupal\webform_wizard_extra
 */
class WebformWizardExtraSubmissionForm extends WebformSubmissionForm {

  use DonationsWebformHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): bool|array {

    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $this->getWebform();

    // Lookup storage values.
    $storage = $form_state->getStorage();
    // Check if we need to prepare the form state when the 1st step of a
    // donation Webform is skipped.
    if (!isset($storage[DonationsWebformAmount::STORAGE_FREQUENCY], $storage[DonationsWebformAmount::STORAGE_AMOUNT])) {
      $request_query = \Drupal::request()->query;
      // Set amount and frequency.
      $form_state->set(DonationsWebformAmount::STORAGE_FREQUENCY, $request_query->get('fq'));
      $form_state->set(DonationsWebformAmount::STORAGE_AMOUNT, $request_query->get('val'));
    }

    // Check if we have a progress render array key.
    if (isset($form['progress'])) {
      // Re-lookup storage values.
      $storage = $form_state->getStorage();
      // Add the donation amount details below the progress bar.
      if (isset($storage[DonationsWebformAmount::STORAGE_FREQUENCY], $storage[DonationsWebformAmount::STORAGE_AMOUNT])) {
        /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
        $currency = Currency::load($webform->getThirdPartySetting('wateraid_donation_forms', 'currency', 'GBP'));
        // Create a simple donation object.
        $donation = new Donation();
        $donation->setCurrencySign($currency->getSign())
          ->setAmount($storage[DonationsWebformAmount::STORAGE_AMOUNT]);
        // Lookup the frequency and get the correct donation message.
        $frequency = $form_state->get(DonationsWebformAmount::STORAGE_FREQUENCY);
        $handler = self::getWebformDonationsHandler($webform);
        $config = $handler->getConfiguration();
        $progress_donation_message = $config['settings'][$frequency]['progress_donation_message'] ?? DonationService::getDefaultPaymentFrequencyProgressMessage($frequency);
        $form['progress']['#actions']['donation_context'] = [
          '#type' => 'markup',
          '#markup' => \Drupal::token()
            ->replace($progress_donation_message, ['donation' => $donation]),
          '#prefix' => '<div class="progress-bar__donation-amount-info">',
          '#suffix' => '</div>',
        ];
      }
    }

    // Check if we are dealing with a paged Webform.
    if ($storage['current_page']) {
      // Get current page.
      $current_page = $storage['current_page'];

      // Ensure "help" element to be rendered below the action buttons.
      $search_help_key = '#' . WebformWizardExtraPage::ELEMENT_KEY_HELP;
      if (isset($form['elements'][$current_page][$search_help_key])) {
        // Move the help element out of its page.
        $form['elements']['actions'][] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->renderer->render($form['elements'][$current_page][$search_help_key]),
          '#attributes' => [
            'class' => ['webform-page-help', 'description'],
          ],
          // Add a higher weight attribute.
          '#weight' => 100,
        ];
      }

      // Hide the title if specified in the current step.
      $search_hide_title_key = '#' . WebformWizardExtraPage::ELEMENT_KEY_HIDE_TITLE;
      if (!empty($form['elements'][$current_page][$search_hide_title_key])) {
        // Move the hide_title identifier where it operates as a parent over the
        // title's location in the DOM.
        $form['#attributes']['class'][] = 'webform-hide-title';
      }

      // Add testimonial block if set and on step 1.
      if ($current_page === 'step_1') {
        $testimonial_block_key = '#' . WebformWizardExtraPage::ELEMENT_KEY_SELECT_TESTIMONIAL_BLOCK;
        $block_id = $form['elements'][$current_page][$testimonial_block_key] ?? NULL;

        if ($block_id !== NULL) {
          // Check the current route.
          $params = \Drupal::routeMatch()->getParameters();
          $node = $params->get('node');

          // We only show the 'Testimonial' block when we are
          // on a Webform object (ie: Donation).
          if (!$node instanceof Node) {
            $block = BlockContent::load($block_id);

            if ($block !== NULL) {
              $block_content = $this->entityTypeManager->getViewBuilder('block_content')
                ->view($block);

              $form['testimonial_block'] = [
                '#type' => 'html_tag',
                '#tag' => 'div',
                '#value' => $this->renderer->render($block_content),
                '#attributes' => [
                  'class' => [
                    'testimonial-block',
                    'testimonial-block--donation-webform',
                    'description',
                  ],
                ],
                // Add a higher weight attribute.
                '#weight' => 0,
              ];
            }
          }
        }
      }

      // Add Security icons if set.
      $search_icons_block_key = '#' . WebformWizardExtraPage::ELEMENT_KEY_SELECT_SECURITY_ICONS_BLOCK;
      $search_icons_show_default_key = '#' . WebformWizardExtraPage::ELEMENT_KEY_SHOW_SECURITY_ICONS;
      // If the default security icons are checked,
      // use the default block content.
      $block_id = NULL;
      if (isset($form['elements'][$current_page][$search_icons_show_default_key])) {
        $block_id = \Drupal::state()
          ->get('webform_wizard_extra_security_icons_block_id');
      }
      // If the default security icons are unchecked, load the custom block.
      elseif (isset($form['elements'][$current_page][$search_icons_block_key])) {
        $block_id = $form['elements'][$current_page][$search_icons_block_key];
      }

      if (!empty($block_id)) {
        $block = BlockContent::load($block_id);
        if ($block !== NULL) {
          $block_content = $this->entityTypeManager->getViewBuilder('block_content')
            ->view($block);
          $form['elements']['actions'][] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->renderer->render($block_content),
            '#attributes' => [
              'class' => ['webform-security-icons', 'description'],
            ],
            // Add a higher weight attribute.
            '#weight' => 100,
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    $this->setErrorStartPage($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Get the settings for the current page.
    $current_page = $this->getCurrentPage($form, $form_state);
    $element = $this->getWebform()->getElement($current_page);

    // Enable the secure logo option.
    if (!empty($element['#' . WebformWizardExtraPage::ELEMENT_KEY_SECURE_DONATION_BUTTON])) {
      if (!empty($actions['wizard_next'])) {
        $actions['wizard_next']['#secure_logo'] = TRUE;
      }
      if (!empty($actions['submit'])) {
        $actions['submit']['#secure_logo'] = TRUE;
      }
    }

    return $actions;
  }

  /**
   * Check if there were any errors on the form, and set the correct start page.
   *
   * @param mixed[] $form
   *   The Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function setErrorStartPage(array &$form, FormStateInterface $form_state): void {
    if ($form_state::hasAnyErrors()) {
      $elements = $form['elements'];
      $child_keys = Element::children($elements, TRUE);
      $start_on_step = NULL;

      foreach ($form_state->getErrors() as $name => $error_value) {
        foreach ($child_keys as $step => $child_key) {
          $key_exists = FALSE;
          $element = NestedArray::getValue($elements[$child_key], explode('][', $name), $key_exists);
          if ($element) {
            if ($start_on_step === NULL || $start_on_step > $step) {
              $start_on_step = $step;
            }
          }
        }
      }

      if ($start_on_step !== NULL) {
        $form['#attached']['drupalSettings']['webformWizardExtra']['startOnStep'] = $start_on_step;
      }
    }
  }

}
