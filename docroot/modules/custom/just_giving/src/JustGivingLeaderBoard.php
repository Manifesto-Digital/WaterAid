<?php

namespace Drupal\just_giving;

/**
 * Generate a just Giving leaderboard.
 *
 * @package Drupal\just_giving
 */
class JustGivingLeaderBoard {

  /**
   * JustGiving Client interface.
   */
  protected JustGivingClientInterface $justGivingClient;

  /**
   * Constructs a new JustGivingAccount object.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   *   JustGiving Client interface.
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * Get the JustGiving leader board.
   *
   * @param string $leaderBoardType
   *   Leader board type.
   * @param int $id
   *   JustGiving entity ID.
   *
   * @throws \Exception
   *
   * @return mixed
   *   Stuff.
   */
  public function getLeaderBoard(string $leaderBoardType, int $id): mixed {

    switch ($leaderBoardType) {
      case 'charity':
        return $this->getCharityLeaderboard($id);

      case 'event':
        return $this->getEventLeaderboard($id);

      default:
        throw new \Exception('Invalid leaderboard type.');
    }
  }

  /**
   * Get charity leader board.
   *
   * @param int $charityId
   *   The JustGiving charity id.
   *
   * @return mixed
   *   Stuff.
   */
  private function getCharityLeaderboard(int $charityId): mixed {
    if ($this->justGivingClient->jgLoad() === FALSE) {
      return NULL;
    }
    return $this->justGivingClient->jgLoad()->Leaderboard->GetCharityLeaderboard($charityId);
  }

  /**
   * Get event leader board.
   *
   * @param int $eventId
   *   The JustGiving event id.
   *
   * @return mixed
   *   Stuff.
   */
  private function getEventLeaderboard(int $eventId): mixed {
    if ($this->justGivingClient->jgLoad() === FALSE) {
      return NULL;
    }
    return $this->justGivingClient->jgLoad()->Leaderboard->GetEventLeaderboard($eventId);
  }

}
