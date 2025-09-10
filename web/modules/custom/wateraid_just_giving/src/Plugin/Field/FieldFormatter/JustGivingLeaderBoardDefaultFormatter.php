<?php

namespace Drupal\wateraid_just_giving\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\just_giving\JustGivingLeaderBoard;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "just_giving_leaderboard",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "just_giving_leaderboard",
 *   }
 * )
 */
class JustGivingLeaderBoardDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Just Giving Leader board.
   */
  protected JustGivingLeaderBoard $justGivingLeaderBoard;

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * Contains the configuration object factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('just_giving.leaderboard'),
      $container->get('logger.factory')->get('wateraid_just_giving'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new LinkFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param mixed[] $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param mixed[] $third_party_settings
   *   Third party settings.
   * @param \Drupal\just_giving\JustGivingLeaderBoard $leader_board
   *   Third party settings.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(string $plugin_id, mixed $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, string $label, string $view_mode, array $third_party_settings, JustGivingLeaderBoard $leader_board, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->justGivingLeaderBoard = $leader_board;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($items as $delta => $item) {

      $type = $item->type ?: NULL;
      $id = $item->just_giving_id ?: NULL;

      try {
        $leaderBoardApiResponse = $this->justGivingLeaderBoard->getLeaderBoard($type, $id);
        if ($leaderBoardApiResponse->httpStatusCode !== 200) {
          continue;
        }
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        continue;
      }

      // Check environment from settings.
      $config = $this->configFactory->get('just_giving.justgivingconfig');
      $justGivingBaseUrl = 'https://www.justgiving.com/fundraising/';
      if (str_contains($config->get('environments'), 'staging')) {
        $justGivingBaseUrl = 'https://www.staging.justgiving.com/fundraising/';
      }

      $listItems = [];
      foreach ($leaderBoardApiResponse->bodyResponse->pages as $topUser) {
        // Build fundraiser page URL.
        $fundraiserPageUrl = $justGivingBaseUrl . $topUser->pageShortName;
        $listItems[] = [
          '#prefix' => '<a href="' . $fundraiserPageUrl . '" target="_blank">',
          '#suffix' => '</a>',
          'children' => [
            [
              '#markup' => $topUser->owner,
              '#wrapper_attributes' => [
                'class' => ['leaderboard-owner'],
              ],
            ],
            [
              '#markup' => '£' . number_format($topUser->totalRaisedOnline, 2),
              '#wrapper_attributes' => [
                'class' => ['leaderboard-amount'],
              ],
            ],
            [
              '#markup' => '<img src="' . $topUser->defaultImage . '">',
              '#wrapper_attributes' => [
                'class' => ['leaderboard-image'],
              ],
            ],
          ],
        ];
      }

      $elements[$delta] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $listItems,
        '#wrapper_attributes' => [
          'class' => [
            'just-giving-leader-board-wrapper',
          ],
        ],
        '#attributes' => [
          'class' => [
            'leaderboard-list',
          ],
        ],
      ];
    }
    return $elements;
  }

}
