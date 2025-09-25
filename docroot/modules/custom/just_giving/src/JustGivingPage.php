<?php

namespace Drupal\just_giving;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Class justGivingPage.
 */
class JustGivingPage implements JustGivingPageInterface {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   */
  protected JustGivingClient $justGivingClient;

  /**
   * The user information.
   *
   * @var mixed[]
   */
  protected array $userInfo;

  /**
   * The page info.
   */
  protected mixed $pageInfo;

  /**
   * The Register Page request.
   */
  protected \RegisterPageRequest $regPageRequest;

  /**
   * The module config.
   */
  protected ImmutableConfig $config;

  /**
   * JustGivingPage constructor.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   *   The client interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory service.
   */
  public function __construct(JustGivingClientInterface $just_giving_client, ConfigFactoryInterface $config_factory) {
    $this->justGivingClient = $just_giving_client;
    $this->config = $config_factory->get('just_giving.justgivingconfig');
  }

  /**
   * {@inheritDoc}
   */
  public function setUserInfo(array $userInfo): void {
    $this->userInfo = $userInfo;
  }

  /**
   * {@inheritDoc}
   */
  public function setPageInfo(mixed $pageInfo): void {
    $this->pageInfo = $pageInfo;
  }

  /**
   * {@inheritDoc}
   */
  public function registerFundraisingPage(): string {

    // Set signup user credentials.
    $this->justGivingClient->setUsername($this->userInfo['username']);
    $this->justGivingClient->setPassword($this->userInfo['password']);

    // Pull config and just giving field name for current node.
    $jgFieldName = $this->contentTypeJustGivingFields();

    // Load RegisterPageClass to prepare object for save.
    $this->regPageRequest = new \RegisterPageRequest();

    $this->regPageRequest->reference = NULL;
    $this->regPageRequest->charityId = $this->config->get('charity_id');
    $this->regPageRequest->eventId = $this->pageInfo->get($jgFieldName)->event_id;
    $this->regPageRequest->causeId = NULL;
    $this->regPageRequest->pageShortName = $this->pageShortName();
    $this->regPageRequest->pageStory = $this->pageInfo->get($jgFieldName)->page_story;
    $this->regPageRequest->pageSummaryWhat = $this->pageInfo->get($jgFieldName)->page_summary_what;
    $this->regPageRequest->pageSummaryWhy = $this->pageInfo->get($jgFieldName)->page_summary_what;

    $this->regPageRequest->pageTitle = $this->pageInfo->getTitle();
    $this->regPageRequest->eventName = $this->pageInfo->getTitle();
    // @todo this probably is an option for the user, if their date is different.
    $this->regPageRequest->eventDate = NULL;
    $this->regPageRequest->targetAmount = $this->pageInfo->get($jgFieldName)->suggested_target_amount;
    // @todo add currency to configuration form.
    $this->regPageRequest->currency = "GBP";

    // @todo probably to add to user form as fields.
    $this->regPageRequest->charityOptIn = FALSE;
    $this->regPageRequest->justGivingOptIn = FALSE;
    $this->regPageRequest->charityFunded = FALSE;

    // @todo Add images to the field plugin to provide default.
    $this->regPageRequest->images = NULL;
    $this->regPageRequest->videos = NULL;

    // @todo add team id configuration to plugin form.
    $this->regPageRequest->teamid = NULL;

    // @todo what purpose, add config.
    $this->regPageRequest->attribution = NULL;

    return $this->createPage($this->regPageRequest);
  }

  /**
   * Page short name.
   *
   * @return mixed
   *   Returns page short name.
   */
  private function pageShortName(): mixed {
    $page_url_suggest = $this->userInfo['first_name'] . ' ';
    $page_url_suggest .= $this->userInfo['last_name'] . ' ';
    $page_url_suggest .= $this->pageInfo->getTitle();

    $suggestedShortName = $this->suggestPageShortNames($page_url_suggest);
    return $this->checkShortName($suggestedShortName);
  }

  /**
   * Suggest Page short names.
   *
   * @param string $shortname_string
   *   Name suggest parameter.
   *
   * @return mixed
   *   Returns shortname value.
   */
  private function suggestPageShortNames(string $shortname_string): mixed {

    return $this->justGivingClient->jgLoad()->Page->SuggestPageShortNames($shortname_string);
  }

  /**
   * Check short name.
   *
   * @param mixed $suggestedShortName
   *   The short name parameter.
   *
   * @return mixed
   *   Returns item.
   */
  private function checkShortName(mixed $suggestedShortName): mixed {

    foreach ($suggestedShortName->Names as $item) {
      if (!$this->justGivingClient->jgLoad()->Page->IsShortNameRegistered($item)) {
        return $item;
      }
    }

    return NULL;
  }

  /**
   * Content type just giving fields.
   *
   * @return int|string|null
   *   Returns int or string value.
   */
  private function contentTypeJustGivingFields(): int|string|null {
    $jg_field = $this->pageInfo->getFieldDefinitions();

    foreach ($jg_field as $index => $item) {
      $field_type = $item->gettype();
      if (isset($field_type) && $field_type == 'just_giving_field_type') {
        return $index;
      }
    }

    return NULL;
  }

  /**
   * Create page.
   *
   * @param \RegisterPageRequest $regPageRequest
   *   The page request.
   *
   * @return string
   *   Returns an array.
   */
  private function createPage(\RegisterPageRequest $regPageRequest): string {
    $createPage = $this->justGivingClient->jgLoad()->Page->Create($regPageRequest);
    // @todo convert into a better return object to render to user.
    if ($createPage->error === TRUE) {
      $pageResult = 'Event is not available at this time, please try again later.';
    }
    elseif ($createPage->error === NULL) {
      // @todo Find a better way todo this using twig, refactor this.
      //   $pageUrl = Url::fromUri($createPage->next->uri);
      //   $pageLink = Link::fromTextAndUrl('This is a link', $pageUrl);
      //   $signOnUrl = Url::fromUri($createPage->signOnUrl);
      //   $signOnLink = Link::fromTextAndUrl('This is a link', $signOnUrl);
      $pageResult = "<h3>Thank-you for registering for the event!</h3>";
      $pageResult .= "<div>You can view your page by following this link:</div><div>";
      $pageResult .= '<a href="' . $createPage->next->uri . '" target="_blank">' . $createPage->next->uri . '</a>';
      $pageResult .= "<div>You can sign in to your account using the following URL:</div><div>";
      $pageResult .= '<a href="' . $createPage->signOnUrl . '" target="_blank">' . $createPage->signOnUrl . '</a>';
      $pageResult .= "</div>";
    }
    else {
      $pageResult = 'Event is not available at this time, please try again later.';
    }
    return $pageResult;
  }

}
