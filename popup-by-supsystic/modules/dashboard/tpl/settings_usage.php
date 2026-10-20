<?php
if (!defined('ABSPATH')) {
  exit();
}
$enabled = function_exists('ppsgen_usage_tracking_enabled') && ppsgen_usage_tracking_enabled();
$snapshot = is_array($this->usageSnapshot ?? null) ? $this->usageSnapshot : [];
$lastSent = (int) get_option('ppsgen_usage_last_sent', 0);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle"><?php esc_html_e('Usage data', 'ppsgen'); ?></h2>
</div>
<div class="ppsgenSettingsCard ppsgenUsageSettings">
  <p><?php esc_html_e('Sharing anonymous usage counts helps us see where CES works well and where people get stuck. It is off unless you switch it on, it never includes names, emails or anything about your contacts, and it never turns any feature on or off.', 'ppsgen'); ?></p>
  <label class="ppsgenEditorLive<?php echo $enabled ? ' ppsgenEditorLiveOn' : ''; ?> ppsgenUsageSwitch">
    <input type="checkbox" class="ppsgenEditorLiveInput ppsgenUsageToggle"<?php echo $enabled ? ' checked' : ''; ?>>
    <span class="ppsgenEditorLiveTrack"></span>
    <span class="ppsgenEditorLiveText"><?php echo $enabled ? esc_html__('Sharing anonymous counts', 'ppsgen') : esc_html__('Not sharing', 'ppsgen'); ?></span>
  </label>
  <p class="ppsgenMuted">
    <?php
    echo esc_html($lastSent > 0
      /* translators: %s: date and time */
      ? sprintf(__('Last shared: %s', 'ppsgen'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $lastSent))
      : __('Nothing has been shared yet.', 'ppsgen'));
    ?>
  </p>
  <details class="ppsgenUsageDetails">
    <summary><?php esc_html_e('See exactly what would be shared', 'ppsgen'); ?></summary>
    <pre><?php echo esc_html(wp_json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></pre>
  </details>
</div>
