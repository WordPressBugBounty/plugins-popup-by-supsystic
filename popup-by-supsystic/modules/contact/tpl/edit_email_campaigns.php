<?php
if (!defined('ABSPATH')) {
  exit();
}
global $wpdb;
$rows = is_array($this->contactCampaignRows ?? null) ? $this->contactCampaignRows : [];
$contactId = (int) ($this->contactCampaignObjId ?? 0);
$flowStatus = is_array($this->contactCampaignFlowStatus ?? null) ? $this->contactCampaignFlowStatus : [];
$stepIds = !empty($rows) ? array_values(array_unique(array_map('intval', array_column($rows, 'current_step_id')))) : [];
$stepNames = [];
if (!empty($stepIds)) {
  $stepPlaceholders = implode(',', array_fill(0, count($stepIds), '%d'));
  $stepRows = $wpdb->get_results(
    $wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id IN ($stepPlaceholders)", ...$stepIds),
    ARRAY_A,
  );
  if (!empty($stepRows)) {
    $stepNames = array_column($stepRows, 'name', 'id');
  }
}
?>
<div class="ppsgenContactCampaignsPage">
  <?php if (empty($rows)) { ?>
    <div class="ppsgenContactCampaignsEmpty ppsgenFormSubmitsWrapperWarning">
      <?php esc_html_e('This contact is not connected to any email campaigns yet.', 'ppsgen'); ?>
    </div>
  <?php } ?>

  <?php foreach ($rows as $row) { ?>
    <?php
    $flowId = (int) ($row['id'] ?? 0);
    $chainId = (int) ($row['chain_id'] ?? 0);
    $stepId = (int) ($row['current_step_id'] ?? 0);
    $chain = is_array($row['chain'] ?? null) ? $row['chain'] : [];
    $campaignStats = is_array($row['contact_chain_stats'] ?? null) ? $row['contact_chain_stats'] : [];
    $chainName = (string) ($chain['name'] ?? ('Campaign #' . $chainId));
    $stepName = !empty($stepNames[$stepId]) ? $stepNames[$stepId] : '';
    $flowStatusLabel = (string) ($flowStatus[$row['status']] ?? ($row['status'] ?? ''));
    $cronStatusLabel = !empty($row['cron_status']) ? (string) $row['cron_status'] : __('-', 'ppsgen');
    $nextRunLabel = !empty($row['next_run_at']) ? (string) $row['next_run_at'] : __('Not scheduled', 'ppsgen');
    $lastActivityLabel = !empty($campaignStats['last_activity_text']) ? (string) $campaignStats['last_activity_text'] : __('No activity yet', 'ppsgen');
    $engagementScore = (int) ($campaignStats['percent'] ?? 0);
    $engagementLabel = !empty($campaignStats['label']) ? (string) $campaignStats['label'] : __('Inactive', 'ppsgen');
    $logsUrl = add_query_arg([
      'page' => 'ppsgen',
      'tab' => 'eca_flows_logs',
      'filter_contact_id' => $contactId,
      'filter_chain_id' => $chainId,
    ], admin_url('admin.php'));
    $eventsUrl = add_query_arg([
      'page' => 'ppsgen',
      'tab' => 'eca_step_events',
      'filter_contact_id' => $contactId,
      'filter_chain_id' => $chainId,
    ], admin_url('admin.php'));
    $chainUrl = add_query_arg([
      'page' => 'ppsgen',
      'tab' => 'eca_chain_edit',
      'id' => $chainId,
    ], admin_url('admin.php'));
    ?>
    <div class="ppsgenContactCampaignCard">
      <div class="ppsgenContactCampaignCardHeader">
        <div class="ppsgenContactCampaignCardHeaderMain">
          <div class="ppsgenContactCampaignCardEyebrow"><?php esc_html_e('Email Campaign', 'ppsgen'); ?></div>
          <h3 class="ppsgenContactCampaignCardTitle">
            <a href="<?php echo esc_url($chainUrl); ?>"><?php echo esc_html($chainName); ?></a>
          </h3>
          <div class="ppsgenContactCampaignCardMeta">
            <span class="ppsgenContactCampaignPill is-id"><?php echo esc_html(sprintf(__('Flow #%d', 'ppsgen'), $flowId)); ?></span>
            <span class="ppsgenContactCampaignPill is-status"><?php esc_html_e('Flow', 'ppsgen'); ?>: <?php echo esc_html($flowStatusLabel); ?></span>
            <span class="ppsgenContactCampaignPill"><?php esc_html_e('Cron', 'ppsgen'); ?>: <?php echo esc_html($cronStatusLabel); ?></span>
            <span class="ppsgenContactCampaignPill"><?php esc_html_e('Active', 'ppsgen'); ?>: <?php echo !empty($row['active']) ? esc_html__('Yes', 'ppsgen') : esc_html__('No', 'ppsgen'); ?></span>
            <?php if (!empty($stepId)) { ?>
              <span class="ppsgenContactCampaignPill"><?php esc_html_e('Current Step', 'ppsgen'); ?>: <?php echo !empty($stepName) ? esc_html($stepName) : ''; ?></span>
            <?php } ?>
            <span class="ppsgenContactCampaignPill"><?php esc_html_e('Next Run', 'ppsgen'); ?>: <?php echo esc_html($nextRunLabel); ?></span>
          </div>
        </div>
        <div class="ppsgenContactCampaignCardActions">
          <a class="ppsgenContactCampaignAction" href="<?php echo esc_url($chainUrl); ?>"><?php esc_html_e('Open Campaign', 'ppsgen'); ?></a>
          <a class="ppsgenContactCampaignAction" href="<?php echo esc_url($logsUrl); ?>"><?php esc_html_e('Flow Logs', 'ppsgen'); ?></a>
          <a class="ppsgenContactCampaignAction" href="<?php echo esc_url($eventsUrl); ?>"><?php esc_html_e('Step Events', 'ppsgen'); ?></a>
          <?php if (($row['status'] ?? '') === 'active') { ?>
            <button type="button" class="ppsgenContactCampaignAction ppsgenContactCampaignActionBtn ppsgenContactCampaignFlowAction" data-action="unsubscribeChainContactFlow" data-flow-id="<?php echo esc_attr($flowId); ?>" data-confirm="<?php esc_attr_e('Unsubscribe this contact from the current flow?', 'ppsgen'); ?>"><?php esc_html_e('Unsubscribe', 'ppsgen'); ?></button>
            <button type="button" class="ppsgenContactCampaignAction ppsgenContactCampaignActionBtn ppsgenContactCampaignActionDanger ppsgenContactCampaignFlowAction" data-action="exitChainContactFlow" data-flow-id="<?php echo esc_attr($flowId); ?>" data-confirm="<?php esc_attr_e('Exit this contact from the current flow?', 'ppsgen'); ?>"><?php esc_html_e('Exit Flow', 'ppsgen'); ?></button>
          <?php } ?>
        </div>
      </div>

      <div class="ppsgenContactCampaignPanels">
        <div class="ppsgenContactCampaignPanel is-health">
          <div class="ppsgenContactCampaignPanelHeader">
            <div class="ppsgenContactCampaignPanelTitleWrap">
              <span class="ppsgenContactCampaignPanelIcon dashicons dashicons-chart-line"></span>
              <div class="ppsgenContactCampaignPanelTitle"><?php esc_html_e('Campaign Pulse', 'ppsgen'); ?></div>
            </div>
          </div>
          <div class="ppsgenContactCampaignPanelBody">
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Engagement Score', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-primary"><?php echo esc_html($engagementScore . '%'); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Lead Status', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-success"><?php echo esc_html($engagementLabel); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Last Activity', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-text"><?php echo esc_html($lastActivityLabel); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Total Events', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-neutral"><?php echo esc_html((int) ($campaignStats['event_total'] ?? 0)); ?></div>
            </div>
          </div>
        </div>

        <div class="ppsgenContactCampaignPanel is-flow">
          <div class="ppsgenContactCampaignPanelHeader">
            <div class="ppsgenContactCampaignPanelTitleWrap">
              <span class="ppsgenContactCampaignPanelIcon dashicons dashicons-randomize"></span>
              <div class="ppsgenContactCampaignPanelTitle"><?php esc_html_e('Flow Snapshot', 'ppsgen'); ?></div>
            </div>
          </div>
          <div class="ppsgenContactCampaignPanelBody">
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Flow Status', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-primary"><?php echo esc_html($flowStatusLabel); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Cron Status', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-neutral"><?php echo esc_html($cronStatusLabel); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Current Step', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-text"><?php echo !empty($stepName) ? esc_html($stepName) : esc_html__('No step selected', 'ppsgen'); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Next Run', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-text"><?php echo esc_html($nextRunLabel); ?></div>
            </div>
          </div>
        </div>

        <div class="ppsgenContactCampaignPanel is-open">
          <div class="ppsgenContactCampaignPanelHeader">
            <div class="ppsgenContactCampaignPanelTitleWrap">
              <span class="ppsgenContactCampaignPanelIcon dashicons dashicons-visibility"></span>
              <div class="ppsgenContactCampaignPanelTitle"><?php esc_html_e('Opens', 'ppsgen'); ?></div>
            </div>
          </div>
          <div class="ppsgenContactCampaignPanelBody">
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Open Signals', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignSignalPills">
                <span class="ppsgenContactCampaignSignalPill is-total" title="<?php echo esc_attr(sprintf(__('Total Opens: %d', 'ppsgen'), (int) ($campaignStats['open_total'] ?? 0))); ?>"><?php echo esc_html((int) ($campaignStats['open_total'] ?? 0)); ?></span>
                <span class="ppsgenContactCampaignSignalPill is-suspicious" title="<?php echo esc_attr(sprintf(__('Suspicious Opens: %d', 'ppsgen'), (int) ($campaignStats['open_suspicious_total'] ?? 0))); ?>"><?php echo esc_html((int) ($campaignStats['open_suspicious_total'] ?? 0)); ?></span>
                <span class="ppsgenContactCampaignSignalPill is-live" title="<?php echo esc_attr(sprintf(__('Live Opens: %d', 'ppsgen'), (int) ($campaignStats['open_live_total'] ?? 0))); ?>"><?php echo esc_html((int) ($campaignStats['open_live_total'] ?? 0)); ?></span>
              </div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Unique Opened Steps', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-warning"><?php echo esc_html((int) ($campaignStats['open_unique_steps'] ?? 0)); ?></div>
            </div>
          </div>
        </div>

        <div class="ppsgenContactCampaignPanel is-click">
          <div class="ppsgenContactCampaignPanelHeader">
            <div class="ppsgenContactCampaignPanelTitleWrap">
              <span class="ppsgenContactCampaignPanelIcon dashicons dashicons-external"></span>
              <div class="ppsgenContactCampaignPanelTitle"><?php esc_html_e('Clicks', 'ppsgen'); ?></div>
            </div>
          </div>
          <div class="ppsgenContactCampaignPanelBody">
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Click Signals', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignSignalPills">
                <span class="ppsgenContactCampaignSignalPill is-total" title="<?php echo esc_attr(sprintf(__('Total Clicks: %d', 'ppsgen'), (int) ($campaignStats['click_total'] ?? 0))); ?>"><?php echo esc_html((int) ($campaignStats['click_total'] ?? 0)); ?></span>
                <span class="ppsgenContactCampaignSignalPill is-suspicious" title="<?php echo esc_attr(sprintf(__('Suspicious Clicks: %d', 'ppsgen'), (int) ($campaignStats['click_suspicious_total'] ?? 0))); ?>"><?php echo esc_html((int) ($campaignStats['click_suspicious_total'] ?? 0)); ?></span>
                <span class="ppsgenContactCampaignSignalPill is-live" title="<?php echo esc_attr(sprintf(__('Live Clicks: %d', 'ppsgen'), (int) ($campaignStats['click_live_total'] ?? 0))); ?>"><?php echo esc_html((int) ($campaignStats['click_live_total'] ?? 0)); ?></span>
              </div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Unique Clicked Links', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-success"><?php echo esc_html((int) ($campaignStats['click_unique_links'] ?? 0)); ?></div>
            </div>
          </div>
        </div>

        <div class="ppsgenContactCampaignPanel is-log">
          <div class="ppsgenContactCampaignPanelHeader">
            <div class="ppsgenContactCampaignPanelTitleWrap">
              <span class="ppsgenContactCampaignPanelIcon dashicons dashicons-list-view"></span>
              <div class="ppsgenContactCampaignPanelTitle"><?php esc_html_e('Step Progress', 'ppsgen'); ?></div>
            </div>
          </div>
          <div class="ppsgenContactCampaignPanelBody">
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Entries', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-primary"><?php echo esc_html((int) ($campaignStats['entries_total'] ?? 0)); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Passed Steps', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-success"><?php echo esc_html((int) ($campaignStats['steps_success'] ?? 0)); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Finished Steps', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-success"><?php echo esc_html((int) ($campaignStats['steps_finished'] ?? 0)); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Skipped Steps', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-warning"><?php echo esc_html((int) ($campaignStats['steps_skipped'] ?? 0)); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Condition False', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-neutral"><?php echo esc_html((int) ($campaignStats['condition_false'] ?? 0)); ?></div>
            </div>
            <div class="ppsgenContactCampaignMetric">
              <div class="ppsgenContactCampaignMetricLabel"><?php esc_html_e('Errors', 'ppsgen'); ?></div>
              <div class="ppsgenContactCampaignMetricValue is-danger"><?php echo esc_html((int) ($campaignStats['errors_total'] ?? 0)); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php } ?>
</div>
