<?php

namespace Drupal\Tests\group_webform\Functional;

use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Test webform access form groups.
 *
 * @group group_webform
 */
class GroupWebformAccessTest extends GroupBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_webform', 'block'];

  /**
   * Checks webforms overview page access.
   */
  public function testWebformsOverviewAccess() {
    $group = $this->createGroup();
    $this->drupalGet('group/1/webforms');
    $this->assertSession()->statusCodeEquals(403);

    // Set permissions.
    $role = $group->getGroupType()->getMemberRole();
    $role->grantPermissions(['access group_webform overview']);
    $role->save();

    $this->drupalGet('group/1/webforms');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('There are no webforms related to this group yet.');
  }

  /**
   * Checks create webform actions.
   */
  public function testWebformsCreateAccess() {
    $group = $this->createGroup();

    $this->drupalPlaceBlock('local_actions_block', [
      'region' => 'content',
    ])->save();

    // Set permissions.
    $role = $group->getGroupType()->getMemberRole();
    $role->grantPermissions(['access group_webform overview']);
    $role->save();

    $this->drupalGet('group/1/webforms');
    $this->assertSession()->linkByHrefNotExists('group/1/webform/create');

    // Install webform entity for group.
    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    $storage->createFromPlugin($group->getGroupType(), 'group_webform:webform')->save();

    $role->grantPermissions(['create group_webform:webform entity']);
    $role->save();

    $this->drupalGet('group/1/webforms');
    $this->assertSession()->linkByHrefExists('group/1/webform/create');
    $this->assertSession()->linkByHrefNotExists('group/1/webform/add');

    $role->grantPermissions(['create group_webform:webform relationship']);
    $role->save();

    $this->drupalGet('group/1/webforms');
    $this->assertSession()->linkByHrefExists('group/1/webform/add');
  }

}
