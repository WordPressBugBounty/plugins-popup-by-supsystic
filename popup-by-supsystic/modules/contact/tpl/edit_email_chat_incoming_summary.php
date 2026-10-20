<?php
if (!defined('ABSPATH')) {
  exit();
}
$summary = is_array($this->contactEmailChatIncomingSummary ?? null) ? $this->contactEmailChatIncomingSummary : [];
$trackingEnabled = !empty($summary['tracking_enabled']);
$selectedUnhandledTotal = max(0, (int) ($summary['selected_unhandled_total'] ?? 0));
$allUnhandledTotal = max(0, (int) ($summary['all_unhandled_total'] ?? 0));
$otherUnhandledTotal = max(0, $allUnhandledTotal - $selectedUnhandledTotal);
$statusClass = $selectedUnhandledTotal > 0 ? ' is-alert' : ($trackingEnabled ? ' is-clear' : ' is-disabled');
if ($selectedUnhandledTotal > 0) {
  $statusText = sprintf(
    _n('%d incoming email needs attention', '%d incoming emails need attention', $selectedUnhandledTotal, 'ppsgen'),
    $selectedUnhandledTotal,
  );
} elseif ($trackingEnabled) {
  $statusText = __('Mailbox incoming tracking is clear.', 'ppsgen');
} else {
  $statusText = __('Mailbox incoming tracking is off for this mail config.', 'ppsgen');
}
?>
<div class="ppsgenContactEmailIncomingSummary">
  <span class="ppsgenContactCampaignPill ppsgenContactEmailIncomingPill<?php echo esc_attr($statusClass); ?>">
    <?php echo esc_html($statusText); ?>
  </span>
  <?php if ($otherUnhandledTotal > 0) { ?>
    <span class="ppsgenContactCampaignPill ppsgenContactEmailIncomingPill is-secondary">
      <?php
      echo esc_html(sprintf(
        _n('%d pending email in other mailboxes', '%d pending emails in other mailboxes', $otherUnhandledTotal, 'ppsgen'),
        $otherUnhandledTotal,
      ));
      ?>
    </span>
  <?php } ?>
  <?php if ($selectedUnhandledTotal > 0) { ?>
    <div
      class="ppsgenContactCampaignAction ppsgenContactEmailMarkHandledBtn"
      role="button"
      tabindex="0"
      aria-label="<?php esc_attr_e('Mark mailbox as handled', 'ppsgen'); ?>"
    >
      <?php esc_html_e('Mark Mailbox as Handled', 'ppsgen'); ?>
    </div>
  <?php } ?>
</div>
