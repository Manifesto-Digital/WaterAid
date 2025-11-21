<?php

namespace Drupal\wateraid_core\Entity;

use Drupal\user\Entity\User;

class WaUser extends User {

  /**
   * {@inheritdoc}
   */
  public function label() {

    // Fallback to the user's label.
    $name = parent::label();

    if ($this->hasField('field_real_name')) {
      if ($real = $this->get('field_real_name')->getString()) {
        $name = $real;
      }
    }

    return $name;
  }

}
