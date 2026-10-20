<?php

if (!defined('ABSPATH')) {
  exit();
}

class EcaModulePpsgen extends BaseModulePpsgen
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
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('eca.template.edit.template_html', [$this->getView(), 'renderEcaEditTemplateTemplate'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('eca.template.edit.options_html', [$this->getView(), 'renderEcaEditTemplateOptions'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('eca.template.edit.design_html', [$this->getView(), 'renderEcaEditTemplateDesign'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('eca.chain.edit.steps_html', [$this->getView(), 'renderEcaEditChainSteps'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('eca.chain.edit.options_html', [$this->getView(), 'renderEcaEditChainOptions'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('eca.chain.edit.contacts_html', [$this->getView(), 'renderEcaEditChainContacts'], 10);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('eca.chain.edit.stats_html', [$this->getView(), 'renderEcaEditChainStats'], 10);
      $extCronActive = $this->getModel()->getOption('ppsgen_ext_cron', 'cron', 0, 'eca');
      if (empty($extCronActive)) {
        add_action('init', [$this, 'overrideExtCrons'], 1);
        add_action('init', [$this, 'registerEcaFlowCron']); //ppsgen_cron_eca_runEcaFlowCron
        add_action('init', [$this, 'registerEcaApplyChainToContactsCron']); //ppsgen_cron_eca_runEcaApplyChainToContactsCron
        add_action('init', [$this, 'registerEcaSendEmailCron']); //ppsgen_cron_eca_runEcaSendEmailCron
        add_action('init', [$this, 'registerEcaAutoBounceCron']); //ppsgen_cron_eca_runEcaAutoBounceCron
        add_filter('cron_schedules', [$this, 'registerEcaFlowCronSchedule']);
        add_filter('cron_schedules', [$this, 'registerEcaApplyChainToContactsCronSchedule']);
        add_filter('cron_schedules', [$this, 'registerEcaSendEmailCronSchedule']);
        add_filter('cron_schedules', [$this, 'registerEcaAutoBounceCronSchedule']);
      }
    }
    if ($context == 'frontend') {
      $extCronActive = $this->getModel()->getOption('ppsgen_ext_cron', 'cron', 0, 'eca');
      if (empty($extCronActive)) {
        add_action('init', [$this->getView(), 'getEcaActionFromEmail']);
        add_action('init', [$this->getView(), 'getEcaUnsubscribeActionFromEmail']);
      }
    }
  }

  public function deactivatePluginHook()
  {
    global $wpdb;
    $this->overrideFreeCrons();
  }

  public function overrideFreeCrons()
  {
    $hooks = ['ppsgen_cron_eca_runEcaFlowCron', 'ppsgen_cron_eca_runEcaApplyChainToContactsCron', 'ppsgen_cron_eca_runEcaSendEmailCron', 'ppsgen_cron_eca_runEcaAutoBounceCron'];
    foreach ($hooks as $hook) {
      $timestamp = wp_next_scheduled($hook);
      if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
      }
    }
  }

  public function registerEcaFlowCron()
  {
    if (!wp_next_scheduled('ppsgen_cron_eca_runEcaFlowCron')) {
      wp_schedule_event(time(), 'ppsgen_eca_flow', 'ppsgen_cron_eca_runEcaFlowCron');
    }
  }

  public function registerEcaApplyChainToContactsCron()
  {
    if (!wp_next_scheduled('ppsgen_cron_eca_runEcaApplyChainToContactsCron')) {
      wp_schedule_event(time() + 1, 'ppsgen_eca_chain', 'ppsgen_cron_eca_runEcaApplyChainToContactsCron');
    }
  }

  public function registerEcaSendEmailCron()
  {
    if (!wp_next_scheduled('ppsgen_cron_eca_runEcaSendEmailCron')) {
      wp_schedule_event(time() + 2, 'ppsgen_eca_email', 'ppsgen_cron_eca_runEcaSendEmailCron');
    }
  }

  public function registerEcaAutoBounceCron()
  {
    if (!wp_next_scheduled('ppsgen_cron_eca_runEcaAutoBounceCron')) {
      wp_schedule_event(time() + 3, 'ppsgen_eca_auto_bounce', 'ppsgen_cron_eca_runEcaAutoBounceCron');
    }
  }

  public function overrideExtCrons()
  {
    $hooks = ['ppsgen_cron_ecaExt_runEcaFlowCron', 'ppsgen_cron_ecaExt_runEcaApplyChainToContactsCron', 'ppsgen_cron_ecaExt_runEcaSendEmailCron', 'ppsgen_cron_ecaExt_runEcaAutoBounceCron'];
    foreach ($hooks as $hook) {
      $timestamp = wp_next_scheduled($hook);
      if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
      }
    }
  }

  public function registerEcaFlowCronSchedule($schedules)
  {
    $schedules['ppsgen_eca_flow'] = [
      'interval' => 55,
      'display' => 'ECA Flow',
    ];
    return $schedules;
  }

  public function registerEcaApplyChainToContactsCronSchedule($schedules)
  {
    $schedules['ppsgen_eca_chain'] = [
      'interval' => 50,
      'display' => 'ECA Apply Campaign',
    ];
    return $schedules;
  }

  public function registerEcaSendEmailCronSchedule($schedules)
  {
    $schedules['ppsgen_eca_email'] = [
      'interval' => 60,
      'display' => 'ECA Send Email',
    ];
    return $schedules;
  }

  public function registerEcaAutoBounceCronSchedule($schedules)
  {
    $schedules['ppsgen_eca_auto_bounce'] = [
      'interval' => 60,
      'display' => 'ECA Auto Bounce',
    ];
    return $schedules;
  }

  public function registerModuleBackendAssets()
  {
    $moduleDashboardAssetsUrl = PPSGEN_MODULES_URL . 'dashboard/assets/';
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'eca/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'eca_overview':
        wp_register_style('ppsgen-backend-eca-overview', $moduleDashboardAssetsUrl . 'css/backend.overview.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-eca-overview');
        break;
      case 'eca_templates':
        wp_register_script('ppsgen-backend-eca-templates', $moduleAssetsUrl . 'js/backend.templates.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-templates');
        break;
      case 'eca_flows':
        wp_register_script('ppsgen-backend-eca-flows', $moduleAssetsUrl . 'js/backend.flows.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-flows');
        break;
      case 'eca_newsletters':
      case 'eca_chains':
        wp_register_style('ppsgen-backend-eca-chains-list', $moduleAssetsUrl . 'css/backend.chains.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-eca-chains-list');
        wp_register_script('ppsgen-backend-eca-chains', $moduleAssetsUrl . 'js/backend.chains.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-chains');
        break;
      case 'eca_flows_logs':
        wp_register_script('ppsgen-backend-eca-flows-logs', $moduleAssetsUrl . 'js/backend.flows.logs.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-flows-logs');
        break;
      case 'eca_step_events':
        wp_register_script('ppsgen-backend-eca-steps-event', $moduleAssetsUrl . 'js/backend.steps.event.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-steps-event');
        break;
      case 'eca_conversions':
        wp_register_script('ppsgen-backend-eca-conversions', $moduleAssetsUrl . 'js/backend.conversions.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-conversions');
        break;
      case 'eca_template_edit':
        wp_register_style('ppsgen-backend-eca-template-edit', $moduleAssetsUrl . 'css/backend.template.edit.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-eca-template-edit');
        wp_register_script('ppsgen-backend-eca-template-edit', $moduleAssetsUrl . 'js/backend.template.edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-template-edit');
        $this->getFrame()->core('component')->loadSortable();
        break;
      case 'eca_chain_edit':
        wp_register_style('ppsgen-backend-eca-chain-edit', $moduleAssetsUrl . 'css/backend.chain.edit.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-eca-chain-edit');
        wp_register_script('ppsgen-backend-eca-newsletters', $moduleAssetsUrl . 'js/backend.newsletters.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-newsletters');
        wp_localize_script('ppsgen-backend-eca-newsletters', 'ppsgenNewsletterL10n', [
          'saving' => __('Saving…', 'ppsgen'),
          'saved' => __('Saved', 'ppsgen'),
          'error' => __('Not saved', 'ppsgen'),
          'badEmail' => __('Enter a valid email address', 'ppsgen'),
          'saveEmail' => __('Save email', 'ppsgen'),
        ]);
        wp_register_script('ppsgen-backend-eca-chain-step-fields-edit', $moduleAssetsUrl . 'js/backend.chain.step.fields.edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-chain-step-fields-edit');
        wp_register_script('ppsgen-backend-eca-chain-step-edit', $moduleAssetsUrl . 'js/backend.chain.step.edit.js', ['jquery', 'ppsgen-backend-eca-chain-step-fields-edit'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-chain-step-edit');
        $this->getFrame()->core('component')->loadSortable();
        break;
      case 'eca_options':
        wp_register_script('ppsgen-backend-eca-options', $moduleAssetsUrl . 'js/backend.options.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
        wp_enqueue_script('ppsgen-backend-eca-options');
        break;
    }
  }

  public function registerModuleNavigation($data)
  {
    $new = [
      'title' => 'Campaigns',
      'slug' => 'eca_chains',
      'icon' => 'dashicons dashicons-email-alt',
      'children' => [
        [
          'title' => 'Overview',
          'slug' => 'eca_overview',
          'icon' => 'dashicons dashicons-admin-home',
        ],
        [
          'title' => 'Templates',
          'slug' => 'eca_templates',
          'icon' => 'dashicons dashicons-admin-appearance',
        ],
        [
          'title' => 'Campaigns',
          'slug' => 'eca_chains',
          'icon' => 'dashicons dashicons-admin-links',
        ],
        [
          'title' => 'Flows',
          'slug' => 'eca_flows',
          'icon' => 'dashicons dashicons dashicons-randomize',
        ],
        [
          'title' => 'Flow Logs',
          'slug' => 'eca_flows_logs',
          'icon' => 'dashicons dashicons-calendar-alt',
        ],
        [
          'title' => 'Email Events',
          'slug' => 'eca_step_events',
          'icon' => 'dashicons dashicons-visibility',
        ],
        [
          'title' => 'Conversions',
          'slug' => 'eca_conversions',
          'icon' => 'dashicons dashicons-chart-line',
        ],
        [
          'title' => 'Edit Campaign',
          'slug' => 'eca_chain_edit',
          'icon' => 'dashicons dashicons-admin-links',
        ],
        [
          'title' => 'Options',
          'slug' => 'eca_options',
          'icon' => 'dashicons dashicons-admin-settings',
        ],
      ],
    ];
    $data[] = $new;
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['eca_overview'] = [$this->getView(), 'renderEcaOverview'];
    $pages['eca_templates'] = [$this->getView(), 'renderEcaTemplates'];
    $pages['eca_chains'] = [$this->getView(), 'renderEcaChains'];
    $pages['eca_newsletters'] = [$this->getView(), 'renderEcaNewsletters'];
    $pages['eca_flows'] = [$this->getView(), 'renderEcaFlows'];
    $pages['eca_flows_logs'] = [$this->getView(), 'renderEcaFlowsLogs'];
    $pages['eca_step_events'] = [$this->getView(), 'renderEcaStepEvents'];
    $pages['eca_conversions'] = [$this->getView(), 'renderEcaConversions'];
    $pages['eca_options'] = [$this->getView(), 'renderEcaOptions'];
    $pages['eca_chain_edit'] = [$this->getView(), 'renderEcaChainEdit'];
    $pages['eca_template_edit'] = [$this->getView(), 'renderEcaTemplateEdit'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['eca_overview'] = 'eca';
    $tabs['eca_templates'] = 'eca';
    $tabs['eca_template_edit'] = 'eca';
    $tabs['eca_chains'] = 'eca';
    $tabs['eca_newsletters'] = 'eca';
    $tabs['eca_flows'] = 'eca';
    $tabs['eca_chain_edit'] = 'eca';
    $tabs['eca_flows_logs'] = 'eca';
    $tabs['eca_step_events'] = 'eca';
    $tabs['eca_conversions'] = 'eca';
    $tabs['eca_options'] = 'eca';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['eca_overview'] = [
      'page_title' => __('Email Campaigns Overview', 'ppsgen'),
      'admin_title' => __('Email Campaigns Overview', 'ppsgen'),
      'menu_title' => __('Overview', 'ppsgen'),
    ];
    $data['eca_templates'] = [
      'page_title' => __('Email Templates', 'ppsgen'),
      'admin_title' => __('Email Templates', 'ppsgen'),
      'menu_title' => __('Templates', 'ppsgen'),
    ];
    $data['eca_template_edit'] = [
      'page_title' => __('Email Template', 'ppsgen'),
      'page_title_callback' => [$this, 'getEcaTemplateEditPageTitle'],
      'admin_title_callback' => [$this, 'getEcaTemplateEditPageTitle'],
    ];
    $data['eca_newsletters'] = [
      'page_title' => __('Newsletters', 'ppsgen'),
      'admin_title' => __('Newsletters', 'ppsgen'),
      'menu_title' => __('Newsletters', 'ppsgen'),
    ];
    $data['eca_chains'] = [
      'page_title' => __('Email Campaigns', 'ppsgen'),
      'admin_title' => __('Email Campaigns', 'ppsgen'),
      'menu_title' => __('Campaigns', 'ppsgen'),
    ];
    $data['eca_chain_edit'] = [
      'page_title' => __('Email Campaign', 'ppsgen'),
      'page_title_callback' => [$this, 'getEcaChainEditPageTitle'],
      'admin_title_callback' => [$this, 'getEcaChainEditPageTitle'],
    ];
    $data['eca_flows'] = [
      'page_title' => __('Email Flows', 'ppsgen'),
      'admin_title' => __('Email Flows', 'ppsgen'),
      'menu_title' => __('Flows', 'ppsgen'),
    ];
    $data['eca_flows_logs'] = [
      'page_title' => __('Email Flow Logs', 'ppsgen'),
      'admin_title' => __('Email Flow Logs', 'ppsgen'),
      'menu_title' => __('Flow Logs', 'ppsgen'),
    ];
    $data['eca_step_events'] = [
      'page_title' => __('Email Events', 'ppsgen'),
      'admin_title' => __('Email Events', 'ppsgen'),
      'menu_title' => __('Email Events', 'ppsgen'),
    ];
    $data['eca_conversions'] = [
      'page_title' => __('Email Conversions', 'ppsgen'),
      'admin_title' => __('Email Conversions', 'ppsgen'),
      'menu_title' => __('Conversions', 'ppsgen'),
    ];
    $data['eca_options'] = [
      'page_title' => __('Email Campaign Options', 'ppsgen'),
      'admin_title' => __('Email Campaign Options', 'ppsgen'),
      'menu_title' => __('Options', 'ppsgen'),
    ];
    return $data;
  }

  public function getEcaChainEditPageTitle(): string
  {
    $chainId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($chainId > 0) {
      $chain = $this->getModel()->getChainById($chainId);
      if (!empty($chain['name'])) {
        return sprintf(
          /* translators: %s: email campaign name */
          __('Edit Email Campaign: %s', 'ppsgen'),
          (string) $chain['name']
        );
      }
      return __('Edit Email Campaign', 'ppsgen');
    }
    return __('Email Campaign', 'ppsgen');
  }

  public function getEcaTemplateEditPageTitle(): string
  {
    $templateId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($templateId > 0) {
      $template = $this->getModel()->getTemplateById($templateId);
      if (!empty($template['name'])) {
        return sprintf(
          /* translators: %s: email template name */
          __('Edit Email Template: %s', 'ppsgen'),
          (string) $template['name']
        );
      }
      return __('Edit Email Template', 'ppsgen');
    }
    return __('Create New Email Template', 'ppsgen');
  }
}
