<?php

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Forms › Move from Contact Form (admin.php?page=ppsgen&tab=form_import_cf): moves the forms, emails, logic and
 * submissions of Contact Form by Supsystic into CES in one click. The entry from Contact Form's own banner links
 * here (optional ?from=cfs and ?ids=1,2 to pick forms).
 */
class CfImportSubmodulePpsgen extends BaseModulePpsgen
{
  const TAB = 'form_import_cf';

  public function init($context = 'backend')
  {
    parent::init();
    if ($context !== 'backend') {
      return;
    }
    add_filter('ppsgen_module_pages', [$this, 'registerPages']);
    add_filter('ppsgen_module_tab', [$this, 'registerTabMap']);
    add_filter('ppsgen_module_page_meta', [$this, 'registerPageMeta']);
    add_filter('ppsgen_admin_sections', [$this, 'registerSection']);
    add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
  }

  public static function getPageUrl(array $args = []): string
  {
    return add_query_arg(array_merge(['page' => 'ppsgen', 'tab' => self::TAB], $args), admin_url('admin.php'));
  }

  public function registerPages($pages)
  {
    $pages[self::TAB] = [$this->getView(), 'renderImportPage'];
    return $pages;
  }

  public function registerTabMap($tabs)
  {
    $tabs[self::TAB] = 'form';
    return $tabs;
  }

  public function registerPageMeta($data)
  {
    $data[self::TAB] = [
      'page_title' => __('Move from Contact Form', 'ppsgen'),
      'admin_title' => __('Move from Contact Form', 'ppsgen'),
      'menu_title' => __('Move from Contact Form', 'ppsgen'),
    ];
    return $data;
  }

  /** The page belongs to Forms in the CES top bar */
  public function registerSection($sections)
  {
    if (isset($sections['forms']['tabs']) && is_array($sections['forms']['tabs'])) {
      $sections['forms']['tabs'][] = self::TAB;
    }
    return $sections;
  }

  public function registerAssets()
  {
    $tab = $this->getFrame()->isPluginAdminPage();
    $url = PPSGEN_MODULES_URL . 'form/submodules/cf_import/assets/';
    if ($tab === 'form_list') {
      // Only the banner styles are needed there
      wp_enqueue_style('ppsgen-backend-cf-import', $url . 'css/backend.import.css', [], PPSGEN_VERSION);
      return;
    }
    if ($tab !== self::TAB) {
      return;
    }
    wp_enqueue_style('ppsgen-backend-cf-import', $url . 'css/backend.import.css', [], PPSGEN_VERSION);
    wp_enqueue_script('ppsgen-backend-cf-import', $url . 'js/backend.import.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_localize_script('ppsgen-backend-cf-import', 'ppsgenCfImport', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('ppsgenAjaxNonce'),
      'i18n' => $this->getView()->getJsStrings(),
    ]);
  }

  /** The banner on the CES Forms list while Contact Form has forms that were not moved */
  public function getBannerHtml(): string
  {
    if (!current_user_can('manage_options')) {
      return '';
    }
    $count = $this->getModel()->countNotMoved();
    if ($count <= 0) {
      return '';
    }
    return $this->getView()->renderBanner($count);
  }
}
