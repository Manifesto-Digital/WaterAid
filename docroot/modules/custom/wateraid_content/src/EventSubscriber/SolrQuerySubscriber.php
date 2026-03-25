<?php

namespace Drupal\wateraid_content\EventSubscriber;

use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Utility\Utility;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters Solr queries for the wateraid_content module.
 */
class SolrQuerySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreQueryEvent::class => 'onPreQuery',
    ];
  }

  /**
   * Replaces separate sorts with a coalesced sort on press release views.
   *
   * Uses Solr's def() function to sort by field_published_date when it exists,
   * falling back to the changed date otherwise — treating both as a single
   * sort expression.
   */
  public function onPreQuery(PreQueryEvent $event): void {
    $search_api_query = $event->getSearchApiQuery();
    $view = $search_api_query->getOption('search_api_view');

    if (!$view || !in_array($view->id(), ['press_releases_solr_jp', 'press_releases_solr']) || !in_array($view->current_display, ['block_1', 'block_2'])) {
      return;
    }

    $index = $search_api_query->getIndex();
    /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SolrBackend $backend */
    $backend = $index->getServerInstance()->getBackend();
    $field_names = $backend->getSolrFieldNamesKeyedByLanguage(
      Utility::ensureLanguageCondition($search_api_query),
      $index,
    );

    $published_date = Utility::getSortableSolrField('field_published_date', $field_names, $search_api_query);
    $changed = Utility::getSortableSolrField('changed', $field_names, $search_api_query);

    /** @var \Solarium\QueryType\Select\Query\Query $solarium_query */
    $solarium_query = $event->getSolariumQuery();
    $solarium_query->clearSorts();
    $solarium_query->addSort("def($published_date,$changed)", 'desc');
  }

}
