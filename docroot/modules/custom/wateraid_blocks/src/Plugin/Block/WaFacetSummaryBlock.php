<?php

declare(strict_types=1);

namespace Drupal\wateraid_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * Provides a facet summary block.
 *
 * @Block(
 *   id = "wateraid_blocks_facet_summary",
 *   admin_label = @Translation("Facet Summary"),
 *   category = @Translation("Views"),
 * )
 */
final class WaFacetSummaryBlock extends BlockBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      'content' => [
        '#lazy_builder' => [
          self::class . '::lazyBuilder',
          [],
        ],
      ],
    ];
  }

  /**
   * The trusted callback return.
   *
   * @return string[]
   *  An array of trusted callbacks.
   */
  public static function trustedCallbacks(): array {
    return [
      'lazyBuilder',
    ];
  }

  /**
   * Builds the block content.
   *
   * @return array[]
   *   An array of facets with links to reset, or empty if none set.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function lazyBuilder(): array {
    $view_id = NULL;
    $links = [];

    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if ($node->hasField('field_view')) {
        $view_info = $node->get('field_view')->getValue();

        if (isset($view_info[0]['target_id'])) {
          $view_id = $view_info[0]['target_id'];
        }
      }
    }

    if ($view_id && $params = \Drupal::request()->query->all()) {
      $map = self::getViewMap();

      if (isset($params['keywords']) && $params['keywords'] !== '') {
        $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
        $copy = $params;
        unset($copy['keywords']);
        $url->setOptions([
          'query' => $copy,
          'attributes' => [
            'aria-label' => t('Remove the :search search filter', [
              ':search' => $params['keywords'],
            ]),
          ],
        ]);
        $links[] = Link::fromTextAndUrl($params['keywords'], $url);
      }

      if (isset($map[$view_id]['block_1'])) {
        foreach ($params as $vocab => $tids) {
          if (isset($map[$view_id]['block_1'][$vocab])) {

            /** @var \Drupal\taxonomy\TermInterface $term */
            foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($tids) as $term) {
              $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
              $copy = $params;
              unset($copy[$vocab][$term->id()]);
              $url->setOptions([
                'query' => $copy,
                'attributes' => [
                  'class' => [
                    'wa-pill',
                  ],
                  'aria-label' => t('Remove the :term filter', [
                    ':term' => $term->label(),
                  ]),
                ],
              ]);
              $links[] = Link::fromTextAndUrl($term->label(), $url);
            }
          }
        }
      }
    }

    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#attributes' => [],
      '#configuration' => [
        'provider' => 'wateraid_block',
      ],
      '#plugin_id' => 'wateraid_blocks_facet_summary',
      '#base_plugin_id' => 'wateraid_blocks_facet_summary',
      '#derivative_plugin_id' => 'wateraid_blocks_facet_summary',
      '#theme' => 'block',
      'content' => [],
    ];

    if ($links) {
      $links[] = Link::createFromRoute('Clear all filters', 'entity.node.canonical', ['node' => $node->id()], [
        'attributes' => [
          'aria-label' => t('Remove all search filters'),
        ],
      ]);
      $build['content'] = [
        '#theme' => 'item_list',
        '#title' => '',
        '#items' => $links,
        '#type' => 'ul',
      ];
    }

    return $build;
  }

  /**
   * Holder to return a map of view ids and settings.
   *
   * @return array[]
   *   The settings for the fact blocks.
   */
  public static function getViewMap(): array {
    return [
      'events_solr' => [
        'block_1' => [
          'type' => 'type',
          'regions' => 'regions',
        ],
      ],
      'blogs_solr' => [
        'block_1' => [
          'audience' => 'audience',
          'country' => 'country',
          'get_involved' => 'get_involved',
          'theme' => 'theme',
        ],
      ],
      'press_releases_solr' => [
        'block_1' => [
          'country' => 'country',
          'event_type' => 'event_type',
          'key_stage' => 'key_stage',
          'regions' => 'regions',
          'subject_area' => 'subject_area',
          'theme' => 'theme',
          'topic' => 'topic',
        ],
      ],
      'stories_solr' => [
        'block_1' => [
          'audience' => 'audience',
          'country' => 'country',
          'get_involved' => 'get_involved',
          'theme' => 'theme',
        ],
      ],
    ];
  }

}
