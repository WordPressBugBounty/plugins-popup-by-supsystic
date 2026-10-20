<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$objId = $options['obj_id'];
$activeTab = !empty($this->activeTab) ? $this->activeTab : 'steps';
$prefix = 'data[chain]';
$component->setNamePrefix($prefix);
$serverTime = current_time('mysql');
$cronStats = [];
$mailConfigStatsMap = [];
$chainSettings = !empty($options['settings']) && is_array($options['settings']) ? $options['settings'] : [];
if (!empty($objId)) {
  global $wpdb;
  $getNextCronTime = static function (array $hooks): string {
    $timestamps = [];
    foreach ($hooks as $hook) {
      $timestamp = wp_next_scheduled($hook);
      if (!empty($timestamp)) {
        $timestamps[] = (int) $timestamp;
      }
    }
    if (empty($timestamps)) {
      return '-';
    }
    $timestamp = min($timestamps);
    return wp_date('Y-m-d H:i:s', $timestamp, wp_timezone());
  };
  $flowCron = $getNextCronTime([
    'ppsgen_cron_eca_runEcaFlowCron',
    'ppsgen_cron_ecaExt_runEcaFlowCron',
  ]);
  $emailCron = $getNextCronTime([
    'ppsgen_cron_eca_runEcaSendEmailCron',
    'ppsgen_cron_ecaExt_runEcaSendEmailCron',
  ]);
  $autoBounceCron = $getNextCronTime([
    'ppsgen_cron_eca_runEcaAutoBounceCron',
    'ppsgen_cron_ecaExt_runEcaAutoBounceCron',
  ]);
  $emailsInQueue = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_mail_queue");
  $mailConfigId = !empty($chainSettings['mail_config_id']) ? (int) $chainSettings['mail_config_id'] : 0;
  $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
  $mailConfig = $dashboardModel->getResolvedMailConfigRemaining($mailConfigId);
  $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
  $dailyLimit = max(0, (int) ($mailSettings['mail_daily_limit'] ?? 300));
  $mailRemaining = max(0, min($dailyLimit, (int) ($mailSettings['mail_remaining'] ?? $dailyLimit)));
  $cronStats = [
    'server_time' => $serverTime,
    'flow_cron' => $flowCron,
    'email_cron' => $emailCron,
    'auto_bounce_cron' => $autoBounceCron,
    'emails_queue' => $emailsInQueue,
    'mail_remaining' => $mailRemaining,
    'mail_limit' => $dailyLimit,
    'mail_config_name' => (string) ($mailConfig['name'] ?? ''),
  ];
  foreach ($dashboardModel->getMailConfigs() as $configRow) {
    $resolvedConfig = $dashboardModel->getResolvedMailConfigRemaining((int) ($configRow['id'] ?? 0));
    $resolvedSettings = is_array($resolvedConfig['settings'] ?? null) ? $resolvedConfig['settings'] : [];
    $resolvedLimit = max(0, (int) ($resolvedSettings['mail_daily_limit'] ?? 300));
    $resolvedRemaining = max(0, min($resolvedLimit, (int) ($resolvedSettings['mail_remaining'] ?? $resolvedLimit)));
    $mailConfigStatsMap[(int) ($configRow['id'] ?? 0)] = [
      'name' => (string) ($resolvedConfig['name'] ?? ''),
      'remaining' => $resolvedRemaining,
      'limit' => $resolvedLimit,
    ];
  }
  $defaultResolvedConfig = $dashboardModel->getResolvedMailConfigRemaining(0);
  $defaultResolvedSettings = is_array($defaultResolvedConfig['settings'] ?? null) ? $defaultResolvedConfig['settings'] : [];
  $defaultResolvedLimit = max(0, (int) ($defaultResolvedSettings['mail_daily_limit'] ?? 300));
  $defaultResolvedRemaining = max(0, min($defaultResolvedLimit, (int) ($defaultResolvedSettings['mail_remaining'] ?? $defaultResolvedLimit)));
  $mailConfigStatsMap[0] = [
    'name' => (string) ($defaultResolvedConfig['name'] ?? __('Default', 'ppsgen')),
    'remaining' => $defaultResolvedRemaining,
    'limit' => $defaultResolvedLimit,
  ];
}
?>
<div class="ppsgenModulePageTitleWrapper">
  <div class="ppsgenChainEditHeaderMain">
    <h2 class="ppsgenModulePageTitle">
      <?php if ($objId) { ?>
      <span class="dashicons <?php echo !empty($this->isNewsletter) ? 'dashicons-email-alt' : 'dashicons-admin-links'; ?>"></span>
      <?php echo !empty($this->isNewsletter) ? esc_html__('Newsletters', 'ppsgen') : esc_html__('Automations', 'ppsgen'); ?> /
      <span class="ppsgenModulePageTitleName"><?php echo esc_html(!empty($options['name']) ? $options['name'] : __('Untitled', 'ppsgen')); ?></span>
    <?php $partOfPlay = $this->getFrame()->getModule('dashboard') ? $this->getFrame()->getModule('dashboard')->getPlays()->getPlayByPart('chain', (int) $objId) : []; ?>
    <?php if (!empty($partOfPlay)) { ?><a class="ppsgenPartOfChip" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=' . (in_array($partOfPlay['status'] ?? '', ['live', 'paused'], true) ? 'play_summary' : 'play_review') . '&id=' . $partOfPlay['id'])); ?>"><?php echo esc_html(sprintf(__('Part of: %s', 'ppsgen'), $partOfPlay['name'])); ?></a><?php } ?>
      <?php } else { ?>
      <span class="dashicons dashicons-admin-links"></span> <?php esc_html_e('New automation', 'ppsgen'); ?>
      <?php } ?>
    </h2>
  </div>
  <div class="ppsgenChainEditHeaderMeta">
    <?php if (!empty($cronStats)) { ?>
      <button type="button" class="ppsgenChainEditHeaderMetaToggle" aria-expanded="true">
        <span class="dashicons dashicons-arrow-up-alt2"></span>
      </button>
      <div class="ppsgenChainEditHeaderMetaGrid">
        <div class="ppsgenChainEditHeaderMetaRow ppsgenChainEditHeaderMetaRowPrimary"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Server Time', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue"><?php echo esc_html($cronStats['server_time']); ?></span></div>
        <div class="ppsgenChainEditHeaderMetaRow ppsgenChainEditHeaderMetaRowPrimary"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Next CRON Run (Flow)', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue"><?php echo esc_html($cronStats['flow_cron']); ?></span></div>
        <div class="ppsgenChainEditHeaderMetaRow ppsgenChainEditHeaderMetaRowPrimary"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Next CRON Run (Email)', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue"><?php echo esc_html($cronStats['email_cron']); ?></span></div>
        <div class="ppsgenChainEditHeaderMetaRow ppsgenChainEditHeaderMetaRowPrimary"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Next CRON Run (Auto Bounce)', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue"><?php echo esc_html($cronStats['auto_bounce_cron']); ?></span></div>
        <div class="ppsgenChainEditHeaderMetaRow ppsgenChainEditHeaderMetaRowSecondary"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Emails in Queue', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue"><?php echo esc_html($cronStats['emails_queue']); ?></span></div>
        <div class="ppsgenChainEditHeaderMetaRow ppsgenChainEditHeaderMetaRowSecondary"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Mail Config', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue" data-mail-config-name><?php echo esc_html($cronStats['mail_config_name'] !== '' ? $cronStats['mail_config_name'] : __('Default', 'ppsgen')); ?></span></div>
        <div class="ppsgenChainEditHeaderMetaRow ppsgenChainEditHeaderMetaRowSecondary"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Daily Email Limit (remaining)', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue" data-mail-config-limit><?php echo esc_html($cronStats['mail_remaining'] . ' / ' . $cronStats['mail_limit']); ?></span></div>
      </div>
    <?php } else { ?>
      <div class="ppsgenChainEditHeaderMetaRow"><span class="ppsgenChainEditHeaderMetaKey"><?php esc_html_e('Server Time', 'ppsgen'); ?></span><span class="ppsgenChainEditHeaderMetaValue"><?php echo esc_html($serverTime); ?></span></div>
    <?php } ?>
  </div>
</div>
<?php if (!empty($mailConfigStatsMap)) { ?>
  <div
    id="ppsgenChainMailConfigStatsData"
    hidden
    data-stats="<?php echo esc_attr(wp_json_encode($mailConfigStatsMap)); ?>"
    data-default-label="<?php echo esc_attr(__('Default', 'ppsgen')); ?>"
  ></div>
<?php } ?>

<?php if (!empty($this->isNewsletter)) { ?>
  <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  echo $this->getFrame()->core('helper')->wpKses($this->renderTpl('newsletter_panel', 'eca')); ?>
<?php } ?>
<form method="POST" id="ppsgenEditObjFormChain"<?php echo !empty($this->isNewsletter) ? ' class="ppsgenNewsletterEditor"' : ''; ?>>
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" name="mod" value="chain">
  <input type="hidden" id="action" name="action" value="editChain">
  <?php if ($objId) { ?>
  <input type="hidden" name="obj_id" value="<?php ppsgen_esc_attr($objId); ?>">
  <?php } ?>
  <input type="hidden" name="chain_edit_active_tab" value="<?php echo esc_attr($activeTab); ?>">

  <div class="ppsgenObjEditWrapper">
    <div class="ppsgenObjEditTabsButtons">
      <?php foreach ($this->tabs as $tabKey => $tabData) { ?>
      <div class="ppsgenObjEditTabButton<?php echo $tabKey === $activeTab ? ' ppsgenObjEditTabButtonActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
        <?php echo esc_html($tabData['title']); ?>
      </div>
      <?php } ?>
    </div>
    <div class="ppsgenObjEditTabs">
      <?php foreach ($this->tabs as $tabKey => $tabData) { ?>
      <div class="ppsgenObjEditTab<?php echo $tabKey === $activeTab ? ' ppsgenObjEditTabActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo $this->getFrame()->core('helper')->wpKses($tabData['content']); ?>
      </div>
      <?php } ?>
    </div>
  </div>
</form>
<?php $component->setNamePrefix('');
?>
