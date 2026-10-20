<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactFieldSubmodulePpsgen extends BaseModulePpsgen
{
  public function init($context = 'backend')
  {
    parent::init();
    if ($context == 'backend') {
      $this->getModel()->maybeMigrate();
      add_action('admin_enqueue_scripts', [$this, 'registerModuleBackendAssets']);
      add_filter('ppsgen_module_pages', [$this, 'registerModulePages'], 99);
      add_filter('ppsgen_module_tab', [$this, 'registerModuleTabMap'], 99);
      add_filter('ppsgen_module_navigation', [$this, 'registerModuleNavigation'], 99);
      add_filter('ppsgen_module_page_meta', [$this, 'registerModulePageMeta'], 99);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.contact_field.options_html', [$this->getView(), 'renderContactEditOptions']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.contact_field.edit_field_settings', [$this->getView(), 'renderContactFieldEditSettings'], 10);
    }
  }

  public function registerModuleBackendAssets()
  {
    $moduleDashboardAssetsUrl = PPSGEN_MODULES_URL . 'dashboard/assets/';
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'contact/submodules/contact_field/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'contact_field':
        wp_register_script('ppsgen-backend-contact-field-list', $moduleAssetsUrl . 'js/backend.list.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-contact-field-list');
        break;
      case 'contact_field_edit':
        wp_register_script('ppsgen-backend-contact-field-edit', $moduleAssetsUrl . 'js/backend.edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-contact-field-edit');
        wp_enqueue_script('jquery-ui-sortable');
        break;
    }
  }

  public function registerModuleNavigation($data)
  {
    $newItem = [
      'title' => 'Fields',
      'slug' => 'contact_field',
      'icon' => 'dashicons dashicons-button',
      'children' => [
        [
          'title' => 'Create New',
          'slug' => 'contact_field_edit',
          'icon' => 'dashicons dashicons-plus',
        ],
      ],
    ];
    $insertAt = 3;
    array_splice($data, $insertAt, 0, [$newItem]);
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['contact_field'] = [$this->getView(), 'renderContactFieldList'];
    $pages['contact_field_edit'] = [$this->getView(), 'renderContactFieldEdit'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['contact_field'] = 'contact_field';
    $tabs['contact_field_edit'] = 'contact_field';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['contact_field'] = [
      'page_title' => __('Contact Fields', 'ppsgen'),
      'admin_title' => __('Contact Fields', 'ppsgen'),
      'menu_title' => __('Fields', 'ppsgen'),
    ];
    $data['contact_field_edit'] = [
      'page_title' => __('Contact Field', 'ppsgen'),
      'page_title_callback' => [$this, 'getContactFieldEditPageTitle'],
      'admin_title_callback' => [$this, 'getContactFieldEditPageTitle'],
      'menu_title' => __('Create New', 'ppsgen'),
    ];
    return $data;
  }

  public function getContactFieldEditPageTitle(): string
  {
    $fieldId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($fieldId > 0) {
      $field = $this->getModel()->getContactFieldById($fieldId);
      if (!empty($field['label'])) {
        return sprintf(
          /* translators: %s: contact field label */
          __('Edit Contact Field: %s', 'ppsgen'),
          (string) $field['label']
        );
      }
      return __('Edit Contact Field', 'ppsgen');
    }
    return __('Create New Contact Field', 'ppsgen');
  }
}
