<?php
// Optional anonymous usage data (Free-to-PRO section 6). Off by default and opt-in only; counts only, never names,
// emails or anything about a contact; it never locks or unlocks a feature and can be switched off at any time.
// We ask after the first play goes live and on PRO licence activation, never before the user has a result.
// Counts go to Supsystic's stats server (cestats.supsystic.com) once a week; the ppsgen_usage_tracking_endpoint filter can
// point them elsewhere or switch sending off ('').
if (!defined('ABSPATH')) {
  exit();
}

if (!defined('PPSGEN_USAGE_ENDPOINT')) {
  define('PPSGEN_USAGE_ENDPOINT', 'https://cestats.supsystic.com/collect.php');
}

if (!defined('PPSGEN_USAGE_OPTION')) {
  define('PPSGEN_USAGE_OPTION', 'ppsgen_usage_tracking');
}

// 'yes', 'no', or '' when the user has not been asked yet
if (!function_exists('ppsgen_usage_tracking_choice')) {
  function ppsgen_usage_tracking_choice(): string
  {
    $choice = (string) get_option(PPSGEN_USAGE_OPTION, '');
    return in_array($choice, ['yes', 'no'], true) ? $choice : '';
  }
}

if (!function_exists('ppsgen_usage_tracking_enabled')) {
  function ppsgen_usage_tracking_enabled(): bool
  {
    return ppsgen_usage_tracking_choice() === 'yes';
  }
}

if (!function_exists('ppsgen_set_usage_tracking')) {
  function ppsgen_set_usage_tracking(bool $enabled): void
  {
    update_option(PPSGEN_USAGE_OPTION, $enabled ? 'yes' : 'no', false);
    if ($enabled && !wp_next_scheduled('ppsgen_usage_tracking_weekly')) {
      wp_schedule_event(time() + HOUR_IN_SECONDS, 'weekly', 'ppsgen_usage_tracking_weekly');
    }
    if (!$enabled) {
      wp_clear_scheduled_hook('ppsgen_usage_tracking_weekly');
      delete_option('ppsgen_usage_gateways');
    }
  }
}

// Gateways seen and clicked (e.g. "second_automation", "contact_limit"), counted only while sharing is on
if (!function_exists('ppsgen_track_gateway')) {
  function ppsgen_track_gateway(string $key, string $event = 'seen'): void
  {
    if (!ppsgen_usage_tracking_enabled() || !in_array($event, ['seen', 'clicked'], true)) {
      return;
    }
    $key = sanitize_key($key);
    $counts = get_option('ppsgen_usage_gateways', []);
    $counts = is_array($counts) ? $counts : [];
    $counts[$key][$event] = (int) ($counts[$key][$event] ?? 0) + 1;
    update_option('ppsgen_usage_gateways', $counts, false);
  }
}

// Everything that would be shared, as plain counts
if (!function_exists('ppsgen_collect_usage_snapshot')) {
  function ppsgen_collect_usage_snapshot(): array
  {
    global $wpdb, $wp_version;
    $p = $wpdb->prefix . 'ppsgen_';
    $tableExists = function (string $table) use ($wpdb, $p): bool {
      return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $p . $table)) === $p . $table;
    };
    $count = function (string $table, string $where = '1 = 1') use ($wpdb, $p, $tableExists): int {
      return $tableExists($table) ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}{$table} WHERE {$where}") : 0;
    };
    $siteId = (string) get_option('ppsgen_usage_site_id', '');
    if ($siteId === '') {
      $siteId = wp_generate_uuid4();
      update_option('ppsgen_usage_site_id', $siteId, false);
    }
    $weekAgo = gmdate('Y-m-d H:i:s', current_time('timestamp') - WEEK_IN_SECONDS);
    $monthAgo = gmdate('Y-m-d H:i:s', current_time('timestamp') - 30 * DAY_IN_SECONDS);
    $frame = class_exists('FramePpsgen') ? FramePpsgen::getInstance() : null;

    $automations = ['total' => 0, 'running' => 0];
    $newsletters = 0;
    if ($frame && $frame->getModule('eca')) {
      $summary = $frame->getModule('eca')->getModel()->getAutomationLimitSummary();
      $automations = ['total' => (int) $summary['total'], 'running' => (int) $summary['running']];
      $newsletters = count((array) $frame->getModule('eca')->getModel()->getNewsletterIds());
    }
    $plays = ['started' => 0, 'live' => 0, 'types' => []];
    if ($frame && $frame->getModule('dashboard')) {
      foreach ($frame->getModule('dashboard')->getPlays()->getPlays() as $play) {
        $plays['started']++;
        $plays['live'] += ($play['status'] ?? '') === 'live' ? 1 : 0;
        $type = sanitize_key((string) ($play['type'] ?? ''));
        $plays['types'][$type] = (int) ($plays['types'][$type] ?? 0) + 1;
      }
    }
    $popupViews = 0;
    $popupConversions = 0;
    if ($frame && $frame->getModule('popup') && $tableExists('popups')) {
      $ids = array_map('intval', (array) $wpdb->get_col("SELECT id FROM {$p}popups"));
      foreach ($ids ? $frame->getModule('popup')->getModel()->getPopupStatsSummaryByIds($ids) : [] as $summary) {
        $popupViews += (int) ($summary['unique_views'] ?? 0);
        $popupConversions += (int) ($summary['actions'] ?? 0) + (int) ($summary['form_submits'] ?? 0);
      }
    }
    $limit = function_exists('ppsgen_get_contact_limit_status') ? ppsgen_get_contact_limit_status() : [];
    $gateways = get_option('ppsgen_usage_gateways', []);
    return [
      'schema' => 1,
      'site' => $siteId,
      'plugin_version' => defined('PPSGEN_VERSION') ? PPSGEN_VERSION : '',
      'wp_version' => (string) $wp_version,
      'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
      'plan' => function_exists('ppsgen_get_pro_license_state') ? ppsgen_get_pro_license_state() : 'none',
      'contacts' => $count('contacts', 'deleted_at IS NULL'),
      'contacts_new_7_days' => $count('contacts', $wpdb->prepare('deleted_at IS NULL AND created_at >= %s', $weekAgo)),
      'contacts_over_free_limit' => (int) ($limit['over'] ?? 0),
      'automations' => $automations['total'],
      'automations_running' => $automations['running'],
      'newsletters' => $newsletters,
      'plays_started' => $plays['started'],
      'plays_live' => $plays['live'],
      'plays_by_type' => $plays['types'],
      'popups' => $count('popups'),
      'popups_live' => $count('popups', 'active = 1'),
      'popup_unique_views' => $popupViews,
      'popup_conversion_percent' => $popupViews > 0 ? round($popupConversions / $popupViews * 100, 1) : 0,
      'forms' => $count('forms'),
      'form_submissions_30_days' => $count('form_submits', $wpdb->prepare('created_date >= %s', $monthAgo)),
      'gateways' => is_array($gateways) ? $gateways : [],
    ];
  }
}

if (!function_exists('ppsgen_send_usage_snapshot')) {
  function ppsgen_send_usage_snapshot(): bool
  {
    if (!ppsgen_usage_tracking_enabled()) {
      return false;
    }
    $endpoint = (string) apply_filters('ppsgen_usage_tracking_endpoint', PPSGEN_USAGE_ENDPOINT);
    if ($endpoint === '' || !wp_http_validate_url($endpoint)) {
      return false;
    }
    $response = wp_remote_post($endpoint, [
      'timeout' => 10,
      'headers' => ['Content-Type' => 'application/json'],
      'body' => wp_json_encode(ppsgen_collect_usage_snapshot()),
    ]);
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
      return false;
    }
    update_option('ppsgen_usage_last_sent', current_time('timestamp'), false);
    return true;
  }
}
add_action('ppsgen_usage_tracking_weekly', 'ppsgen_send_usage_snapshot');
