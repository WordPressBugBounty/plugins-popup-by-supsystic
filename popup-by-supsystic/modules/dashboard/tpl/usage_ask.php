<?php
// One-time question about anonymous usage data, shown only after a real result (first live play, PRO activation)
if (!defined('ABSPATH')) {
  exit();
}
if (!function_exists('ppsgen_usage_tracking_choice') || ppsgen_usage_tracking_choice() !== '') {
  return;
}
?>
<div class="ppsgenCard ppsgenUsageAsk">
  <div class="ppsgenUsageAskBody">
    <strong><?php esc_html_e('Help us make CES better for sites like yours?', 'ppsgen'); ?></strong>
    <p class="ppsgenMuted"><?php esc_html_e('Share anonymous counts, such as how many contacts and automations you have and how your popups convert. Never names, emails or anything about your contacts. It changes nothing in the plugin, and you can switch it off any time in Settings › Usage data.', 'ppsgen'); ?></p>
  </div>
  <div class="ppsgenUsageAskActions">
    <button type="button" class="button button-primary ppsgenUsageChoice" data-value="yes"><?php esc_html_e('Yes, share anonymous counts', 'ppsgen'); ?></button>
    <button type="button" class="button ppsgenUsageChoice" data-value="no"><?php esc_html_e('No thanks', 'ppsgen'); ?></button>
  </div>
</div>
