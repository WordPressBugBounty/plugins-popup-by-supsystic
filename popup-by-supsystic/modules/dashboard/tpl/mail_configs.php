<?php
if (!defined('ABSPATH')) {
  exit();
}
$rows = is_array($this->mailConfigs ?? null) ? $this->mailConfigs : [];
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-email-alt2"></span>
    <?php esc_html_e('Email sending', 'ppsgen'); ?>
  </h2>
  <a class="ppsgenSaveBtn" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=dashboard_mail_config_edit')); ?>"><span class="dashicons dashicons-plus-alt"></span><?php esc_html_e('Add sending account', 'ppsgen'); ?></a>
</div>
<?php
$emailStatus = is_array($this->emailStatus ?? null) ? $this->emailStatus : [];
$lastTest = is_array($this->lastTestEmail ?? null) ? $this->lastTestEmail : [];
$defaultConfigId = (int) ($this->defaultMailConfigId ?? 0);
$setupUrl = admin_url('admin.php?page=ppsgen&tab=dashboard_mail_config_edit' . ($defaultConfigId > 0 ? '&id=' . $defaultConfigId : ''));
$dateFormat = get_option('date_format') . ' ' . get_option('time_format');
?>
<?php if (!empty($emailStatus)) { ?>
<section class="ppsgenEmailStatusCard ppsgenEmailStatusCard-<?php echo esc_attr($emailStatus['state']); ?>">
  <div class="ppsgenEmailStatusMain">
    <span class="ppsgenEmailStatusDot"></span>
    <div>
      <div class="ppsgenEmailStatusTitle"><?php echo esc_html($emailStatus['label']); ?></div>
      <div class="ppsgenEmailStatusHint"><?php echo esc_html($emailStatus['hint']); ?></div>
      <div class="ppsgenEmailStatusHint ppsgenEmailTestResult">
        <?php if (empty($lastTest)) { ?>
          <?php esc_html_e('No test email sent yet.', 'ppsgen'); ?>
        <?php } elseif (!empty($lastTest['ok'])) { ?>
          <?php echo esc_html(sprintf(__('Last test email sent to %1$s · %2$s', 'ppsgen'), $lastTest['to'] ?? '', date_i18n($dateFormat, (int) ($lastTest['time'] ?? 0)))); ?>
        <?php } else { ?>
          <?php echo esc_html(sprintf(__('Last test failed · %1$s · %2$s', 'ppsgen'), date_i18n($dateFormat, (int) ($lastTest['time'] ?? 0)), (string) ($lastTest['error'] ?? ''))); ?>
        <?php } ?>
      </div>
    </div>
  </div>
  <div class="ppsgenEmailStatusActions">
    <?php if ($emailStatus['state'] !== 'ok') { ?>
      <a class="ppsgenBtnPrimary" href="<?php echo esc_url($setupUrl); ?>"><?php esc_html_e('Set up SMTP', 'ppsgen'); ?></a>
    <?php } ?>
    <button type="button" class="ppsgenBtnSecondary ppsgenSendTestEmailBtn" data-sending="<?php esc_attr_e('Sending…', 'ppsgen'); ?>"><?php esc_html_e('Send test email', 'ppsgen'); ?></button>
  </div>
</section>
<?php } ?>
<?php $domainCheck = is_array($this->domainCheck ?? null) ? $this->domainCheck : []; ?>
<?php if (!empty($domainCheck['items'])) { ?>
<section class="ppsgenCard ppsgenDomainCheck">
  <div class="ppsgenCardHead">
    <h2><?php echo esc_html($domainCheck['domain'] !== '' ? sprintf(__('Sending domain: %s', 'ppsgen'), $domainCheck['domain']) : __('Sending domain', 'ppsgen')); ?></h2>
    <button type="button" class="ppsgenBtnSecondary ppsgenRecheckDomain" data-busy="<?php esc_attr_e('Checking…', 'ppsgen'); ?>"><?php esc_html_e('Check again', 'ppsgen'); ?></button>
  </div>
  <?php foreach ($domainCheck['items'] as $item) { ?>
    <div class="ppsgenAttention">
      <span class="ppsgenAttentionDot<?php echo $item['ok'] === true ? ' ppsgenAttentionDotOk' : ($item['ok'] === null ? ' ppsgenAttentionDotInfo' : ''); ?>"></span>
      <div>
        <div class="ppsgenAttentionTitle"><?php echo esc_html($item['title']); ?></div>
        <div class="ppsgenMuted ppsgenDomainCheckText"><?php echo esc_html($item['text']); ?></div>
      </div>
    </div>
  <?php } ?>
  <p class="ppsgenMuted"><?php echo esc_html(sprintf(__('Checked %s. DKIM is set up with your email provider and cannot be checked from here.', 'ppsgen'), date_i18n($dateFormat, (int) $domainCheck['checked_at']))); ?></p>
</section>
<?php } ?>
<div class="ppsgenOptionsWrapper">
  <?php if (empty($rows)) { ?>
    <div class="ppsgenFormSubmitsWrapperWarning"><?php esc_html_e('No mail configs yet. Create your first mail provider config to start using multiple mail identities.', 'ppsgen'); ?></div>
  <?php } else { ?>
    <table class="ppsgenModuleTableList widefat striped">
      <thead>
        <tr>
          <th><?php esc_html_e('Name', 'ppsgen'); ?></th>
          <th><?php esc_html_e('Provider', 'ppsgen'); ?></th>
          <th><?php esc_html_e('From Email', 'ppsgen'); ?></th>
          <th><?php esc_html_e('Daily Limit', 'ppsgen'); ?></th>
          <th><?php esc_html_e('Remaining', 'ppsgen'); ?></th>
          <th><?php esc_html_e('Updated', 'ppsgen'); ?></th>
          <th><?php esc_html_e('Actions', 'ppsgen'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row) { ?>
          <?php $settings = is_array($row['settings'] ?? null) ? $row['settings'] : []; ?>
          <?php $providerType = (string) ($settings['provider_type'] ?? 'custom_smtp_imap'); ?>
          <tr>
            <td>
              <strong><?php echo esc_html((string) ($row['name'] ?? '')); ?></strong>
              <?php if (!empty($row['is_default'])) { ?>
                <div><span class="ppsgenContactCampaignPill"><?php esc_html_e('Default', 'ppsgen'); ?></span></div>
              <?php } ?>
            </td>
            <td><?php echo esc_html($this->getModel()->getMailConfigProviderLabel($providerType)); ?></td>
            <td><?php echo esc_html((string) ($settings['mail_from_email'] ?? '')); ?></td>
            <td><?php echo esc_html((int) ($settings['mail_daily_limit'] ?? 0)); ?></td>
            <td><?php echo esc_html((int) ($settings['mail_remaining'] ?? 0)); ?></td>
            <td><?php echo esc_html((string) ($row['updated_at'] ?? '')); ?></td>
            <td>
              <div class="ppsgenDashboardMailConfigActions">
                <a class="ppsgenTableListBtn ppsgenDashboardMailConfigActionBtn" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=dashboard_mail_config_edit&id=' . (int) $row['id'])); ?>"><span class="dashicons dashicons-edit"></span><?php esc_html_e('Edit', 'ppsgen'); ?></a>
                <button type="button" class="ppsgenTableListBtn ppsgenDashboardMailConfigActionBtn ppsgenDashboardMailConfigActionBtnDelete ppsgenDashboardMailConfigDeleteBtn" data-config-id="<?php echo esc_attr((int) $row['id']); ?>" data-confirm="<?php esc_attr_e('Delete this mail config?', 'ppsgen'); ?>"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Delete', 'ppsgen'); ?></button>
              </div>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  <?php } ?>
</div>
