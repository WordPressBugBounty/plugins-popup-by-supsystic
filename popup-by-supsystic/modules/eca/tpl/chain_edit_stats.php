<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$chain_id = !empty($options['obj_id']) ? $options['obj_id'] : 0;
$chainStats = !empty($this->chainStats) && is_array($this->chainStats) ? $this->chainStats : [];
$trend = is_array($this->chainTrend ?? null) ? $this->chainTrend : [];
$trendGroup = !empty($this->chainTrendGroup) ? $this->chainTrendGroup : 'day';
$trendRange = !empty($this->chainTrendRange) ? (int) $this->chainTrendRange : 30;
if (empty($chain_id)) {
  return esc_html__('Cant find Campaign with specific ID', 'ppsgen');
}
$totalFlows = (int) ($chainStats['total'] ?? 0);
$activeFlows = (int) ($chainStats['processing'] ?? 0);
$successFlows = (int) ($chainStats['finished'] ?? 0);
$exitedFlows = (int) ($chainStats['exited'] ?? 0);
$unsubscribedFlows = (int) ($chainStats['unsubscribed'] ?? 0);
$totalLetters = (int) ($chainStats['total_letters'] ?? 0);
$eventTotal = (int) ($chainStats['total_events'] ?? 0);
$eventOpen = (int) ($chainStats['event_open'] ?? 0);
$eventClick = (int) ($chainStats['event_click'] ?? 0);
$suspiciousEventOpen = (int) ($chainStats['suspicious_event_open'] ?? 0);
$liveEventOpen = (int) ($chainStats['live_event_open'] ?? 0);
$suspiciousEventClick = (int) ($chainStats['suspicious_event_click'] ?? 0);
$liveEventClick = (int) ($chainStats['live_event_click'] ?? 0);
$conversionPaidTotal = (int) ($chainStats['conversion_paid_total'] ?? 0);
$conversionPaidContacts = (int) ($chainStats['conversion_paid_contacts'] ?? 0);
$conversionPaidEmails = (int) ($chainStats['conversion_paid_emails'] ?? 0);
$conversionPaidValue = (string) ($chainStats['conversion_paid_value_label'] ?? '0');
$statsSections = [
  [
    'title' => __('Flow Status', 'ppsgen'),
    'icon' => 'dashicons-randomize',
    'class' => 'is-flow',
    'items' => [
      ['label' => __('Total', 'ppsgen'), 'value' => $totalFlows, 'value_class' => 'is-neutral'],
      ['label' => __('Processing', 'ppsgen'), 'value' => $activeFlows, 'value_class' => 'is-primary'],
      ['label' => __('Finished', 'ppsgen'), 'value' => $successFlows, 'value_class' => 'is-success'],
      ['label' => __('Exited', 'ppsgen'), 'value' => $exitedFlows, 'value_class' => 'is-danger'],
      ['label' => __('Unsubscribed', 'ppsgen'), 'value' => $unsubscribedFlows, 'value_class' => 'is-muted'],
    ],
  ],
  [
    'title' => __('Delivery', 'ppsgen'),
    'icon' => 'dashicons-email-alt',
    'class' => 'is-delivery',
    'items' => [
      ['label' => __('Total Letters', 'ppsgen'), 'value' => $totalLetters, 'value_class' => 'is-primary'],
      ['label' => __('Total Events', 'ppsgen'), 'value' => $eventTotal, 'value_class' => 'is-neutral'],
    ],
  ],
  [
    'title' => __('Opens', 'ppsgen'),
    'icon' => 'dashicons-visibility',
    'class' => 'is-open',
    'items' => [
      ['label' => __('Total Opens', 'ppsgen'), 'value' => $eventOpen, 'value_class' => 'is-primary'],
      ['label' => __('Suspicious Opens', 'ppsgen'), 'value' => $suspiciousEventOpen, 'value_class' => 'is-warning'],
      ['label' => __('Live Opens', 'ppsgen'), 'value' => $liveEventOpen, 'value_class' => 'is-success'],
    ],
  ],
  [
    'title' => __('Clicks', 'ppsgen'),
    'icon' => 'dashicons-external',
    'class' => 'is-click',
    'items' => [
      ['label' => __('Total Clicks', 'ppsgen'), 'value' => $eventClick, 'value_class' => 'is-primary'],
      ['label' => __('Suspicious Clicks', 'ppsgen'), 'value' => $suspiciousEventClick, 'value_class' => 'is-warning'],
      ['label' => __('Live Clicks', 'ppsgen'), 'value' => $liveEventClick, 'value_class' => 'is-success'],
    ],
  ],
  [
    'title' => __('Conversions', 'ppsgen'),
    'icon' => 'dashicons-chart-line',
    'class' => 'is-conversion',
    'items' => [
      ['label' => __('Paid Conversions', 'ppsgen'), 'value' => $conversionPaidTotal, 'value_class' => 'is-success'],
      ['label' => __('Unique Contacts', 'ppsgen'), 'value' => $conversionPaidContacts, 'value_class' => 'is-primary'],
      ['label' => __('Unique Emails', 'ppsgen'), 'value' => $conversionPaidEmails, 'value_class' => 'is-primary'],
      ['label' => __('Total Value', 'ppsgen'), 'value' => $conversionPaidValue, 'value_class' => 'is-success'],
    ],
  ],
];
$trendBaseUrl = admin_url('admin.php?page=ppsgen&tab=eca_chain_edit&id=' . (int) $chain_id . '&chain_edit_active_tab=stats');
$trendMax = 0;
foreach ($trend as $point) {
  $trendMax = max($trendMax, (int) ($point['entries'] ?? 0), (int) ($point['emails_sent'] ?? 0), (int) ($point['opens'] ?? 0), (int) ($point['clicks'] ?? 0));
}
?>

<div class="ppsgenChainStatsActions">
  <button type="button" class="ppsgenTableListBtn ppsgenChainStatsResetBtn" data-action="resetChainLogs" data-chain-id="<?php echo esc_attr($chain_id); ?>">
        <span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Reset Campaign Logs', 'ppsgen'); ?>
  </button>
  <button type="button" class="ppsgenTableListBtn ppsgenChainStatsResetBtn" data-action="resetChainEvents" data-chain-id="<?php echo esc_attr($chain_id); ?>">
        <span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Reset Campaign Events', 'ppsgen'); ?>
  </button>
</div>

<div class="ppsgenChainStatsGrid">
  <?php foreach ($statsSections as $section) { ?>
    <div class="ppsgenChainStatsCard <?php echo esc_attr($section['class']); ?>">
      <div class="ppsgenChainStatsCardHeader">
        <div class="ppsgenChainStatsCardTitleWrap">
          <span class="ppsgenChainStatsCardIcon dashicons <?php echo esc_attr($section['icon']); ?>"></span>
          <div class="ppsgenChainStatsCardTitle"><?php echo esc_html($section['title']); ?></div>
        </div>
      </div>
      <div class="ppsgenChainStatsCardBody">
        <?php foreach ($section['items'] as $item) { ?>
          <div class="ppsgenChainStatsMetric">
            <div class="ppsgenChainStatsMetricLabel"><?php echo esc_html($item['label']); ?></div>
            <div class="ppsgenChainStatsMetricValue <?php echo esc_attr($item['value_class']); ?>"><?php echo esc_html((string) $item['value']); ?></div>
          </div>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
</div>

<div class="ppsgenStatsTrendPanel">
  <div class="ppsgenStatsTrendToolbar">
    <div class="ppsgenStatsTrendGroup">
      <strong><?php esc_html_e('Group:', 'ppsgen'); ?></strong>
      <?php foreach (['day' => __('Day', 'ppsgen'), 'week' => __('Week', 'ppsgen'), 'month' => __('Month', 'ppsgen'), 'year' => __('Year', 'ppsgen')] as $groupKey => $groupLabel) { ?>
        <a class="ppsgenStatsTrendBtn<?php echo $trendGroup === $groupKey ? ' ppsgenStatsTrendBtnActive' : ''; ?>" href="<?php echo esc_url(add_query_arg(['chain_stats_group' => $groupKey, 'chain_stats_range' => $trendRange], $trendBaseUrl)); ?>"><?php echo esc_html($groupLabel); ?></a>
      <?php } ?>
    </div>
    <div class="ppsgenStatsTrendGroup">
      <strong><?php esc_html_e('Range:', 'ppsgen'); ?></strong>
      <?php foreach ([7, 30, 365] as $rangeOption) { ?>
        <a class="ppsgenStatsTrendBtn<?php echo $trendRange === $rangeOption ? ' ppsgenStatsTrendBtnActive' : ''; ?>" href="<?php echo esc_url(add_query_arg(['chain_stats_group' => $trendGroup, 'chain_stats_range' => $rangeOption], $trendBaseUrl)); ?>"><?php echo esc_html($rangeOption); ?></a>
      <?php } ?>
    </div>
  </div>
  <div class="ppsgenStatsTrendLegend">
    <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorPrimary"></i><?php esc_html_e('New Entries', 'ppsgen'); ?></span>
    <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorSecondary"></i><?php esc_html_e('Emails Sent', 'ppsgen'); ?></span>
    <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorTertiary"></i><?php esc_html_e('Opens', 'ppsgen'); ?></span>
    <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorDanger"></i><?php esc_html_e('Clicks', 'ppsgen'); ?></span>
  </div>
  <div class="ppsgenStatsTrendChart">
    <?php if (!empty($trend) && $trendMax > 0) { ?>
      <?php foreach ($trend as $point) {
        $entriesHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['entries'] ?? 0) / $trendMax) * 140)) : 4;
        $sentHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['emails_sent'] ?? 0) / $trendMax) * 140)) : 4;
        $openHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['opens'] ?? 0) / $trendMax) * 140)) : 4;
        $clickHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['clicks'] ?? 0) / $trendMax) * 140)) : 4;
        ?>
        <div class="ppsgenStatsTrendCol">
          <div class="ppsgenStatsTrendBars">
            <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarPrimary" style="height: <?php echo esc_attr($entriesHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('New Entries: %d', 'ppsgen'), (int) ($point['entries'] ?? 0))); ?>"></span>
            <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarSecondary" style="height: <?php echo esc_attr($sentHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Emails Sent: %d', 'ppsgen'), (int) ($point['emails_sent'] ?? 0))); ?>"></span>
            <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarTertiary" style="height: <?php echo esc_attr($openHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Opens: %d', 'ppsgen'), (int) ($point['opens'] ?? 0))); ?>"></span>
            <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarDanger" style="height: <?php echo esc_attr($clickHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Clicks: %d', 'ppsgen'), (int) ($point['clicks'] ?? 0))); ?>"></span>
          </div>
          <div class="ppsgenStatsTrendLabel"><?php echo esc_html($point['label'] ?? ''); ?></div>
        </div>
      <?php } ?>
    <?php } else { ?>
      <div class="ppsgenStatsTrendEmpty"><?php esc_html_e('Not enough campaign activity yet to build a trend chart.', 'ppsgen'); ?></div>
    <?php } ?>
  </div>
</div>
