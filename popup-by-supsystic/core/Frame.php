<?php

if (!defined('ABSPATH')) {
  exit();
}

final class FramePpsgen
{
  private static ?self $instance = null;
  private string $_internalToken;
  private string $_mod = '';
  private ?object $_submod = null;
  private string $_action = '';
  private string $_cronAction = '';
  private bool $_modulesInited = false;
  private array $_modules = [];
  private array $_modulesMap = [];
  private array $_coreInstances = [];
  private ?object $_fieldBuilder = null;
  private ?object $_fieldValidator = null;

  // Map of Default Plugin Core Classes
  private $coreMap = [
    'FramePpsgen' => PPSGEN_PLUGIN_DIR . 'core/Frame.php',
    'EventDispatcherPpsgen' => PPSGEN_PLUGIN_DIR . 'core/EventDispatcher.php',
    'ShortcodeDispatcherPpsgen' => PPSGEN_PLUGIN_DIR . 'core/ShortcodeDispatcher.php',
    'ContactFieldBuilderPpsgen' => PPSGEN_PLUGIN_DIR . 'core/ContactFieldBuilder.php',
    'ContactFieldValidatorPpsgen' => PPSGEN_PLUGIN_DIR . 'core/ContactFieldValidator.php',
    'HelperPpsgen' => PPSGEN_PLUGIN_DIR . 'core/Helper.php',
    'ComponentPpsgen' => PPSGEN_PLUGIN_DIR . 'core/Component.php',
    'TemplateManager' => PPSGEN_PLUGIN_DIR . 'core/TemplateManager.php',
    'RequestPpsgen' => PPSGEN_PLUGIN_DIR . 'core/Request.php',
    'InstallerPpsgen' => PPSGEN_PLUGIN_DIR . 'core/Installer.php',
    'ModuleManagerPpsgen' => PPSGEN_PLUGIN_DIR . 'core/ModuleManager.php',
    'BaseModulePpsgen' => PPSGEN_PLUGIN_DIR . 'core/BaseModule.php',
    'BaseControllerPpsgen' => PPSGEN_PLUGIN_DIR . 'core/BaseController.php',
    'BaseModelPpsgen' => PPSGEN_PLUGIN_DIR . 'core/BaseModel.php',
    'BaseViewPpsgen' => PPSGEN_PLUGIN_DIR . 'core/BaseView.php',
  ];

  private function __clone() {}

  public function __wakeup() {}

  final public function __serialize(): array
  {
    throw new \Exception('Cannot serialize singleton');
  }

  final public function __unserialize(array $data): void
  {
    throw new \Exception('Cannot unserialize singleton');
  }

  private function __construct() {}

  private function _initAutoload(): void
  {
    $this->_internalToken = wp_generate_password(20, false);
    // Create map of Active Modules (Base, Extend, Addon) using in Autoload/Core/loadModules(ModuleManager) Methods.
    $this->_loadAutoloadMaps();
    // Autoload with Modules and Core Classes From loadAutoloadMaps
    $this->_registerAutoload();
  }

  final public static function _init(bool $force = false): void
  {
    static $called = false;
    if ($called && !$force) {
      return;
    }
    if (!$force && defined('DOING_CRON') && DOING_CRON) {
      if (!self::_isCronAction()) {
        return;
      }
    }
    if (!$force && isset($_SERVER['REQUEST_URI'])) {
      $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
      if (preg_match('/favicon\.ico$/i', $request_uri)) {
        return;
      }
    }
    if (!$force && wp_doing_ajax()) {
      $plugin = isset($_POST['plugin']) ? sanitize_key(wp_unslash($_POST['plugin'])) : '';
      if ($plugin !== 'ppsgen' && !self::_shouldInitForAutoImportAjax()) {
        return;
      }
    }
    if (self::$instance === null) {
      self::$instance = new self();
    }
    if (!self::$instance->_modulesInited) {
      self::$instance->_isCronAction(true);
      self::$instance->_initAutoload();
      self::$instance->_initModules();
      self::$instance->_initModulesHooks();
      self::$instance->_modulesInited = true;
    }
    $called = true;
  }

  final public static function getInstance(): ?self
  {
    return self::$instance;
  }

  private static function _isCronAction($recognize = false): bool
  {
    $cron = get_option('cron');
    if (!is_array($cron)) {
      return false;
    }
    foreach ($cron as $hooks) {
      if (!is_array($hooks)) {
        continue;
      }
      foreach ($hooks as $hookName => $hook) {
        if (strpos($hookName, 'ppsgen_cron_') === 0) {
          if ($recognize) {
            self::$instance->_cronAction = $hookName;
          }
          return true;
        }
      }
    }
    return false;
  }

  private static function _shouldInitForAutoImportAjax(): bool
  {
    $wcAjax = isset($_REQUEST['wc-ajax']) ? sanitize_key(wp_unslash($_REQUEST['wc-ajax'])) : '';
    if ($wcAjax !== 'checkout') {
      return false;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'ppsgen_options';
    $tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($tableExists !== $table) {
      return false;
    }
    $rows = $wpdb->get_col($wpdb->prepare(
      "SELECT option_value
      FROM {$table}
      WHERE option_group = %s
        AND mod_slug = %s
        AND option_name IN ('wp_user_autoimport_enabled', 'woo_autoimport_enabled')",
      'autoimport_opts',
      'contact'
    ));
    foreach ((array) $rows as $value) {
      if (!empty(maybe_unserialize($value))) {
        return true;
      }
    }
    return false;
  }

  private function _registerAutoload(): void
  {
    spl_autoload_register([$this, '_autoload']);
  }

  public function getInternalToken(): string
  {
    return $this->_internalToken;
  }

  private function _initModules(): void
  {
    if (is_admin() && current_user_can('manage_options')) {
      // Install base database tables for modules core classes and addons
      $this->core('installer')->init();
    }
    // Create Instance and Init Default Modules and Addons
    $this->core('moduleManager')->loadModules();
    $this->core('request')->init();
    $this->core('helper')->safeStyleWpKses();
    $this->setCfb($this->core('ContactFieldBuilder'));
    $this->setCfv($this->core('ContactFieldValidator'));
    // Check Request Path
    $this->_getPath();
    // Init Ajax Controllers
    $this->_execAjaxAction();
    // Init Cron Controllers
    $this->_execCronAction();
  }

  private function _initModulesHooks(): void
  {
    add_filter('admin_title', [$this, 'filterAdminTitle'], 20, 2);
    if (!empty($this->getModule('contactExt'))) {
      add_action('ppsgen_contact_updated', [$this->getModule('contactExt')->getModel(), 'triggerContactUpdated'], 10, 2);
    } elseif (!empty($this->getModule('contact'))) {
      add_action('ppsgen_contact_updated', [$this->getModule('contact')->getModel(), 'triggerContactUpdated'], 10, 2);
    }
  }

  // Autoload with Modules and Core Classes From loadAutoloadMaps
  private function _autoload($className): void
  {
    if (substr($className, -6) !== 'Ppsgen') {
      return;
    }
    $className = ucfirst($className);
    if (isset($this->coreMap[$className])) {
      $path = $this->coreMap[$className];
      if (file_exists($path)) {
        require_once $path;
        return;
      }
    }
    if (preg_match('/^([a-zA-Z0-9]+)ModulePpsgen$/', $className, $matches)) {
      $module = $this->_fromCamelCase($matches[1]);
      $path = PPSGEN_PLUGIN_DIR . 'modules/' . $module . '/module.php';
      if (file_exists($path)) {
        require_once $path;
        return;
      }
      if (isset($this->_modulesMap[$className])) {
        $addonPath = $this->_modulesMap[$className];
        if (file_exists($addonPath)) {
          require_once $addonPath;
        }
      }
    }
  }

  // Create map of Active Modules (Base, Extend, Addon) using in Autoload/Core/loadModules(ModuleManager) Methods.
  private function _loadAutoloadMaps(): void
  {
    global $wpdb;
    $table = $wpdb->prefix . 'ppsgen_modules';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($table_exists !== $table) {
      return;
    }
    $rows = $wpdb->get_results(" SELECT * FROM {$wpdb->prefix}ppsgen_modules  WHERE active = 1 AND addon_folder != ''", ARRAY_A);
    if (empty($rows)) {
      return;
    }
    $coreMap = [];
    $addonModuleMap = [];
    $deactivateSlugs = [];
    if (!function_exists('is_plugin_active')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    foreach ($rows as $row) {
      $baseDir = trailingslashit(WP_PLUGIN_DIR) . '/' . $row['addon_folder'] . '/';
      $pluginBasename = $row['addon_folder'] . '/' . $row['addon_name'] . '.php';
      $isAddonActive = is_plugin_active($pluginBasename);
      $folderExists = is_dir($baseDir);
      if (!$isAddonActive || !$folderExists) {
        if (!$isAddonActive) {
          $deactivateSlugs[] = $row['slug'];
        }
        continue;
      }
      if ($row['type'] == 4) {
        // Core
        $coreDir = trailingslashit($baseDir . 'core/' . trim($row['addon_path'], '/'));
        if (!is_dir($coreDir)) {
          continue;
        }
        foreach (glob($coreDir . '*.php') as $file) {
          $className = basename($file, '.php') . 'Ppsgen';
          $coreMap[$className] = $file;
        }
      } elseif (in_array($row['type'], [3, 6])) {
        // Module
        $modulePath = trailingslashit($baseDir . trim($row['addon_path'], '/') . '/modules/' . $row['slug']) . 'module.php';
        $parts = explode('_', $row['slug']);
        $class = implode('', array_map('ucfirst', $parts));
        $className = $class . 'ModulePpsgen';
        if (file_exists($modulePath)) {
          $addonModuleMap[$className] = $modulePath;
        }
      }
    }
    if (!empty($deactivateSlugs)) {
      foreach ($deactivateSlugs as $slug) {
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ppsgen_modules SET active = 0 WHERE slug = %s", $slug));
      }
    }
    if (!empty($coreMap)) {
      foreach ($coreMap as $class => $absolutePath) {
        $this->coreMap[$class] = $absolutePath;
      }
    }
    if (!empty($addonModuleMap)) {
      $this->_modulesMap = $addonModuleMap;
    }
  }

  // Return modules map created in loadAutoLoadMaps method to ModuleManager
  public function getModulesMap(): array
  {
    return $this->_modulesMap;
  }

  // Return created instance of Core Classes, declared in loadAutoLoadMaps methods
  public function core(string $code): ?object
  {
    $code = ucfirst($code);
    if (!isset($this->_coreInstances[$code])) {
      $className = $code . 'Ppsgen';
      if (!class_exists($className)) {
        return null;
        // throw new Exception('Core class ' . esc_html($className) . ' not found');
      }
      $token = $this->getInternalToken();
      $this->_coreInstances[$code] = new $className($this, $token);
    }
    return $this->_coreInstances[$code];
  }

  // Get instance of Module (not submodule) trying to use Extend version of Module if exist
  public function getModule($code, $context = 'backend'): ?object
  {
    $code = ucfirst($code);
    $baseCode = preg_replace('/Ext$/', '', $code);
    if (isset($this->_modules[$baseCode])) {
      if (method_exists($this->_modules[$baseCode], 'init')) {
        if (!$this->_modules[$baseCode]->getIsInited()) {
          $this->_modules[$baseCode]->init($context);
        }
      }
      $extendCode = $baseCode . 'Ext';
      if (isset($this->_modules[$extendCode])) {
        if (method_exists($this->_modules[$extendCode], 'init')) {
          if (!$this->_modules[$extendCode]->getIsInited()) {
            $this->_modules[$extendCode]->init($context);
          }
        }
      }
      if (!empty($this->_modules[$code])) {
        return $this->_modules[$code];
      }
      return null;
    }
    return null;
  }

  // Create Module Instance Map for getModule
  public function setModule($code, $module): void
  {
    $code = ucfirst($code);
    $this->_modules[$code] = $module;
  }

  // Safety call of modules and submodules without fatal error if not exist
  public function useModule($moduleCode, $submoduleCode = false, $componentType = 'model', $method = false, $args = [])
  {
    $moduleCode = ucfirst($moduleCode);
    $module = $this->getModule($moduleCode);
    if (!$module) {
      return null;
    }
    if (!empty($submoduleCode)) {
      $module = $module->getSubmod($submoduleCode);
      if (!$module) {
        return null;
      }
    }
    switch (strtolower($componentType)) {
      case 'model':
        $component = $module->getModel();
        break;
      case 'view':
        $component = $module->getView();
        break;
      case 'controller':
        $component = $module->getController();
        break;
      default:
        return null;
    }
    if (!empty($method)) {
      if (!empty($args)) {
        $args = is_array($args) ? $args : [$args];
      } else {
        $args = [];
      }
      if (method_exists($component, $method)) {
        return call_user_func_array([$component, $method], $args);
      } else {
        return null;
      }
    }
    return $component;
  }

  private function _execCronAction(): void
  {
    if (empty($this->_cronAction)) {
      return;
    }
    $hookParts = explode('_', $this->_cronAction, 4);
    if (count($hookParts) < 4) {
      return;
    }
    $modSub = $hookParts[2];
    $this->_action = $hookParts[3];
    $parts = explode('--', $modSub, 2);
    $this->_mod = ucfirst($parts[0]);
    $this->_submod = $parts[1] ?? null;
    if (!empty($this->_submod)) {
      if (!empty($this->getModule($this->_mod))) {
        $sub = $this->getModule($this->_mod)->getSubmod($this->_submod);
      }
      if (!$sub) {
        return;
      }
      $ctrl = $sub->getController();
    } else {
      if (!empty($this->_modules[$this->_mod])) {
        if (!empty($this->getModule($this->_mod))) {
          $ctrl = $this->getModule($this->_mod)->getController();
        }
      } else {
        return;
      }
    }
    if (!empty($ctrl)) {
      if (!method_exists($ctrl, $this->_action)) {
        return;
      }
      add_action($this->_cronAction, [$ctrl, $this->_action], 10, 10);
    }
    return;
  }

  public function deactivatePlugin(): void
  {
    if (!empty($this->_modules)) {
      foreach ($this->_modules as $slug => $mod) {
        $this->getModule($slug)->deactivatePluginHook();
      }
    }
  }

  // Check Request Path
  private function _getPath(): void
  {
    $plugin = $this->core('request')->getVar('plugin');
    if ($plugin == 'ppsgen') {
      $mod = $this->core('request')->getMod();
      if ($mod) {
        $this->_mod = ucfirst($mod);
      }
      $action = $this->core('request')->getVar('action');
      if ($action) {
        $this->_action = $action;
      }
    }
  }

  // Init Ajax Controllers
  private function _execAjaxAction(): void
  {
    if (wp_doing_ajax()) {
      if (!empty($this->_mod) && !empty($this->_action)) {
        // If path with submodule (contact/contact_field)
        if (strpos($this->_mod, '/') !== false) {
          $exp = explode('/', $this->_mod);
          if (!empty($exp[0]) && !empty($exp[1])) {
            $module = $exp[0];
            $submodule = $exp[1];
            if (!empty($this->_modules[$module])) {
              if (!empty($this->getModule($module)->getSubmod($submodule))) {
                $ctrl = $this->getModule($module)->getSubmod($submodule)->getController();
                if (method_exists($ctrl, $this->_action)) {
                  add_action('wp_ajax_' . $this->_action, [$ctrl, $this->_action]);
                  add_action('wp_ajax_nopriv_' . $this->_action, [$ctrl, $this->_action]);
                }
              }
            }
          }
        } else {
          // If single module (contact)
          if (!empty($this->_modules[$this->_mod])) {
            $ctrl = $this->getModule($this->_mod)->getController();
            if (method_exists($ctrl, $this->_action)) {
              add_action('wp_ajax_' . $this->_action, [$ctrl, $this->_action]);
              add_action('wp_ajax_nopriv_' . $this->_action, [$ctrl, $this->_action]);
            }
          }
        }
      }
    }
  }

  public function setCfb($builder): void
  {
    $this->_fieldBuilder = $builder;
  }

  public function getCfb(): ?object
  {
    return $this->_fieldBuilder;
  }

  public function setCfv($builder): void
  {
    $this->_fieldValidator = $builder;
  }

  public function getCfv(): ?object
  {
    return $this->_fieldValidator;
  }

  public function isPluginAdminPage(): ?string
  {
    $urlParams = !empty($this->core('request')->getServerVar('REQUEST_URI')) ? wp_parse_url($this->core('request')->getServerVar('REQUEST_URI')) : [];
    if (!empty($urlParams['query'])) {
      parse_str($urlParams['query'], $urlParams);
      $page = !empty($urlParams['page']) ? $urlParams['page'] : '';
      $tab = !empty($urlParams['tab']) ? $urlParams['tab'] : '';
      if ($page == 'ppsgen') {
        if (empty($tab)) {
          $tab = 'dashboard';
        }
        return $tab;
      }
    }
    return false;
  }

  public function getCurrentPluginPageMeta(?string $tab = null): array
  {
    $tab = !empty($tab) ? sanitize_key($tab) : $this->isPluginAdminPage();
    if (empty($tab)) {
      return [];
    }
    $metaMap = $this->core('moduleManager')->getPageMetaMap();
    if (!is_array($metaMap)) {
      return [];
    }
    return !empty($metaMap[$tab]) && is_array($metaMap[$tab]) ? $metaMap[$tab] : [];
  }

  public function getCurrentPluginPageTitle(string $context = 'page_title', ?string $tab = null): string
  {
    $tab = !empty($tab) ? sanitize_key($tab) : $this->isPluginAdminPage();
    if (empty($tab)) {
      return '';
    }
    $meta = $this->getCurrentPluginPageMeta($tab);
    $title = $this->resolvePluginPageTitleFromMeta($meta, $context, $tab);
    if ($title !== '') {
      return $title;
    }
    $navigationTitle = $this->getNavigationTitleByTab($tab);
    if ($navigationTitle !== '') {
      return $navigationTitle;
    }
    return __('Customer Engagement System – Popups, Forms, CRM & Automation', 'ppsgen');
  }

  protected function resolvePluginPageTitleFromMeta(array $meta, string $context, string $tab): string
  {
    $callbackKey = $context . '_callback';
    if (!empty($meta[$callbackKey]) && is_callable($meta[$callbackKey])) {
      $title = call_user_func($meta[$callbackKey]);
      if (is_string($title) && trim($title) !== '') {
        return trim($title);
      }
    }
    if (!empty($meta[$context]) && is_string($meta[$context])) {
      return trim($meta[$context]);
    }
    if ($context !== 'page_title' && !empty($meta['page_title']) && is_string($meta['page_title'])) {
      return trim($meta['page_title']);
    }
    return '';
  }

  protected function getNavigationTitleByTab(string $tab): string
  {
    $navigation = $this->core('moduleManager')->getNavigation();
    if (!is_array($navigation)) {
      return '';
    }
    return $this->searchNavigationTitle($navigation, $tab);
  }

  protected function searchNavigationTitle(array $items, string $tab): string
  {
    foreach ($items as $item) {
      if (!empty($item['slug']) && $item['slug'] === $tab && !empty($item['title']) && is_string($item['title'])) {
        return trim($item['title']);
      }
      if (!empty($item['children']) && is_array($item['children'])) {
        $childTitle = $this->searchNavigationTitle($item['children'], $tab);
        if ($childTitle !== '') {
          return $childTitle;
        }
      }
    }
    return '';
  }

  public function filterAdminTitle(string $adminTitle, string $title): string
  {
    $tab = $this->isPluginAdminPage();
    if (empty($tab)) {
      return $adminTitle;
    }
    $resolvedTitle = $this->getCurrentPluginPageTitle('admin_title', $tab);
    if ($resolvedTitle === '') {
      return $adminTitle;
    }
    return $resolvedTitle . ' < Customer Engagement System – Popups, Forms, CRM & Automation < ' . get_bloginfo('name') . ' - WordPress';
    if ($title !== '' && strpos($adminTitle, $title) !== false) {
      return preg_replace('/' . preg_quote($title, '/') . '/', $resolvedTitle, $adminTitle, 1);
    }
    return $resolvedTitle . ' ‹ ' . get_bloginfo('name') . ' — WordPress';
  }

  private function _fromCamelCase($input): string
  {
    $pattern = '/(?<=\\w)([A-Z])/';
    $snake = strtolower(preg_replace($pattern, '_$1', $input));
    return str_replace('__', '_', $snake);
  }
}
