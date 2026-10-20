<?php

if (!defined('ABSPATH')) {
  exit();
}

class InstallerPpsgen
{
  protected FramePpsgen $_frame;

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_frame = $frame;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function init()
  {
    global $wpdb;
    add_action('ppsgen_install_addon', [$this, 'handleAddonInstall']);
    add_action('ppsgen_deactivation_addon', [$this, 'handleAddonDeactivation']);
    add_action('ppsgen_deactivation', [$this, 'handleDeactivation']);

    if (!$this->exist('ppsgen_modules')) {
      $charset = $wpdb->get_charset_collate();
      $tableName = $wpdb->prefix . 'ppsgen_modules';
      $sql = "CREATE TABLE $tableName (
            `module_id` int(11) NOT NULL AUTO_INCREMENT,
            `slug` varchar(255) NOT NULL,
            `parent` varchar(255) NOT NULL,
            `addon_name` varchar(255) NOT NULL,
            `addon_folder` varchar(255) NOT NULL,
            `addon_path` varchar(255) NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT '0',
            `available` tinyint(1) NOT NULL DEFAULT '0',
            `type` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`module_id`)
          ) $charset";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
      $wpdb->insert($tableName, [
        'slug' => 'dashboard',
        'active' => 1,
        'available' => 1,
      ]);
      $wpdb->insert($tableName, [
        'slug' => 'contact',
        'active' => 1,
        'available' => 1,
      ]);
      $wpdb->insert($tableName, [
        'slug' => 'template',
        'active' => 1,
        'available' => 1,
      ]);
      $wpdb->insert($tableName, [
        'slug' => 'form',
        'active' => 1,
        'available' => 1,
      ]);
      $wpdb->insert($tableName, [
        'slug' => 'popup',
        'active' => 1,
        'available' => 1,
      ]);
      // Emails and automations are on from the first install (sites that switched it off keep their choice)
      $wpdb->insert($tableName, [
        'slug' => 'eca',
        'active' => 1,
        'available' => 1,
      ]);
      if (!get_option('ppsgen_greetings_oneday')) {
        update_option('ppsgen_greetings_oneday', date('Y-m-d H:i:s', time() + 86400));
      }
      // A clean install (not a site coming from the classic PopUp plugin): the first visit to the plugin opens the
      // Overview, later visits the Dashboard (see DashboardModulePpsgen::maybeOpenOverviewFirst)
      if (!ppsgen_is_legacy_user()) {
        add_option('ppsgen_overview_first', 1, '', false);
      }
    }
    if (!$this->exist('ppsgen_options')) {
      global $wpdb;
      $charset = $wpdb->get_charset_collate();
      $tableName = $wpdb->prefix . 'ppsgen_options';
      $sql = "CREATE TABLE $tableName (
          `option_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `mod_slug` VARCHAR(100) NOT NULL,
          `mod_obj_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
          `option_group` VARCHAR(255) NOT NULL,
          `option_name`  VARCHAR(255) NOT NULL,
          `option_value` LONGTEXT NOT NULL,
          `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `date_edited` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`option_id`),
          UNIQUE KEY `mod_option` (`mod_slug`, `mod_obj_id`, `option_name`),
          KEY `mod_lookup` (`mod_slug`, `mod_obj_id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }

    $this->maybeMigrate();
  }

  public function maybeMigrate(): bool
  {
    $currentVersion = defined('PPSGEN_VERSION') ? (string) PPSGEN_VERSION : '0';
    $optionKey = 'ppsgen_installer_last_version';
    $lastVersion = (string) get_option($optionKey, '0');

    if (version_compare($lastVersion, $currentVersion, '>=')) {
      return false;
    }

    $migrated = false;
    if ($this->maybeMigrateTemplateModuleRegistration()) {
      $migrated = true;
    }

    update_option($optionKey, $currentVersion);
    return $migrated;
  }

  protected function maybeMigrateTemplateModuleRegistration(): bool
  {
    global $wpdb;

    if (!$this->exist('ppsgen_modules')) {
      return false;
    }

    $tableName = $wpdb->prefix . 'ppsgen_modules';
    $templateModuleId = $wpdb->get_var(
      $wpdb->prepare("SELECT module_id FROM {$tableName} WHERE slug = %s LIMIT 1", 'template')
    );

    if (!empty($templateModuleId)) {
      return false;
    }

    $inserted = $wpdb->insert($tableName, [
      'slug' => 'template',
      'active' => 1,
      'available' => 1,
    ]);

    return $inserted !== false;
  }

  public function exist(string $table): bool
  {
    global $wpdb;
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if (empty($safeTable)) {
      return false;
    }
    $tableName = $wpdb->prefix . $safeTable;
    $res = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tableName));
    return !empty($res);
  }

  public function handleAddonInstall($filePath)
  {
    if (!$this->isAddonFileTrusted($filePath)) {
      return;
    }
    $this->registerAddon($filePath);
  }

  public function handleAddonDeactivation($filePath)
  {
    if (!$this->isAddonFileTrusted($filePath)) {
      return;
    }
    $this->deactivateAddon($filePath);
  }

  public function handleDeactivation()
  {
    global $wpdb;
    $this->_frame->deactivatePlugin();
  }

  private function registerAddon($filePath)
  {
    global $wpdb;
    $realPath = realpath($filePath);
    $addonFolder = basename(dirname($realPath));
    $addonName = strtolower(preg_replace('/[^a-z_-]/', '', basename($realPath, '.php')));
    $xmlPath = dirname($realPath) . DIRECTORY_SEPARATOR . 'addon.xml';
    if (!file_exists($xmlPath)) {
      return;
    }
    $xml = simplexml_load_file($xmlPath);
    if (!$xml || !isset($xml->modules->module)) {
      return;
    }
    foreach ($xml->modules->module as $mod) {
      $slug = (string) $mod->slug;
      $parent = isset($mod->parent) ? (string) $mod->parent : '';
      $active = (int) $mod->active;
      $type = (int) $mod->type;
      $addonPath = '';
      if ($type === 4) {
        $addonPath = isset($mod->path) ? (string) $mod->path : '';
      }
      $exists = $wpdb->get_var($wpdb->prepare("SELECT module_id FROM {$wpdb->prefix}ppsgen_modules WHERE slug = %s AND addon_name = %s", $slug, $addonName));
      $data = [
        'slug' => $slug,
        'parent' => $parent,
        'addon_name' => $addonName,
        'addon_folder' => $addonFolder,
        'addon_path' => $addonPath,
        'available' => 1,
        'type' => $type,
      ];
      if ($exists) {
        if ($active === 1) {
          $data['active'] = 1;
        }
        $wpdb->update("{$wpdb->prefix}ppsgen_modules", $data, ['module_id' => $exists]);
      } else {
        $data['active'] = $active;
        $wpdb->insert("{$wpdb->prefix}ppsgen_modules", $data);
      }
    }
  }

  private function deactivateAddon($filePath)
  {
    global $wpdb;
    $realPath = realpath($filePath);
    $addonFolder = basename(dirname($realPath));
    $addonName = strtolower(preg_replace('/[^a-z_-]/', '', basename($realPath, '.php')));
    $xmlPath = dirname($realPath) . DIRECTORY_SEPARATOR . 'addon.xml';
    if (!file_exists($xmlPath)) {
      return;
    }
    $xml = simplexml_load_file($xmlPath);
    if (!$xml || !isset($xml->modules->module)) {
      return;
    }
    foreach ($xml->modules->module as $mod) {
      $slug = (string) $mod->slug;
      if (!empty($this->_frame->getModule($slug))) {
        $this->_frame->getModule($slug)->deactivatePluginHook();
      }
    }
  }

  private function isAddonFileTrusted($filePath): bool
  {
    if (!is_file($filePath)) {
      return false;
    }
    $realPath = realpath($filePath);
    if (!$realPath) {
      return false;
    }
    $isPpsgenAddon = stripos($realPath, 'ppsgen') !== false;
    $isPopupSupsysticAddon = stripos($realPath, 'popup-by-supsystic') !== false;
    if (!$isPpsgenAddon && !$isPopupSupsysticAddon) {
      return false;
    }
    $dirName = basename(dirname($realPath));
    $baseName = basename($realPath);
    $isKnownAddonName =
      stripos($dirName, 'ppsgen') !== false ||
      stripos($baseName, 'ppsgen') !== false ||
      stripos($dirName, 'popup-by-supsystic') !== false ||
      stripos($baseName, 'popup-by-supsystic') !== false;
    if (!$isKnownAddonName) {
      return false;
    }
    if (!function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $pluginBasename = plugin_basename($realPath);
    $allPlugins = get_plugins();
    if (!isset($allPlugins[$pluginBasename])) {
      return false;
    }
    return true;
  }
}
