## CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Field Widget
 * Subgroup
 * Permissions
 * Maintainers


## INTRODUCTION

This module is designed to associate group specific webforms with a group when
using the Group module.  This module creates Group Relationships directly
between the webform and the group, as opposed to requiring Webform Nodes.


 * For a full description:
   https://drupal.org/project/group_webform

 * Issue queue for Group Webform:
   https://drupal.org/project/issues/group_webform

## REQUIREMENTS

 * Group module (http://drupal.org/project/group).
 * Webform module. (http://drupal.org/project/webform).


## INSTALLATION

  * Install normally as other modules are installed. For support:
    https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules
  * Enable the Group Webform plugin for a group type via:
   _/admin/group/types/manage/[GROUP TYPE]/relationship_

## CONFIGURATION

 * The configuration options for a group type are available via:
   _/admin/group/types/manage/[GROUP TYPE]/relationship_


## FIELD WIDGET

 * Adds a new Webform Group Select Entity Reference Field Widget
 that restricts available webforms to only those accessible
 by the user.  It can be selected on Form Display pages
 whenever a Webform Entity Reference field is used,
 specifically on Paragraphs.

 * A checkbox is available to show or hide
 non-group-related webforms in the Field Widget
 for users who have sitewide admin access to webforms.

 * Users who do not have access to the webform selected
 in the selectbox can remove it and replace it with a
 webform to which they have access, but cannot replace an
 un-accessible webform if it is removed.


## SUBGROUP

* Should now support the Subgroup module by default.
Tested down to three levels:
(Group > Subgroup > Sub-subgroup).


## PERMISSIONS
* Permissions are slightly truncated from existing list of
Webform permissions. See explanation in
GroupWebformPermissionProvider.php for further details.


## MAINTAINERS

Current maintainers:
 * Ivan Duarte (jidrone) - https://www.drupal.org/u/jidrone
 * Fabian Sierra (fabiansierra5191) - https://www.drupal.org/u/fabiansierra5191
