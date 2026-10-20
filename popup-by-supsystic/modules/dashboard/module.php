<?php

if (!defined('ABSPATH')) {
  exit();
}

class DashboardModulePpsgen extends BaseModulePpsgen
{
  public function init($context = 'backend')
  {
    parent::init();
    if ($context == 'backend') {
      $this->getModel()->maybeMigrate();
      $this->getModel()->ensureMailConfigStorage();
      add_action('admin_menu', [$this, 'registerPluginPages']);
      add_action('admin_init', [$this, 'maybeOpenOverviewFirst']);
      add_filter('option_page_capability_' . 'ppsgen', 'manage_options');
      add_action('admin_enqueue_scripts', [$this, 'registerPluginBackendLayoutAssets']);
      add_action('admin_enqueue_scripts', [$this, 'addAjaxSettings']);
      add_action('admin_head', [$this, 'printBackendThemeInitScript']);
      add_action('admin_head', [$this, 'printMenuIconStyle']);
      add_filter('admin_body_class', [$this, 'addBackendThemeBodyClass']);
      add_filter('ppsgen_module_pages', [$this, 'registerModulePages']);
      add_filter('ppsgen_module_tab', [$this, 'registerModuleTabMap']);
      add_filter('ppsgen_module_navigation', [$this, 'registerModuleNavigation']);
      add_filter('ppsgen_module_page_meta', [$this, 'registerModulePageMeta']);
      // Runs after every module so the plain-English labels win
      add_filter('ppsgen_module_page_meta', [$this, 'relabelModulePageMeta'], 100);
    }
  }

  public function registerPluginBackendLayoutAssets()
  {
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'dashboard/assets/';
    $pluginAssetsUrl = PPSGEN_PLUGIN_URL . 'public/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    if ($tab) {
      wp_register_style('ppsgen-backend', $pluginAssetsUrl . 'css/ppsgen.backend.css', [], PPSGEN_VERSION);
      wp_enqueue_style('ppsgen-backend');
      wp_register_style('ppsgen-backend-shell', $moduleAssetsUrl . 'css/backend.shell.css', ['ppsgen-backend'], PPSGEN_VERSION . '.' . (int) @filemtime(__DIR__ . '/assets/css/backend.shell.css'));
      wp_enqueue_style('ppsgen-backend-shell');
      wp_register_style('ppsgen-backend-dark', $pluginAssetsUrl . 'css/ppsgen.backend.dark.css', ['ppsgen-backend-shell'], PPSGEN_VERSION . '.' . (int) @filemtime(PPSGEN_PLUGIN_DIR . 'public/assets/css/ppsgen.backend.dark.css'));
      wp_enqueue_style('ppsgen-backend-dark');
      // The PRO offer dialog (template catalogues, PRO palettes and other PRO gates in the editors)
      wp_enqueue_style('ppsgen-backend-prooffer', $pluginAssetsUrl . 'css/ppsgen.backend.prooffer.css', ['ppsgen-backend-dark'], PPSGEN_VERSION . '.' . (int) @filemtime(PPSGEN_PLUGIN_DIR . 'public/assets/css/ppsgen.backend.prooffer.css'));
      wp_enqueue_script('ppsgen-backend-prooffer', $pluginAssetsUrl . 'js/ppsgen.backend.prooffer.js', ['jquery'], PPSGEN_VERSION . '.' . (int) @filemtime(PPSGEN_PLUGIN_DIR . 'public/assets/js/ppsgen.backend.prooffer.js'), ['in_footer' => true]);
      wp_localize_script('ppsgen-backend-prooffer', 'ppsgenProOfferL10n', [
        'offerUrl' => 'https://supsystic.com/plugins/wordpress-popup-plugin/',
        'eyebrow' => __('This one comes with PRO', 'ppsgen'),
        'title' => __('Get the full CES with PRO', 'ppsgen'),
        'text' => __('PRO adds more styles, specialist designs and deeper tools for growing sites, for one yearly price.', 'ppsgen'),
        'list' => [
          __('Premium high-CVR templates for forms, popups, and emails', 'ppsgen'),
          __('Advanced CRM fields: files, maps, taxonomies, WooCommerce products, attributes, categories, and tags', 'ppsgen'),
          __('Run as many automations at once as you need, with deeper campaign controls', 'ppsgen'),
          __('GeoLite IP recognition, internal API for custom integrations, and extra premium modules', 'ppsgen'),
        ],
        'priceLabel' => __('Yearly licence · 1 site', 'ppsgen'),
        'priceMeta' => __('renews at the same price · 30-day money-back guarantee', 'ppsgen'),
        'cta' => __('Get PRO for $99 a year', 'ppsgen'),
        'later' => __('Maybe Later', 'ppsgen'),
        'close' => __('Close popup', 'ppsgen'),
      ]);
      wp_register_script('ppsgen-backend', $pluginAssetsUrl . 'js/ppsgen.backend.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend');
      // File time in the version so browsers pick up preview fixes without a plugin version bump
      $livePreviewFile = PPSGEN_PLUGIN_DIR . 'public/assets/js/ppsgen.backend.livepreview.js';
      $livePreviewVersion = PPSGEN_VERSION . (is_file($livePreviewFile) ? '.' . filemtime($livePreviewFile) : '');
      wp_register_script('ppsgen-backend-live-preview', $pluginAssetsUrl . 'js/ppsgen.backend.livepreview.js', ['jquery', 'ppsgen-backend'], $livePreviewVersion, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-live-preview');
      // Colour styles on the Design tab of popups, forms and email templates (8 Free, 16 more with PRO)
      wp_register_script('ppsgen-backend-palettes', $pluginAssetsUrl . 'js/ppsgen.backend.palettes.js', ['jquery', 'ppsgen-backend-live-preview'], PPSGEN_VERSION . '.' . (int) @filemtime(PPSGEN_PLUGIN_DIR . 'public/assets/js/ppsgen.backend.palettes.js'), ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-palettes');
      wp_localize_script('ppsgen-backend-palettes', 'ppsgenPalettesL10n', [
        'pro' => $this->getModel()->isProAddonActive() && !(function_exists('ppsgen_is_pro_license_frozen') && ppsgen_is_pro_license_frozen()),
        'upgradeUrl' => admin_url('admin.php?page=ppsgen&tab=settings_pro'),
        'title' => __('Color style', 'ppsgen'),
        'hint_popup' => __('Pick a palette: the popup, its overlay and every title, text, button and icon change to match. The form inside keeps its own colors (style it in the form). Save to keep it.', 'ppsgen'),
        'hint_form' => __('Pick a palette: the form, its fields, labels, button and tooltips change to match. Save to keep it.', 'ppsgen'),
        'hint_template' => __('Pick a palette: the email background, the content box and every title, text and button change to match. Save to keep it.', 'ppsgen'),
        'proTitle' => __('More palettes', 'ppsgen'),
        'upgrade' => __('Get PRO', 'ppsgen'),
        'proOnly' => __('This palette is part of PRO.', 'ppsgen'),
        'offerEyebrow' => __('This palette comes with PRO', 'ppsgen'),
        'offerTitle' => __('Every color style, one click away', 'ppsgen'),
        'offerText' => __('PRO adds 16 more designer palettes for popups, forms and emails. Pick one and every title, text, button and background changes to match.', 'ppsgen'),
        'keepImage' => __('Keep the background image', 'ppsgen'),
        'applied' => __('Applied', 'ppsgen'),
        'undo' => __('Undo', 'ppsgen'),
        'undone' => __('Previous colors are back.', 'ppsgen'),
        'name_white' => __('White', 'ppsgen'),
        'name_gray' => __('Gray', 'ppsgen'),
        'name_black' => __('Black', 'ppsgen'),
        'name_blue' => __('Blue', 'ppsgen'),
        'name_green' => __('Green', 'ppsgen'),
        'name_sunset' => __('Sunset', 'ppsgen'),
        'name_rose' => __('Rose', 'ppsgen'),
        'name_lavender' => __('Lavender', 'ppsgen'),
        'name_ocean' => __('Ocean', 'ppsgen'),
        'name_sand' => __('Sand', 'ppsgen'),
        'name_citrus' => __('Citrus', 'ppsgen'),
        'name_midnight' => __('Midnight', 'ppsgen'),
        'name_forest' => __('Forest', 'ppsgen'),
        'name_neon' => __('Neon night', 'ppsgen'),
        'name_royal' => __('Royal', 'ppsgen'),
        'name_red' => __('Red', 'ppsgen'),
        'name_orange' => __('Orange', 'ppsgen'),
        'name_purple' => __('Purple', 'ppsgen'),
        'name_mint' => __('Mint', 'ppsgen'),
        'name_peach' => __('Peach', 'ppsgen'),
        'name_olive' => __('Olive', 'ppsgen'),
        'name_coffee' => __('Coffee', 'ppsgen'),
        'name_slate' => __('Slate', 'ppsgen'),
        'name_berry' => __('Berry', 'ppsgen'),
      ]);
      wp_localize_script('ppsgen-backend-live-preview', 'ppsgenLivePreviewL10n', [
        // Preview frames open this page before their HTML is written (see ppsgenFrameWrite)
        'blankUrl' => PPSGEN_PLUGIN_URL . 'public/preview-blank.html',
        'title' => __('Live preview', 'ppsgen'),
        'updating' => __('Updating…', 'ppsgen'),
        'error' => __('Couldn’t update the preview', 'ppsgen'),
        'refresh' => __('Refresh', 'ppsgen'),
        'full' => __('Full preview', 'ppsgen'),
        'reset' => __('Start over', 'ppsgen'),
        'resetText' => __('The preview remembers what you picked or won', 'ppsgen'),
        'resetHint' => __('Forget it and play again as a new visitor', 'ppsgen'),
        'steps' => __('Steps', 'ppsgen'),
        'step' => __('Step', 'ppsgen'),
        'stepPrev' => __('Show the previous step', 'ppsgen'),
        'stepNext' => __('Show the next step', 'ppsgen'),
        'hide' => __('Hide preview', 'ppsgen'),
        'show' => __('Show live preview', 'ppsgen'),
        'pc' => __('Desktop', 'ppsgen'),
        'tablet' => __('Tablet', 'ppsgen'),
        'mobile' => __('Mobile', 'ppsgen'),
        'toggle' => __('Show or hide what is inside', 'ppsgen'),
        'empty' => __('empty', 'ppsgen'),
        'up' => __('Move up', 'ppsgen'),
        'defaultTitle' => __('Title', 'ppsgen'),
        'defaultText' => __('Text', 'ppsgen'),
        'defaultButton' => __('Get started', 'ppsgen'),
        'defaultIcon' => __('Gift', 'ppsgen'),
        'mockSite' => __('Your website', 'ppsgen'),
        'mockNavHome' => __('Home', 'ppsgen'),
        'mockNavShop' => __('Shop', 'ppsgen'),
        'mockNavAbout' => __('About', 'ppsgen'),
        'mockNavContact' => __('Contact', 'ppsgen'),
        'mockButton' => __('Get started', 'ppsgen'),
        'mockTitle' => __('Your page content', 'ppsgen'),
        'mockText' => __('Visitors read your page here. The popup appears on top of it, just like in this preview.', 'ppsgen'),
        'mockMore' => __('Learn more', 'ppsgen'),
        'mockFormTitle' => __('Your page content', 'ppsgen'),
        'unsaved' => __('Unsaved changes', 'ppsgen'),
        'dblEdit' => __('double-click to edit', 'ppsgen'),
        'down' => __('Move down', 'ppsgen'),
        'types' => [
          'row' => __('Row', 'ppsgen'),
          'column' => __('Column', 'ppsgen'),
          'title' => __('Title', 'ppsgen'),
          'textarea' => __('Text', 'ppsgen'),
          'description' => __('Description', 'ppsgen'),
          'desc' => __('Description', 'ppsgen'),
          'image' => __('Image', 'ppsgen'),
          'svg_icon' => __('Icon', 'ppsgen'),
          'icon' => __('Icon', 'ppsgen'),
          'button' => __('Button', 'ppsgen'),
          'submit' => __('Submit button', 'ppsgen'),
          'form' => __('Form', 'ppsgen'),
          'socials' => __('Social links', 'ppsgen'),
          'social' => __('Social links', 'ppsgen'),
          'divider' => __('Divider', 'ppsgen'),
          'iframe' => __('Embed', 'ppsgen'),
          'video' => __('Video', 'ppsgen'),
          'email_content' => __('Email content', 'ppsgen'),
          'unsubscribe' => __('Unsubscribe link', 'ppsgen'),
          'captcha' => __('Captcha', 'ppsgen'),
          'pro' => __('Posts', 'ppsgen'),
          'empty' => __('Spacer', 'ppsgen'),
          'woo_categories' => __('Woo categories', 'ppsgen'),
          'woo_tags' => __('Woo tags', 'ppsgen'),
          'woo_attributes' => __('Woo attributes', 'ppsgen'),
          'woo_products' => __('Woo products', 'ppsgen'),
          'email' => __('Email', 'ppsgen'),
          'text' => __('Text field', 'ppsgen'),
          'phone' => __('Phone', 'ppsgen'),
          'select' => __('Dropdown', 'ppsgen'),
          'checkbox' => __('Checkboxes', 'ppsgen'),
          'radio' => __('Choice', 'ppsgen'),
          'date' => __('Date', 'ppsgen'),
          'range' => __('Range', 'ppsgen'),
          'rating' => __('Rating', 'ppsgen'),
          'number' => __('Number', 'ppsgen'),
          'hidden' => __('Hidden field', 'ppsgen'),
        ],
      ]);
      wp_register_style('ppsgen-backend-dashboard-tour', $moduleAssetsUrl . 'css/backend.tour.css', [], PPSGEN_VERSION);
      wp_enqueue_style('ppsgen-backend-dashboard-tour');
      wp_register_script('ppsgen-backend-dashboard-tour', $moduleAssetsUrl . 'js/backend.tour.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-dashboard-tour');
      wp_enqueue_media();
      $this->getFrame()->core('component')->loadPpsgenselect();
      $this->getFrame()->core('component')->initFrontendScripts();
      $this->getFrame()->core('component')->loadFA();
      $this->getFrame()->core('component')->loadUndr();
    }
    if ($tab == 'dashboard_modules') {
      wp_register_style('ppsgen-backend-dashboard-modules', $moduleAssetsUrl . 'css/backend.modules.css', [], PPSGEN_VERSION . '.' . (int) @filemtime(__DIR__ . '/assets/css/backend.modules.css'));
      wp_enqueue_style('ppsgen-backend-dashboard-modules');
      wp_register_script('ppsgen-backend-dashboard-modules', $moduleAssetsUrl . 'js/backend.modules.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-dashboard-modules');
    }
    if ($tab == 'dashboard_options') {
      wp_register_script('ppsgen-backend-dashboard-options', $moduleAssetsUrl . 'js/backend.options.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-dashboard-options');
    }
    if ($tab == 'dashboard_mail_configs') {
      wp_register_script('ppsgen-backend-dashboard-mail-configs', $moduleAssetsUrl . 'js/backend.mail_configs.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-dashboard-mail-configs');
    }
    if ($tab == 'dashboard_mail_config_edit') {
      wp_register_script('ppsgen-backend-dashboard-mail-config-edit', $moduleAssetsUrl . 'js/backend.mail_config_edit.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-dashboard-mail-config-edit');
    }
    if (in_array($tab, ['plays', 'play_setup', 'play_review', 'play_summary'], true)) {
      wp_register_script('ppsgen-backend-dashboard-plays', $moduleAssetsUrl . 'js/backend.plays.js', ['jquery', 'ppsgen-backend'], PPSGEN_VERSION, ['in_footer' => true]);
      wp_enqueue_script('ppsgen-backend-dashboard-plays');
    }
    if ($tab == 'dashboard_overview') {
      wp_register_style('ppsgen-backend-dashboard-overview', $moduleAssetsUrl . 'css/backend.overview.css', [], PPSGEN_VERSION);
      wp_enqueue_style('ppsgen-backend-dashboard-overview');
    }
  }

  /**
   * After a clean install the first visit to the plugin (its menu entry, or the Dashboard) opens the Overview, which
   * explains what is where. It happens once: every later visit opens the Dashboard as usual.
   */
  public function maybeOpenOverviewFirst()
  {
    $tab = $this->getFrame()->isPluginAdminPage();
    if (!$tab || !get_option('ppsgen_overview_first') || wp_doing_ajax() || !current_user_can('manage_options')) {
      return;
    }
    if ($tab === 'dashboard_overview') {
      delete_option('ppsgen_overview_first');
      return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only reads which screen was asked for
    if ($tab === 'dashboard' && empty($_GET['action']) && empty($_POST)) {
      delete_option('ppsgen_overview_first');
      wp_safe_redirect(admin_url('admin.php?page=ppsgen&tab=dashboard_overview'));
      exit;
    }
  }

  public function registerPluginPages()
  {
    // One WordPress menu entry, right under Dashboard, with the Supsystic logo: the CES top bar handles everything inside the plugin
    add_menu_page(
      __('Customer Engagement', 'ppsgen'),
      __('Customer Engagement', 'ppsgen'),
      'manage_options',
      'ppsgen',
      [$this, 'renderPluginPage'],
      PPSGEN_MODULES_URL . 'dashboard/assets/img/menu-logo.png',
      '2.1'
    );
  }

  // The logo is a 40px picture (sharp on HiDPI screens): show it at the size of the other menu icons, in full colour
  public function printMenuIconStyle()
  {
    ?>
    <style id="ppsgen-menu-icon">
      #adminmenu #toplevel_page_ppsgen .wp-menu-image img { width: 20px; height: 20px; padding: 7px 0 0; opacity: 1; }
    </style>
    <?php
  }

  protected ?PlaysPpsgen $plays = null;

  public function getPlays(): PlaysPpsgen
  {
    if ($this->plays === null) {
      require_once __DIR__ . '/plays/plays.php';
      $this->plays = new PlaysPpsgen($this->getFrame());
    }
    return $this->plays;
  }

  // Top bar sections. "items" are the visible sub-tabs, "tabs" are pages that belong to the section without a sub-tab
  public function getAdminSections(): array
  {
    $isPro = $this->getModel()->isProAddonActive();
    $isLegacyUser = function_exists('ppsgen_is_legacy_user') && ppsgen_is_legacy_user();
    $sections = [
      'home' => [
        'title' => __('Home', 'ppsgen'),
        'tabs' => ['dashboard', 'dashboard_overview'],
      ],
      'plays' => [
        'title' => __('Plays', 'ppsgen'),
        'tabs' => ['plays', 'play_setup', 'play_review', 'play_summary'],
      ],
      'popups' => [
        'title' => __('Popups', 'ppsgen'),
        'tabs' => ['popup_list', 'popup_edit', 'popup_overview'],
        'template_type' => 'popup',
      ],
      'forms' => [
        'title' => __('Forms', 'ppsgen'),
        'tabs' => ['form_list', 'form_edit', 'form_overview'],
        'template_type' => 'form',
      ],
      'contacts' => [
        'title' => __('Contacts', 'ppsgen'),
        'tabs' => ['contact_overview'],
        'items' => [
          ['title' => __('All contacts', 'ppsgen'), 'tab' => 'contact_list', 'tabs' => ['contact_edit']],
          ['title' => __('Tags', 'ppsgen'), 'tab' => 'contact_tag', 'tabs' => ['contact_tag_edit']],
          ['title' => __('Contact fields', 'ppsgen'), 'tab' => 'contact_field', 'tabs' => ['contact_field_edit']],
          ['title' => __('Import', 'ppsgen'), 'tab' => 'contact_import'],
          ['title' => __('Files', 'ppsgen'), 'tab' => 'files_list'],
          ['title' => __('Files', 'ppsgen'), 'tab' => 'dashboard_files_pro', 'pro' => true, 'hide' => $isPro],
        ],
      ],
      'campaigns' => [
        'title' => __('Campaigns', 'ppsgen'),
        'tabs' => ['eca_overview'],
        'template_type' => 'email_template',
        'items' => [
          ['title' => __('Newsletters', 'ppsgen'), 'tab' => 'eca_newsletters'],
          ['title' => __('Automations', 'ppsgen'), 'tab' => 'eca_chains', 'tabs' => ['eca_chain_edit', 'eca_flows']],
          ['title' => __('Email templates', 'ppsgen'), 'tab' => 'eca_templates', 'tabs' => ['eca_template_edit']],
        ],
      ],
      'reports' => [
        'title' => __('Reports', 'ppsgen'),
        'items' => [
          ['title' => __('Popups', 'ppsgen'), 'tab' => 'report_popups', 'hide' => !$this->getFrame()->getModule('popup')],
          ['title' => __('Forms', 'ppsgen'), 'tab' => 'report_forms', 'hide' => !$this->getFrame()->getModule('form')],
          ['title' => __('Email activity', 'ppsgen'), 'tab' => 'eca_step_events'],
          ['title' => __('Conversions', 'ppsgen'), 'tab' => 'eca_conversions'],
        ],
      ],
      'settings' => [
        'title' => __('Settings', 'ppsgen'),
        'layout' => 'side',
        // The Settings link opens Email sending, even though Account is listed first
        'entry' => 'dashboard_mail_configs',
        'items' => [
          ['group' => __('Account', 'ppsgen')],
          ['title' => __('Licence & PRO', 'ppsgen'), 'tab' => 'license_overview'],
          ['title' => __('Licence & PRO', 'ppsgen'), 'tab' => 'settings_pro', 'hide' => $isPro],
          ['group' => __('Setup', 'ppsgen')],
          ['title' => __('Email sending', 'ppsgen'), 'tab' => 'dashboard_mail_configs', 'tabs' => ['dashboard_mail_config_edit']],
          ['title' => __('Features', 'ppsgen'), 'tab' => 'dashboard_modules'],
          ['title' => __('General', 'ppsgen'), 'tab' => 'dashboard_options', 'hide' => !$isLegacyUser],
          ['group' => __('Defaults for new items', 'ppsgen')],
          ['title' => __('Contacts', 'ppsgen'), 'tab' => 'contact_options'],
          ['title' => __('Forms', 'ppsgen'), 'tab' => 'form_options'],
          ['title' => __('Popups', 'ppsgen'), 'tab' => 'popup_options'],
          ['title' => __('Campaigns', 'ppsgen'), 'tab' => 'eca_options'],
          ['group' => __('Advanced', 'ppsgen')],
          // Flow logs belong to campaigns (each campaign lists its contacts and their flow), so they are not in Settings
          ['title' => __('Logs & scheduled tasks', 'ppsgen'), 'tab' => 'eca_flows_logs', 'hide' => true],
          ['title' => __('Usage data', 'ppsgen'), 'tab' => 'settings_usage'],
          // Shown on its own as a button at the very bottom of the menu
          ['title' => __('Classic PopUp', 'ppsgen'), 'tab' => 'settings_classic', 'hide' => !$isLegacyUser, 'button' => 'dashicons-backup'],
        ],
      ],
    ];
    $sections = apply_filters('ppsgen_admin_sections', $sections);

    // Drop items whose page is not registered (module switched off, PRO missing) and sections left empty
    $pages = apply_filters('ppsgen_module_pages', []);
    $adminBase = admin_url('admin.php?page=ppsgen');
    foreach ($sections as $key => $section) {
      $items = [];
      foreach ($section['items'] ?? [] as $item) {
        if (!empty($item['group'])) {
          $items[] = $item;
          continue;
        }
        if (!empty($item['hide']) || empty($pages[$item['tab']])) {
          continue;
        }
        $item['url'] = add_query_arg('tab', $item['tab'], $adminBase);
        $items[] = $item;
      }
      // A group label is kept only when at least one item follows it
      $visibleItems = [];
      foreach ($items as $index => $item) {
        if (!empty($item['group']) && (empty($items[$index + 1]) || !empty($items[$index + 1]['group']))) {
          continue;
        }
        $visibleItems[] = $item;
      }
      $tabs = array_values(array_filter($section['tabs'] ?? [], function ($tab) use ($pages) {
        return !empty($pages[$tab]);
      }));
      $entryTab = '';
      foreach ($visibleItems as $item) {
        if (!empty($section['entry']) && empty($item['group']) && $item['tab'] === $section['entry']) {
          $entryTab = $item['tab'];
          break;
        }
      }
      foreach ($entryTab === '' ? $visibleItems : [] as $item) {
        if (empty($item['group'])) {
          $entryTab = $item['tab'];
          break;
        }
      }
      if ($entryTab === '' && !empty($tabs)) {
        $entryTab = $tabs[0];
      }
      if ($entryTab === '') {
        unset($sections[$key]);
        continue;
      }
      $sections[$key]['items'] = $visibleItems;
      $sections[$key]['tabs'] = $tabs;
      $sections[$key]['url'] = add_query_arg('tab', $entryTab, $adminBase);
    }
    return $sections;
  }

  // Which top bar section (and which sub-tab) a plugin tab belongs to
  public function getAdminSectionForTab(string $tab, array $sections): array
  {
    $templateType = '';
    if ($tab === 'template_add') {
      $templateType = sanitize_key((string) $this->getFrame()->core('request')->getVar('template_type'));
    }
    // A newsletter is edited in the campaign editor but belongs under Newsletters
    if ($tab === 'eca_chain_edit' && !empty($sections['campaigns'])) {
      $eca = $this->getFrame()->getModule('eca');
      $chainId = (int) $this->getFrame()->core('request')->getVar('id');
      if ($eca && method_exists($eca->getModel(), 'isNewsletter') && $eca->getModel()->isNewsletter($chainId)) {
        return ['section' => 'campaigns', 'item' => 'eca_newsletters'];
      }
    }
    foreach ($sections as $key => $section) {
      if ($templateType !== '' && ($section['template_type'] ?? '') === $templateType) {
        return ['section' => $key, 'item' => ''];
      }
      foreach ($section['items'] ?? [] as $item) {
        if (empty($item['group']) && ($item['tab'] === $tab || in_array($tab, $item['tabs'] ?? [], true))) {
          return ['section' => $key, 'item' => $item['tab']];
        }
      }
      if (in_array($tab, $section['tabs'] ?? [], true)) {
        return ['section' => $key, 'item' => ''];
      }
    }
    return ['section' => '', 'item' => ''];
  }

  public function renderPluginPage()
  {
    $urlParams = !empty($this->getFrame()->core('request')->getServerVar('REQUEST_URI')) ? wp_parse_url($this->getFrame()->core('request')->getServerVar('REQUEST_URI')) : [];
    parse_str($urlParams['query'], $urlParams);
    $tab = !empty($urlParams['tab']) ? $urlParams['tab'] : 'dashboard';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->getFrame()->core('helper')->wpKses($this->_renderPluginPage($tab, $urlParams));
  }

  public function _renderPluginPage($tab, $urlParams)
  {
    $tabToModule = $this->getFrame()->core('moduleManager')->getTabToModuleMap();
    if (!empty($tabToModule[$tab])) {
      $this->getFrame()->getModule($tabToModule[$tab]);
    }
    $pages = apply_filters('ppsgen_module_pages', []);
    if (!empty($pages[$tab]) && is_callable($pages[$tab])) {
      $content = call_user_func($pages[$tab], $urlParams);
    } else {
      $content = '<h2>Module page not found for tab: ' . esc_html($tab) . '</h2>';
    }
    $html = $this->getView()->renderDashboardLayout([
      'tab' => $tab,
      'content' => $content,
    ]);
    return $html;
  }

  public function addAjaxSettings()
  {
    $tab = $this->getFrame()->isPluginAdminPage();
    $pluginAssetsUrl = PPSGEN_PLUGIN_URL . 'public/assets/';
    if ($tab) {
      wp_localize_script('ppsgen-backend', 'ppsgenAjax', [
        'url' => admin_url('admin-ajax.php'),
        'admin_url' => admin_url('admin.php'),
        'prefix' => 'ppsgen',
        'nonce' => wp_create_nonce('ppsgenAjaxNonce'),
        'maxlength' => max(10, (int) ini_get('max_input_vars')),
        'confirmDeleteText' => esc_html__('Are you sure you want to delete this object? This action cannot be undone.', 'ppsgen'),
        'confirmResetText' => esc_html__('Are you sure you want to reset this statistics data? This action cannot be undone.', 'ppsgen'),
        'confirmVerifyText' => esc_html__('Are you sure you want to verify this contact?', 'ppsgen'),
        'confirmVerifySubmitText' => esc_html__('Are you sure you want to verify this submit?', 'ppsgen'),
        'confirmVerifySubmitRewrite' => esc_html__('Would you like to overwrite the Contact’s field values from this submit?', 'ppsgen'),
        'links' => [
          'icon_list_url' => $pluginAssetsUrl . 'libs/ppsgensvg/',
          'add_form_template' => admin_url('admin.php?page=ppsgen&tab=template_add&template_type=form'),
          'add_popup_template' => admin_url('admin.php?page=ppsgen&tab=template_add&template_type=popup'),
          'add_email_template' => admin_url('admin.php?page=ppsgen&tab=template_add&template_type=email_template'),
        ],
        'background_assets' => function_exists('ppsgen_get_background_asset_choices') ? ppsgen_get_background_asset_choices() : [],
        'notice_statuses' => [
          'success' => [
            'edit' => esc_html__('Changes saved successfully.', 'ppsgen'),
            'delete' => esc_html__('Delete successful.', 'ppsgen'),
            'default' => esc_html__('Operation completed successfully.', 'ppsgen'),
          ],
          'error' => [
            'edit' => esc_html__('Error saving changes.', 'ppsgen'),
            'delete' => esc_html__('Error deleting.', 'ppsgen'),
            'default' => esc_html__('An error occurred.', 'ppsgen'),
          ],
          'warning' => [
            'edit' => esc_html__('Warning: changes not saved.', 'ppsgen'),
            'delete' => esc_html__('Warning: deletion not completed.', 'ppsgen'),
            'default' => esc_html__('Warning', 'ppsgen'),
          ],
        ],
        'cfvNotices' => [
          'required' => esc_html__('This field is required.', 'ppsgen'),
        ],
        'cfvInputMaskDef' => $this->getFrame()->core('component')->getInputMaskDef(),
        'legacy' => [
          'is_user' => function_exists('ppsgen_is_legacy_user') && ppsgen_is_legacy_user(),
          'is_active' => function_exists('ppsgen_is_legacy_active') && ppsgen_is_legacy_active(),
          'admin_url' => admin_url('admin.php?page=popup-wp-supsystic'),
        ],
      ]);
      wp_localize_script('ppsgen-backend-dashboard-tour', 'ppsgenTourData', $this->getDashboardTourData());
    }
  }

  protected function getDashboardTourData(): array
  {
    $adminBase = admin_url('admin.php?page=ppsgen');
    $makeUrl = static function (string $tab, array $extra = []) use ($adminBase): string {
      return add_query_arg(array_merge(['tab' => $tab], $extra), $adminBase);
    };
    $step = static function (string $tab, string $selector, string $title, string $text, string $placement = 'bottom', array $extra = []) use ($makeUrl): array {
      return array_merge([
        'tab' => $tab,
        'url' => $makeUrl($tab),
        'selector' => $selector,
        'title' => $title,
        'text' => $text,
        'placement' => $placement,
      ], $extra);
    };
    $editStep = static function (string $tab, string $selector, string $title, string $text, string $placement = 'bottom', array $extra = []): array {
      return array_merge([
        'tab' => $tab,
        'selector' => $selector,
        'title' => $title,
        'text' => $text,
        'placement' => $placement,
      ], $extra);
    };

    return [
      'default_tour_id' => 'dashboard',
      'active_tour_key' => 'ppsgen_active_tour_id',
      'active_step_key' => 'ppsgen_active_tour_step',
      'seen_prefix' => 'ppsgen_tour_seen_',
      'auto_start' => false,
      'auto_tour_map' => [
        'popup_edit' => 'popup_edit',
        'form_edit' => 'form_edit',
        'contact_edit' => 'contact_edit',
        'contact_import' => 'contact_import',
        'eca_template_edit' => 'eca_template_edit',
        'eca_chain_edit' => 'eca_chain_edit',
      ],
      'start_url' => $makeUrl('dashboard', ['ppsgen_tour' => '1', 'ppsgen_tour_step' => '0']),
      'finish_text' => __('Finish', 'ppsgen'),
      'next_text' => __('Next', 'ppsgen'),
      'prev_text' => __('Prev', 'ppsgen'),
      'close_text' => __('Close tutorial', 'ppsgen'),
      'tours' => [
        'dashboard' => [
          'start_url' => $makeUrl('dashboard'),
          'steps' => [
            $step('dashboard', '.ppsgenOverviewHero', __('Start from the new marketing workspace', 'ppsgen'), __('This dashboard explains the new Customer Engagement System: popups, forms, CRM contacts, templates, mail configs, and email campaigns now work together from one admin panel.', 'ppsgen')),
            $step('dashboard', '.ppsgenOverviewFeatureGrid', __('Four modules, one flow', 'ppsgen'), __('A visitor sees a popup, submits a form, becomes a CRM contact, and can enter an email campaign. The new plugin is designed around that complete conversion path.', 'ppsgen'), 'top'),
            $step('dashboard', '.ppsgenDashNavigationWrapper nav', __('Use the left navigation to move through the workflow', 'ppsgen'), __('The navigation exposes active modules and their subpages. Legacy users can still manage the old admin from Dashboard Options while building new flows here.', 'ppsgen'), 'right'),
            $step('dashboard_modules', '.ppsgenModulesListWrapper', __('Enable only the modules you need', 'ppsgen'), __('Modules keep the toolkit lightweight. Activate CRM, forms, popups, email campaigns, files, and PRO extensions as your project needs them.', 'ppsgen'), 'top'),
            $step('template_add', '.ppsgenTemplateGrid', __('Start from templates when you want speed', 'ppsgen'), __('Templates give users a ready structure for popups, forms, and emails. Choose a layout, name it, and then customize it in the builder.', 'ppsgen'), 'top', ['url' => $makeUrl('template_add', ['template_type' => 'popup'])]),
            $step('contact_list', '.ppsgenModuleContactTableList, .ppsgenBulkAssignTagsScope', __('Contacts are the center of the system', 'ppsgen'), __('The CRM stores leads, tags, custom fields, verification status, imports, exports, and engagement activity so follow-up can be based on real contact data.', 'ppsgen'), 'top'),
            $step('form_list', '.ppsgenModuleFormTableList, .ppsgenTableListTopButtons', __('Forms collect structured data', 'ppsgen'), __('Forms can create or update contacts, collect custom fields, show validation, use templates, and connect submits directly to CRM workflows.', 'ppsgen'), 'top'),
            $step('popup_list', '.ppsgenModulePopupTableList, .ppsgenTableListTopButtons', __('Popups become conversion entry points', 'ppsgen'), __('The popup builder includes responsive design, targeting controls, form blocks, statistics, custom code, and reusable templates for campaign launches.', 'ppsgen'), 'top'),
            $step('eca_chains', '.ppsgenModulechainTableList, .ppsgenTableListTopButtons', __('Email campaigns continue the conversation', 'ppsgen'), __('Campaign chains, flows, waits, logs, opens, clicks, and templates let the plugin follow up after a submit without leaving WordPress.', 'ppsgen'), 'top'),
            $step('dashboard_mail_configs', '.ppsgenOptionsWrapper, .ppsgenModuleTableList, .ppsgenModulePageTitleWrapper', __('Mail configs control delivery identities', 'ppsgen'), __('SMTP / IMAP configs let users manage multiple mail identities, provider settings, daily limits, and reply handling for campaigns and notifications.', 'ppsgen'), 'top'),
          ],
        ],
        'popup_module' => [
          'start_url' => $makeUrl('popup_overview'),
          'steps' => [
            $step('popup_overview', '.overviewContentStyle', __('Popup module overview', 'ppsgen'), __('Popups are the visible campaign entry point: they can show offers, embed forms, collect clicks, and send visitors into the CRM and email workflow.', 'ppsgen')),
            $step('popup_list', '.ppsgenTableListTopButtons', __('Create and manage popups', 'ppsgen'), __('Use Add to start from a popup template, duplicate proven popups, delete old variants, and keep the list clean while testing campaigns.', 'ppsgen'), 'bottom'),
            $step('popup_list', '.ppsgenModulePopupTableList', __('Read popup performance quickly', 'ppsgen'), __('The list shows status, dates, views, unique views, CTR, actions, submits, bounce rate, and average time on screen so you can spot weak popups fast.', 'ppsgen'), 'top'),
            $step('template_add', '.ppsgenTemplateGrid', __('Pick a popup template', 'ppsgen'), __('Choose a ready popup layout, use the preview magnifier to inspect details, name it, and then open the builder for final design and targeting.', 'ppsgen'), 'top', ['url' => $makeUrl('template_add', ['template_type' => 'popup'])]),
            $step('popup_edit', '.ppsgenModulePageTitleWrapper', __('The popup editor is the main workspace', 'ppsgen'), __('Here you edit content, design, display logic, statistics, and PRO custom code. First-time popup edit pages also launch the detailed builder tutorial automatically.', 'ppsgen'), 'bottom'),
          ],
        ],
        'popup_edit' => [
          'steps' => [
            $editStep('popup_edit', '.ppsgenModulePageTitleWrapper', __('Popup title, shortcode, preview, and save', 'ppsgen'), __('The header shows the popup name and shortcode when the popup is saved. Use Load Preview to render the current state and Save before placing it on the site.', 'ppsgen')),
            $editStep('popup_edit', '.ppsgenObjEditTabsButtons', __('Work through the popup tabs', 'ppsgen'), __('Template controls the popup content, Design controls visual styling, Options controls triggers and targeting, Stats shows performance, and PRO tabs add custom CSS/JS.', 'ppsgen'), 'bottom'),
            $editStep('popup_edit', '.ppsgenObjEditTabButton[data-tab="template"]', __('Build popup content from elements', 'ppsgen'), __('The Template tab is where rows, columns, titles, text, buttons, icons, dividers, iframes, and embedded Supsystic Forms are added to the popup.', 'ppsgen'), 'bottom', ['inner_tab' => 'template']),
            $editStep('popup_edit', '.ppsgenCfbAddFieldWrapper', __('Choose an element and add it', 'ppsgen'), __('Select an element type, add it to the popup, then drag it into the needed row or column. Rows and columns create the layout structure.', 'ppsgen'), 'right', ['inner_tab' => 'template']),
            $editStep('popup_edit', '.ppsgenCfbFieldsWrapper', __('Arrange the layout visually', 'ppsgen'), __('Drag elements to reorder them. Click any element to open its settings in the right panel, including spacing, alignment, visibility, typography, and links.', 'ppsgen'), 'top', ['inner_tab' => 'template']),
            $editStep('popup_edit', '.ppsgenCfbFieldsWrapperSizeChanger', __('Tune desktop, tablet, and mobile rules', 'ppsgen'), __('Use the screen buttons to preview and edit responsive column sizes. The eye toggle exposes field visibility rules by screen size.', 'ppsgen'), 'right', ['inner_tab' => 'template']),
            $editStep('popup_edit', '.ppsgenObjEditTabButton[data-tab="design"]', __('Design the overlay and popup container', 'ppsgen'), __('The Design tab controls overlay color or image, popup background, borders, size, positioning, animation, Google fonts, and scrollable popup styling.', 'ppsgen'), 'bottom', ['inner_tab' => 'design']),
            $editStep('popup_edit', '.ppsgenObjEditTabButton[data-tab="options"]', __('Set when and where the popup appears', 'ppsgen'), __('Options define the trigger: page load, click, scroll, exit intent, bottom of page, and PRO behavioral conditions. This is where targeting and frequency rules live.', 'ppsgen'), 'bottom', ['inner_tab' => 'options']),
            $editStep('popup_edit', '.ppsgenObjEditTabButton[data-tab="stats"]', __('Use stats to improve conversion', 'ppsgen'), __('After the popup runs, the Stats tab helps compare views, actions, submits, bounce, and engagement over time.', 'ppsgen'), 'bottom', ['inner_tab' => 'stats']),
            $editStep('popup_edit', '.ppsgenLoadPreview, .ppsgenObjEditSaveBtn', __('Preview, then save', 'ppsgen'), __('Load Preview is for visual checking before publishing. Save writes the current tab settings, content order, custom code, and design options to the database.', 'ppsgen'), 'left'),
          ],
        ],
        'form_module' => [
          'start_url' => $makeUrl('form_overview'),
          'steps' => [
            $step('form_overview', '.overviewContentStyle', __('Form module overview', 'ppsgen'), __('Forms collect structured visitor data and can create or update CRM contacts, save submits, verify leads, and trigger email campaign entry.', 'ppsgen')),
            $step('form_list', '.ppsgenTableListTopButtons', __('Create, duplicate, and remove forms', 'ppsgen'), __('Use Add to start from a template, duplicate forms for A/B variants, and delete obsolete forms when a funnel changes.', 'ppsgen')),
            $step('form_list', '.ppsgenModuleFormTableList', __('Review form performance', 'ppsgen'), __('The form list includes views, submits, success count, errors, CTR, and success per view, which helps find forms that need design or validation changes.', 'ppsgen'), 'top'),
            $step('template_add', '.ppsgenTemplateGrid', __('Start from a form template', 'ppsgen'), __('Templates create the initial field structure quickly. After installation, open the form editor to connect CRM fields and final options.', 'ppsgen'), 'top', ['url' => $makeUrl('template_add', ['template_type' => 'form'])]),
            $step('form_edit', '.ppsgenModulePageTitleWrapper', __('Open the detailed form builder', 'ppsgen'), __('The form editor tutorial starts automatically on the first visit and explains fields, design, form logic, submits, preview, and saving.', 'ppsgen')),
          ],
        ],
        'form_edit' => [
          'steps' => [
            $editStep('form_edit', '.ppsgenModulePageTitleWrapper', __('Form title, shortcode, preview, and save', 'ppsgen'), __('The header gives the shortcode after saving. Load Preview renders the current form, and Save stores fields, design, options, and custom code.', 'ppsgen')),
            $editStep('form_edit', '.ppsgenObjEditTabsButtons', __('The form editor has four core tabs', 'ppsgen'), __('Fields builds the form, Design styles it, Options controls logic and actions, and Submits stores responses for contact-related forms.', 'ppsgen')),
            $editStep('form_edit', '.ppsgenCfbAddContactFieldList, .ppsgenCfbAddContactFieldBtn', __('Add CRM contact fields', 'ppsgen'), __('Contact fields come from the Contacts module. Adding them to the form decides what customer data this form collects or updates.', 'ppsgen'), 'right', ['inner_tab' => 'fields']),
            $editStep('form_edit', '.ppsgenCfbAddOtherFieldList, .ppsgenCfbAddOtherFieldBtn', __('Add special form elements', 'ppsgen'), __('Special fields add submit buttons, captcha, titles, descriptions, dividers, and layout helpers that are not stored as CRM data.', 'ppsgen'), 'right', ['inner_tab' => 'fields']),
            $editStep('form_edit', '.ppsgenCfbFieldsWrapper', __('Order fields and choose field settings', 'ppsgen'), __('Drag fields to change order. Click a field to edit label, placeholder, validation, default values, styles, visibility, and PRO field behavior.', 'ppsgen'), 'top', ['inner_tab' => 'fields']),
            $editStep('form_edit', '.ppsgenCfbFieldsWrapperSizeChanger', __('Check responsive columns', 'ppsgen'), __('Desktop, tablet, and mobile controls let one form keep a different column layout on each screen size without separate forms.', 'ppsgen'), 'right', ['inner_tab' => 'fields']),
            $editStep('form_edit', '.ppsgenObjEditTabButton[data-tab="design"]', __('Style the form globally', 'ppsgen'), __('Design controls the form container, background, borders, spacing, typography, Google fonts, and default field appearance.', 'ppsgen'), 'bottom', ['inner_tab' => 'design']),
            $editStep('form_edit', '.ppsgenObjEditTabButton[data-tab="options"]', __('Choose what the form does after submit', 'ppsgen'), __('Options define the form type, restrictions, success behavior, contact creation or identification, tags, submit saving, notifications, and campaign triggers.', 'ppsgen'), 'bottom', ['inner_tab' => 'options']),
            $editStep('form_edit', '.ppsgenObjEditTabButton[data-tab="submits"]', __('Review saved submits', 'ppsgen'), __('The Submits tab is available for saved contact-related forms and helps verify, inspect, and connect submissions to CRM contacts.', 'ppsgen'), 'bottom', ['inner_tab' => 'submits']),
            $editStep('form_edit', '.ppsgenLoadPreview, .ppsgenObjEditSaveBtn', __('Preview before placing the shortcode', 'ppsgen'), __('Preview checks field layout and custom styling. Save first, then place the shortcode in a page, popup, widget, or builder block.', 'ppsgen'), 'left'),
          ],
        ],
        'contact_module' => [
          'start_url' => $makeUrl('contact_overview'),
          'steps' => [
            $step('contact_overview', '.overviewContentStyle', __('Contacts module overview', 'ppsgen'), __('Contacts are the CRM layer: fields, tags, verification, activity, submits, campaign state, imports, exports, and engagement are kept together.', 'ppsgen')),
            $step('contact_list', '.ppsgenTableListTopButtons', __('List actions for CRM work', 'ppsgen'), __('Add creates a contact, Export downloads selected data, and bulk tag actions let you segment contacts without opening each profile.', 'ppsgen')),
            $step('contact_list', '.ppsgenSelectFilterColumnsWrapper', __('Choose visible CRM columns', 'ppsgen'), __('Select Specific Contact Fields controls which custom fields appear in the table, so large databases stay readable.', 'ppsgen'), 'left'),
            $step('contact_list', '.ppsgenModuleContactTableList', __('Filter, sort, and open contact profiles', 'ppsgen'), __('Use filters for tags, fields, user, verification, and dates. Activity sorting brings the most recently engaged or replied contacts to the top.', 'ppsgen'), 'top'),
            $step('contact_list', '.ppsgenContactEngagementMini', __('Read engagement without opening the contact', 'ppsgen'), __('The expanded row summarizes form submits, email state, opens, clicks, active flows, WooCommerce stats, and last activity.', 'ppsgen'), 'top'),
            $step('contact_import', '.ppsgenObjEditWrapper', __('Import contacts when starting from existing data', 'ppsgen'), __('The import page can generate contacts from WordPress users, WooCommerce customers, or a CSV file with duplicate handling and tags.', 'ppsgen'), 'top'),
          ],
        ],
        'contact_edit' => [
          'steps' => [
            $editStep('contact_edit', '.ppsgenModulePageTitleWrapper', __('Contact header and save button', 'ppsgen'), __('The contact header shows verification, email, tags, engagement score, incoming email attention, and the Save button for profile changes.', 'ppsgen')),
            $editStep('contact_edit', '.ppsgenObjEditTabsButtons', __('Each contact has operational tabs', 'ppsgen'), __('Fields stores CRM values, Tags manages segments, Options controls the contact state, Stats summarizes engagement, Logs shows history, and Email Chat handles replies.', 'ppsgen')),
            $editStep('contact_edit', '.ppsgenObjEditTabButton[data-tab="contact_field"]', __('Edit CRM field values', 'ppsgen'), __('The Fields tab is the contact profile itself. These values can be filled manually, imported, submitted by forms, or used by email campaign conditions.', 'ppsgen'), 'bottom', ['inner_tab' => 'contact_field']),
            $editStep('contact_edit', '.ppsgenObjEditTabButton[data-tab="contact_tag"]', __('Assign tags for segmentation', 'ppsgen'), __('Tags are reusable labels for campaigns, form logic, popup targeting, imports, exports, and manual follow-up.', 'ppsgen'), 'bottom', ['inner_tab' => 'contact_tag']),
            $editStep('contact_edit', '.ppsgenObjEditTabButton[data-tab="email_campaigns"]', __('See related email campaigns', 'ppsgen'), __('When Campaigns are switched on, this tab shows which campaigns and flows are connected to the contact and what state they are in.', 'ppsgen'), 'bottom', ['inner_tab' => 'email_campaigns']),
            $editStep('contact_edit', '.ppsgenObjEditTabButton[data-tab="email_chat"]', __('Handle incoming and outgoing email', 'ppsgen'), __('Email Chat uses configured mail identities to reply from WordPress and keep the conversation attached to the contact timeline.', 'ppsgen'), 'bottom', ['inner_tab' => 'email_chat']),
            $editStep('contact_edit', '.ppsgenObjEditTabButton[data-tab="stats"]', __('Measure contact health', 'ppsgen'), __('Stats combine form submits, email opens, clicks, flow progress, incoming email, WooCommerce activity, and lead temperature.', 'ppsgen'), 'bottom', ['inner_tab' => 'stats']),
            $editStep('contact_edit', '.ppsgenObjEditSaveBtn', __('Save after manual CRM changes', 'ppsgen'), __('Save writes field values, tags, options, and manual changes so the rest of the toolkit can use the updated contact data.', 'ppsgen'), 'left'),
          ],
        ],
        'contact_import' => [
          'steps' => [
            $editStep('contact_import', '.ppsgenModulePageTitleWrapper', __('Import contacts from several sources', 'ppsgen'), __('Use this page when contacts already exist in WordPress, WooCommerce, or a CSV file and need to be moved into the CRM.', 'ppsgen')),
            $editStep('contact_import', '.ppsgenObjEditTabsButtons', __('Choose the import source', 'ppsgen'), __('WordPress user import maps user fields, WooCommerce import maps customer data, and CSV import reads an uploaded file.', 'ppsgen')),
            $editStep('contact_import', '#ppsgenGenerateContactSubmitForm, .ppsgenObjEditTab[data-tab="generate_contact"]', __('Map WordPress users into CRM fields', 'ppsgen'), __('Select which contact field should receive email, nickname, display name, role, and tags, then generate contacts from existing users.', 'ppsgen'), 'top', ['inner_tab' => 'generate_contact']),
            $editStep('contact_import', '.ppsgenContactAutoImportToggleBtn', __('Optional automatic WordPress user import', 'ppsgen'), __('The auto-import toggle keeps new WordPress users synchronized with the CRM using the mapping selected on this tab.', 'ppsgen'), 'top', ['inner_tab' => 'generate_contact']),
            $editStep('contact_import', '#ppsgenCsvContactSubmitForm, .ppsgenObjEditTab[data-tab="import_csv"]', __('Import from CSV', 'ppsgen'), __('CSV import supports duplicate logic and optional tags. Use the example file to keep delimiter and encoding compatible.', 'ppsgen'), 'top', ['inner_tab' => 'import_csv']),
            $editStep('contact_import', '.ppsgenCsvContactSubmitBtn', __('Run the import after mapping', 'ppsgen'), __('After choosing the file, duplicate behavior, and tags, Import Contacts creates or updates CRM rows and keeps field values connected.', 'ppsgen'), 'top', ['inner_tab' => 'import_csv']),
            $step('contact_list', '.ppsgenTableListContactExport', __('Export contacts from the list page', 'ppsgen'), __('Export is managed from Contacts List. Select contacts or apply filters, then use Export to download CRM data for backup or migration.', 'ppsgen'), 'bottom'),
          ],
        ],
        'eca_module' => [
          'start_url' => $makeUrl('eca_overview'),
          'steps' => [
            $step('eca_overview', '.overviewContentStyle', __('Email Campaigns overview', 'ppsgen'), __('Campaigns send newsletters and automated sequences based on contact data, tags, delays, conditions, reusable email templates, and queue processing.', 'ppsgen')),
            $step('eca_templates', '.ppsgenTableListTopButtons', __('Create reusable email templates', 'ppsgen'), __('Templates define the outer email layout. Campaign steps inject their own content into the required Email Step/Campaign Content element.', 'ppsgen')),
            $step('eca_templates', '.ppsgenModuleTemplateTableList', __('Manage template versions', 'ppsgen'), __('Edit, duplicate, delete, and enable templates here. Updating a linked template can regenerate related campaign step emails.', 'ppsgen'), 'top'),
            $step('eca_chains', '.ppsgenTableListTopButtons', __('Create campaign chains', 'ppsgen'), __('Campaigns are automation containers. Each campaign can contain one or more email steps, conditions, delays, and contact entry rules.', 'ppsgen')),
            $step('eca_chains', '.ppsgenModulechainTableList', __('Read campaign health from the list', 'ppsgen'), __('The chain list shows processing state, finished contacts, exits, unsubscribes, total emails, opens, clicks, and suspicious tracking signals.', 'ppsgen'), 'top'),
            $step('eca_flows', '.ppsgenTableList, .ppsgenModulePageTitleWrapper', __('Monitor active flows', 'ppsgen'), __('Flows show where each contact currently sits inside a campaign and when the next run is scheduled.', 'ppsgen'), 'top'),
            $step('eca_flows_logs', '.ppsgenTableList, .ppsgenModulePageTitleWrapper', __('Audit delivery logs', 'ppsgen'), __('Flow Logs explain what the automation did: processed steps, skipped conditions, exits, errors, and queue behavior.', 'ppsgen'), 'top'),
            $step('eca_step_events', '.ppsgenTableList, .ppsgenModulePageTitleWrapper', __('Review opens and clicks', 'ppsgen'), __('Email Events show engagement tracking, including opens, clicks, user agent, referrer, and suspicious activity signals.', 'ppsgen'), 'top'),
          ],
        ],
        'eca_template_edit' => [
          'steps' => [
            $editStep('eca_template_edit', '.ppsgenModulePageTitleWrapper', __('Template editor header', 'ppsgen'), __('Use Load Preview to render the email frame and Save to store structure, global design, and options.', 'ppsgen')),
            $editStep('eca_template_edit', '.ppsgenObjEditTabsButtons', __('Template, options, and design', 'ppsgen'), __('Template builds the email layout, Options controls template-level settings, and Design defines global email typography, spacing, and colors.', 'ppsgen')),
            $editStep('eca_template_edit', '.ppsgenCfbAddFieldWrapper', __('Add email-safe layout elements', 'ppsgen'), __('Build emails in order: Row, Column, then content elements. Email clients are stricter than browsers, so this builder keeps structure table-friendly.', 'ppsgen'), 'right', ['inner_tab' => 'template']),
            $editStep('eca_template_edit', '.ppsgenCfbFieldsWrapper', __('Keep required email placeholders', 'ppsgen'), __('Every campaign template should include Email Step/Campaign Content and Unsubscribe. The campaign step content is injected into that placeholder before sending.', 'ppsgen'), 'top', ['inner_tab' => 'template']),
            $editStep('eca_template_edit', '.ppsgenTemplateFieldsRight, .ppsgenCffOptionContainer', __('Edit selected element options', 'ppsgen'), __('Click an element to adjust width, spacing, colors, text, image source, links, and other options in the right settings panel.', 'ppsgen'), 'left', ['inner_tab' => 'template']),
            $editStep('eca_template_edit', '.ppsgenObjEditTabButton[data-tab="design"]', __('Set default email styling', 'ppsgen'), __('Design applies default font, text color, background, body width, and spacing used by the email template unless an element overrides it.', 'ppsgen'), 'bottom', ['inner_tab' => 'design']),
            $editStep('eca_template_edit', '.ppsgenLoadTemplatePreview, .ppsgenObjEditSaveBtn', __('Preview the generated email', 'ppsgen'), __('Always preview email templates after changing rows, images, links, or required elements so campaigns inherit a valid layout.', 'ppsgen'), 'left'),
          ],
        ],
        'eca_chain_edit' => [
          'steps' => [
            $editStep('eca_chain_edit', '.ppsgenModulePageTitleWrapper', __('Campaign header and delivery status', 'ppsgen'), __('The header shows the campaign, server time, next CRON runs, queue size, and mail limit status so you can diagnose delivery timing.', 'ppsgen')),
            $editStep('eca_chain_edit', '.ppsgenObjEditTabsButtons', __('Campaign tabs control the whole automation', 'ppsgen'), __('Emails & Steps edits the sequence, Options controls entry and exit rules, Contacts shows assigned contacts, and Stats summarizes performance.', 'ppsgen')),
            $editStep('eca_chain_edit', '.ppsgenChainStepsContainer', __('Campaign steps are the email timeline', 'ppsgen'), __('Each step is one email in the campaign. Select a step to edit content and conditions, drag to reorder, or add another step for a longer sequence.', 'ppsgen'), 'bottom', ['inner_tab' => 'steps']),
            $editStep('eca_chain_edit', '.ppsgenChainStepsBtns', __('Add or remove email steps', 'ppsgen'), __('Add New Step creates another email. Delete Selected removes the chosen step, so use it carefully after checking linked stats or live flows.', 'ppsgen'), 'top', ['inner_tab' => 'steps']),
            $editStep('eca_chain_edit', '.ppsgenChainStepBtns', __('Step tabs: content, options, and stats', 'ppsgen'), __('Template & Content edits the message, Options controls subject, delay, tags and false-condition behavior, and Stats shows how the step performed.', 'ppsgen'), 'bottom', ['inner_tab' => 'steps']),
            $editStep('eca_chain_edit', '.ppsgenLoadChainPreview, .ppsgenChainStepBtnSave', __('Preview and save the current step', 'ppsgen'), __('Load Preview renders the selected email using the chosen template. Save Current Step writes only the active step, not the whole campaign options tab.', 'ppsgen'), 'left', ['inner_tab' => 'steps']),
            $editStep('eca_chain_edit', '.ppsgenObjEditTabButton[data-tab="options"]', __('Define who enters and exits the campaign', 'ppsgen'), __('Campaign Options set active state, template, mail config, verification rules, required tags, forbidden tags, field conditions, and exit conditions.', 'ppsgen'), 'bottom', ['inner_tab' => 'options']),
            $editStep('eca_chain_edit', '.ppsgenChainOptionsBtnSave, .ppsgenChainOptionsBtnApplyChain', __('Save options, then apply to contacts', 'ppsgen'), __('Save Campaign Options stores the rules. Apply Campaign to Contacts scans existing contacts and starts flows for contacts that match the entry conditions.', 'ppsgen'), 'top', ['inner_tab' => 'options']),
            $editStep('eca_chain_edit', '.ppsgenObjEditTabButton[data-tab="contacts"]', __('Review contacts in this campaign', 'ppsgen'), __('The Contacts tab shows contacts currently related to this campaign so you can inspect state and activity without leaving the automation.', 'ppsgen'), 'bottom', ['inner_tab' => 'contacts']),
            $editStep('eca_chain_edit', '.ppsgenObjEditTabButton[data-tab="stats"]', __('Use campaign stats after launch', 'ppsgen'), __('Stats combines flow totals, processing state, delivery results, opens, clicks, suspicious activity, exits, and unsubscribe behavior.', 'ppsgen'), 'bottom', ['inner_tab' => 'stats']),
          ],
        ],
      ],
    ];
  }

  public function addBackendThemeBodyClass($classes)
  {
    if (!$this->getFrame()->isPluginAdminPage()) {
      return $classes;
    }
    $cookieTheme = !empty($_COOKIE['ppsgen_backend_theme']) ? sanitize_key(wp_unslash($_COOKIE['ppsgen_backend_theme'])) : '';
    if ($cookieTheme === 'dark') {
      $classes .= ' ppsgen-theme-dark';
    }
    return $classes;
  }

  public function printBackendThemeInitScript()
  {
    if (!$this->getFrame()->isPluginAdminPage()) {
      return;
    }
    ?>
    <script>
      (function () {
        var storageKey = 'ppsgen_backend_theme';
        var className = 'ppsgen-theme-dark';
        var docEl = document.documentElement;
        var cookieMatch = document.cookie.match(/(?:^|; )ppsgen_backend_theme=([^;]+)/);
        var cookieTheme = cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
        var storedTheme = '';
        try {
          storedTheme = window.localStorage.getItem(storageKey) || '';
        } catch (e) {}
        var theme = cookieTheme || storedTheme;
        if (theme === 'dark') {
          docEl.classList.add(className);
          window.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add(className);
          });
          if (!cookieTheme) {
            document.cookie = 'ppsgen_backend_theme=dark; path=/; max-age=' + (60 * 60 * 24 * 365);
          }
        }
      })();
    </script>
    <?php
  }

  public function registerModuleNavigation($data)
  {
    $new = [
      'title' => 'Overview',
      'slug' => 'dashboard',
      'icon' => 'dashicons dashicons-admin-home',
    ];
    $data[] = $new;
    $new = [
      'title' => 'Modules',
      'slug' => 'dashboard_modules',
      'icon' => 'dashicons dashicons-admin-generic',
    ];
    $data[] = $new;
    if (function_exists('ppsgen_is_legacy_user') && ppsgen_is_legacy_user()) {
      $new = [
        'title' => 'Options',
        'slug' => 'dashboard_options',
        'icon' => 'dashicons dashicons-admin-settings',
      ];
      $data[] = $new;
    }
    $new = [
      'title' => 'SMTP / IMAP',
      'slug' => 'dashboard_mail_configs',
      'icon' => 'dashicons dashicons-email-alt2',
    ];
    $data[] = $new;
    $new = [
      'title' => 'Files (PRO)',
      'slug' => 'dashboard_files_pro',
      'icon' => 'dashicons dashicons-media-archive',
    ];
    $data[] = $new;
    return $data;
  }

  public function registerModulePages($pages)
  {
    $pages['dashboard'] = [$this->getView(), 'renderDashboardHome'];
    $pages['dashboard_overview'] = [$this->getView(), 'renderDashboardOverview'];
    $pages['dashboard_modules'] = [$this->getView(), 'renderDashboardModules'];
    $pages['dashboard_options'] = [$this->getView(), 'renderDashboardOptions'];
    $pages['dashboard_mail_configs'] = [$this->getView(), 'renderDashboardMailConfigs'];
    $pages['dashboard_mail_config_edit'] = [$this->getView(), 'renderDashboardMailConfigEdit'];
    $pages['dashboard_files_pro'] = [$this->getView(), 'renderDashboardFilesPro'];
    $pages['settings_pro'] = [$this->getView(), 'renderSettingsPro'];
    $pages['settings_classic'] = [$this->getView(), 'renderSettingsClassic'];
    $pages['settings_usage'] = [$this->getView(), 'renderSettingsUsage'];
    $pages['report_popups'] = [$this->getView(), 'renderReportPopups'];
    $pages['plays'] = [$this->getView(), 'renderPlays'];
    $pages['play_setup'] = [$this->getView(), 'renderPlaySetup'];
    $pages['play_review'] = [$this->getView(), 'renderPlayReview'];
    $pages['play_summary'] = [$this->getView(), 'renderPlaySummary'];
    $pages['report_forms'] = [$this->getView(), 'renderReportForms'];
    return $pages;
  }

  public function registerModuleTabMap($tabs)
  {
    $tabs['dashboard'] = 'dashboard';
    $tabs['dashboard_overview'] = 'dashboard';
    $tabs['dashboard_modules'] = 'dashboard';
    $tabs['dashboard_options'] = 'dashboard';
    $tabs['dashboard_mail_configs'] = 'dashboard';
    $tabs['dashboard_mail_config_edit'] = 'dashboard';
    $tabs['dashboard_files_pro'] = 'dashboard';
    $tabs['settings_pro'] = 'dashboard';
    $tabs['settings_classic'] = 'dashboard';
    $tabs['settings_usage'] = 'dashboard';
    $tabs['report_popups'] = 'dashboard';
    $tabs['plays'] = 'dashboard';
    $tabs['play_setup'] = 'dashboard';
    $tabs['play_review'] = 'dashboard';
    $tabs['play_summary'] = 'dashboard';
    $tabs['report_forms'] = 'dashboard';
    return $tabs;
  }

  public function registerModulePageMeta($data)
  {
    $data['dashboard'] = [
      'page_title' => __('Home', 'ppsgen'),
      'admin_title' => __('Home', 'ppsgen'),
      'menu_title' => __('Home', 'ppsgen'),
    ];
    $data['dashboard_overview'] = [
      'page_title' => __('How CES works', 'ppsgen'),
      'admin_title' => __('How CES works', 'ppsgen'),
      'menu_title' => __('How CES works', 'ppsgen'),
    ];
    $data['dashboard_modules'] = [
      'page_title' => __('Features', 'ppsgen'),
      'admin_title' => __('Features', 'ppsgen'),
      'menu_title' => __('Features', 'ppsgen'),
    ];
    $data['dashboard_options'] = [
      'page_title' => __('General defaults', 'ppsgen'),
      'admin_title' => __('General defaults', 'ppsgen'),
      'menu_title' => __('General', 'ppsgen'),
    ];
    $data['dashboard_mail_configs'] = [
      'page_title' => __('Email sending', 'ppsgen'),
      'admin_title' => __('Email sending', 'ppsgen'),
      'menu_title' => __('Email sending', 'ppsgen'),
    ];
    $data['settings_pro'] = [
      'page_title' => __('Licence & PRO', 'ppsgen'),
      'admin_title' => __('Licence & PRO', 'ppsgen'),
      'menu_title' => __('Licence & PRO', 'ppsgen'),
    ];
    $data['plays'] = [
      'page_title' => __('Plays', 'ppsgen'),
      'admin_title' => __('Plays', 'ppsgen'),
      'menu_title' => __('Plays', 'ppsgen'),
    ];
    $data['play_setup'] = [
      'page_title' => __('Set up a play', 'ppsgen'),
      'admin_title' => __('Set up a play', 'ppsgen'),
    ];
    $data['play_review'] = [
      'page_title' => __("Review what we'll build", 'ppsgen'),
      'admin_title' => __("Review what we'll build", 'ppsgen'),
    ];
    $data['play_summary'] = [
      'page_title' => __('Play summary', 'ppsgen'),
      'admin_title' => __('Play summary', 'ppsgen'),
    ];
    $data['report_popups'] = [
      'page_title' => __('Popup report', 'ppsgen'),
      'admin_title' => __('Popup report', 'ppsgen'),
      'menu_title' => __('Popups', 'ppsgen'),
    ];
    $data['report_forms'] = [
      'page_title' => __('Form report', 'ppsgen'),
      'admin_title' => __('Form report', 'ppsgen'),
      'menu_title' => __('Forms', 'ppsgen'),
    ];
    $data['settings_usage'] = [
      'page_title' => __('Usage data', 'ppsgen'),
      'admin_title' => __('Usage data', 'ppsgen'),
      'menu_title' => __('Usage data', 'ppsgen'),
    ];
    $data['settings_classic'] = [
      'page_title' => __('Classic PopUp', 'ppsgen'),
      'admin_title' => __('Classic PopUp', 'ppsgen'),
      'menu_title' => __('Classic PopUp', 'ppsgen'),
    ];
    $data['dashboard_mail_config_edit'] = [
      'page_title' => __('Mail Config', 'ppsgen'),
      'page_title_callback' => [$this, 'getDashboardMailConfigEditPageTitle'],
      'admin_title_callback' => [$this, 'getDashboardMailConfigEditPageTitle'],
    ];
    $data['dashboard_files_pro'] = [
      'page_title' => __('Files (PRO)', 'ppsgen'),
      'admin_title' => __('Files (PRO)', 'ppsgen'),
      'menu_title' => __('Files (PRO)', 'ppsgen'),
    ];
    return $data;
  }

  // Plain words in page titles: Automation instead of Campaign/Chain, no "Flow" or "ECA"
  public function relabelModulePageMeta($data)
  {
    $labels = [
      'eca_overview' => __('How campaigns work', 'ppsgen'),
      'eca_chains' => __('Automations', 'ppsgen'),
      'eca_chain_edit' => __('Automation', 'ppsgen'),
      'eca_templates' => __('Email templates', 'ppsgen'),
      'eca_template_edit' => __('Email template', 'ppsgen'),
      'eca_flows' => __('People in automations', 'ppsgen'),
      'eca_flows_logs' => __('Logs & scheduled tasks', 'ppsgen'),
      'eca_step_events' => __('Email activity', 'ppsgen'),
      'eca_conversions' => __('Conversions', 'ppsgen'),
      'eca_options' => __('Campaign defaults', 'ppsgen'),
      'popup_options' => __('Popup defaults', 'ppsgen'),
      'form_options' => __('Form defaults', 'ppsgen'),
      'contact_options' => __('Contact defaults', 'ppsgen'),
      'contact_field' => __('Contact fields', 'ppsgen'),
      'license_overview' => __('Licence & PRO', 'ppsgen'),
    ];
    foreach ($labels as $tab => $label) {
      $meta = isset($data[$tab]) && is_array($data[$tab]) ? $data[$tab] : [];
      // Edit pages keep their dynamic title callbacks (they include the item name)
      $meta['page_title'] = $label;
      $meta['menu_title'] = $label;
      if (empty($meta['admin_title_callback'])) {
        $meta['admin_title'] = $label;
      }
      $data[$tab] = $meta;
    }
    return $data;
  }

  public function getDashboardMailConfigEditPageTitle(): string
  {
    $configId = (int) $this->getFrame()->core('request')->getVar('id');
    if ($configId > 0) {
      $mailConfig = $this->getModel()->getMailConfigById($configId);
      if (!empty($mailConfig['name'])) {
        return sprintf(
          /* translators: %s: mail config name */
          __('Edit Mail Config: %s', 'ppsgen'),
          (string) $mailConfig['name']
        );
      }
      return __('Edit Mail Config', 'ppsgen');
    }
    return __('Create Mail Config', 'ppsgen');
  }
}
