<?php
// "Who sees it" rows shared by the popup and form editors (free and PRO). Expects $component, $settings and $sprefix
// from the including template; the component name prefix must already be the settings prefix.
if (!defined('ABSPATH')) {
  exit();
}
global $wpdb;
$audienceSettings = is_array($settings) ? $settings : [];
$audienceLists = [];
$audienceContact = FramePpsgen::getInstance()->getModule('contact');
foreach ($audienceContact ? $audienceContact->getModel()->getSavedContactLists() : [] as $audienceList) {
  $audienceLists[(string) $audienceList['id']] = (string) $audienceList['name'];
}
$audienceTags = array_column((array) $wpdb->get_results("SELECT id, slug FROM {$wpdb->prefix}ppsgen_contact_tags ORDER BY slug ASC", ARRAY_A), 'slug', 'id');
?>
<div class="ppsgenComponentRow">
  <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Who sees it', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Visitors are recognised automatically: logged-in users, people who submitted one of your forms or clicked a link in your emails (remembered in their browser), and, as a last resort, a recent visit from the same IP address.', 'ppsgen'); ?>"></div>
  <div class="ppsgenComponentOption">
    <?php $component->select('[audience]', [
      'options' => ppsgen_get_audience_rules(),
      'class' => 'ppsgenselect data-option-toggle-trigger',
      'selected' => $component->value($audienceSettings['audience'] ?? null, 'everyone'),
    ]); ?>
  </div>
</div>

<div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[audience]" data-toggle-val="in_list,not_in_list">
  <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('List', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('A saved list from Contacts › Lists. Membership is checked on every page view, so new contacts join the list automatically.', 'ppsgen'); ?>"></div>
  <div class="ppsgenComponentOption">
    <?php if (empty($audienceLists)) { ?>
      <span class="ppsgenMuted"><?php esc_html_e('No lists yet.', 'ppsgen'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_list')); ?>" target="_blank"><?php esc_html_e('Filter your contacts and save a list', 'ppsgen'); ?> &rarr;</a></span>
    <?php } else {
      $component->select('[audience_list_id]', [
        'options' => $audienceLists,
        'class' => 'ppsgenselect',
        'selected' => $component->value($audienceSettings['audience_list_id'] ?? null, (string) array_key_first($audienceLists)),
      ]);
    } ?>
  </div>
</div>

<div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[audience]" data-toggle-val="with_tags,without_tags">
  <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tags', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Matches contacts with any of the selected tags.', 'ppsgen'); ?>"></div>
  <div class="ppsgenComponentOption">
    <?php if (empty($audienceTags)) { ?>
      <span class="ppsgenMuted"><?php esc_html_e('No tags yet. Tags are added by forms, automations and plays, or in Contacts › Tags.', 'ppsgen'); ?></span>
    <?php } else {
      $component->selectmultiple('[audience_tags][]', [
        'options' => $audienceTags,
        'class' => 'ppsgenselect',
        'selected' => array_map('strval', (array) ($audienceSettings['audience_tags'] ?? [])),
      ]);
    } ?>
  </div>
</div>
