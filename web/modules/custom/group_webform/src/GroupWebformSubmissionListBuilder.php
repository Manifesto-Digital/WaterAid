<?php

namespace Drupal\group_webform;

use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformSubmissionListBuilder;

/**
 * Provides a list controller for webform submission entity.
 *
 * @ingroup webform
 */
class GroupWebformSubmissionListBuilder extends WebformSubmissionListBuilder {

  /**
   * Build the webform submission entity list.
   *
   * @return array
   *   A renderable array containing the entity list.
   */
  protected function buildEntityList() {
    $build = [];

    // Filter form.
    if (empty($this->account)) {
      $build['filter_form'] = $this->buildFilterForm();
    }

    // Customize buttons.
    if ($this->customize) {
      $build['custom_top'] = $this->buildCustomizeButton();
    }

    // Display info.
    if ($this->total) {
      $build['info'] = $this->buildInfo();
    }

    // Table.
    $build += $this->renderWithAccess();
    $build['table']['#sticky'] = TRUE;
    $build['table']['#attributes']['class'][] = 'webform-results-table';

    // Customize.
    // Only displayed when more than 20 submissions are being displayed.
    if ($this->customize && isset($build['table']['#rows']) && count($build['table']['#rows']) >= 20) {
      $build['custom_bottom'] = $this->buildCustomizeButton();
      if (isset($build['pager'])) {
        $build['pager']['#weight'] = 10;
      }
    }

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * Builds the entity listing as renderable array for table.html.twig.
   */
  public function renderWithAccess() {
    $plugin_id = 'group_webform:webform';
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    $group_relationship_storage = $this->entityTypeManager->getStorage('group_relationship');
    $account = $this->currentUser;
    $webform = $this->webform;
    // Load all group relationships for this webform.
    $group_relationships = $group_relationship_storage->loadByEntity($webform);

    // Get all groups to which this webform belongs,
    // including subgroups and parent groups.
    $groups = [];

    foreach ($group_relationships as $group_relationship) {
      $group = $group_relationship->getGroup();
      $groups[] = $group;
    }
    $groupPermission = FALSE;
    // If any one of the groups grants permission to view submissions
    // allow user to view them.
    foreach ($groups as $group) {
      if ($group->hasPermission("view any $plugin_id submission", $account)
        || $group->hasPermission("view $plugin_id submission", $account)
        || $group->hasPermission('submission_view_any ' . $plugin_id . 'entity', $account)
      ) {
        $groupPermission = TRUE;
        break;
      }
    }
    foreach ($this->load() as $webform_submission) {
      if (
          $account->hasPermission('administer webform submission')
          || $groupPermission === TRUE
         ) {
        if ($row = $this->buildRow($webform_submission)) {
          $build['table']['#rows'][$webform_submission->id()] = $row;
        }
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }

}
