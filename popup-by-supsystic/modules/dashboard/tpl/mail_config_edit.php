<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$mailConfig = is_array($this->mailConfig ?? null) ? $this->mailConfig : [];
$settings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
$configId = (int) ($mailConfig['id'] ?? 0);
$contactTagOptions = is_array($this->contactTagOptions ?? null) ? $this->contactTagOptions : [];
// PRO provider connections keep working (and can be reconnected) after the license expires
$licenseState = (string) $this->getFrame()->useModule('license', '', 'model', 'LState');
$licenseActive = in_array($licenseState, ['active', 'grace', 'expired'], true);
$prefix = 'data[mail_config]';
$providerFieldsContext = [
  'mail_config' => $mailConfig,
  'settings' => $settings,
  'prefix' => $prefix,
  'component' => $component,
];
$microsoft365FieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'microsoft_365', $providerFieldsContext);
$googleWorkspaceFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'google_workspace', $providerFieldsContext);
$zohoMailFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'zoho_mail', $providerFieldsContext);
$fastmailFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'fastmail', $providerFieldsContext);
$sendgridFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'sendgrid', $providerFieldsContext);
$mailgunFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'mailgun', $providerFieldsContext);
$brevoFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'brevo', $providerFieldsContext);
$postmarkFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'postmark', $providerFieldsContext);
$smtp2goFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'smtp2go', $providerFieldsContext);
$resendFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'resend', $providerFieldsContext);
$amazonSesFieldsHtml = (string) $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.provider_fields_html', '', 'amazon_ses', $providerFieldsContext);
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-email-alt2"></span>
    <?php echo $configId > 0 ? esc_html__('Edit Mail Config', 'ppsgen') : esc_html__('Create Mail Config', 'ppsgen'); ?>
  </h2>
  <div class="ppsgenDashboardMailConfigHeaderActions">
    <button class="ppsgenSaveBtn ppsgenDashboardMailConfigSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </div>
</div>
<div class="ppsgenOptionsWrapper">
  <form class="ppsgenOptionsForm" id="ppsgenDashboardMailConfigForm">
    <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
    <input type="hidden" name="mod" value="dashboard">
    <input type="hidden" name="action" value="saveMailConfig">
    <?php if ($configId > 0) { ?>
      <input type="hidden" name="config_id" value="<?php echo esc_attr($configId); ?>">
    <?php } ?>
    <div class="ppsgenComponentsList">
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Config Name', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[name]', ['value' => $component->value($mailConfig['name'] ?? null), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Use as Default Config', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('[is_default]', ['checked' => $component->value(!empty($mailConfig['is_default']) ? '1' : '0', '0'), 'class' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Provider', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Choose which provider type this mail identity will use.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('[settings][provider_type]', [
            'options' => $this->getModel()->getMailConfigProviderOptions(),
            'class' => 'data-option-toggle-trigger',
            'selected' => $component->value($settings['provider_type'] ?? null, 'custom_smtp_imap'),
          ]); ?>
        </div>
      </div>
      <h3>Provider Settings</h3>
      <?php if (!$licenseActive) : ?>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigProNoticeRow" style="display:none;">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('PRO Provider', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php
          $proMailProviderNotices = [
            'microsoft_365' => [
              'label' => __('Microsoft 365', 'ppsgen'),
              'title' => __('Connect Microsoft 365  ', 'ppsgen'),
              'text' => __('Unlock OAuth, Microsoft Graph sending, mailbox reading, thread sync, and real contact conversations directly inside Supsystic.', 'ppsgen'),
            ],
            'google_workspace' => [
              'label' => __('Google Workspace', 'ppsgen'),
              'title' => __('Connect Google Workspace  ', 'ppsgen'),
              'text' => __('Unlock Gmail API sending, mailbox sync, threaded replies, and a clean email workflow for support and CRM communication.', 'ppsgen'),
            ],
            'zoho_mail' => [
              'label' => __('Zoho Mail', 'ppsgen'),
              'title' => __('Use Zoho Mail  ', 'ppsgen'),
              'text' => __('Enable send + read mode, faster presets, mailbox sync, and provider-specific handling for smoother email operations.', 'ppsgen'),
            ],
            'fastmail' => [
              'label' => __('Fastmail', 'ppsgen'),
              'title' => __('Use Fastmail  ', 'ppsgen'),
              'text' => __('Send emails, read replies, and keep contact conversations connected with a full mailbox workflow inside the plugin.', 'ppsgen'),
            ],
            'sendgrid' => [
              'label' => __('SendGrid', 'ppsgen'),
              'title' => __('Unlock SendGrid  ', 'ppsgen'),
              'text' => __('Use API-based delivery for forms, automations, campaigns, and dedicated sending identities managed from one place.', 'ppsgen'),
            ],
            'mailgun' => [
              'label' => __('Mailgun', 'ppsgen'),
              'title' => __('Unlock Mailgun  ', 'ppsgen'),
              'text' => __('Connect Mailgun API sending for high-volume delivery, forms, automations, and cleaner provider-based email workflows.', 'ppsgen'),
            ],
            'brevo' => [
              'label' => __('Brevo', 'ppsgen'),
              'title' => __('Unlock Brevo  ', 'ppsgen'),
      'text' => __('Use a dedicated Brevo config for forms, email campaigns, and CRM communication with API-based sending.', 'ppsgen'),
            ],
            'postmark' => [
              'label' => __('Postmark', 'ppsgen'),
              'title' => __('Unlock Postmark  ', 'ppsgen'),
              'text' => __('Enable fast transactional sending and manage purpose-based mail configs for your project from one dashboard.', 'ppsgen'),
            ],
            'smtp2go' => [
              'label' => __('SMTP2GO', 'ppsgen'),
              'title' => __('Unlock SMTP2GO  ', 'ppsgen'),
              'text' => __('Use SMTP2GO API delivery and keep dedicated sending identities for campaigns, forms, and automation flows.', 'ppsgen'),
            ],
            'resend' => [
              'label' => __('Resend', 'ppsgen'),
              'title' => __('Unlock Resend  ', 'ppsgen'),
              'text' => __('Use modern API-based sending for forms, automations, and direct contact emails with flexible config management.', 'ppsgen'),
            ],
            'amazon_ses' => [
              'label' => __('Amazon SES', 'ppsgen'),
              'title' => __('Unlock Amazon SES  ', 'ppsgen'),
              'text' => __('Connect AWS-powered delivery for scalable email sending and advanced provider-based mail workflows.', 'ppsgen'),
            ],
          ];
          foreach ($proMailProviderNotices as $providerKey => $providerNotice) :
          ?>
          <div class="ppsgenDashboardMailConfigProNotice" data-provider="<?php echo esc_attr($providerKey); ?>" style="display:none;">
            <div class="ppsgenDashboardMailConfigProNoticeHead">
              <span class="ppsgenDashboardMailConfigProNoticeBadge"><?php esc_html_e('PRO', 'ppsgen'); ?></span>
              <span class="ppsgenDashboardMailConfigProNoticeProvider"><?php echo esc_html($providerNotice['label']); ?></span>
            </div>
            <div class="ppsgenDashboardMailConfigProNoticeTitle"><?php echo esc_html($providerNotice['title']); ?></div>
            <div class="ppsgenDashboardMailConfigProNoticeText"><?php echo esc_html($providerNotice['text']); ?></div>
            <div class="ppsgenDashboardMailConfigProNoticeMeta">
              <span class="ppsgenDashboardMailConfigProNoticePrice"><span style="font-size:18px;">$99 / year · 1 site</span></span>
            </div>
            <div class="ppsgenDashboardMailConfigProNoticeActions">
              <a class="ppsgenDashboardMailConfigProNoticeBtn" href="https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&amp;utm_medium=feature&amp;utm_campaign=email_providers_pro" target="_blank" rel="noopener noreferrer">
                <span class="dashicons dashicons-external"></span><?php esc_html_e('Unlock All Email Providers and PRO features', 'ppsgen'); ?>
              </a>
            </div>
            <div class="ppsgenDashboardMailConfigProNoticeMeta">
              <span class="ppsgenDashboardMailConfigProNoticeCoupon" style="font-weight:400;font-size:10px;"><?php esc_html_e('$99 stays $99 when you renew · No contact-based pricing · 30-day money-back guarantee', 'ppsgen'); ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($microsoft365FieldsHtml !== '') : ?>
        <?php echo $microsoft365FieldsHtml; ?>
      <?php endif; ?>
      <?php if ($googleWorkspaceFieldsHtml !== '') : ?>
        <?php echo $googleWorkspaceFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($zohoMailFieldsHtml !== '') : ?>
        <?php echo $zohoMailFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($fastmailFieldsHtml !== '') : ?>
        <?php echo $fastmailFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($sendgridFieldsHtml !== '') : ?>
        <?php echo $sendgridFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($mailgunFieldsHtml !== '') : ?>
        <?php echo $mailgunFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($brevoFieldsHtml !== '') : ?>
        <?php echo $brevoFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($postmarkFieldsHtml !== '') : ?>
        <?php echo $postmarkFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($smtp2goFieldsHtml !== '') : ?>
        <?php echo $smtp2goFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($resendFieldsHtml !== '') : ?>
        <?php echo $resendFieldsHtml; ?>
      <?php endif; ?>
      <?php if ($amazonSesFieldsHtml !== '') : ?>
        <?php echo $amazonSesFieldsHtml; ?>
      <?php endif; ?>
      <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][provider_type]" data-toggle-val="custom_smtp_imap">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Connection Mode', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <div class="ppsgenFormSubmitsWrapperWarning" style="display:block;">
            <?php esc_html_e('Custom provider mode uses the built-in SMTP / IMAP workflow from the free plugin.', 'ppsgen'); ?>
          </div>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('From Email (optional)', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][mail_from_email]', ['value' => $component->value($settings['mail_from_email'] ?? null, get_bloginfo('admin_email')), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('From Name', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][mail_from_name]', ['value' => $component->value($settings['mail_from_name'] ?? null, get_bloginfo('name')), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][provider_type]" data-toggle-val="custom_smtp_imap">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Use PHP mail function or SMTP / IMAP', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('[settings][mail_method]', [
            'options' => ['mail' => 'PHP mail() - Send Only', 'smtp' => 'SMTP / IMAP - Server (Send + Read)'],
            'class' => 'data-option-toggle-trigger',
            'selected' => $component->value($settings['mail_method'] ?? null, 'mail'),
          ]); ?>
        </div>
      </div>
      <?php
      // Guided set-up: picking a provider fills in the server details (backend.mail_config_edit.js)
      $smtpHost = (string) wp_parse_url(home_url(), PHP_URL_HOST);
      $smtpPresets = [
        'gmail' => ['label' => __('Gmail / Google Workspace', 'ppsgen'), 'smtp' => 'smtp.gmail.com', 'imap' => 'imap.gmail.com', 'port' => 465, 'secure' => 'ssl', 'help' => __('Turn on 2-step verification in your Google account, then create an app password and paste it below (not your normal password).', 'ppsgen'), 'url' => 'https://myaccount.google.com/apppasswords'],
        'outlook' => ['label' => __('Outlook.com / Hotmail', 'ppsgen'), 'smtp' => 'smtp-mail.outlook.com', 'imap' => 'outlook.office365.com', 'port' => 587, 'secure' => 'tls', 'help' => __('Create an app password in your Microsoft account security settings. Microsoft is phasing out password sign-in for email apps: if the test email fails, use the Microsoft 365 connection in PRO.', 'ppsgen'), 'url' => 'https://account.microsoft.com/security'],
        'office365' => ['label' => __('Microsoft 365 (work or school)', 'ppsgen'), 'smtp' => 'smtp.office365.com', 'imap' => 'outlook.office365.com', 'port' => 587, 'secure' => 'tls', 'help' => __('Your Microsoft 365 admin must allow "Authenticated SMTP" for this mailbox. If that is not possible, the Microsoft 365 connection in PRO signs in for you.', 'ppsgen'), 'url' => 'https://learn.microsoft.com/exchange/clients-and-mobile-in-exchange-online/authenticated-client-smtp-submission'],
        'yahoo' => ['label' => __('Yahoo Mail', 'ppsgen'), 'smtp' => 'smtp.mail.yahoo.com', 'imap' => 'imap.mail.yahoo.com', 'port' => 465, 'secure' => 'ssl', 'help' => __('Generate an app password in your Yahoo account security settings and paste it below.', 'ppsgen'), 'url' => 'https://login.yahoo.com/account/security'],
        'zoho' => ['label' => __('Zoho Mail', 'ppsgen'), 'smtp' => 'smtp.zoho.com', 'imap' => 'imap.zoho.com', 'port' => 465, 'secure' => 'ssl', 'help' => __('Use your Zoho password, or an app password if two-factor sign-in is on. Business domains often use smtppro.zoho.com.', 'ppsgen'), 'url' => 'https://accounts.zoho.com/home#security/app_password'],
        'host' => ['label' => __('My web host\'s mailbox', 'ppsgen'), 'smtp' => 'mail.' . preg_replace('/^www\./', '', $smtpHost), 'imap' => 'mail.' . preg_replace('/^www\./', '', $smtpHost), 'port' => 465, 'secure' => 'ssl', 'help' => __('Use the mailbox address and password from your hosting control panel. The server name is usually mail.yourdomain; your host\'s help pages list the exact one.', 'ppsgen'), 'url' => ''],
      ];
      ?>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email provider', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Pick where your mailbox is and we fill in the server details for you.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <select class="ppsgenSmtpPreset" data-presets="<?php echo esc_attr(wp_json_encode($smtpPresets)); ?>">
            <option value=""><?php esc_html_e('Other SMTP server (fill in below)', 'ppsgen'); ?></option>
            <?php foreach ($smtpPresets as $presetKey => $preset) { ?>
              <option value="<?php echo esc_attr($presetKey); ?>"><?php echo esc_html($preset['label']); ?></option>
            <?php } ?>
          </select>
          <div class="ppsgenComponentOptionNoticeUnder ppsgenSmtpPresetHelp" style="display:none;"><span class="ppsgenSmtpPresetHelpText"></span> <a class="ppsgenSmtpPresetHelpLink" href="#" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Where do I find it?', 'ppsgen'); ?> &rarr;</a></div>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SMTP Server', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][smtp_server_name]', ['value' => $component->value($settings['smtp_server_name'] ?? null), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('IMAP Server', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][imap_server_name]', ['value' => $component->value($settings['imap_server_name'] ?? null), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Login (Email)', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][smtp_login]', ['value' => $component->value($settings['smtp_login'] ?? null), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Password', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][smtp_password]', ['value' => $component->value($settings['smtp_password'] ?? null), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SMTP Port (465 (SSL), 25, 2525 or 587)', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->number('[settings][smtp_port]', ['value' => $component->value($settings['smtp_port'] ?? null, '465'), 'attrs' => 'min="0"']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Encryption', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('SSL usually goes with port 465, STARTTLS (TLS) with port 587.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('[settings][smtp_secure]', [
            'options' => ['ssl' => __('SSL (port 465)', 'ppsgen'), 'tls' => __('STARTTLS / TLS (port 587)', 'ppsgen')],
            'selected' => $component->value($settings['smtp_secure'] ?? null, 'ssl'),
          ]); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('IMAP Port (993 (SSL), 143)', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->number('[settings][imap_port]', ['value' => $component->value($settings['imap_port'] ?? null, '993'), 'attrs' => 'min="0"']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigCustomSmtpRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][mail_method]" data-toggle-val="smtp">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SMTP Timeout', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->number('[settings][smtp_timeout]', ['value' => $component->value($settings['smtp_timeout'] ?? null, '0'), 'attrs' => 'min="0"']); ?>
        </div>
      </div>
      <h3 class="ppsgenDashboardMailConfigMailboxTrackingTitle"><?php esc_html_e('Automatic Processing (Read providers only)', 'ppsgen'); ?></h3>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceEnableRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Auto Processing', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Automatically scan this mailbox for bounced emails and track new incoming contact emails in the background when this config works in Send + Read mode.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('[settings][auto_bounce_enabled]', ['checked' => $component->value($settings['auto_bounce_enabled'] ?? null, '0'), 'class' => 'data-option-toggle-trigger']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][auto_bounce_enabled]" data-toggle-val="1">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Auto Check Interval', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Choose how often Supsystic should scan this mailbox for new bounce notifications.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('[settings][auto_bounce_interval]', [
            'options' => $this->getModel()->getMailConfigAutoBounceIntervalOptions(),
            'selected' => $component->value($settings['auto_bounce_interval'] ?? null, '15'),
          ]); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][auto_bounce_enabled]" data-toggle-val="1">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max Messages Per Auto Run', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Limit how many unseen mailbox messages can be scanned during one background task to avoid slow or heavy cron runs.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->number('[settings][auto_bounce_max_messages_per_run]', ['value' => $component->value($settings['auto_bounce_max_messages_per_run'] ?? null, '50'), 'attrs' => 'min="1"']); ?>
        </div>
      </div>
      <h4 class="ppsgenDashboardMailConfigMailboxTrackingSubTitle ppsgenDashboardMailConfigAutoBounceSubTitle"><?php esc_html_e('Automatic Bounce Processing', 'ppsgen'); ?></h4>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][auto_bounce_enabled]" data-toggle-val="1">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Move Processed Bounce Emails to Trash', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('If enabled, matched bounce emails will be moved from INBOX to Trash after processing to avoid repeated scans.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('[settings][auto_bounce_move_to_trash]', ['checked' => $component->value($settings['auto_bounce_move_to_trash'] ?? null, '1'), 'class' => 'data-option-toggle-trigger']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][auto_bounce_enabled]" data-toggle-val="1">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Bounce Error Tags', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select existing contact tags to add when bounced recipients are matched to your contacts.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->selectmultiple('[settings][auto_bounce_tag_ids][]', [
            'class' => 'ppsgenselect',
            'options' => $contactTagOptions,
            'selected' => !empty($settings['auto_bounce_tag_ids']) && is_array($settings['auto_bounce_tag_ids']) ? $settings['auto_bounce_tag_ids'] : [],
          ]); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][auto_bounce_enabled]" data-toggle-val="1">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Additional Bounce Error Tag Slugs', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Optional comma-separated tag slugs to auto-create and assign to contacts detected in bounce notifications. Example: email_error, hard_bounce.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][auto_bounce_tag_slugs]', ['value' => $component->value($settings['auto_bounce_tag_slugs'] ?? ($settings['auto_bounce_tag_slug'] ?? null), 'email_error'), 'attrs' => '']); ?>
        </div>
      </div>
      <h4 class="ppsgenDashboardMailConfigMailboxTrackingSubTitle ppsgenDashboardMailConfigAutoBounceUnknownContactSubTitle"><?php esc_html_e('Automatic Contact Creation', 'ppsgen'); ?></h4>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[settings][auto_bounce_enabled]" data-toggle-val="1">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Create Contacts for Unknown Senders', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('If a valid incoming email address does not exist in Contacts yet, Supsystic will create a verified contact automatically during mailbox sync for this mailbox.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('[settings][auto_bounce_create_unknown_contacts]', ['checked' => $component->value($settings['auto_bounce_create_unknown_contacts'] ?? null, '0'), 'class' => 'data-option-toggle-trigger']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow ppsgenDashboardMailConfigAutoBounceUnknownContactDetailRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Unknown Sender Tags', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select existing contact tags to add when Supsystic creates a new verified contact from an unknown incoming sender.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->selectmultiple('[settings][auto_bounce_unknown_contact_tag_ids][]', [
            'class' => 'ppsgenselect',
            'options' => $contactTagOptions,
            'selected' => !empty($settings['auto_bounce_unknown_contact_tag_ids']) && is_array($settings['auto_bounce_unknown_contact_tag_ids']) ? $settings['auto_bounce_unknown_contact_tag_ids'] : [],
          ]); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingRow ppsgenDashboardMailConfigAutoBounceDetailRow ppsgenDashboardMailConfigAutoBounceUnknownContactDetailRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Additional Unknown Sender Tag Slugs', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Optional comma-separated tag slugs to auto-create and assign to newly created contacts from unknown senders. Example: inbox_reply, inbound_email.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[settings][auto_bounce_unknown_contact_tag_slugs]', ['value' => $component->value($settings['auto_bounce_unknown_contact_tag_slugs'] ?? null, ''), 'attrs' => '']); ?>
        </div>
      </div>
      <h3>Limits & Delay</h3>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Daily Limit (emails)', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->number('[settings][mail_daily_limit]', ['value' => $component->value($settings['mail_daily_limit'] ?? null, '300'), 'attrs' => 'min="0"']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Delay Between Send Emails (seconds)', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->number('[settings][mail_delay]', ['value' => $component->value($settings['mail_delay'] ?? null, '1'), 'attrs' => 'min="0"']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Batch Size for CRON (emails per task)', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->number('[settings][mail_batch_size]', ['value' => $component->value($settings['mail_batch_size'] ?? null, '10'), 'attrs' => 'min="0"']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow ppsgenDashboardMailConfigMailboxTrackingConnectionRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Connection Status', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <button type="button" class="ppsgenTableListBtn ppsgenDashboardMailConfigActionBtn ppsgenDashboardMailConfigTestBtn" style="margin-bottom:8px;"><span class="dashicons dashicons-update-alt"></span><?php esc_html_e('Check Connection', 'ppsgen'); ?></button>
          <div class="ppsgenFormSubmitsWrapperWarning ppsgenDashboardMailConfigTestResult" style="display:none;"></div>
        </div>
      </div>
    </div>
    <button class="ppsgenSaveBtn ppsgenDashboardMailConfigSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </form>
</div>
<?php $component->setNamePrefix(''); ?>
