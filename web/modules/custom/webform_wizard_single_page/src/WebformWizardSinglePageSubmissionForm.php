<?php

namespace Drupal\webform_wizard_single_page;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform_wizard_extra\Plugin\WebformElement\WebformWizardExtraPage;
use Drupal\webform_wizard_extra\WebformWizardExtraSubmissionForm;

/**
 * Webform Wizard Single Page Submission Form.
 *
 * @package Drupal\webform_wizard_single_page
 */
class WebformWizardSinglePageSubmissionForm extends WebformWizardExtraSubmissionForm {

  /**
   * Check if single-page mode is enabled.
   *
   * @return bool
   *   TRUE if single-page mode is enabled.
   */
  public function singlePageEnabled(): bool {
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $this->getWebform();
    return (bool) $webform->getThirdPartySetting('webform_wizard_single_page', 'single_page');
  }

  /**
   * Enable/disable Ajax depending on settings and context.
   *
   * @return bool
   *   True if Ajax should be enabled, otherwise false.
   */
  protected function isAjax(): bool {

    // Enable Ajax when single-page mode is enabled.
    if ($this->singlePageEnabled()) {
      return TRUE;
    }

    // Fall back to the standard behaviour.
    return parent::isAjax();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): bool|array {
    /*
     * Do not modify the Webform to add step titles if single page mode is
     * disabled or the Webform is rendered within a WebformCTAFormatter.
     */
    if (!$this->singlePageEnabled()) {
      // Use the legacy wizard functionality (deprecated).
      return parent::form($form, $form_state);
    }
    else {
      // Inherit from the base form.
      $form = WebformSubmissionForm::form($form, $form_state);
    }

    // Hide the progress bar.
    unset($form['progress']);

    $steps = $this->getStepOrder($form, $form_state);

    // Add elements to the top of the form.
    $prefix_elements = $this->previousPagesElement($steps);
    $form['elements']['prefix'] = $prefix_elements;
    $form['elements']['prefix']['#weight'] = -999;

    // Show the current step title.
    $form['elements']['step_title'] = $this->currentPageTitle($steps);
    $form['elements']['step_title']['#weight'] = -998;

    // Add scroll to attribute to the page title.
    if ($form_state->getTriggeringElement()) {
      $form['elements']['step_title']['#attributes']['data-webform-single-page-scroll'] = 'scroll-element';
    }

    // Show error messages within the current step.
    $form['elements']['status_messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'webform_step_status_messages',
        ],
      ],
      '#weight' => -997,
    ];
    $form['elements']['status_messages']['messages'] = [
      '#type' => 'status_messages',
    ];

    // Add 'help' text for the wizard page to the current step.
    $this->addPageHelpText($form, $form_state);

    // Show titles of next steps.
    $suffix_elements = $this->nextPagesElement($steps);
    $form['elements']['suffix'] = $suffix_elements;
    $form['elements']['suffix']['#weight'] = 999;

    // Validate forms before AJAX submit.
    $form['elements']['actions']['#wizard_next__attributes']['class'][] = 'cv-validate-before-ajax';
    $form['elements']['actions']['#submit__attributes']['class'][] = 'cv-validate-before-ajax';

    // Attach the library allowing step buttons to function.
    $form['#attached']['library'][] = 'webform_wizard_single_page/webform_wizard_single_page';
    return $form;
  }

  /**
   * Returns a list of to previous wizard page titles with edit buttons.
   *
   * @param mixed[] $steps
   *   An array of steps.
   *
   * @return mixed[]
   *   Array of form elements.
   */
  public function previousPagesElement(array $steps): array {
    $elements = [];

    foreach ($steps['past'] as $page_id => $page) {
      $elements['section_goto_page_' . $page_id] = [
        '#type' => 'fieldset',
        '#title' => '<i class="fa fa-check-circle"></i>' . $page['#title'],
        '#attributes' => ['class' => 'prev-form-step'],
      ];

      // Create a link back to each previous step.
      $elements['section_goto_page_' . $page_id]['edit_' . $page_id] = [
        '#type' => 'submit',
        '#value' => $this->t('Edit'),
        '#page' => $page_id,
        '#validate' => ['::noValidate'],
        '#submit' => ['::gotoPage'],
        '#name' => 'webform_wizard_page-' . $page_id,
        '#attributes' => [
          'data-edit-step' => $page_id,
        ],
      ];
    }
    return $elements;
  }

  /**
   * Returns a list of to subsequent wizard page titles.
   *
   * @param mixed[] $steps
   *   An array of steps.
   *
   * @return mixed[]
   *   Array of form elements.
   */
  public function nextPagesElement(array $steps): array {
    $elements = [];

    foreach ($steps['future'] as $page_id => $page) {
      $elements['goto_page_' . $page_id] = [
        '#type' => 'fieldset',
        '#title' => '<span class="step-title"><span class="step-title__icon fa-stack"><i class="fa fa-solid fa-circle fa-stack-1x"></i><strong class="fa-stack-1x">' . $page['#page_number'] . '</strong></span><span class="step-title__text">' . $page['#title'] . '</span></span>',
        '#attributes' => ['class' => 'next-form-step'],
      ];
    }
    return $elements;
  }

  /**
   * Gets the current page title render element.
   *
   * @param mixed[] $steps
   *   An array of steps.
   *
   * @return mixed[]
   *   Array of form elements.
   */
  public function currentPageTitle(array $steps): array {
    $step = $steps['current'][0];

    return [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => '<span class="step-title"><span class="step-title__icon fa-stack"><i class="fa fa-solid fa-circle fa-stack-1x"></i><strong class="fa-stack-1x">' . $step['#page_number'] . '</strong></span><span class="step-title__text">' . $step['#title'] . '</span></span>',
      '#attributes' => [
        'class' => ['step-header', 'step-header__elements'],
      ],
      '#weight' => -1,
    ];
  }

  /**
   * Custom callback to get the step order.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed[]
   *   An array of steps.
   */
  private function getStepOrder(array &$form, FormStateInterface $form_state): array {
    $current_page = $this->getCurrentPage($form, $form_state);
    $pages = $this->getPages($form, $form_state);

    $i = 1;
    foreach ($pages as $key => $values) {
      $pages[$key]['#page_number'] = $i;
      $i++;
    }

    $page_ids = array_keys($pages);
    $current_position = array_search($current_page, $page_ids);

    // Identify current, past and future steps.
    $keys_before = array_filter($page_ids, fn($var) => $var < $current_position, 2);
    $keys_after = array_filter($page_ids, fn($var) => $var > $current_position, 2);
    return [
      'past' => array_filter($pages, fn($key) => in_array($key, $keys_before), 2),
      'current' => [$pages[$current_page]],
      'future' => array_filter($pages, fn($key) => in_array($key, $keys_after), 2),
    ];
  }

  /**
   * Add the wizard page help text to the render array.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Exception
   */
  private function addPageHelpText(array &$form, FormStateInterface $form_state): void {
    $current_page = $this->getCurrentPage($form, $form_state);

    // Render the help text below submit button.
    $help_key = '#' . WebformWizardExtraPage::ELEMENT_KEY_HELP;
    if (isset($form['elements'][$current_page][$help_key])) {
      $form['elements']['actions'][] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->renderer->render($form['elements'][$current_page][$help_key]),
        '#attributes' => [
          'class' => ['webform-page-help', 'description'],
        ],
        '#weight' => 100,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    /*
     * In order to support Ajax, we need ajax-enabled buttons to
     * exist in the actions section. Create copies of each of the
     * buttons and link the two together using data attributes.
     *
     * When a user clicks the "Edit" button for a past step, the
     * corresponding action button will be clicked via JS.
     */
    $actions = parent::actions($form, $form_state);

    // Only modify actions for single-page Webforms.
    if (!$this->singlePageEnabled()) {
      return $actions;
    }

    // Only modify actions if the prefix element exists.
    if (empty($form['elements']['prefix'])) {
      return $actions;
    }

    $prefix_elements = $form['elements']['prefix'];
    foreach ($prefix_elements as $prefix_element) {
      if (!is_array($prefix_element)) {
        // Skip this loop iteration unless the element is a render array.
        continue;
      }
      foreach ($prefix_element as $key => $element) {
        if (isset($element['#type'])) {
          if ($element['#type'] == 'submit') {
            $actions[$key] = $element;

            // Ensure the button is hidden.
            $actions[$key]['#attributes']['class'][] = 'hidden';

            // Name must be unique so triggering element is correct.
            $actions[$key]['#name'] .= '-action';

            // Set data attributes so JS can link the buttons together.
            $actions[$key]['#attributes']['data-trigger-step'] = $actions[$key]['#attributes']['data-edit-step'];
            unset($actions[$key]['#attributes']['data-edit-step']);
          }
        }
      }
    }

    // Remove the standard back button.
    if (!empty($actions['wizard_prev'])) {
      unset($actions['wizard_prev']);
    }
    return $actions;
  }

}
