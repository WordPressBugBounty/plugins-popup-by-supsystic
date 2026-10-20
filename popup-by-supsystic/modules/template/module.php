<?php

if (!defined('ABSPATH')) {
  exit();
}

class TemplateModulePpsgen extends BaseModulePpsgen
{
  public function init($context = 'backend')
  {
    parent::init();
    if ($context == 'backend') {
      $this->getModel()->maybeMigrate();
      add_action('admin_enqueue_scripts', [$this, 'registerModuleBackendAssets']);
      add_filter('ppsgen_module_pages', [$this, 'registerModulePages']);
      add_filter('ppsgen_module_tab', [$this, 'registerModuleTabMap']);
      add_filter('ppsgen_module_navigation', [$this, 'registerModuleNavigation']);
      add_filter('ppsgen_module_page_meta', [$this, 'registerModulePageMeta']);
    }
  }

  public function registerModuleBackendAssets()
  {
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'template/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'template_add':
        $assetsDir = __DIR__ . '/assets/';
        wp_register_style('ppsgen-backend-template-list', $moduleAssetsUrl . 'css/backend.list.css', [], PPSGEN_VERSION . '.' . (int) @filemtime($assetsDir . 'css/backend.list.css'));
        wp_enqueue_style('ppsgen-backend-template-list');
        wp_register_script('ppsgen-backend-template-list', $moduleAssetsUrl . 'js/backend.list.js', ['jquery'], PPSGEN_VERSION . '.' . (int) @filemtime($assetsDir . 'js/backend.list.js'), ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-template-list');
        break;
    }
  }

  public function registerModuleNavigation($data)
  {
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['template_add'] = [$this->getView(), 'renderTemplateList'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['template_add'] = 'template';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['template_add'] = [
      'page_title' => __('Choose Template', 'ppsgen'),
      'page_title_callback' => [$this, 'getTemplateAddPageTitle'],
      'admin_title_callback' => [$this, 'getTemplateAddPageTitle'],
    ];
    return $data;
  }

  public function getTemplateAddPageTitle(): string
  {
    $templateType = (string) $this->getFrame()->core('request')->getVar('template_type', 'get');
    $templateTypeList = $this->getModel()->templateTypeList();
    if (!empty($templateType) && !empty($templateTypeList[$templateType])) {
      return sprintf(
        /* translators: %s: template type label */
        __('Choose a Template for %s', 'ppsgen'),
        (string) $templateTypeList[$templateType]
      );
    }
    return __('Choose Template', 'ppsgen');
  }
}
