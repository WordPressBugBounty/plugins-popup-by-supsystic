<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactTagSubmodulePpsgen extends BaseModulePpsgen
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
        ->addFilter('contact.contact_tag.options_html', [$this->getView(), 'renderContactEditOptions']);
    }
  }

  public function registerModuleBackendAssets()
  {
    $moduleDashboardAssetsUrl = PPSGEN_MODULES_URL . 'dashboard/assets/';
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'contact/submodules/contact_tag/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'contact_tag':
        wp_register_script('ppsgen-backend-contact-tag-list', $moduleAssetsUrl . 'js/backend.list.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-contact-tag-list');
        break;
      case 'contact_tag_edit':
        wp_register_script('ppsgen-backend-contact-tag-edit', $moduleAssetsUrl . 'js/backend.edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-contact-tag-edit');
        break;
    }
  }

  public function registerModuleNavigation($data)
  {
    foreach ($data as $key => $moduleNav) {
      if ($moduleNav['slug'] == 'contact_list') {
        $data[$key]['children'][] = [
          'title' => 'Tags',
          'slug' => 'contact_tag',
          'icon' => 'dashicons dashicons-tag',
        ];
        $data[$key]['children'][] = [
          'title' => 'Edit Tag',
          'slug' => 'contact_tag_edit',
          'icon' => 'dashicons dashicons-tag',
          'hide' => true,
        ];
      }
    }
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['contact_tag'] = [$this->getView(), 'renderContactTagList'];
    $pages['contact_tag_edit'] = [$this->getView(), 'renderContactTagEdit'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['contact_tag'] = 'contact_tag';
    $tabs['contact_tag_edit'] = 'contact_tag';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['contact_tag'] = [
      'page_title' => __('Contact Tags', 'ppsgen'),
      'admin_title' => __('Contact Tags', 'ppsgen'),
      'menu_title' => __('Tags', 'ppsgen'),
    ];
    $data['contact_tag_edit'] = [
      'page_title' => __('Contact Tag', 'ppsgen'),
      'page_title_callback' => [$this, 'getContactTagEditPageTitle'],
      'admin_title_callback' => [$this, 'getContactTagEditPageTitle'],
      'menu_title' => __('Edit Tag', 'ppsgen'),
    ];
    return $data;
  }

  public function getContactTagEditPageTitle(): string
  {
    $tagId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($tagId > 0) {
      $tag = $this->getModel()->getContactTagById($tagId);
      if (!empty($tag['slug'])) {
        return sprintf(
          /* translators: %s: contact tag slug */
          __('Edit Contact Tag: %s', 'ppsgen'),
          (string) $tag['slug']
        );
      }
      return __('Edit Contact Tag', 'ppsgen');
    }
    return __('Create New Contact Tag', 'ppsgen');
  }
}
