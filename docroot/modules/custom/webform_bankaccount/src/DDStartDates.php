<?php

namespace Drupal\webform_bankaccount;

use Yasumi\Yasumi;

/**
 * Helper to calculate start dates for direct debits.
 */
class DDStartDates {

  /**
   * Return start dates for a given date, country and set of options.
   *
   * @param string $country_code
   *   The country code.
   * @param \DateTime|null $date
   *   The date to find start dates.
   * @param mixed[] $options
   *   Options.
   *
   * @return \DateTime[]
   *   An array of start dates.
   *
   * @throws \ReflectionException
   */
  public static function startDates(string $country_code = 'GB', ?\DateTime $date = NULL, array $options = []): array {
    // If no date given default to now.
    if (is_null($date)) {
      $date = new \DateTime('now', NULL);
    }

    // Get all public holidays for the given country for the current year.
    $providers = Yasumi::getProviders();
    $holidays = $next_years_holidays = NULL;

    if (!empty($providers[$country_code])) {
      $holidays = Yasumi::create($providers[$country_code], $date->format('Y'));
      $next_year = clone $date;
      $next_year = $next_year->modify('+1 year')->format('Y');
      $next_years_holidays = Yasumi::create($providers[$country_code], $next_year);
    }

    // Start collecting the start dates.
    $start_dates = [];

    // Advance 12 working days excluding weekends and bank/national holidays.
    $workingdays = 0;
    while ($workingdays < 12) {

      if ($holidays && !$holidays->isWorkingDay($date) && $date->format('Y') == $holidays->getYear()) {
        // Advance the date 1 weekday and don't count this as a working day.
        $date->modify('+1 weekday');
        continue;
      }
      elseif ($next_years_holidays && !$next_years_holidays->isWorkingDay($date) && $date->format('Y') == $next_years_holidays->getYear()) {
        $date->modify('+1 weekday');
        continue;
      }

      // Advance the date 1 weekday (will skip from Fri to Mon).
      $date->modify('+1 weekday');

      if (!(isset($holidays) && $holidays->isHoliday($date)) && !(isset($next_years_holidays) && $next_years_holidays->isHoliday($date))) {
        // The date is a work day - include this in the working day count.
        $workingdays += 1;
      }
    }

    // Now get the next 1st, 15th and 25th of the month.
    while (count($start_dates) < 3) {
      $day_of_month = $date->format('j');
      if (in_array($date->format('j'), ['1', '15', '25'])) {
        // The date is 1st, 15th or 25th. Add to our set of start dates.
        $start_dates[$day_of_month] = clone $date;
      }

      // Try the next day.
      $date->modify('+1 day');
    }

    return $start_dates;
  }

}
