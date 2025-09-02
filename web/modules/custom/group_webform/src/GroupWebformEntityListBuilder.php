<?php

namespace Drupal\group_webform;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\webform\WebformEntityListBuilder;

/**
 * Removes webforms w/group relationships from main webform list.
 */
class GroupWebformEntityListBuilder extends WebformEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    $plugin_id = 'group_webform:webform';
    $group_relationships = [];
    // Load all the group webform content to exclude.
    /** @var \Drupal\group\Entity\GroupRelationshipInterface[] $group_relationships */
    try {
      $group_relationships = $this->entityTypeManager->getStorage('group_relationship')
        ->loadByPluginId($plugin_id);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // Exception handling.
      return parent::getEntityIds();
    }
    $webforms = [];
    if (!empty($group_relationships)) {
      foreach ($group_relationships as $group_relationship) {
        $webform = $group_relationship->getEntity();
        if (!$webform) {
          continue;
        }
        $webform_name = $webform->id();
        if (!in_array($webform_name, $webforms)) {
          $webforms[] = $webform_name;
        }
      }
    }

    $header = $this->buildHeader();
    if ($this->request->query->get('order') === (string) $header['results']['data']) {

      if (!empty($webforms)) {
        $entity_ids = $this->getQuery($this->keys, $this->category, $this->state)
          ->condition($this->entityType->getKey('id'), $webforms, 'NOT IN')
          ->execute();
      }
      else {
        $entity_ids = $this->getQuery($this->keys, $this->category, $this->state)
          ->execute();
      }
      // Make sure all entity ids have totals.
      $this->totalNumberOfResults += array_fill_keys($entity_ids, 0);

      // Calculate totals.
      // @see \Drupal\webform\WebformEntityStorage::getTotalNumberOfResults
      if ($entity_ids) {
        $query = $this->database->select('webform_submission', 'ws');
        $query->fields('ws', ['webform_id']);
        $query->condition('webform_id', $entity_ids, 'IN');
        $query->addExpression('COUNT(sid)', 'results');
        $query->groupBy('webform_id');
        $totals = array_map('intval', $query->execute()->fetchAllKeyed());
        foreach ($totals as $entity_id => $total) {
          $this->totalNumberOfResults[$entity_id] = $total;
        }
      }

      // Sort totals.
      asort($this->totalNumberOfResults, SORT_NUMERIC);
      if ($this->request->query->get('sort') === 'desc') {
        $this->totalNumberOfResults = array_reverse($this->totalNumberOfResults, TRUE);
      }

      // Build an associative array of entity ids from totals.
      $entity_ids = array_keys($this->totalNumberOfResults);
      $entity_ids = array_combine($entity_ids, $entity_ids);

      // Manually initialize and apply paging to the entity ids.
      $page = $this->request->query->get('page') ?: 0;
      $total = count($entity_ids);
      $limit = $this->getLimit();
      $start = ($page * $limit);
      \Drupal::service('pager.manager')->createPager($total, $limit);
      return array_slice($entity_ids, $start, $limit, TRUE);
    }
    else {
      if (!empty($webforms)) {
        $query = $this->getQuery($this->keys, $this->category, $this->state)
          ->condition($this->entityType->getKey('id'), $webforms, 'NOT IN');
      }
      else {
        $query = $this->getQuery($this->keys, $this->category, $this->state);
      }

      $query->tableSort($header);
      $query->pager($this->getLimit());
      $entity_ids = $query->execute();

      // Calculate totals.
      // @see \Drupal\webform\WebformEntityStorage::getTotalNumberOfResults
      if ($entity_ids) {
        $query = $this->database->select('webform_submission', 'ws');
        $query->fields('ws', ['webform_id']);
        $query->condition('webform_id', $entity_ids, 'IN');
        $query->addExpression('COUNT(sid)', 'results');
        $query->groupBy('webform_id');
        $this->totalNumberOfResults = array_map('intval', $query->execute()->fetchAllKeyed());
      }

      // Make sure all entity ids have totals.
      $this->totalNumberOfResults += array_fill_keys($entity_ids, 0);

      return $entity_ids;

    }
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    return [];
  }

}
