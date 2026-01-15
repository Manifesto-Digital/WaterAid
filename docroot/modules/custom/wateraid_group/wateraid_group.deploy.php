<?php

/**
 * @file
 *  Deploy hooks for the WaterAid Groups.
 */

/**
 * Adds GTM ids to groups.
 */
function wateraid_group_deploy_add_gtm_ids(&$sandbox): void {
  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = \Drupal::entityTypeManager()->getStorage('group')->getQuery()->accessCheck(FALSE)->execute();
    $sandbox['max'] = count($sandbox['ids']);
    $sandbox['#finished'] = 0;
  }

  $map = [
    1 => 'GTM-NFPXXW9',
    16 => 'GTM-K8MT39B',
    71 => 'GTM-P2DQWK6',
    76 => 'GTM-N9MR7FP',
    11 => 'GTM-M795DQD',
    66 => 'GTM-TNWX7J5',
    81 => 'GTM-5CM8C59',
    21 => 'GTM-PW4KRZW',
    26 => 'GTM-KNNHHP6P',
    36 => 'GTM-NF2V8H3',
    41 => 'GTM-PFT3H3V',
    46 => 'GTM-NQF84SP',
    61 => 'GTM-NC7SGM2',
    51 => 'GTM-5GN4ZD8',
    56 => 'GTM-N5SGNKN',
    31 => 'GTM-WHX9VPX',
    6 => 'GTM-5N98728',
  ];

  if ($id = array_pop($sandbox['ids'])) {
    if ($group = \Drupal::entityTypeManager()->getStorage('group')->load($id)) {
      if (array_key_exists($id, $map)) {
        $group->set('field_google_tag_id', $map[$id]);
        $group->save();
      }
    }
  }

  $sandbox['#finished'] = empty($sandbox['max']) || empty($sandbox['ids']) ? 1 : ($sandbox['max'] - count($sandbox['ids'])) / $sandbox['max'];
}
