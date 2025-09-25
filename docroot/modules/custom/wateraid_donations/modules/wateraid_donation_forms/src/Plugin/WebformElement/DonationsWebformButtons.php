<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\OptionsBase;

/**
 * Provides a custom WA donations 'buttons' element.
 *
 * @WebformElement(
 *   id = "donations_webform_buttons",
 *   label = @Translation("WA Buttons"),
 *   description = @Translation("Provides a group of multiple buttons used for selecting a value."),
 *   category = @Translation("Options elements"),
 * )
 */
class DonationsWebformButtons extends OptionsBase {}
