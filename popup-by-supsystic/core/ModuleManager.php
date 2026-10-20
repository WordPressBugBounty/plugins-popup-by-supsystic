<?php

if (!defined('ABSPATH')) {
  exit();
}

class ModuleManagerPpsgen
{
  protected FramePpsgen $_frame;
  protected $_token;

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_token = $token;
    $this->_frame = $frame;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  // Create Instance and Init Default Modules and Addons
  public function loadModules()
  {
    $isPluginPage = $this->getFrame()->isPluginAdminPage();
    $isAjax = wp_doing_ajax();
    $isFrontend = !is_admin() && !$isAjax;
    $modulesList = $this->getModulesList();
    if (empty($modulesList)) {
      return;
    }
    $modulesDir = PPSGEN_PLUGIN_DIR . 'modules/';
    $addonModuleMap = $this->getFrame()->getModulesMap() ?? [];
    // Create Instance and Init Default Modules and Addons Modules (DashboardPpsgen, PopupPpsgen, ...), (for example PopupExtPpsgen, newAddonModulePpsgen, ...)
    foreach ($modulesList as $module) {
      $this->loadSingleModule($module, $modulesDir, $addonModuleMap, $isPluginPage, $isAjax, $isFrontend);
    }
    // On a CES page every module ran its backend init, which creates its tables: from now on visitors' requests
    // load the modules too (see getModulesList)
    if ($isPluginPage && is_admin() && current_user_can('manage_options') && get_option('ppsgen_modules_ready') === false) {
      update_option('ppsgen_modules_ready', defined('PPSGEN_VERSION') ? PPSGEN_VERSION : '1');
    }
  }

  private function loadSingleModule(array $module, string $modulesDir, array $addonModuleMap, bool $isPluginPage, bool $isAjax, bool $isFrontend): void
  {
    if ($module['active'] != '1' || $module['available'] != '1') {
      return;
    }
    $type = $module['type'];
    $frame = $this->getFrame();
    $moduleSlug = $module['slug'];
    $hasEcaTrackingRequest = function_exists('ppsgen_request_has_eca_tracking_params')
      ? ppsgen_request_has_eca_tracking_params()
      : (!empty($frame->core('request')->getVar('ppsgen_eca_action')) || !empty($frame->core('request')->getVar('ppsgen_eca_unsubscribe')));
    $className = $this->buildModuleClassName($moduleSlug);
    if (isset($addonModuleMap[$className])) {
      $moduleFile = $addonModuleMap[$className];
      if (!class_exists($className) && file_exists($moduleFile)) {
        require_once $moduleFile;
      }
    } else {
      $modulePath = $modulesDir . $moduleSlug . '/module.php';
      if (!class_exists($className) && file_exists($modulePath)) {
        require_once $modulePath;
      }
    }
    if (!class_exists($className)) {
      return;
    }
    // Create Module Instance (Not submodule)
    $moduleInstance = new $className($frame, $this->_token, $moduleSlug);
    if (in_array($type, [0, 6])) {
      $moduleInstance->afterInit();
    }
    if (!$moduleInstance->getBeforeInit() || !$moduleInstance->getAfterInit()) {
      return;
    }
    // Set Module Instance - need to use getModule from Frame
    $frame->setModule($moduleSlug, $moduleInstance);
    // Register Shortcodes from Modules
    if (method_exists($className, 'getFrontendShortcodes')) {
      $shortcodes = $className::getFrontendShortcodes();
      foreach ($shortcodes as $shortcode => $shortcodeData) {
        $frame->core('shortcodeDispatcher')->register($shortcode, $shortcodeData['slug'], $shortcodeData['method']);
      }
    }
    if (
      ($moduleSlug === 'dashboard' && is_admin() && current_user_can('manage_options')) ||
      ($isPluginPage && is_admin() && current_user_can('manage_options')) ||
      ($isAjax && $frame->core('request')->getVar('plugin') == 'ppsgen' && !empty($frame->core('request')->getVar('action')) && strpos($frame->core('request')->getMod(), $moduleSlug) !== false)
    ) {
      $moduleInstance->init('backend');
    } elseif ($isFrontend && strpos($moduleSlug, 'popup') !== false) {
      $moduleInstance->init('frontend');
    } elseif ($isFrontend && $hasEcaTrackingRequest) {
      if (strpos($moduleSlug, 'eca') !== false || strpos($moduleSlug, 'ecaExt') !== false) {
        $moduleInstance->init('frontend');
      }
    }
  }

  protected function buildModuleClassName($moduleSlug)
  {
    $parts = explode('_', $moduleSlug);
    $class = implode('', array_map('ucfirst', $parts));
    return $class . 'ModulePpsgen';
  }

  public function getModulesList()
  {
    global $wpdb;
    // Not installed yet (the installer runs on the first wp-admin visit, e.g. after an automatic update from
    // Classic PopUp): no modules until then, instead of a database error on every request
    if (get_option('ppsgen_installer_last_version') === false) {
      $table = $wpdb->prefix . 'ppsgen_modules';
      if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) {
        return [];
      }
    }
    // Each module creates its tables when an administrator first opens a CES page (see loadModules). Until then
    // visitors' requests and cron load no modules (command-line tools still do): there is nothing of CES on the
    // site yet, and their tables would not exist
    if (!is_admin() && !wp_doing_ajax() && !(PHP_SAPI === 'cli' && empty($_SERVER['REQUEST_METHOD'])) && get_option('ppsgen_modules_ready') === false) {
      return [];
    }
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_modules WHERE type IN (0,3,6)", ARRAY_A);
  }

  public function getTabToModuleMap()
  {
    return apply_filters('ppsgen_module_tab', []);
  }

  public function getNavigation()
  {
    return apply_filters('ppsgen_module_navigation', []);
  }

  public function getPageMetaMap()
  {
    return apply_filters('ppsgen_module_page_meta', []);
  }
}
