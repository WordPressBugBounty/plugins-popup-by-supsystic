<?php

if (!defined('ABSPATH')) {
  exit();
}

class EcaModelPpsgen extends BaseModelPpsgen
{
  private $redirectUrl = null;
  private string $ecaFlowCronLockValue = '';

  public function updateOptions($data, $group = '')
  {
    if (empty($data) || !is_array($data)) {
      return false;
    }
    foreach ($data as $slug => $module) {
      if (empty($module) || !is_array($module)) {
        continue;
      }
      foreach ($module as $keyOpt => $keyVal) {
        if ($slug === 'eca' && $keyOpt === 'eca_tracking_query_prefix') {
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

  //=====================================
  //            INSTALLER START
  //=====================================

  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $installer = $this->getFrame()->core('installer');
    if (!$installer->exist('ppsgen_eca_templates')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_templates';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `settings` TEXT NULL,
        `design` TEXT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
      $this->installDefaultTemplate();
    }
    if (!$installer->exist('ppsgen_eca_chains')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_chains';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `template_id` BIGINT(20) NOT NULL,
        `settings` TEXT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_eca_chain_step')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_chain_step';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `chain_id`  BIGINT(20) NOT NULL DEFAULT 0,
        `step_order` INT(11) NOT NULL DEFAULT 0,
        `name` VARCHAR(255) NOT NULL,
        `settings` TEXT NULL,
        `html` TEXT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `is_exit_chain`  TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_eca_fields')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_fields';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `owner_id` BIGINT(20) UNSIGNED NOT NULL,
        `owner_type` ENUM('template', 'chain_step') DEFAULT NULL,
        `parent_key` VARCHAR(255) NOT NULL,
        `fkey` VARCHAR(255) NOT NULL,
        `type` VARCHAR(255) NOT NULL,
        `settings` TEXT NULL,
        `order_index` INT(11) NOT NULL DEFAULT 0,
        `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
      $this->installDefaultTemplateFields();
    }
    if (!$installer->exist('ppsgen_eca_flow')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_flow';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `contact_id` BIGINT(20) UNSIGNED NOT NULL,
        `chain_id` BIGINT(20) UNSIGNED NOT NULL,
        `current_step_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `next_run_at` DATETIME NOT NULL,
        `status` ENUM('active','exited','finished','unsubscribed') NOT NULL DEFAULT 'active',
        `cron_status` ENUM('waiting','processing') NOT NULL DEFAULT 'waiting',
        `active` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `contact_id` (`contact_id`),
        KEY `chain_id` (`chain_id`),
        KEY `next_run_at` (`next_run_at`),
        KEY `status` (`status`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_eca_flow_logs')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_flow_logs';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `contact_id` BIGINT(20) UNSIGNED NOT NULL,
        `chain_id` BIGINT(20) UNSIGNED NOT NULL,
        `step_id` BIGINT(20) UNSIGNED NOT NULL,
        `flow_id` BIGINT(20) UNSIGNED NOT NULL,
        `run_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status` ENUM('success', 'skipped', 'error', 'entry', 'exit', 'condition_false', 'unsubscribed') NOT NULL DEFAULT 'success',
        `message` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `contact_id` (`contact_id`),
        KEY `chain_id` (`chain_id`),
        KEY `step_id` (`step_id`),
        KEY `run_at` (`run_at`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
      if (!$installer->exist('ppsgen_eca_step_events')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_step_events';
      $sql = "CREATE TABLE $tableName (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,      
          `flow_id` BIGINT(20) UNSIGNED NOT NULL,
          `contact_id` BIGINT(20) UNSIGNED NOT NULL,
          `chain_id` BIGINT(20) UNSIGNED NOT NULL,
          `step_id` BIGINT(20) UNSIGNED NOT NULL,
          `event_type` ENUM('open', 'click') NOT NULL,
          `clicked_url` VARCHAR(255) NULL,
          `ip_address` VARCHAR(45) NULL,
          `user_agent` VARCHAR(255) NULL,
          `referrer` VARCHAR(255) NULL,
          `is_suspicious` TINYINT(1) NOT NULL DEFAULT 0,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `flow_id` (`flow_id`),
          KEY `contact_id` (`contact_id`),
          KEY `chain_id` (`chain_id`),
          KEY `step_id` (`step_id`),
          KEY `event_type` (`event_type`),
          KEY `is_suspicious` (`is_suspicious`)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
      }
    if (!$installer->exist('ppsgen_eca_mail_queue')) {
      global $wpdb;
      $tableName = $wpdb->prefix . 'ppsgen_eca_mail_queue';
      $charset = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $tableName (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `email` VARCHAR(255) NOT NULL,
          `step_id` BIGINT(20) UNSIGNED NOT NULL,
          `flow_id` BIGINT(20) UNSIGNED NOT NULL,
          `chain_id` BIGINT(20) UNSIGNED NOT NULL,
          `contact_id` BIGINT(20) UNSIGNED NOT NULL,
          `locked_at` DATETIME NULL DEFAULT NULL,
          `lock_token` CHAR(36) NULL DEFAULT NULL,
          `attempts` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `locked_at` (`locked_at`),
          KEY `lock_token` (`lock_token`),
          KEY `step_id` (`step_id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_eca_contact_incoming_emails')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_contact_incoming_emails';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `contact_id` BIGINT(20) UNSIGNED NOT NULL,
        `mail_config_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `mailbox_hash` CHAR(32) NOT NULL,
        `mailbox_path` VARCHAR(255) NOT NULL,
        `imap_uid` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `message_id` VARCHAR(191) NULL,
        `from_email` VARCHAR(191) NOT NULL,
        `from_name` VARCHAR(255) NULL,
        `to_email` VARCHAR(191) NULL,
        `subject` VARCHAR(255) NULL,
        `received_at` DATETIME NOT NULL,
        `detected_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `mailbox_seen` TINYINT(1) NOT NULL DEFAULT 0,
        `handled_at` DATETIME NULL DEFAULT NULL,
        `handled_by_user_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_mailbox_uid` (`mail_config_id`, `mailbox_hash`, `imap_uid`),
        KEY `contact_id` (`contact_id`),
        KEY `mail_config_id` (`mail_config_id`),
        KEY `contact_handled` (`contact_id`, `handled_at`),
        KEY `contact_received` (`contact_id`, `received_at`),
        KEY `received_at` (`received_at`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_eca_conversions')) {
      $tableName = $wpdb->prefix . 'ppsgen_eca_conversions';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `contact_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `conversion_email` VARCHAR(191) NOT NULL DEFAULT '',
        `chain_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `step_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `flow_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `click_event_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `attribution_method` VARCHAR(40) NOT NULL DEFAULT 'none',
        `attribution_window_days` INT(11) NOT NULL DEFAULT 180,
        `conversion_key` VARCHAR(100) NOT NULL DEFAULT 'conversion',
        `external_id` VARCHAR(191) NOT NULL DEFAULT '',
        `status` VARCHAR(60) NOT NULL DEFAULT 'completed',
        `value` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
        `currency` VARCHAR(10) NOT NULL DEFAULT '',
        `source` VARCHAR(100) NOT NULL DEFAULT '',
        `product_id` VARCHAR(191) NOT NULL DEFAULT '',
        `product_name` VARCHAR(255) NOT NULL DEFAULT '',
        `clicked_url` TEXT NULL,
        `data_json` LONGTEXT NULL,
        `converted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `contact_id` (`contact_id`),
        KEY `conversion_email` (`conversion_email`),
        KEY `chain_id` (`chain_id`),
        KEY `step_id` (`step_id`),
        KEY `flow_id` (`flow_id`),
        KEY `click_event_id` (`click_event_id`),
        KEY `conversion_lookup` (`conversion_key`, `external_id`, `source`),
        KEY `status` (`status`),
        KEY `source` (`source`),
        KEY `converted_at` (`converted_at`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
  }

  public function maybeMigrate(): bool
  {
    $migrated = parent::maybeMigrate();
    $migratedSuspiciousColumn = $this->maybeMigrateStepEventsSuspiciousColumn();
    $migratedConversionsTable = false;
    if (!$this->getFrame()->core('installer')->exist('ppsgen_eca_conversions')) {
      $this->installTables();
      $migratedConversionsTable = true;
    }
    if (!$this->getFrame()->core('installer')->exist('ppsgen_eca_contact_incoming_emails')) {
      $this->installTables();
      return true;
    }
    return $migrated || $migratedSuspiciousColumn || $migratedConversionsTable;
  }

  protected function maybeMigrateStepEventsSuspiciousColumn(): bool
  {
    global $wpdb;
    $tableName = $wpdb->prefix . 'ppsgen_eca_step_events';
    if (!$this->getFrame()->core('installer')->exist('ppsgen_eca_step_events')) {
      return false;
    }
    $column = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$tableName} LIKE %s", 'is_suspicious'));
    if (!empty($column)) {
      return false;
    }
    $altered = $wpdb->query("ALTER TABLE {$tableName} ADD COLUMN `is_suspicious` TINYINT(1) NOT NULL DEFAULT 0 AFTER `referrer`, ADD KEY `is_suspicious` (`is_suspicious`)");
    return $altered !== false;
  }

  // The default email design is the FREE "Basic Light" template, read from the plugin's own checked package so the
  // two never drift apart; default-template.php (the same design) is only the fallback if the package can't be read
  private function getDefaultTemplateSeed(): array
  {
    require_once PPSGEN_PLUGIN_DIR . 'modules/template/package.php';
    $package = TemplatePackagePpsgen::readLocal('email_basic_light');
    if (!is_wp_error($package) && !empty($package['email_template'])) {
      $package = TemplatePackagePpsgen::placeholders($package);
      return [
        'template' => [
          'settings' => $package['email_template']['settings'] ?? '[]',
          'design' => $package['email_template']['design'] ?? '[]',
        ],
        'fields' => $package['email_template_fields'] ?? [],
      ];
    }
    $seedFile = __DIR__ . DIRECTORY_SEPARATOR . 'default-template.php';
    if (!is_file($seedFile)) {
      return [];
    }
    $seed = require $seedFile;
    return is_array($seed) ? $seed : [];
  }

  public function installDefaultTemplate()
  {
    global $wpdb;
    $seed = $this->getDefaultTemplateSeed();
    $data = $seed['template'] ?? [];
    if (empty($data) || !is_array($data)) {
      return;
    }
    $date = current_time('mysql');
    $data['id'] = 1;
    $data['name'] = 'Default Emails Campaign Template';
    $data['active'] = 1;
    $data['created_at'] = $date;
    $data['updated_at'] = $date;
    $wpdb->insert("{$wpdb->prefix}ppsgen_eca_templates", $data);
  }

  public function installDefaultTemplateFields()
  {
    global $wpdb;
    $seed = $this->getDefaultTemplateSeed();
    $fields = $seed['fields'] ?? [];
    if (empty($fields) || !is_array($fields)) {
      return;
    }
    $date = current_time('mysql');
    foreach ($fields as $field) {
      if (empty($field) || !is_array($field)) {
        continue;
      }
      $field['owner_id'] = 1;
      $field['owner_type'] = 'template';
      $field['created_date'] = $date;
      $field['updated_date'] = $date;
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_fields", $field);
    }
  }

  public function getTemplates(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'name', 'active', 'created_at', 'updated_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['name'])) {
      $whereParts[] = 'name LIKE %s';
      $params[] = $args['filters']['name'];
    }
    if (!empty($args['filters']['active'])) {
      $val = $args['filters']['active'] === 'x' ? 0 : 1;
      $whereParts[] = 'active = %d';
      $params[] = $val;
    }
    if (!empty($args['filters']['created_at'])) {
      $whereParts[] = 'DATE(created_at) = %s';
      $params[] = $args['filters']['created_at'];
    }
    if (!empty($args['filters']['updated_at'])) {
      $whereParts[] = 'DATE(updated_at) = %s';
      $params[] = $args['filters']['updated_at'];
    }
    $whereSql = implode(' AND ', $whereParts);
    $allowedOrderFields = $defaults['columns'];
    $orderBy = in_array($args['order_by'], $allowedOrderFields, true) ? $args['order_by'] : 'id';
    $orderDir = $args['order_dir'] === 'DESC' ? 'DESC' : 'ASC';
    $limit = (int) $args['per_page'];
    $offset = ($args['page'] - 1) * $limit;
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT id, name, active, created_at, updated_at
        FROM {$wpdb->prefix}ppsgen_eca_templates
        WHERE $whereSql
        GROUP BY id
        ORDER BY $orderBy $orderDir
        LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    // phpcs:enable

    $params = array_slice($params, 0, -2);
    $params[] = 9999999999999;

    $sql = "
        SELECT COUNT(DISTINCT id)
        FROM {$wpdb->prefix}ppsgen_eca_templates
        WHERE $whereSql
        LIMIT %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $total = $wpdb->get_var($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    return [
      'rows' => $rows,
      'total' => (int) $total,
    ];
  }

  public function getChains(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'name', 'active', 'created_at', 'updated_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['name'])) {
      $whereParts[] = 'name LIKE %s';
      $params[] = $args['filters']['name'];
    }
    if (!empty($args['filters']['active'])) {
      $val = $args['filters']['active'] === 'x' ? 0 : 1;
      $whereParts[] = 'active = %d';
      $params[] = $val;
    }
    if (!empty($args['filters']['created_at'])) {
      $whereParts[] = 'DATE(created_at) = %s';
      $params[] = $args['filters']['created_at'];
    }
    if (!empty($args['filters']['updated_at'])) {
      $whereParts[] = 'DATE(updated_at) = %s';
      $params[] = $args['filters']['updated_at'];
    }
    if (($args['type'] ?? '') === 'newsletter') {
      $whereParts[] = $this->getOnlyNewslettersSql('id');
    } elseif (($args['type'] ?? '') === 'automation') {
      $whereParts[] = $this->getExcludeNewslettersSql('id');
    }
    $whereSql = implode(' AND ', $whereParts);
    $allowedOrderFields = $defaults['columns'];
    $orderBy = in_array($args['order_by'], $allowedOrderFields, true) ? $args['order_by'] : 'id';
    $orderDir = $args['order_dir'] === 'DESC' ? 'DESC' : 'ASC';
    $limit = (int) $args['per_page'];
    $offset = ($args['page'] - 1) * $limit;
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT id, name, active, created_at, updated_at
        FROM {$wpdb->prefix}ppsgen_eca_chains
        WHERE $whereSql
        GROUP BY id
        ORDER BY $orderBy $orderDir
        LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    // phpcs:enable

    $params = array_slice($params, 0, -2);
    $params[] = 9999999999999;

    $sql = "
        SELECT COUNT(DISTINCT id)
        FROM {$wpdb->prefix}ppsgen_eca_chains
        WHERE $whereSql
        LIMIT %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $total = $wpdb->get_var($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    return [
      'rows' => $rows,
      'total' => (int) $total,
    ];
  }

  public function getChainStats($chainId): array
  {
    global $wpdb;

    $chainId = (int) $chainId;
    if (empty($chainId)) {
      return [];
    }

    $eventStats = $wpdb->get_row($wpdb->prepare(
      "SELECT
        COUNT(*) AS total_events,
        SUM(CASE WHEN event_type = 'open' THEN 1 ELSE 0 END) AS event_open,
        SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) AS event_click,
        COUNT(DISTINCT CASE WHEN event_type = 'open' THEN contact_id END) AS unique_event_open,
        COUNT(DISTINCT CASE WHEN event_type = 'open' AND is_suspicious = 1 THEN contact_id END) AS suspicious_unique_event_open,
        SUM(CASE WHEN event_type = 'open' AND is_suspicious = 1 THEN 1 ELSE 0 END) AS suspicious_event_open,
        SUM(CASE WHEN event_type = 'open' AND is_suspicious = 0 THEN 1 ELSE 0 END) AS live_event_open,
        SUM(CASE WHEN event_type = 'click' AND is_suspicious = 1 THEN 1 ELSE 0 END) AS suspicious_event_click,
        SUM(CASE WHEN event_type = 'click' AND is_suspicious = 0 THEN 1 ELSE 0 END) AS live_event_click
      FROM {$wpdb->prefix}ppsgen_eca_step_events
      WHERE chain_id = %d",
      $chainId
    ), ARRAY_A);
    $eventStats = is_array($eventStats) ? $eventStats : [];

    $stats = [
      'total' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d", $chainId)),
      'processing' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d AND status = 'active'", $chainId)),
      'finished' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d AND status = 'finished'", $chainId)),
      'exited' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d AND status = 'exited'", $chainId)),
      'unsubscribed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d AND status = 'unsubscribed'", $chainId)),
      'total_letters' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE chain_id = %d AND status = 'success'", $chainId)),
      'total_events' => (int) ($eventStats['total_events'] ?? 0),
      'event_open' => (int) ($eventStats['event_open'] ?? 0),
      'event_click' => (int) ($eventStats['event_click'] ?? 0),
      'unique_event_open' => (int) ($eventStats['unique_event_open'] ?? 0),
      'suspicious_unique_event_open' => (int) ($eventStats['suspicious_unique_event_open'] ?? 0),
      'suspicious_event_open' => (int) ($eventStats['suspicious_event_open'] ?? 0),
      'live_event_open' => (int) ($eventStats['live_event_open'] ?? 0),
      'suspicious_event_click' => (int) ($eventStats['suspicious_event_click'] ?? 0),
      'live_event_click' => (int) ($eventStats['live_event_click'] ?? 0),
    ];

    return array_merge($stats, $this->getPaidConversionStats('chain_id', $chainId));
  }

  protected function getPaidConversionStats(string $scopeColumn, int $scopeId): array
  {
    global $wpdb;

    $emptyStats = [
      'conversion_paid_total' => 0,
      'conversion_paid_contacts' => 0,
      'conversion_paid_emails' => 0,
      'conversion_paid_value_label' => '0',
    ];
    if (!in_array($scopeColumn, ['chain_id', 'step_id'], true) || empty($scopeId)) {
      return $emptyStats;
    }
    if (!$this->getFrame()->core('installer')->exist('ppsgen_eca_conversions')) {
      return $emptyStats;
    }

    $totalRow = $wpdb->get_row($wpdb->prepare(
      "SELECT
        COUNT(*) AS total,
        COUNT(DISTINCT CASE WHEN contact_id > 0 THEN contact_id ELSE NULL END) AS contacts,
        COUNT(DISTINCT NULLIF(conversion_email, '')) AS emails
      FROM {$wpdb->prefix}ppsgen_eca_conversions
      WHERE {$scopeColumn} = %d AND status = 'paid'",
      $scopeId,
    ), ARRAY_A);
    if (empty($totalRow) || empty($totalRow['total'])) {
      return $emptyStats;
    }

    $valueRows = $wpdb->get_results($wpdb->prepare(
      "SELECT currency, COALESCE(SUM(value), 0) AS total_value
      FROM {$wpdb->prefix}ppsgen_eca_conversions
      WHERE {$scopeColumn} = %d AND status = 'paid'
      GROUP BY currency
      ORDER BY currency ASC",
      $scopeId,
    ), ARRAY_A);

    return [
      'conversion_paid_total' => (int) ($totalRow['total'] ?? 0),
      'conversion_paid_contacts' => (int) ($totalRow['contacts'] ?? 0),
      'conversion_paid_emails' => (int) ($totalRow['emails'] ?? 0),
      'conversion_paid_value_label' => $this->formatConversionValueSummary(is_array($valueRows) ? $valueRows : []),
    ];
  }

  protected function formatConversionValueSummary(array $valueRows): string
  {
    $parts = [];
    foreach ($valueRows as $row) {
      $value = (float) ($row['total_value'] ?? 0);
      if (abs($value) < 0.00001) {
        continue;
      }
      $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($row['currency'] ?? ''))));
      $parts[] = trim(number_format_i18n($value, 2) . ' ' . $currency);
    }
    return !empty($parts) ? implode(' | ', $parts) : '0';
  }

  protected function getTrendBuckets(string $groupBy, int $range, array $metricKeys): array
  {
    $buckets = [];
    $today = current_time('timestamp');

      if ($groupBy === 'year') {
        for ($i = $range - 1; $i >= 0; $i--) {
          $ts = strtotime("-{$i} year", $today);
          $key = wp_date('Y-01-01', $ts);
          $buckets[$key] = array_merge(['label' => wp_date('Y', $ts)], array_fill_keys($metricKeys, 0));
        }
        return $buckets;
      }

      if ($groupBy === 'month') {
        for ($i = $range - 1; $i >= 0; $i--) {
          $ts = strtotime("-{$i} month", $today);
          $key = wp_date('Y-m-01', $ts);
          $buckets[$key] = array_merge(['label' => wp_date('M Y', $ts)], array_fill_keys($metricKeys, 0));
        }
        return $buckets;
      }

      if ($groupBy === 'week') {
        $monday = strtotime('monday this week', $today);
        for ($i = $range - 1; $i >= 0; $i--) {
          $ts = strtotime('-' . $i . ' week', $monday);
          $key = wp_date('Y-m-d', $ts);
          $buckets[$key] = array_merge(['label' => wp_date('d M', $ts)], array_fill_keys($metricKeys, 0));
        }
        return $buckets;
      }

      for ($i = $range - 1; $i >= 0; $i--) {
        $ts = strtotime("-{$i} day", $today);
        $key = wp_date('Y-m-d', $ts);
        $buckets[$key] = array_merge(['label' => wp_date('d M', $ts)], array_fill_keys($metricKeys, 0));
      }

      return $buckets;
    }

    protected function getTrendBucketSql(string $columnName, string $groupBy): string
    {
      if ($groupBy === 'year') {
        return "DATE_FORMAT({$columnName}, '%Y-01-01')";
      }
      if ($groupBy === 'month') {
        return "DATE_FORMAT({$columnName}, '%Y-%m-01')";
      }
      if ($groupBy === 'week') {
        return "DATE_SUB(DATE({$columnName}), INTERVAL WEEKDAY({$columnName}) DAY)";
      }
      return "DATE({$columnName})";
    }

    protected function getTrendBucketCount(string $groupBy, int $range): int
    {
      if ($groupBy === 'year') {
        return min(5, max(2, (int) ceil($range / 365)));
      }
      if ($groupBy === 'month') {
        return min(12, max(3, (int) ceil($range / 30)));
      }
      if ($groupBy === 'week') {
        return min(16, max(4, (int) ceil($range / 7)));
      }
      return $range;
    }

    public function getChainTrend(int $chainId, string $groupBy = 'day', int $range = 30): array
    {
      global $wpdb;

      $chainId = (int) $chainId;
      if (empty($chainId)) {
        return [];
      }

      $groupBy = in_array($groupBy, ['day', 'week', 'month', 'year'], true) ? $groupBy : 'day';
      $range = in_array($range, [7, 30, 365], true) ? $range : 30;
      $metricKeys = ['entries', 'emails_sent', 'opens', 'clicks'];
      $buckets = $this->getTrendBuckets($groupBy, $this->getTrendBucketCount($groupBy, $range), $metricKeys);
      $keys = array_keys($buckets);
      $dateFrom = reset($keys);

      $flowLogsBucketSql = $this->getTrendBucketSql('run_at', $groupBy);
      $eventBucketSql = $this->getTrendBucketSql('created_at', $groupBy);

      $logRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT
            {$flowLogsBucketSql} AS bucket_key,
            SUM(CASE WHEN status = 'entry' THEN 1 ELSE 0 END) AS entries,
            SUM(CASE WHEN status IN ('success', 'success_finished') THEN 1 ELSE 0 END) AS emails_sent
          FROM {$wpdb->prefix}ppsgen_eca_flow_logs
          WHERE chain_id = %d AND run_at >= %s
          GROUP BY bucket_key
          ORDER BY bucket_key ASC",
          $chainId,
          $dateFrom . ' 00:00:00',
        ),
        ARRAY_A,
      );

      foreach ($logRows as $row) {
        $bucketKey = (string) ($row['bucket_key'] ?? '');
        if (!isset($buckets[$bucketKey])) {
          continue;
        }
        $buckets[$bucketKey]['entries'] = (int) ($row['entries'] ?? 0);
        $buckets[$bucketKey]['emails_sent'] = (int) ($row['emails_sent'] ?? 0);
      }

      $eventRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT
            {$eventBucketSql} AS bucket_key,
            SUM(CASE WHEN event_type = 'open' THEN 1 ELSE 0 END) AS opens,
            SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) AS clicks
          FROM {$wpdb->prefix}ppsgen_eca_step_events
          WHERE chain_id = %d AND created_at >= %s
          GROUP BY bucket_key
          ORDER BY bucket_key ASC",
          $chainId,
          $dateFrom . ' 00:00:00',
        ),
        ARRAY_A,
      );

      foreach ($eventRows as $row) {
        $bucketKey = (string) ($row['bucket_key'] ?? '');
        if (!isset($buckets[$bucketKey])) {
          continue;
        }
        $buckets[$bucketKey]['opens'] = (int) ($row['opens'] ?? 0);
        $buckets[$bucketKey]['clicks'] = (int) ($row['clicks'] ?? 0);
      }

      return array_values($buckets);
    }

    public function getChainStepTrend(int $stepId, string $groupBy = 'day', int $range = 30): array
    {
      global $wpdb;

      $stepId = (int) $stepId;
      if (empty($stepId)) {
        return [];
      }

      $groupBy = in_array($groupBy, ['day', 'week', 'month', 'year'], true) ? $groupBy : 'day';
      $range = in_array($range, [7, 30, 365], true) ? $range : 30;
      $metricKeys = ['emails_sent', 'opens', 'clicks', 'unsubscribed'];
      $buckets = $this->getTrendBuckets($groupBy, $this->getTrendBucketCount($groupBy, $range), $metricKeys);
      $keys = array_keys($buckets);
      $dateFrom = reset($keys);

      $flowLogsBucketSql = $this->getTrendBucketSql('run_at', $groupBy);
      $eventBucketSql = $this->getTrendBucketSql('created_at', $groupBy);

      $logRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT
            {$flowLogsBucketSql} AS bucket_key,
            SUM(CASE WHEN status IN ('success', 'success_finished') THEN 1 ELSE 0 END) AS emails_sent,
            SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) AS unsubscribed
          FROM {$wpdb->prefix}ppsgen_eca_flow_logs
          WHERE step_id = %d AND run_at >= %s
          GROUP BY bucket_key
          ORDER BY bucket_key ASC",
          $stepId,
          $dateFrom . ' 00:00:00',
        ),
        ARRAY_A,
      );

      foreach ($logRows as $row) {
        $bucketKey = (string) ($row['bucket_key'] ?? '');
        if (!isset($buckets[$bucketKey])) {
          continue;
        }
        $buckets[$bucketKey]['emails_sent'] = (int) ($row['emails_sent'] ?? 0);
        $buckets[$bucketKey]['unsubscribed'] = (int) ($row['unsubscribed'] ?? 0);
      }

      $eventRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT
            {$eventBucketSql} AS bucket_key,
            SUM(CASE WHEN event_type = 'open' THEN 1 ELSE 0 END) AS opens,
            SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) AS clicks
          FROM {$wpdb->prefix}ppsgen_eca_step_events
          WHERE step_id = %d AND created_at >= %s
          GROUP BY bucket_key
          ORDER BY bucket_key ASC",
          $stepId,
          $dateFrom . ' 00:00:00',
        ),
        ARRAY_A,
      );

      foreach ($eventRows as $row) {
        $bucketKey = (string) ($row['bucket_key'] ?? '');
        if (!isset($buckets[$bucketKey])) {
          continue;
        }
        $buckets[$bucketKey]['opens'] = (int) ($row['opens'] ?? 0);
        $buckets[$bucketKey]['clicks'] = (int) ($row['clicks'] ?? 0);
      }

      return array_values($buckets);
    }

  public function getFlows(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'contact_id', 'chain_id', 'current_step_id', 'next_run_at', 'cron_status', 'status', 'active', 'created_at', 'updated_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $requestedOrderBy = (string) ($args['order_by'] ?? 'id');
    $activityOrderSql = ['select' => '', 'joins' => ''];
    if ($requestedOrderBy === 'contact_activity_at') {
      $activityOrderSql = $this->getChainContactActivityOrderSql((int) ($args['filters']['chain_id'] ?? 0));
    }
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'f.id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['contact_id'])) {
      $whereParts[] = 'f.contact_id = %d';
      $params[] = (int) $args['filters']['contact_id'];
    }
    $selectedChainIds = !empty($args['filters']['chain_ids']) && is_array($args['filters']['chain_ids'])
      ? array_values(array_unique(array_filter(array_map('intval', $args['filters']['chain_ids']))))
      : [];
    if (!empty($selectedChainIds)) {
      $chainPlaceholders = implode(',', array_fill(0, count($selectedChainIds), '%d'));
      $whereParts[] = "f.chain_id IN ($chainPlaceholders)";
      foreach ($selectedChainIds as $selectedChainId) {
        $params[] = $selectedChainId;
      }
    } elseif (!empty($args['filters']['chain_id'])) {
      $whereParts[] = 'f.chain_id = %d';
      $params[] = (int) $args['filters']['chain_id'];
    }
    if (!empty($args['filters']['current_step_id'])) {
      $whereParts[] = 'f.current_step_id = %d';
      $params[] = (int) $args['filters']['current_step_id'];
    }
    if (!empty($args['filters']['status'])) {
      $whereParts[] = 'f.status = %s';
      $params[] = $args['filters']['status'];
    }
    if (!empty($args['filters']['cron_status'])) {
      $whereParts[] = 'f.cron_status = %s';
      $params[] = $args['filters']['cron_status'];
    }
    if (!empty($args['filters']['active'])) {
      $val = $args['filters']['active'] === 'x' ? 0 : 1;
      $whereParts[] = 'f.active = %d';
      $params[] = $val;
    }
    if (!empty($args['filters']['next_run_at'])) {
      $whereParts[] = 'DATE(f.next_run_at) = %s';
      $params[] = $args['filters']['next_run_at'];
    }
    if (!empty($args['filters']['created_at'])) {
      $whereParts[] = 'DATE(f.created_at) = %s';
      $params[] = $args['filters']['created_at'];
    }
    if (!empty($args['filters']['updated_at'])) {
      $whereParts[] = 'DATE(f.updated_at) = %s';
      $params[] = $args['filters']['updated_at'];
    }
    if (!empty($args['filters']['contact_email'])) {
      $emailFieldId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email' LIMIT 1");
      $emailFilter = sanitize_text_field((string) $args['filters']['contact_email']);
      if ($emailFieldId > 0 && $emailFilter !== '') {
        $whereParts[] = "EXISTS (
          SELECT 1
          FROM {$wpdb->prefix}ppsgen_contact_field_values AS cfv
          WHERE cfv.contact_id = f.contact_id
            AND cfv.field_id = %d
            AND cfv.value LIKE %s
        )";
        $params[] = $emailFieldId;
        $params[] = '%' . $wpdb->esc_like($emailFilter) . '%';
      }
    }
    $selectedTagIds = !empty($args['filters']['contact_tag_ids']) && is_array($args['filters']['contact_tag_ids'])
      ? array_values(array_unique(array_filter(array_map('intval', $args['filters']['contact_tag_ids']))))
      : [];
    if (!empty($selectedTagIds)) {
      $tagPlaceholders = implode(',', array_fill(0, count($selectedTagIds), '%d'));
      $whereParts[] = "(
        SELECT COUNT(DISTINCT ctt.tag_id)
        FROM {$wpdb->prefix}ppsgen_contact_to_tags AS ctt
        WHERE ctt.contact_id = f.contact_id
          AND ctt.tag_id IN ($tagPlaceholders)
      ) = %d";
      foreach ($selectedTagIds as $tagId) {
        $params[] = $tagId;
      }
      $params[] = count($selectedTagIds);
    }
    $whereSql = implode(' AND ', $whereParts);
    $allowedOrderFields = $defaults['columns'];
    if ($requestedOrderBy === 'contact_activity_at') {
      $orderBy = 'contact_activity_at';
    } else {
      $orderBy = in_array($requestedOrderBy, $allowedOrderFields, true) ? 'f.' . $requestedOrderBy : 'f.id';
    }
    $orderDir = $args['order_dir'] === 'DESC' ? 'DESC' : 'ASC';
    $limit = (int) $args['per_page'];
    $offset = ($args['page'] - 1) * $limit;
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT f.id, f.contact_id, f.chain_id, f.current_step_id, f.next_run_at, f.cron_status, f.status, f.active, f.created_at, f.updated_at
        {$activityOrderSql['select']}
        FROM {$wpdb->prefix}ppsgen_eca_flow AS f
        {$activityOrderSql['joins']}
        WHERE $whereSql
        GROUP BY f.id
        ORDER BY $orderBy $orderDir
        LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    // phpcs:enable

    $params = array_slice($params, 0, -2);
    $params[] = 9999999999999;

    $sql = "
        SELECT COUNT(DISTINCT id)
          FROM {$wpdb->prefix}ppsgen_eca_flow AS f
          WHERE $whereSql
          LIMIT %d
      ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $total = $wpdb->get_var($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    return [
      'rows' => $rows,
      'total' => (int) $total,
    ];
  }

  protected function getChainContactActivityOrderSql(int $chainId): array
  {
    global $wpdb;
    $fallbackDate = '1970-01-01 00:00:00';
    $chainWhereSql = $chainId > 0 ? $wpdb->prepare('WHERE chain_id = %d', $chainId) : '';
    $joins = [];
    $joins[] = "LEFT JOIN (
      SELECT contact_id, MAX(created_at) AS last_event_at
      FROM {$wpdb->prefix}ppsgen_eca_step_events
      {$chainWhereSql}
      GROUP BY contact_id
    ) AS chainEventSort ON chainEventSort.contact_id = f.contact_id";
    $joins[] = "LEFT JOIN (
      SELECT contact_id, MAX(updated_at) AS last_flow_at
      FROM {$wpdb->prefix}ppsgen_eca_flow
      {$chainWhereSql}
      GROUP BY contact_id
    ) AS chainFlowSort ON chainFlowSort.contact_id = f.contact_id";
    $joins[] = "LEFT JOIN (
      SELECT contact_id, MAX(run_at) AS last_log_at
      FROM {$wpdb->prefix}ppsgen_eca_flow_logs
      {$chainWhereSql}
      GROUP BY contact_id
    ) AS chainLogSort ON chainLogSort.contact_id = f.contact_id";
    $select = ",
      GREATEST(
        COALESCE(MAX(chainEventSort.last_event_at), '{$fallbackDate}'),
        COALESCE(MAX(chainFlowSort.last_flow_at), '{$fallbackDate}'),
        COALESCE(MAX(chainLogSort.last_log_at), '{$fallbackDate}')
      ) AS contact_activity_at";
    return [
      'select' => $select,
      'joins' => implode(' ', $joins),
    ];
  }

  public function getFlowsLogs(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'contact_id', 'chain_id', 'step_id', 'flow_id', 'run_at', 'status', 'message'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['contact_id'])) {
      $whereParts[] = 'contact_id = %d';
      $params[] = (int) $args['filters']['contact_id'];
    }
    $selectedChainIds = !empty($args['filters']['chain_ids']) && is_array($args['filters']['chain_ids'])
      ? array_values(array_unique(array_filter(array_map('intval', $args['filters']['chain_ids']))))
      : [];
    if (!empty($selectedChainIds)) {
      $chainPlaceholders = implode(',', array_fill(0, count($selectedChainIds), '%d'));
      $whereParts[] = "chain_id IN ($chainPlaceholders)";
      foreach ($selectedChainIds as $selectedChainId) {
        $params[] = $selectedChainId;
      }
    } elseif (!empty($args['filters']['chain_id'])) {
      $whereParts[] = 'chain_id = %d';
      $params[] = (int) $args['filters']['chain_id'];
    }
    if (!empty($args['filters']['step_id'])) {
      $whereParts[] = 'step_id = %d';
      $params[] = (int) $args['filters']['step_id'];
    }
    if (!empty($args['filters']['flow_id'])) {
      $whereParts[] = 'flow_id = %d';
      $params[] = (int) $args['filters']['flow_id'];
    }
    if (!empty($args['filters']['status'])) {
      $whereParts[] = 'status = %s';
      $params[] = $args['filters']['status'];
    }
    if (!empty($args['filters']['run_at'])) {
      $whereParts[] = 'DATE(run_at) = %s';
      $params[] = $args['filters']['run_at'];
    }
    $whereSql = implode(' AND ', $whereParts);
    $allowedOrderFields = $defaults['columns'];
    $orderBy = in_array($args['order_by'], $allowedOrderFields, true) ? $args['order_by'] : 'id';
    $orderDir = $args['order_dir'] === 'DESC' ? 'DESC' : 'ASC';
    $limit = (int) $args['per_page'];
    $offset = ($args['page'] - 1) * $limit;
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT id, contact_id, chain_id, step_id, flow_id, run_at, status, message
        FROM {$wpdb->prefix}ppsgen_eca_flow_logs
        WHERE $whereSql
        GROUP BY id
        ORDER BY $orderBy $orderDir
        LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    // phpcs:enable

    $params = array_slice($params, 0, -2);
    $params[] = 9999999999999;

    $sql = "
        SELECT COUNT(DISTINCT id)
        FROM {$wpdb->prefix}ppsgen_eca_flow_logs
        WHERE $whereSql
        LIMIT %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $total = $wpdb->get_var($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    return [
      'rows' => $rows,
      'total' => (int) $total,
    ];
  }

  public function getStepEvents(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'contact_id', 'chain_id', 'step_id', 'flow_id', 'event_type', 'clicked_url', 'ip_address', 'user_agent', 'referrer', 'created_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['contact_id'])) {
      $whereParts[] = 'contact_id = %d';
      $params[] = (int) $args['filters']['contact_id'];
    }
    $selectedChainIds = !empty($args['filters']['chain_ids']) && is_array($args['filters']['chain_ids'])
      ? array_values(array_unique(array_filter(array_map('intval', $args['filters']['chain_ids']))))
      : [];
    if (!empty($selectedChainIds)) {
      $chainPlaceholders = implode(',', array_fill(0, count($selectedChainIds), '%d'));
      $whereParts[] = "chain_id IN ($chainPlaceholders)";
      foreach ($selectedChainIds as $selectedChainId) {
        $params[] = $selectedChainId;
      }
    } elseif (!empty($args['filters']['chain_id'])) {
      $whereParts[] = 'chain_id = %d';
      $params[] = (int) $args['filters']['chain_id'];
    }
    if (!empty($args['filters']['step_id'])) {
      $whereParts[] = 'step_id = %d';
      $params[] = (int) $args['filters']['step_id'];
    }
    if (!empty($args['filters']['flow_id'])) {
      $whereParts[] = 'flow_id = %d';
      $params[] = (int) $args['filters']['flow_id'];
    }
    if (!empty($args['filters']['event_type'])) {
      $whereParts[] = 'event_type = %s';
      $params[] = $args['filters']['event_type'];
    }
    if (!empty($args['filters']['clicked_url'])) {
      $whereParts[] = 'clicked_url LIKE %s';
      $params[] = '%' . $wpdb->esc_like((string) $args['filters']['clicked_url']) . '%';
    }
    if (!empty($args['filters']['user_agent'])) {
      $whereParts[] = 'user_agent LIKE %s';
      $params[] = '%' . $wpdb->esc_like((string) $args['filters']['user_agent']) . '%';
    }
    if (!empty($args['filters']['referrer'])) {
      $whereParts[] = 'referrer LIKE %s';
      $params[] = '%' . $wpdb->esc_like((string) $args['filters']['referrer']) . '%';
    }
    if (!empty($args['filters']['ip_address'])) {
      $whereParts[] = 'ip_address = %s';
      $params[] = $args['filters']['ip_address'];
    }
    if (!empty($args['filters']['created_at'])) {
      $whereParts[] = 'DATE(created_at) = %s';
      $params[] = $args['filters']['created_at'];
    }
    $whereSql = implode(' AND ', $whereParts);
    $allowedOrderFields = $defaults['columns'];
    $orderBy = in_array($args['order_by'], $allowedOrderFields, true) ? $args['order_by'] : 'id';
    $orderDir = $args['order_dir'] === 'DESC' ? 'DESC' : 'ASC';
    $limit = (int) $args['per_page'];
    $offset = ($args['page'] - 1) * $limit;
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT id, contact_id, chain_id, step_id, flow_id, event_type, clicked_url, ip_address, user_agent, referrer, created_at
        FROM {$wpdb->prefix}ppsgen_eca_step_events
        WHERE $whereSql
        GROUP BY id
        ORDER BY $orderBy $orderDir
        LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    // phpcs:enable

    $params = array_slice($params, 0, -2);
    $params[] = 9999999999999;

    $sql = "
        SELECT COUNT(DISTINCT id)
        FROM {$wpdb->prefix}ppsgen_eca_step_events
        WHERE $whereSql
        LIMIT %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $total = $wpdb->get_var($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    return [
      'rows' => $rows,
      'total' => (int) $total,
    ];
  }

  public function getConversions(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'converted_at',
      'order_dir' => 'DESC',
      'columns' => ['id', 'contact_id', 'conversion_email', 'chain_id', 'step_id', 'flow_id', 'click_event_id', 'attribution_method', 'conversion_key', 'external_id', 'status', 'value', 'currency', 'source', 'product_id', 'product_name', 'converted_at', 'created_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    [$whereSql, $params] = $this->buildConversionsWhereSql($args['filters']);
    $allowedOrderFields = $defaults['columns'];
    $orderBy = in_array($args['order_by'], $allowedOrderFields, true) ? $args['order_by'] : 'converted_at';
    $orderDir = $args['order_dir'] === 'ASC' ? 'ASC' : 'DESC';
    $limit = max(1, (int) $args['per_page']);
    $offset = (max(1, (int) $args['page']) - 1) * $limit;
    $listParams = $params;
    $listParams[] = $limit;
    $listParams[] = $offset;

    $sql = "
      SELECT id, contact_id, conversion_email, chain_id, step_id, flow_id, click_event_id, attribution_method, conversion_key, external_id, status, value, currency, source, product_id, product_name, clicked_url, converted_at, created_at
      FROM {$wpdb->prefix}ppsgen_eca_conversions
      WHERE $whereSql
      ORDER BY $orderBy $orderDir
      LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$listParams), ARRAY_A);

    $countSql = "SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_conversions WHERE $whereSql";
    $total = !empty($params) ? $wpdb->get_var($wpdb->prepare($countSql, ...$params)) : $wpdb->get_var($countSql);

    $totalsSql = "
      SELECT COUNT(*) AS conversions_total, COALESCE(SUM(value), 0) AS value_total
      FROM {$wpdb->prefix}ppsgen_eca_conversions
      WHERE $whereSql
    ";
    $totals = !empty($params) ? $wpdb->get_row($wpdb->prepare($totalsSql, ...$params), ARRAY_A) : $wpdb->get_row($totalsSql, ARRAY_A);
    // phpcs:enable

    return [
      'rows' => $rows,
      'total' => (int) $total,
      'summary' => [
        'conversions_total' => (int) ($totals['conversions_total'] ?? 0),
        'value_total' => (float) ($totals['value_total'] ?? 0),
      ],
    ];
  }

  protected function buildConversionsWhereSql(array $filters): array
  {
    global $wpdb;
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($filters['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $filters['id'];
    }
    if (!empty($filters['contact_id'])) {
      $whereParts[] = 'contact_id = %d';
      $params[] = (int) $filters['contact_id'];
    }
    if (!empty($filters['conversion_email'])) {
      $whereParts[] = 'conversion_email LIKE %s';
      $params[] = '%' . $wpdb->esc_like((string) $filters['conversion_email']) . '%';
    }
    $selectedChainIds = !empty($filters['chain_ids']) && is_array($filters['chain_ids'])
      ? array_values(array_unique(array_filter(array_map('intval', $filters['chain_ids']))))
      : [];
    if (!empty($selectedChainIds)) {
      $whereParts[] = 'chain_id IN (' . implode(',', array_fill(0, count($selectedChainIds), '%d')) . ')';
      foreach ($selectedChainIds as $selectedChainId) {
        $params[] = $selectedChainId;
      }
    } elseif (!empty($filters['chain_id'])) {
      $whereParts[] = 'chain_id = %d';
      $params[] = (int) $filters['chain_id'];
    }
    foreach (['step_id', 'flow_id', 'click_event_id'] as $intKey) {
      if (!empty($filters[$intKey])) {
        $whereParts[] = "{$intKey} = %d";
        $params[] = (int) $filters[$intKey];
      }
    }
    foreach (['attribution_method', 'conversion_key', 'status', 'source', 'currency'] as $textKey) {
      if (!empty($filters[$textKey])) {
        $whereParts[] = "{$textKey} = %s";
        $params[] = (string) $filters[$textKey];
      }
    }
    foreach (['external_id', 'product_id', 'product_name'] as $likeKey) {
      if (!empty($filters[$likeKey])) {
        $whereParts[] = "{$likeKey} LIKE %s";
        $params[] = '%' . $wpdb->esc_like((string) $filters[$likeKey]) . '%';
      }
    }
    if (!empty($filters['converted_from'])) {
      $whereParts[] = 'converted_at >= %s';
      $params[] = $filters['converted_from'] . ' 00:00:00';
    }
    if (!empty($filters['converted_to'])) {
      $whereParts[] = 'converted_at <= %s';
      $params[] = $filters['converted_to'] . ' 23:59:59';
    }
    return [implode(' AND ', $whereParts), $params];
  }

  //=====================================
  //       VIEW TABLES MODEL END
  //=====================================

  //=====================================
  //     DELETE OBJECT MODEL START
  //=====================================

  public function deleteTemplate($id)
  {
    global $wpdb;
    $id = intval($id);
    if (!empty($id)) {
      if ($id == 1) {
        return false;
      }
      $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", ['template_id' => 1], ['template_id' => $id]);
      $wpdb->delete("{$wpdb->prefix}ppsgen_eca_fields", ['owner_type' => 'template', 'owner_id' => $id]);
      $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_templates", ['id' => $id]);
      return $deleted !== false;
    }
    return false;
  }

  public function deleteChain($id)
  {
    global $wpdb;
    $id = intval($id);
    if (!empty($id)) {
      $steps = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE chain_id = %d", $id));
      if (!empty($steps)) {
        foreach ($steps as $step) {
          $this->deleteStep($step);
        }
      }
      $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow", ['chain_id' => $id]);
      $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow_logs", ['chain_id' => $id]);
      $wpdb->delete("{$wpdb->prefix}ppsgen_eca_step_events", ['chain_id' => $id]);
      $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_chains", ['id' => $id]);
      $this->deleteNewsletterOptions($id);
      return $deleted !== false;
    }
    return false;
  }

  public function duplicateChain($chain_id)
  {
    global $wpdb;

    $chain_id = (int) $chain_id;
    if (empty($chain_id)) {
      $this->setStatus('error');
      $this->setMessage(__('Campaign ID is invalid.', 'ppsgen'));
      return false;
    }

    $chain = $this->getChainById($chain_id);
    if (empty($chain)) {
      $this->setStatus('error');
      $this->setMessage(__('Campaign was not found.', 'ppsgen'));
      return false;
    }

    $now = current_time('mysql');
    $chain_data = [
      'name' => (!empty($chain['name']) ? $chain['name'] : __('Campaign', 'ppsgen')) . ' Copy',
      'template_id' => $chain['template_id'] ?? 1,
      'settings' => $chain['settings'] ?? null,
      'active' => $chain['active'] ?? 0,
      'created_at' => $now,
      'updated_at' => $now,
    ];
    // Free runs 1 automation at a time, so the copy starts switched off
    if (!empty($chain_data['active']) && $this->freeAutomationLimitApplies() && !$this->isNewsletter((int) $chain_id)) {
      $chain_data['active'] = 0;
      $copySettings = json_decode((string) $chain_data['settings'], true);
      if (is_array($copySettings)) {
        $copySettings['active'] = 0;
        $chain_data['settings'] = wp_json_encode($copySettings);
      }
    }

    $inserted = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_chains", $chain_data);
    $new_chain_id = (int) $wpdb->insert_id;
    if (empty($inserted) || empty($new_chain_id)) {
      $this->setStatus('error');
      $this->setMessage(__('Error! Campaign is not duplicated.', 'ppsgen'));
      return false;
    }

    $steps = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE chain_id = %d ORDER BY step_order ASC, id ASC", $chain_id), ARRAY_A);
    $step_map = [];

    if (!empty($steps)) {
      foreach ($steps as $step) {
        $step_data = [
          'chain_id' => $new_chain_id,
          'step_order' => $step['step_order'] ?? 0,
          'name' => $step['name'] ?? __('Step', 'ppsgen'),
          'settings' => $step['settings'] ?? null,
          'html' => $step['html'] ?? null,
          'active' => $step['active'] ?? 0,
          'is_exit_chain' => $step['is_exit_chain'] ?? 0,
          'created_at' => $now,
          'updated_at' => $now,
        ];

        $step_inserted = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_chain_step", $step_data);
        $new_step_id = (int) $wpdb->insert_id;
        if (!empty($step_inserted) && !empty($new_step_id)) {
          $step_map[(int) $step['id']] = $new_step_id;
        }
      }
    }

    if (!empty($step_map)) {
      $placeholders = implode(',', array_fill(0, count($step_map), '%d'));
      $fields = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_type = 'chain_step' AND owner_id IN ($placeholders) ORDER BY order_index ASC, id ASC",
          ...array_keys($step_map)
        ),
        ARRAY_A
      );

      if (!empty($fields)) {
        foreach ($fields as $field) {
          $old_step_id = (int) ($field['owner_id'] ?? 0);
          $new_owner_id = $step_map[$old_step_id] ?? 0;
          if (empty($new_owner_id)) {
            continue;
          }

          $field_data = [
            'owner_id' => $new_owner_id,
            'owner_type' => 'chain_step',
            'parent_key' => $field['parent_key'] ?? '',
            'fkey' => $field['fkey'] ?? '',
            'type' => $field['type'] ?? '',
            'settings' => $field['settings'] ?? null,
            'order_index' => $field['order_index'] ?? 0,
            'created_date' => $now,
            'updated_date' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_eca_fields", $field_data);
        }
      }
    }

    if (!empty($new_chain_id) && $this->isNewsletter($chain_id)) {
      $this->markNewsletter((int) $new_chain_id);
    }
    $this->setStatus('success');
    $this->setMessage(__('Campaign is duplicated.', 'ppsgen'));
    return $new_chain_id;
  }

  public function deleteStep($step_id)
  {
    global $wpdb;
    $step_id = intval($step_id);
    if (!empty($step_id)) {
      $wpdb->delete("{$wpdb->prefix}ppsgen_eca_fields", ['owner_type' => 'chain_step', 'owner_id' => $step_id]);
      $wpdb->delete("{$wpdb->prefix}ppsgen_eca_step_events", ['step_id' => $step_id]);
      $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_chain_step", ['id' => $step_id]);
      return $deleted !== false;
    }
    return false;
  }

  public function deleteFlows($id)
  {
    $id = intval($id);
    if (!empty($id)) {
      return $this->deleteFlowCascadeById($id);
    }
    return false;
  }

  public function deleteFlowLog($id)
  {
    global $wpdb;
    $id = intval($id);
    if (!empty($id)) {
      $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow_logs", ['id' => $id]);
      return $deleted !== false;
    }
    return false;
  }

  public function deleteFlowLogAll()
  {
    global $wpdb;
    $res = $wpdb->query("TRUNCATE {$wpdb->prefix}ppsgen_eca_flow_logs");
    return !empty($res);
  }

  public function deleteStepsEvent($id)
  {
    global $wpdb;
    $id = intval($id);
    if (!empty($id)) {
      $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_step_events", ['id' => $id]);
      return $deleted !== false;
    }
    return false;
  }

  public function deleteConversion($id)
  {
    global $wpdb;
    $id = intval($id);
    if (!empty($id)) {
      $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_conversions", ['id' => $id]);
      return $deleted !== false;
    }
    return false;
  }

  public function resetChainStepLogs($step_id)
  {
    global $wpdb;
    $step_id = (int) $step_id;
    if (empty($step_id)) {
      return false;
    }
    $res = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow_logs", ['step_id' => $step_id], ['%d']);
    return $res !== false;
  }

  public function resetChainStepEvents($step_id)
  {
    global $wpdb;
    $step_id = (int) $step_id;
    if (empty($step_id)) {
      return false;
    }
    $res = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_step_events", ['step_id' => $step_id], ['%d']);
    return $res !== false;
  }

  public function resetChainLogs($chain_id)
  {
    global $wpdb;
    $chain_id = (int) $chain_id;
    if (empty($chain_id)) {
      return false;
    }
    $res = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow_logs", ['chain_id' => $chain_id], ['%d']);
    return $res !== false;
  }

  public function resetChainEvents($chain_id)
  {
    global $wpdb;
    $chain_id = (int) $chain_id;
    if (empty($chain_id)) {
      return false;
    }
    $res = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_step_events", ['chain_id' => $chain_id], ['%d']);
    return $res !== false;
  }

  //=====================================
  //     DELETE OBJECT MODEL END
  //=====================================

  public function updateChainStepsOrder($steps)
  {
    global $wpdb;
    $tableName = $wpdb->prefix . 'ppsgen_eca_chain_step';
    foreach ($steps as $step) {
      $wpdb->update($tableName, ['step_order' => $step['order']], ['id' => $step['id']]);
    }
  }

  public function getChainStep($chain_id)
  {
    global $wpdb;
    if (!empty($chain_id)) {
      return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE " . $wpdb->prepare('chain_id = %d', [$chain_id]), ARRAY_A);
    }
    return false;
  }

  public function getChainIdByStepId($step_id): int
  {
    global $wpdb;
    if (empty($step_id)) {
      return 0;
    }
    return (int) $wpdb->get_var($wpdb->prepare("SELECT chain_id FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", [(int) $step_id]));
  }

  public function getChainById($chain_id)
  {
    global $wpdb;
    if (!empty($chain_id)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chains WHERE " . $wpdb->prepare('id = %d', [$chain_id]), ARRAY_A);
    }
    return false;
  }

  public function getAllCampaignOptions(): array
  {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ppsgen_eca_chains ORDER BY name ASC, id ASC", ARRAY_A);
    if (empty($rows)) {
      return [];
    }
    $options = [];
    foreach ($rows as $row) {
      $campaignId = (int) ($row['id'] ?? 0);
      if ($campaignId <= 0) {
        continue;
      }
      $campaignName = trim((string) ($row['name'] ?? ''));
      $options[$campaignId] = $campaignName !== '' ? $campaignName : sprintf(__('Campaign #%d', 'ppsgen'), $campaignId);
    }
    return $options;
  }

  public function getChainStepFields($step_id)
  {
    if (!empty($step_id)) {
      global $wpdb;
      $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_id = %d AND owner_type = 'chain_step' ORDER BY order_index ASC", $step_id), ARRAY_A);
      $fields = $this->prepareTemplateFields($fields);
      if (!empty($fields)) {
        $fieldsByKey = [];
        if (!empty($fields)) {
          foreach ($fields as $key => $field) {
            $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
          }
        }
        return $fieldsByKey;
      }
      return $fields;
    }
    return false;
  }

  public function getFieldByKey($fkey)
  {
    global $wpdb;
    $field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_fields WHERE fkey = %s", $fkey), ARRAY_A);
    if (!empty($field)) {
      $field['settings'] = $this->_prepareValueFromStore($field['settings'], 'json');
      return $field;
    }
    return false;
  }

    public function getChainStepData($step_id, string $trendGroup = 'day', int $trendRange = 30)
    {
      if (!empty($step_id)) {
        global $wpdb;
      $stepData = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", $step_id), ARRAY_A);
      $stepData['settings'] = $this->_prepareValueFromStore($stepData['settings'], 'json');
      $stepData['settings']['name'] = !isset($stepData['settings']['name']) ? $stepData['name'] : $stepData['settings']['name'];
      $stepData['settings']['active'] = !isset($stepData['settings']['active']) ? $stepData['active'] : $stepData['settings']['active'];
      $stepData['settings']['created_at'] = !isset($stepData['settings']['created_at']) ? $stepData['created_at'] : $stepData['settings']['created_at'];
      $stepData['settings']['updated_at'] = !isset($stepData['settings']['updated_at']) ? $stepData['updated_at'] : $stepData['settings']['updated_at'];

      // $stepData['html'] = $this->_prepareValueFromStore($stepData['design'], 'json');

      $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_id = %d AND owner_type = 'chain_step' ORDER BY order_index ASC", $step_id), ARRAY_A);
      if (!empty($fields)) {
        $fields = $this->prepareTemplateFields($fields);

        $fieldTree = $this->getView()->templateEditBuildFieldsTree($fields);
        $fieldsContainerHtml = '';
        foreach ($fieldTree as $field) {
          $html = $this->getView()->templateEditRenderField($field, ['row', 'column'], 'chain');
          $fieldsContainerHtml .= $this->getFrame()->core('helper')->wpKses($html);
        }
        $fieldsByKey = [];
        if (!empty($fields)) {
          foreach ($fields as $key => $field) {
            $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
          }
        }
      }
        $res = [
          'stepData' => !empty($stepData) ? $stepData : [],
          'stepFields' => !empty($fieldsByKey) ? $fieldsByKey : [],
          'stepFieldsContainerHtml' => !empty($fieldsContainerHtml) ? $fieldsContainerHtml : '',
          'stepStats' => $this->getChainStepStats($step_id),
          'stepTrend' => $this->getChainStepTrend((int) $step_id, $trendGroup, $trendRange),
          'stepTrendGroup' => $trendGroup,
          'stepTrendRange' => $trendRange,
        ];
        return $res;
      }
    return false;
  }

  public function getChainStepStats($step_id): array
  {
    global $wpdb;

    $step_id = (int) $step_id;
    if (empty($step_id)) {
      return [];
    }

    $stats = [
      'current_total' => 0,
      'current_waiting' => 0,
      'current_processing' => 0,
      'completed_contacts' => 0,
      'sent_total' => 0,
      'finished_on_step' => 0,
      'skipped_contacts' => 0,
      'condition_false_contacts' => 0,
      'condition_false_total' => 0,
      'exited_on_step' => 0,
      'unsubscribed_on_step' => 0,
      'errors_total' => 0,
      'events_total' => 0,
      'open_total' => 0,
      'open_suspicious_total' => 0,
      'open_live_total' => 0,
      'open_unique' => 0,
      'click_total' => 0,
      'click_suspicious_total' => 0,
      'click_live_total' => 0,
      'click_unique' => 0,
      'engaged_contacts' => 0,
    ];

    $stats['current_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE current_step_id = %d AND status = 'active'", $step_id));
    $stats['current_waiting'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE current_step_id = %d AND status = 'active' AND cron_status = 'waiting'", $step_id));
    $stats['current_processing'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE current_step_id = %d AND status = 'active' AND cron_status = 'processing'", $step_id));
    $stats['completed_contacts'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT flow_id) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status IN ('success', 'success_finished')", $step_id));
    $stats['sent_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'success'", $step_id));
    $stats['finished_on_step'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT flow_id) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'success_finished'", $step_id));
    $stats['skipped_contacts'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT flow_id) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'skipped'", $step_id));
    $stats['condition_false_contacts'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT flow_id) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'condition_false'", $step_id));
    $stats['condition_false_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'condition_false'", $step_id));
    $stats['exited_on_step'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT flow_id) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'exit'", $step_id));
    $stats['unsubscribed_on_step'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT flow_id) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'unsubscribed'", $step_id));
    $stats['errors_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow_logs WHERE step_id = %d AND status = 'error'", $step_id));
    $stats['events_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d", $step_id));
    $stats['open_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'open'", $step_id));
    $stats['open_suspicious_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'open' AND is_suspicious = 1", $step_id));
    $stats['open_live_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'open' AND is_suspicious = 0", $step_id));
    $stats['open_unique'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT contact_id) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'open'", $step_id));
    $stats['click_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'click'", $step_id));
    $stats['click_suspicious_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'click' AND is_suspicious = 1", $step_id));
    $stats['click_live_total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'click' AND is_suspicious = 0", $step_id));
    $stats['click_unique'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT contact_id) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d AND event_type = 'click'", $step_id));
    $stats['engaged_contacts'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT contact_id) FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE step_id = %d", $step_id));

    return array_merge($stats, $this->getPaidConversionStats('step_id', $step_id));
  }

  public function getTemplateById($template_id)
  {
    global $wpdb;
    if (!empty($template_id)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_eca_templates WHERE " . $wpdb->prepare('id = %d', [$template_id]), ARRAY_A);
    }
    return false;
  }

  public function getTemplateFields($template_id)
  {
    if (!empty($template_id)) {
      global $wpdb;
      $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_id = %d AND owner_type = 'template' ORDER BY order_index ASC", $template_id), ARRAY_A);
      $fields = $this->prepareTemplateFields($fields);
      return $fields;
    }
    return false;
  }

  public function prepareTemplateFields($fields)
  {
    if (!empty($fields)) {
      global $wpdb;
      foreach ($fields as $key => $field) {
        $fields[$key]['settings'] = $this->_prepareValueFromStore($field['settings'], 'json');
        if (!empty($fields[$key]['settings']['html_textarea_content'])) {
          $fields[$key]['settings']['html_textarea_content'] = htmlspecialchars_decode(stripslashes($fields[$key]['settings']['html_textarea_content']));
        }
      }
      return $fields;
    }
    return false;
  }

  public function editTemplate($data, $obj_id = 0)
  {
    global $wpdb;
    if (!empty($data)) {
      $cdata = !empty($data['template']) ? $data['template'] : [];
      $cdataDesign = !empty($data['template']['design']) ? $data['template']['design'] : [];
      $cdataSettings = !empty($data['template']['settings']) ? $data['template']['settings'] : [];
      $cdataFields = !empty($data['template']['fields']) ? $data['template']['fields'] : [];
      $cdataPopupName = !empty($data['template']['name']) ? $data['template']['name'] : 'Email Template ' . current_time('mysql');
      if (!empty($data)) {
        unset($data);
      }
      if (!empty($cdata['design'])) {
        unset($cdata['design']);
      }
      if (!empty($cdata['settings'])) {
        unset($cdata['settings']);
      }
      if (!empty($cdata['fields'])) {
        unset($cdata['fields']);
      }
      $cdata['name'] = $cdataPopupName;
      $cdata['updated_at'] = current_time('mysql');

      $cdata['design'] = $this->_prepareValueToStore($cdataDesign, 'json');
      $cdata['settings'] = $this->_prepareValueToStore($cdataSettings, 'json');
      //$cdata['fields'] = $this->_prepareValueToStore($cdataFields, 'json');

      if (!empty($obj_id)) {
        $res = $wpdb->update("{$wpdb->prefix}ppsgen_eca_templates", $cdata, ['id' => $obj_id]);
        if (!empty($res)) {
          $this->saveTemplateFields($obj_id, $cdataFields);
          $this->updateStepHtmlByTemplate($obj_id);
          $this->setStatus('success');
          $this->setMessage(__('Template is updated.', 'ppsgen'));
          return $res;
        } else {
          $this->setStatus('error');
          $this->setMessage(__('Error! Template is not updated.', 'ppsgen'));
          return false;
        }
      } else {
        $res = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_templates", $cdata);
        $template_id = $wpdb->insert_id;
        if (!empty($template_id)) {
          $this->saveTemplateFields($template_id, $cdataFields);
          $this->setStatus('success');
          $this->setMessage(__('Template is added.', 'ppsgen'));
          return $template_id;
        } else {
          $this->setStatus('error');
          $this->setMessage(__('Error! Template is not added.', 'ppsgen'));
          return false;
        }
      }
    }
  }

  public function saveTemplateFields($template_id, $fields)
  {
    if (empty($template_id) || empty($fields)) {
      return false;
    }

    global $wpdb;
    $template_id = (int) $template_id;
    $processedFkeys = [];
    $existingFkeys = $wpdb->get_col($wpdb->prepare("SELECT fkey FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_type = 'template' AND owner_id = %d", $template_id));

    foreach ($fields as $fkey => $field) {
      if (empty($field['field_key'])) {
        continue;
      }
      $fkey = sanitize_key($field['field_key']);
      $processedFkeys[] = $fkey;
      $settings = $field;
      $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_type = 'template' AND owner_id = %d AND fkey = %s", $template_id, $fkey));
      $data = [
        'fkey' => $fkey,
        'owner_type' => 'template',
        'owner_id' => $template_id,
        'parent_key' => $field['parent_key'],
        'type' => $field['field_type'],
        'settings' => $this->_prepareValueToStore($settings, 'json'),
        'order_index' => !empty($field['order_index']) ? $field['order_index'] : 0,
        'updated_date' => current_time('mysql'),
      ];
      if ($exists) {
        $wpdb->update("{$wpdb->prefix}ppsgen_eca_fields", $data, ['owner_type' => 'template', 'owner_id' => $template_id, 'fkey' => $fkey]);
      } else {
        $data['created_date'] = current_time('mysql');
        $wpdb->insert("{$wpdb->prefix}ppsgen_eca_fields", $data);
      }
    }

    $toDeleteFkeys = array_diff($existingFkeys, $processedFkeys);
    foreach ($toDeleteFkeys as $fkeyToDelete) {
      $wpdb->delete(
        "{$wpdb->prefix}ppsgen_eca_fields",
        [
          'owner_type' => 'template',
          'owner_id' => $template_id,
          'fkey' => sanitize_key($fkeyToDelete),
        ],
        ['%s', '%d', '%s'],
      );
    }
    return true;
  }

  public function addNewChainStep($chain_id, $step_name = '')
  {
    global $wpdb;
    $chain_id = intval($chain_id);
    if (!empty($chain_id)) {
      $res = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_chain_step", ['chain_id' => $chain_id, 'step_order' => 999, 'name' => $step_name, 'active' => 1, 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')]);
      if (!empty($res)) {
        return $wpdb->insert_id;
      }
    }
    return false;
  }

  public function saveChainOptions($chain_id, $data)
  {
    global $wpdb;
    if (empty($chain_id) || empty($data)) {
      return false;
    }
    $postData['name'] = !empty($data['chain']['options']['name']) ? $data['chain']['options']['name'] : 'Chain ' . $chain_id;
    $postData['active'] = isset($data['chain']['options']['active']) ? $data['chain']['options']['active'] : 1;
    $postData['template_id'] = isset($data['chain']['options']['template_id']) ? $data['chain']['options']['template_id'] : 0;
    $postData['updated_at'] = current_time('mysql');
    $postData['settings'] = !empty($data['chain']['options']) ? $data['chain']['options'] : [];
    if (isset($postData['settings']['eca_tracking_query_prefix'])) {
      $postData['settings']['eca_tracking_query_prefix'] = function_exists('ppsgen_sanitize_eca_tracking_query_prefix')
        ? ppsgen_sanitize_eca_tracking_query_prefix($postData['settings']['eca_tracking_query_prefix'])
        : sanitize_key((string) $postData['settings']['eca_tracking_query_prefix']);
    }
    $postData['settings']['updated_at'] = $postData['updated_at'];
    $postData['settings']['name'] = $postData['name'];
    $postData['settings']['active'] = $postData['active'];
    $postData['settings'] = $this->_prepareValueToStore($postData['settings'], 'json');
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['active' => $postData['active']], ['chain_id' => $chain_id]);
    return $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", $postData, ['id' => $chain_id]);
  }

  public function saveChainStepOptions($step_id, $data)
  {
    global $wpdb;
    if (empty($step_id) || empty($data)) {
      return false;
    }
    $postData['name'] = !empty($data['chain']['step_options']['name']) ? $data['chain']['step_options']['name'] : 'Campaign Step ' . $step_id;
    $postData['active'] = isset($data['chain']['step_options']['active']) ? $data['chain']['step_options']['active'] : 0;
    $postData['updated_at'] = current_time('mysql');
    $postData['is_exit_chain'] = isset($data['chain']['step_options']['is_exit_chain']) ? $data['chain']['step_options']['is_exit_chain'] : 0;
    $postData['settings'] = !empty($data['chain']['step_options']) ? $data['chain']['step_options'] : [];
    $postData['settings'] = $this->normalizeStepScheduleOptions($postData['settings']);
    $postData['settings']['updated_at'] = $postData['updated_at'];
    $postData['settings']['name'] = $postData['name'];
    $postData['settings']['active'] = $postData['active'];
    $postData['settings'] = $this->_prepareValueToStore($postData['settings'], 'json');
    return $wpdb->update("{$wpdb->prefix}ppsgen_eca_chain_step", $postData, ['id' => $step_id]);
  }

  protected function normalizeStepScheduleOptions(array $settings): array
  {
    $allowedUnits = ['minutes', 'hours', 'days', 'specific_day_of_month', 'specific_date'];
    $unit = sanitize_key((string) ($settings['delay_unit'] ?? 'days'));
    $settings['delay_unit'] = in_array($unit, $allowedUnits, true) ? $unit : 'days';
    $settings['delay_time'] = max(0, (int) ($settings['delay_time'] ?? 1));
    $settings['delay_day_of_month'] = min(31, max(1, (int) ($settings['delay_day_of_month'] ?? 1)));
    $settings['delay_specific_date'] = $this->normalizeStepSpecificDate($settings['delay_specific_date'] ?? '');
    return $settings;
  }

  protected function normalizeStepSpecificDate($value): string
  {
    $value = trim((string) $value);
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone());
    $errors = \DateTimeImmutable::getLastErrors();
    if (!$date || (is_array($errors) && (($errors['warning_count'] ?? 0) || ($errors['error_count'] ?? 0)))) {
      return '';
    }
    return $date->format('Y-m-d');
  }

  public function saveChainStepFields($step_id, $data)
  {
    $fields = !empty($data['chain']['step_fields']) ? $data['chain']['step_fields'] : [];
    if (empty($step_id) || empty($fields)) {
      return false;
    }

    global $wpdb;
    $step_id = (int) $step_id;
    $processedFkeys = [];
    $existingFkeys = $wpdb->get_col($wpdb->prepare("SELECT fkey FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_type = 'chain_step' AND owner_id = %d", $step_id));

    foreach ($fields as $fkey => $field) {
      if (empty($field['field_key'])) {
        continue;
      }
      $fkey = sanitize_key($field['field_key']);
      $processedFkeys[] = $fkey;
      $settings = $field;
      $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_type = 'chain_step' AND owner_id = %d AND fkey = %s", $step_id, $fkey));
      $data = [
        'fkey' => $fkey,
        'owner_type' => 'chain_step',
        'owner_id' => $step_id,
        'parent_key' => $field['parent_key'],
        'type' => $field['field_type'],
        'settings' => $this->_prepareValueToStore($settings, 'json'),
        'order_index' => !empty($field['order_index']) ? $field['order_index'] : 0,
        'updated_date' => current_time('mysql'),
      ];
      if ($exists) {
        $wpdb->update("{$wpdb->prefix}ppsgen_eca_fields", $data, ['owner_type' => 'chain_step', 'owner_id' => $step_id, 'fkey' => $fkey]);
      } else {
        $data['created_date'] = current_time('mysql');
        $wpdb->insert("{$wpdb->prefix}ppsgen_eca_fields", $data);
      }
    }

    $toDeleteFkeys = array_diff($existingFkeys, $processedFkeys);
    foreach ($toDeleteFkeys as $fkeyToDelete) {
      $wpdb->delete(
        "{$wpdb->prefix}ppsgen_eca_fields",
        [
          'owner_type' => 'chain_step',
          'owner_id' => $step_id,
          'fkey' => sanitize_key($fkeyToDelete),
        ],
        ['%s', '%d', '%s'],
      );
    }

    $this->generateStepHtmlOnSaving($step_id, true);

    return true;
  }

  public function updateStepHtmlByTemplate($template_id)
  {
    if (!empty($template_id)) {
      global $wpdb;
      $chains = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_chains WHERE template_id = %d ", $template_id));
      if (!empty($chains)) {
        foreach ($chains as $chain) {
          $steps = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE chain_id = %d ", $chain));
          if (!empty($steps)) {
            foreach ($steps as $step) {
              $this->generateStepHtmlOnSaving($step, true);
            }
          }
        }
      }
    }
    return false;
  }

  public function generateStepHtmlOnSaving($step_id, $saveHtml = true)
  {
    if (!empty($step_id)) {
      global $wpdb;
      $step = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", $step_id), ARRAY_A);
      if (!empty($step)) {
        $step['settings'] = $this->_prepareValueFromStore($step['settings'], 'json');
        $step['fields'] = $this->getChainStepFields($step_id);
        $template_id = $wpdb->get_var($wpdb->prepare("SELECT template_id FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", $step['chain_id']));
        $step['template_id'] = !empty($template_id) ? $template_id : 1;
        $html = $this->getView()->renderChainPreviewByData($step);
        if ($saveHtml) {
          $wpdb->update("{$wpdb->prefix}ppsgen_eca_chain_step", ['html' => $html], ['id' => $step_id]);
          return true;
        } else {
          return $html;
        }
      }
    }
    return false;
  }

  public function getTemplatesList($asList = false, $asCol = false)
  {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ppsgen_eca_templates", ARRAY_A);
    if ($asList) {
      return array_column($rows, 'name', 'id');
    }
    if ($asCol) {
      return array_column($rows, 'id');
    }
    return $rows;
  }

  // Free limits the number of RUNNING automations (1), not drafts or steps: the first automation is fully capable.
  // The limit is checked when an automation is switched on (see getAutomationConflict).
  public function canCreateChain()
  {
    return true;
  }

  public function canCreateChainStep()
  {
    return true;
  }

  // Free, or a lapsed (frozen) PRO licence for the automations created after it lapsed
  public function freeAutomationLimitApplies(): bool
  {
    if (function_exists('ppsgen_is_pro_license_frozen') && ppsgen_is_pro_license_frozen()) {
      return true;
    }
    return function_exists('ppsgen_contact_limit_applies') && ppsgen_contact_limit_applies();
  }

  // PRO automations that existed when the licence lapsed keep working exactly as in PRO (only editing is frozen),
  // so the Free rule never counts, holds or blocks them
  protected function isFrozenProAutomation(int $chainId): bool
  {
    return function_exists('ppsgen_is_pro_item_locked') && ppsgen_is_pro_item_locked('eca_chain', $chainId);
  }

  // Switched-on automations under the Free rule (newsletters and frozen PRO automations excluded), oldest first
  public function getRunningAutomations(): array
  {
    global $wpdb;
    $rows = (array) $wpdb->get_results(
      "SELECT id, name, created_at FROM {$wpdb->prefix}ppsgen_eca_chains WHERE active = 1 AND " . $this->getExcludeNewslettersSql('id') . " ORDER BY created_at ASC, id ASC",
      ARRAY_A
    );
    return array_values(array_filter($rows, function ($row) {
      return !$this->isFrozenProAutomation((int) $row['id']);
    }));
  }

  // On Free only the oldest running automation sends; contacts in the others are held at their current step
  // and continue as soon as the others are switched off or PRO is active.
  public function getHeldAutomationIds(): array
  {
    if (!$this->freeAutomationLimitApplies()) {
      return [];
    }
    $running = $this->getRunningAutomations();
    return array_map('intval', array_column(array_slice($running, 1), 'id'));
  }

  // Switching an automation on while another one runs needs PRO on Free
  public function getAutomationConflict(int $chainId): array
  {
    if (!$this->freeAutomationLimitApplies() || $this->isNewsletter($chainId) || $this->isFrozenProAutomation($chainId)) {
      return [];
    }
    global $wpdb;
    // Already switched on: saving its options never blocks it
    if ((int) $wpdb->get_var($wpdb->prepare("SELECT active FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", $chainId)) === 1) {
      return [];
    }
    foreach ($this->getRunningAutomations() as $row) {
      if ((int) $row['id'] !== $chainId) {
        return ['id' => (int) $row['id'], 'name' => (string) $row['name']];
      }
    }
    return [];
  }

  // Automations under the Free rule: how many exist, run and wait on the limit
  public function getAutomationLimitSummary(): array
  {
    global $wpdb;
    $running = $this->getRunningAutomations();
    $held = $this->getHeldAutomationIds();
    $ids = array_map('intval', (array) $wpdb->get_col("SELECT id FROM {$wpdb->prefix}ppsgen_eca_chains WHERE " . $this->getExcludeNewslettersSql('id')));
    $total = count(array_filter($ids, function ($id) {
      return !$this->isFrozenProAutomation($id);
    }));
    return [
      'applies' => $this->freeAutomationLimitApplies(),
      'total' => $total,
      'running' => count($running) - count($held),
      'running_name' => (string) ($running[0]['name'] ?? ''),
      'held_ids' => $held,
    ];
  }

  public function setAutomationActive(int $chainId, bool $active): void
  {
    global $wpdb;
    $state = $active ? 1 : 0;
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", ['active' => $state], ['id' => $chainId]);
    // Flows keep their step; switching back on resumes them
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['active' => $state], ['chain_id' => $chainId]);
    $settings = json_decode((string) $wpdb->get_var($wpdb->prepare("SELECT settings FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", $chainId)), true);
    if (is_array($settings)) {
      $settings['active'] = $state;
      $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", ['settings' => wp_json_encode($settings)], ['id' => $chainId]);
    }
  }

  public function createChain($chain_name = 'New automation')
  {
    global $wpdb;
    $res = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_chains", [
      'name' => $chain_name,
      'template_id' => 1,
      'active' => 0,
      'created_at' => current_time('mysql'),
      'updated_at' => current_time('mysql'),
    ]);
    if (!empty($res)) {
      return $res = $wpdb->insert_id;
    }
    return false;
  }

  public function triggerChangeContactFlow($contact_id): void
  {
    $this->checkContactFlowCondition($contact_id, 'entry');
  }

  public function checkContactFlowCondition($contact_id, $logic = 'entry', $chain_id = 0)
  {
    // entry - exit
    global $wpdb;
    if (empty($chain_id)) {
      // Newsletters are sent once on request, so contacts never enter them automatically
      $chains = $wpdb->get_results($wpdb->prepare("SELECT c.* FROM {$wpdb->prefix}ppsgen_eca_chains c LEFT JOIN {$wpdb->prefix}ppsgen_eca_flow f ON f.chain_id = c.id AND f.contact_id = %d WHERE c.active = 1 AND f.id IS NULL AND " . $this->getExcludeNewslettersSql('c.id'), $contact_id), ARRAY_A);
    } else {
      $chains = $wpdb->get_results($wpdb->prepare("SELECT c.* FROM {$wpdb->prefix}ppsgen_eca_chains c LEFT JOIN {$wpdb->prefix}ppsgen_eca_flow f ON f.chain_id = c.id AND f.contact_id = %d WHERE c.id = %d AND c.active = 1 AND f.id IS NULL", $contact_id, $chain_id), ARRAY_A);
    }
    if (!empty($chains)) {
      $helper = $this->getFrame()->core('helper');
      $contactModel = $this->getFrame()->getModule('contact')->getModel();
      $contactFieldModel = $this->getFrame()->getModule('contact')->getSubmod('contact_field')->getModel();
      foreach ($chains as $chain) {
        $canEnter = true;
        $chain_id = $chain['id'];
        $settings = $this->_prepareValueFromStore($chain['settings'], 'json');
        if (!empty($settings)) {
          $key = $logic . '_tag_yes';
          $tags = $this->normalizeConditionIds($settings[$key] ?? []);
          if (!empty($tags)) {
            $key = $logic . '_tag_yes_condition';
            $condition = !empty($settings[$key]) ? $settings[$key] : 'or';
            $res = $contactModel->contactHasTags($contact_id, $tags, $condition);
            if (empty($res)) {
              $canEnter = false;
            }
          }
          $key = $logic . '_tag_not';
          $tags = $this->normalizeConditionIds($settings[$key] ?? []);
          if ($canEnter && !empty($tags)) {
            $key = $logic . '_tag_not_condition';
            $condition = !empty($settings[$key]) ? $settings[$key] : 'or';
            $res = $contactModel->contactHasNotTags($contact_id, $tags, $condition);
            if (empty($res)) {
              $canEnter = false;
            }
          }
        }
        if ($canEnter && $logic === 'entry' && !$this->contactPassesEntryList((int) $contact_id, is_array($settings) ? $settings : [])) {
          $canEnter = false;
        }
        if ($canEnter && $logic === 'entry' && $this->isNewsletter($chain_id) && $this->contactHasUnsubscribed((int) $contact_id)) {
          $canEnter = false;
        }
        if ($canEnter) {
          $this->contactFlowEntry($contact_id, $chain_id);
        }
      }
    }
  }

  public function contactFlowEntry($contact_id, $chain_id)
  {
    global $wpdb;
    $date = current_time('mysql');
    $res = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow", [
      'contact_id' => $contact_id,
      'chain_id' => $chain_id,
      'current_step_id' => 0,
      'next_run_at' => 0,
      'status' => 'active',
      'active' => 1,
      'cron_status' => 'waiting',
      'created_at' => $date,
      'updated_at' => $date,
    ]);
    if (!empty($res)) {
      $flow_id = $wpdb->insert_id;
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow_id, 'contact_id' => (int) $contact_id, 'chain_id' => (int) $chain_id, 'step_id' => 0, 'status' => 'entry', 'message' => '', 'run_at' => $date]);
    }
  }

  public function contactFlowExit($contact_id, $chain_id)
  {
    global $wpdb;
    $date = current_time('mysql');
    $res = $wpdb->update(
      "{$wpdb->prefix}ppsgen_eca_flow",
      [
        'next_run_at' => 0,
        'status' => 'exited',
        'updated_at' => $date,
      ],
      ['contact_id' => $contact_id, 'chain_id' => $chain_id],
    );
    $flow_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_flow WHERE contact_id = %d AND chain_id = %d", $contact_id, $chain_id));
    $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow_id, 'contact_id' => (int) $contact_id, 'chain_id' => (int) $chain_id, 'step_id' => 0, 'status' => 'exit', 'message' => '', 'run_at' => $date]);
  }

  public function getFlowById($flow_id)
  {
    global $wpdb;
    $flow_id = (int) $flow_id;
    if (empty($flow_id)) {
      return false;
    }
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_flow WHERE id = %d", $flow_id), ARRAY_A);
  }

  public function exitFlowById($flow_id)
  {
    global $wpdb;
    $flow = $this->getFlowById($flow_id);
    if (empty($flow) || ($flow['status'] ?? '') !== 'active') {
      return false;
    }
    $date = current_time('mysql');
    $updated = $wpdb->update(
      "{$wpdb->prefix}ppsgen_eca_flow",
      [
        'next_run_at' => 0,
        'status' => 'exited',
        'updated_at' => $date,
      ],
      ['id' => (int) $flow_id],
    );
    if ($updated === false) {
      return false;
    }
    $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", [
      'flow_id' => (int) $flow_id,
      'contact_id' => (int) $flow['contact_id'],
      'chain_id' => (int) $flow['chain_id'],
      'step_id' => (int) ($flow['current_step_id'] ?? 0),
      'status' => 'exit',
        'message' => __('Flow manually exited from Campaign Contacts tab', 'ppsgen'),
      'run_at' => $date,
    ]);
    return true;
  }

  public function unsubscribeFlowById($flow_id)
  {
    global $wpdb;
    $flow = $this->getFlowById($flow_id);
    if (empty($flow) || ($flow['status'] ?? '') !== 'active') {
      return false;
    }
    $date = current_time('mysql');
    $updated = $wpdb->update(
      "{$wpdb->prefix}ppsgen_eca_flow",
      [
        'status' => 'unsubscribed',
        'updated_at' => $date,
      ],
      ['id' => (int) $flow_id],
    );
    if ($updated === false) {
      return false;
    }
    $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", [
      'flow_id' => (int) $flow_id,
      'contact_id' => (int) $flow['contact_id'],
      'chain_id' => (int) $flow['chain_id'],
      'step_id' => (int) ($flow['current_step_id'] ?? 0),
      'status' => 'unsubscribed',
        'message' => __('Contact manually unsubscribed from Campaign Contacts tab', 'ppsgen'),
      'run_at' => $date,
    ]);
    $this->applyUnsubscribeContactTags((int) $flow['contact_id'], (int) $flow['chain_id'], (int) ($flow['current_step_id'] ?? 0));
    return true;
  }

  public function deleteFlowByIdWithLog($flow_id)
  {
    return $this->deleteFlowCascadeById((int) $flow_id);
  }

  public function deleteContactCascadeById($contact_id): bool
  {
    global $wpdb;
    $contact_id = (int) $contact_id;
    if (empty($contact_id)) {
      return false;
    }
    $affected = 0;
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow", ['contact_id' => $contact_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow_logs", ['contact_id' => $contact_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_mail_queue", ['contact_id' => $contact_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_step_events", ['contact_id' => $contact_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    $deleted = $wpdb->delete($this->getIncomingEmailTable(), ['contact_id' => $contact_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    return $affected > 0;
  }

  protected function deleteFlowCascadeById(int $flow_id): bool
  {
    global $wpdb;
    if (empty($flow_id)) {
      return false;
    }
    $affected = 0;
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow", ['id' => $flow_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => $flow_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_mail_queue", ['flow_id' => $flow_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    $deleted = $wpdb->delete("{$wpdb->prefix}ppsgen_eca_step_events", ['flow_id' => $flow_id], ['%d']);
    if ($deleted !== false) {
      $affected += (int) $deleted;
    }
    return $affected > 0;
  }

  protected function getIncomingEmailTable(): string
  {
    global $wpdb;
    return $wpdb->prefix . 'ppsgen_eca_contact_incoming_emails';
  }

  protected function getEmptyIncomingMailboxSummary(): array
  {
    return [
      'tracking_enabled' => false,
      'selected_unhandled_total' => 0,
      'selected_last_received_at' => '',
      'all_unhandled_total' => 0,
      'all_last_received_at' => '',
    ];
  }

  public function getIncomingEmailStatsByContactIds(array $contactIds): array
  {
    global $wpdb;
    $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds))));
    if (empty($contactIds)) {
      return [];
    }
    $table = $this->getIncomingEmailTable();
    $placeholders = implode(',', array_fill(0, count($contactIds), '%d'));
    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT
          contact_id,
          COUNT(*) AS incoming_email_total,
          SUM(CASE WHEN handled_at IS NULL THEN 1 ELSE 0 END) AS incoming_email_unhandled_total,
          MAX(received_at) AS last_incoming_email_at
        FROM {$table}
        WHERE contact_id IN ($placeholders)
        GROUP BY contact_id",
        ...$contactIds,
      ),
      ARRAY_A,
    );
    $stats = [];
    foreach ($rows as $row) {
      $contactId = (int) ($row['contact_id'] ?? 0);
      if ($contactId <= 0) {
        continue;
      }
      $incomingTotal = (int) ($row['incoming_email_total'] ?? 0);
      $unhandledTotal = (int) ($row['incoming_email_unhandled_total'] ?? 0);
      $stats[$contactId] = [
        'incoming_email_total' => $incomingTotal,
        'incoming_email_unhandled_total' => $unhandledTotal,
        'has_unhandled_incoming_email' => $unhandledTotal > 0,
        'last_incoming_email_at' => (string) ($row['last_incoming_email_at'] ?? ''),
      ];
    }
    return $stats;
  }

  public function getContactIncomingMailboxSummary(int $contactId, int $mailConfigId = 0): array
  {
    global $wpdb;
    $summary = $this->getEmptyIncomingMailboxSummary();
    $contactId = (int) $contactId;
    $mailConfigId = (int) $mailConfigId;
    if ($contactId <= 0) {
      return $summary;
    }
    if ($this->getFrame()->useModule('dashboard')) {
      $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
      $mailConfig = $dashboardModel->getResolvedMailConfigData($mailConfigId);
      $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
      $summary['tracking_enabled'] = $dashboardModel->mailConfigSupportsAutoBounce($mailConfig) && !empty($mailSettings['auto_bounce_enabled']);
    }
    $table = $this->getIncomingEmailTable();
    $selectedRow = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT
          COUNT(*) AS selected_unhandled_total,
          MAX(received_at) AS selected_last_received_at
        FROM {$table}
        WHERE contact_id = %d AND mail_config_id = %d AND handled_at IS NULL",
        $contactId,
        $mailConfigId,
      ),
      ARRAY_A,
    );
    if (!empty($selectedRow)) {
      $summary['selected_unhandled_total'] = (int) ($selectedRow['selected_unhandled_total'] ?? 0);
      $summary['selected_last_received_at'] = (string) ($selectedRow['selected_last_received_at'] ?? '');
    }
    $allRow = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT
          COUNT(*) AS all_unhandled_total,
          MAX(received_at) AS all_last_received_at
        FROM {$table}
        WHERE contact_id = %d AND handled_at IS NULL",
        $contactId,
      ),
      ARRAY_A,
    );
    if (!empty($allRow)) {
      $summary['all_unhandled_total'] = (int) ($allRow['all_unhandled_total'] ?? 0);
      $summary['all_last_received_at'] = (string) ($allRow['all_last_received_at'] ?? '');
    }
    return $summary;
  }

  public function markContactIncomingEmailsHandled(int $contactId, int $mailConfigId = 0, int $handledByUserId = 0): int
  {
    global $wpdb;
    $contactId = (int) $contactId;
    $mailConfigId = (int) $mailConfigId;
    $handledByUserId = (int) $handledByUserId;
    if ($contactId <= 0) {
      return 0;
    }
    $table = $this->getIncomingEmailTable();
    $now = current_time('mysql');
    if ($mailConfigId >= 0) {
      $updated = $wpdb->query(
        $wpdb->prepare(
          "UPDATE {$table}
          SET handled_at = %s,
              handled_by_user_id = %d,
              mailbox_seen = 1,
              updated_at = %s
          WHERE contact_id = %d
            AND mail_config_id = %d
            AND handled_at IS NULL",
          $now,
          $handledByUserId,
          $now,
          $contactId,
          $mailConfigId,
        ),
      );
      return $updated !== false ? (int) $updated : 0;
    }
    $updated = $wpdb->query(
      $wpdb->prepare(
        "UPDATE {$table}
        SET handled_at = %s,
            handled_by_user_id = %d,
            mailbox_seen = 1,
            updated_at = %s
        WHERE contact_id = %d
          AND handled_at IS NULL",
        $now,
        $handledByUserId,
        $now,
        $contactId,
      ),
    );
    return $updated !== false ? (int) $updated : 0;
  }

  public function markIncomingMailboxMessageSeen(int $contactId, int $mailConfigId, string $mailboxKey, int $imapUid): bool
  {
    global $wpdb;
    $contactId = (int) $contactId;
    $mailConfigId = (int) $mailConfigId;
    $imapUid = (int) $imapUid;
    if ($contactId <= 0 || $imapUid <= 0 || $mailboxKey === '') {
      return false;
    }
    $mailboxPath = ctype_xdigit($mailboxKey) && strlen($mailboxKey) % 2 === 0 ? hex2bin($mailboxKey) : $mailboxKey;
    if (!is_string($mailboxPath) || $mailboxPath === '') {
      return false;
    }
    $updated = $wpdb->update(
      $this->getIncomingEmailTable(),
      [
        'mailbox_seen' => 1,
        'updated_at' => current_time('mysql'),
      ],
      [
        'contact_id' => $contactId,
        'mail_config_id' => $mailConfigId,
        'mailbox_hash' => md5($mailboxPath),
        'imap_uid' => $imapUid,
      ],
      ['%d', '%s'],
      ['%d', '%d', '%s', '%d'],
    );
    return $updated !== false;
  }

  public function runEcaFlowCron(): void
  {
    if (!$this->acquireEcaFlowCronLock()) {
      return;
    }
    try {
      $this->maybeStartScheduledNewsletters();
      $this->ecaFlowCron();
    } finally {
      $this->releaseEcaFlowCronLock();
    }
  }

  public function acquireEcaFlowCronLock(int $ttl = 60): bool
  {
    global $wpdb;
    $now = time();
    $optionName = 'ppsgen_eca_flow_cron_lock';
    $lockValue = $now . ':' . wp_generate_uuid4();
    if (add_option($optionName, $lockValue, '', 'no')) {
      $this->ecaFlowCronLockValue = $lockValue;
      return true;
    }
    $existingValue = (string) get_option($optionName, '');
    $existingAt = (int) strtok($existingValue, ':');
    if ($existingAt > 0 && $now - $existingAt < $ttl) {
      return false;
    }
    $updated = $wpdb->query(
      $wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
        $lockValue,
        $optionName,
        $existingValue,
      ),
    );
    if ($updated === 1) {
      wp_cache_delete($optionName, 'options');
      $this->ecaFlowCronLockValue = $lockValue;
      return true;
    }
    return false;
  }

  protected function releaseEcaFlowCronLock(): void
  {
    if ($this->ecaFlowCronLockValue === '') {
      return;
    }
    global $wpdb;
    $optionName = 'ppsgen_eca_flow_cron_lock';
    $wpdb->delete($wpdb->options, ['option_name' => $optionName, 'option_value' => $this->ecaFlowCronLockValue], ['%s', '%s']);
    wp_cache_delete($optionName, 'options');
    $this->ecaFlowCronLockValue = '';
  }

  public function ecaFlowCron(): void
  {
    global $wpdb;
    $now = current_time('mysql');
    $staleBefore = wp_date('Y-m-d H:i:s', time() - 300, wp_timezone());
    $wpdb->query(
      $wpdb->prepare(
        "UPDATE {$wpdb->prefix}ppsgen_eca_flow
        SET cron_status = 'waiting', updated_at = %s
        WHERE status = 'active' AND cron_status = 'processing' AND updated_at < %s",
        $now,
        $staleBefore,
      ),
    );
    // ppsgenlog('CRON Flow Started At: ' . $now);
    $heldIds = $this->getHeldAutomationIds();
    $heldSql = empty($heldIds) ? '' : ' AND chain_id NOT IN (' . implode(',', $heldIds) . ')';
    $updated = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ppsgen_eca_flow SET cron_status = 'processing', updated_at = %s WHERE status = 'active' AND cron_status = 'waiting' AND next_run_at <= %s AND active = 1" . $heldSql . " ORDER BY next_run_at ASC LIMIT %d ", $now, $now, 500));
    if ($updated === 0) {
      return;
    }
    $flows = $wpdb->get_results("SELECT id, contact_id, chain_id, current_step_id FROM {$wpdb->prefix}ppsgen_eca_flow WHERE cron_status = 'processing' ORDER BY next_run_at ASC LIMIT 500", ARRAY_A);
    if (empty($flows)) {
      return;
    }
    $allowedDaysMap = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7];
    $chainIds = array_unique(array_column($flows, 'chain_id'));
    $chainIds = array_map('intval', $chainIds);
    $chainsRaw = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id IN (" . implode(',', $chainIds) . ')', ARRAY_A);
    $chains = [];
    foreach ($chainsRaw as $chain) {
      $chain['settings_array'] = $this->_prepareValueFromStore($chain['settings'], 'json');
      $allowedDays = [];
      if (!empty($chain['settings_array']['time_days'])) {
        foreach ($chain['settings_array']['time_days'] as $d) {
          if (!empty($allowedDaysMap[strtolower($d)])) {
            $allowedDays[] = $allowedDaysMap[strtolower($d)];
          }
        }
      }
      $chain['_allowed_days'] = $allowedDays;
      $chains[(int) $chain['id']] = $chain;
    }
    $stepRows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE chain_id IN (" . implode(',', $chainIds) . ') AND active = 1 ORDER BY step_order ASC', ARRAY_A);
    $steps = [];
    foreach ($stepRows as $row) {
      $row['settings_array'] = $this->_prepareValueFromStore($row['settings'], 'json');
      $steps[(int) $row['chain_id']][(int) $row['id']] = $row;
    }
    foreach ($flows as $flow) {
      $flowId = (int) $flow['id'];
      try {
        $chainId = (int) $flow['chain_id'];
        $stepId = (int) $flow['current_step_id'];
        $chain = $chains[$chainId] ?? null;
        $step = $steps[$chainId][$stepId] ?? null;
        if (empty($chain) || empty($steps[$chainId]) || (!empty($stepId) && empty($step))) {
          $this->finishInvalidFlow($flow, 'Campaign or active Step is no longer available.');
          continue;
        }
        if (empty($stepId)) {
          $this->updateNextStep($flow, $chain, $step, $steps[$chainId]);
          continue;
        }
        $ok = $this->evaluateStepConditions((int) $flow['contact_id'], $step);
        if ($ok) {
          if ($this->executeStep($flow, $chain, $step)) {
            $this->updateNextStep($flow, $chain, $step, $steps[$chainId]);
          } else {
            $this->retryFlowAfterQueueError($flow);
          }
        } else {
          $this->applyStepOnFalse($flow, $chain, $step, $steps[$chainId]);
        }
      } catch (\Throwable $e) {
        $this->retryFlowAfterWorkerException($flow, $e);
      } finally {
        $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['cron_status' => 'waiting'], ['id' => $flowId]);
      }
    }
  }

  protected function finishInvalidFlow(array $flow, string $message): void
  {
    global $wpdb;
    $now = current_time('mysql');
    $wpdb->update(
      "{$wpdb->prefix}ppsgen_eca_flow",
      ['status' => 'finished', 'cron_status' => 'waiting', 'next_run_at' => null, 'updated_at' => $now],
      ['id' => (int) $flow['id']],
    );
    $wpdb->insert(
      "{$wpdb->prefix}ppsgen_eca_flow_logs",
      [
        'flow_id' => (int) $flow['id'],
        'contact_id' => (int) $flow['contact_id'],
        'chain_id' => (int) $flow['chain_id'],
        'step_id' => (int) ($flow['current_step_id'] ?? 0),
        'status' => 'error',
        'message' => $message,
        'run_at' => $now,
      ],
    );
  }

  protected function applyStepOnFalse($flow, $chain, $step, $steps)
  {
    $now = current_time('mysql');
    // ppsgenlog('CRON Flow applyStepOnFalse At: ' . $now);
    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $flow['contact_id'], 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'condition_false', 'message' => '', 'run_at' => $now]);
    switch ($step['settings_array']['on_false_condition']) {
      case 'try_after_hours': //Try After 2 Hours
        $step['settings_array']['delay_time'] = 2;
        $step['settings_array']['delay_unit'] = 'hours';
        $nextRunAt = $this->calculateNextRunTs($step, $chain);
        $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['next_run_at' => $nextRunAt, 'updated_at' => $now], ['id' => $flow['id']]);
        break;
      case 'try_after_day': //Try After One Day
        $step['settings_array']['delay_time'] = 1;
        $step['settings_array']['delay_unit'] = 'days';
        $nextRunAt = $this->calculateNextRunTs($step, $chain);
        $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['next_run_at' => $nextRunAt, 'updated_at' => $now], ['id' => $flow['id']]);
        break;
      case 'skip': //Skip Step and Continue the Chain
        $this->updateNextStep($flow, $chain, $step, $steps);
        $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $flow['contact_id'], 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'skipped', 'message' => '', 'run_at' => $now]);
        break;
      case 'exit': //Stop the Chain for this Contact
        $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['status' => 'exited', 'updated_at' => $now], ['id' => $flow['id']]);
        $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $flow['contact_id'], 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'exit', 'message' => '', 'run_at' => $now]);
        break;
    }
  }

  protected function executeStep($flow, $chain, $step)
  {
    // ppsgenlog('CRON Flow executeStep');
    global $wpdb;
    $now = current_time('mysql');
    $contactId = (int) $flow['contact_id'];
    $contactModule = $this->getFrame()->getModule('contact');
    $contactFieldModule = $contactModule->getSubmod('contact_field');
    $contactFieldModel = $contactFieldModule->getModel();
    $contactFieldEmailId = $contactFieldModel->getContactFieldIdBySlug('email');
    $contactEmail = $contactFieldModel->getContactFieldsValuesByContactId($contactId, $contactFieldEmailId);
    if (!is_email($contactEmail)) {
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $contactId, 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'error', 'message' => 'Contact does not have a valid email address.', 'run_at' => $now]);
      return false;
    }
    // Free emails the first 500 contacts only: anyone beyond that skips this email (the flow carries on)
    if (function_exists('ppsgen_is_contact_reachable') && !ppsgen_is_contact_reachable($contactId)) {
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $contactId, 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'skipped', 'message' => 'Not sent: beyond the 500 contacts the Free plan emails.', 'run_at' => $now]);
      return true;
    }
    $queueTable = "{$wpdb->prefix}ppsgen_eca_mail_queue";
    $alreadyQueued = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$queueTable} WHERE flow_id = %d AND step_id = %d LIMIT 1", (int) $flow['id'], (int) $step['id']));
    if (!empty($alreadyQueued)) {
      return true;
    }
    $queued = $wpdb->insert($queueTable, ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $contactId, 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'email' => $contactEmail, 'created_at' => $now]);
    if ($queued === false) {
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $contactId, 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'error', 'message' => 'Could not add the email to the sending queue.', 'run_at' => $now]);
      return false;
    }
    $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $contactId, 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'success', 'message' => '', 'run_at' => $now]);
    return true;
  }

  protected function retryFlowAfterQueueError(array $flow): void
  {
    global $wpdb;
    $now = new \DateTimeImmutable('now', wp_timezone());
    $wpdb->update(
      "{$wpdb->prefix}ppsgen_eca_flow",
      ['next_run_at' => $now->modify('+5 minutes')->format('Y-m-d H:i:s'), 'updated_at' => $now->format('Y-m-d H:i:s')],
      ['id' => (int) $flow['id']],
    );
  }

  protected function retryFlowAfterWorkerException(array $flow, \Throwable $exception): void
  {
    global $wpdb;
    $now = new \DateTimeImmutable('now', wp_timezone());
    $message = substr(wp_strip_all_tags($exception->getMessage()), 0, 500);
    $wpdb->update(
      "{$wpdb->prefix}ppsgen_eca_flow",
      ['next_run_at' => $now->modify('+5 minutes')->format('Y-m-d H:i:s'), 'updated_at' => $now->format('Y-m-d H:i:s')],
      ['id' => (int) $flow['id']],
    );
    $wpdb->insert(
      "{$wpdb->prefix}ppsgen_eca_flow_logs",
      [
        'flow_id' => (int) $flow['id'],
        'contact_id' => (int) $flow['contact_id'],
        'chain_id' => (int) $flow['chain_id'],
        'step_id' => (int) ($flow['current_step_id'] ?? 0),
        'status' => 'error',
        'message' => $message !== '' ? $message : 'ECA flow worker failed.',
        'run_at' => $now->format('Y-m-d H:i:s'),
      ],
    );
  }

  public function updateNextStep($flow, $chain, $step, $steps)
  {
    $now = current_time('mysql');
    global $wpdb;
    if (empty($steps)) {
      return;
    }
    $stepIds = array_keys($steps);
    $nextStepId = null;
    if (empty($step)) {
      $nextStepId = $stepIds[0] ?? null;
    } else {
      $currentStepId = (int) $step['id'];
      $currentIndex = array_search($currentStepId, $stepIds, true);
      if ($currentIndex !== false) {
        $nextStepId = $stepIds[$currentIndex + 1] ?? null;
      }
    }
    if (empty($nextStepId) || empty($steps[$nextStepId])) {
      $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['status' => 'finished', 'next_run_at' => null, 'updated_at' => $now], ['id' => (int) $flow['id']]);
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['flow_id' => (int) $flow['id'], 'contact_id' => (int) $flow['contact_id'], 'chain_id' => (int) $flow['chain_id'], 'step_id' => (int) $step['id'], 'status' => 'success_finished', 'message' => '', 'run_at' => $now]);
      return;
    }
    $nextStep = $steps[$nextStepId];
    $nextRunAt = $this->calculateNextRunTs($nextStep, $chain);
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['current_step_id' => (int) $nextStepId, 'next_run_at' => $nextRunAt, 'updated_at' => $now], ['id' => (int) $flow['id']]);
  }

  protected function calculateNextRunTs(array $nextStep, array $chain): string
  {
    $settings = $this->normalizeStepScheduleOptions(is_array($nextStep['settings_array'] ?? null) ? $nextStep['settings_array'] : []);
    $timezone = wp_timezone();
    $now = new \DateTimeImmutable('now', $timezone);
    $delay = (int) $settings['delay_time'];
    $startHour = min(23, max(0, (int) ($chain['settings_array']['time_hours_start'] ?? 0)));
    $candidate = $now;
    switch ($settings['delay_unit']) {
      case 'minutes':
        $candidate = $candidate->modify('+' . $delay . ' minutes');
        break;
      case 'hours':
        $candidate = $candidate->modify('+' . $delay . ' hours');
        break;
      case 'days':
        $candidate = $candidate->modify('+' . $delay . ' days');
        break;
      case 'specific_day_of_month':
        $candidate = $this->getNextMonthlyScheduleDate($now, (int) $settings['delay_day_of_month'], $startHour);
        break;
      case 'specific_date':
        $date = $this->normalizeStepSpecificDate($settings['delay_specific_date']);
        if ($date !== '') {
          $candidate = new \DateTimeImmutable($date . ' ' . sprintf('%02d:00:00', $startHour), $timezone);
        }
        if ($candidate < $now) {
          $candidate = $now;
        }
        break;
      default:
        break;
    }
    return $this->moveToCampaignScheduleWindow($candidate, $chain)->format('Y-m-d H:i:s');
  }

  protected function getNextMonthlyScheduleDate(\DateTimeImmutable $now, int $dayOfMonth, int $hour): \DateTimeImmutable
  {
    $dayOfMonth = min(31, max(1, $dayOfMonth));
    $monthStart = $now->modify('first day of this month')->setTime($hour, 0, 0);
    $lastDay = (int) $monthStart->format('t');
    $candidate = $monthStart->setDate((int) $monthStart->format('Y'), (int) $monthStart->format('m'), min($dayOfMonth, $lastDay));
    if ($candidate < $now) {
      $monthStart = $now->modify('first day of next month')->setTime($hour, 0, 0);
      $lastDay = (int) $monthStart->format('t');
      $candidate = $monthStart->setDate((int) $monthStart->format('Y'), (int) $monthStart->format('m'), min($dayOfMonth, $lastDay));
    }
    return $candidate;
  }

  protected function moveToCampaignScheduleWindow(\DateTimeImmutable $candidate, array $chain): \DateTimeImmutable
  {
    $allowedDays = array_values(array_unique(array_filter(array_map('intval', $chain['_allowed_days'] ?? []), static function ($day) {
      return $day >= 1 && $day <= 7;
    })));
    if (empty($allowedDays)) {
      $allowedDays = range(1, 7);
    }
    $startHour = min(23, max(0, (int) ($chain['settings_array']['time_hours_start'] ?? 0)));
    $endHour = min(23, max(0, (int) ($chain['settings_array']['time_hours_end'] ?? 23)));
    $hasTimeWindow = $endHour > $startHour;
    for ($i = 0; $i < 14; $i++) {
      $dayOfWeek = (int) $candidate->format('N');
      $hour = (int) $candidate->format('G');
      if (!in_array($dayOfWeek, $allowedDays, true)) {
        $candidate = $candidate->modify('+1 day')->setTime($startHour, 0, 0);
      } elseif ($hasTimeWindow && $hour < $startHour) {
        return $candidate->setTime($startHour, 0, 0);
      } elseif ($hasTimeWindow && $hour >= $endHour) {
        $candidate = $candidate->modify('+1 day')->setTime($startHour, 0, 0);
      } else {
        return $candidate;
      }
    }
    return $candidate;
  }

  public function evaluateStepConditions(int $contactId, array $step): bool
  {
    // ppsgenlog('CRON Flow evaluateStepConditions');
    $stepSettings = $step['settings_array'];
    $contactModel = $this->getFrame()->getModule('contact')->getModel();
    $res = true;
    $tagYes = $this->normalizeConditionIds($stepSettings['tag_yes'] ?? []);
    if (!empty($tagYes)) {
      $condition = $stepSettings['tag_yes_condition'] ?? 'or';
      if (!$contactModel->contactHasTags($contactId, $tagYes, $condition)) {
        $res = false;
      }
    }
    $tagNot = $this->normalizeConditionIds($stepSettings['tag_not'] ?? []);
    if ($res && !empty($tagNot)) {
      $condition = $stepSettings['tag_not_condition'] ?? 'or';
      if (!$contactModel->contactHasNotTags($contactId, $tagNot, $condition)) {
        $res = false;
      }
    }
    return $res;
  }

  protected function normalizeConditionIds($values): array
  {
    if (!is_array($values)) {
      $values = [$values];
    }
    return array_values(array_filter(array_map('intval', $values)));
  }

  protected function applyUnsubscribeContactTags(int $contactId, int $chainId, int $stepId = 0): void
  {
    global $wpdb;
    if (empty($contactId) || empty($chainId)) {
      return;
    }
    $tagIds = [];
    $chainData = $this->getChainById($chainId);
    if (!empty($chainData['settings'])) {
      $chainSettings = $this->_prepareValueFromStore($chainData['settings'], 'json');
      $tagIds = array_merge($tagIds, $this->normalizeConditionIds($chainSettings['add_tag_unsubscribe'] ?? []));
    }
    if (!empty($stepId)) {
      $stepData = $wpdb->get_row($wpdb->prepare("SELECT settings FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d AND chain_id = %d", $stepId, $chainId), ARRAY_A);
      if (!empty($stepData['settings'])) {
        $stepSettings = $this->_prepareValueFromStore($stepData['settings'], 'json');
        $tagIds = array_merge($tagIds, $this->normalizeConditionIds($stepSettings['add_tag_unsubscribe'] ?? []));
      }
    }
    $tagIds = array_values(array_unique($this->normalizeConditionIds($tagIds)));
    if (!empty($tagIds)) {
      $this->getFrame()->getModule('contact')->getModel()->addContactTags($tagIds, $contactId, 'eca');
    }
  }

  protected function normalizeContactFieldValues($fieldType, $fieldValues, array $labels = []): array
  {
    // select / checkbox / radio → use label
    if (in_array($fieldType, ['checkbox', 'radio', 'select'], true)) {
      return array_map('mb_strtolower', $labels);
    }
    // text / number / other
    if (!is_array($fieldValues)) {
      $fieldValues = [$fieldValues];
    }
    return array_map('mb_strtolower', array_filter(array_map('trim', $fieldValues)));
  }

  protected function evaluateFieldCondition(array $contactValues, $expectedValue, string $operator): bool
  {
    $expectedValues = is_array($expectedValue) ? $expectedValue : [$expectedValue];
    $expectedValues = array_map('mb_strtolower', array_map('trim', $expectedValues));
    switch ($operator) {
      case 'equals':
        return !empty(array_intersect($contactValues, $expectedValues));
      case 'not_equals':
        return empty(array_intersect($contactValues, $expectedValues));
      case 'contains':
        foreach ($contactValues as $cv) {
          foreach ($expectedValues as $ev) {
            if (mb_strpos($cv, $ev) !== false) {
              return true;
            }
          }
        }
        return false;
      case 'not_contains':
        foreach ($contactValues as $cv) {
          foreach ($expectedValues as $ev) {
            if (mb_strpos($cv, $ev) !== false) {
              return false;
            }
          }
        }
        return true;
      case 'starts_with':
        foreach ($contactValues as $cv) {
          foreach ($expectedValues as $ev) {
            if (mb_strpos($cv, $ev) === 0) {
              return true;
            }
          }
        }
        return false;
      case 'ends_with':
        foreach ($contactValues as $cv) {
          foreach ($expectedValues as $ev) {
            if (mb_substr($cv, -mb_strlen($ev)) === $ev) {
              return true;
            }
          }
        }
        return false;
      case 'empty':
        return empty($contactValues);
      case 'not_empty':
        return !empty($contactValues);
      case 'greater':
        return (float) ($contactValues[0] ?? 0) > (float) $expectedValues[0];
      case 'greater_equal':
        return (float) ($contactValues[0] ?? 0) >= (float) $expectedValues[0];
      case 'less':
        return (float) ($contactValues[0] ?? 0) < (float) $expectedValues[0];
      case 'less_equal':
        return (float) ($contactValues[0] ?? 0) <= (float) $expectedValues[0];
    }
    return false;
  }

  public function runEcaApplyChainToContactsCron()
  {
    $now = current_time('mysql');
    // ppsgenlog('CRON Apply Chain To Contacts Started At:  ' . $now);
    $cronChainId = $this->getOption('ppsgen_eca_apply_chain_id', 'cron', 0, 'eca');
    $cronStatus = $this->getOption('ppsgen_eca_apply_chain_status', 'cron', 0, 'eca');
    if (empty($cronChainId) || $cronStatus !== 'processing') {
      return;
    }
    global $wpdb;
    $wpdb->query("UPDATE {$wpdb->prefix}ppsgen_contacts SET cron_status = 'processing' WHERE cron_status = 'waiting' ORDER BY id ASC LIMIT 1000");
    $contacts = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE cron_status = 'processing' ORDER BY id ASC LIMIT 1000");
    foreach ($contacts as $contactId) {
      try {
        $this->checkContactFlowCondition($contactId, 'entry', $cronChainId);
      } catch (\Throwable $e) {
        // ppsgenlog(
        //   'ECA apply chain error. Contact ID: ' . $contactId .
        //   ' | ' . $e->getMessage()
        // );
      } finally {
        $wpdb->update("{$wpdb->prefix}ppsgen_contacts", ['cron_status' => 'done'], ['id' => $contactId]);
      }
    }
    $remaining = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_contacts WHERE cron_status IN ('waiting', 'processing')");
    if ($remaining === 0) {
      $wpdb->update("{$wpdb->prefix}ppsgen_contacts", ['cron_status' => 'waiting'], ['cron_status' => 'done']);
      $this->updateOption('ppsgen_eca_apply_chain_id', 'cron', 0, 0, 'eca', 'json');
      $this->updateOption('ppsgen_eca_apply_chain_status', 'cron', 'done', 0, 'eca', 'json');
    }
  }

  //=====================================
  //       STATUSES ARRAY START
  //=====================================

  public function applyChainGetProgress(): ?array
  {
    global $wpdb;
    $row = $wpdb->get_row("SELECT COUNT(*) AS total, SUM(cron_status = 'done') AS processed FROM {$wpdb->prefix}ppsgen_contacts", ARRAY_A);
    return [
      'status' => $row['total'] == $row['processed'] ? 'done' : 'processing',
      'total' => (int) $row['total'],
      'processed' => (int) $row['processed'],
    ];
  }

  public function flowStatus($status = '')
  {
    $statuses = [
      'active' => __('Flow (Campaign) in Processing', 'ppsgen'),
      'exited' => __('Contact is Exit from Flow (Campaign)', 'ppsgen'),
      'finished' => __('Flow (Campaign) Successfully Finished', 'ppsgen'),
      'unsubscribed' => __('Contact Unsubscribed From Campaign (Email Sequence)', 'ppsgen'),
    ];
    if ($status) {
      return !empty($statuses[$status]) ? $statuses[$status] : '';
    }
    return $statuses;
  }

  public function flowLogStatus($status = '')
  {
    $statuses = [
      'success' => __('Step is Successfully', 'ppsgen'),
      'skipped' => __('Step is Skipped', 'ppsgen'),
      'exit' => __('Exit from Flow (Campaign)', 'ppsgen'),
      'entry' => __('Entry in Flow (Campaign)', 'ppsgen'),
      'condition_false' => __('False on Step Conditions Checks', 'ppsgen'),
      'success_finished' => __('Flow (Campaign) Successfully Finished', 'ppsgen'),
      'error' => __('Error', 'ppsgen'),
      'unsubscribed' => __('Contact Unsubscribed From Campaign (Email Sequence)', 'ppsgen'),
    ];
    if ($status) {
      return !empty($statuses[$status]) ? $statuses[$status] : '';
    }
    return $statuses;
  }

  public function eventTypeStatus($status = '')
  {
    $statuses = [
      'open' => __('Email is Opened', 'ppsgen'),
      'click' => __('Link in Email is Clicked', 'ppsgen'),
    ];
    if ($status) {
      return !empty($statuses[$status]) ? $statuses[$status] : '';
    }
    return $statuses;
  }

  public function conversionStatus($status = '')
  {
    $statuses = [
      'completed' => __('Completed', 'ppsgen'),
      'paid' => __('Paid', 'ppsgen'),
      'pending' => __('Pending', 'ppsgen'),
      'refunded' => __('Refunded', 'ppsgen'),
      'cancelled' => __('Cancelled', 'ppsgen'),
      'failed' => __('Failed', 'ppsgen'),
    ];
    if ($status) {
      return !empty($statuses[$status]) ? $statuses[$status] : $status;
    }
    return $statuses;
  }

  public function attributionMethodStatus($status = '')
  {
    $statuses = [
      'browser_token' => __('Browser Token', 'ppsgen'),
      'email_last_click' => __('Email Last Click', 'ppsgen'),
      'email_last_click_suspicious' => __('Email Last Click (Suspicious)', 'ppsgen'),
      'email_recent_send' => __('Email Recent Send', 'ppsgen'),
      'manual' => __('Manual', 'ppsgen'),
      'none' => __('None', 'ppsgen'),
    ];
    if ($status) {
      return !empty($statuses[$status]) ? $statuses[$status] : $status;
    }
    return $statuses;
  }

  //=====================================
  //       STATUSES ARRAY END
  //=====================================

  public function addStepEvents($data)
  {
    global $wpdb;
    $now = current_time('mysql');
    $data['created_at'] = $now;
    if ($data['event_type'] == 'open') {
      $isOpenedBefore = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE contact_id = %d AND chain_id = %d AND flow_id = %d AND step_id = %d AND event_type = 'open'", $data['contact_id'], $data['chain_id'], $data['flow_id'], $data['step_id']));
      if (empty($isOpenedBefore)) {
        $stepData = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", $data['step_id']), ARRAY_A);
        if (!empty($stepData)) {
          $settings = $this->_prepareValueFromStore($stepData['settings'], 'json');
          if (!empty($settings['add_tag_view']) || !empty($settings['delete_tag_view'])) {
            $contactModel = $this->getFrame()->getModule('contact')->getModel();
            if (!empty($settings['add_tag_view'])) {
                $contactModel->addContactTags($settings['add_tag_view'], (int) $data['contact_id'], 'eca');
            }
            if (!empty($settings['delete_tag_view'])) {
                $contactModel->deleteContactTags($settings['delete_tag_view'], (int) $data['contact_id'], 'eca');
            }
          }
        }
        return $this->insertStepEventAndApplyDetection($data);
      }
      return (int) $isOpenedBefore;
    }
    if ($data['event_type'] == 'click') {
      $isClickedBefore = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE contact_id = %d AND chain_id = %d AND flow_id = %d AND step_id = %d AND clicked_url = %s AND event_type = 'click'", $data['contact_id'], $data['chain_id'], $data['flow_id'], $data['step_id'], $data['clicked_url']),
      );
      if (empty($isClickedBefore)) {
        $stepData = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", $data['step_id']), ARRAY_A);
        if (!empty($stepData)) {
          $settings = $this->_prepareValueFromStore($stepData['settings'], 'json');
          if (!empty($settings['add_tag_click']) || !empty($settings['delete_tag_click'])) {
            $contactModel = $this->getFrame()->getModule('contact')->getModel();
            if (!empty($settings['add_tag_click'])) {
                $contactModel->addContactTags($settings['add_tag_click'], (int) $data['contact_id'], 'eca');
            }
            if (!empty($settings['delete_tag_click'])) {
                $contactModel->deleteContactTags($settings['delete_tag_click'], (int) $data['contact_id'], 'eca');
            }
          }
        }
        return $this->insertStepEventAndApplyDetection($data);
      }
      return (int) $isClickedBefore;
    }
    return false;
  }

  protected function insertStepEventAndApplyDetection(array $data)
  {
    global $wpdb;
    $inserted = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_step_events", $data);
    if (empty($inserted)) {
      return $inserted;
    }
    $this->markSuspiciousAutoCheckEvents(
      (int) ($data['contact_id'] ?? 0),
      (int) ($data['chain_id'] ?? 0),
      (int) ($data['step_id'] ?? 0),
      (string) ($data['created_at'] ?? current_time('mysql'))
    );
    return (int) $wpdb->insert_id;
  }

  public function buildEcaAttributionToken(array $data): string
  {
    $payloadData = [
      'contact_id' => (int) ($data['contact_id'] ?? 0),
      'flow_id' => (int) ($data['flow_id'] ?? 0),
      'chain_id' => (int) ($data['chain_id'] ?? 0),
      'step_id' => (int) ($data['step_id'] ?? 0),
      'click_event_id' => (int) ($data['click_event_id'] ?? $data['event_id'] ?? 0),
      'created_at' => (int) ($data['created_at'] ?? time()),
    ];
    if (empty($payloadData['contact_id']) || empty($payloadData['chain_id']) || empty($payloadData['step_id'])) {
      return '';
    }
    $payload = implode('|', $payloadData);
    $signature = hash_hmac('sha256', $payload, wp_salt('auth'));
    return rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');
  }

  public function parseEcaAttributionToken(string $token, int $windowDays = 180)
  {
    $token = trim($token);
    if ($token === '') {
      return false;
    }
    $decoded = base64_decode(strtr($token, '-_', '+/'));
    if (!$decoded) {
      return false;
    }
    $parts = explode('|', $decoded);
    if (count($parts) !== 7) {
      return false;
    }
    [$contactId, $flowId, $chainId, $stepId, $clickEventId, $createdAt, $signature] = $parts;
    $payload = implode('|', [$contactId, $flowId, $chainId, $stepId, $clickEventId, $createdAt]);
    $expected = hash_hmac('sha256', $payload, wp_salt('auth'));
    if (!hash_equals($expected, $signature)) {
      return false;
    }
    $createdAt = (int) $createdAt;
    $windowDays = max(1, (int) $windowDays);
    if ($createdAt < time() - DAY_IN_SECONDS * $windowDays) {
      return false;
    }
    return [
      'contact_id' => (int) $contactId,
      'flow_id' => (int) $flowId,
      'chain_id' => (int) $chainId,
      'step_id' => (int) $stepId,
      'click_event_id' => (int) $clickEventId,
      'created_at' => $createdAt,
    ];
  }

  public function rememberEcaBrowserAttribution(array $data, int $days = 180): string
  {
    $days = max(1, $days);
    $token = $this->buildEcaAttributionToken($data);
    if ($token === '') {
      return '';
    }
    if (!headers_sent()) {
      $cookieArgs = [
        'expires' => time() + DAY_IN_SECONDS * $days,
        'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
        'secure' => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
      ];
      if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
        $cookieArgs['domain'] = COOKIE_DOMAIN;
      }
      setcookie('ppsgen_eca_attribution', $token, $cookieArgs);
    }
    add_action('wp_footer', static function () use ($token) {
      echo '<script>try{window.ppsgenEcaAttributionToken=' . wp_json_encode($token) . ';localStorage.setItem("ppsgen_eca_attribution",window.ppsgenEcaAttributionToken);}catch(e){}</script>';
    }, 99);
    return $token;
  }

  public function getEcaBrowserAttribution($token = null, int $windowDays = 180)
  {
    if ($token === null || $token === '') {
      if (!empty($_POST['ppsgen_eca_attribution'])) {
        $token = sanitize_text_field(wp_unslash($_POST['ppsgen_eca_attribution']));
      } elseif (!empty($_COOKIE['ppsgen_eca_attribution'])) {
        $token = sanitize_text_field(wp_unslash($_COOKIE['ppsgen_eca_attribution']));
      }
    }
    return $this->parseEcaAttributionToken((string) $token, $windowDays);
  }

  public function getContactIdByEmail(string $email): int
  {
    global $wpdb;
    $email = sanitize_email($email);
    if ($email === '') {
      return 0;
    }
    $contactFieldId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email'");
    if (empty($contactFieldId)) {
      return 0;
    }
    return (int) $wpdb->get_var($wpdb->prepare("SELECT contact_id FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE field_id = %d AND value = %s ORDER BY contact_id DESC LIMIT 1", $contactFieldId, $email));
  }

  public function getContactEcaAttribution($emailOrContactId, array $args = []): array
  {
    $windowDays = max(1, (int) ($args['window_days'] ?? 180));
    $contactId = is_numeric($emailOrContactId) ? (int) $emailOrContactId : $this->getContactIdByEmail((string) $emailOrContactId);
    if ($contactId <= 0) {
      return [];
    }
    $attribution = $this->findLatestContactClickAttribution($contactId, $windowDays, !empty($args['include_suspicious']));
    if (!empty($attribution)) {
      return $attribution;
    }
    return $this->findLatestContactSendAttribution($contactId, $windowDays);
  }

  protected function findLatestContactClickAttribution(int $contactId, int $windowDays = 180, bool $includeSuspicious = false): array
  {
    global $wpdb;
    if ($contactId <= 0) {
      return [];
    }
    $since = date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS * max(1, $windowDays));
    $whereSuspicious = $includeSuspicious ? '' : ' AND is_suspicious = 0';
    $row = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT id, contact_id, chain_id, step_id, flow_id, clicked_url, is_suspicious, created_at
        FROM {$wpdb->prefix}ppsgen_eca_step_events
        WHERE contact_id = %d AND event_type = 'click' AND created_at >= %s {$whereSuspicious}
        ORDER BY created_at DESC, id DESC
        LIMIT 1",
        $contactId,
        $since
      ),
      ARRAY_A
    );
    if (empty($row) && !$includeSuspicious) {
      $row = $wpdb->get_row(
        $wpdb->prepare(
          "SELECT id, contact_id, chain_id, step_id, flow_id, clicked_url, is_suspicious, created_at
          FROM {$wpdb->prefix}ppsgen_eca_step_events
          WHERE contact_id = %d AND event_type = 'click' AND created_at >= %s
          ORDER BY created_at DESC, id DESC
          LIMIT 1",
          $contactId,
          $since
        ),
        ARRAY_A
      );
    }
    if (empty($row)) {
      return [];
    }
    return [
      'contact_id' => (int) $row['contact_id'],
      'chain_id' => (int) $row['chain_id'],
      'step_id' => (int) $row['step_id'],
      'flow_id' => (int) $row['flow_id'],
      'click_event_id' => (int) $row['id'],
      'clicked_url' => (string) ($row['clicked_url'] ?? ''),
      'attribution_method' => !empty($row['is_suspicious']) ? 'email_last_click_suspicious' : 'email_last_click',
    ];
  }

  protected function findLatestContactSendAttribution(int $contactId, int $windowDays = 180): array
  {
    global $wpdb;
    if ($contactId <= 0) {
      return [];
    }
    $since = date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS * max(1, $windowDays));
    $row = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT id, contact_id, chain_id, step_id, flow_id, run_at
        FROM {$wpdb->prefix}ppsgen_eca_flow_logs
        WHERE contact_id = %d AND status = 'success' AND run_at >= %s
        ORDER BY run_at DESC, id DESC
        LIMIT 1",
        $contactId,
        $since
      ),
      ARRAY_A
    );
    if (empty($row)) {
      return [];
    }
    return [
      'contact_id' => (int) $row['contact_id'],
      'chain_id' => (int) $row['chain_id'],
      'step_id' => (int) $row['step_id'],
      'flow_id' => (int) $row['flow_id'],
      'click_event_id' => 0,
      'clicked_url' => '',
      'attribution_method' => 'email_recent_send',
    ];
  }

  public function setEcaConversion(array $data)
  {
    global $wpdb;
    if (!$this->getFrame()->core('installer')->exist('ppsgen_eca_conversions')) {
      $this->installTables();
    }
    $windowDays = max(1, (int) ($data['attribution_window_days'] ?? $data['window_days'] ?? 180));
    $email = sanitize_email((string) ($data['email'] ?? $data['conversion_email'] ?? ''));
    $browserAttribution = [];
    if (!empty($data['browser_attribution']) && is_array($data['browser_attribution'])) {
      $browserAttribution = $data['browser_attribution'];
    } elseif (!empty($data['browser_attribution'])) {
      $parsed = $this->parseEcaAttributionToken((string) $data['browser_attribution'], $windowDays);
      $browserAttribution = is_array($parsed) ? $parsed : [];
    } elseif (!empty($data['attribution_token'])) {
      $parsed = $this->parseEcaAttributionToken((string) $data['attribution_token'], $windowDays);
      $browserAttribution = is_array($parsed) ? $parsed : [];
    } else {
      $parsed = $this->getEcaBrowserAttribution(null, $windowDays);
      $browserAttribution = is_array($parsed) ? $parsed : [];
    }

    $contactId = (int) ($data['contact_id'] ?? 0);
    if ($contactId <= 0 && !empty($browserAttribution['contact_id'])) {
      $contactId = (int) $browserAttribution['contact_id'];
    }
    if ($contactId <= 0 && $email !== '') {
      $contactId = $this->getContactIdByEmail($email);
    }

    $attribution = [];
    if (!empty($browserAttribution)) {
      $attribution = [
        'contact_id' => (int) ($browserAttribution['contact_id'] ?? 0),
        'chain_id' => (int) ($browserAttribution['chain_id'] ?? 0),
        'step_id' => (int) ($browserAttribution['step_id'] ?? 0),
        'flow_id' => (int) ($browserAttribution['flow_id'] ?? 0),
        'click_event_id' => (int) ($browserAttribution['click_event_id'] ?? 0),
        'clicked_url' => '',
        'attribution_method' => 'browser_token',
      ];
      if (!empty($attribution['click_event_id'])) {
        $clickedUrl = $wpdb->get_var($wpdb->prepare("SELECT clicked_url FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE id = %d LIMIT 1", (int) $attribution['click_event_id']));
        $attribution['clicked_url'] = (string) $clickedUrl;
      }
      if ($contactId <= 0) {
        $contactId = (int) $attribution['contact_id'];
      }
    }
    if (empty($attribution) && $contactId > 0) {
      $attribution = $this->findLatestContactClickAttribution($contactId, $windowDays);
    }
    if (empty($attribution) && $contactId > 0) {
      $attribution = $this->findLatestContactSendAttribution($contactId, $windowDays);
    }
    if (empty($attribution)) {
      $attribution = [
        'contact_id' => $contactId,
        'chain_id' => (int) ($data['chain_id'] ?? 0),
        'step_id' => (int) ($data['step_id'] ?? 0),
        'flow_id' => (int) ($data['flow_id'] ?? 0),
        'click_event_id' => (int) ($data['click_event_id'] ?? 0),
        'clicked_url' => (string) ($data['clicked_url'] ?? ''),
        'attribution_method' => !empty($data['chain_id']) || !empty($data['step_id']) ? 'manual' : 'none',
      ];
    }

    $conversionKey = sanitize_key((string) ($data['conversion_key'] ?? 'conversion'));
    $conversionKey = $conversionKey !== '' ? $conversionKey : 'conversion';
    $externalId = sanitize_text_field((string) ($data['external_id'] ?? ''));
    $source = sanitize_key((string) ($data['source'] ?? ''));
    $status = sanitize_key((string) ($data['status'] ?? 'completed'));
    $status = $status !== '' ? $status : 'completed';
    $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($data['currency'] ?? ''))));
    $knownKeys = ['email', 'conversion_email', 'browser_attribution', 'attribution_token', 'contact_id', 'chain_id', 'step_id', 'flow_id', 'click_event_id', 'clicked_url', 'attribution_method', 'attribution_window_days', 'window_days', 'conversion_key', 'external_id', 'status', 'value', 'currency', 'source', 'product_id', 'product_name', 'converted_at'];
    $extra = array_diff_key($data, array_flip($knownKeys));
    $now = current_time('mysql');
    $convertedAt = $this->normalizeConversionDate($data['converted_at'] ?? $now, $now);
    $row = [
      'contact_id' => (int) ($attribution['contact_id'] ?? $contactId),
      'conversion_email' => $email,
      'chain_id' => (int) ($attribution['chain_id'] ?? 0),
      'step_id' => (int) ($attribution['step_id'] ?? 0),
      'flow_id' => (int) ($attribution['flow_id'] ?? 0),
      'click_event_id' => (int) ($attribution['click_event_id'] ?? 0),
      'attribution_method' => sanitize_key((string) ($attribution['attribution_method'] ?? 'none')),
      'attribution_window_days' => $windowDays,
      'conversion_key' => $conversionKey,
      'external_id' => $externalId,
      'status' => $status,
      'value' => (float) ($data['value'] ?? 0),
      'currency' => substr($currency, 0, 10),
      'source' => $source,
      'product_id' => sanitize_text_field((string) ($data['product_id'] ?? '')),
      'product_name' => sanitize_text_field((string) ($data['product_name'] ?? '')),
      'clicked_url' => esc_url_raw((string) ($attribution['clicked_url'] ?? $data['clicked_url'] ?? '')),
      'data_json' => !empty($extra) ? wp_json_encode($extra) : null,
      'converted_at' => $convertedAt,
      'updated_at' => $now,
    ];

    $existingId = 0;
    if ($externalId !== '') {
      $existingId = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ppsgen_eca_conversions WHERE conversion_key = %s AND external_id = %s AND source = %s LIMIT 1",
        $conversionKey,
        $externalId,
        $source
      ));
    }
    if ($existingId > 0) {
      $updated = $wpdb->update("{$wpdb->prefix}ppsgen_eca_conversions", $row, ['id' => $existingId]);
      return $updated === false ? false : $existingId;
    }
    $row['created_at'] = $now;
    $inserted = $wpdb->insert("{$wpdb->prefix}ppsgen_eca_conversions", $row);
    if (empty($inserted)) {
      return false;
    }
    return (int) $wpdb->insert_id;
  }

  protected function normalizeConversionDate($value, string $fallback): string
  {
    if ($value instanceof DateTimeInterface) {
      return $value->format('Y-m-d H:i:s');
    }
    $value = trim((string) $value);
    if ($value === '') {
      return $fallback;
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : $fallback;
  }

  protected function markSuspiciousAutoCheckEvents(int $contactId, int $chainId, int $stepId, string $eventCreatedAt): bool
  {
    global $wpdb;
    if ($contactId <= 0 || $stepId <= 0 || $eventCreatedAt === '') {
      return false;
    }
    $tableName = $wpdb->prefix . 'ppsgen_eca_step_events';
    $rows = $wpdb->get_results(
      $wpdb->prepare(
          "SELECT id
          FROM {$tableName}
          WHERE contact_id = %d
            AND step_id = %d
            AND ABS(TIMESTAMPDIFF(SECOND, created_at, %s)) <= 1",
          $contactId,
          $stepId,
          $eventCreatedAt
        ),
        ARRAY_A
      );
    if (count($rows) < 2) {
      return false;
    }
    $eventIds = array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'id')))));
    if (empty($eventIds)) {
      return false;
    }
    $updated = $this->markStepEventsAsSuspicious($eventIds);
    $this->applyAutocheckDetectionTagsToContacts([$contactId]);
    return $updated >= 0;
  }

  public function runManualSuspiciousActivityScan(): array
  {
    global $wpdb;
    if (function_exists('set_time_limit')) {
      @set_time_limit(0);
    }
    $tableName = $wpdb->prefix . 'ppsgen_eca_step_events';
    if (!$this->getFrame()->core('installer')->exist('ppsgen_eca_step_events')) {
      $this->setStatus('error');
      $this->setMessage(__('Email campaign events table was not found.', 'ppsgen'));
      return [];
    }
    $rows = $wpdb->get_results(
      "SELECT DISTINCT e1.id, e1.contact_id
      FROM {$tableName} AS e1
      INNER JOIN {$tableName} AS e2
        ON e1.contact_id = e2.contact_id
        AND e1.step_id = e2.step_id
        AND e1.id <> e2.id
        AND ABS(TIMESTAMPDIFF(SECOND, e1.created_at, e2.created_at)) <= 1
      WHERE e1.contact_id > 0
        AND e1.step_id > 0",
      ARRAY_A
    );
    if (empty($rows)) {
      $this->setStatus('success');
      $this->setMessage(__('Manual suspicious activity scan completed. No suspicious events were found.', 'ppsgen'));
      return [
        'suspicious_events' => 0,
        'newly_marked_events' => 0,
        'affected_contacts' => 0,
        'tagged_contacts' => 0,
      ];
    }
    $eventIds = array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'id')))));
    $contactIds = array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'contact_id')))));
    $newlyMarkedEvents = $this->markStepEventsAsSuspicious($eventIds);
    $taggedContacts = $this->applyAutocheckDetectionTagsToContacts($contactIds);
    $result = [
      'suspicious_events' => count($eventIds),
      'newly_marked_events' => $newlyMarkedEvents,
      'affected_contacts' => count($contactIds),
      'tagged_contacts' => $taggedContacts,
    ];
    $this->setStatus('success');
    $this->setMessage(
      sprintf(
        /* translators: 1: total suspicious events, 2: newly marked events, 3: affected contacts, 4: contacts that received tags */
        __('Manual suspicious activity scan completed. Suspicious events: %1$d. Newly marked: %2$d. Affected contacts: %3$d. Tagged contacts: %4$d.', 'ppsgen'),
        (int) $result['suspicious_events'],
        (int) $result['newly_marked_events'],
        (int) $result['affected_contacts'],
        (int) $result['tagged_contacts']
      )
    );
    return $result;
  }

  protected function markStepEventsAsSuspicious(array $eventIds): int
  {
    global $wpdb;
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if (empty($eventIds)) {
      return 0;
    }
    $tableName = $wpdb->prefix . 'ppsgen_eca_step_events';
    $updated = 0;
    foreach (array_chunk($eventIds, 500) as $eventIdChunk) {
      $placeholders = implode(',', array_fill(0, count($eventIdChunk), '%d'));
      $chunkUpdated = $wpdb->query(
        $wpdb->prepare(
          "UPDATE {$tableName}
          SET is_suspicious = 1
          WHERE is_suspicious = 0
            AND id IN ($placeholders)",
          ...$eventIdChunk
        )
      );
      if ($chunkUpdated !== false) {
        $updated += (int) $chunkUpdated;
      }
    }
    return $updated;
  }

  protected function applyAutocheckDetectionTagsToContacts(array $contactIds): int
  {
    $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds))));
    if (empty($contactIds) || !$this->getFrame()->useModule('contact')) {
      return 0;
    }
    $contactModel = $this->getFrame()->getModule('contact')->getModel();
    if (empty($contactModel) || !method_exists($contactModel, 'getEcaAutocheckDetectionTagIds') || !method_exists($contactModel, 'addContactTags')) {
      return 0;
    }
    $tagIds = $contactModel->getEcaAutocheckDetectionTagIds();
    if (empty($tagIds)) {
      return 0;
    }
    $taggedContacts = 0;
    foreach ($contactIds as $contactId) {
      $addedTagIds = $contactModel->addContactTags($tagIds, $contactId, 'eca');
      if (!empty($addedTagIds)) {
        $taggedContacts++;
      }
    }
    return $taggedContacts;
  }

  public function runEcaSendEmailCron()
  {
    $siteNow = new \DateTimeImmutable(current_time('mysql'), wp_timezone());
    $now = $siteNow->format('Y-m-d H:i:s');
    global $wpdb;
    $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
    $defaultConfig = $dashboardModel->getResolvedMailConfigRemaining(0);
    $defaultSettings = is_array($defaultConfig['settings'] ?? null) ? $defaultConfig['settings'] : [];
    $delay = max(1, (int) ($defaultSettings['mail_delay'] ?? 15));
    $batchSize = max(1, (int) ($defaultSettings['mail_batch_size'] ?? 5));
    foreach ($dashboardModel->getMailConfigs() as $mailConfig) {
      $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
      $batchSize = max($batchSize, max(1, (int) ($mailSettings['mail_batch_size'] ?? 1)));
    }
    $table = $wpdb->prefix . 'ppsgen_eca_mail_queue';
    $lockExpiredAt = $siteNow->modify('-10 minutes')->format('Y-m-d H:i:s');
    $wpdb->query($wpdb->prepare("UPDATE {$table} SET locked_at = NULL, lock_token = NULL WHERE locked_at IS NOT NULL AND locked_at < %s", $lockExpiredAt));
    $lockToken = wp_generate_uuid4();
    $locked = $wpdb->query($wpdb->prepare("UPDATE {$table} SET locked_at = %s, lock_token = %s WHERE locked_at IS NULL ORDER BY id ASC LIMIT %d ", $now, $lockToken, $batchSize));
    if (!$locked) {
      return;
    }
    // ====== FETCH MY JOBS ======
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE lock_token = %s ORDER BY id ASC", $lockToken));
    if (!$items) {
      return;
    }
    $contactFieldMap = $wpdb->get_results("SELECT slug, id  FROM {$wpdb->prefix}ppsgen_contact_fields WHERE 1=1", OBJECT_K);
    $contactFieldMap = array_column($contactFieldMap, 'id', 'slug');
    $startTime = time();
    foreach ($items as $itemIndex => $item) {
      if (time() - $startTime > 55) {
        $remainingItems = array_slice($items, (int) $itemIndex);
        foreach ($remainingItems as $remainingItem) {
          if (!empty($remainingItem->id)) {
            $this->unlockQueueItem((int) $remainingItem->id, $lockToken);
          }
        }
        break;
      }
      $emailData = $this->buildEmailByStep($item->step_id, $item->email, $item->contact_id, $item->flow_id, $item->chain_id, $contactFieldMap);
      if (empty($emailData)) {
        $wpdb->delete($table, ['id' => (int) $item->id, 'lock_token' => $lockToken], ['%d', '%s']);
        continue;
      }
      $mailConfigId = (int) ($emailData['mail_config_id'] ?? 0);
      $configData = $dashboardModel->getResolvedMailConfigRemaining($mailConfigId);
      $configSettings = is_array($configData['settings'] ?? null) ? $configData['settings'] : [];
      $remaining = max(0, (int) ($configSettings['mail_remaining'] ?? 0));
      if ($remaining <= 0) {
        $this->unlockQueueItem((int) $item->id, $lockToken);
        continue;
      }
      try {
        $sent = $this->sendEmail($emailData['to'], $emailData['fromEmail'], $emailData['fromName'], $emailData['subject'], $emailData['html'], $emailData['headers'], [], $mailConfigId);
      } catch (\Throwable $e) {
        $sent = false;
      }
      if ($sent) {
        $dashboardModel->consumeResolvedMailConfigLimit($mailConfigId);
        $wpdb->delete($table, ['id' => (int) $item->id, 'lock_token' => $lockToken], ['%d', '%s']);
        $delay = max(1, (int) ($configSettings['mail_delay'] ?? $delay));
        if ($itemIndex < count($items) - 1) {
          sleep($delay);
        }
      } else {
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET attempts = attempts + 1, locked_at = NULL, lock_token = NULL WHERE id = %d AND lock_token = %s", $item->id, $lockToken));
      }
    }
  }

  public function runEcaAutoBounceCron(): void
  {
    if (!$this->acquireEcaAutoBounceCronLock()) {
      return;
    }
    try {
      $this->ecaAutoBounceCron();
    } finally {
      $this->releaseEcaAutoBounceCronLock();
    }
  }

  protected function acquireEcaAutoBounceCronLock(int $ttl = 120): bool
  {
    $now = time();
    $lock = (int) get_option('ppsgen_eca_auto_bounce_cron_lock');
    if ($lock && $now - $lock < $ttl) {
      return false;
    }
    delete_option('ppsgen_eca_auto_bounce_cron_lock');
    return add_option('ppsgen_eca_auto_bounce_cron_lock', $now, '', 'no');
  }

  protected function releaseEcaAutoBounceCronLock(): void
  {
    delete_option('ppsgen_eca_auto_bounce_cron_lock');
  }

  protected function ecaAutoBounceCron(): void
  {
    if (!$this->getFrame()->useModule('dashboard')) {
      return;
    }
    $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
    $mailConfigs = $dashboardModel->getAutoBounceMailConfigs(time());
    if (empty($mailConfigs)) {
      return;
    }
    foreach ($mailConfigs as $mailConfig) {
      $configId = (int) ($mailConfig['id'] ?? 0);
      if ($this->mailConfigUsesProviderAutoProcessing($mailConfig)) {
        try {
          $this->processProviderAutoMailboxByConfig($mailConfig);
        } catch (\Throwable $e) {
        } finally {
          $dashboardModel->touchAutoBounceMailConfigRun($configId, time());
        }
      } else {
        try {
          $this->deleteUndeliveredEmailsByConfig($mailConfig);
        } catch (\Throwable $e) {
        }
        try {
          $this->syncIncomingContactMailboxActivityByConfig($mailConfig);
        } catch (\Throwable $e) {
        } finally {
          $dashboardModel->touchAutoBounceMailConfigRun($configId, time());
        }
      }
    }
  }

  protected function mailConfigUsesProviderAutoProcessing(array $mailConfig): bool
  {
    $provider = $this->getAutoProcessingMailProvider($mailConfig);
    return $provider && method_exists($provider, 'listAutoProcessingMessages');
  }

  protected function getAutoProcessingMailProvider(array $mailConfig)
  {
    $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
    $providerType = sanitize_key((string) ($mailSettings['provider_type'] ?? ''));
    if ($providerType === '' || $providerType === 'custom_smtp_imap') {
      return null;
    }
    if (!class_exists('MailProviderManagerPpsgen') && defined('PPSGEN_PRO_PLUGIN_DIR')) {
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
    $helper = $this->getFrame()->core('helper');
    if ($helper && method_exists($helper, 'bootProMailProviders')) {
      $helper->bootProMailProviders();
    }
    $manager = $this->getFrame()->core('MailProviderManager');
    if (!$manager || !method_exists($manager, 'getProvider')) {
      return null;
    }
    $provider = $manager->getProvider($providerType);
    if (!$provider || !method_exists($provider, 'getCapabilities')) {
      return null;
    }
    $capabilities = $provider->getCapabilities();
    if (empty($capabilities['can_auto_process_mailbox'])) {
      return null;
    }
    return $provider;
  }

  protected function processProviderAutoMailboxByConfig(array $mailConfig): array
  {
    $provider = $this->getAutoProcessingMailProvider($mailConfig);
    if (!$provider || !method_exists($provider, 'listAutoProcessingMessages')) {
      return ['error' => __('Mail provider does not support automatic mailbox processing.', 'ppsgen')];
    }
    $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
    $configId = (int) ($mailConfig['id'] ?? 0);
    $maxMessagesPerRun = max(1, (int) ($mailSettings['auto_bounce_max_messages_per_run'] ?? 50));
    $providerResult = $provider->listAutoProcessingMessages([
      'mail_config_id' => $configId,
      'mail_config' => $mailConfig,
      'max_messages' => $maxMessagesPerRun,
    ]);
    if (!is_array($providerResult) || !empty($providerResult['error'])) {
      return ['error' => (string) ($providerResult['error'] ?? __('Unable to read provider mailbox.', 'ppsgen'))];
    }
    $messages = !empty($providerResult['messages']) && is_array($providerResult['messages']) ? $providerResult['messages'] : [];
    $stats = [
      'total_found' => 0,
      'processed' => 0,
      'scanned' => 0,
      'matched' => 0,
      'inserted' => 0,
      'updated' => 0,
      'created_contacts' => 0,
      'verified_contacts' => 0,
      'bounced' => 0,
      'tagged' => 0,
      'moved' => 0,
    ];
    if (empty($messages)) {
      return $stats;
    }
    $stats['total_found'] = count($messages);
    $contactsModel = $this->getFrame()->getModule('contact')->getModel();
    $ownMailbox = (string) ($mailSettings['workspace_email'] ?? ($mailSettings['m365_email'] ?? ($mailSettings['mail_from_email'] ?? '')));
    $ownEmails = $this->getIncomingContactMailboxOwnEmails($mailSettings, $ownMailbox);
    $createUnknownContacts = !empty($mailSettings['auto_bounce_create_unknown_contacts']);
    $mailboxPath = (string) ($providerResult['mailbox_path'] ?? sanitize_key((string) ($mailSettings['provider_type'] ?? 'provider_api')));
    $mailboxHash = (string) ($providerResult['mailbox_hash'] ?? md5($mailboxPath));
    $cursorKey = sanitize_key((string) ($providerResult['cursor_key'] ?? 'auto_provider_last_cursor'));
    $cursorKey = $cursorKey !== '' ? $cursorKey : 'auto_provider_last_cursor';
    $maxCursor = max(0, (int) ($mailSettings[$cursorKey] ?? 0));
    $now = current_time('mysql');
    $bounceContactTagIds = [];
    $messagesToTrash = [];
    foreach ($messages as $message) {
      $stats['scanned']++;
      $stats['processed']++;
      $internalDate = max(0, (int) ($message['internal_date'] ?? 0));
      if ($internalDate > $maxCursor && (!array_key_exists('cursor_eligible', $message) || !empty($message['cursor_eligible']))) {
        $maxCursor = $internalDate;
      }
      $deliveryStatusBody = (string) ($message['delivery_status_body'] ?? '');
      $subject = $this->truncateIncomingContactMailboxText((string) ($message['subject'] ?? ''), 255);
      if ($this->providerMessageIsBounce($message, $deliveryStatusBody)) {
        $stats['bounced']++;
        if (empty($bounceContactTagIds)) {
          $bounceContactTagIds = $this->resolveBounceContactTagIds($mailSettings);
        }
        foreach ($this->extractBounceRecipientsFromProviderMessage($message, $deliveryStatusBody) as $email) {
          $contactId = (int) $contactsModel->getContactIdByEmail($email);
          if ($contactId <= 0) {
            continue;
          }
          $addedTagIds = !empty($bounceContactTagIds) ? $contactsModel->addContactTags($bounceContactTagIds, $contactId, 'eca') : [];
          if (!empty($addedTagIds)) {
            $stats['tagged']++;
          }
        }
        if (!empty($mailSettings['auto_bounce_move_to_trash']) && !empty($message['provider_message_id'])) {
          $messagesToTrash[] = (string) $message['provider_message_id'];
        }
        continue;
      }
      $fromEmail = sanitize_email((string) ($message['from_email'] ?? ''));
      if (!is_email($fromEmail) || in_array(strtolower($fromEmail), $ownEmails, true)) {
        continue;
      }
      $contactId = (int) $contactsModel->getContactIdByEmail($fromEmail);
      if ($contactId <= 0) {
        if (!$createUnknownContacts) {
          continue;
        }
        $contactId = $this->createIncomingUnknownContactByMailboxSender($mailSettings, $fromEmail, (string) ($message['from_name'] ?? ''), $subject);
        if ($contactId <= 0) {
          continue;
        }
        $stats['created_contacts']++;
      }
      if ($this->verifyIncomingMailboxContactIfNeeded($contactId, $contactsModel)) {
        $stats['verified_contacts']++;
      }
      $stats['matched']++;
      $messageTimestamp = !empty($message['timestamp']) ? (int) $message['timestamp'] : 0;
      $receivedAt = $messageTimestamp > 0 ? wp_date('Y-m-d H:i:s', $messageTimestamp, wp_timezone()) : $now;
      $persistStatus = $this->upsertIncomingContactMailboxMessage([
        'contact_id' => $contactId,
        'mail_config_id' => $configId,
        'mailbox_hash' => $mailboxHash,
        'mailbox_path' => $mailboxPath,
        'imap_uid' => (int) ($message['uid'] ?? 0),
        'message_id' => !empty($message['message_id']) ? trim((string) $message['message_id'], '<>') : (string) ($message['provider_message_id'] ?? ''),
        'from_email' => $fromEmail,
        'from_name' => (string) ($message['from_name'] ?? ''),
        'to_email' => sanitize_email((string) ($message['to_email'] ?? '')),
        'subject' => $subject,
        'received_at' => $receivedAt,
        'detected_at' => $now,
        'mailbox_seen' => !empty($message['seen']) ? 1 : 0,
      ]);
      if ($persistStatus === 'inserted') {
        $stats['inserted']++;
      } elseif ($persistStatus === 'updated') {
        $stats['updated']++;
      }
    }
    if (!empty($messagesToTrash) && method_exists($provider, 'finalizeAutoProcessingMessages')) {
      $provider->finalizeAutoProcessingMessages([
        'mail_config_id' => $configId,
        'mail_config' => $mailConfig,
        'message_ids' => $messagesToTrash,
      ]);
      $stats['moved'] = count(array_unique($messagesToTrash));
    }
    if ($maxCursor > (int) ($mailSettings[$cursorKey] ?? 0) && $this->getFrame()->useModule('dashboard')) {
      $this->getFrame()->getModule('dashboard')->getModel()->updateResolvedMailConfigSettings($configId, [
        $cursorKey => $maxCursor,
      ]);
    }
    return $stats;
  }

  protected function providerMessageIsBounce(array $message, string $deliveryStatusBody): bool
  {
    if (!empty($message['is_bounce'])) {
      return true;
    }
    if ($deliveryStatusBody !== '' && preg_match('/Status:\s*(4|5)\.\d+\.\d+/i', $deliveryStatusBody)) {
      return true;
    }
    $headers = !empty($message['headers']) && is_array($message['headers']) ? $message['headers'] : [];
    $subject = strtolower((string) ($message['subject'] ?? ''));
    $from = strtolower((string) ($message['from'] ?? ($message['from_email'] ?? '')));
    $contentType = strtolower((string) ($headers['content-type'] ?? ($headers['Content-Type'] ?? '')));
    $autoSubmitted = strtolower((string) ($headers['auto-submitted'] ?? ($headers['Auto-Submitted'] ?? '')));
    if ($deliveryStatusBody !== '' && preg_match('/Action:\s*failed/i', $deliveryStatusBody) && (
      strpos($contentType, 'report-type=delivery-status') !== false
      || strpos($from, 'mailer-daemon') !== false
      || strpos($from, 'mail delivery subsystem') !== false
      || !empty($message['failed_recipients'])
    )) {
      return true;
    }
    if (!empty($message['failed_recipients']) || !empty($headers['x-failed-recipients']) || !empty($headers['X-Failed-Recipients'])) {
      return strpos($subject, 'delivery status notification') !== false
        || strpos($subject, 'delivery failure') !== false
        || strpos($from, 'mailer-daemon') !== false
        || strpos($from, 'mail delivery subsystem') !== false
        || strpos($contentType, 'report-type=delivery-status') !== false
        || strpos($autoSubmitted, 'auto-') === 0;
    }
    return false;
  }

  protected function extractBounceRecipientsFromProviderMessage(array $message, string $deliveryStatusBody): array
  {
    $emails = $this->extractBounceRecipientsFromDeliveryStatus($deliveryStatusBody);
    $providerRecipients = $message['failed_recipients'] ?? [];
    if (is_string($providerRecipients)) {
      $providerRecipients = preg_split('/[\s,;]+/', $providerRecipients) ?: [];
    }
    if (is_array($providerRecipients)) {
      $emails = array_merge($emails, $providerRecipients);
    }
    $headers = !empty($message['headers']) && is_array($message['headers']) ? $message['headers'] : [];
    foreach (['x-failed-recipients', 'X-Failed-Recipients'] as $headerName) {
      if (!empty($headers[$headerName])) {
        $emails = array_merge($emails, $this->extractEmailAddressesFromBounceText((string) $headers[$headerName]));
      }
    }
    if (empty($emails)) {
      $body = (string) ($message['body'] ?? '');
      if ($this->incomingMailboxTextLooksLikeDeliveryFailure($body)) {
        $emails = array_merge($emails, $this->extractEmailAddressesFromBounceText($body));
      }
    }
    return $this->sanitizeBounceRecipientEmails($emails);
  }

  protected function incomingMailboxTextLooksLikeDeliveryFailure(string $body): bool
  {
    return stripos($body, "wasn't delivered to") !== false
      || stripos($body, 'was not delivered to') !== false
      || stripos($body, "couldn't be delivered") !== false
      || stripos($body, 'could not be delivered') !== false
      || stripos($body, 'delivery incomplete') !== false
      || stripos($body, 'delivery has failed') !== false
      || stripos($body, 'message not delivered') !== false
      || stripos($body, 'address not found') !== false;
  }

  protected function extractBounceRecipientsFromDeliveryStatus(string $deliveryStatusBody): array
  {
    $deliveryStatusBody = $this->decodeMaybeQuotedPrintableBounceText($deliveryStatusBody);
    preg_match_all('/(?:Final-Recipient|Original-Recipient):\s*rfc822;\s*<?([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})>?/i', $deliveryStatusBody, $matches);
    if (empty($matches[1])) {
      return [];
    }
    return $this->sanitizeBounceRecipientEmails($matches[1]);
  }

  protected function extractEmailAddressesFromBounceText(string $text): array
  {
    $text = $this->decodeMaybeQuotedPrintableBounceText($text);
    preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $matches);
    return !empty($matches[0]) ? (array) $matches[0] : [];
  }

  protected function sanitizeBounceRecipientEmails(array $emails): array
  {
    $clean = [];
    foreach ($emails as $email) {
      $email = sanitize_email(trim((string) $email, " \t\n\r\0\x0B<>.,;:"));
      if (is_email($email)) {
        $clean[] = strtolower($email);
      }
    }
    return array_values(array_unique($clean));
  }

  protected function decodeMaybeQuotedPrintableBounceText(string $value): string
  {
    if ($value !== '' && (strpos($value, '=3D') !== false || preg_match("/=\r?\n/", $value))) {
      $decoded = quoted_printable_decode($value);
      if (is_string($decoded) && $decoded !== '') {
        return $decoded;
      }
    }
    return $value;
  }

  protected function syncIncomingContactMailboxActivityByConfig(array $mailConfig): array
  {
    $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
    $host = (string) ($mailSettings['imap_server_name'] ?? '');
    $user = (string) ($mailSettings['smtp_login'] ?? '');
    $pass = (string) ($mailSettings['smtp_password'] ?? '');
    $port = (int) ($mailSettings['imap_port'] ?? 0);
    $secure = !empty($mailSettings['smtp_secure']) ? (string) $mailSettings['smtp_secure'] : 'ssl';
    $maxMessagesPerRun = max(1, (int) ($mailSettings['auto_bounce_max_messages_per_run'] ?? 50));
    $configId = (int) ($mailConfig['id'] ?? 0);
    $stats = [
      'scanned' => 0,
      'matched' => 0,
      'inserted' => 0,
      'updated' => 0,
      'created_contacts' => 0,
      'verified_contacts' => 0,
    ];
    if (!$host || !$user || !$pass || !$port || !function_exists('imap_open') || !function_exists('imap_fetch_overview') || !function_exists('imap_status')) {
      return $stats;
    }
    $mailbox = sprintf('{%s:%d/imap/%s}INBOX', $host, $port, $secure);
    imap_timeout(IMAP_OPENTIMEOUT, 20);
    imap_timeout(IMAP_READTIMEOUT, 20);
    $imap = @imap_open($mailbox, $user, $pass);
    if (!$imap) {
      return $stats;
    }
    $status = @imap_status($imap, $mailbox, SA_UIDNEXT);
    $uidNext = !empty($status->uidnext) ? (int) $status->uidnext : 0;
    $lastUid = max(0, (int) ($mailSettings['auto_incoming_last_uid'] ?? 0));
    if ($uidNext <= 0) {
      imap_close($imap);
      return $stats;
    }
    if ($lastUid <= 0) {
      $startUid = max(1, $uidNext - $maxMessagesPerRun);
    } else {
      $startUid = $lastUid + 1;
    }
    $endUid = min($uidNext - 1, $startUid + $maxMessagesPerRun - 1);
    if ($endUid < $startUid) {
      if ($uidNext > 1 && $lastUid < ($uidNext - 1)) {
        $this->updateIncomingContactMailboxCursor($configId, $uidNext - 1);
      }
      imap_close($imap);
      return $stats;
    }
    $contactsModel = $this->getFrame()->getModule('contact')->getModel();
    $ownEmails = $this->getIncomingContactMailboxOwnEmails($mailSettings, $user);
    $createUnknownContacts = !empty($mailSettings['auto_bounce_create_unknown_contacts']);
    $now = current_time('mysql');
    $mailboxHash = md5($mailbox);
    for ($uid = $startUid; $uid <= $endUid; $uid++) {
      $overviewRows = @imap_fetch_overview($imap, (string) $uid, FT_UID);
      $overview = !empty($overviewRows[0]) ? $overviewRows[0] : null;
      if (empty($overview)) {
        continue;
      }
      $stats['scanned']++;
      $fromAddress = $this->extractIncomingContactMailboxAddress((string) ($overview->from ?? ''));
      $toAddress = $this->extractIncomingContactMailboxAddress((string) ($overview->to ?? ''));
      $fromEmail = sanitize_email((string) ($fromAddress['email'] ?? ''));
      $subject = $this->truncateIncomingContactMailboxText($this->decodeIncomingContactMailboxHeader((string) ($overview->subject ?? '')), 255);
      if (!is_email($fromEmail) || in_array(strtolower($fromEmail), $ownEmails, true)) {
        continue;
      }
      $contactId = (int) $contactsModel->getContactIdByEmail($fromEmail);
      if ($contactId <= 0) {
        if (!$createUnknownContacts) {
          continue;
        }
        $contactId = $this->createIncomingUnknownContactByMailboxSender($mailSettings, $fromEmail, (string) ($fromAddress['name'] ?? ''), $subject);
        if ($contactId <= 0) {
          continue;
        }
        $stats['created_contacts']++;
      }
      if ($this->verifyIncomingMailboxContactIfNeeded($contactId, $contactsModel)) {
        $stats['verified_contacts']++;
      }
      $stats['matched']++;
      $messageTimestamp = !empty($overview->date) ? strtotime((string) $overview->date) : 0;
      $receivedAt = $messageTimestamp > 0 ? wp_date('Y-m-d H:i:s', $messageTimestamp, wp_timezone()) : $now;
      $persistStatus = $this->upsertIncomingContactMailboxMessage([
        'contact_id' => $contactId,
        'mail_config_id' => $configId,
        'mailbox_hash' => $mailboxHash,
        'mailbox_path' => $mailbox,
        'imap_uid' => $uid,
        'message_id' => !empty($overview->message_id) ? trim((string) $overview->message_id, '<>') : '',
        'from_email' => $fromEmail,
        'from_name' => (string) ($fromAddress['name'] ?? ''),
        'to_email' => sanitize_email((string) ($toAddress['email'] ?? '')),
        'subject' => $subject,
        'received_at' => $receivedAt,
        'detected_at' => $now,
        'mailbox_seen' => !empty($overview->seen) ? 1 : 0,
      ]);
      if ($persistStatus === 'inserted') {
        $stats['inserted']++;
      } elseif ($persistStatus === 'updated') {
        $stats['updated']++;
      }
    }
    imap_close($imap);
    $this->updateIncomingContactMailboxCursor($configId, $endUid);
    return $stats;
  }

  protected function createIncomingUnknownContactByMailboxSender(array $mailSettings, string $fromEmail, string $fromName = '', string $subject = ''): int
  {
    $fromEmail = sanitize_email($fromEmail);
    if (!is_email($fromEmail) || $this->shouldSkipIncomingUnknownContactCreation($fromEmail, $subject)) {
      return 0;
    }
    $contactsModel = $this->getFrame()->getModule('contact')->getModel();
    $existingContactId = (int) $contactsModel->getContactIdByEmail($fromEmail);
    if ($existingContactId > 0) {
      return $existingContactId;
    }
    $preparedFields = [
      'email' => $fromEmail,
    ];
    $preparedName = $this->prepareIncomingUnknownContactName($fromName, $fromEmail);
    if ($preparedName !== '') {
      $nameParts = preg_split('/\s+/', $preparedName, 2);
      if (is_array($nameParts) && !empty($nameParts[0])) {
        $preparedFields['first_name'] = sanitize_text_field((string) $nameParts[0]);
        if (!empty($nameParts[1])) {
          $preparedFields['last_name'] = sanitize_text_field((string) $nameParts[1]);
        }
      }
    }
    $tagIds = $this->resolveIncomingUnknownContactTagIds($mailSettings);
    $contactId = $contactsModel->createContactBySubmit([
      'user_id' => 0,
      'verified' => 1,
      'fields' => $preparedFields,
      'tag_ids' => $tagIds,
      'initiator' => 'incoming_mailbox_autoimport',
    ]);
    return !empty($contactId) ? (int) $contactId : 0;
  }

  protected function verifyIncomingMailboxContactIfNeeded(int $contactId, $contactsModel): bool
  {
    if ($contactId <= 0 || empty($contactsModel) || !method_exists($contactsModel, 'contactIsverified') || !method_exists($contactsModel, 'verifyContact')) {
      return false;
    }
    if (!empty($contactsModel->contactIsverified($contactId))) {
      return false;
    }
    return $contactsModel->verifyContact($contactId) !== false;
  }

  protected function shouldSkipIncomingUnknownContactCreation(string $fromEmail, string $subject = ''): bool
  {
    $fromEmail = strtolower(sanitize_email($fromEmail));
    if ($fromEmail === '') {
      return true;
    }
    $localPart = strstr($fromEmail, '@', true);
    $blockedLocalParts = ['mailer-daemon', 'postmaster'];
    if ($localPart !== false && in_array($localPart, $blockedLocalParts, true)) {
      return true;
    }
    $normalizedSubject = strtolower(trim(wp_strip_all_tags($subject)));
    if ($normalizedSubject !== '' && preg_match('/delivery status notification|undeliverable|delivery failure|mail delivery subsystem/i', $normalizedSubject)) {
      return true;
    }
    return false;
  }

  protected function prepareIncomingUnknownContactName(string $fromName, string $fromEmail): string
  {
    $fromName = $this->truncateIncomingContactMailboxText($fromName, 120);
    $fromName = trim(wp_strip_all_tags($fromName));
    if ($fromName === '' || strcasecmp($fromName, $fromEmail) === 0) {
      return '';
    }
    if (strpos($fromName, '@') !== false) {
      return '';
    }
    $fromName = preg_replace('/\s+/', ' ', $fromName);
    return is_string($fromName) ? sanitize_text_field($fromName) : '';
  }

  protected function resolveIncomingUnknownContactTagIds(array $mailSettings): array
  {
    $selectedTagIds = !empty($mailSettings['auto_bounce_unknown_contact_tag_ids']) && is_array($mailSettings['auto_bounce_unknown_contact_tag_ids'])
      ? array_values(array_unique(array_filter(array_map('absint', $mailSettings['auto_bounce_unknown_contact_tag_ids']))))
      : [];
    $resolvedTagIds = $this->filterExistingContactTagIds($selectedTagIds);
    $tagSlugs = $this->parseContactTagSlugsInput((string) ($mailSettings['auto_bounce_unknown_contact_tag_slugs'] ?? ''));
    foreach ($tagSlugs as $tagSlug) {
      $tagId = $this->getOrCreateContactTagIdBySlug($tagSlug);
      if ($tagId > 0) {
        $resolvedTagIds[] = $tagId;
      }
    }
    return array_values(array_unique(array_filter(array_map('absint', $resolvedTagIds))));
  }

  protected function filterExistingContactTagIds(array $tagIds): array
  {
    global $wpdb;
    $tagIds = array_values(array_unique(array_filter(array_map('absint', $tagIds))));
    if (empty($tagIds)) {
      return [];
    }
    $placeholders = implode(',', array_fill(0, count($tagIds), '%d'));
    return array_map('intval', (array) $wpdb->get_col(
      $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id IN ($placeholders)",
        ...$tagIds,
      ),
    ));
  }

  protected function parseContactTagSlugsInput(string $tagSlugs, string $fallbackSlug = ''): array
  {
    if ($tagSlugs === '' && $fallbackSlug !== '') {
      $tagSlugs = $fallbackSlug;
    }
    if ($tagSlugs === '') {
      return [];
    }
    $parts = preg_split('/[\r\n,]+/', $tagSlugs);
    if (!is_array($parts)) {
      return [];
    }
    return array_values(array_unique(array_filter(array_map(static function ($slug) {
      return sanitize_key((string) $slug);
    }, $parts))));
  }

  protected function updateIncomingContactMailboxCursor(int $configId, int $lastUid): bool
  {
    if (!$this->getFrame()->useModule('dashboard')) {
      return false;
    }
    $lastUid = max(0, (int) $lastUid);
    return $this->getFrame()->getModule('dashboard')->getModel()->updateResolvedMailConfigSettings($configId, [
      'auto_incoming_last_uid' => $lastUid,
    ]);
  }

  protected function upsertIncomingContactMailboxMessage(array $data): string
  {
    global $wpdb;
    $table = $this->getIncomingEmailTable();
    $mailConfigId = (int) ($data['mail_config_id'] ?? 0);
    $mailboxHash = (string) ($data['mailbox_hash'] ?? '');
    $imapUid = (int) ($data['imap_uid'] ?? 0);
    if ($mailboxHash === '' || $imapUid <= 0 || empty($data['contact_id'])) {
      return 'skipped';
    }
    $existingId = (int) $wpdb->get_var(
      $wpdb->prepare(
        "SELECT id FROM {$table} WHERE mail_config_id = %d AND mailbox_hash = %s AND imap_uid = %d LIMIT 1",
        $mailConfigId,
        $mailboxHash,
        $imapUid,
      ),
    );
    $baseData = [
      'contact_id' => (int) ($data['contact_id'] ?? 0),
      'mail_config_id' => $mailConfigId,
      'mailbox_hash' => $mailboxHash,
      'mailbox_path' => (string) ($data['mailbox_path'] ?? ''),
      'imap_uid' => $imapUid,
      'message_id' => $this->truncateIncomingContactMailboxText((string) ($data['message_id'] ?? ''), 191),
      'from_email' => sanitize_email((string) ($data['from_email'] ?? '')),
      'from_name' => $this->truncateIncomingContactMailboxText((string) ($data['from_name'] ?? ''), 255),
      'to_email' => sanitize_email((string) ($data['to_email'] ?? '')),
      'subject' => $this->truncateIncomingContactMailboxText((string) ($data['subject'] ?? ''), 255),
      'received_at' => (string) ($data['received_at'] ?? current_time('mysql')),
      'detected_at' => (string) ($data['detected_at'] ?? current_time('mysql')),
      'mailbox_seen' => !empty($data['mailbox_seen']) ? 1 : 0,
      'updated_at' => current_time('mysql'),
    ];
    if ($existingId > 0) {
      $updated = $wpdb->update(
        $table,
        [
          'mailbox_seen' => $baseData['mailbox_seen'],
          'updated_at' => $baseData['updated_at'],
          'subject' => $baseData['subject'],
          'from_name' => $baseData['from_name'],
        ],
        ['id' => $existingId],
        ['%d', '%s', '%s', '%s'],
        ['%d'],
      );
      return $updated === false ? 'skipped' : 'updated';
    }
    $inserted = $wpdb->insert(
      $table,
      array_merge($baseData, [
        'created_at' => current_time('mysql'),
      ]),
      ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'],
    );
    return !empty($inserted) ? 'inserted' : 'skipped';
  }

  protected function getIncomingContactMailboxOwnEmails(array $mailSettings, string $user): array
  {
    $emails = [];
    $emails[] = sanitize_email($user);
    $emails[] = sanitize_email((string) ($mailSettings['mail_from_email'] ?? ''));
    return array_values(array_unique(array_filter(array_map('strtolower', $emails))));
  }

  protected function extractIncomingContactMailboxAddress(string $header): array
  {
    $result = [
      'email' => '',
      'name' => '',
    ];
    if ($header === '' || !function_exists('imap_rfc822_parse_adrlist')) {
      return $result;
    }
    $parsed = @imap_rfc822_parse_adrlist($header, '');
    if (empty($parsed) || !is_array($parsed)) {
      return $result;
    }
    foreach ($parsed as $address) {
      $mailbox = !empty($address->mailbox) ? (string) $address->mailbox : '';
      $host = !empty($address->host) ? (string) $address->host : '';
      if ($mailbox === '' || strtoupper($mailbox) === 'UNKNOWN' || $host === '' || strtoupper($host) === '.SYNTAX-ERROR.') {
        continue;
      }
      $result['email'] = sanitize_email($mailbox . '@' . $host);
      $result['name'] = !empty($address->personal) ? $this->truncateIncomingContactMailboxText($this->decodeIncomingContactMailboxHeader((string) $address->personal), 255) : '';
      break;
    }
    return $result;
  }

  protected function decodeIncomingContactMailboxHeader(string $value): string
  {
    if ($value === '' || !function_exists('imap_mime_header_decode')) {
      return trim(wp_strip_all_tags($value));
    }
    $decodedParts = @imap_mime_header_decode($value);
    if (empty($decodedParts) || !is_array($decodedParts)) {
      return trim(wp_strip_all_tags($value));
    }
    $decoded = '';
    foreach ($decodedParts as $part) {
      $text = (string) ($part->text ?? '');
      $charset = (string) ($part->charset ?? 'default');
      if ($charset !== '' && strtolower($charset) !== 'default' && strtoupper($charset) !== 'UTF-8' && function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($text, 'UTF-8', $charset);
        if (is_string($converted) && $converted !== '') {
          $text = $converted;
        }
      }
      $decoded .= $text;
    }
    return trim(wp_strip_all_tags($decoded));
  }

  protected function truncateIncomingContactMailboxText(string $value, int $limit): string
  {
    $value = trim(wp_strip_all_tags($value));
    if ($value === '') {
      return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      if (mb_strlen($value) > $limit) {
        return mb_substr($value, 0, $limit);
      }
      return $value;
    }
    return strlen($value) > $limit ? substr($value, 0, $limit) : $value;
  }

  protected function buildEcaActionHash(int $contactId, int $flowId, int $chainId, int $stepId, string $action = 'open', string $url = '-'): string
  {
    $encodedUrl = rawurlencode($url);
    $timestamp = time();
    $payload = implode('|', [$contactId, $flowId, $chainId, $stepId, $action, $encodedUrl, $timestamp]);
    $signature = hash_hmac('sha256', $payload, wp_salt('auth'));
    // payload + signature → base64url
    return rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');
  }

  protected function buildUnsubscribeHash(int $contactId, int $chainId, int $stepId, string $email): string
  {
    $timestamp = time();
    $payload = implode('|', [$contactId, $chainId, $stepId, strtolower($email), $timestamp]);
    $signature = hash_hmac('sha256', $payload, wp_salt('auth'));
    return rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');
  }

  public function unsubscribeContact($contact_id, $chain_id, $step_id = 0)
  {
    global $wpdb;
    $flow = $wpdb->get_row($wpdb->prepare("SELECT id, current_step_id FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d AND contact_id = %d AND status = 'active'", $chain_id, $contact_id), ARRAY_A);
    $flow_id = !empty($flow['id']) ? (int) $flow['id'] : 0;
    if (!empty($flow_id)) {
      $now = current_time('mysql');
      $step_id = !empty($step_id) ? (int) $step_id : (int) ($flow['current_step_id'] ?? 0);
      $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['status' => 'unsubscribed'], ['id' => $flow_id]);
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_flow_logs", ['contact_id' => $contact_id, 'flow_id' => $flow_id, 'chain_id' => $chain_id, 'step_id' => $step_id, 'run_at' => $now, 'status' => 'unsubscribed']);
      $this->applyUnsubscribeContactTags((int) $contact_id, (int) $chain_id, $step_id);
      return true;
    }
    return false;
  }

  public function canUnsubscribeContact($contact_id, $chain_id)
  {
    global $wpdb;
    return $flow_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d AND contact_id = %d AND status = 'active'", $chain_id, $contact_id));
  }

  public function parseUnsubscribeHash(string $hash)
  {
    $decoded = base64_decode(strtr($hash, '-_', '+/'));
    if (!$decoded) {
      return false;
    }
    $parts = explode('|', $decoded);
    if (!in_array(count($parts), [5, 6], true)) {
      return false;
    }
    $stepId = 0;
    if (count($parts) === 6) {
      [$contactId, $chainId, $stepId, $email, $timestamp, $signature] = $parts;
      $payload = implode('|', [$contactId, $chainId, $stepId, strtolower($email), $timestamp]);
    } else {
      [$contactId, $chainId, $email, $timestamp, $signature] = $parts;
      $payload = implode('|', [$contactId, $chainId, strtolower($email), $timestamp]);
    }
    $expected = hash_hmac('sha256', $payload, wp_salt('auth'));
    if (!hash_equals($expected, $signature)) {
      return false;
    }
    if ($timestamp < time() - DAY_IN_SECONDS * 365) {
      return false;
    }
    return [
      'contact_id' => (int) $contactId,
      'chain_id' => (int) $chainId,
      'step_id' => (int) $stepId,
      'email' => $email,
    ];
  }

  protected function getChainTrackingQueryPrefixFromSettings(array $chainSettings): string
  {
    $campaignPrefix = $chainSettings['eca_tracking_query_prefix'] ?? '';
    if (function_exists('ppsgen_sanitize_eca_tracking_query_prefix')) {
      $campaignPrefix = ppsgen_sanitize_eca_tracking_query_prefix($campaignPrefix);
    } else {
      $campaignPrefix = sanitize_key((string) $campaignPrefix);
    }
    if ($campaignPrefix !== '') {
      return $campaignPrefix;
    }
    return function_exists('ppsgen_get_eca_tracking_query_prefix') ? ppsgen_get_eca_tracking_query_prefix() : '';
  }

  public function getChainTrackingQueryPrefix(int $chainId): string
  {
    $chainRow = $this->getChainById($chainId);
    $chainSettings = !empty($chainRow['settings']) ? $this->_prepareValueFromStore($chainRow['settings'], 'json') : [];
    return $this->getChainTrackingQueryPrefixFromSettings(is_array($chainSettings) ? $chainSettings : []);
  }

  public function isEcaTrackingRequestAllowedForChain(string $type, int $chainId): bool
  {
    if ($chainId <= 0) {
      return false;
    }
    $requestKey = function_exists('ppsgen_get_eca_tracking_request_key') ? ppsgen_get_eca_tracking_request_key($type) : '';
    if ($requestKey === '') {
      return false;
    }
    if (in_array($requestKey, ['ppsgen_eca_action', 'ppsgen_eca_unsubscribe'], true)) {
      return true;
    }
    return hash_equals($this->getEcaTrackingQueryArg($type, $this->getChainTrackingQueryPrefix($chainId)), $requestKey);
  }

  protected function getEcaTrackingQueryArg(string $type, string $prefix = ''): string
  {
    if (function_exists('ppsgen_get_eca_tracking_query_arg')) {
      return ppsgen_get_eca_tracking_query_arg($type, $prefix);
    }
    return $type === 'unsubscribe' ? 'ppsgen_eca_unsubscribe' : 'ppsgen_eca_action';
  }

  protected function isGmailRecipient(string $email): bool
  {
    $email = strtolower(trim($email));
    if ($email === '' || strpos($email, '@') === false) {
      return false;
    }
    $domain = substr(strrchr($email, '@'), 1);
    return in_array($domain, ['gmail.com', 'googlemail.com'], true);
  }

  protected function pickRandomEmailSubject($subject): string
  {
    $subject = is_scalar($subject) ? (string) $subject : '';
    $variants = preg_split('/[\r\n,]+/', $subject);
    if (!is_array($variants)) {
      $variants = [$subject];
    }
    $variants = array_values(array_filter(array_map(static function ($variant) {
      $variant = trim(wp_strip_all_tags((string) $variant));
      $variant = str_replace(["\r", "\n"], ' ', $variant);
      return preg_replace('/\s+/', ' ', $variant);
    }, $variants), static function ($variant) {
      return $variant !== '';
    }));
    if (empty($variants)) {
      return '';
    }
    return $variants[wp_rand(0, count($variants) - 1)];
  }

  protected function buildEmailByStep(int $stepId, string $email, int $contactId, int $flowId, int $chainId, array $contactFieldMap)
  {
    global $wpdb;
    $stepData = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", $stepId), ARRAY_A);
    if (empty($stepData)) {
      return false;
    }
    $settings = $this->_prepareValueFromStore($stepData['settings'], 'json');
    $emailSubject = $this->pickRandomEmailSubject($settings['email_subject'] ?? '');
    $html = $stepData['html'];
    $chainRow = $this->getChainById($chainId);
    $chainSettings = !empty($chainRow['settings']) ? $this->_prepareValueFromStore($chainRow['settings'], 'json') : [];
    $chainSettings = is_array($chainSettings) ? $chainSettings : [];
    $trackingPrefix = $this->getChainTrackingQueryPrefixFromSettings($chainSettings);
    $sendWithoutOpenTracking = !empty($settings['send_without_open_tracking']) || !empty($settings['send_without_tracking']);
    $sendWithoutLinkTracking = !empty($settings['send_without_link_tracking']) || !empty($settings['send_without_tracking']);
    $sendWithoutUnsubscribe = !empty($settings['send_without_unsubscribe']);
    if (!empty($settings['send_gmail_without_tracking']) && $this->isGmailRecipient($email)) {
      $sendWithoutOpenTracking = true;
      $sendWithoutLinkTracking = true;
      $sendWithoutUnsubscribe = true;
    }
    $actionQueryArg = $this->getEcaTrackingQueryArg('action', $trackingPrefix);
    $unsubscribeQueryArg = $this->getEcaTrackingQueryArg('unsubscribe', $trackingPrefix);
    if (!$sendWithoutOpenTracking) {
      //Pixel IMG for Email Event & Analytics START
      $ecaHash = $this->buildEcaActionHash($contactId, $flowId, $chainId, $stepId, 'open');
      $pixelUrl = add_query_arg($actionQueryArg, $ecaHash, home_url('/'));
      $html .= '<img src="' . esc_url($pixelUrl) . '" width="1" height="1" style="display:none" alt="">';
      //Pixel IMG for Email Event & Analytics END
    }
    $unsubscribeUrl = '';
    if ($sendWithoutUnsubscribe) {
      $html = $this->removeUnsubscribeUrlLinks($html);
    } else {
      // Add Ubsubscribe Link START
      $unsubHash = $this->buildUnsubscribeHash($contactId, $chainId, $stepId, $email);
      $unsubscribeUrl = add_query_arg($unsubscribeQueryArg, $unsubHash, home_url('/'));
      $html = str_replace('[unsubscribe_url]', esc_url($unsubscribeUrl), $html);
      // Add Ubsubscribe Link END
    }
    //Replace for {{contact.*}} field values START
    if (!empty($html) && $contactId > 0 && !empty($contactFieldMap) && preg_match_all('/\{\{\s*contact\.([a-zA-Z0-9_]+)(?:\|([^}]+))?\s*\}\}/', $html, $matches, PREG_SET_ORDER)) {
      $slugs = [];
      $placeholdersMap = [];
      foreach ($matches as $match) {
        $slug = $match[1];
        $default = isset($match[2]) ? trim($match[2]) : '';
        $slugs[$slug] = true;
        $placeholdersMap[$slug][] = ['search' => $match[0], 'default' => $default];
      }
      // slug → field_id
      $fieldIds = [];
      $slugToFieldId = [];
      foreach (array_keys($slugs) as $slug) {
        if (isset($contactFieldMap[$slug])) {
          $fieldId = (int) $contactFieldMap[$slug];
          $fieldIds[] = $fieldId;
          $slugToFieldId[$slug] = $fieldId;
        }
      }
      $rows = [];
      if (!empty($fieldIds)) {
        $placeholders = implode(',', array_fill(0, count($fieldIds), '%d'));
        $query = $wpdb->prepare("SELECT field_id, value FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE contact_id = %d AND field_id IN ($placeholders)", array_merge([$contactId], $fieldIds));
        $rows = $wpdb->get_results($query, OBJECT_K);
      }
      foreach ($placeholdersMap as $slug => $items) {
        $fieldId = $slugToFieldId[$slug] ?? null;
        $dbValue = $fieldId && isset($rows[$fieldId]) ? (string) $rows[$fieldId]->value : '';
        foreach ($items as $item) {
          $value = $dbValue !== '' ? $dbValue : $item['default'];
          $html = str_replace($item['search'], esc_html($value), $html);
        }
      }
    }
    //Replace for {{contact.*}} field values END
    // Newsletters: preview text, reply-to and UTM tags (before click tracking, so tracked links keep the UTM tags)
    $extraHeaders = [];
    $html = $this->applyNewsletterExtras($chainId, (string) $html, $extraHeaders);
    // Add Link Click Analytics START
    $siteHost = wp_parse_url(home_url(), PHP_URL_HOST);
    if (!$sendWithoutLinkTracking) {
      $html = preg_replace_callback(
        '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/i',
        function ($m) use ($contactId, $flowId, $chainId, $stepId, $siteHost, $actionQueryArg, $unsubscribeQueryArg) {
          $originalUrl = trim($m[1]);
          if ($originalUrl === '#' || str_starts_with($originalUrl, 'mailto:') || str_starts_with($originalUrl, 'tel:') || str_starts_with($originalUrl, 'javascript:')) {
            return $m[0];
          }
          $parsed = wp_parse_url($originalUrl);
          if (empty($parsed['scheme'])) {
            $absoluteUrl = home_url($originalUrl);
            $parsed = wp_parse_url($absoluteUrl);
          } else {
            $absoluteUrl = $originalUrl;
          }
          if (empty($parsed['host']) || $parsed['host'] !== $siteHost) {
            return str_replace($originalUrl, esc_url($absoluteUrl), $m[0]);
          }
          $query = [];
          if (!empty($parsed['query'])) {
            parse_str((string) $parsed['query'], $query);
          }
          if (isset($query[$unsubscribeQueryArg]) || isset($query['ppsgen_eca_unsubscribe'])) {
            return str_replace($originalUrl, esc_url($absoluteUrl), $m[0]);
          }
          $hash = $this->buildEcaActionHash($contactId, $flowId, $chainId, $stepId, 'click', $absoluteUrl);
          $delimiter = str_contains($absoluteUrl, '?') ? '&' : '?';
          $trackedUrl = $absoluteUrl . $delimiter . rawurlencode($actionQueryArg) . '=' . rawurlencode($hash);
          return str_replace($originalUrl, esc_url($trackedUrl), $m[0]);
        },
        $html,
      );
    }
    // Add Link Click Analytics END
    $html = $this->getView()->getAdditionalFieldsHtml($html);
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    if (!$sendWithoutUnsubscribe) {
      $headers[] = 'List-Unsubscribe: <' . esc_url($unsubscribeUrl) . '>';
      $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
    }
    $headers = array_merge($headers, $extraHeaders);
    return [
      'to' => $email,
      'fromEmail' => '',
      'fromName' => '',
      'subject' => $emailSubject,
      'html' => $html,
      'mail_config_id' => !empty($chainSettings['mail_config_id']) ? (int) $chainSettings['mail_config_id'] : 0,
      'headers' => $headers,
    ];
  }

  protected function removeUnsubscribeUrlLinks(string $html): string
  {
    $html = preg_replace('/<a\b[^>]*href=(["\'])\[unsubscribe_url\]\1[^>]*>.*?<\/a>/is', '', $html);
    return str_replace('[unsubscribe_url]', '', (string) $html);
  }

  protected function unlockQueueItem(int $id, string $lockToken = ''): void
  {
    global $wpdb;
    $where = ['id' => $id];
    if ($lockToken !== '') {
      $where['lock_token'] = $lockToken;
    }
    $wpdb->update($wpdb->prefix . 'ppsgen_eca_mail_queue', ['locked_at' => null, 'lock_token' => null], $where);
  }

  public function sendEmail($to, $fromEmail, $fromName, $subject, $message, $headers = [], $attachments = [], $mailConfigId = 0)
  {
    return $this->getFrame()->core('helper')->sendEmail($to, $fromEmail, $fromName, $subject, $message, $headers, $attachments, $mailConfigId);
  }

  public function deleteUndeliveredEmails()
  {
    $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
    $mailConfigs = $dashboardModel->getBounceMailConfigs();
    if (empty($mailConfigs)) {
      return ['error' => __('SMTP/IMAP is disabled. Bounce processing is unavailable when using PHP mail().', 'ppsgen')];
    }
    $stats = [
      'total_found' => 0,
      'processed' => 0,
      'bounced' => 0,
      'tagged' => 0,
      'moved' => 0,
    ];
    $errors = [];
    foreach ($mailConfigs as $mailConfig) {
      $configStats = $this->mailConfigUsesProviderAutoProcessing($mailConfig)
        ? $this->processProviderAutoMailboxByConfig($mailConfig)
        : $this->deleteUndeliveredEmailsByConfig($mailConfig);
      if (!empty($configStats['error'])) {
        $configName = !empty($mailConfig['name']) ? (string) $mailConfig['name'] : ('#' . (int) ($mailConfig['id'] ?? 0));
        $errors[] = $configName . ': ' . $configStats['error'];
        continue;
      }
      foreach (array_keys($stats) as $key) {
        $stats[$key] += (int) ($configStats[$key] ?? 0);
      }
    }
    if (!empty($errors) && empty(array_filter($stats))) {
      return ['error' => implode(' | ', $errors)];
    }
    if (!empty($errors)) {
      $stats['warnings'] = $errors;
    }
    return $stats;
  }

  protected function deleteUndeliveredEmailsByConfig(array $mailConfig): array
  {
    $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
    $host = (string) ($mailSettings['imap_server_name'] ?? '');
    $user = (string) ($mailSettings['smtp_login'] ?? '');
    $pass = (string) ($mailSettings['smtp_password'] ?? '');
    $port = (int) ($mailSettings['imap_port'] ?? 0);
    $secure = !empty($mailSettings['smtp_secure']) ? (string) $mailSettings['smtp_secure'] : 'ssl';
    $moveToTrash = !empty($mailSettings['auto_bounce_move_to_trash']);
    $maxMessagesPerRun = max(1, (int) ($mailSettings['auto_bounce_max_messages_per_run'] ?? 50));
    if (!$host || !$user || !$pass || !$port) {
      return ['error' => __('SMTP/IMAP settings are incomplete. Cannot connect to mailbox.', 'ppsgen')];
    }
    $mailbox = sprintf('{%s:%d/imap/%s}INBOX', $host, $port, $secure);
    imap_timeout(IMAP_OPENTIMEOUT, 30);
    imap_timeout(IMAP_READTIMEOUT, 30);
    $imap = @imap_open($mailbox, $user, $pass);
    if (!$imap) {
      return ['error' => imap_last_error()];
    }
    $stats = [
      'total_found' => 0,
      'processed' => 0,
      'bounced' => 0,
      'tagged' => 0,
      'moved' => 0,
    ];
    $emails = imap_search($imap, 'UNSEEN');
    if (!$emails || !is_array($emails)) {
      imap_close($imap);
      return $stats;
    }
    $stats['total_found'] = count($emails);
    if (count($emails) > $maxMessagesPerRun) {
      $emails = array_slice($emails, 0, $maxMessagesPerRun);
    }
    $contactsModel = $this->getFrame()->getModule('contact')->getModel();
    $contactTagIds = $this->resolveBounceContactTagIds($mailSettings);
    if (empty($contactTagIds)) {
      imap_close($imap);
      return ['error' => __('Bounce error tags could not be created.', 'ppsgen')];
    }
    $toMove = [];
    foreach ($emails as $emailNumber) {
      $stats['processed']++;
      $structure = imap_fetchstructure($imap, $emailNumber);
      if (empty($structure->parts) || !is_array($structure->parts)) {
        continue;
      }
      $dsnPart = null;
      foreach ($structure->parts as $index => $part) {
        if (!empty($part->subtype) && strtolower($part->subtype) === 'delivery-status') {
          $dsnPart = $index + 1;
          break;
        }
      }
      if (!$dsnPart) {
        continue;
      }
      $dsnBody = imap_fetchbody($imap, $emailNumber, $dsnPart);
      if (!$dsnBody) {
        continue;
      }
      if (!preg_match('/Status:\s*(4|5)\.\d+\.\d+/i', $dsnBody)) {
        continue;
      }
      $stats['bounced']++;
      preg_match_all('/Final-Recipient:\s*rfc822;\s*([^\s]+)/i', $dsnBody, $matches);
      if (!empty($matches[1])) {
        foreach (array_unique($matches[1]) as $email) {
          $email = sanitize_email((string) $email);
          if (!is_email($email)) {
            continue;
          }
          $contactId = $contactsModel->getContactIdByEmail($email);
          if (!$contactId) {
            continue;
          }
            $addedTagIds = $contactsModel->addContactTags($contactTagIds, $contactId, 'eca');
            if (!empty($addedTagIds)) {
              $stats['tagged']++;
            }
          }
        }
      $toMove[] = $emailNumber;
    }
    if ($moveToTrash && !empty($toMove)) {
      $mailboxRoot = sprintf('{%s:%d/imap/%s}', $host, $port, $secure);
      //ppsgenlog(print_r(imap_list($imap, $mailboxRoot, '*'), true));
      $trashMailboxFull = $this->getTrashMailbox($imap, $mailboxRoot);
      if ($trashMailboxFull) {
        $trashMailbox = str_replace($mailboxRoot, '', $trashMailboxFull);
        imap_mail_move($imap, implode(',', $toMove), $trashMailbox);
        $stats['moved'] = count($toMove);
        imap_expunge($imap);
      }
    }
    imap_close($imap);
    return $stats;
  }

  protected function getOrCreateBounceContactTagId(string $tagSlug): int
  {
    return $this->getOrCreateContactTagIdBySlug($tagSlug);
  }

  protected function resolveBounceContactTagIds(array $mailSettings): array
  {
    $selectedTagIds = !empty($mailSettings['auto_bounce_tag_ids']) && is_array($mailSettings['auto_bounce_tag_ids'])
      ? array_values(array_unique(array_filter(array_map('absint', $mailSettings['auto_bounce_tag_ids']))))
      : [];
    $resolvedTagIds = $this->filterExistingContactTagIds($selectedTagIds);
    $tagSlugs = $this->parseContactTagSlugsInput((string) ($mailSettings['auto_bounce_tag_slugs'] ?? ''), sanitize_key((string) ($mailSettings['auto_bounce_tag_slug'] ?? 'email_error')));
    foreach ($tagSlugs as $tagSlug) {
      $tagId = $this->getOrCreateContactTagIdBySlug($tagSlug);
      if ($tagId > 0) {
        $resolvedTagIds[] = $tagId;
      }
    }
    return array_values(array_unique(array_filter(array_map('absint', $resolvedTagIds))));
  }

  protected function getOrCreateContactTagIdBySlug(string $tagSlug): int
  {
    global $wpdb;
    $tagSlug = sanitize_key($tagSlug);
    if ($tagSlug === '') {
      return 0;
    }
    $contactTagId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug = %s", $tagSlug));
    if ($contactTagId > 0) {
      return $contactTagId;
    }
    $inserted = $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags", ['slug' => $tagSlug], ['%s']);
    if (empty($inserted)) {
      return 0;
    }
    return (int) $wpdb->insert_id;
  }

  private function getTrashMailbox($imap, $mailboxRoot)
  {
    $mailboxes = imap_list($imap, $mailboxRoot, '*');
    if (!$mailboxes || !is_array($mailboxes)) {
      return null;
    }
    foreach ($mailboxes as $box) {
      // Gmail, Yandex, Mail.ru, Exchange
      if (preg_match('/trash|deleted items/i', $box)) {
        return $box;
      }
    }

    return null;
  }

  public function duplicateTemplate($obj_id)
  {
    global $wpdb;
    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_templates WHERE id = %d", $obj_id), ARRAY_A);
    $template_id_new = null;
    $now = current_time('mysql');
    if (!empty($template)) {
      $template_data = [
        'name' => $template['name'] . ' Copy',
        'settings' => $template['settings'] ?? '{}',
        'design' => $template['design'] ?? '{}',
        'active' => $template['active'] ?? 0,
        'created_at' => $now,
        'updated_at' => $now,
      ];
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_templates", $template_data);
      $template_id_new = $wpdb->insert_id;
      $this->setStatus('success');
      if (!$template_id_new) {
        return false;
      }
      $template_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_eca_fields WHERE owner_type = 'template' AND owner_id = %d", $obj_id), ARRAY_A);
      if (!empty($template_fields) && is_array($template_fields)) {
        foreach ($template_fields as $field) {
          $field_data = [
            'owner_type' => 'template',
            'owner_id' => $template_id_new,
            'parent_key' => $field['parent_key'] ?? '',
            'fkey' => $field['fkey'] ?? '',
            'type' => $field['type'] ?? '',
            'settings' => $field['settings'] ?? '{}',
            'order_index' => $field['order_index'] ?? 0,
            'created_date' => $now,
            'updated_date' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_eca_fields", $field_data);
        }
      }
    }
    return $template_id_new;
  }

  // "Contact is on list" entry rule: a saved Contacts list (its filters are checked for this contact)
  public function contactPassesEntryList(int $contactId, array $settings): bool
  {
    $listId = sanitize_key((string) ($settings['entry_list_id'] ?? ''));
    if ($listId === '') {
      return true;
    }
    $contact = $this->getFrame()->getModule('contact');
    if (!$contact || !method_exists($contact->getModel(), 'contactMatchesSavedList')) {
      return false;
    }
    // A deleted list lets nobody in rather than everybody
    return $contact->getModel()->contactMatchesSavedList($contactId, $listId);
  }

  // Newsletters never go to people who unsubscribed from any campaign before
  public function contactHasUnsubscribed(int $contactId): bool
  {
    global $wpdb;
    return (bool) $wpdb->get_var($wpdb->prepare(
      "SELECT 1 FROM {$wpdb->prefix}ppsgen_eca_flow WHERE contact_id = %d AND status = 'unsubscribed' LIMIT 1",
      $contactId
    ));
  }

  protected function getExcludeNewslettersSql(string $column): string
  {
    $ids = $this->getNewsletterIds();
    return empty($ids) ? '1=1' : $column . ' NOT IN (' . implode(',', array_map('intval', $ids)) . ')';
  }

  protected function getOnlyNewslettersSql(string $column): string
  {
    $ids = $this->getNewsletterIds();
    return empty($ids) ? '1=0' : $column . ' IN (' . implode(',', array_map('intval', $ids)) . ')';
  }

  //=====================================
  //       NEWSLETTERS
  //=====================================
  // A newsletter is a one-step campaign sent once to the contacts that match its "Send to" tags at send time.
  // It is stored as a normal chain plus a flag in the plugin options table (chain settings are rewritten on every save).
  // Newsletters never pick up contacts automatically and do not count toward the Free automation limit.

  protected ?array $newsletterIds = null;

  public function getNewsletterIds(): array
  {
    global $wpdb;
    if ($this->newsletterIds === null) {
      $this->newsletterIds = array_map('intval', (array) $wpdb->get_col(
        "SELECT mod_obj_id FROM {$wpdb->prefix}ppsgen_options WHERE mod_slug = 'eca' AND option_group = 'newsletter' AND option_name = 'is_newsletter'"
      ));
    }
    return $this->newsletterIds;
  }

  public function isNewsletter($chainId): bool
  {
    return in_array((int) $chainId, $this->getNewsletterIds(), true);
  }

  public function createNewsletter(string $name)
  {
    $chainId = $this->createChain($name !== '' ? $name : __('New newsletter', 'ppsgen'));
    if (empty($chainId)) {
      return false;
    }
    $this->markNewsletter((int) $chainId);
    $this->addNewChainStep((int) $chainId, __('Newsletter email', 'ppsgen'));
    return (int) $chainId;
  }

  public function markNewsletter(int $chainId): void
  {
    $this->updateOption('is_newsletter', 'newsletter', 1, $chainId, 'eca', 'json');
    $this->newsletterIds = null;
  }

  // Newsletter extras (brief: Setup › preview text, "UTM tracking, reply-to address")
  public function getNewsletterExtras(int $chainId): array
  {
    return [
      'preview_text' => (string) $this->getOption('preview_text', 'newsletter', $chainId, 'eca'),
      'reply_to' => (string) $this->getOption('reply_to', 'newsletter', $chainId, 'eca'),
      'utm' => (string) $this->getOption('utm', 'newsletter', $chainId, 'eca') === '1',
    ];
  }

  public function setNewsletterExtras(int $chainId, string $previewText, string $replyTo, bool $utm): array
  {
    if ((int) $this->getOption('sent_at', 'newsletter', $chainId, 'eca') > 0) {
      return ['ok' => false, 'message' => __('This newsletter has already been sent.', 'ppsgen')];
    }
    $previewText = trim(mb_substr(sanitize_text_field($previewText), 0, 150));
    $replyTo = trim($replyTo);
    if ($replyTo !== '' && !is_email($replyTo)) {
      return ['ok' => false, 'message' => __('The reply-to address is not a valid email address.', 'ppsgen')];
    }
    foreach (['preview_text' => $previewText, 'reply_to' => sanitize_email($replyTo), 'utm' => $utm ? '1' : ''] as $name => $value) {
      if ($value === '') {
        $this->deleteOption($name, 'newsletter', $chainId, 'eca');
      } else {
        $this->updateOption($name, 'newsletter', $value, $chainId, 'eca', 'json');
      }
    }
    return ['ok' => true, 'message' => __('Saved.', 'ppsgen')];
  }

  // Adds the preview text (hidden preheader), the Reply-To header and UTM tags on links to this site
  protected function applyNewsletterExtras(int $chainId, string $html, array &$headers): string
  {
    if ($chainId <= 0 || !$this->isNewsletter($chainId)) {
      return $html;
    }
    $extras = $this->getNewsletterExtras($chainId);
    if ($extras['utm']) {
      $chain = $this->getChainById($chainId);
      $campaign = sanitize_title((string) ($chain['name'] ?? '')) ?: 'newsletter-' . $chainId;
      $siteHost = (string) wp_parse_url(home_url(), PHP_URL_HOST);
      $chainSettings = !empty($chain['settings']) ? $this->_prepareValueFromStore($chain['settings'], 'json') : [];
      $unsubscribeArg = $this->getEcaTrackingQueryArg('unsubscribe', $this->getChainTrackingQueryPrefixFromSettings(is_array($chainSettings) ? $chainSettings : []));
      $html = (string) preg_replace_callback('/(<a\s[^>]*href=["\'])([^"\']+)(["\'])/i', static function ($m) use ($siteHost, $campaign, $unsubscribeArg) {
        $url = html_entity_decode(trim($m[2]), ENT_QUOTES);
        if ($url === '' || $url[0] === '#' || $url[0] === '[' || preg_match('/^(mailto|tel|javascript):/i', $url)) {
          return $m[0];
        }
        // Never tag unsubscribe links
        if (str_contains($url, $unsubscribeArg . '=') || str_contains($url, 'ppsgen_eca_unsubscribe=')) {
          return $m[0];
        }
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if (($host !== '' && strcasecmp($host, $siteHost) !== 0) || stripos($url, 'utm_source=') !== false) {
          return $m[0];
        }
        // Plain "&" (not &#038;) so click tracking records the real address; quotes cannot occur (excluded by the pattern)
        return $m[1] . esc_url_raw(add_query_arg(['utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => $campaign], $url)) . $m[3];
      }, $html);
    }
    if ($extras['preview_text'] !== '') {
      // Shown next to the subject in the inbox, never in the email itself
      $html = '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'
        . esc_html($extras['preview_text']) . str_repeat('&#8199;&#65279;&#847; ', 30) . '</div>' . $html;
    }
    if ($extras['reply_to'] !== '' && is_email($extras['reply_to'])) {
      $headers[] = 'Reply-To: ' . $extras['reply_to'];
    }
    return $html;
  }

  public function deleteNewsletterOptions(int $chainId): void
  {
    global $wpdb;
    $wpdb->query($wpdb->prepare(
      "DELETE FROM {$wpdb->prefix}ppsgen_options WHERE mod_slug = 'eca' AND option_group = 'newsletter' AND mod_obj_id = %d",
      $chainId
    ));
    $this->newsletterIds = null;
  }

  // SQL fragment that keeps only contacts matching the chain's "Send to" (entry) tag rules
  protected function getNewsletterAudienceSql(int $chainId): array
  {
    global $wpdb;
    $chain = $this->getChainById($chainId);
    $settings = !empty($chain['settings']) ? $this->_prepareValueFromStore($chain['settings'], 'json') : [];
    $settings = is_array($settings) ? $settings : [];
    $tagsTable = $wpdb->prefix . 'ppsgen_contact_to_tags';
    $where = ['c.deleted_at IS NULL', "NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}ppsgen_eca_flow uf WHERE uf.contact_id = c.id AND uf.status = 'unsubscribed')"];
    $params = [];
    $rules = [
      'entry_tag_yes' => true,
      'entry_tag_not' => false,
    ];
    foreach ($rules as $key => $mustHave) {
      $tags = $this->normalizeConditionIds($settings[$key] ?? []);
      if (empty($tags)) {
        continue;
      }
      $condition = ($settings[$key . '_condition'] ?? 'or') === 'and' ? 'and' : 'or';
      $placeholders = implode(',', array_fill(0, count($tags), '%d'));
      $sub = $condition === 'and'
        ? "(SELECT COUNT(DISTINCT t.tag_id) FROM {$tagsTable} t WHERE t.contact_id = c.id AND t.tag_id IN ($placeholders)) = " . count($tags)
        : "EXISTS (SELECT 1 FROM {$tagsTable} t WHERE t.contact_id = c.id AND t.tag_id IN ($placeholders))";
      $where[] = $mustHave ? $sub : 'NOT ' . $sub;
      $params = array_merge($params, $tags);
    }
    return [implode(' AND ', $where), $params, $settings];
  }

  // "Send to" can be a saved contact list instead of the tag rules
  public function getNewsletterListId(int $chainId): string
  {
    $listId = (string) $this->getOption('list_id', 'newsletter', $chainId, 'eca');
    if ($listId === '') {
      return '';
    }
    $contact = $this->getFrame()->getModule('contact');
    if (!$contact || !method_exists($contact->getModel(), 'getSavedContactList') || empty($contact->getModel()->getSavedContactList($listId))) {
      return '';
    }
    return $listId;
  }

  public function setNewsletterList(int $chainId, string $listId): array
  {
    if ((int) $this->getOption('sent_at', 'newsletter', $chainId, 'eca') > 0) {
      return ['ok' => false, 'message' => __('This newsletter has already been sent.', 'ppsgen')];
    }
    if ($listId === '') {
      $this->deleteOption('list_id', 'newsletter', $chainId, 'eca');
      return ['ok' => true, 'message' => __('Send to: contacts matching the tags in Send to & options.', 'ppsgen')];
    }
    $contact = $this->getFrame()->getModule('contact');
    $list = $contact && method_exists($contact->getModel(), 'getSavedContactList') ? $contact->getModel()->getSavedContactList($listId) : [];
    if (empty($list)) {
      return ['ok' => false, 'message' => __('List not found.', 'ppsgen')];
    }
    $this->updateOption('list_id', 'newsletter', $listId, $chainId, 'eca', 'json');
    return ['ok' => true, 'message' => sprintf(__('Send to: list "%s".', 'ppsgen'), $list['name'])];
  }

  protected function getNewsletterListContactIds(int $chainId, string $listId): array
  {
    global $wpdb;
    $ids = $this->getFrame()->getModule('contact')->getModel()->getContactIdsBySavedList($listId);
    if (empty($ids)) {
      return [];
    }
    $unsubscribed = array_map('intval', (array) $wpdb->get_col("SELECT DISTINCT contact_id FROM {$wpdb->prefix}ppsgen_eca_flow WHERE status = 'unsubscribed'"));
    return array_values(array_diff($ids, $unsubscribed));
  }

  // How many people in the audience the Free plan will not email (beyond the first 500 contacts)
  public function countNewsletterAudienceBeyondLimit(int $chainId): int
  {
    global $wpdb;
    if (!function_exists('ppsgen_contact_limit_applies') || !ppsgen_contact_limit_applies()) {
      return 0;
    }
    $listId = $this->getNewsletterListId($chainId);
    if ($listId !== '') {
      $ids = $this->getNewsletterListContactIds($chainId, $listId);
    } else {
      [$whereSql, $params] = $this->getNewsletterAudienceSql($chainId);
      $sql = "SELECT c.id FROM {$wpdb->prefix}ppsgen_contacts c WHERE {$whereSql}";
      $ids = array_map('intval', (array) $wpdb->get_col(empty($params) ? $sql : $wpdb->prepare($sql, ...$params)));
    }
    $reachable = ppsgen_get_reachable_contact_ids();
    $beyond = 0;
    foreach ($ids as $id) {
      if (!isset($reachable[$id])) {
        $beyond++;
      }
    }
    return $beyond;
  }

  public function getNewsletterAudienceLabel(int $chainId): string
  {
    $listId = $this->getNewsletterListId($chainId);
    if ($listId !== '') {
      $list = $this->getFrame()->getModule('contact')->getModel()->getSavedContactList($listId);
      return sprintf(__('List: %s', 'ppsgen'), (string) ($list['name'] ?? ''));
    }
    $tags = $this->getNewsletterAudienceTagLabels($chainId);
    return !empty($tags) ? implode(', ', $tags) : __('All contacts', 'ppsgen');
  }

  public function countNewsletterAudience(int $chainId): int
  {
    global $wpdb;
    $listId = $this->getNewsletterListId($chainId);
    if ($listId !== '') {
      return count($this->getNewsletterListContactIds($chainId, $listId));
    }
    [$whereSql, $params] = $this->getNewsletterAudienceSql($chainId);
    $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_contacts c WHERE {$whereSql}";
    return (int) $wpdb->get_var(empty($params) ? $sql : $wpdb->prepare($sql, ...$params));
  }

  public function getNewsletterAudienceTagLabels(int $chainId): array
  {
    [, , $settings] = $this->getNewsletterAudienceSql($chainId);
    $tagIds = $this->normalizeConditionIds($settings['entry_tag_yes'] ?? []);
    if (empty($tagIds)) {
      return [];
    }
    $tagModule = $this->getFrame()->getModule('contact');
    $allTags = $tagModule && $tagModule->getSubmod('contact_tag') ? (array) $tagModule->getSubmod('contact_tag')->getModel()->getAllContactTags(true) : [];
    $labels = [];
    foreach ($tagIds as $tagId) {
      if (!empty($allTags[$tagId])) {
        $labels[] = (string) $allTags[$tagId];
      }
    }
    return $labels;
  }

  public function getNewsletterState(int $chainId): array
  {
    global $wpdb;
    $sendAt = (int) $this->getOption('send_at', 'newsletter', $chainId, 'eca');
    $sentAt = (int) $this->getOption('sent_at', 'newsletter', $chainId, 'eca');
    $flows = $wpdb->get_row($wpdb->prepare(
      "SELECT COUNT(*) AS total, SUM(status = 'active') AS active FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d",
      $chainId
    ), ARRAY_A);
    $recipients = (int) ($flows['total'] ?? 0);
    $applyChainId = (int) $this->getOption('ppsgen_eca_apply_chain_id', 'cron', 0, 'eca');
    $applyStatus = (string) $this->getOption('ppsgen_eca_apply_chain_status', 'cron', 0, 'eca');
    if ($sentAt > 0) {
      if ($applyChainId === $chainId && $applyStatus === 'processing') {
        $status = 'preparing';
      } elseif ((int) ($flows['active'] ?? 0) > 0) {
        $status = 'sending';
      } else {
        $status = 'sent';
      }
    } elseif ($sendAt > 0) {
      $status = 'scheduled';
    } else {
      $status = 'draft';
    }
    $opened = 0;
    $clicked = 0;
    if ($recipients > 0) {
      $events = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(DISTINCT CASE WHEN event_type = 'open' AND is_suspicious = 0 THEN contact_id END) AS opened,
                COUNT(DISTINCT CASE WHEN event_type = 'click' AND is_suspicious = 0 THEN contact_id END) AS clicked
        FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE chain_id = %d",
        $chainId
      ), ARRAY_A);
      $opened = (int) ($events['opened'] ?? 0);
      $clicked = (int) ($events['clicked'] ?? 0);
    }
    return [
      'status' => $status,
      'send_at' => $sendAt,
      'sent_at' => $sentAt,
      'recipients' => $recipients,
      'opened' => $opened,
      'clicked' => $clicked,
      'opened_rate' => $recipients > 0 ? round($opened / $recipients * 100, 1) : 0,
      'clicked_rate' => $recipients > 0 ? round($clicked / $recipients * 100, 1) : 0,
    ];
  }

  protected function getNewsletterStep(int $chainId): array
  {
    $steps = $this->getChainStep($chainId);
    return !empty($steps[0]) && is_array($steps[0]) ? $steps[0] : [];
  }

  // Send now: switch the newsletter on and enrol the matching contacts once, through the regular apply queue
  public function sendNewsletterNow(int $chainId): array
  {
    global $wpdb;
    if (!$this->isNewsletter($chainId)) {
      return ['ok' => false, 'message' => __('This is not a newsletter.', 'ppsgen')];
    }
    if ((int) $this->getOption('sent_at', 'newsletter', $chainId, 'eca') > 0) {
      return ['ok' => false, 'message' => __('This newsletter has already been sent.', 'ppsgen')];
    }
    $step = $this->getNewsletterStep($chainId);
    if (empty($step) || trim((string) ($step['html'] ?? '')) === '') {
      return ['ok' => false, 'message' => __('Write the email first: open the email, add content and save it.', 'ppsgen')];
    }
    $applyChainId = (int) $this->getOption('ppsgen_eca_apply_chain_id', 'cron', 0, 'eca');
    $applyStatus = (string) $this->getOption('ppsgen_eca_apply_chain_status', 'cron', 0, 'eca');
    if ($applyStatus === 'processing' && $applyChainId > 0 && $applyChainId !== $chainId) {
      return ['ok' => false, 'message' => __('Another campaign is being sent to contacts right now. Try again in a few minutes.', 'ppsgen')];
    }
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", ['active' => 1, 'updated_at' => current_time('mysql')], ['id' => $chainId]);
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['active' => 1], ['chain_id' => $chainId]);
    $this->updateOption('sent_at', 'newsletter', current_time('timestamp'), $chainId, 'eca', 'json');
    $this->deleteOption('send_at', 'newsletter', $chainId, 'eca');
    $listId = $this->getNewsletterListId($chainId);
    if ($listId !== '') {
      // A saved list is resolved now, once: everyone on it enters the newsletter flow straight away
      $contactIds = $this->getNewsletterListContactIds($chainId, $listId);
      $already = array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT contact_id FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d", $chainId)));
      foreach (array_diff($contactIds, $already) as $contactId) {
        $this->contactFlowEntry((int) $contactId, $chainId);
      }
      return ['ok' => true, 'message' => sprintf(__('Sending to %s contacts. Emails go out in batches over the next minutes.', 'ppsgen'), number_format_i18n(count($contactIds)))];
    }
    $this->updateOption('ppsgen_eca_apply_chain_id', 'cron', $chainId, 0, 'eca', 'json');
    $this->updateOption('ppsgen_eca_apply_chain_status', 'cron', 'processing', 0, 'eca', 'json');
    return ['ok' => true, 'message' => sprintf(__('Sending to %s contacts. Emails go out in batches over the next minutes.', 'ppsgen'), number_format_i18n($this->countNewsletterAudience($chainId)))];
  }

  public function scheduleNewsletter(int $chainId, int $timestamp): array
  {
    if (!$this->isNewsletter($chainId) || (int) $this->getOption('sent_at', 'newsletter', $chainId, 'eca') > 0) {
      return ['ok' => false, 'message' => __('This newsletter cannot be scheduled.', 'ppsgen')];
    }
    if ($timestamp <= current_time('timestamp')) {
      return ['ok' => false, 'message' => __('Pick a time in the future.', 'ppsgen')];
    }
    $step = $this->getNewsletterStep($chainId);
    if (empty($step) || trim((string) ($step['html'] ?? '')) === '') {
      return ['ok' => false, 'message' => __('Write the email first: open the email, add content and save it.', 'ppsgen')];
    }
    $this->updateOption('send_at', 'newsletter', $timestamp, $chainId, 'eca', 'json');
    return ['ok' => true, 'message' => sprintf(__('Scheduled for %s.', 'ppsgen'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp))];
  }

  public function cancelNewsletterSchedule(int $chainId): array
  {
    $this->deleteOption('send_at', 'newsletter', $chainId, 'eca');
    return ['ok' => true, 'message' => __('Schedule cancelled. The newsletter is a draft again.', 'ppsgen')];
  }

  // "When someone clicks a link, add the tag …": the step's existing add_tag_click setting
  public function getNewsletterClickTag(int $chainId): string
  {
    global $wpdb;
    $step = $this->getNewsletterStep($chainId);
    $settings = !empty($step['settings']) ? $this->_prepareValueFromStore($step['settings'], 'json') : [];
    $tagIds = $this->normalizeConditionIds(is_array($settings) ? ($settings['add_tag_click'] ?? []) : []);
    if (empty($tagIds)) {
      return '';
    }
    return (string) $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id = %d", $tagIds[0]));
  }

  public function setNewsletterClickTag(int $chainId, string $slug): array
  {
    global $wpdb;
    $step = $this->getNewsletterStep($chainId);
    if (empty($step)) {
      return ['ok' => false, 'message' => __('Write the email first.', 'ppsgen')];
    }
    $settings = $this->_prepareValueFromStore($step['settings'] ?? '', 'json');
    $settings = is_array($settings) ? $settings : [];
    $slug = sanitize_title($slug);
    if ($slug === '') {
      $settings['add_tag_click'] = [];
      $message = __('Clicks no longer add a tag.', 'ppsgen');
    } else {
      $tagId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug = %s", $slug));
      if ($tagId <= 0) {
        $now = current_time('mysql');
        $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags", ['slug' => $slug, 'order_index' => 0, 'created_at' => $now, 'updated_at' => $now]);
        $tagId = (int) $wpdb->insert_id;
      }
      $settings['add_tag_click'] = [(string) $tagId];
      $message = sprintf(__('People who click a link get the tag %s.', 'ppsgen'), $slug);
    }
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_chain_step", ['settings' => $this->_prepareValueToStore($settings, 'json')], ['id' => (int) $step['id']]);
    return ['ok' => true, 'message' => $message];
  }

  // Runs on the flow cron: starts newsletters whose scheduled time has come
  public function maybeStartScheduledNewsletters(): void
  {
    $now = current_time('timestamp');
    foreach ($this->getNewsletterIds() as $chainId) {
      $sendAt = (int) $this->getOption('send_at', 'newsletter', $chainId, 'eca');
      if ($sendAt > 0 && $sendAt <= $now) {
        $result = $this->sendNewsletterNow($chainId);
        if (empty($result['ok'])) {
          // Another campaign is still being applied: try again on the next run
          continue;
        }
      }
    }
  }

  // Test send: the real email without tracking or unsubscribe link, so it never shows in the stats
  public function sendNewsletterTest(int $chainId, string $to): array
  {
    $step = $this->getNewsletterStep($chainId);
    if (empty($step) || trim((string) ($step['html'] ?? '')) === '') {
      return ['ok' => false, 'message' => __('Write the email first: open the email, add content and save it.', 'ppsgen')];
    }
    $stepSettings = $this->_prepareValueFromStore($step['settings'] ?? '', 'json');
    $stepSettings = is_array($stepSettings) ? $stepSettings : [];
    $subject = $this->pickRandomEmailSubject($stepSettings['email_subject'] ?? '');
    $subject = '[' . __('Test', 'ppsgen') . '] ' . ($subject !== '' ? $subject : (string) ($step['name'] ?? ''));
    $html = $this->removeUnsubscribeUrlLinks((string) $step['html']);
    $html = preg_replace_callback('/\{\{\s*contact\.[a-zA-Z0-9_]+(?:\|([^}]+))?\s*\}\}/', static function ($m) {
      return esc_html(isset($m[1]) ? trim($m[1]) : '');
    }, $html);
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $html = $this->applyNewsletterExtras($chainId, (string) $html, $headers);
    $html = $this->getView()->getAdditionalFieldsHtml($html);
    $chain = $this->getChainById($chainId);
    $chainSettings = !empty($chain['settings']) ? $this->_prepareValueFromStore($chain['settings'], 'json') : [];
    $mailConfigId = is_array($chainSettings) && !empty($chainSettings['mail_config_id']) ? (int) $chainSettings['mail_config_id'] : 0;
    $sent = (bool) $this->sendEmail($to, '', '', $subject, $html, $headers, [], $mailConfigId);
    if ($sent) {
      return ['ok' => true, 'message' => sprintf(__('Test email sent to %s.', 'ppsgen'), $to)];
    }
    $error = $this->getFrame()->core('helper')->getLastMailProviderError();
    return ['ok' => false, 'message' => $error !== '' ? $error : __('The test email could not be sent. Check Settings › Email sending.', 'ppsgen')];
  }
}
