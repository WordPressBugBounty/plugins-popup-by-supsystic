<?php
if (!defined('ABSPATH')) {
  exit();
}
$newsletter = is_array($this->newsletter ?? null) ? $this->newsletter : [];
$state = $newsletter['state'] ?? [];
$status = (string) ($state['status'] ?? 'draft');
$chainId = (int) ($this->options['obj_id'] ?? 0);
$dateFormat = get_option('date_format') . ' ' . get_option('time_format');
$isSent = in_array($status, ['preparing', 'sending', 'sent'], true);
$statusText = [
  'draft' => __('Draft — not sent yet', 'ppsgen'),
  'scheduled' => sprintf(__('Scheduled for %s', 'ppsgen'), date_i18n($dateFormat, (int) ($state['send_at'] ?? 0))),
  'preparing' => __('Preparing the recipient list…', 'ppsgen'),
  'sending' => sprintf(__('Sending since %s', 'ppsgen'), date_i18n($dateFormat, (int) ($state['sent_at'] ?? 0))),
  'sent' => sprintf(__('Sent on %s', 'ppsgen'), date_i18n($dateFormat, (int) ($state['sent_at'] ?? 0))),
];
$sendTo = (string) ($newsletter['label'] ?? __('All contacts', 'ppsgen'));
$lists = is_array($newsletter['lists'] ?? null) ? $newsletter['lists'] : [];
$listId = (string) ($newsletter['list_id'] ?? '');
$minSchedule = date('Y-m-d\TH:i', current_time('timestamp') + 5 * MINUTE_IN_SECONDS);
?>
<?php $extras = is_array($newsletter['extras'] ?? null) ? $newsletter['extras'] : ['preview_text' => '', 'reply_to' => '', 'utm' => false]; ?>
<div class="ppsgenNewsletterPanel" data-chain-id="<?php echo esc_attr($chainId); ?>">
  <div class="ppsgenNlTop">
    <span class="ppsgenNlStatus ppsgenNlStatus-<?php echo esc_attr($status); ?>"><?php echo esc_html($statusText[$status] ?? ''); ?></span>
    <span class="ppsgenNlSendTo">
      <span class="ppsgenNlLabel"><?php esc_html_e('Send to', 'ppsgen'); ?></span>
      <?php if (!$isSent && !empty($lists)) { ?>
        <select class="ppsgenNewsletterListSelect" aria-label="<?php esc_attr_e('Send to', 'ppsgen'); ?>">
          <option value=""<?php selected($listId, ''); ?>><?php esc_html_e('Tags in Send to & options', 'ppsgen'); ?></option>
          <?php foreach ($lists as $list) { ?>
            <option value="<?php echo esc_attr($list['id']); ?>"<?php selected($listId, (string) $list['id']); ?>><?php echo esc_html(sprintf(__('List: %s', 'ppsgen'), $list['name'])); ?></option>
          <?php } ?>
        </select>
      <?php } else { ?>
        <strong><?php echo esc_html($sendTo); ?></strong>
      <?php } ?>
      <span class="ppsgenNlCount"><?php echo esc_html(sprintf(_n('%s contact', '%s contacts', $isSent ? (int) $state['recipients'] : (int) $newsletter['audience'], 'ppsgen'), number_format_i18n($isSent ? (int) $state['recipients'] : (int) $newsletter['audience']))); ?></span>
      <?php if (!$isSent && $listId === '') { ?>
        <a href="#" class="ppsgenNewsletterOpenOptions"><?php esc_html_e('Change tags', 'ppsgen'); ?></a>
      <?php } elseif (!$isSent) { ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_list')); ?>"><?php esc_html_e('Manage lists', 'ppsgen'); ?></a>
      <?php } ?>
      <?php if (!$isSent && !empty($newsletter['beyond_limit'])) { ?>
        <span class="ppsgenNlWarn">
          <?php echo esc_html(sprintf(_n('%s is beyond the 500 Free contacts', '%s are beyond the 500 Free contacts', (int) $newsletter['beyond_limit'], 'ppsgen'), number_format_i18n((int) $newsletter['beyond_limit']))); ?>
          <a class="ppsgenPartOfChip" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=settings_pro')); ?>">PRO</a>
        </span>
      <?php } ?>
    </span>
    <span class="ppsgenNewsletterPanelActions">
      <a class="ppsgenTableListBtn ppsgenNewsletterAction" data-action="sendNewsletterTest"><span class="dashicons dashicons-email"></span> <?php esc_html_e('Send test', 'ppsgen'); ?></a>
      <?php if ($status === 'draft') { ?>
        <input type="datetime-local" class="ppsgenNewsletterScheduleInput" min="<?php echo esc_attr($minSchedule); ?>" aria-label="<?php esc_attr_e('Send at', 'ppsgen'); ?>">
        <a class="ppsgenTableListBtn ppsgenNewsletterAction" data-action="scheduleNewsletter"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Schedule', 'ppsgen'); ?></a>
      <?php } elseif ($status === 'scheduled') { ?>
        <a class="ppsgenTableListBtn ppsgenNewsletterAction" data-action="cancelNewsletterSchedule"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Cancel schedule', 'ppsgen'); ?></a>
      <?php } ?>
      <?php if (in_array($status, ['draft', 'scheduled'], true)) { ?>
        <a
          class="ppsgenTableListBtn ppsgenNewsletterAction ppsgenNlSendNow"
          data-action="sendNewsletterNow"
          data-confirm="<?php echo esc_attr(sprintf(__('Send this newsletter now to about %s contacts? This cannot be undone.', 'ppsgen'), number_format_i18n((int) $newsletter['audience']))); ?>"
        ><span class="dashicons dashicons-controls-play"></span> <?php esc_html_e('Send now', 'ppsgen'); ?></a>
      <?php } ?>
    </span>
  </div>

  <div class="ppsgenNlFields">
    <label class="ppsgenNlField ppsgenNlFieldWide">
      <span class="ppsgenNlLabel"><?php esc_html_e('Preview text', 'ppsgen'); ?></span>
      <?php if ($isSent) { ?>
        <span class="ppsgenNlValue"><?php echo $extras['preview_text'] !== '' ? esc_html($extras['preview_text']) : '—'; ?></span>
      <?php } else { ?>
        <input type="text" class="ppsgenNewsletterPreviewText ppsgenNlAutosave" maxlength="150" value="<?php echo esc_attr($extras['preview_text']); ?>" placeholder="<?php esc_attr_e('Shown next to the subject in the inbox', 'ppsgen'); ?>">
      <?php } ?>
    </label>
    <label class="ppsgenNlField">
      <span class="ppsgenNlLabel"><?php esc_html_e('Tag people who click', 'ppsgen'); ?></span>
      <?php if (!empty($newsletter['can_tag_clicks'])) { ?>
        <?php if ($isSent) { ?>
          <span class="ppsgenNlValue"><?php echo ($newsletter['click_tag'] ?? '') !== '' ? esc_html($newsletter['click_tag']) : '—'; ?></span>
        <?php } else { ?>
          <input type="text" class="ppsgenNewsletterClickTag ppsgenNlAutosave" list="ppsgenNewsletterTagList" value="<?php echo esc_attr($newsletter['click_tag'] ?? ''); ?>" placeholder="<?php esc_attr_e('e.g. spring-offer', 'ppsgen'); ?>">
          <datalist id="ppsgenNewsletterTagList"><?php foreach ((array) ($newsletter['all_tags'] ?? []) as $tagSlug) { ?><option value="<?php echo esc_attr($tagSlug); ?>"></option><?php } ?></datalist>
        <?php } ?>
      <?php } else { ?>
        <span class="ppsgenNlValue"><?php esc_html_e('so a popup or automation can follow up', 'ppsgen'); ?> <a class="ppsgenPartOfChip" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=settings_pro')); ?>">PRO</a></span>
      <?php } ?>
    </label>
    <label class="ppsgenNlField">
      <span class="ppsgenNlLabel"><?php esc_html_e('Reply-to', 'ppsgen'); ?></span>
      <?php if ($isSent) { ?>
        <span class="ppsgenNlValue"><?php echo $extras['reply_to'] !== '' ? esc_html($extras['reply_to']) : esc_html__('the sender address', 'ppsgen'); ?></span>
      <?php } else { ?>
        <input type="email" class="ppsgenNewsletterReplyTo ppsgenNlAutosave" value="<?php echo esc_attr($extras['reply_to']); ?>" placeholder="<?php esc_attr_e('the sender address', 'ppsgen'); ?>">
      <?php } ?>
    </label>
    <label class="ppsgenNlCheck" title="<?php esc_attr_e('Adds utm_source=newsletter, utm_medium=email and the newsletter name to links to your site, so your analytics shows these visits', 'ppsgen'); ?>">
      <input type="checkbox" class="ppsgenNewsletterUtm ppsgenNlAutosave"<?php checked($extras['utm']); ?><?php disabled($isSent); ?>>
      <?php esc_html_e('UTM tags on links', 'ppsgen'); ?>
    </label>
    <?php if ($isSent) { ?>
      <span class="ppsgenNlField">
        <span class="ppsgenNlLabel"><?php esc_html_e('Opened / clicked', 'ppsgen'); ?></span>
        <span class="ppsgenNlValue"><?php echo esc_html(number_format_i18n((float) $state['opened_rate'], 1) . '% / ' . number_format_i18n((float) $state['clicked_rate'], 1) . '%'); ?></span>
      </span>
    <?php } else { ?>
      <span class="ppsgenNlSaved"></span>
    <?php } ?>
  </div>
  <?php if (!$isSent) { ?>
    <p class="ppsgenNewsletterPanelHint"><?php esc_html_e('Write the email below and save it. Unsubscribed people are always left out. Newsletters don’t use your 1 Free automation.', 'ppsgen'); ?></p>
  <?php } ?>
</div>
