<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactModulePpsgen extends BaseModulePpsgen
{
  public function beforeInit()
  {
    $model = $this->getModel();
    $wpAutoImportEnabled = $model->isWpContactAutoImportEnabled();
    $wooAutoImportEnabled = $model->isWooContactAutoImportEnabled();
    if ($wpAutoImportEnabled || $wooAutoImportEnabled) {
      $this->ensureContactSupportSubmodules();
    }
    if ($wpAutoImportEnabled) {
      add_action('user_register', [$this, 'handleAutoImportWpUser'], 20, 1);
    }
    if ($wooAutoImportEnabled) {
      add_action('woocommerce_created_customer', [$this, 'handleAutoImportWooCustomer'], 20, 3);
      add_action('woocommerce_checkout_order_processed', [$this, 'handleAutoImportWooCheckoutOrder'], 20, 3);
      add_action('woocommerce_new_order', [$this, 'handleAutoImportWooNewOrder'], 20, 2);
      add_action('woocommerce_update_order', [$this, 'handleAutoImportWooUpdatedOrder'], 20, 2);
      add_action('woocommerce_order_status_changed', [$this, 'handleAutoImportWooOrderStatusChanged'], 20, 4);
      add_action('woocommerce_process_shop_order_meta', [$this, 'handleAutoImportWooAdminOrderSave'], 60, 2);
    }
    return true;
  }

  public function init($context = 'backend')
  {
    parent::init();
    if ($context == 'backend') {
      $this->ensureContactSupportSubmodules();
      $this->getSubmod('contact_field')->init($context);
      $this->getSubmod('contact_tag')->init($context);
      $this->getModel()->maybeMigrate();
      add_action('admin_enqueue_scripts', [$this, 'registerModuleBackendAssets']);
      add_filter('ppsgen_module_pages', [$this, 'registerModulePages']);
      add_filter('ppsgen_module_tab', [$this, 'registerModuleTabMap']);
      add_filter('ppsgen_module_navigation', [$this, 'registerModuleNavigation']);
      add_filter('ppsgen_module_page_meta', [$this, 'registerModulePageMeta']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.edit.options_html', [$this->getView(), 'renderContactEditOptions']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.edit.stats_html', [$this->getView(), 'renderContactEditStats']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.edit.logs_html', [$this->getView(), 'renderContactEditLogs']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.edit.email_campaigns_html', [$this->getView(), 'renderContactEditEmailCampaigns']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.edit.email_chat_html', [$this->getView(), 'renderContactEditEmailChat']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.import.generate_contact_html', [$this->getView(), 'renderContactImportGenerate']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.import.generate_woo_contact_html', [$this->getView(), 'renderContactImportGenerateWoo']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('contact.import.csv_html', [$this->getView(), 'renderContactImportCsv']);
    }
  }

  protected function ensureContactSupportSubmodules(): void
  {
    if (!$this->getSubmod('contact_field')) {
      $this->registerSubmodule('contact_field');
    }
    if (!$this->getSubmod('contact_tag')) {
      $this->registerSubmodule('contact_tag');
    }
  }

  public function handleAutoImportWpUser($userId): void
  {
    $this->getModel()->autoImportWpUserById((int) $userId);
  }

  public function handleAutoImportWooCustomer($customerId): void
  {
    $this->getModel()->autoImportWooCustomerById((int) $customerId);
  }

  public function handleAutoImportWooCheckoutOrder($orderId, $postedData = [], $order = null): void
  {
    $this->getModel()->autoImportWooOrderById((int) $orderId, $order);
  }

  public function handleAutoImportWooNewOrder($orderId, $order = null): void
  {
    $this->getModel()->autoImportWooOrderById((int) $orderId, $order);
  }

  public function handleAutoImportWooUpdatedOrder($orderId, $order = null): void
  {
    $this->getModel()->autoImportWooOrderById((int) $orderId, $order);
  }

  public function handleAutoImportWooOrderStatusChanged($orderId, $oldStatus = '', $newStatus = '', $order = null): void
  {
    $this->getModel()->autoImportWooOrderById((int) $orderId, $order);
  }

  public function handleAutoImportWooAdminOrderSave($orderId, $order = null): void
  {
    $this->getModel()->autoImportWooOrderById((int) $orderId, $order);
  }

  public function registerModuleBackendAssets()
  {
    $moduleDashboardAssetsUrl = PPSGEN_MODULES_URL . 'dashboard/assets/';
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'contact/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'contact_overview':
        wp_register_style('ppsgen-backend-dashboard-overview', $moduleDashboardAssetsUrl . 'css/backend.overview.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-dashboard-overview');
        break;
      case 'contact_list':
        wp_register_style('ppsgen-backend-contact-engagement', $moduleAssetsUrl . 'css/backend.engagement.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-contact-engagement');
        $this->getFrame()->core('component')->loadPpsgenselect();
        wp_register_script('ppsgen-backend-contact-list', $moduleAssetsUrl . 'js/backend.list.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-contact-list');
        break;
      case 'contact_edit':
        wp_register_style('ppsgen-backend-contact-engagement', $moduleAssetsUrl . 'css/backend.engagement.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-contact-engagement');
        wp_register_script('ppsgen-backend-contact-edit', $moduleAssetsUrl . 'js/backend.edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-contact-edit');
        break;
      case 'contact_options':
        $this->getFrame()->core('component')->loadPpsgenselect();
        wp_register_script('ppsgen-backend-contact-options', $moduleAssetsUrl . 'js/backend.options.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-contact-options');
        break;
    }
  }

  public function enqueueContactLookupAssets(): void
  {
    static $localized = false;
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'contact/assets/';
    wp_register_style('ppsgen-backend-contact-lookup', $moduleAssetsUrl . 'css/backend.lookup.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-backend-contact-lookup');
    wp_register_script('ppsgen-backend-contact-lookup', $moduleAssetsUrl . 'js/backend.lookup.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-contact-lookup');
    if (!$localized) {
      wp_localize_script('ppsgen-backend-contact-lookup', 'ppsgenContactLookupI18n', [
        'searching' => __('Searching contacts...', 'ppsgen'),
        'no_results' => __('No matching contacts found.', 'ppsgen'),
        'type_more' => __('Type at least %d characters to search.', 'ppsgen'),
        'search_error' => __('Unable to search contacts right now.', 'ppsgen'),
        'search_placeholder' => __('Search by contact ID or email', 'ppsgen'),
        'selected_contact' => __('Selected contact', 'ppsgen'),
      ]);
      $localized = true;
    }
  }

  public function registerModuleNavigation($data)
  {
    $new = [
      'title' => 'Contacts',
      'slug' => 'contact_list',
      'icon' => 'dashicons dashicons-admin-users',
      'children' => [
        [
          'title' => 'Overview',
          'slug' => 'contact_overview',
          'icon' => 'dashicons dashicons-admin-home',
        ],
        [
          'title' => 'Create New',
          'slug' => 'contact_edit',
          'icon' => 'dashicons dashicons-plus',
        ],
        [
          'title' => 'Options',
          'slug' => 'contact_options',
          'icon' => 'dashicons dashicons-admin-settings',
        ],
        [
          'title' => 'Import',
          'slug' => 'contact_import',
          'icon' => 'dashicons dashicons-database-import',
        ],
      ],
    ];
    $data[] = $new;
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['contact_overview'] = [$this->getView(), 'renderContactOverview'];
    $pages['contact_list'] = [$this->getView(), 'renderContactList'];
    $pages['contact_edit'] = [$this->getView(), 'renderContactEdit'];
    $pages['contact_options'] = [$this->getView(), 'renderContactOptions'];
    $pages['contact_import'] = [$this->getView(), 'renderContactImport'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['contact_overview'] = 'contact';
    $tabs['contact_list'] = 'contact';
    $tabs['contact_edit'] = 'contact';
    $tabs['contact_options'] = 'contact';
    $tabs['contact_import'] = 'contact';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['contact_overview'] = [
      'page_title' => __('Contacts Overview', 'ppsgen'),
      'admin_title' => __('Contacts Overview', 'ppsgen'),
      'menu_title' => __('Overview', 'ppsgen'),
    ];
    $data['contact_list'] = [
      'page_title' => __('Contacts', 'ppsgen'),
      'admin_title' => __('Contacts', 'ppsgen'),
      'menu_title' => __('Contacts', 'ppsgen'),
    ];
    $data['contact_edit'] = [
      'page_title' => __('Contact', 'ppsgen'),
      'page_title_callback' => [$this, 'getContactEditPageTitle'],
      'admin_title_callback' => [$this, 'getContactEditPageTitle'],
    ];
    $data['contact_options'] = [
      'page_title' => __('Contact Options', 'ppsgen'),
      'admin_title' => __('Contact Options', 'ppsgen'),
      'menu_title' => __('Options', 'ppsgen'),
    ];
    $data['contact_import'] = [
      'page_title' => __('Contact Import', 'ppsgen'),
      'admin_title' => __('Contact Import', 'ppsgen'),
      'menu_title' => __('Import', 'ppsgen'),
    ];
    return $data;
  }

  public function getContactEditPageTitle(): string
  {
    $contactId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($contactId > 0) {
      $contactEmail = $this->getModel()->getContactFieldValueBySlug($contactId, 'email');
      if ($contactEmail !== '') {
        return sprintf(
          /* translators: %s: contact email */
          __('Edit Contact: %s', 'ppsgen'),
          $contactEmail
        );
      }
      return __('Edit Contact', 'ppsgen');
    }
    return __('Create New Contact', 'ppsgen');
  }
}
