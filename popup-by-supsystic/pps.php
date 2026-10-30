<?php

/*
Plugin Name: Customer Engagement System – Popups, Forms, CRM & Automation
Plugin URI: https://supsystic.com/plugins/popup-plugin/
Description: Popups, forms, a WordPress CRM and email marketing automation in one plugin. Capture leads, tag every contact and follow up automatically.
Version: 2.0.3
Author: supsystic.com
Author URI: https://supsystic.com/
Text Domain: popup-by-supsystic
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
  exit();
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'visitor.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'usage.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'internal-api.php';
require_once PPSGEN_PLUGIN_DIR . 'core/Frame.php';

register_activation_hook(__FILE__, 'ppsgenActivateCallback');

if (!function_exists('ppsgenActivateCallback')) {
  function ppsgenActivateCallback()
  {
    ppsgen_setup_legacy_flags();
  }
}

register_deactivation_hook(__FILE__, 'ppsgenDeactivateCallback');

if (!function_exists('ppsgenDeactivateCallback')) {
  function ppsgenDeactivateCallback()
  {
    if (class_exists('FramePpsgen')) {
      do_action('ppsgen_deactivation', __FILE__);
    }
  }
}

if (!function_exists('ppsgenLoadTextdomain')) {
  function ppsgenLoadTextdomain()
  {
    load_plugin_textdomain('ppsgen', false, dirname(plugin_basename(__FILE__)) . '/languages');
  }
}

add_action('init', 'ppsgenLoadTextdomain', 1);

ppsgen_setup_legacy_flags();

if (ppsgen_should_load_legacy_runtime()) {
  require_once PPSGEN_PLUGIN_DIR . 'legacy/pps.php';
}

if (is_admin()) {
  add_action('admin_menu', 'ppsgen_hide_inactive_mode_admin_menu', 999);
  add_action('admin_notices', 'ppsgen_render_legacy_admin_switch_banner', 1);
  // Take the PRO freeze snapshot before any request can create new (Free) items
  add_action('admin_init', 'ppsgen_sync_pro_freeze_snapshot', 1);
  // Spooky Sale banner on the CES and Classic PopUp admin pages
  require_once PPSGEN_PLUGIN_DIR . 'promo' . PPSGEN_DS . 'spooky-sale.php';
  $spookySaleArgs = [
    'proUrl' => 'https://supsystic.com/plugins/wordpress-popup-plugin/',
    'isPro' => function () {
      if (ppsgen_is_pro_license_active()) {
        return true;
      }
      // Classic PopUp PRO 1.x. The Classic runtime loads PRO modules from the PRO folder even while PRO is deactivated,
      // so the PRO plugin itself has to be active too
      if (!function_exists('is_plugin_active') || !is_plugin_active('popup-by-supsystic-pro/popup-by-supsystic-pro.php')) {
        return false;
      }
      $promo = class_exists('framePps') ? framePps::_()->getModule('supsystic_promo') : null;
      return $promo && $promo->isPro();
    },
    'utmCampaign' => 'popup',
    'utmSource' => 'ces_plugin',
  ];
  // CES and Classic PopUp PRO are different products in the shop
  SupsysticSpookySale2026::register('ppsgen', PPSGEN_PLUGIN_URL . 'promo/', ['proPid' => 30155] + $spookySaleArgs);
  SupsysticSpookySale2026::register('popup-wp-supsystic', PPSGEN_PLUGIN_URL . 'promo/', ['proPid' => 365] + $spookySaleArgs);
}

add_action('init', function () {
  FramePpsgen::_init();
}, 5);

add_filter('cron_schedules', 'ppsgen_register_eca_cron_schedules');
add_action('init', 'ppsgen_repair_eca_cron_events', 20);

// Monthly summary email (Home › This month): a daily check sends last month's report once, on or after the 1st
add_action('init', function () {
  if (!wp_next_scheduled('ppsgen_monthly_report')) {
    wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'ppsgen_monthly_report');
  }
}, 20);
add_action('ppsgen_monthly_report', function () {
  $frame = class_exists('FramePpsgen') ? FramePpsgen::getInstance() : null;
  $dashboard = $frame ? $frame->getModule('dashboard') : null;
  if ($dashboard) {
    $dashboard->getModel()->maybeSendMonthlyReport();
  }
});

// Double opt-in confirmation link (?ppsgen_action=verify_submit) works on any page of the site
add_action('init', function () {
  if (is_admin() || ($_GET['ppsgen_action'] ?? '') !== 'verify_submit' || !class_exists('FramePpsgen') || !FramePpsgen::getInstance()) {
    return;
  }
  $form = FramePpsgen::getInstance()->getModule('form', 'frontend');
  if ($form) {
    $form->getModel()->verifyContactByLink();
  }
}, 20);
