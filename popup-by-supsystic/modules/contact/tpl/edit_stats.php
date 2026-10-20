<?php
if (!defined('ABSPATH')) {
  exit();
}
$stats = is_array($this->contactStats ?? null) ? $this->contactStats : [];
$formatText = static function ($value, string $fallback = '-') {
  $value = is_string($value) ? trim($value) : '';
  return $value !== '' ? $value : $fallback;
};
$formatMoney = static function ($value) {
  $amount = is_numeric($value) ? (float) $value : 0.0;
  if (function_exists('wc_price')) {
    return wp_strip_all_tags(wc_price($amount));
  }
  return number_format_i18n($amount, 2);
};
$incomingAttentionTotal = (int) ($stats['incoming_email_unhandled_total'] ?? 0);
$incomingAttentionTitle = $incomingAttentionTotal > 0
  ? sprintf(
    _n('%d new incoming email needs attention', '%d new incoming emails need attention', $incomingAttentionTotal, 'ppsgen'),
    $incomingAttentionTotal
  )
  : __('No new incoming emails need attention', 'ppsgen');
$lastActivityValueHtml = '<span class="ppsgenContactStatsMetricInline">'
  . (!empty($stats['has_unhandled_incoming_email'])
    ? '<span class="ppsgenContactIncomingIndicatorBadge is-active" title="' . esc_attr($incomingAttentionTitle) . '"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span></span>'
    : '')
  . '<span>' . esc_html($stats['last_activity_text'] ?? __('No activity yet', 'ppsgen')) . '</span>'
  . '</span>';
$lastIncomingValueHtml = '<span class="ppsgenContactStatsMetricInline">'
  . (!empty($stats['has_unhandled_incoming_email'])
    ? '<span class="ppsgenContactIncomingIndicatorBadge is-active" title="' . esc_attr($incomingAttentionTitle) . '"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span></span>'
    : '')
  . '<span>' . esc_html($formatText((string) ($stats['last_incoming_email_at'] ?? ''), __('No incoming emails yet', 'ppsgen'))) . '</span>'
  . '</span>';
$statsSections = [
  [
    'title' => __('Contact Health', 'ppsgen'),
    'icon' => 'dashicons-chart-line',
    'class' => 'is-health',
    'items' => [
      ['label' => __('Engagement Score', 'ppsgen'), 'value' => ((int) ($stats['percent'] ?? 0)) . '%', 'value_class' => 'is-primary'],
      ['label' => __('Lead Status', 'ppsgen'), 'value' => $stats['label'] ?? __('Inactive', 'ppsgen'), 'value_class' => 'is-success'],
      ['label' => __('Current State', 'ppsgen'), 'value' => $stats['activity_state'] ?? __('No Flow', 'ppsgen'), 'value_class' => 'is-neutral'],
      ['label' => __('Last Activity', 'ppsgen'), 'value_html' => $lastActivityValueHtml, 'value_class' => 'is-text'],
    ],
  ],
  [
    'title' => __('Email Engagement', 'ppsgen'),
    'icon' => 'dashicons-email-alt',
    'class' => 'is-summary',
    'items' => [
      ['label' => __('Total Events', 'ppsgen'), 'value' => (int) ($stats['event_total'] ?? 0), 'value_class' => 'is-primary'],
  ['label' => __('Engaged Campaigns', 'ppsgen'), 'value' => (int) ($stats['engaged_chains'] ?? 0), 'value_class' => 'is-neutral'],
      ['label' => __('Unique Opened Steps', 'ppsgen'), 'value' => (int) ($stats['open_unique_steps'] ?? 0), 'value_class' => 'is-warning'],
      ['label' => __('Unique Clicked Links', 'ppsgen'), 'value' => (int) ($stats['click_unique_links'] ?? 0), 'value_class' => 'is-success'],
    ],
  ],
  [
    'title' => __('Opens', 'ppsgen'),
    'icon' => 'dashicons-visibility',
    'class' => 'is-open',
    'items' => [
      ['label' => __('Total Opens', 'ppsgen'), 'value' => (int) ($stats['open_total'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Suspicious Opens', 'ppsgen'), 'value' => (int) ($stats['open_suspicious_total'] ?? 0), 'value_class' => 'is-warning'],
      ['label' => __('Live Opens', 'ppsgen'), 'value' => (int) ($stats['open_live_total'] ?? 0), 'value_class' => 'is-success'],
    ],
  ],
  [
    'title' => __('Clicks', 'ppsgen'),
    'icon' => 'dashicons-external',
    'class' => 'is-click',
    'items' => [
      ['label' => __('Total Clicks', 'ppsgen'), 'value' => (int) ($stats['click_total'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Suspicious Clicks', 'ppsgen'), 'value' => (int) ($stats['click_suspicious_total'] ?? 0), 'value_class' => 'is-warning'],
      ['label' => __('Live Clicks', 'ppsgen'), 'value' => (int) ($stats['click_live_total'] ?? 0), 'value_class' => 'is-success'],
    ],
  ],
  [
    'title' => __('Conversions', 'ppsgen'),
    'icon' => 'dashicons-chart-line',
    'class' => 'is-conversion',
    'items' => [
      ['label' => __('Total Conversions', 'ppsgen'), 'value' => (int) ($stats['conversion_total'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Paid Conversions', 'ppsgen'), 'value' => (int) ($stats['conversion_paid_total'] ?? 0), 'value_class' => 'is-success'],
      ['label' => __('Total Value', 'ppsgen'), 'value' => (string) ($stats['conversion_paid_value_label'] ?? '0'), 'value_class' => 'is-success'],
      ['label' => __('Last Conversion', 'ppsgen'), 'value' => $formatText((string) ($stats['conversion_last_text'] ?? ''), __('No conversions yet', 'ppsgen')), 'value_class' => 'is-text'],
      ['label' => __('Last Product', 'ppsgen'), 'value' => $formatText((string) ($stats['conversion_last_product_name'] ?? '')), 'value_class' => 'is-text'],
    ],
  ],
  [
    'title' => __('Flow Status', 'ppsgen'),
    'icon' => 'dashicons-randomize',
    'class' => 'is-flow',
    'items' => [
      ['label' => __('Total Flows', 'ppsgen'), 'value' => (int) ($stats['flows_total'] ?? 0), 'value_class' => 'is-neutral'],
      ['label' => __('Active Flows', 'ppsgen'), 'value' => (int) ($stats['active_flows'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Waiting Right Now', 'ppsgen'), 'value' => (int) ($stats['waiting_flows'] ?? 0), 'value_class' => 'is-warning'],
      ['label' => __('Processing Right Now', 'ppsgen'), 'value' => (int) ($stats['processing_flows'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Finished Flows', 'ppsgen'), 'value' => (int) ($stats['finished_flows'] ?? 0), 'value_class' => 'is-success'],
      ['label' => __('Exited Flows', 'ppsgen'), 'value' => (int) ($stats['exited_flows'] ?? 0), 'value_class' => 'is-danger'],
      ['label' => __('Unsubscribed Flows', 'ppsgen'), 'value' => (int) ($stats['unsubscribed_flows'] ?? 0), 'value_class' => 'is-muted'],
    ],
  ],
  [
    'title' => __('Flow Logs', 'ppsgen'),
    'icon' => 'dashicons-list-view',
    'class' => 'is-log',
    'items' => [
      ['label' => __('Entries', 'ppsgen'), 'value' => (int) ($stats['entries_total'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Passed Steps', 'ppsgen'), 'value' => (int) ($stats['steps_success'] ?? 0), 'value_class' => 'is-success'],
      ['label' => __('Finished Steps', 'ppsgen'), 'value' => (int) ($stats['steps_finished'] ?? 0), 'value_class' => 'is-success'],
      ['label' => __('Skipped Steps', 'ppsgen'), 'value' => (int) ($stats['steps_skipped'] ?? 0), 'value_class' => 'is-warning'],
      ['label' => __('Condition False', 'ppsgen'), 'value' => (int) ($stats['condition_false'] ?? 0), 'value_class' => 'is-muted'],
      ['label' => __('Errors', 'ppsgen'), 'value' => (int) ($stats['errors_total'] ?? 0), 'value_class' => 'is-danger'],
    ],
  ],
  [
    'title' => __('Forms', 'ppsgen'),
    'icon' => 'dashicons-feedback',
    'class' => 'is-form',
    'items' => [
      ['label' => __('Total Submits', 'ppsgen'), 'value' => (int) ($stats['total_submits'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Forms Used', 'ppsgen'), 'value' => (int) ($stats['forms_used'] ?? 0), 'value_class' => 'is-neutral'],
      ['label' => __('Last Submit', 'ppsgen'), 'value' => $formatText((string) ($stats['last_submit_at'] ?? '')), 'value_class' => 'is-text'],
    ],
  ],
];
if (!empty($stats['incoming_email_total']) || !empty($stats['incoming_email_unhandled_total']) || !empty($stats['last_incoming_email_at'])) {
  $statsSections[] = [
    'title' => __('Mailbox Activity', 'ppsgen'),
    'icon' => 'dashicons-email',
    'class' => 'is-mailbox',
    'items' => [
      ['label' => __('Total Incoming Emails', 'ppsgen'), 'value' => (int) ($stats['incoming_email_total'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Needs Attention', 'ppsgen'), 'value' => (int) ($stats['incoming_email_unhandled_total'] ?? 0), 'value_class' => !empty($stats['incoming_email_unhandled_total']) ? 'is-warning' : 'is-muted'],
      ['label' => __('Last Incoming Email', 'ppsgen'), 'value_html' => $lastIncomingValueHtml, 'value_class' => 'is-text'],
    ],
  ];
}
if (!empty($stats['woo_orders_total']) || (float) ($stats['woo_total_spent'] ?? 0) > 0) {
  $statsSections[] = [
    'title' => __('WooCommerce', 'ppsgen'),
    'icon' => 'dashicons-cart',
    'class' => 'is-woo',
    'items' => [
      ['label' => __('Orders', 'ppsgen'), 'value' => (int) ($stats['woo_orders_total'] ?? 0), 'value_class' => 'is-primary'],
      ['label' => __('Total Spend', 'ppsgen'), 'value' => $formatMoney($stats['woo_total_spent'] ?? 0), 'value_class' => 'is-success'],
      ['label' => __('Average Order Value', 'ppsgen'), 'value' => $formatMoney($stats['woo_aov'] ?? 0), 'value_class' => 'is-neutral'],
    ],
  ];
}
?>

<div class="ppsgenContactStatsCards">
  <?php foreach ($statsSections as $section) { ?>
    <div class="ppsgenContactStatsCard <?php echo esc_attr($section['class']); ?>">
      <div class="ppsgenContactStatsCardHeader">
        <div class="ppsgenContactStatsCardTitleWrap">
          <span class="ppsgenContactStatsCardIcon dashicons <?php echo esc_attr($section['icon']); ?>"></span>
          <div class="ppsgenContactStatsCardTitle"><?php echo esc_html($section['title']); ?></div>
        </div>
      </div>
      <div class="ppsgenContactStatsCardBody">
        <?php foreach ($section['items'] as $item) { ?>
          <div class="ppsgenContactStatsMetric">
            <div class="ppsgenContactStatsMetricLabel"><?php echo esc_html($item['label']); ?></div>
            <div class="ppsgenContactStatsMetricValue <?php echo esc_attr($item['value_class']); ?>">
              <?php
              if (!empty($item['value_html'])) {
                echo wp_kses_post($item['value_html']);
              } else {
                echo esc_html((string) $item['value']);
              }
              ?>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
</div>
