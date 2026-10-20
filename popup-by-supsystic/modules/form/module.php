<?php

if (!defined('ABSPATH')) {
  exit();
}

class FormModulePpsgen extends BaseModulePpsgen
{
  public function init($context = 'backend')
  {
    parent::init();
    // Conditional logic is a Free feature (submodules/conditional_logic)
    if ($this->registerSubmodule('conditional_logic')) {
      $this->getSubmod('conditional_logic')->init($context);
    }
    if ($context == 'backend') {
      add_action('admin_init', [$this, 'redirectEmptyList']);
      $this->getModel()->maybeMigrate();
      add_action('admin_enqueue_scripts', [$this, 'registerModuleBackendAssets']);
      add_filter('ppsgen_module_pages', [$this, 'registerModulePages']);
      add_filter('ppsgen_module_tab', [$this, 'registerModuleTabMap']);
      add_filter('ppsgen_module_navigation', [$this, 'registerModuleNavigation']);
      add_filter('ppsgen_module_page_meta', [$this, 'registerModulePageMeta']);
      $dispatcher = $this->getFrame()->core('eventDispatcher');
      $dispatcher->addFilter('form.edit.fields_html', [$this->getView(), 'renderFormEditFields']);
      $dispatcher->addFilter('form.edit.design_html', [$this->getView(), 'renderFormEditDesign']);
      $dispatcher->addFilter('form.edit.options_html', [$this->getView(), 'renderFormEditOptions'], 10);
      $dispatcher->addFilter('form.edit.submits_html', [$this->getView(), 'renderFormEditSubmits'], 10);
      $dispatcher->addFilter('form.edit.fields_html.options', [$this->getView(), 'renderFormEditFieldsOptions'], 10);
      $dispatcher->addFilter('contact.form_submits.options_html', [$this->getView(), 'renderContactFormSubmits'], 10);
      $dispatcher->addFilter('form.edit.design_html.options', [$this->getView(), 'renderFormEditDesignOptions'], 10);
    }
    if ($context == 'frontend') {
      $this->getFrame()->core('component')->initFrontendScripts();
    }
  }

  public static function getFrontendShortcodes()
  {
    return ['supsystic-ces-form' => ['slug' => 'form', 'method' => 'renderFormShortcode']];
  }

  /** An empty form list has nothing to show: open the form template catalogue instead */
  public function redirectEmptyList()
  {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation
    if (wp_doing_ajax() || sanitize_key(wp_unslash($_GET['page'] ?? '')) !== 'ppsgen' || sanitize_key(wp_unslash($_GET['tab'] ?? '')) !== 'form_list') {
      return;
    }
    if (!current_user_can('manage_options')) {
      return;
    }
    global $wpdb;
    if ((int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_forms") > 0) {
      return;
    }
    wp_safe_redirect(admin_url('admin.php?page=ppsgen&tab=template_add&template_type=form'));
    exit();
  }

  public function registerModuleBackendAssets()
  {
    $moduleDashboardAssetsUrl = PPSGEN_MODULES_URL . 'dashboard/assets/';
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'form/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'form_overview':
        wp_register_style('ppsgen-backend-form-overview', $moduleDashboardAssetsUrl . 'css/backend.overview.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-form-overview');
        break;
      case 'form_list':
        wp_register_style('ppsgen-backend-form-list', $moduleAssetsUrl . 'css/backend.list.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-form-list');
        wp_register_script('ppsgen-backend-form-list', $moduleAssetsUrl . 'js/backend.list.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-form-list');
        break;
      case 'form_edit':
        wp_register_style('ppsgen-backend-form-edit', $moduleAssetsUrl . 'css/backend.edit.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-form-edit');
        wp_register_script('ppsgen-backend-form-edit', $moduleAssetsUrl . 'js/backend.edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-form-edit');
        $this->enqueueCustomCssEditor();
        $this->getFrame()->core('component')->loadSortable();
        break;
      case 'form_options':
        wp_register_script('ppsgen-backend-form-options', $moduleAssetsUrl . 'js/backend.options.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-form-options');
        break;
    }
  }

  private function enqueueCustomCssEditor(): void
  {
    $editorSettings = wp_enqueue_code_editor(['type' => 'text/css']);
    wp_enqueue_style('code-editor');
    wp_enqueue_script('code-editor');
    wp_enqueue_script('wp-theme-plugin-editor');
    wp_register_script('ppsgen-backend-form-custom-css-edit', PPSGEN_PLUGIN_URL . 'modules/form/assets/js/backend.custom_css_edit.js', ['jquery', 'code-editor', 'wp-theme-plugin-editor'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-form-custom-css-edit');
    wp_localize_script('ppsgen-backend-form-custom-css-edit', 'ppsgenFormCustomCssEditor', ['css' => $editorSettings ?: new stdClass()]);
  }

  public function registerModuleNavigation($data)
  {
    $new = [
      'title' => 'Forms',
      'slug' => 'form_list',
      'icon' => 'dashicons dashicons-format-aside',
      'children' => [
        [
          'title' => 'Overview',
          'slug' => 'form_overview',
          'icon' => 'dashicons dashicons-admin-home',
        ],
        [
          'title' => 'Create New',
          'slug' => 'form_edit',
          'icon' => 'dashicons dashicons-plus',
        ],
        [
          'title' => 'Options',
          'slug' => 'form_options',
          'icon' => 'dashicons dashicons-admin-settings',
        ],
      ],
    ];
    $data[] = $new;
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['form_overview'] = [$this->getView(), 'renderFormOverview'];
    $pages['form_list'] = [$this->getView(), 'renderFormList'];
    $pages['form_edit'] = [$this->getView(), 'renderFormEdit'];
    $pages['form_options'] = [$this->getView(), 'renderFormOptions'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['form_overview'] = 'form';
    $tabs['form_list'] = 'form';
    $tabs['form_edit'] = 'form';
    $tabs['form_options'] = 'form';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['form_overview'] = [
      'page_title' => __('Forms Overview', 'ppsgen'),
      'admin_title' => __('Forms Overview', 'ppsgen'),
      'menu_title' => __('Overview', 'ppsgen'),
    ];
    $data['form_list'] = [
      'page_title' => __('Forms', 'ppsgen'),
      'admin_title' => __('Forms', 'ppsgen'),
      'menu_title' => __('Forms', 'ppsgen'),
    ];
    $data['form_edit'] = [
      'page_title' => __('Form', 'ppsgen'),
      'page_title_callback' => [$this, 'getFormEditPageTitle'],
      'admin_title_callback' => [$this, 'getFormEditPageTitle'],
      'menu_title' => __('Create New', 'ppsgen'),
    ];
    $data['form_options'] = [
      'page_title' => __('Form Options', 'ppsgen'),
      'admin_title' => __('Form Options', 'ppsgen'),
      'menu_title' => __('Options', 'ppsgen'),
    ];
    return $data;
  }

  public function getFormEditPageTitle(): string
  {
    $formId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($formId > 0) {
      $form = $this->getModel()->getFormById($formId);
      if (!empty($form['name'])) {
        return sprintf(
          /* translators: %s: form name */
          __('Edit Form: %s', 'ppsgen'),
          (string) $form['name']
        );
      }
      return __('Edit Form', 'ppsgen');
    }
    return __('Create New Form', 'ppsgen');
  }

  public function renderFormShortcode($atts)
  {
    $atts = shortcode_atts(
      [
        'id' => 0,
      ],
      $atts,
      'id',
    );
    $formId = (int) $atts['id'];
    if (!$formId) {
      return '<div class="ppsgen-error">Invalid form ID</div>';
    }
    return $this->getView()->renderFormView(['id' => $formId]);
  }
}
