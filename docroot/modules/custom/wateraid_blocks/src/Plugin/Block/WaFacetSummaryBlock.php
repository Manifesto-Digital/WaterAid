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

            // Regions is a select list not a taxonomy reference so needs special handling.
            if ($vocab == 'regions') {
              $field_definition = \Drupal::service('entity_field.manager')->getFieldDefinitions('node','event')['field_regions'];
              $regions = $field_definition->getFieldStorageDefinition()->toArray()['settings']['allowed_values'];

              foreach ($tids as $tid) {
                if (array_key_exists($tid, $regions)) {
                  $links[] = self::getLink($regions[$tid], $tid, $vocab, $node->id(), $params);;
                }
              }
            }
            else {

              /** @var \Drupal\taxonomy\TermInterface $term */
              foreach (\Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadMultiple($tids) as $term) {
                $links[] = self::getLink($term->label(), $term->id(), $vocab, $node->id(), $params);
              }
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

  /**
   * Helper to create a link to remove the filter.
   *
   * @param string $label
   *   The link label
   * @param int|string $value
   *   The filter value
   * @param string $vocab
   *   The key of the value in the parameter array.
   * @param int|string $nid
   *   The node idea for the current URL.
   * @param array $params
   *   The array of currently set filter parameters.
   *
   * @return \Drupal\Core\Link
   *   The link.
   */
  public static function getLink(string $label, int|string $value, string $vocab, int|string $nid, array $params): Link {
    $url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);

    // Remove the parameter we want the link to remove.
    unset($params[$vocab][$value]);

    $url->setOptions([
      'query' => $params,
      'attributes' => [
        'class' => [
          'wa-pill',
        ],
        'aria-label' => t('Remove the :term filter', [
          ':term' => $label,
        ]),
      ],
    ]);

    return Link::fromTextAndUrl($label, $url);
  }

}
