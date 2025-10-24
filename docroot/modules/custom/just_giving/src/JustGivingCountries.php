<?php

namespace Drupal\just_giving;

/**
 * Just Giving Countries class.
 */
class JustGivingCountries implements JustGivingCountriesInterface {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   */
  protected JustGivingClient $justGivingClient;

  /**
   * JustGivingCountries constructor.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   *   The just giving client service.
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * {@inheritDoc}
   */
  public function getCountriesFormList(): ?array {
    if (!$this->justGivingClient->jgLoad()) {
      return NULL;
    }
    else {
      $jgCountries = $this->justGivingClient->jgLoad()->Countries->Countries();
      $countryList = ['0' => "Please Select a Country"];
      foreach ($jgCountries as $index) {
        $countryCode = $index->countryCode;
        $name = $index->name;
        $countryList[$countryCode] = $name;
      }
      return $countryList;
    }
  }

}
