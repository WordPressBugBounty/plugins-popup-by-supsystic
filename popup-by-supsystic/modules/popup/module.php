<?php

if (!defined('ABSPATH')) {
  exit();
}

class PopupModulePpsgen extends BaseModulePpsgen
{
  public function init($context = 'backend')
  {
    parent::init();
    if ($context == 'backend') {
      $this->getModel()->maybeMigrate();
      add_action('admin_init', [$this, 'redirectEmptyList']);
      add_action('admin_enqueue_scripts', [$this, 'registerModuleBackendAssets']);
      add_filter('ppsgen_module_pages', [$this, 'registerModulePages']);
      add_filter('ppsgen_module_tab', [$this, 'registerModuleTabMap']);
      add_filter('ppsgen_module_navigation', [$this, 'registerModuleNavigation']);
      add_filter('ppsgen_module_page_meta', [$this, 'registerModulePageMeta']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('popup.edit.template_html', [$this->getView(), 'renderPopupEditTemplate'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('popup.edit.options_html', [$this->getView(), 'renderPopupEditOptions'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('popup.edit.design_html', [$this->getView(), 'renderPopupEditDesign'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('popup.edit.stats_html', [$this->getView(), 'renderPopupEditStats'], 10);
      add_filter('tiny_mce_before_init', [$this, 'disableTinyMceAutop'], 99);
    }
    if ($context == 'frontend') {
      add_action('wp_enqueue_scripts', [$this->getFrame()->core('component'), 'initFrontendScripts']);
      add_action('wp_footer', [$this, 'renderPopupsAuto']);
      // Extra triggers (Free): after a WooCommerce purchase, after a comment or review
      add_action('woocommerce_thankyou', [$this, 'renderPopupsAfterCheckout'], 10, 1);
      add_action('comment_post', [$this, 'rememberCommentForPopups'], 10, 1);
    }
  }

  public static function getFrontendShortcodes()
  {
    return ['supsystic-ces-popup' => ['slug' => 'popup', 'method' => 'renderPopupShortcode']];
  }

  /** An empty popup list has nothing to show: open the template catalogue instead */
  public function redirectEmptyList()
  {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation
    if (wp_doing_ajax() || sanitize_key(wp_unslash($_GET['page'] ?? '')) !== 'ppsgen' || sanitize_key(wp_unslash($_GET['tab'] ?? '')) !== 'popup_list') {
      return;
    }
    if (!current_user_can('manage_options')) {
      return;
    }
    global $wpdb;
    if ((int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_popups") > 0) {
      return;
    }
    wp_safe_redirect(admin_url('admin.php?page=ppsgen&tab=template_add&template_type=popup'));
    exit();
  }

  public function registerModuleBackendAssets()
  {
    $moduleDashboardAssetsUrl = PPSGEN_MODULES_URL . 'dashboard/assets/';
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'popup/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'popup_overview':
        wp_register_style('ppsgen-backend-popup-overview', $moduleDashboardAssetsUrl . 'css/backend.overview.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-popup-overview');
        break;
      case 'popup_list':
        wp_register_style('ppsgen-backend-popup-list', $moduleAssetsUrl . 'css/backend.list.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-popup-list');
        wp_register_script('ppsgen-backend-popup-list', $moduleAssetsUrl . 'js/backend.list.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-popup-list');
        break;
      case 'popup_edit':
        wp_register_style('ppsgen-backend-popup-edit', $moduleAssetsUrl . 'css/backend.edit.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-popup-edit');
        wp_register_script('ppsgen-backend-popup-edit', $moduleAssetsUrl . 'js/backend.edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-popup-edit');
        $this->enqueueCustomCssEditor();
        $this->getFrame()->core('component')->loadSortable();
        $this->getFrame()->core('component')->loadAnimate();
        break;
      case 'popup_options':
        wp_register_script('ppsgen-backend-popup-options', $moduleAssetsUrl . 'js/backend.options.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-popup-options');
        break;
    }
  }

  private function enqueueCustomCssEditor(): void
  {
    $editorSettings = wp_enqueue_code_editor(['type' => 'text/css']);
    wp_enqueue_style('code-editor');
    wp_enqueue_script('code-editor');
    wp_enqueue_script('wp-theme-plugin-editor');
    wp_register_script('ppsgen-backend-popup-custom-css-edit', PPSGEN_PLUGIN_URL . 'modules/popup/assets/js/backend.custom_css_edit.js', ['jquery', 'code-editor', 'wp-theme-plugin-editor'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-popup-custom-css-edit');
    wp_localize_script('ppsgen-backend-popup-custom-css-edit', 'ppsgenPopupCustomCssEditor', ['css' => $editorSettings ?: new stdClass()]);
  }

  public function registerModuleNavigation($data)
  {
    $new = [
      'title' => 'Popups',
      'slug' => 'popup_list',
      'icon' => 'dashicons dashicons-testimonial',
      'children' => [
        [
          'title' => 'Overview',
          'slug' => 'popup_overview',
          'icon' => 'dashicons dashicons-admin-home',
        ],
        [
          'title' => 'Create New',
          'slug' => 'popup_edit',
          'icon' => 'dashicons dashicons-plus',
        ],
        [
          'title' => 'Options',
          'slug' => 'popup_options',
          'icon' => 'dashicons dashicons-admin-settings',
        ],
      ],
    ];
    $data[] = $new;
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['popup_overview'] = [$this->getView(), 'renderPopupOverview'];
    $pages['popup_list'] = [$this->getView(), 'renderPopupList'];
    $pages['popup_edit'] = [$this->getView(), 'renderPopupEdit'];
    $pages['popup_options'] = [$this->getView(), 'renderPopupOptions'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['popup_overview'] = 'popup';
    $tabs['popup_list'] = 'popup';
    $tabs['popup_edit'] = 'popup';
    $tabs['popup_options'] = 'popup';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['popup_overview'] = [
      'page_title' => __('Popups Overview', 'ppsgen'),
      'admin_title' => __('Popups Overview', 'ppsgen'),
      'menu_title' => __('Overview', 'ppsgen'),
    ];
    $data['popup_list'] = [
      'page_title' => __('Popups', 'ppsgen'),
      'admin_title' => __('Popups', 'ppsgen'),
      'menu_title' => __('Popups', 'ppsgen'),
    ];
    $data['popup_edit'] = [
      'page_title' => __('Popup', 'ppsgen'),
      'page_title_callback' => [$this, 'getPopupEditPageTitle'],
      'admin_title_callback' => [$this, 'getPopupEditPageTitle'],
      'menu_title' => __('Create New', 'ppsgen'),
    ];
    $data['popup_options'] = [
      'page_title' => __('Popup Options', 'ppsgen'),
      'admin_title' => __('Popup Options', 'ppsgen'),
      'menu_title' => __('Options', 'ppsgen'),
    ];
    return $data;
  }

  public function getPopupEditPageTitle(): string
  {
    $popupId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($popupId > 0) {
      $popup = $this->getModel()->getPopupById($popupId);
      if (!empty($popup['name'])) {
        return sprintf(
          /* translators: %s: popup name */
          __('Edit Popup: %s', 'ppsgen'),
          (string) $popup['name']
        );
      }
      return __('Edit Popup', 'ppsgen');
    }
    return __('Create New Popup', 'ppsgen');
  }

  public function renderPopupShortcode($atts)
  {
    $atts = shortcode_atts(
      [
        'id' => 0,
        'embed' => 0,
        'hideoverlay' => 0,
        'forceshow' => 0,
      ],
      $atts,
      'supsystic-ces-popup',
    );
    $popupId = (int) $atts['id'];
    if (!$popupId) {
      return '<div class="ppsgen-error">Invalid popup ID</div>';
    }
    return $this->getView()->renderPopupView([
      'id' => $popupId,
      'embed' => (int) $atts['embed'],
      'hideoverlay' => (int) $atts['hideoverlay'],
      'forceshow' => (int) $atts['forceshow'],
    ]);
  }

  public function disableTinyMceAutop($init)
  {
    if (!empty($init['selector']) && $init['selector'] !== '#html_textarea_content') {
      return $init;
    }
    $init['wpautop'] = false;
    $init['force_p_newlines'] = false;
    $init['forced_root_block'] = 'div';
    $init['apply_source_formatting'] = true;
    return $init;
  }

  // "After purchasing (Checkout)": shown on WooCommerce's order-received page only
  public function renderPopupsAfterCheckout($orderId)
  {
    if (!function_exists('wc_get_order') || !wc_get_order($orderId)) {
      return;
    }
    foreach ($this->getModel()->getPopupsByShowOn('after_checkout') as $popupId) {
      $html = $this->getView()->renderPopupView(['id' => $popupId]);
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $this->getFrame()->core('helper')->wpKses($html);
    }
  }

  // "After user comment, review": the page the visitor lands on next shows the popup (cookie lives for a minute)
  public function rememberCommentForPopups($commentId)
  {
    $popups = $this->getModel()->getPopupsByShowOn('after_comment');
    if (empty($popups) || headers_sent()) {
      return;
    }
    setcookie('ppsgen_after_comment', wp_json_encode(array_map('strval', $popups)), time() + 60, defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/');
  }

  public function renderPopupsAuto()
  {
    $popups = $this->getModel()->getAllActivePopups();
    foreach ($popups as $popup) {
      $html = $this->getView()->renderPopupView(['id' => $popup]);
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $this->getFrame()->core('helper')->wpKses($html);
    }
  }
}
