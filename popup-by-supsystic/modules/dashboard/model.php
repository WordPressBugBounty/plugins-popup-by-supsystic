<?php

if (!defined('ABSPATH')) {
  exit();
}

class DashboardModelPpsgen extends BaseModelPpsgen
{
  protected bool $mailConfigStorageChecked = false;

  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $tableName = $this->getMailConfigTable();
    $sql = "CREATE TABLE $tableName (
      `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NOT NULL,
      `settings` LONGTEXT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) $charset;";
    dbDelta($sql);
    $this->migrateLegacyMailConfig();
    $this->ensureDefaultMailConfigId();
  }

  public function ensureMailConfigStorage(): void
  {
    if ($this->mailConfigStorageChecked) {
      return;
    }
    global $wpdb;
    $this->mailConfigStorageChecked = true;
    $table = $this->getMailConfigTable();
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($exists !== $table) {
      $this->installTables();
      return;
    }
    $this->migrateLegacyMailConfig();
    $this->ensureDefaultMailConfigId();
  }

  public function updateOptions($data, $group = '')
  {
    if (!empty($data)) {
      foreach ($data as $slug => $module) {
        foreach ($module as $keyOpt => $keyVal) {
          if ($keyOpt == 'mail_daily_limit') {
            $this->updateOption('mail_remaining', 'options', $keyVal, 0, 'dashboard', 'json');
          }
          if ($slug === 'dashboard' && $keyOpt === 'eca_tracking_query_prefix') {
            $keyVal = function_exists('ppsgen_sanitize_eca_tracking_query_prefix') ? ppsgen_sanitize_eca_tracking_query_prefix($keyVal) : sanitize_key((string) $keyVal);
            if ($keyVal === '' && function_exists('ppsgen_get_default_eca_tracking_query_prefix')) {
              $keyVal = ppsgen_get_default_eca_tracking_query_prefix();
            }
          }
          $this->updateOption($keyOpt, $group, $keyVal, 0, $slug, 'json');
        }
      }
      return true;
    }
  }

  protected function getMailConfigTable(): string
  {
    global $wpdb;
    return $wpdb->prefix . 'ppsgen_mail_configs';
  }

  protected function prepareMailConfigSettingsForRuntime(array $settings, int $configId = 0): array
  {
    $defaults = $this->getMailConfigDefaultSettings();
    $settings = wp_parse_args($settings, $defaults);
    $this->getFrame()->core('helper')->bootProMailProviders();
    $providerOptions = $this->getMailConfigProviderOptions();
    $settings['provider_type'] = sanitize_key((string) ($settings['provider_type'] ?? 'custom_smtp_imap'));
    if (empty($providerOptions[$settings['provider_type']])) {
      $settings['provider_type'] = 'custom_smtp_imap';
    }
    $settings = $this->getFrame()->core('eventDispatcher')->applyFilters(
      'dashboard.mail_config.normalize_settings',
      $settings,
      ['settings' => $settings],
      $configId,
      ['settings' => $settings]
    );
    return wp_parse_args($settings, $defaults);
  }

  public function getMailConfigDefaultSettings(): array
  {
    return [
      'provider_type' => 'custom_smtp_imap',
      'mail_method' => 'mail',
      'mail_from_email' => get_bloginfo('admin_email'),
      'mail_from_name' => get_bloginfo('name'),
      'smtp_server_name' => '',
      'imap_server_name' => '',
      'smtp_login' => '',
      'smtp_password' => '',
      'smtp_port' => 465,
      'imap_port' => 993,
      'smtp_timeout' => 0,
      'smtp_secure' => 'ssl',
      'mail_daily_limit' => 300,
      'mail_delay' => 1,
      'mail_batch_size' => 10,
      'mail_remaining' => 300,
      'mail_reset_ts' => 0,
      'auto_bounce_enabled' => 0,
      'auto_bounce_interval' => 15,
      'auto_bounce_move_to_trash' => 1,
      'auto_bounce_tag_ids' => [],
      'auto_bounce_tag_slugs' => 'email_error',
      'auto_bounce_tag_slug' => 'email_error',
      'auto_bounce_max_messages_per_run' => 50,
      'auto_bounce_create_unknown_contacts' => 0,
      'auto_bounce_unknown_contact_tag_ids' => [],
      'auto_bounce_unknown_contact_tag_slugs' => '',
      'auto_bounce_last_run_ts' => 0,
    ];
  }

  public function getMailConfigAutoBounceIntervalOptions(): array
  {
    return [
      1 => __('Every minute', 'ppsgen'),
      5 => __('Every 5 minutes', 'ppsgen'),
      10 => __('Every 10 minutes', 'ppsgen'),
      15 => __('Every 15 minutes', 'ppsgen'),
      30 => __('Every 30 minutes', 'ppsgen'),
      60 => __('Every hour', 'ppsgen'),
    ];
  }

  public function getMailConfigProviderOptions(): array
  {
    $this->getFrame()->core('helper')->bootProMailProviders();
    $options = [
      'custom_smtp_imap' => __('Custom SMTP / IMAP', 'ppsgen'),
      'google_workspace' => __('Google Workspace (PRO)', 'ppsgen'),
      'zoho_mail' => __('Zoho Mail (PRO)', 'ppsgen'),
      'fastmail' => __('Fastmail (PRO)', 'ppsgen'),
      'sendgrid' => __('SendGrid (PRO)', 'ppsgen'),
      'mailgun' => __('Mailgun (PRO)', 'ppsgen'),
      'brevo' => __('Brevo (PRO)', 'ppsgen'),
      'postmark' => __('Postmark (PRO)', 'ppsgen'),
      'smtp2go' => __('SMTP2GO (PRO)', 'ppsgen'),
      'resend' => __('Resend (PRO)', 'ppsgen'),
      'amazon_ses' => __('Amazon SES (PRO)', 'ppsgen'),
      'microsoft_365' => __('Microsoft 365 (PRO)', 'ppsgen'),
    ];
    return $this->getFrame()->core('eventDispatcher')->applyFilters('dashboard.mail_config.provider_options', $options);
  }

  public function getMailConfigProviderLabel(string $providerType): string
  {
    $options = $this->getMailConfigProviderOptions();
    return !empty($options[$providerType]) ? (string) $options[$providerType] : (string) $options['custom_smtp_imap'];
  }

  public function isProMailConfigProvider(string $providerType): bool
  {
    return in_array($providerType, ['microsoft_365', 'google_workspace', 'zoho_mail', 'fastmail', 'sendgrid', 'mailgun', 'brevo', 'postmark', 'smtp2go', 'resend', 'amazon_ses'], true);
  }

  public function getLegacyMailConfigSettings(): array
  {
    $defaults = $this->getMailConfigDefaultSettings();
    foreach (array_keys($defaults) as $key) {
      $value = $this->getOption($key, 'options', 0, 'dashboard');
      if ($value !== null && $value !== '') {
        $defaults[$key] = $value;
      }
    }
    return $defaults;
  }

  protected function migrateLegacyMailConfig(): void
  {
    global $wpdb;
    $table = $this->getMailConfigTable();
    $exists = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    if ($exists > 0) {
      return;
    }
    $legacy = $this->getLegacyMailConfigSettings();
    $hasLegacyData = false;
    foreach (['smtp_server_name', 'imap_server_name', 'smtp_login', 'smtp_password', 'mail_from_email'] as $key) {
      if (!empty($legacy[$key])) {
        $hasLegacyData = true;
        break;
      }
    }
    if (!$hasLegacyData) {
      return;
    }
    $now = current_time('mysql');
    $wpdb->insert($table, [
      'name' => __('Primary Mail Config', 'ppsgen'),
      'settings' => $this->_prepareValueToStore($legacy, 'json'),
      'created_at' => $now,
      'updated_at' => $now,
    ]);
    $configId = (int) $wpdb->insert_id;
    if ($configId > 0) {
      $this->updateOption('default_mail_config_id', 'options', $configId, 0, 'dashboard', 'json');
    }
  }

  public function getMailConfigs(): array
  {
    $this->ensureMailConfigStorage();
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM {$this->getMailConfigTable()} ORDER BY id ASC", ARRAY_A);
    if (empty($rows)) {
      return [];
    }
    $defaultId = $this->getDefaultMailConfigId();
    $defaults = $this->getMailConfigDefaultSettings();
    foreach ($rows as $key => $row) {
      $settings = !empty($row['settings']) ? $this->_prepareValueFromStore($row['settings']) : [];
      if (!is_array($settings)) {
        $settings = [];
      }
      $settings = $this->prepareMailConfigSettingsForRuntime($settings, (int) ($row['id'] ?? 0));
      $rows[$key]['settings'] = $settings;
      $rows[$key]['is_default'] = (int) $row['id'] === $defaultId;
    }
    return $rows;
  }

  public function getMailConfigById(int $id): array
  {
    $this->ensureMailConfigStorage();
    global $wpdb;
    if ($id <= 0) {
      return [];
    }
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->getMailConfigTable()} WHERE id = %d", $id), ARRAY_A);
    if (empty($row)) {
      return [];
    }
    $settings = !empty($row['settings']) ? $this->_prepareValueFromStore($row['settings']) : [];
    if (!is_array($settings)) {
      $settings = [];
    }
    $row['settings'] = $this->prepareMailConfigSettingsForRuntime($settings, $id);
    $row['is_default'] = $id === $this->getDefaultMailConfigId();
    return $row;
  }

  public function getMailConfigOptions(bool $includeEmpty = false): array
  {
    $options = [];
    if ($includeEmpty) {
      $options[0] = '';
    }
    foreach ($this->getMailConfigs() as $row) {
      $label = (string) ($row['name'] ?? ('#' . (int) ($row['id'] ?? 0)));
      if (!empty($row['is_default'])) {
        $label .= ' (' . __('Default', 'ppsgen') . ')';
      }
      $options[(int) $row['id']] = $label;
    }
    return $options;
  }

  public function getDefaultMailConfigId(): int
  {
    $this->ensureMailConfigStorage();
    return (int) $this->getOption('default_mail_config_id', 'options', 0, 'dashboard');
  }

  public function ensureDefaultMailConfigId(): int
  {
    $this->mailConfigStorageChecked = true;
    global $wpdb;
    $defaultId = $this->getDefaultMailConfigId();
    if ($defaultId > 0) {
      $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->getMailConfigTable()} WHERE id = %d", $defaultId));
      if ($exists > 0) {
        return $defaultId;
      }
    }
    $firstId = (int) $wpdb->get_var("SELECT id FROM {$this->getMailConfigTable()} ORDER BY id ASC LIMIT 1");
    if ($firstId > 0) {
      $this->updateOption('default_mail_config_id', 'options', $firstId, 0, 'dashboard', 'json');
      return $firstId;
    }
    $this->updateOption('default_mail_config_id', 'options', 0, 0, 'dashboard', 'json');
    return 0;
  }

  public function normalizeMailConfigPayload(array $data, int $configId = 0): array
  {
    $this->getFrame()->core('helper')->bootProMailProviders();
    $name = !empty($data['name']) ? sanitize_text_field($data['name']) : '';
    $settings = !empty($data['settings']) && is_array($data['settings']) ? $data['settings'] : [];
    $defaults = $this->getMailConfigDefaultSettings();
    $existing = $configId > 0 ? $this->getMailConfigById($configId) : [];
    $settings = wp_parse_args($settings, $defaults);
    $providerOptions = $this->getMailConfigProviderOptions();
    $settings['provider_type'] = sanitize_key((string) ($settings['provider_type'] ?? 'custom_smtp_imap'));
    if (empty($providerOptions[$settings['provider_type']])) {
      $settings['provider_type'] = 'custom_smtp_imap';
    }
    $settings['mail_method'] = ($settings['mail_method'] ?? 'mail') === 'smtp' ? 'smtp' : 'mail';
    $settings['mail_from_email'] = sanitize_email((string) ($settings['mail_from_email'] ?? ''));
    $settings['mail_from_name'] = sanitize_text_field((string) ($settings['mail_from_name'] ?? ''));
    $settings['smtp_server_name'] = sanitize_text_field((string) ($settings['smtp_server_name'] ?? ''));
    $settings['imap_server_name'] = sanitize_text_field((string) ($settings['imap_server_name'] ?? ''));
    $settings['smtp_login'] = sanitize_text_field((string) ($settings['smtp_login'] ?? ''));
    $settings['smtp_password'] = sanitize_text_field((string) ($settings['smtp_password'] ?? ''));
    if ($configId > 0 && $settings['smtp_password'] === '' && !empty($existing['settings']['smtp_password'])) {
      $settings['smtp_password'] = (string) $existing['settings']['smtp_password'];
    }
    $settings['smtp_port'] = max(0, (int) ($settings['smtp_port'] ?? 0));
    $settings['imap_port'] = max(0, (int) ($settings['imap_port'] ?? 0));
    $settings['smtp_timeout'] = max(0, (int) ($settings['smtp_timeout'] ?? 0));
    $settings['smtp_secure'] = in_array(($settings['smtp_secure'] ?? 'ssl'), ['ssl', 'tls'], true) ? $settings['smtp_secure'] : 'ssl';
    $settings['mail_daily_limit'] = max(0, (int) ($settings['mail_daily_limit'] ?? 300));
    $settings['mail_delay'] = max(0, (int) ($settings['mail_delay'] ?? 1));
    $settings['mail_batch_size'] = max(0, (int) ($settings['mail_batch_size'] ?? 10));
    $settings['mail_remaining'] = isset($settings['mail_remaining']) ? max(0, (int) $settings['mail_remaining']) : $settings['mail_daily_limit'];
    $settings['mail_reset_ts'] = isset($settings['mail_reset_ts']) ? (int) $settings['mail_reset_ts'] : 0;
    $autoBounceIntervalOptions = $this->getMailConfigAutoBounceIntervalOptions();
    $settings['auto_bounce_enabled'] = !empty($settings['auto_bounce_enabled']) ? 1 : 0;
    $settings['auto_bounce_interval'] = (int) ($settings['auto_bounce_interval'] ?? 15);
    if (empty($autoBounceIntervalOptions[$settings['auto_bounce_interval']])) {
      $settings['auto_bounce_interval'] = 15;
    }
    $settings['auto_bounce_move_to_trash'] = !empty($settings['auto_bounce_move_to_trash']) ? 1 : 0;
    if ($configId > 0 && !array_key_exists('auto_bounce_tag_ids', $data['settings'] ?? []) && !empty($existing['settings']['auto_bounce_tag_ids']) && is_array($existing['settings']['auto_bounce_tag_ids'])) {
      $settings['auto_bounce_tag_ids'] = $existing['settings']['auto_bounce_tag_ids'];
    }
    $bounceTagIds = !empty($settings['auto_bounce_tag_ids']) && is_array($settings['auto_bounce_tag_ids'])
      ? $settings['auto_bounce_tag_ids']
      : [];
    $settings['auto_bounce_tag_ids'] = array_values(array_unique(array_filter(array_map('absint', $bounceTagIds))));
    if ($configId > 0 && !array_key_exists('auto_bounce_tag_slugs', $data['settings'] ?? []) && !empty($existing['settings']['auto_bounce_tag_slugs'])) {
      $settings['auto_bounce_tag_slugs'] = (string) $existing['settings']['auto_bounce_tag_slugs'];
    }
    $legacyBounceTagSlug = sanitize_key((string) ($settings['auto_bounce_tag_slug'] ?? 'email_error'));
    $bounceTagSlugs = !empty($settings['auto_bounce_tag_slugs']) ? (string) $settings['auto_bounce_tag_slugs'] : '';
    if ($bounceTagSlugs === '' && $legacyBounceTagSlug !== '') {
      $bounceTagSlugs = $legacyBounceTagSlug;
    }
    $bounceTagSlugParts = preg_split('/[\r\n,]+/', $bounceTagSlugs);
    if (!is_array($bounceTagSlugParts)) {
      $bounceTagSlugParts = [];
    }
    $bounceTagSlugParts = array_values(array_unique(array_filter(array_map(static function ($slug) {
      return sanitize_key((string) $slug);
    }, $bounceTagSlugParts))));
    if (empty($bounceTagSlugParts)) {
      $bounceTagSlugParts = ['email_error'];
    }
    $settings['auto_bounce_tag_slugs'] = implode(', ', $bounceTagSlugParts);
    $settings['auto_bounce_tag_slug'] = sanitize_key((string) ($settings['auto_bounce_tag_slug'] ?? 'email_error'));
    $settings['auto_bounce_tag_slug'] = !empty($bounceTagSlugParts[0]) ? (string) $bounceTagSlugParts[0] : 'email_error';
    $settings['auto_bounce_max_messages_per_run'] = max(1, (int) ($settings['auto_bounce_max_messages_per_run'] ?? 50));
    $settings['auto_bounce_create_unknown_contacts'] = !empty($settings['auto_bounce_create_unknown_contacts']) ? 1 : 0;
    if ($configId > 0 && !array_key_exists('auto_bounce_unknown_contact_tag_ids', $data['settings'] ?? []) && !empty($existing['settings']['auto_bounce_unknown_contact_tag_ids']) && is_array($existing['settings']['auto_bounce_unknown_contact_tag_ids'])) {
      $settings['auto_bounce_unknown_contact_tag_ids'] = $existing['settings']['auto_bounce_unknown_contact_tag_ids'];
    }
    $unknownContactTagIds = !empty($settings['auto_bounce_unknown_contact_tag_ids']) && is_array($settings['auto_bounce_unknown_contact_tag_ids'])
      ? $settings['auto_bounce_unknown_contact_tag_ids']
      : [];
    $settings['auto_bounce_unknown_contact_tag_ids'] = array_values(array_unique(array_filter(array_map('absint', $unknownContactTagIds))));
    if ($configId > 0 && !array_key_exists('auto_bounce_unknown_contact_tag_slugs', $data['settings'] ?? []) && !empty($existing['settings']['auto_bounce_unknown_contact_tag_slugs'])) {
      $settings['auto_bounce_unknown_contact_tag_slugs'] = (string) $existing['settings']['auto_bounce_unknown_contact_tag_slugs'];
    }
    $unknownContactTagSlugs = !empty($settings['auto_bounce_unknown_contact_tag_slugs']) ? (string) $settings['auto_bounce_unknown_contact_tag_slugs'] : '';
    $unknownContactTagSlugParts = preg_split('/[\r\n,]+/', $unknownContactTagSlugs);
    if (!is_array($unknownContactTagSlugParts)) {
      $unknownContactTagSlugParts = [];
    }
    $unknownContactTagSlugParts = array_values(array_unique(array_filter(array_map(static function ($slug) {
      return sanitize_key((string) $slug);
    }, $unknownContactTagSlugParts))));
    $settings['auto_bounce_unknown_contact_tag_slugs'] = implode(', ', $unknownContactTagSlugParts);
    $settings['auto_bounce_last_run_ts'] = !empty($existing['settings']['auto_bounce_last_run_ts']) ? (int) $existing['settings']['auto_bounce_last_run_ts'] : 0;
    $settings = $this->getFrame()->core('eventDispatcher')->applyFilters('dashboard.mail_config.normalize_settings', $settings, $data, $configId, $existing);
    return [
      'name' => $name,
      'settings' => $settings,
      'is_default' => !empty($data['is_default']),
    ];
  }

  public function saveMailConfig(array $data, int $configId = 0): int
  {
    $this->ensureMailConfigStorage();
    global $wpdb;
    $normalized = $this->normalizeMailConfigPayload($data, $configId);
    $name = (string) ($normalized['name'] ?? '');
    $settings = is_array($normalized['settings'] ?? null) ? $normalized['settings'] : [];
    if ($name === '') {
      $this->setStatus('error');
      $this->setMessage(__('Config name is required.', 'ppsgen'));
      return 0;
    }
    $isDefault = !empty($normalized['is_default']);
    $row = [
      'name' => $name,
      'settings' => $this->_prepareValueToStore($settings, 'json'),
    ];
    if ($configId > 0) {
      $res = $wpdb->update($this->getMailConfigTable(), $row, ['id' => $configId]);
      if ($res === false) {
        $this->setStatus('error');
        $this->setMessage(__('Mail config was not updated.', 'ppsgen'));
        return 0;
      }
    } else {
      $res = $wpdb->insert($this->getMailConfigTable(), $row);
      if (empty($res)) {
        $this->setStatus('error');
        $this->setMessage(__('Mail config was not created.', 'ppsgen'));
        return 0;
      }
      $configId = (int) $wpdb->insert_id;
    }
    if ($isDefault || $this->getDefaultMailConfigId() <= 0) {
      $this->updateOption('default_mail_config_id', 'options', $configId, 0, 'dashboard', 'json');
    }
    $this->setStatus('success');
    $this->setMessage(__('Mail config is saved.', 'ppsgen'));
    return $configId;
  }

  public function testMailConfigConnection(array $data, int $configId = 0): array
  {
    $normalized = $this->normalizeMailConfigPayload($data, $configId);
    $settings = is_array($normalized['settings'] ?? null) ? $normalized['settings'] : [];
    $providerType = (string) ($settings['provider_type'] ?? 'custom_smtp_imap');
    $this->getFrame()->core('helper')->bootProMailProviders();
    $providerTestResult = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('dashboard.mail_config.test_connection', null, $normalized, $configId);
    if (is_array($providerTestResult)) {
      return $providerTestResult;
    }
    if ($this->isProMailConfigProvider($providerType)) {
      $providerLabel = $this->getMailConfigProviderLabel($providerType);
      return [
        'status' => 'error',
        'message' => sprintf(
          __('%s integration is available in the PRO version. Save this config as a placeholder here, then complete provider connection in PRO.', 'ppsgen'),
          $providerLabel
        ),
        'smtp' => ['status' => 'info', 'message' => __('Provider-specific sending is handled in PRO.', 'ppsgen')],
        'imap' => ['status' => 'info', 'message' => in_array($providerType, ['google_workspace', 'microsoft_365', 'zoho_mail', 'fastmail'], true) ? __('Mailbox reading and thread sync are handled in PRO.', 'ppsgen') : __('This provider is send-only in PRO.', 'ppsgen')],
      ];
    }
    $mailMethod = (string) ($settings['mail_method'] ?? 'mail');
    if ($mailMethod !== 'smtp') {
      return [
        'status' => 'success',
        'message' => __('PHP mail() is selected. SMTP / IMAP connection test is not required for this config.', 'ppsgen'),
        'smtp' => ['status' => 'info', 'message' => __('SMTP is disabled for this config.', 'ppsgen')],
        'imap' => ['status' => 'info', 'message' => __('IMAP is disabled for this config.', 'ppsgen')],
      ];
    }
    $smtpResult = $this->testSmtpConnection($settings);
    $imapResult = $this->testImapConnection($settings);
    $isSuccess = ($smtpResult['status'] ?? 'error') === 'success' && ($imapResult['status'] ?? 'error') === 'success';
    return [
      'status' => $isSuccess ? 'success' : 'error',
      'message' => $isSuccess
        ? __('SMTP and IMAP connections are working correctly.', 'ppsgen')
        : __('Connection check finished with errors. See details below.', 'ppsgen'),
      'smtp' => $smtpResult,
      'imap' => $imapResult,
    ];
  }

  protected function testSmtpConnection(array $settings): array
  {
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
      $phpmailerDir = trailingslashit(ABSPATH . WPINC . '/PHPMailer');
      if (file_exists($phpmailerDir . 'Exception.php')) {
        require_once $phpmailerDir . 'Exception.php';
      }
      if (file_exists($phpmailerDir . 'PHPMailer.php')) {
        require_once $phpmailerDir . 'PHPMailer.php';
      }
      if (file_exists($phpmailerDir . 'SMTP.php')) {
        require_once $phpmailerDir . 'SMTP.php';
      }
    }
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
      return ['status' => 'error', 'message' => __('PHPMailer is not available.', 'ppsgen')];
    }
    $host = (string) ($settings['smtp_server_name'] ?? '');
    $user = (string) ($settings['smtp_login'] ?? '');
    $pass = (string) ($settings['smtp_password'] ?? '');
    $port = (int) ($settings['smtp_port'] ?? 0);
    $secure = !empty($settings['smtp_secure']) ? (string) $settings['smtp_secure'] : 'ssl';
    $timeout = max(5, (int) ($settings['smtp_timeout'] ?? 10));
    $debugConfig = $this->formatMailConfigTestDebug('smtp', [
      'host' => $host,
      'login' => $user,
      'password_set' => $pass !== '',
      'port' => $port,
      'secure' => $secure,
      'timeout' => $timeout,
      'provider_type' => (string) ($settings['provider_type'] ?? ''),
      'provider_debug_type' => (string) ($settings['provider_debug_type'] ?? ''),
      'mail_method' => (string) ($settings['mail_method'] ?? ''),
    ]);
    if ($host === '' || $user === '' || $pass === '' || $port <= 0) {
      return [
        'status' => 'error',
        'message' => __('SMTP settings are incomplete.', 'ppsgen'),
        'debug' => $debugConfig,
      ];
    }
    try {
      $phpmailer = new \PHPMailer\PHPMailer\PHPMailer(true);
      $phpmailer->isSMTP();
      $phpmailer->Host = $host;
      $phpmailer->SMTPAuth = true;
      $phpmailer->Username = $user;
      $phpmailer->Password = $pass;
      $phpmailer->Port = $port;
      $phpmailer->SMTPSecure = $secure;
      $phpmailer->Timeout = $timeout;
      $debugLines = [];
      $phpmailer->SMTPDebug = 2;
      $phpmailer->Debugoutput = function ($str, $level) use (&$debugLines) {
        $line = trim(wp_strip_all_tags((string) $str));
        if ($line === '') {
          return;
        }
        $debugLines[] = '[L' . (int) $level . '] ' . $line;
      };
      $connected = $phpmailer->smtpConnect();
      if ($connected) {
        $phpmailer->smtpClose();
        return [
          'status' => 'success',
          'message' => sprintf(__('SMTP connected successfully to %1$s:%2$d.', 'ppsgen'), $host, $port),
          'debug' => $this->appendMailConfigDebugTranscript($debugConfig, $debugLines),
        ];
      }
      $smtpError = method_exists($phpmailer->getSMTPInstance(), 'getError') ? $phpmailer->getSMTPInstance()->getError() : [];
      $errorCode = !empty($smtpError['smtp_code']) ? ' #' . $smtpError['smtp_code'] : '';
      $errorMessage = !empty($phpmailer->ErrorInfo) ? $phpmailer->ErrorInfo : __('SMTP connection failed.', 'ppsgen');
      return [
        'status' => 'error',
        'message' => $errorMessage . $errorCode,
        'debug' => $this->appendMailConfigDebugTranscript($debugConfig, $debugLines),
      ];
    } catch (\Exception $e) {
      $code = $e->getCode() ? ' #' . $e->getCode() : '';
      return [
        'status' => 'error',
        'message' => $e->getMessage() . $code,
        'debug' => $debugConfig,
      ];
    }
  }

  protected function testImapConnection(array $settings): array
  {
    if (!function_exists('imap_open')) {
      return ['status' => 'error', 'message' => __('IMAP extension is not available on this server.', 'ppsgen')];
    }
    $host = (string) ($settings['imap_server_name'] ?? '');
    $user = (string) ($settings['smtp_login'] ?? '');
    $pass = (string) ($settings['smtp_password'] ?? '');
    $port = (int) ($settings['imap_port'] ?? 0);
    $secure = !empty($settings['smtp_secure']) ? (string) $settings['smtp_secure'] : 'ssl';
    $debugConfig = $this->formatMailConfigTestDebug('imap', [
      'host' => $host,
      'login' => $user,
      'password_set' => $pass !== '',
      'port' => $port,
      'secure' => $secure,
      'provider_type' => (string) ($settings['provider_type'] ?? ''),
      'provider_debug_type' => (string) ($settings['provider_debug_type'] ?? ''),
      'mail_method' => (string) ($settings['mail_method'] ?? ''),
    ]);
    if ($host === '' || $user === '' || $pass === '' || $port <= 0) {
      return [
        'status' => 'error',
        'message' => __('IMAP settings are incomplete.', 'ppsgen'),
        'debug' => $debugConfig,
      ];
    }
    $mailbox = sprintf('{%s:%d/imap/%s}INBOX', $host, $port, $secure);
    imap_timeout(IMAP_OPENTIMEOUT, 20);
    imap_timeout(IMAP_READTIMEOUT, 20);
    $imap = @imap_open($mailbox, $user, $pass);
    if (!$imap) {
      $error = function_exists('imap_last_error') ? (string) imap_last_error() : '';
      return [
        'status' => 'error',
        'message' => $error !== '' ? $error : __('IMAP connection failed.', 'ppsgen'),
        'debug' => $debugConfig . ' | ' . sprintf(__('Mailbox: %s', 'ppsgen'), $mailbox),
      ];
    }
    imap_close($imap);
    return [
      'status' => 'success',
      'message' => sprintf(__('IMAP connected successfully to %1$s:%2$d.', 'ppsgen'), $host, $port),
      'debug' => $debugConfig . ' | ' . sprintf(__('Mailbox: %s', 'ppsgen'), $mailbox),
    ];
  }

  public function deleteMailConfig(int $configId): bool
  {
    $this->ensureMailConfigStorage();
    global $wpdb;
    if ($configId <= 0) {
      return false;
    }
    $deleted = $wpdb->delete($this->getMailConfigTable(), ['id' => $configId], ['%d']);
    if ($deleted === false) {
      return false;
    }
    if ($this->getDefaultMailConfigId() === $configId) {
      $this->ensureDefaultMailConfigId();
    }
    return true;
  }

  public function getResolvedMailConfigData(int $configId = 0): array
  {
    $this->ensureMailConfigStorage();
    $resolvedId = $configId > 0 ? $configId : $this->ensureDefaultMailConfigId();
    if ($resolvedId > 0) {
      $row = $this->getMailConfigById($resolvedId);
      if (!empty($row)) {
        return [
          'id' => (int) $row['id'],
          'name' => (string) ($row['name'] ?? ''),
          'settings' => is_array($row['settings']) ? $row['settings'] : $this->getMailConfigDefaultSettings(),
          'source' => 'table',
        ];
      }
    }
    return [
      'id' => 0,
      'name' => __('Legacy Mail Settings', 'ppsgen'),
      'settings' => $this->getLegacyMailConfigSettings(),
      'source' => 'legacy',
    ];
  }

  public function updateResolvedMailConfigSettings(int $configId, array $settings): bool
  {
    global $wpdb;
    if ($configId <= 0) {
      foreach ($settings as $key => $value) {
        $this->updateOption((string) $key, 'options', $value, 0, 'dashboard', 'json');
      }
      return true;
    }
    $row = $this->getMailConfigById($configId);
    if (empty($row)) {
      return false;
    }
    $merged = wp_parse_args($settings, is_array($row['settings']) ? $row['settings'] : []);
    $merged = $this->prepareMailConfigSettingsForRuntime($merged, $configId);
    $updated = $wpdb->update($this->getMailConfigTable(), [
      'settings' => $this->_prepareValueToStore($merged, 'json'),
    ], ['id' => $configId], ['%s'], ['%d']);
    return $updated !== false;
  }

  protected function formatMailConfigTestDebug(string $type, array $data): string
  {
    $parts = [
      strtoupper($type),
      sprintf(__('provider: %s', 'ppsgen'), (string) ($data['provider_debug_type'] ?? $data['provider_type'] ?? '')),
      sprintf(__('mode: %s', 'ppsgen'), (string) ($data['mail_method'] ?? '')),
      sprintf(__('host: %s', 'ppsgen'), (string) ($data['host'] ?? '')),
      sprintf(__('login: %s', 'ppsgen'), (string) ($data['login'] ?? '')),
      sprintf(__('password: %s', 'ppsgen'), !empty($data['password_set']) ? __('set', 'ppsgen') : __('empty', 'ppsgen')),
      sprintf(__('port: %d', 'ppsgen'), (int) ($data['port'] ?? 0)),
      sprintf(__('encryption: %s', 'ppsgen'), (string) ($data['secure'] ?? '')),
    ];
    if (isset($data['timeout'])) {
      $parts[] = sprintf(__('timeout: %d', 'ppsgen'), (int) $data['timeout']);
    }
    return implode(' | ', $parts);
  }

  protected function appendMailConfigDebugTranscript(string $debugConfig, array $debugLines): string
  {
    $debugLines = array_values(array_filter(array_map('trim', $debugLines)));
    if (empty($debugLines)) {
      return $debugConfig;
    }
    return $debugConfig . ' | ' . implode(' | ', array_slice($debugLines, -6));
  }

  public function getResolvedMailConfigRemaining(int $configId = 0): array
  {
    $config = $this->getResolvedMailConfigData($configId);
    $settings = is_array($config['settings'] ?? null) ? $config['settings'] : [];
    $dailyLimit = max(0, (int) ($settings['mail_daily_limit'] ?? 300));
    $resetTs = (int) ($settings['mail_reset_ts'] ?? 0);
    $now = time();
    if ($resetTs <= 0 || $now >= $resetTs) {
      $settings['mail_remaining'] = $dailyLimit;
      $settings['mail_reset_ts'] = $now + DAY_IN_SECONDS;
      $this->updateResolvedMailConfigSettings((int) ($config['id'] ?? 0), [
        'mail_remaining' => $settings['mail_remaining'],
        'mail_reset_ts' => $settings['mail_reset_ts'],
      ]);
    } else {
      $settings['mail_remaining'] = max(0, (int) ($settings['mail_remaining'] ?? $dailyLimit));
    }
    $config['settings'] = $settings;
    return $config;
  }

  public function consumeResolvedMailConfigLimit(int $configId = 0): array
  {
    $config = $this->getResolvedMailConfigRemaining($configId);
    $settings = is_array($config['settings'] ?? null) ? $config['settings'] : [];
    $remaining = max(0, (int) ($settings['mail_remaining'] ?? 0));
    $newRemaining = max(0, $remaining - 1);
    $settings['mail_remaining'] = $newRemaining;
    $this->updateResolvedMailConfigSettings((int) ($config['id'] ?? 0), ['mail_remaining' => $newRemaining]);
    $config['settings'] = $settings;
    return $config;
  }

  public function hasSmtpMailConfigs(): bool
  {
    $this->ensureMailConfigStorage();
    foreach ($this->getMailConfigs() as $row) {
      if (($row['settings']['mail_method'] ?? 'mail') === 'smtp') {
        return true;
      }
    }
    return ($this->getLegacyMailConfigSettings()['mail_method'] ?? 'mail') === 'smtp';
  }

  public function getBounceMailConfigs(): array
  {
    $this->ensureMailConfigStorage();
    $configs = [];
    foreach ($this->getMailConfigs() as $row) {
      $settings = is_array($row['settings'] ?? null) ? $row['settings'] : [];
      if (($settings['mail_method'] ?? 'mail') !== 'smtp' && !$this->mailConfigSupportsAutoBounce($row)) {
        continue;
      }
      $configs[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'settings' => $settings,
        'source' => 'table',
      ];
    }
    if (empty($configs)) {
      $legacy = $this->getLegacyMailConfigSettings();
      if (($legacy['mail_method'] ?? 'mail') === 'smtp') {
        $configs[] = [
          'id' => 0,
          'name' => __('Legacy Mail Settings', 'ppsgen'),
          'settings' => $legacy,
          'source' => 'legacy',
        ];
      }
    }
    return $configs;
  }

  public function mailConfigSupportsAutoBounce(array $mailConfig): bool
  {
    $settings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
    $supports = (($settings['mail_method'] ?? 'mail') === 'smtp');
    $supports = (bool) $this->getFrame()->core('eventDispatcher')->applyFilters('dashboard.mail_config.supports_auto_bounce', $supports, $mailConfig, $settings);
    if ($supports) {
      return true;
    }
    $providerType = sanitize_key((string) ($settings['provider_type'] ?? ''));
    if ($providerType === '' || $providerType === 'custom_smtp_imap' || !defined('PPSGEN_PRO_PLUGIN_DIR')) {
      return false;
    }
    if (!class_exists('MailProviderManagerPpsgen')) {
      $contractPath = PPSGEN_PRO_PLUGIN_DIR . 'core/api/MailProviderContract.php';
      $abstractPath = PPSGEN_PRO_PLUGIN_DIR . 'core/api/AbstractMailProvider.php';
      $managerPath = PPSGEN_PRO_PLUGIN_DIR . 'core/api/MailProviderManager.php';
      if (!class_exists('MailProviderContractPpsgen') && file_exists($contractPath)) {
        require_once $contractPath;
      }
      if (!class_exists('AbstractMailProviderPpsgen') && file_exists($abstractPath)) {
        require_once $abstractPath;
      }
      if (file_exists($managerPath)) {
        require_once $managerPath;
      }
    }
    $manager = class_exists('MailProviderManagerPpsgen') ? $this->getFrame()->core('MailProviderManager') : null;
    if (!$manager || !method_exists($manager, 'getProviderCapabilities')) {
      return false;
    }
    $capabilities = $manager->getProviderCapabilities($providerType);
    return !empty($capabilities['can_auto_process_mailbox']);
  }

  public function getAutoBounceMailConfigs(int $timestamp = 0): array
  {
    $timestamp = $timestamp > 0 ? $timestamp : time();
    $configs = [];
    foreach ($this->getBounceMailConfigs() as $mailConfig) {
      $settings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
      if (!$this->mailConfigSupportsAutoBounce($mailConfig) || empty($settings['auto_bounce_enabled'])) {
        continue;
      }
      $intervalMinutes = max(1, (int) ($settings['auto_bounce_interval'] ?? 15));
      $lastRunTs = (int) ($settings['auto_bounce_last_run_ts'] ?? 0);
      if ($lastRunTs > 0 && ($lastRunTs + ($intervalMinutes * MINUTE_IN_SECONDS)) > $timestamp) {
        continue;
      }
      $configs[] = $mailConfig;
    }
    return $configs;
  }

  public function touchAutoBounceMailConfigRun(int $configId, int $timestamp = 0): bool
  {
    $timestamp = $timestamp > 0 ? $timestamp : time();
    return $this->updateResolvedMailConfigSettings($configId, ['auto_bounce_last_run_ts' => $timestamp]);
  }

  public function activateModule($module)
  {
    if (!empty($module) && !in_array($module, ['dashboard', 'builder', 'contact'])) {
      global $wpdb;
      $moduleStatus = $wpdb->get_row("SELECT active, available, type FROM {$wpdb->prefix}ppsgen_modules WHERE " . $wpdb->prepare(' slug = %s ', [$module]), ARRAY_A);
      if (!empty($moduleStatus)) {
        if (empty($moduleStatus['available']) || !empty($moduleStatus['active'])) {
          return false;
        }
        if (!empty($moduleStatus['available']) && empty($moduleStatus['active'])) {
          $parent = $wpdb->get_var($wpdb->prepare("SELECT parent FROM {$wpdb->prefix}ppsgen_modules WHERE slug = %s", $module));
          if (!empty($parent)) {
            $res = $wpdb->update("{$wpdb->prefix}ppsgen_modules", ['active' => 1], ['slug' => $parent]);
          }
          return $res = $wpdb->update("{$wpdb->prefix}ppsgen_modules", ['active' => 1], ['slug' => $module]);
        }
      }
    }
    return false;
  }

  public function deactivateModule($module)
  {
    if (!empty($module) && !in_array($module, ['dashboard', 'builder', 'contact'])) {
      global $wpdb;
      $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ppsgen_modules SET active = 0 WHERE parent = %s", $module));
      return $res = $wpdb->update("{$wpdb->prefix}ppsgen_modules", ['active' => 0], ['slug' => $module]);
    }
    return false;
  }

  public function saveFeedbackNoticeAction(string $action): bool
  {
    $allowed = ['submit', 'already', 'remind'];
    if (empty($action) || !in_array($action, $allowed, true)) {
      return false;
    }
    $now = current_time('timestamp');
    switch ($action) {
      case 'submit':
        update_option('ppsgen_feedback_submit', $now, false);
        return true;
      case 'already':
        update_option('ppsgen_feedback_already', $now, false);
        return true;
      case 'remind':
        update_option('ppsgen_feedback_remind', $now + WEEK_IN_SECONDS, false);
        return true;
    }
    return false;
  }

  public function isProAddonActive(): bool
  {
    if (defined('PPSGEN_PRO_PLUGIN_NAME') || class_exists('FrameProPpsgen')) {
      return true;
    }
    if (!function_exists('is_plugin_active')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    return function_exists('is_plugin_active') && is_plugin_active('popup-by-supsystic-pro/popup-by-supsystic-pro.php');
  }

  // Renewal reminder in the grace period and the frozen (expired) license state
  public function getProLicenseNoticeData(): array
  {
    if (!function_exists('ppsgen_get_pro_license_info')) {
      return [];
    }
    $info = ppsgen_get_pro_license_info();
    $dateFormat = (string) get_option('date_format', 'Y-m-d');
    if ($info['state'] === 'active') {
      return $this->getProLicenseExpiryNoticeData($info, $dateFormat);
    }
    if (!in_array($info['state'], ['grace', 'expired'], true)) {
      return [];
    }
    return [
      'state' => $info['state'],
      'date_end' => date_i18n($dateFormat, strtotime($info['date_end'])),
      'grace_end' => date_i18n($dateFormat, strtotime($info['grace_end'])),
      'grace_days_left' => (int) $info['grace_days_left'],
      'item_locked' => $info['state'] === 'expired' && ppsgen_is_pro_locked_edit_page(),
      'renew_url' => ppsgen_get_pro_renew_url(),
      'license_url' => admin_url('admin.php?page=ppsgen&tab=license_overview'),
    ];
  }

  // 30, 14 and 7 days before the license ends: what will change if it lapses. Closing it hides it until the next milestone.
  protected function getProLicenseExpiryNoticeData(array $info, string $dateFormat): array
  {
    $dateEnd = strtotime((string) $info['date_end']);
    if (empty($dateEnd)) {
      return [];
    }
    $daysLeft = (int) floor(($dateEnd - strtotime(current_time('Y-m-d'))) / DAY_IN_SECONDS);
    $milestone = $daysLeft <= 7 ? 7 : ($daysLeft <= 14 ? 14 : ($daysLeft <= 30 ? 30 : 0));
    $milestoneKey = $info['date_end'] . '|' . $milestone;
    if ($milestone === 0 || $daysLeft < 0 || (string) get_option('ppsgen_license_expiry_notice_dismissed') === $milestoneKey) {
      return [];
    }
    global $wpdb;
    $count = function (string $table) use ($wpdb): int {
      return $this->ppsgenTableExists($table) ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_{$table}") : 0;
    };
    $eca = $this->getFrame()->getModule('eca');
    $parts = [];
    foreach ([
      [$count('popups'), _n('%d popup', '%d popups', $count('popups'), 'ppsgen')],
      [$count('forms'), _n('%d form', '%d forms', $count('forms'), 'ppsgen')],
    ] as [$number, $label]) {
      if ($number > 0) {
        $parts[] = sprintf($label, $number);
      }
    }
    $automations = $eca ? (int) $eca->getModel()->getAutomationLimitSummary()['total'] : 0;
    if ($automations > 0) {
      $parts[] = sprintf(_n('%d automation', '%d automations', $automations, 'ppsgen'), $automations);
    }
    return [
      'state' => 'expiring',
      'date_end' => date_i18n($dateFormat, $dateEnd),
      'days_left' => $daysLeft,
      'milestone' => $milestoneKey,
      'items' => empty($parts) ? __('your PRO items', 'ppsgen') : (count($parts) > 1 ? sprintf(__('%1$s and %2$s', 'ppsgen'), implode(', ', array_slice($parts, 0, -1)), end($parts)) : $parts[0]),
      'item_locked' => false,
      'renew_url' => ppsgen_get_pro_renew_url(),
      'license_url' => admin_url('admin.php?page=ppsgen&tab=license_overview'),
    ];
  }

  public function dismissProLicenseExpiryNotice(string $milestone): bool
  {
    return update_option('ppsgen_license_expiry_notice_dismissed', sanitize_text_field($milestone), false);
  }

  public function saveProOfferNoticeAction(string $action): bool
  {
    $allowed = ['submit', 'already', 'remind'];
    if (empty($action) || !in_array($action, $allowed, true)) {
      return false;
    }
    $now = current_time('timestamp');
    switch ($action) {
      case 'submit':
        update_option('ppsgen_pro_offer_submit', $now, false);
        return true;
      case 'already':
        update_option('ppsgen_pro_offer_already', $now, false);
        return true;
      case 'remind':
        update_option('ppsgen_pro_offer_remind', $now + WEEK_IN_SECONDS, false);
        return true;
    }
    return false;
  }

  public function getProOfferNoticeData(): array
  {
    $show = false;
    $offerUrl = 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=dashboard_pro';
    $startedAt = (int) get_option('ppsgen_pro_offer_started_at');
    $now = current_time('timestamp');
    if ($startedAt <= 0) {
      $startedAt = $now;
      update_option('ppsgen_pro_offer_started_at', $startedAt, false);
    }
    $daysUsing = max(1, (int) floor(($now - $startedAt) / DAY_IN_SECONDS));
    if (!$this->isProAddonActive()) {
      $submitAt = (int) get_option('ppsgen_pro_offer_submit');
      $alreadyAt = (int) get_option('ppsgen_pro_offer_already');
      $remindAt = (int) get_option('ppsgen_pro_offer_remind');
      $show = $daysUsing >= 1;
      if (!empty($submitAt) || !empty($alreadyAt)) {
        $show = false;
      }
      if (!empty($remindAt) && $now < $remindAt) {
        $show = false;
      }
    }
    return [
      'show' => $show,
      'days_using' => $daysUsing,
      'offer_url' => $offerUrl,
    ];
  }

  public function getFeedbackNoticeData(): array
  {
    $startedAt = (int) get_option('ppsgen_feedback_started_at');
    $now = current_time('timestamp');
    if ($startedAt <= 0) {
      $startedAt = $now;
      update_option('ppsgen_feedback_started_at', $startedAt, false);
    }
    $submitAt = (int) get_option('ppsgen_feedback_submit');
    $alreadyAt = (int) get_option('ppsgen_feedback_already');
    $remindAt = (int) get_option('ppsgen_feedback_remind');
    $dismissUntil = (int) get_option('ppsgen_feedback_dismiss');
    $daysUsing = max(1, (int) floor(($now - $startedAt) / DAY_IN_SECONDS));
    // Ask once, after the first real result (a live play or a first enquiry), never people who stay on Classic PopUp
    $firstResult = $this->getFirstResult();
    $shouldShow = $firstResult !== '' && !(function_exists('ppsgen_is_legacy_active') && ppsgen_is_legacy_active());
    if (!empty($submitAt) || !empty($alreadyAt)) {
      $shouldShow = false;
    }
    if (!empty($remindAt) && $now < $remindAt) {
      $shouldShow = false;
    }
    if (!empty($dismissUntil) && $now < $dismissUntil) {
      $shouldShow = false;
    }
    $reviewUrl = 'https://wordpress.org/support/plugin/popup-by-supsystic/reviews/#new-post';
    return [
      'show' => $shouldShow,
      'days_using' => $daysUsing,
      'first_result' => $firstResult,
      'review_url' => $reviewUrl,
    ];
  }

  // The first thing CES achieved on this site, in plain words, or '' when nothing has happened yet
  protected function getFirstResult(): string
  {
    global $wpdb;
    $frame = $this->getFrame();
    if ($frame->getModule('dashboard')) {
      foreach ($frame->getModule('dashboard')->getPlays()->getPlays() as $play) {
        if (($play['status'] ?? '') === 'live') {
          return sprintf(__('“%s” is live.', 'ppsgen'), (string) ($play['name'] ?? __('Your play', 'ppsgen')));
        }
      }
    }
    if ($this->ppsgenTableExists('form_submits') && (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_form_submits") > 0) {
      return __('Your forms are collecting enquiries.', 'ppsgen');
    }
    return '';
  }

  public function getModulesInfo()
  {
    $modulesList = [
      'dashboard' => [
        'default' => true,
        'is_ready' => true,
        'title' => 'Dashboard',
        'short_description' => 'Control center for modules, mail configs, and global plugin settings',
        'description' => 'The Dashboard is the main control panel of Supsystic. Manage enabled modules, configure mail delivery, access global options, and keep your CRM and email workflows organized from one admin area.',
        'highlights' => [
          __('One place to manage modules, options, and mail configs.', 'ppsgen'),
          __('Quick access to Contacts, Forms, Popups, and Email Campaigns & Automations.', 'ppsgen'),
          __('Built to keep the modules connected instead of splitting setup across multiple plugins.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-admin-home',
        'order' => 1,
      ],
      'contact' => [
        'default' => true,
        'is_ready' => true,
        'title' => 'Contacts (CRM)',
        'short_description' => 'Build contact profiles, custom fields, tags, and segmentation inside WordPress',
        'description' => 'Store and manage contact records directly in WordPress. Create custom fields, organize tags, review contact activity, and use this data across Forms, Popups, and Email Campaigns & Automations.',
        'highlights' => [
          __('Create and manage contact profiles with custom fields and tags.', 'ppsgen'),
          __('Use contact data for segmentation, automation entry rules, and follow-up logic.', 'ppsgen'),
          __('Prepare your CRM for richer WordPress and WooCommerce-connected data structures as your setup grows.', 'ppsgen'),
          __('Import, export, and grow a WordPress-based CRM without relying on external SaaS storage.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-admin-users',
        'order' => 2,
      ],
      'form' => [
        'default' => true,
        'is_ready' => true,
        'title' => 'Form Builder',
        'short_description' => 'Create lead capture, registration, login, and contact forms with CRM mapping',
        'description' => 'Build simple or advanced forms with field controls, validation, email notifications, styling options, and direct mapping into contact profiles. Forms are designed to capture leads and feed the rest of the system.',
        'highlights' => [
          __('Create custom forms using contact fields and reusable field logic.', 'ppsgen'),
          __('Send admin/contact notifications and capture submits in a dedicated panel.', 'ppsgen'),
          __('Support WooCommerce-oriented lead capture scenarios when combined with advanced CRM field types in PRO.', 'ppsgen'),
          __('Use forms for lead capture, registration, login/logout flows, and contact verification workflows.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-format-aside',
        'order' => 3,
      ],
      'popup' => [
        'default' => true,
        'is_ready' => true,
        'title' => 'Popup Builder',
        'short_description' => 'Launch targeted popups with triggers, behavior rules, and embedded forms',
        'description' => 'Design popups for promotions, lead capture, announcements, and conversion flows. Control when they appear with timing, scroll, exit intent, and visitor behavior rules, and combine them with embedded forms.',
        'highlights' => [
          __('Show or hide popups using behavioral and display conditions.', 'ppsgen'),
          __('Embed Supsystic forms directly inside popup flows for higher conversions.', 'ppsgen'),
          __('Track views, interactions, and popup submits from one system.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-testimonial',
        'order' => 4,
      ],
      'eca' => [
        'default' => false,
        'is_ready' => true,
        'title' => 'Email Campaigns & Automations',
        'short_description' => 'Send campaigns, run automations, and manage contact-driven email flows',
      'description' => 'Create one-time email campaigns, reusable templates, and automated campaigns powered by contact segmentation, tags, and field conditions. This module is fully integrated with the Contacts CRM and mail config system.',
        'highlights' => [
        __('Build email templates, campaigns, and flow-based follow-up logic.', 'ppsgen'),
          __('Trigger automation using contact tags, fields, and segmentation conditions.', 'ppsgen'),
          __('Process sending through queue-based workflows with event tracking and logs.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-email-alt',
        'order' => 5,
      ],
    ];
    $modulesList = $this->getFrame()->core('eventDispatcher')->applyFilters('dashboard.modules_list', $modulesList);
    return $modulesList;
  }

  public function getProModulesInfo(): array
  {
    return [
      'contactExt' => [
        'default' => false,
        'is_ready' => true,
        'is_pro_upsell' => true,
        'title' => 'Contacts (CRM)',
        'short_description' => 'Extend the CRM with advanced field types, richer profiles, and more structured contact data',
        'description' => 'Unlock advanced CRM field types and richer profile management for deeper segmentation and data collection. PRO expands the base Contacts module with additional field controls and more flexible data structures.',
        'highlights' => [
          __('Advanced contact field types such as files, time, color, address search and more (date, range and rating are free).', 'ppsgen'),
          __('WooCommerce-aware field types for products, product categories, product tags, and attributes.', 'ppsgen'),
          __('Richer customer profiles for segmentation, form mapping, and automation logic.', 'ppsgen'),
          __('Extended CRM flexibility for teams that need more than basic lead records.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-admin-users',
        'order' => 20,
      ],
      'formExt' => [
        'default' => false,
        'is_ready' => true,
        'is_pro_upsell' => true,
        'title' => 'Form Builder',
        'short_description' => 'Extend forms with richer field behavior and advanced workflows',
        'description' => 'PRO adds support for advanced CRM field types and custom code. Conditional logic is included in the free version.',
        'highlights' => [
          __('Use WooCommerce-connected CRM field types inside forms for product-interest and catalog-driven lead capture.', 'ppsgen'),
          __('Use advanced CRM field types directly inside form workflows.', 'ppsgen'),
          __('Create more dynamic lead capture, qualification, and submit flows.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-format-aside',
        'order' => 30,
      ],
      'popupExt' => [
        'default' => false,
        'is_ready' => true,
        'is_pro_upsell' => true,
        'title' => 'Popup Builder',
        'short_description' => 'Extend popups with advanced display logic, action rules, and conversion-focused behavior',
        'description' => 'PRO expands popup targeting and action control, giving you more precise visibility rules, conversion scenarios, and integrated tracking behavior for lead capture campaigns.',
        'highlights' => [
          __('Advanced conditions for showing or hiding popups.', 'ppsgen'),
          __('More precise trigger logic for targeted conversion campaigns.', 'ppsgen'),
          __('Custom form actions and richer integration/tracking scenarios.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-testimonial',
        'order' => 40,
      ],
      'files_list' => [
        'default' => false,
        'is_ready' => true,
        'is_pro_upsell' => true,
        'title' => 'Files',
        'short_description' => 'Manage uploaded files linked to contacts, forms, users, and submits',
        'description' => 'The Files module gives you a dedicated place to review, organize, and manage uploaded assets connected to CRM records and form activity. It is especially useful for upload-based workflows and support teams.',
        'highlights' => [
          __('Browse files attached to contacts, forms, users, and submits.', 'ppsgen'),
          __('Filter, inspect, download, and clean up uploaded files from one panel.', 'ppsgen'),
          __('Helpful for richer WooCommerce-related forms and product inquiry workflows that collect supporting documents.', 'ppsgen'),
          __('Useful for file upload fields, application flows, and support handoff scenarios.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-media-archive',
        'order' => 45,
      ],
      'ecaExt' => [
        'default' => false,
        'is_ready' => true,
        'is_pro_upsell' => true,
        'title' => 'Email Campaigns & Automations',
        'short_description' => 'Extend email campaigns with deeper logic, more flexibility, and unlimited automation scale',
      'description' => 'PRO removes important automation limits and adds more advanced entry and exit logic for campaigns and steps. This is built for teams that need deeper lifecycle flows and larger campaign structures.',
        'highlights' => [
        __('Additional entry and exit logic for campaigns and steps.', 'ppsgen'),
          __('Dynamic WooCommerce content blocks for email templates and step templates, including Woo Categories and Woo Products.', 'ppsgen'),
          __('Build product and category sections inside emails with images, titles, prices, descriptions, and call-to-action buttons.', 'ppsgen'),
        __('Unlimited numbers of campaigns and steps for larger lifecycle automation.', 'ppsgen'),
          __('More advanced contact journey control across campaigns and automations.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-email-alt',
        'order' => 50,
      ],
      'providers' => [
        'default' => false,
        'is_ready' => true,
        'is_pro_upsell' => true,
        'title' => 'Email Providers',
        'short_description' => 'Connect mailbox-style and API-based providers for delivery, inbox reading, and Email Chat workflows',
        'description' => 'PRO adds modern provider integrations for both send-only delivery and full mailbox-style communication. Use built-in SMTP / IMAP, connect full send-and-read mailbox providers, or combine a delivery provider with a separate mailbox config for reply reading in Email Chat.',
        'highlights' => [
          __('Built-in support for classic custom SMTP / IMAP mailbox workflows.', 'ppsgen'),
          __('Mailbox-style providers: Google Workspace, Zoho Mail, Fastmail, and Microsoft 365.', 'ppsgen'),
          __('Send-only API providers: SendGrid, Mailgun, Brevo, Postmark, SMTP2GO, Resend, and Amazon SES.', 'ppsgen'),
          __('Split delivery and reply-reading into separate configs when needed.', 'ppsgen'),
          __('Support real contact conversations, sent-message sync, reply flows, and provider-specific connection strategies.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-email-alt2',
        'order' => 55,
      ],
      'geolite' => [
        'default' => false,
        'is_ready' => true,
        'is_pro_upsell' => true,
        'title' => 'GeoLite',
        'short_description' => 'Standalone country-by-IP addon for local detection without external API calls',
        'description' => 'GeoLite PRO is a standalone addon that enables fast IP-to-country detection directly inside your environment. It is built for privacy-conscious conditional logic and location-aware conversion flows.',
        'highlights' => [
          __('Run IP-to-country detection locally without external API dependencies.', 'ppsgen'),
          __('Useful for popup targeting, localization logic, and geo-aware workflows.', 'ppsgen'),
          __('Designed for speed, privacy, and predictable availability.', 'ppsgen'),
        ],
        'icon' => 'dashicons dashicons-location-alt',
        'order' => 60,
      ],
    ];
  }

  public function getProModulesOfferHtml(): string
  {
    $url = 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=modules_pro';
    return '<div class="ppsgenProOfferNotice ppsgenProOfferNoticeTab">'
      . '<div class="ppsgenProOfferNoticeHero">'
      . '<div class="ppsgenProOfferNoticeEyebrow">' . esc_html__('Unlock every PRO module', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeBadges">'
      . '<span class="ppsgenProOfferNoticeBadge">CRM</span>'
      . '<span class="ppsgenProOfferNoticeBadge">FORM</span>'
      . '<span class="ppsgenProOfferNoticeBadge">POPUP</span>'
      . '<span class="ppsgenProOfferNoticeBadge">EMAIL AUTOMATIONS</span>'
      . '</div>'
      . '<h3 class="ppsgenProOfferNoticeTitle">' . esc_html__('Activate the full CES PRO stack', 'ppsgen') . '</h3>'
      . '<p class="ppsgenProOfferNoticeText">' . esc_html__('Get every premium module in one purchase: advanced CRM fields, WooCommerce-powered field types, files manager, GeoLite country targeting, email delivery services and unlimited automation power.', 'ppsgen') . '</p>'
      . '<div class="ppsgenProOfferNoticeCouponLabel">' . esc_html__('Yearly licence · 1 site', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeCoupon"><span><strong>$99</strong> / year</span></div>'
      . '<div class="ppsgenProOfferNoticeSubtext">' . esc_html__('$99 stays $99 when you renew · No contact-based pricing · 30-day money-back guarantee', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeActions">'
      . '<a class="ppsgenProOfferNoticeBtn ppsgenProOfferNoticeBtnPrimary" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Get PRO for $99 a year', 'ppsgen') . '</a>'
      . '</div>'
      . '</div>'
      . '<div class="ppsgenProOfferNoticeFeatures">'
      . '<div class="ppsgenProOfferNoticeFeaturesTitle">' . esc_html__('Included PRO modules', 'ppsgen') . '</div>'
      . '<ul class="ppsgenProOfferNoticeFeatureList">'
      . '<li>' . esc_html__('Contacts (CRM) PRO with advanced custom field types and richer profile data', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Form Builder PRO with richer field controls and custom code', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Popup Builder PRO with countdown timers, multi-step popups and country targeting', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Files manager for uploads linked to contacts, users, and submits', 'ppsgen') . '</li>'
        . '<li>' . esc_html__('Email Campaigns & Automations PRO with unlimited campaigns and deeper logic', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('WooCommerce field types: products, categories, tags, and attributes inside CRM fields', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Future-ready expansion for advanced field types in Email Campaigns', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('GeoLite PRO plus license updates and premium support', 'ppsgen') . '</li>'
      . '</ul>'
      . '</div>'
      . '</div>';
  }

  // Top bar health pill: how CES sends email right now
  public function getEmailSendingStatus(): array
  {
    $url = admin_url('admin.php?page=ppsgen&tab=dashboard_mail_configs');
    $config = $this->getMailConfigById($this->getDefaultMailConfigId());
    $settings = !empty($config['settings']) && is_array($config['settings']) ? $config['settings'] : $this->getLegacyMailConfigSettings();
    $providerType = (string) ($settings['provider_type'] ?? '');
    if ($providerType !== '' && $this->isProMailConfigProvider($providerType)) {
      return [
        'state' => 'ok',
        'label' => sprintf(__('Email sending: %s', 'ppsgen'), $this->getMailConfigProviderLabel($providerType)),
        'hint' => __('Emails are sent through your email delivery service.', 'ppsgen'),
        'url' => $url,
      ];
    }
    if (($settings['mail_method'] ?? 'mail') === 'smtp') {
      return [
        'state' => 'ok',
        'label' => __('Email sending: SMTP', 'ppsgen'),
        'hint' => __('Emails are sent through your own email account.', 'ppsgen'),
        'url' => $url,
      ];
    }
    return [
      'state' => 'warn',
      'label' => __('Email sending: website mail', 'ppsgen'),
      'hint' => __('Your website\'s own mail often lands in spam. Set up SMTP so enquiry alerts and follow-ups arrive.', 'ppsgen'),
      'url' => $url,
    ];
  }

  // Sending domain check (PDF: email deliverability is the major Happy Path risk): SPF and DMARC records of the
  // From domain, and a free mailbox (gmail.com…) sent through the website's own mail. Cached for 12 hours.
  public function getEmailDomainCheck(bool $refresh = false): array
  {
    $config = $this->getMailConfigById($this->getDefaultMailConfigId());
    $settings = !empty($config['settings']) && is_array($config['settings']) ? $config['settings'] : $this->getLegacyMailConfigSettings();
    $providerType = (string) ($settings['provider_type'] ?? '');
    $method = $providerType !== '' && $this->isProMailConfigProvider($providerType) ? 'provider' : ((($settings['mail_method'] ?? 'mail') === 'smtp') ? 'smtp' : 'mail');
    $from = sanitize_email((string) ($settings['mail_from_email'] ?? '')) ?: sanitize_email((string) get_bloginfo('admin_email'));
    $domain = strtolower((string) substr(strrchr($from, '@') ?: '', 1));
    $cached = get_transient('ppsgen_email_domain_check');
    if (!$refresh && is_array($cached) && ($cached['domain'] ?? '') === $domain && ($cached['method'] ?? '') === $method) {
      return $cached;
    }
    $items = [];
    $freeMailboxes = ['gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'outlook.com', 'hotmail.com', 'live.com', 'msn.com', 'icloud.com', 'me.com', 'aol.com', 'gmx.com', 'gmx.de', 'proton.me', 'protonmail.com', 'zoho.com', 'yandex.ru', 'yandex.com', 'mail.ru', 'ukr.net'];
    if ($domain === '') {
      $items[] = ['ok' => false, 'title' => __('No sender address', 'ppsgen'), 'text' => __('Set a "From" email address in your email settings.', 'ppsgen')];
    } elseif (in_array($domain, $freeMailboxes, true)) {
      $items[] = $method === 'mail'
        ? ['ok' => false, 'title' => sprintf(__('Sending as @%s from your website', 'ppsgen'), $domain), 'text' => sprintf(__('%s rejects or spams mail that claims to be from its addresses but comes from your website. Set up SMTP with that mailbox, or send from an address on your own domain.', 'ppsgen'), $domain)]
        : ['ok' => true, 'title' => sprintf(__('Sending through your @%s mailbox', 'ppsgen'), $domain), 'text' => __('The mailbox provider signs your emails, so no domain records are needed.', 'ppsgen')];
    } elseif (!function_exists('dns_get_record')) {
      $items[] = ['ok' => null, 'title' => __('Domain records could not be checked', 'ppsgen'), 'text' => __('This server cannot look up DNS records.', 'ppsgen')];
    } else {
      $txt = static function (string $host): ?array {
        $records = @dns_get_record($host, DNS_TXT);
        if ($records === false) {
          return null;
        }
        return array_map(static fn($r) => (string) ($r['txt'] ?? implode('', (array) ($r['entries'] ?? []))), (array) $records);
      };
      $root = $txt($domain);
      $spf = $root === null ? null : array_values(array_filter($root, static fn($t) => stripos(trim($t), 'v=spf1') === 0));
      if ($spf === null) {
        $items[] = ['ok' => null, 'key' => 'spf', 'title' => __('SPF record could not be checked', 'ppsgen'), 'text' => __('The DNS lookup failed. Try again later.', 'ppsgen')];
      } elseif (count($spf) === 0) {
        $items[] = ['ok' => false, 'key' => 'spf', 'title' => sprintf(__('No SPF record for %s', 'ppsgen'), $domain), 'text' => __('Without SPF, inboxes cannot tell your emails from fakes and often send them to spam. Ask your host or email provider for the SPF record to add to your domain\'s DNS.', 'ppsgen')];
      } elseif (count($spf) > 1) {
        $items[] = ['ok' => false, 'key' => 'spf', 'title' => sprintf(__('%s has more than one SPF record', 'ppsgen'), $domain), 'text' => __('Only one SPF record is allowed, so all of them are ignored. Merge them into one.', 'ppsgen')];
      } else {
        $items[] = ['ok' => true, 'key' => 'spf', 'title' => __('SPF record found', 'ppsgen'), 'text' => $spf[0]];
      }
      $dmarcTxt = $txt('_dmarc.' . $domain);
      $dmarc = $dmarcTxt === null ? [] : array_values(array_filter($dmarcTxt, static fn($t) => stripos(trim($t), 'v=DMARC1') === 0));
      $items[] = !empty($dmarc)
        ? ['ok' => true, 'key' => 'dmarc', 'title' => __('DMARC record found', 'ppsgen'), 'text' => $dmarc[0]]
        : ['ok' => false, 'key' => 'dmarc', 'title' => sprintf(__('No DMARC record for %s', 'ppsgen'), $domain), 'text' => __('Gmail and Yahoo expect a DMARC record from senders. A basic one is enough: a TXT record named _dmarc with the value v=DMARC1; p=none;', 'ppsgen')];
      if ($method === 'mail') {
        $items[] = ['ok' => null, 'key' => 'method', 'title' => __('Sent by your website\'s own mail', 'ppsgen'), 'text' => __('Even with SPF and DMARC, mail from a shared web server often lands in spam. SMTP through your email account is more reliable.', 'ppsgen')];
      }
    }
    $result = [
      'domain' => $domain,
      'method' => $method,
      'items' => $items,
      'problems' => count(array_filter($items, static fn($i) => $i['ok'] === false)),
      'checked_at' => current_time('timestamp'),
    ];
    set_transient('ppsgen_email_domain_check', $result, 12 * HOUR_IN_SECONDS);
    return $result;
  }

  protected function ppsgenTableExists(string $table): bool
  {
    global $wpdb;
    $name = $wpdb->prefix . 'ppsgen_' . $table;
    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $name)) === $name;
  }

  // Numbers and lists for the Home screen. Only reads data CES already stores.
  public function getHomeData(): array
  {
    global $wpdb;
    $prefix = $wpdb->prefix . 'ppsgen_';
    $frame = $this->getFrame();
    $now = current_time('timestamp');
    $weekAgo = date('Y-m-d H:i:s', $now - 7 * DAY_IN_SECONDS);
    $twoWeeksAgo = date('Y-m-d H:i:s', $now - 14 * DAY_IN_SECONDS);
    $isPro = $this->isProAddonActive();
    $data = [
      'is_pro' => $isPro,
      'modules' => [
        'popup' => (bool) $frame->getModule('popup'),
        'form' => (bool) $frame->getModule('form'),
        'contact' => (bool) $frame->getModule('contact'),
        'eca' => (bool) $frame->getModule('eca'),
      ],
      'cards' => [],
      'recent' => [],
      'attention' => [],
      'automations' => ['total' => 0, 'running' => 0],
    ];

    if ($data['modules']['form'] && $this->ppsgenTableExists('form_submits')) {
      $week = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}form_submits WHERE created_date >= %s", $weekAgo));
      $prev = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}form_submits WHERE created_date >= %s AND created_date < %s", $twoWeeksAgo, $weekAgo));
      $diff = $week - $prev;
      $data['cards'][] = [
        'label' => __('New enquiries', 'ppsgen'),
        'value' => number_format_i18n($week),
        'sub' => sprintf(__('%s vs previous 7 days', 'ppsgen'), ($diff >= 0 ? '+' : '') . number_format_i18n($diff)),
        'url' => admin_url('admin.php?page=ppsgen&tab=report_forms'),
      ];
      $rows = $wpdb->get_results(
        "SELECT s.id, s.form_id, s.contact_id, s.created_date, f.name AS form_name
        FROM {$prefix}form_submits AS s
        LEFT JOIN {$prefix}forms AS f ON f.id = s.form_id
        ORDER BY s.id DESC LIMIT 5",
        ARRAY_A
      );
      $contactModel = $data['modules']['contact'] ? $frame->getModule('contact')->getModel() : null;
      foreach ($rows ?: [] as $row) {
        $contactId = (int) $row['contact_id'];
        $email = '';
        $name = '';
        if ($contactModel && $contactId > 0) {
          $email = $contactModel->getContactFieldValueBySlug($contactId, 'email');
          $name = trim($contactModel->getContactFieldValueBySlug($contactId, 'first_name') . ' ' . $contactModel->getContactFieldValueBySlug($contactId, 'last_name'));
        }
        $data['recent'][] = [
          'name' => $name,
          'email' => $email,
          'source' => (string) ($row['form_name'] ?? ''),
          'when' => sprintf(__('%s ago', 'ppsgen'), human_time_diff(strtotime($row['created_date']), $now)),
          'url' => $contactId > 0 ? admin_url('admin.php?page=ppsgen&tab=contact_edit&id=' . $contactId) : admin_url('admin.php?page=ppsgen&tab=form_edit&id=' . (int) $row['form_id']),
        ];
      }
    }

    if ($data['modules']['contact'] && $this->ppsgenTableExists('contacts')) {
      $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}contacts WHERE deleted_at IS NULL");
      $week = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}contacts WHERE deleted_at IS NULL AND created_at >= %s", $weekAgo));
      $limitStatus = function_exists('ppsgen_get_contact_limit_status') ? ppsgen_get_contact_limit_status() : [];
      $data['cards'][] = [
        'label' => __('New contacts', 'ppsgen'),
        'value' => number_format_i18n($week),
        'sub' => !empty($limitStatus['applies'])
          ? sprintf(__('%1$s / %2$s on Free', 'ppsgen'), number_format_i18n($total), number_format_i18n($limitStatus['limit']))
          : sprintf(__('%s contacts in total', 'ppsgen'), number_format_i18n($total)),
        'url' => admin_url('admin.php?page=ppsgen&tab=contact_list'),
      ];
    }

    if ($data['modules']['eca'] && $this->ppsgenTableExists('eca_step_events')) {
      $opens = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}eca_step_events WHERE event_type = 'open' AND is_suspicious = 0 AND created_at >= %s", $weekAgo));
      $clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}eca_step_events WHERE event_type = 'click' AND is_suspicious = 0 AND created_at >= %s", $weekAgo));
      $data['cards'][] = [
        'label' => __('Emails opened', 'ppsgen'),
        'value' => number_format_i18n($opens),
        'sub' => sprintf(__('%s link clicks', 'ppsgen'), number_format_i18n($clicks)),
        'url' => admin_url('admin.php?page=ppsgen&tab=eca_step_events'),
      ];
    }

    if ($data['modules']['eca'] && $this->ppsgenTableExists('eca_chains')) {
      // Newsletters are one-off sends, not automations
      $limitSummary = $frame->getModule('eca')->getModel()->getAutomationLimitSummary();
      $data['automations'] = array_merge($data['automations'], $limitSummary);
    }

    $livePopups = 0;
    if ($data['modules']['popup'] && $this->ppsgenTableExists('popups')) {
      $popupIds = array_map('intval', (array) $wpdb->get_col("SELECT id FROM {$prefix}popups"));
      $livePopups = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}popups WHERE active = 1");
      $views = 0;
      $uniqueViews = 0;
      $conversions = 0;
      if (!empty($popupIds)) {
        $summaries = $frame->getModule('popup')->getModel()->getPopupStatsSummaryByIds($popupIds);
        foreach ($summaries as $summary) {
          $views += (int) ($summary['total_views'] ?? 0);
          $uniqueViews += (int) ($summary['unique_views'] ?? 0);
          $conversions += (int) ($summary['actions'] ?? 0) + (int) ($summary['form_submits'] ?? 0);
        }
      }
      // Same formula as the popup Stats tab: interactions per unique view
      $data['cards'][] = [
        'label' => __('Popup conversion', 'ppsgen'),
        'value' => $uniqueViews > 0 ? number_format_i18n($conversions / $uniqueViews * 100, 1) . '%' : '—',
        'sub' => sprintf(__('Seen %s times · all time', 'ppsgen'), number_format_i18n($views)),
        'url' => admin_url('admin.php?page=ppsgen&tab=report_popups'),
      ];
    }

    $limitStatus = function_exists('ppsgen_get_contact_limit_status') ? ppsgen_get_contact_limit_status() : [];
    if (!empty($limitStatus['warn'])) {
      if (function_exists('ppsgen_track_gateway')) {
        ppsgen_track_gateway('contact_limit');
      }
      $data['attention'][] = [
        'title' => sprintf(__('You’re at %1$s of %2$s contacts', 'ppsgen'), number_format_i18n($limitStatus['total']), number_format_i18n($limitStatus['limit'])),
        'text' => !empty($limitStatus['over'])
          ? sprintf(__('%s contacts are saved but can’t be emailed on Free. Automations and newsletters reach the first 500.', 'ppsgen'), number_format_i18n($limitStatus['over']))
          : __('New contacts are always saved. Free can email up to 500.', 'ppsgen'),
        'link' => __('See your options →', 'ppsgen'),
        'url' => admin_url('admin.php?page=ppsgen&tab=settings_pro'),
      ];
    }
    $emailStatus = $this->getEmailSendingStatus();
    if ($emailStatus['state'] !== 'ok' && ($data['modules']['form'] || $data['modules']['eca'])) {
      $data['attention'][] = [
        'title' => __('Emails are sent with your website\'s own mail', 'ppsgen'),
        'text' => __('This often lands in spam. Every enquiry is still saved in CES, but alerts and follow-ups may not arrive.', 'ppsgen'),
        'link' => __('Set up email sending →', 'ppsgen'),
        'url' => $emailStatus['url'],
      ];
    }
    if ($data['modules']['form'] || $data['modules']['eca']) {
      $domainCheck = $this->getEmailDomainCheck();
      $firstProblem = current(array_filter($domainCheck['items'], static fn($i) => $i['ok'] === false));
      if ($firstProblem) {
        $data['attention'][] = [
          'title' => $firstProblem['title'],
          'text' => $firstProblem['text'],
          'link' => __('Check your email domain →', 'ppsgen'),
          'url' => admin_url('admin.php?page=ppsgen&tab=dashboard_mail_configs'),
        ];
      }
    }
    if ($data['modules']['popup'] && $livePopups === 0) {
      $data['attention'][] = [
        'title' => __('No popup is live', 'ppsgen'),
        'text' => __('Visitors only see the forms you placed on pages yourself.', 'ppsgen'),
        'link' => __('Create a popup →', 'ppsgen'),
        'url' => admin_url('admin.php?page=ppsgen&tab=template_add&template_type=popup'),
      ];
    }
    if (!empty($data['automations']['held_ids'])) {
      $data['attention'][] = [
        'title' => sprintf(_n('%d automation is switched on but waiting', '%d automations are switched on but waiting', count($data['automations']['held_ids']), 'ppsgen'), count($data['automations']['held_ids'])),
        'text' => sprintf(ppsgen_automation_limit_text('home_held'), $data['automations']['running_name']),
        'link' => ppsgen_automation_limit_text('home_link'),
        'url' => ppsgen_automation_limit_url(),
      ];
    }
    if ($data['modules']['eca'] && $data['automations']['total'] > 0 && $data['automations']['running'] === 0) {
      $data['attention'][] = [
        'title' => __('No automation is running', 'ppsgen'),
        'text' => __('New contacts are saved, but nobody gets a follow-up email.', 'ppsgen'),
        'link' => __('Open automations →', 'ppsgen'),
        'url' => admin_url('admin.php?page=ppsgen&tab=eca_chains'),
      ];
    }
    return $data;
  }

  // Home "This month": the site's own numbers so far this month vs the same days of last month
  public function getMonthlySummary(): array
  {
    $now = current_time('timestamp');
    $thisStart = gmdate('Y-m-01 00:00:00', $now);
    $lastStart = gmdate('Y-m-01 00:00:00', strtotime('first day of last month', $now));
    // Same number of days, so the 3rd of the month is not compared with a whole month
    $lastEnd = min(strtotime($thisStart), strtotime($lastStart) + ($now + 1 - strtotime($thisStart)));
    return [
      'this_label' => date_i18n('F', $now),
      'last_label' => sprintf(__('%1$s 1–%2$s', 'ppsgen'), date_i18n('M', strtotime('first day of last month', $now)), min((int) gmdate('j', $now), (int) gmdate('t', strtotime('first day of last month', $now)))),
      'rows' => $this->countPeriodMetrics($thisStart, gmdate('Y-m-d H:i:s', $now + 1), $lastStart, gmdate('Y-m-d H:i:s', $lastEnd)),
    ];
  }

  // The same five numbers for two periods ([from, to) in site time)
  protected function countPeriodMetrics(string $thisFrom, string $thisTo, string $lastFrom, string $lastTo): array
  {
    global $wpdb;
    $p = $wpdb->prefix . 'ppsgen_';
    $metrics = [
      ['contacts', __('New contacts', 'ppsgen'), 'contacts', 'created_at', 'deleted_at IS NULL'],
      ['form', __('Form submissions', 'ppsgen'), 'form_submits', 'created_date', '1 = 1'],
      ['popup', __('Popup sign-ups and clicks', 'ppsgen'), 'popup_submits', 'created_at', '1 = 1'],
      ['eca', __('Emails sent by campaigns', 'ppsgen'), 'eca_flow_logs', 'run_at', "status IN ('success', 'success_finished')"],
      ['eca', __('Emails opened', 'ppsgen'), 'eca_step_events', 'created_at', "event_type = 'open' AND is_suspicious = 0"],
    ];
    $rows = [];
    foreach ($metrics as [$module, $label, $table, $dateColumn, $where]) {
      if (($module !== 'contacts' && !$this->getFrame()->getModule($module)) || !$this->ppsgenTableExists($table)) {
        continue;
      }
      $count = function (string $from, string $to) use ($wpdb, $p, $table, $dateColumn, $where): int {
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}{$table} WHERE {$where} AND {$dateColumn} >= %s AND {$dateColumn} < %s", $from, $to));
      };
      $this_month = $count($thisFrom, $thisTo);
      $last_month = $count($lastFrom, $lastTo);
      $rows[] = [
        'label' => $label,
        'this' => $this_month,
        'last' => $last_month,
        'change' => $last_month > 0 ? (int) round(($this_month - $last_month) / $last_month * 100) : null,
      ];
    }
    return $rows;
  }

  //=====================================
  //  MONTHLY REPORT EMAIL (PDF "Route 1": sent from the site itself, nothing leaves it)
  //=====================================

  public function getMonthlyReportSettings(): array
  {
    $settings = get_option('ppsgen_monthly_report', []);
    $settings = is_array($settings) ? $settings : [];
    return [
      'enabled' => !empty($settings['enabled']),
      'to' => is_email($settings['to'] ?? '') ? (string) $settings['to'] : (string) get_bloginfo('admin_email'),
      'last_sent' => (string) ($settings['last_sent'] ?? ''),
    ];
  }

  public function setMonthlyReportEnabled(bool $enabled): void
  {
    $settings = get_option('ppsgen_monthly_report', []);
    $settings = is_array($settings) ? $settings : [];
    $settings['enabled'] = $enabled;
    // Switching it on mid-month does not send last month's report straight away
    if ($enabled && empty($settings['last_sent'])) {
      $settings['last_sent'] = gmdate('Y-m', strtotime('first day of last month', current_time('timestamp')));
    }
    update_option('ppsgen_monthly_report', $settings, false);
  }

  // Last full month vs the month before, plus up to two tips from what the site is (not) using
  public function buildMonthlyReport(?int $now = null): array
  {
    $now = $now ?? current_time('timestamp');
    $monthStart = strtotime('first day of last month', strtotime(gmdate('Y-m-01', $now)));
    $monthEnd = strtotime(gmdate('Y-m-01', $now));
    $prevStart = strtotime('first day of last month', $monthStart);
    $rows = $this->countPeriodMetrics(gmdate('Y-m-d 00:00:00', $monthStart), gmdate('Y-m-d 00:00:00', $monthEnd), gmdate('Y-m-d 00:00:00', $prevStart), gmdate('Y-m-d 00:00:00', $monthStart));
    $eca = $this->getFrame()->getModule('eca');
    $runningAutomations = $eca ? (int) $eca->getModel()->getAutomationLimitSummary()['running'] : 0;
    $tips = [];
    $base = admin_url('admin.php?page=ppsgen');
    if ($this->getEmailSendingStatus()['state'] !== 'ok') {
      $tips[] = ['text' => __('Your emails go out through your website\'s own mail and may land in spam. Setting up SMTP takes about five minutes.', 'ppsgen'), 'url' => $base . '&tab=dashboard_mail_configs', 'link' => __('Set up email sending', 'ppsgen')];
    }
    $livePopups = 0;
    global $wpdb;
    if ($this->ppsgenTableExists('popups')) {
      $livePopups = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_popups WHERE active = 1");
    }
    if ($livePopups === 0) {
      $tips[] = ['text' => __('No popup is live, so visitors only see the forms you placed yourself. The "Get more enquiries" play sets one up in about three minutes.', 'ppsgen'), 'url' => $base . '&tab=plays', 'link' => __('Start a play', 'ppsgen')];
    }
    if ($eca && $runningAutomations === 0) {
      $tips[] = ['text' => __('No automation is running, so new contacts get no follow-up. A short welcome series keeps them warm.', 'ppsgen'), 'url' => $base . '&tab=plays', 'link' => __('Welcome new leads', 'ppsgen')];
    }
    return [
      'month' => date_i18n('F Y', $monthStart),
      'month_short' => date_i18n('F', $monthStart),
      'prev' => date_i18n('F', $prevStart),
      'month_key' => gmdate('Y-m', $monthStart),
      'rows' => $rows,
      'tips' => array_slice($tips, 0, 2),
      'url' => $base . '&tab=dashboard',
    ];
  }

  public function sendMonthlyReport(?string $to = null, ?int $now = null): bool
  {
    $report = $this->buildMonthlyReport($now);
    $to = $to ?: $this->getMonthlyReportSettings()['to'];
    $byKey = array_column($report['rows'], 'this', 'label');
    $contacts = (int) ($byKey[__('New contacts', 'ppsgen')] ?? 0);
    $submissions = (int) ($byKey[__('Form submissions', 'ppsgen')] ?? 0);
    $subject = sprintf(__('Your %1$s results: %2$s new contacts, %3$s form submissions', 'ppsgen'), $report['month_short'], number_format_i18n($contacts), number_format_i18n($submissions));
    $rowsHtml = '';
    foreach ($report['rows'] as $row) {
      $change = $row['change'] === null ? '—' : (($row['change'] > 0 ? '+' : '') . $row['change'] . '%');
      $color = $row['change'] === null ? '#888888' : ($row['change'] >= 0 ? '#1f8a4c' : '#b26a00');
      $rowsHtml .= '<tr><td style="padding:8px 12px;border-bottom:1px solid #eeeeee;">' . esc_html($row['label']) . '</td>'
        . '<td style="padding:8px 12px;border-bottom:1px solid #eeeeee;text-align:right;font-weight:bold;">' . esc_html(number_format_i18n($row['this'])) . '</td>'
        . '<td style="padding:8px 12px;border-bottom:1px solid #eeeeee;text-align:right;color:#888888;">' . esc_html(number_format_i18n($row['last'])) . '</td>'
        . '<td style="padding:8px 12px;border-bottom:1px solid #eeeeee;text-align:right;color:' . $color . ';">' . esc_html($change) . '</td></tr>';
    }
    $tipsHtml = '';
    foreach ($report['tips'] as $tip) {
      $tipsHtml .= '<p style="margin:0 0 10px;">' . esc_html($tip['text']) . ' <a href="' . esc_url($tip['url']) . '">' . esc_html($tip['link']) . ' &rarr;</a></p>';
    }
    $html = '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Arial,sans-serif;font-size:15px;line-height:1.5;color:#1c1f2e;max-width:560px;">'
      . '<h2 style="font-size:20px;margin:0 0 4px;">' . esc_html(sprintf(__('%s at a glance', 'ppsgen'), $report['month'])) . '</h2>'
      . '<p style="margin:0 0 16px;color:#5e6378;">' . esc_html(sprintf(__('%1$s, from Customer Engagement on %2$s.', 'ppsgen'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), wp_parse_url(home_url(), PHP_URL_HOST))) . '</p>'
      . '<table style="border-collapse:collapse;width:100%;margin:0 0 20px;"><tr><th></th>'
      . '<th style="padding:8px 12px;text-align:right;color:#5e6378;font-weight:normal;">' . esc_html($report['month_short']) . '</th>'
      . '<th style="padding:8px 12px;text-align:right;color:#5e6378;font-weight:normal;">' . esc_html($report['prev']) . '</th><th></th></tr>' . $rowsHtml . '</table>'
      . ($tipsHtml !== '' ? '<h3 style="font-size:16px;margin:0 0 8px;">' . esc_html__('Ideas for this month', 'ppsgen') . '</h3>' . $tipsHtml : '')
      . '<p style="margin:20px 0;"><a href="' . esc_url($report['url']) . '" style="display:inline-block;padding:10px 18px;background:#3f51b5;color:#ffffff;text-decoration:none;border-radius:6px;">' . esc_html__('Open your dashboard', 'ppsgen') . '</a></p>'
      . '<p style="margin:0;color:#888888;font-size:12px;">' . esc_html__('You get this because the monthly summary is switched on in Customer Engagement (Home › This month). Untick it there to stop.', 'ppsgen') . '</p>'
      . '</div>';
    $helper = $this->getFrame()->core('helper');
    $sent = (bool) $helper->sendEmail($to, '', '', $subject, $html, ['Content-Type: text/html; charset=UTF-8'], [], $this->getDefaultMailConfigId());
    return $sent;
  }

  // Daily cron: on or after the 1st, send last month's report once
  public function maybeSendMonthlyReport(): bool
  {
    $settings = $this->getMonthlyReportSettings();
    $due = gmdate('Y-m', strtotime('first day of last month', current_time('timestamp')));
    if (!$settings['enabled'] || $settings['last_sent'] === $due) {
      return false;
    }
    if (!$this->sendMonthlyReport()) {
      return false;
    }
    $stored = get_option('ppsgen_monthly_report', []);
    $stored = is_array($stored) ? $stored : [];
    $stored['last_sent'] = $due;
    update_option('ppsgen_monthly_report', $stored, false);
    return true;
  }

  // Reports › Popups / Forms: one row per item with the numbers from its Stats tab
  public function getReportRows(string $type): array
  {
    global $wpdb;
    $frame = $this->getFrame();
    if (!in_array($type, ['popup', 'form'], true) || !$frame->getModule($type) || !$this->ppsgenTableExists($type . 's')) {
      return [];
    }
    $items = $wpdb->get_results("SELECT id, name, active FROM {$wpdb->prefix}ppsgen_{$type}s ORDER BY active DESC, id DESC", ARRAY_A);
    if (empty($items)) {
      return [];
    }
    $ids = array_map('intval', array_column($items, 'id'));
    $model = $frame->getModule($type)->getModel();
    $summaries = $type === 'popup' ? $model->getPopupStatsSummaryByIds($ids) : $model->getFormStatsSummaryByIds($ids);
    $rows = [];
    foreach ($items as $item) {
      $id = (int) $item['id'];
      $rows[] = [
        'id' => $id,
        'name' => (string) $item['name'] !== '' ? (string) $item['name'] : __('Untitled', 'ppsgen'),
        'active' => !empty($item['active']),
        'stats' => $summaries[$id] ?? [],
        'edit_url' => admin_url('admin.php?page=ppsgen&tab=' . $type . '_edit&id=' . $id),
      ];
    }
    return $rows;
  }

  public function sendTestEmail(): array
  {
    $user = wp_get_current_user();
    $to = !empty($user->user_email) ? (string) $user->user_email : (string) get_bloginfo('admin_email');
    $configId = $this->getDefaultMailConfigId();
    $subject = sprintf(__('Test email from %s', 'ppsgen'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
    $message = '<p>' . esc_html__('This is a test email from the Customer Engagement System. If you can read it, enquiry alerts, thank-you emails and automations can reach you too.', 'ppsgen') . '</p>'
      . '<p>' . esc_html__('Tip: if this landed in spam, set up SMTP in Settings › Email sending.', 'ppsgen') . '</p>';
    $helper = $this->getFrame()->core('helper');
    $sent = (bool) $helper->sendEmail($to, '', '', $subject, $message, [], [], $configId);
    $error = $sent ? '' : $helper->getLastMailProviderError();
    $result = [
      'ok' => $sent,
      'to' => $to,
      'time' => current_time('timestamp'),
      'error' => $error,
    ];
    update_option('ppsgen_mail_test_last', $result, false);
    $result['message'] = $sent
      ? sprintf(__('Test email sent to %s. Check the inbox (and the spam folder).', 'ppsgen'), $to)
      : ($error !== '' ? $error : __('The email could not be sent. Check the settings of your sending method.', 'ppsgen'));
    return $result;
  }

  public function getLastTestEmail(): array
  {
    $last = get_option('ppsgen_mail_test_last', []);
    return is_array($last) ? $last : [];
  }
}
