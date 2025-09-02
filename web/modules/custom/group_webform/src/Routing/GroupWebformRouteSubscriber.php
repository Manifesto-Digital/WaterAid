<?php

namespace Drupal\group_webform\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modify form for config.sync route.
 */
class GroupWebformRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // See webform/webform.routing.yml and
    // webform.module hook webform_webform_access_rules().
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {
        // WEBFORM-RELATED ROUTES.
        // Webform/Webform Group Permission: 'administer webform'.
        case 'entity.webform_options.add_form':
        case 'entity.webform_options.edit_form':
        case 'entity.webform_options.collection':
        case 'entity.webform_options.duplicate_form':
        case 'entity.webform_options.delete_form':
        case 'entity.webform_options.autocomplete':
        case 'entity.webform.results_clear':
        case 'entity.webform.duplicate_form':
        case 'entity.webform.export_form':
        case 'entity.webform.test_form':
        case 'entity.webform_submission.collection_purge':
        case 'entity.webform_submission.notes_form':
        case 'entity.webform_submission.resend_form':
        case 'entity.webform.variant.duplicate_form':
        /*case 'webform.addons':
        case 'webform.config':
        case 'webform.config.elements':
        case 'webform.config.submissions':
        case 'webform.config.handlers':
        case 'webform.config.variants':
        case 'webform.config.exporters':
        case 'webform.config.libraries':
        case 'webform.config.advanced':
        case 'webform.help':
        case 'webform.help.video':
        case 'webform.reports_plugins.elements':
        case 'webform.reports_plugins.handlers':
        case 'webform.reports_plugins.variants':
        case 'webform.reports_plugins.exporters':*/
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformAdministerAccess']);
          break;

        // Webform Group Permission: 'view group_webform:webform entity'
        // Webform permission: webform.view.
        case 'entity.webform.assets.javascript':
        case 'entity.webform.assets.css':
          // '/webform/{webform}/confirmation'
        case 'entity.webform.confirmation':

          // Webform permission: webform.submission_page.
          // The webform page itself.
          // '/webform/{webform}'.
        case 'entity.webform.canonical':

          // Webform permission: webform.submission_create.
          // '/webform/{webform}/drafts/{submission_view}'.
        case 'entity.webform.user.drafts':
          // '/webform/{webform}/autocomplete/{key}'
        case 'webform.element.autocomplete':
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformViewAccess']);
          break;

        // Webform Group Permission:
        // 'update group_webform:webform entity',any/own
        // Webform permission: webform.update. (using 'update' as $op)
        case 'entity.webform.edit_form':
        //case 'entity.webform.add_form':
        case 'entity.webform.handlers':
        case 'entity.webform.handler':
        case 'entity.webform.handler.add_form':
        case 'entity.webform.handler.edit_form':
        case 'entity.webform.handler.add_email':
        case 'entity.webform.handler.duplicate_form':
        case 'entity.webform.handler.delete_form':
        case 'entity.webform.handler.enable':
        case 'entity.webform.handler.disable':
        case 'entity.webform.settings':
        case 'entity.webform.settings_form':
        case 'entity.webform.settings_submissions':
        case 'entity.webform.settings_confirmation':
        case 'entity.webform.settings_assets':
        case 'entity.webform.settings_access':
        case 'entity.webform.source_form':
        case 'entity.webform.results_submissions.custom':
        case 'entity.webform.variant':
        case 'entity.webform.variant.add_form':
        case 'entity.webform.variant.edit_form':
        case 'entity.webform.variant.duplicate_form':
        case 'entity.webform.variant.delete_form':
        case 'entity.webform.variant.test_form':
        case 'entity.webform.variant.share_form':
        case 'entity.webform.variant.enable':
        case 'entity.webform.variant.disable':
        case 'entity.webform.variant.apply_form':
        case 'entity.webform.variant.view_form':
        case 'entity.webform_ui.element.edit_form':
        case 'entity.webform_ui.element':
        case 'entity.webform_ui.change_element':
        case 'entity.webform_ui.element.add_form':
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformEditAccess']);
          break;

        // Webform Group Permission:'delete group_webform:webform entity'any/own
        // Webform permission: webform.delete.
        case 'entity.webform.delete_form':
        case 'entity.webform_ui.element.delete_form':
        case 'entity.webform.multiple_delete_confirm':
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformDeleteAccess']);
          break;

        // Webform Group Permission:
        // 'submission_view_any group_webform:webform entity'
        // Webform permission: webform.submission_view_any.
        // Note difference between this and 'webform_submission.view_any'.
        // Belongs to webform, not submission.
        case 'entity.webform_submission.collection':
        case 'entity.webform.results_export':
        case 'entity.webform.results_export_file':
        case 'entity.webform.results_submissions':
        case 'entity.webform.results_submissions.custom.user':
        case 'entity.webform.results.source_entity.autocomplete':
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformResultsAccess']);
          break;

        // Case entity.webform_submission.user:
        // Webform permission: Custom access check
        // from WebformAccountAccess::checkUserSubmissionsAccess
        // The "submissions" tab on the user profile page.
        // '/user/{user}/submissions/{submission_view}
        //
        // The Group Webform module is not checking this route;
        // we are allowing the webform sitewide permission
        // to provide that access check.
        // See additional notes on GroupWebformPermissionProvider.php
        // for more info.
        // WEBFORM SUBMISSION-RELATED ROUTES
        // 'view group_webform:webform submission' ''/any/own
        // *
        // Webform permission: webform_submission.view.
        // admin/structure/webform/manage/{webform}/submission/{webform_submission}.
        case 'entity.webform_submission.canonical':
          // '/admin/structure/webform/manage/{webform}/submission/{webform_submission}/table'
        case 'entity.webform_submission.table':
          // webform/{webform}/submissions/{webform_submission}.
        case 'entity.webform.user.submission':

          // Webform permission: webform_submission.view_own.
          // '/webform/{webform}/submissions/{submission_view}'.
        case 'entity.webform.user.submissions':

          // Webform permission: webform_submission.view_any.
          // Note difference between this and 'webform.submission_view_any'.
          // Belongs to submission, not webform.
        case 'entity.webform_submission.text':
        case 'entity.webform_submission.yaml':
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformSubmissionViewAccess']);
          break;

        // 'edit group_webform:webform submission' any/own
        // Webform permission: webform_submission.update.(using 'update' as $op)
        case 'entity.webform.user.submission.duplicate':
        case 'entity.webform.user.submission.edit':
        case 'entity.webform_submission.edit_form':
        case 'entity.webform_submission.edit_form.all':
        case 'entity.webform_submission.duplicate_form':
        case 'entity.webform_submission.locked_toggle':
        case 'entity.webform_submission.sticky_toggle':
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformSubmissionEditAccess']);
          break;

        // 'delete group_webform:webform submission' any/own
        // Webform permission: webform_submission.delete'.
        case 'entity.webform_submission.delete_form':
          // '/webform/{webform}/submissions/{webform_submission}/delete'
        case 'entity.webform.user.submission.delete':
          $route->setRequirements(['_custom_access' => '\Drupal\group_webform\Access\GroupWebformAccess::webformSubmissionDeleteAccess']);
          break;
      }
    }
  }

}
