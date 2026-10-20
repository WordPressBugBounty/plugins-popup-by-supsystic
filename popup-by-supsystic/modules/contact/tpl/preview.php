<?php
if (!defined('ABSPATH')) {
  exit();
}
$preview = is_array($this->preview ?? null) ? $this->preview : [];
$title = $preview['name'] !== '' ? $preview['name'] : ($preview['email'] !== '' ? $preview['email'] : sprintf(__('Contact #%d', 'ppsgen'), (int) $preview['id']));
$dateFormat = get_option('date_format') . ' ' . get_option('time_format');
?>
<div class="ppsgenContactPreview">
  <div class="ppsgenContactPreviewHead">
    <h2><?php echo esc_html($title); ?></h2>
    <button type="button" class="ppsgenContactPreviewClose" aria-label="<?php esc_attr_e('Close', 'ppsgen'); ?>"><span class="dashicons dashicons-no-alt"></span></button>
  </div>
  <div class="ppsgenContactPreviewMeta">
    <?php if ($preview['email'] !== '') { ?>
      <a href="mailto:<?php echo esc_attr($preview['email']); ?>"><?php echo esc_html($preview['email']); ?></a>
    <?php } ?>
    <?php if ($preview['phone'] !== '') { ?>
      <a href="tel:<?php echo esc_attr($preview['phone']); ?>"><?php echo esc_html($preview['phone']); ?></a>
    <?php } ?>
    <span class="ppsgenMuted">
      <?php echo esc_html($preview['verified'] ? __('Verified', 'ppsgen') : __('Not verified', 'ppsgen')); ?>
      <?php if ($preview['created_at'] !== '') { ?>
        · <?php echo esc_html(sprintf(__('Added %s', 'ppsgen'), date_i18n(get_option('date_format'), strtotime($preview['created_at'])))); ?>
      <?php } ?>
    </span>
  </div>
  <div class="ppsgenContactPreviewTags">
    <?php if (!empty($preview['tags'])) { ?>
      <?php foreach ($preview['tags'] as $tag) { ?>
        <span class="ppsgenChip"><?php echo esc_html($tag); ?></span>
      <?php } ?>
    <?php } else { ?>
      <span class="ppsgenMuted"><?php esc_html_e('No tags yet', 'ppsgen'); ?></span>
    <?php } ?>
  </div>
  <div class="ppsgenContactPreviewActions">
    <a class="ppsgenBtnSecondary" href="<?php echo esc_url(add_query_arg('obj_edit_active_tab', 'email_chat', $preview['edit_url'])); ?>"><?php esc_html_e('Email', 'ppsgen'); ?></a>
    <?php if (!empty($preview['replied'])) { ?>
      <span class="ppsgenChip"><?php esc_html_e('Replied', 'ppsgen'); ?></span>
    <?php } else { ?>
      <button type="button" class="ppsgenBtnSecondary ppsgenContactMarkReplied" data-contact-id="<?php echo (int) $preview['id']; ?>"><?php esc_html_e('Mark replied', 'ppsgen'); ?></button>
    <?php } ?>
    <a class="ppsgenBtnSecondary" href="<?php echo esc_url(add_query_arg('obj_edit_active_tab', 'contact_tag', $preview['edit_url'])); ?>"><?php esc_html_e('Edit tags', 'ppsgen'); ?></a>
  </div>
  <?php
  $automationChoices = array_values(array_filter((array) ($preview['automations'] ?? []), function ($choice) {
    return empty($choice['in_it']);
  }));
  $inAutomations = array_column(array_filter((array) ($preview['automations'] ?? []), function ($choice) {
    return !empty($choice['in_it']);
  }), 'name');
  ?>
  <?php if (!empty($inAutomations)) { ?>
    <p class="ppsgenMuted"><?php echo esc_html(sprintf(__('In: %s', 'ppsgen'), implode(', ', $inAutomations))); ?></p>
  <?php } ?>
  <?php if (!empty($automationChoices)) { ?>
    <div class="ppsgenContactAddAutomation">
      <select class="ppsgenContactAutomationSelect" aria-label="<?php esc_attr_e('Automation', 'ppsgen'); ?>">
        <?php foreach ($automationChoices as $choice) { ?>
          <option value="<?php echo (int) $choice['id']; ?>"><?php echo esc_html($choice['name'] . ($choice['running'] ? '' : ' (' . __('off', 'ppsgen') . ')')); ?></option>
        <?php } ?>
      </select>
      <button type="button" class="ppsgenBtnSecondary ppsgenContactAddToAutomation" data-contact-id="<?php echo (int) $preview['id']; ?>"><?php esc_html_e('Add to automation', 'ppsgen'); ?></button>
    </div>
  <?php } ?>
  <div class="ppsgenContactPreviewLabel"><?php esc_html_e('Timeline', 'ppsgen'); ?></div>
  <?php if (!empty($preview['timeline'])) { ?>
    <ul class="ppsgenContactPreviewTimeline">
      <?php foreach ($preview['timeline'] as $event) { ?>
        <li>
          <span class="ppsgenContactPreviewEventDot ppsgenContactPreviewEventDot-<?php echo esc_attr($event['side'] ?? 'system'); ?>"></span>
          <div>
            <div class="ppsgenContactPreviewEventTitle"><?php echo esc_html((string) ($event['title'] ?? '')); ?></div>
            <?php if (!empty($event['text'])) { ?>
              <div class="ppsgenMuted ppsgenContactPreviewEventText"><?php echo esc_html(wp_trim_words((string) $event['text'], 18)); ?></div>
            <?php } ?>
            <?php if (!empty($event['time_ts'])) { ?>
              <div class="ppsgenMuted"><?php echo esc_html(date_i18n($dateFormat, (int) $event['time_ts'])); ?></div>
            <?php } ?>
          </div>
        </li>
      <?php } ?>
    </ul>
  <?php } else { ?>
    <p class="ppsgenEmpty"><?php esc_html_e('No activity yet.', 'ppsgen'); ?></p>
  <?php } ?>
  <a class="ppsgenContactPreviewFull" href="<?php echo esc_url($preview['edit_url']); ?>">
    <?php
    echo esc_html($preview['timeline_total'] > count($preview['timeline'])
      ? sprintf(__('Open full profile · %d events →', 'ppsgen'), (int) $preview['timeline_total'])
      : __('Open full profile →', 'ppsgen'));
    ?>
  </a>
</div>
