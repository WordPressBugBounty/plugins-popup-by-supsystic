<?php
if (!defined('ABSPATH')) {
  exit();
}
$options = $this->options;
$objId = $options['obj_id'];
$popupSubmits = !empty($this->popupSubmits) ? $this->popupSubmits : [];
$trend = is_array($this->popupTrend ?? null) ? $this->popupTrend : [];
$trendGroup = !empty($this->popupTrendGroup) ? $this->popupTrendGroup : 'day';
$trendRange = !empty($this->popupTrendRange) ? (int) $this->popupTrendRange : 30;
$stats = [];
if (!empty($objId)) {
  $stats['total_views'] = (int) $this->getModel()->getOption('total_views', 'stats', $objId, 'popup');
  $stats['unique_views'] = (int) $this->getModel()->getOption('unique_views', 'stats', $objId, 'popup');
  $stats['bounces'] = (int) $this->getModel()->getOption('bounces', 'stats', $objId, 'popup');
  $stats['time_on_screen'] = $this->getModel()->getOption('time_on_screen', 'stats', $objId, 'popup');
}
$stats['total_views'] = !empty($stats['total_views']) ? $stats['total_views'] : 0;
$stats['unique_views'] = !empty($stats['unique_views']) ? $stats['unique_views'] : 0;
$stats['bounces'] = !empty($stats['bounces']) ? $stats['bounces'] : 0;
$stats['time_on_screen'] = !empty($stats['time_on_screen']) ? $stats['time_on_screen'] : 0;
$submitCount = 0;
$actionCount = 0;
foreach ($popupSubmits as $s) {
  if ($s['action_type'] === 'submit') {
    $submitCount++;
  } else {
    $actionCount++;
  }
}
$ctr = $stats['unique_views'] > 0 ? round((($submitCount + $actionCount) / $stats['unique_views']) * 100, 2) : 0;
$bounce_rate = $stats['total_views'] > 0 ? round(($stats['bounces'] / $stats['total_views']) * 100, 2) : 0;
$avg_time_on_screen = 0;
if (!empty($stats['time_on_screen']) && strpos($stats['time_on_screen'], '_') !== false) {
  [$sumTime, $viewCount] = explode('_', $stats['time_on_screen']);
  $avg_time_on_screen = $viewCount > 0 ? round((int) $sumTime / (int) $viewCount / 1000, 2) : 0;
}
$trendBaseUrl = admin_url('admin.php?page=ppsgen&tab=popup_edit&id=' . (int) $objId . '&obj_edit_active_tab=stats');
$trendMax = 0;
foreach ($trend as $point) {
  $trendMax = max($trendMax, (int) ($point['entries'] ?? 0), (int) ($point['actions'] ?? 0), (int) ($point['submits'] ?? 0), (int) ($point['bounces'] ?? 0));
}
?>

<?php if (!empty($stats)) { ?>
  <div class="ppsgenPopupStatsActions">
    <a class="ppsgenTableListBtn ppsgenPopupResetStatsBtn" data-obj="<?php echo esc_attr($objId); ?>">
      <span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Reset Stats', 'ppsgen'); ?>
    </a>
  </div>
  <div class="ppsgenPopupStatsWrapper">
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Total Views:', 'ppsgen'); ?> <span><?php echo esc_html($stats['total_views']); ?></span></div>
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Unique Views:', 'ppsgen'); ?> <span><?php echo esc_html($stats['unique_views']); ?></span></div>
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-button"></span> <?php esc_html_e('Actions:', 'ppsgen'); ?> <span><?php echo esc_html($actionCount); ?></span></div>
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Form Submits:', 'ppsgen'); ?> <span><?php echo esc_html($submitCount); ?></span></div>
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Bounces:', 'ppsgen'); ?> <span><?php echo esc_html($stats['bounces']); ?></span></div>
  </div>

  <div class="ppsgenPopupStatsWrapper">
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-feedback"></span> <?php esc_html_e('CTR:', 'ppsgen'); ?> <span><?php echo esc_html($ctr); ?>%</span></div>
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Bounce Rate:', 'ppsgen'); ?> <span><?php echo esc_html($bounce_rate); ?>%</span></div>
    <div class="ppsgenPopupStatsRow"><span class="dashicons dashicons-clock"></span> <?php esc_html_e('Avg Time on Screen:', 'ppsgen'); ?> <span><?php echo esc_html($avg_time_on_screen); ?>s</span></div>
  </div>
  <div class="ppsgenStatsTrendPanel">
    <div class="ppsgenStatsTrendToolbar">
      <div class="ppsgenStatsTrendGroup">
        <strong><?php esc_html_e('Group:', 'ppsgen'); ?></strong>
        <?php foreach (['day' => __('Day', 'ppsgen'), 'week' => __('Week', 'ppsgen'), 'month' => __('Month', 'ppsgen')] as $groupKey => $groupLabel) { ?>
          <a class="ppsgenStatsTrendBtn<?php echo $trendGroup === $groupKey ? ' ppsgenStatsTrendBtnActive' : ''; ?>" href="<?php echo esc_url(add_query_arg(['popup_stats_group' => $groupKey, 'popup_stats_range' => $trendRange], $trendBaseUrl)); ?>"><?php echo esc_html($groupLabel); ?></a>
        <?php } ?>
      </div>
      <div class="ppsgenStatsTrendGroup">
        <strong><?php esc_html_e('Range:', 'ppsgen'); ?></strong>
        <?php foreach ([7, 30, 90, 365] as $rangeOption) { ?>
          <a class="ppsgenStatsTrendBtn<?php echo $trendRange === $rangeOption ? ' ppsgenStatsTrendBtnActive' : ''; ?>" href="<?php echo esc_url(add_query_arg(['popup_stats_group' => $trendGroup, 'popup_stats_range' => $rangeOption], $trendBaseUrl)); ?>"><?php echo esc_html($rangeOption); ?></a>
        <?php } ?>
      </div>
    </div>
    <div class="ppsgenStatsTrendLegend">
      <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorPrimary"></i><?php esc_html_e('Entries', 'ppsgen'); ?></span>
      <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorSecondary"></i><?php esc_html_e('Actions', 'ppsgen'); ?></span>
      <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorTertiary"></i><?php esc_html_e('Submits', 'ppsgen'); ?></span>
      <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorDanger"></i><?php esc_html_e('Bounces', 'ppsgen'); ?></span>
    </div>
    <div class="ppsgenStatsTrendChart">
      <?php if (!empty($trend) && $trendMax > 0) { ?>
        <?php foreach ($trend as $point) {
          $entriesHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['entries'] ?? 0) / $trendMax) * 140)) : 4;
          $actionsHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['actions'] ?? 0) / $trendMax) * 140)) : 4;
          $submitsHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['submits'] ?? 0) / $trendMax) * 140)) : 4;
          $bouncesHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['bounces'] ?? 0) / $trendMax) * 140)) : 4;
          ?>
          <div class="ppsgenStatsTrendCol">
            <div class="ppsgenStatsTrendBars">
              <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarPrimary" style="height: <?php echo esc_attr($entriesHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Entries: %d', 'ppsgen'), (int) ($point['entries'] ?? 0))); ?>"></span>
              <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarSecondary" style="height: <?php echo esc_attr($actionsHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Actions: %d', 'ppsgen'), (int) ($point['actions'] ?? 0))); ?>"></span>
              <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarTertiary" style="height: <?php echo esc_attr($submitsHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Submits: %d', 'ppsgen'), (int) ($point['submits'] ?? 0))); ?>"></span>
              <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarDanger" style="height: <?php echo esc_attr($bouncesHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Bounces: %d', 'ppsgen'), (int) ($point['bounces'] ?? 0))); ?>"></span>
            </div>
            <div class="ppsgenStatsTrendLabel"><?php echo esc_html($point['label'] ?? ''); ?></div>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="ppsgenStatsTrendEmpty"><?php esc_html_e('Not enough popup activity yet to build a trend chart.', 'ppsgen'); ?></div>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<?php if (!empty($popupSubmits) && is_array($popupSubmits)) { ?>
  <div class="ppsgenPopupSubmitsWrapper">
    <?php foreach ($popupSubmits as $submit) { ?>
      <div class="ppsgenPopupSubmitsRow">
        <div class="ppsgenPopupSubmitsRowTitle">
          <div class="ppsgenPopupSubmitsRowTitleEl"><?php esc_html_e('ID:', 'ppsgen'); ?> <span><?php echo esc_html($submit['id']); ?></span></div>
          <?php
          $userId = isset($submit['wp_user_id']) ? (int) $submit['wp_user_id'] : 0;
          $editUserUrl = $userId ? get_edit_user_link($userId) : '';
          ?>
          <div class="ppsgenPopupSubmitsRowTitleEl">
              <?php esc_html_e('User ID:', 'ppsgen'); ?>
              <?php if ($editUserUrl) { ?>
                  <a target="_blank" href="<?php echo esc_url($editUserUrl); ?>">
                      <?php echo esc_html($userId); ?>
                  </a>
              <?php } else { ?>
                  <span><?php echo esc_html($userId); ?></span>
              <?php } ?>
          </div>
          <?php $submitGeo = $this->getFrame()->core('helper')->getGeoLiteLocationLabelByIp($submit['ip'] ?? ''); ?>
          <div class="ppsgenPopupSubmitsRowTitleEl"><?php esc_html_e('IP:', 'ppsgen'); ?> <span><?php echo esc_html($submit['ip']); ?></span><?php if (!empty($submitGeo)) { ?> <span>(<?php echo esc_html($submitGeo); ?>)</span><?php } ?></div>
          <div class="ppsgenPopupSubmitsRowTitleEl"><span><?php echo esc_html($submit['created_at']); ?></span></div>
          <div class="ppsgenPopupSubmitsRowTitleBtns">
            <a class="ppsgenTableListBtn ppsgenPopupSubmitsRowTitleDeleteBtn" data-obj="<?php echo esc_attr($submit['id']); ?>" title="<?php echo esc_attr__('Delete Entry', 'ppsgen'); ?>"><span class="dashicons dashicons-trash"></span></a>
          </div>
        </div>
        <div class="ppsgenPopupSubmitsRowData">
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('Action Type:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html($submit['action_type']); ?></span>
          </div>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('Action Meta:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html($submit['action_meta']); ?></span>
          </div>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('Unique:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html($submit['is_unique'] ? 'Yes' : 'No'); ?></span>
          </div>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('Bounce:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html($submit['is_bounce'] ? 'Yes' : 'No'); ?></span>
          </div>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('Time on Screen:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(round($submit['time_on_screen'] / 1000, 2)); ?>s</span>
          </div>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('User Device:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(substr($submit['user_agent'], 0, 100)); ?></span>
          </div>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('Url:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_url(substr($submit['url'], 0, 300)); ?></span>
          </div>
          <?php if (!empty($submit['referer'])) { ?>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('Referer:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(substr($submit['referer'], 0, 300)); ?></span>
          </div>
          <?php } ?>
          <?php if (!empty($submit['utm_source'])) { ?>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('UTM Source:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(substr($submit['utm_source'], 0, 100)); ?></span>
          </div>
          <?php } ?>
          <?php if (!empty($submit['utm_medium'])) { ?>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('UTM Medium:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(substr($submit['utm_medium'], 0, 100)); ?></span>
          </div>
          <?php } ?>
          <?php if (!empty($submit['utm_campaign'])) { ?>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('UTM Campaign:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(substr($submit['utm_campaign'], 0, 100)); ?></span>
          </div>
          <?php } ?>
          <?php if (!empty($submit['utm_term'])) { ?>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('UTM Term:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(substr($submit['utm_term'], 0, 100)); ?></span>
          </div>
          <?php } ?>
          <?php if (!empty($submit['utm_content'])) { ?>
          <div class="ppsgenPopupSubmitsRowDataField">
            <span class="ppsgenPopupSubmitsRowDataFieldKey"><?php esc_html_e('UTM Content:', 'ppsgen'); ?></span>
            <span class="ppsgenPopupSubmitsRowDataFieldValue"><?php echo esc_html(substr($submit['utm_content'], 0, 100)); ?></span>
          </div>
          <?php } ?>
        </div>
      </div>
    <?php } ?>
  </div>
<?php } else { ?>
  <div class="ppsgenPopupSubmitsWrapperWarning">
    <?php esc_html_e('This popup does not have any entries yet.', 'ppsgen'); ?>
  </div>
<?php } ?>
