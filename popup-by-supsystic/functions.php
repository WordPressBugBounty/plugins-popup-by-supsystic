<?php

if (!defined('ABSPATH')) {
  exit();
}

define('PPSGEN_VERSION', '2.0.2');
define('PPSGEN_DS', DIRECTORY_SEPARATOR);
define('PPSGEN_PLUGIN_NAME', 'ppsgen');
define('PPSGEN_PLUGIN_SHORTNAME', 'ppsgen');
define('PPSGEN_PLUGIN_DEBUG', false);
define('PPSGEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PPSGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PPSGEN_MODULES_URL', PPSGEN_PLUGIN_URL . 'modules' . '/');
define('PPSGEN_LEGACY_USER_OPTION', 'ppsgen_is_legacy_user');
define('PPSGEN_LEGACY_ACTIVE_OPTION', 'ppsgen_is_legacy_active');
define('PPSGEN_LEGACY_SWITCH_DISMISSED_OPTION', 'ppsgen_legacy_switch_dismissed');
define('PPSGEN_LEGACY_UPGRADE_MODAL_DISMISSED_META', 'ppsgen_legacy_upgrade_modal_dismissed_until');

if (!function_exists('ppsgen_esc_attr')) {
  function ppsgen_esc_attr($string)
  {
    echo esc_attr($string);
  }
}

if (!function_exists('ppsgenlog')) {
  function ppsgenlog($string)
  {
    if (!empty(PPSGEN_PLUGIN_DEBUG)) {
      ob_start();
      var_dump($string);
      error_log(ob_get_clean());
    }
  }
}

if (!function_exists('ppsgen_sanitize_eca_tracking_query_prefix')) {
  function ppsgen_sanitize_eca_tracking_query_prefix($prefix): string
  {
    $prefix = strtolower((string) $prefix);
    $prefix = preg_replace('/[^a-z0-9_]/', '', $prefix);
    $prefix = trim((string) $prefix, '_');
    if ($prefix === '' || in_array($prefix, ['ppsgen', 'eca', 'ppsgen_eca'], true)) {
      return '';
    }
    if (!preg_match('/^[a-z]/', $prefix)) {
      $prefix = 'p' . $prefix;
    }
    return substr($prefix, 0, 24);
  }
}

if (!function_exists('ppsgen_get_default_eca_tracking_query_prefix')) {
  function ppsgen_get_default_eca_tracking_query_prefix(): string
  {
    return 'x' . substr(hash('sha256', home_url('/') . '|' . wp_salt('auth')), 0, 10);
  }
}

if (!function_exists('ppsgen_get_eca_tracking_query_prefix')) {
  function ppsgen_get_eca_tracking_query_prefix($prefixOverride = ''): string
  {
    $override = ppsgen_sanitize_eca_tracking_query_prefix($prefixOverride);
    if ($override !== '') {
      return $override;
    }
    static $prefix = null;
    if ($prefix !== null) {
      return $prefix;
    }
    $prefix = '';
    global $wpdb;
    if (!empty($wpdb)) {
      $table = $wpdb->prefix . 'ppsgen_options';
      $tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
      if ($tableExists === $table) {
        foreach (['eca', 'dashboard'] as $modSlug) {
          $stored = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$table} WHERE mod_slug = %s AND mod_obj_id = %d AND option_name = %s AND option_group = %s LIMIT 1",
            $modSlug,
            0,
            'eca_tracking_query_prefix',
            'options'
          ));
          $prefix = ppsgen_sanitize_eca_tracking_query_prefix($stored);
          if ($prefix !== '') {
            break;
          }
        }
      }
    }
    if ($prefix === '') {
      $prefix = ppsgen_get_default_eca_tracking_query_prefix();
    }
    return $prefix;
  }
}

if (!function_exists('ppsgen_get_eca_tracking_query_arg')) {
  function ppsgen_get_eca_tracking_query_arg(string $type, $prefixOverride = ''): string
  {
    $suffix = $type === 'unsubscribe' ? 'u' : 'a';
    return ppsgen_get_eca_tracking_query_prefix($prefixOverride) . '_' . $suffix;
  }
}

if (!function_exists('ppsgen_get_eca_tracking_request_key')) {
  function ppsgen_get_eca_tracking_request_key(string $type): string
  {
    $suffix = $type === 'unsubscribe' ? 'u' : 'a';
    $keys = [ppsgen_get_eca_tracking_query_arg($type)];
    $keys[] = $type === 'unsubscribe' ? 'ppsgen_eca_unsubscribe' : 'ppsgen_eca_action';
    foreach (array_unique($keys) as $key) {
      if (isset($_REQUEST[$key]) && $_REQUEST[$key] !== '') {
        return $key;
      }
    }
    foreach ($_REQUEST as $key => $value) {
      $key = (string) $key;
      if ($value === '' || !preg_match('/^[a-z][a-z0-9_]{0,24}_' . preg_quote($suffix, '/') . '$/', $key)) {
        continue;
      }
      return $key;
    }
    return '';
  }
}

if (!function_exists('ppsgen_get_eca_tracking_request_value')) {
  function ppsgen_get_eca_tracking_request_value(string $type): string
  {
    $key = function_exists('ppsgen_get_eca_tracking_request_key') ? ppsgen_get_eca_tracking_request_key($type) : '';
    if ($key !== '' && isset($_REQUEST[$key])) {
      return sanitize_text_field(wp_unslash($_REQUEST[$key]));
    }
    return '';
  }
}

if (!function_exists('ppsgen_request_has_eca_tracking_params')) {
  function ppsgen_request_has_eca_tracking_params(): bool
  {
    return ppsgen_get_eca_tracking_request_key('action') !== '' || ppsgen_get_eca_tracking_request_key('unsubscribe') !== '';
  }
}

if (!function_exists('ppsgen_legacy_table_exists')) {
  function ppsgen_legacy_table_exists(): bool
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pps_modules';
    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
  }
}

if (!function_exists('ppsgen_setup_legacy_flags')) {
  function ppsgen_setup_legacy_flags(): void
  {
    $legacyUser = get_option(PPSGEN_LEGACY_USER_OPTION, null);
    if ($legacyUser === null || $legacyUser === false) {
      $legacyUser = ppsgen_legacy_table_exists() ? 1 : 0;
      update_option(PPSGEN_LEGACY_USER_OPTION, $legacyUser, false);
    }

    $legacyActive = get_option(PPSGEN_LEGACY_ACTIVE_OPTION, null);
    if ($legacyActive === null || $legacyActive === false) {
      update_option(PPSGEN_LEGACY_ACTIVE_OPTION, !empty($legacyUser) ? 1 : 0, false);
    }
  }
}

if (!function_exists('ppsgen_is_legacy_user')) {
  function ppsgen_is_legacy_user(): bool
  {
    ppsgen_setup_legacy_flags();
    return (bool) get_option(PPSGEN_LEGACY_USER_OPTION, 0);
  }
}

if (!function_exists('ppsgen_is_legacy_active')) {
  function ppsgen_is_legacy_active(): bool
  {
    return ppsgen_is_legacy_user() && (bool) get_option(PPSGEN_LEGACY_ACTIVE_OPTION, 0);
  }
}

if (!function_exists('ppsgen_should_load_legacy_runtime')) {
  function ppsgen_should_load_legacy_runtime(): bool
  {
    return ppsgen_is_legacy_user();
  }
}

// CES PRO license lifecycle (see ppsgen_get_pro_license_info):
// none    - PRO was never activated (or the stored data is invalid): PRO is fully gated.
// active  - valid license: full PRO.
// grace   - license expired less than PPSGEN_PRO_LICENSE_GRACE_DAYS ago: full PRO + renewal reminders. Only a license
//           that ran out gets it; a key the license server does not accept (lk_status "revoked") is frozen at once.
// expired - "frozen" mode: nothing breaks (front end, tracking, stats, emails, providers keep working),
//           but PRO items built before the freeze are view-only and anything new is Free-only.
//           No updates and no support until renewal.
if (!defined('PPSGEN_PRO_LICENSE_GRACE_DAYS')) {
  define('PPSGEN_PRO_LICENSE_GRACE_DAYS', 14);
}
if (!defined('PPSGEN_PRO_FREEZE_OPTION')) {
  define('PPSGEN_PRO_FREEZE_OPTION', 'ppsgen_pro_freeze');
}

if (!function_exists('ppsgen_get_pro_license_info')) {
  function ppsgen_get_pro_license_info(): array
  {
    $info = ['state' => 'none', 'date_end' => '', 'grace_end' => '', 'grace_days_left' => 0];
    if (!function_exists('is_plugin_active')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (!is_plugin_active('popup-by-supsystic-pro/popup-by-supsystic-pro.php')) {
      return $info;
    }

    $data = get_option('ppsgen_pro_data');
    if (empty($data) || !is_array($data) || !in_array($data['lk_status'] ?? '', ['active', 'expired', 'revoked'], true)) {
      return $info;
    }

    foreach (['license_email', 'license_key', 'lk_date_start', 'lk_date_end', 'lk_code', 'lk_days', 'lk_parts', 'license_signature'] as $requiredKey) {
      if (!isset($data[$requiredKey])) {
        return $info;
      }
    }

    $signatureData = [
      'lk_status' => $data['lk_status'],
      'lk_date_start' => $data['lk_date_start'],
      'lk_date_end' => $data['lk_date_end'],
      'lk_code' => $data['lk_code'],
      'lk_days' => $data['lk_days'],
      'lk_parts' => $data['lk_parts'],
    ];
    $expectedSig = hash_hmac('sha256', json_encode($signatureData), (string) $data['license_key']);
    if (!hash_equals($expectedSig, (string) $data['license_signature'])) {
      return $info;
    }

    $today = strtotime(date('Y-m-d'));
    $dateEnd = strtotime((string) $data['lk_date_end']);
    if (empty($dateEnd)) {
      return $info;
    }
    $graceEnd = $dateEnd + PPSGEN_PRO_LICENSE_GRACE_DAYS * DAY_IN_SECONDS;
    $info['date_end'] = date('Y-m-d', $dateEnd);
    $info['grace_end'] = date('Y-m-d', $graceEnd);

    if ($data['lk_status'] === 'active' && $today <= $dateEnd) {
      $info['state'] = 'active';
    } elseif ($data['lk_status'] === 'revoked') {
      $info['state'] = 'expired';
    } elseif ($today <= $graceEnd) {
      $info['state'] = 'grace';
      $info['grace_days_left'] = (int) round(($graceEnd - $today) / DAY_IN_SECONDS);
    } else {
      $info['state'] = 'expired';
    }
    return $info;
  }
}

if (!function_exists('ppsgen_get_pro_license_state')) {
  function ppsgen_get_pro_license_state(): string
  {
    return ppsgen_get_pro_license_info()['state'];
  }
}

// Full PRO is available (valid license or grace period)
if (!function_exists('ppsgen_is_pro_license_active')) {
  function ppsgen_is_pro_license_active(): bool
  {
    return in_array(ppsgen_get_pro_license_state(), ['active', 'grace'], true);
  }
}

// License expired and the grace period is over: PRO keeps running, PRO editing is frozen
if (!function_exists('ppsgen_is_pro_license_frozen')) {
  function ppsgen_is_pro_license_frozen(): bool
  {
    return ppsgen_get_pro_license_state() === 'expired';
  }
}

// Free contact limit (Free-to-PRO model): Free stores every contact, but automations and newsletters
// only email the first 500 contacts by date added. Form emails (thank-you, owner alert) are not affected.
if (!defined('PPSGEN_FREE_REACHABLE_CONTACTS')) {
  define('PPSGEN_FREE_REACHABLE_CONTACTS', 500);
}

if (!function_exists('ppsgen_contact_limit_applies')) {
  function ppsgen_contact_limit_applies(): bool
  {
    // A lapsed (frozen) PRO licence keeps sending as in PRO: only editing PRO items is frozen
    if (function_exists('ppsgen_is_pro_license_frozen') && ppsgen_is_pro_license_frozen()) {
      $applies = false;
    } elseif (defined('PPSGEN_PRO_PLUGIN_NAME') || class_exists('FrameProPpsgen')) {
      $applies = false;
    } else {
      if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      $applies = !is_plugin_active('popup-by-supsystic-pro/popup-by-supsystic-pro.php');
    }
    return (bool) apply_filters('ppsgen_contact_limit_applies', $applies);
  }
}

if (!function_exists('ppsgen_get_reachable_contact_ids')) {
  // The first 500 contacts by date added. Deleting contacts frees their places.
  function ppsgen_get_reachable_contact_ids(bool $refresh = false): array
  {
    static $ids = null;
    if ($ids === null || $refresh) {
      global $wpdb;
      $ids = array_flip(array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE deleted_at IS NULL ORDER BY created_at ASC, id ASC LIMIT %d",
        PPSGEN_FREE_REACHABLE_CONTACTS
      ))));
    }
    return $ids;
  }
}

if (!function_exists('ppsgen_is_contact_reachable')) {
  function ppsgen_is_contact_reachable(int $contactId): bool
  {
    if (!ppsgen_contact_limit_applies()) {
      return true;
    }
    return isset(ppsgen_get_reachable_contact_ids()[$contactId]);
  }
}

if (!function_exists('ppsgen_get_contact_limit_status')) {
  function ppsgen_get_contact_limit_status(): array
  {
    global $wpdb;
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_contacts WHERE deleted_at IS NULL");
    $applies = ppsgen_contact_limit_applies();
    $limit = PPSGEN_FREE_REACHABLE_CONTACTS;
    return [
      'applies' => $applies,
      'limit' => $limit,
      'total' => $total,
      'reachable' => $applies ? min($total, $limit) : $total,
      'over' => $applies ? max(0, $total - $limit) : 0,
      // Warn from about 400 so the limit never comes as a cliff edge
      'warn' => $applies && $total >= (int) round($limit * 0.8),
      'percent' => $applies ? min(100, (int) round($total / $limit * 100)) : 0,
    ];
  }
}

if (!function_exists('ppsgen_contact_import_allows_new')) {
  // Bulk imports (CSV, existing WordPress users, past WooCommerce orders) stop at 500 contacts in total on Free.
  // Website capture and auto-import of new customers are never blocked.
  function ppsgen_contact_import_allows_new(): bool
  {
    if (!ppsgen_contact_limit_applies()) {
      return true;
    }
    global $wpdb;
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_contacts WHERE deleted_at IS NULL") < PPSGEN_FREE_REACHABLE_CONTACTS;
  }
}

if (!function_exists('ppsgen_get_contact_import_limit_message')) {
  function ppsgen_get_contact_import_limit_message(int $notImported): string
  {
    if (function_exists('ppsgen_track_gateway')) {
      ppsgen_track_gateway('import_limit');
    }
    return sprintf(
      /* translators: %d: number of contacts not imported */
      _n(
        "%d contact was not imported: Free brings in up to 500 contacts in total. You're bringing an existing list into CES — PRO handles lists of any size, with a 30-day money-back guarantee.",
        "%d contacts were not imported: Free brings in up to 500 contacts in total. You're bringing an existing list into CES — PRO handles lists of any size, with a 30-day money-back guarantee.",
        $notImported,
        'ppsgen'
      ),
      $notImported
    );
  }
}

if (!function_exists('ppsgen_get_pro_renew_url')) {
  function ppsgen_get_pro_renew_url(): string
  {
    return 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=license&utm_campaign=renew_pro';
  }
}

// Wording of the "1 running automation" rule: Free is offered PRO, an expired (frozen) PRO license is offered a renewal
if (!function_exists('ppsgen_automation_limit_text')) {
  function ppsgen_automation_limit_text(string $key): string
  {
    if (function_exists('ppsgen_is_pro_license_frozen') && ppsgen_is_pro_license_frozen()) {
      $texts = [
        'conflict_save' => __('“%s” is running, and while your PRO license is expired new automations run one at a time. Turn it off first, or renew your license to run both. Nothing was saved.', 'ppsgen'),
        'conflict_play' => __('“%s” is running, and while your PRO license is expired new automations run one at a time. Switch it off and run this play’s follow-up instead? Renew your license to run both.', 'ppsgen'),
        'switch_title' => __('New automations run one at a time while your PRO license is expired: turning this on switches off “%s”.', 'ppsgen'),
        'review_note' => __('New automations run one at a time while your PRO license is expired. Nothing is deleted: switch it back on whenever you like, or renew your license to run both.', 'ppsgen'),
        'held_title' => __('New automations run one at a time while your PRO license is expired, and “%s” is the one running. People here wait at their current step and continue when you switch the other one off or renew your license.', 'ppsgen'),
        'held_label' => __('Waiting · renew to run', 'ppsgen'),
        'home_held' => __('While your PRO license is expired, new automations run one at a time, so only “%s” sends. People in the others wait at their step and nothing is lost.', 'ppsgen'),
        'badge' => __('%d of 1 new automation running', 'ppsgen'),
        'more_link' => __('Renew PRO to run them all', 'ppsgen'),
        'cta' => __('Renew license', 'ppsgen'),
        'home_link' => __('Renew your license →', 'ppsgen'),
      ];
    } else {
      $texts = [
        'conflict_save' => __('Free runs 1 automation at a time and “%s” is running. Turn it off first, or upgrade to PRO to run both. Nothing was saved.', 'ppsgen'),
        'conflict_play' => __('Free runs 1 automation at a time and “%s” is running. Switch it off and run this play’s follow-up instead? Upgrade to PRO to run both.', 'ppsgen'),
        'switch_title' => __('Free runs 1 automation at a time: turning this on switches off “%s”.', 'ppsgen'),
        'review_note' => __('Free runs 1 automation at a time. Nothing is deleted: switch it back on whenever you like, or run both with PRO.', 'ppsgen'),
        'held_title' => __('Free runs 1 automation at a time and “%s” is the one running. People here wait at their current step and continue when you switch the other one off or upgrade to PRO.', 'ppsgen'),
        'held_label' => __('Waiting · Free runs 1', 'ppsgen'),
        'home_held' => __('Free runs 1 automation at a time, so only “%s” sends. People in the others wait at their step and nothing is lost.', 'ppsgen'),
        'badge' => __('%d of 1 Free automation running', 'ppsgen'),
        'more_link' => __('Run more at once with PRO', 'ppsgen'),
        'cta' => __('See PRO', 'ppsgen'),
        'home_link' => __('See your options →', 'ppsgen'),
      ];
    }
    return $texts[$key] ?? '';
  }
}

if (!function_exists('ppsgen_automation_limit_url')) {
  function ppsgen_automation_limit_url(): string
  {
    if (function_exists('ppsgen_is_pro_license_frozen') && ppsgen_is_pro_license_frozen()) {
      return ppsgen_get_pro_renew_url();
    }
    return admin_url('admin.php?page=ppsgen&tab=settings_pro');
  }
}

// Free email branding (the Three B's "Burden"): every email CES sends on Free, and on an expired PRO license,
// carries a small footer. Active and grace-period PRO licenses send without it.
if (!function_exists('ppsgen_email_branding_applies')) {
  function ppsgen_email_branding_applies(): bool
  {
    $applies = !in_array(ppsgen_get_pro_license_state(), ['active', 'grace'], true);
    return (bool) apply_filters('ppsgen_email_branding_applies', $applies);
  }
}

if (!function_exists('ppsgen_get_email_branding_html')) {
  function ppsgen_get_email_branding_html(): string
  {
    if (!ppsgen_email_branding_applies()) {
      return '';
    }
    // The utm_medium=email_footer link also marks the footer, so it is never added twice
    return '<div style="display:block; position:relative;"><a href="https://supsystic.com/?utm_source=ces_plugin&amp;utm_medium=email_footer" style="display:block; position:relative; padding:10px; text-align:center; color:#9e9e9e; text-decoration:none; font-size:12px; font-weight:normal;">' . esc_html__("Powered by Supsystic's Customer Engagement System", 'ppsgen') . '</a></div>';
  }
}

if (!function_exists('ppsgen_get_pro_lockable_tables')) {
  function ppsgen_get_pro_lockable_tables(): array
  {
    return [
      'popup' => 'ppsgen_popups',
      'form' => 'ppsgen_forms',
      'eca_chain' => 'ppsgen_eca_chains',
      'eca_template' => 'ppsgen_eca_templates',
    ];
  }
}

// Items that already exist when the freeze starts are PRO items (view-only), items created later are Free items.
// The snapshot keeps the last ID of every lockable table at the freeze moment and is dropped after renewal.
if (!function_exists('ppsgen_sync_pro_freeze_snapshot')) {
  function ppsgen_sync_pro_freeze_snapshot(): array
  {
    $snapshot = get_option(PPSGEN_PRO_FREEZE_OPTION);
    $state = ppsgen_get_pro_license_state();
    if ($state !== 'expired') {
      // Only a renewal ends the freeze; a deactivated PRO addon keeps the snapshot for when it comes back
      if ($snapshot !== false && in_array($state, ['active', 'grace'], true)) {
        delete_option(PPSGEN_PRO_FREEZE_OPTION);
      }
      return [];
    }
    if (is_array($snapshot) && isset($snapshot['ids'])) {
      return $snapshot;
    }
    global $wpdb;
    $ids = [];
    foreach (ppsgen_get_pro_lockable_tables() as $type => $table) {
      $table = $wpdb->prefix . $table;
      $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
      $ids[$type] = $exists ? (int) $wpdb->get_var("SELECT MAX(id) FROM {$table}") : 0;
    }
    $snapshot = ['frozen_at' => current_time('mysql'), 'ids' => $ids];
    update_option(PPSGEN_PRO_FREEZE_OPTION, $snapshot, false);
    return $snapshot;
  }
}

if (!function_exists('ppsgen_is_pro_item_locked')) {
  function ppsgen_is_pro_item_locked(string $type, $id): bool
  {
    $id = (int) $id;
    if ($id <= 0) {
      return false;
    }
    $snapshot = ppsgen_sync_pro_freeze_snapshot();
    return !empty($snapshot['ids'][$type]) && $id <= (int) $snapshot['ids'][$type];
  }
}

if (!function_exists('ppsgen_get_pro_item_locked_message')) {
  function ppsgen_get_pro_item_locked_message(): string
  {
    return __('Your PRO license has expired, so this PRO item is view-only. It keeps working on your site exactly as before. Renew your license to edit it.', 'ppsgen');
  }
}

// Ajax guard for actions that modify a PRO item
if (!function_exists('ppsgen_reject_locked_pro_item')) {
  function ppsgen_reject_locked_pro_item(string $type, $id): void
  {
    if (ppsgen_is_pro_item_locked($type, $id)) {
      wp_send_json_error(['message' => ppsgen_get_pro_item_locked_message(), 'pro_locked' => 1]);
    }
  }
}

// Frozen PRO items stay view-only but can still be enabled or disabled: only the Enabled status is saved
if (!function_exists('ppsgen_save_locked_pro_item_status')) {
  function ppsgen_save_locked_pro_item_status(string $type, $id, $active): void
  {
    if (!ppsgen_is_pro_item_locked($type, $id)) {
      return;
    }
    $tables = ppsgen_get_pro_lockable_tables();
    if ($active !== null && in_array($type, ['popup', 'form', 'eca_chain'], true)) {
      global $wpdb;
      $id = (int) $id;
      $active = empty($active) ? 0 : 1;
      $table = $wpdb->prefix . $tables[$type];
      $wpdb->update($table, ['active' => $active], ['id' => $id]);
      if ($type === 'eca_chain') {
        $settings = json_decode((string) $wpdb->get_var($wpdb->prepare("SELECT settings FROM {$table} WHERE id = %d", $id)), true);
        if (is_array($settings)) {
          $settings['active'] = $active;
          $wpdb->update($table, ['settings' => wp_json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)], ['id' => $id]);
        }
        $wpdb->update($wpdb->prefix . 'ppsgen_eca_flow', ['active' => $active], ['chain_id' => $id]);
      }
    }
    wp_send_json_success([
      'message' => __('Only the Enabled status was saved: this PRO item is view-only while your license is expired. Renew your license to edit it.', 'ppsgen'),
      'pro_locked' => 1,
    ]);
  }
}

// Edit page of the current admin request: ['type' => ..., 'id' => ...] or null
if (!function_exists('ppsgen_get_pro_lockable_edit_page')) {
  function ppsgen_get_pro_lockable_edit_page(): ?array
  {
    if (!is_admin() || wp_doing_ajax()) {
      return null;
    }
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
    $tabs = [
      'popup_edit' => 'popup',
      'form_edit' => 'form',
      'eca_chain_edit' => 'eca_chain',
      'eca_template_edit' => 'eca_template',
    ];
    if ($page !== 'ppsgen' || empty($tabs[$tab])) {
      return null;
    }
    return ['type' => $tabs[$tab], 'id' => isset($_GET['id']) ? (int) $_GET['id'] : 0];
  }
}

// Frozen PRO + edit page of a PRO item: the page is rendered read-only
if (!function_exists('ppsgen_is_pro_locked_edit_page')) {
  function ppsgen_is_pro_locked_edit_page(): bool
  {
    $editPage = ppsgen_get_pro_lockable_edit_page();
    return !empty($editPage) && ppsgen_is_pro_item_locked($editPage['type'], $editPage['id']);
  }
}

// Frozen PRO + edit page of a new/Free item: PRO editing tools are not loaded (Free-only)
if (!function_exists('ppsgen_is_pro_editing_restricted')) {
  function ppsgen_is_pro_editing_restricted(): bool
  {
    if (!ppsgen_is_pro_license_frozen()) {
      return false;
    }
    $editPage = ppsgen_get_pro_lockable_edit_page();
    return !empty($editPage) && !ppsgen_is_pro_item_locked($editPage['type'], $editPage['id']);
  }
}

if (!function_exists('ppsgen_collect_background_asset_choices')) {
  function ppsgen_collect_background_asset_choices(string $baseDir, string $baseUrl, string $label): array
  {
    $baseDir = wp_normalize_path($baseDir);
    if (!is_dir($baseDir)) {
      return [];
    }

    $baseUrl = trailingslashit($baseUrl);
    $basePath = wp_parse_url($baseUrl, PHP_URL_PATH);
    $basePath = $basePath ? trailingslashit($basePath) : $baseUrl;
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $items = [];
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
      if (!$fileInfo->isFile()) {
        continue;
      }
      $extension = strtolower((string) $fileInfo->getExtension());
      if (!in_array($extension, $extensions, true)) {
        continue;
      }

      $filePath = wp_normalize_path($fileInfo->getPathname());
      $relative = ltrim(substr($filePath, strlen($baseDir)), '/');
      $relative = str_replace('\\', '/', $relative);
      $items[] = [
        'label' => $fileInfo->getBasename(),
        'source' => $label,
        'path' => $basePath . $relative,
        'preview' => $baseUrl . $relative,
      ];
    }

    usort($items, static function ($a, $b) {
      return strnatcasecmp(($a['source'] ?? '') . ($a['label'] ?? ''), ($b['source'] ?? '') . ($b['label'] ?? ''));
    });

    return $items;
  }
}

if (!function_exists('ppsgen_get_background_asset_choices')) {
  function ppsgen_get_background_asset_choices(): array
  {
    static $items = null;
    if ($items !== null) {
      return $items;
    }

    $items = ppsgen_collect_background_asset_choices(
      PPSGEN_PLUGIN_DIR . 'public/assets/img/bg',
      PPSGEN_PLUGIN_URL . 'public/assets/img/bg',
      __('Free backgrounds', 'ppsgen')
    );

    if (ppsgen_is_pro_license_active()) {
      $items = array_merge(
        $items,
        ppsgen_collect_background_asset_choices(
          WP_PLUGIN_DIR . '/popup-by-supsystic-pro/public/assets/img/bg',
          plugins_url('popup-by-supsystic-pro/public/assets/img/bg'),
          __('PRO backgrounds', 'ppsgen')
        )
      );
    }

    return $items;
  }
}

if (!function_exists('ppsgen_render_background_image_picker')) {
  function ppsgen_render_background_image_picker($component, string $name, $value = ''): void
  {
    ?>
    <div class="ppsgenBgImagePicker">
      <div class="ppsgenComponentOptionNoticeUnder">
        <?php esc_html_e('Relative or absolute path', 'ppsgen'); ?>
      </div>
      <div class="ppsgenBgImagePickerRow">
        <?php $component->text($name, [
          'value' => $value,
          'attrs' => 'class="ppsgenW250 ppsgenBgImageInput"',
        ]); ?>
        <button type="button" class="ppsgenBgMediaBtn">
          <span class="dashicons dashicons-format-image"></span>
          <?php esc_html_e('Media Library', 'ppsgen'); ?>
        </button>
        <button type="button" class="ppsgenBgAssetsBtn">
          <span class="dashicons dashicons-images-alt2"></span>
          <?php esc_html_e('Show Images List', 'ppsgen'); ?>
        </button>
        <span class="ppsgenBgImagePreviewWrap" aria-hidden="true">
          <img class="ppsgenBgImagePreview" alt="" />
        </span>
      </div>
    </div>
    <?php
  }
}

if (!function_exists('ppsgen_is_legacy_switch_banner_dismissed')) {
  function ppsgen_is_legacy_switch_banner_dismissed(): bool
  {
    return (bool) get_option(PPSGEN_LEGACY_SWITCH_DISMISSED_OPTION, 0);
  }
}

if (!function_exists('ppsgen_dismiss_legacy_switch_banner')) {
  function ppsgen_dismiss_legacy_switch_banner(): bool
  {
    return update_option(PPSGEN_LEGACY_SWITCH_DISMISSED_OPTION, 1, false);
  }
}

if (!function_exists('ppsgen_should_show_legacy_upgrade_modal')) {
  function ppsgen_should_show_legacy_upgrade_modal(): bool
  {
    if (!ppsgen_is_legacy_admin_page() || !ppsgen_is_legacy_active() || !current_user_can('manage_options')) {
      return false;
    }
    if (!empty($_COOKIE['ppsgen_legacy_upgrade_modal_closed'])) {
      return false;
    }
    $dismissedUntil = (int) get_user_meta(get_current_user_id(), PPSGEN_LEGACY_UPGRADE_MODAL_DISMISSED_META, true);
    return $dismissedUntil < time();
  }
}

if (!function_exists('ppsgen_dismiss_legacy_upgrade_modal')) {
  function ppsgen_dismiss_legacy_upgrade_modal(): bool
  {
    return (bool) update_user_meta(get_current_user_id(), PPSGEN_LEGACY_UPGRADE_MODAL_DISMISSED_META, time() + DAY_IN_SECONDS);
  }
}

if (!function_exists('ppsgen_set_legacy_active')) {
  function ppsgen_set_legacy_active(bool $active): bool
  {
    if (!ppsgen_is_legacy_user()) {
      update_option(PPSGEN_LEGACY_ACTIVE_OPTION, 0, false);
      return false;
    }
    return update_option(PPSGEN_LEGACY_ACTIVE_OPTION, $active ? 1 : 0, false);
  }
}

if (!function_exists('ppsgen_redirect_legacy_plugin_activation_target')) {
  function ppsgen_redirect_legacy_plugin_activation_target(): void
  {
    if (!is_admin() || !current_user_can('activate_plugins')) {
      return;
    }
    if (($GLOBALS['pagenow'] ?? '') !== 'plugins.php' || ($_GET['action'] ?? '') !== 'activate' || empty($_GET['plugin'])) {
      return;
    }
    $plugin = sanitize_text_field(wp_unslash($_GET['plugin']));
    $replacements = [
      'popup-by-supsystic/legacy/pps.php' => 'popup-by-supsystic/pps.php',
      'popup-by-supsystic-pro/legacy/popup-by-supsystic-pro.php' => 'popup-by-supsystic-pro/popup-by-supsystic-pro.php',
    ];
    if (empty($replacements[$plugin])) {
      return;
    }
    $targetPlugin = $replacements[$plugin];
    $targetUrl = add_query_arg(
      [
        'action' => 'activate',
        'plugin' => $targetPlugin,
        '_wpnonce' => wp_create_nonce('activate-plugin_' . $targetPlugin),
      ],
      admin_url('plugins.php'),
    );
    wp_safe_redirect($targetUrl);
    exit;
  }
}

add_action('admin_init', 'ppsgen_redirect_legacy_plugin_activation_target', 1);

if (!function_exists('ppsgen_is_plugin_admin_page')) {
  function ppsgen_is_plugin_admin_page(): bool
  {
    if (empty($_GET['page'])) {
      return false;
    }
    $page = sanitize_key(wp_unslash($_GET['page']));
    return in_array($page, ['ppsgen', 'popup-wp-supsystic'], true);
  }
}

if (!function_exists('ppsgen_disable_wp_screen_meta')) {
  function ppsgen_disable_wp_screen_meta($screen): void
  {
    if (!ppsgen_is_plugin_admin_page()) {
      return;
    }
    add_filter('screen_options_show_screen', '__return_false', 1000);
    if (is_object($screen) && method_exists($screen, 'remove_help_tabs')) {
      $screen->remove_help_tabs();
    }
  }
}

add_action('current_screen', 'ppsgen_disable_wp_screen_meta', 1000);

if (!function_exists('ppsgen_hide_wp_screen_meta')) {
  function ppsgen_hide_wp_screen_meta(): void
  {
    if (!ppsgen_is_plugin_admin_page()) {
      return;
    }
    ?>
    <style>
      #screen-meta,
      #screen-meta-links {
        display: none !important;
      }
    </style>
    <?php
  }
}

add_action('admin_head', 'ppsgen_hide_wp_screen_meta', 1);

if (!function_exists('ppsgen_is_legacy_admin_page')) {
  function ppsgen_is_legacy_admin_page(): bool
  {
    if (empty($_GET['page'])) {
      return false;
    }
    return sanitize_key(wp_unslash($_GET['page'])) === 'popup-wp-supsystic';
  }
}

if (!function_exists('ppsgen_render_legacy_admin_switch_banner')) {
  function ppsgen_render_legacy_admin_switch_banner(): void
  {
    if (!ppsgen_is_legacy_admin_page() || !ppsgen_is_legacy_active() || !current_user_can('manage_options')) {
      return;
    }
    $showUpgradeModal = ppsgen_should_show_legacy_upgrade_modal();
    $nonce = wp_create_nonce('ppsgenAjaxNonce');
    $newAdminUrl = admin_url('admin.php?page=ppsgen&tab=dashboard&ppsgen_new_preview=1');
    $activateUrl = admin_url('admin.php?page=ppsgen&tab=dashboard&ppsgen_new_activated=1');
    ?>
    <div class="ppsgenLegacyAdminSwitchBanner">
      <div class="ppsgenLegacyAdminSwitchBannerText">
        <strong><?php esc_html_e('Classic PopUp is active', 'ppsgen'); ?></strong>
        <span><?php esc_html_e('Switch to the new Customer Engagement System – Popups, Forms, CRM & Automation admin panel when you are ready.', 'ppsgen'); ?></span>
      </div>
      <div class="ppsgenLegacyAdminSwitchBannerActions">
        <a class="ppsgenLegacyAdminSwitchLink" href="<?php echo esc_url($newAdminUrl); ?>">
          <span class="dashicons dashicons-visibility"></span>
          <?php esc_html_e('Preview new interface and features', 'ppsgen'); ?>
        </a>
        <button type="button" class="ppsgenLegacyAdminSwitchBtn ppsgenLegacyAdminActivateBtn">
          <span class="dashicons dashicons-migrate"></span>
          <?php esc_html_e('Activate new interface and features', 'ppsgen'); ?>
        </button>
      </div>
    </div>
    <?php if ($showUpgradeModal) { ?>
    <div class="ppsgenLegacyUpgradeModalOverlay" role="presentation">
      <div class="ppsgenLegacyUpgradeModal" role="dialog" aria-modal="true" aria-labelledby="ppsgenLegacyUpgradeModalTitle">
        <button type="button" class="ppsgenLegacyUpgradeModalClose" aria-label="<?php esc_attr_e('Close for today', 'ppsgen'); ?>">
          <span class="dashicons dashicons-no-alt"></span>
        </button>
        <div class="ppsgenLegacyUpgradeModalVisual" aria-hidden="true">
          <div class="ppsgenLegacyUpgradeModalIcon">
            <img src="<?php echo esc_url(PPSGEN_MODULES_URL . 'dashboard/assets/img/header-logo.webp'); ?>" alt="">
          </div>
          <div class="ppsgenLegacyUpgradeModalMetric">
            <strong>4-in-1</strong>
            <span><?php esc_html_e('Popups, Forms, CRM, Email', 'ppsgen'); ?></span>
          </div>
        </div>
        <div class="ppsgenLegacyUpgradeModalContent">
          <div class="ppsgenLegacyUpgradeModalEyebrow"><?php esc_html_e('A new Supsystic workspace is ready', 'ppsgen'); ?></div>
          <h2 id="ppsgenLegacyUpgradeModalTitle"><?php esc_html_e('Move faster with Customer Engagement System – Popups, Forms, CRM & Automation', 'ppsgen'); ?></h2>
          <p><?php esc_html_e('The new interface brings popups, forms, contacts, email campaigns, templates, statistics, and PRO tools into one clean workflow built for modern WordPress marketing.', 'ppsgen'); ?></p>
          <ul>
            <li><?php esc_html_e('Build conversion popups and forms with a faster visual editor.', 'ppsgen'); ?></li>
            <li><?php esc_html_e('Connect every submit to CRM contacts, tags, campaigns, and automations.', 'ppsgen'); ?></li>
            <li><?php esc_html_e('Keep your current Classic PopUp popups safe: after activation they will still work on the site and remain available for editing in Classic PopUp.', 'ppsgen'); ?></li>
          </ul>
          <div class="ppsgenLegacyUpgradeModalActions">
            <a class="ppsgenLegacyUpgradeModalBtn ppsgenLegacyUpgradeModalBtnSecondary" href="<?php echo esc_url($newAdminUrl); ?>">
              <span class="dashicons dashicons-visibility"></span>
              <?php esc_html_e('Preview new interface and features', 'ppsgen'); ?>
            </a>
            <button type="button" class="ppsgenLegacyUpgradeModalBtn ppsgenLegacyUpgradeModalBtnPrimary">
              <span class="dashicons dashicons-migrate"></span>
              <?php esc_html_e('Activate new interface and features', 'ppsgen'); ?>
            </button>
          </div>
          <p class="ppsgenLegacyUpgradeModalNote"><?php esc_html_e('You can switch back to Classic PopUp anytime from Settings › Classic PopUp.', 'ppsgen'); ?></p>
        </div>
      </div>
    </div>
    <?php } ?>
    <style>
      .ppsgenLegacyAdminSwitchBanner {
        position: sticky;
        top: 32px;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 58px;
        margin: 12px 20px 16px 2px;
        padding: 12px 14px 12px 18px;
        border: 1px solid rgba(31, 94, 255, 0.18);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 14px 32px rgba(25, 52, 110, 0.18);
        color: #172554;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }
      .folded .ppsgenLegacyAdminSwitchBanner,
      .auto-fold .ppsgenLegacyAdminSwitchBanner {
        margin-right: 20px;
      }
      .ppsgenLegacyAdminSwitchBannerText {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
      }
      .ppsgenLegacyAdminSwitchBannerText strong {
        font-size: 14px;
        line-height: 1.25;
      }
      .ppsgenLegacyAdminSwitchBannerText span {
        color: #52607a;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
      }
      .ppsgenLegacyAdminSwitchBannerActions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex: 0 0 auto;
      }
      .ppsgenLegacyAdminSwitchLink,
      .ppsgenLegacyAdminSwitchBtn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        flex: 0 0 auto;
        min-height: 36px;
        padding: 0 13px;
        border: 1px solid #1f5eff;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
      }
      .ppsgenLegacyAdminSwitchBtn {
        background: #1f5eff;
        color: #fff;
      }
      .ppsgenLegacyAdminSwitchLink {
        background: #eef4ff;
        color: #1f3f98;
        border-color: rgba(31, 94, 255, 0.22);
      }
      .ppsgenLegacyAdminSwitchBtn:hover {
        background: #174bd0;
        color: #fff;
      }
      .ppsgenLegacyAdminSwitchLink:hover {
        background: #e2ebff;
        color: #17337e;
      }
      .ppsgenLegacyAdminSwitchBtn[disabled] {
        cursor: wait;
        opacity: 0.72;
      }
      .ppsgenLegacyAdminSwitchLink .dashicons,
      .ppsgenLegacyAdminSwitchBtn .dashicons {
        width: 16px;
        height: 16px;
        font-size: 16px;
        line-height: 16px;
      }
      .ppsgenLegacyUpgradeModalOverlay {
        position: fixed;
        inset: 0;
        z-index: 1000000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 34px 20px;
        background: rgba(7, 16, 35, 0.56);
        backdrop-filter: blur(8px);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }
      .ppsgenLegacyUpgradeModal {
        position: relative;
        display: grid;
        grid-template-columns: 260px minmax(0, 1fr);
        width: min(860px, 100%);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 28px 80px rgba(3, 12, 30, 0.38);
        color: #172554;
      }
      .ppsgenLegacyUpgradeModalClose {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: 1px solid rgba(23, 37, 84, 0.12);
        border-radius: 999px;
        background: #f8fafc;
        color: #334155;
        cursor: pointer;
      }
      .ppsgenLegacyUpgradeModalClose:hover {
        background: #eef4ff;
        color: #1f5eff;
      }
      .ppsgenLegacyUpgradeModalVisual {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 430px;
        padding: 34px 26px;
        background: linear-gradient(160deg, #16347d 0%, #1f5eff 58%, #31c7d4 100%);
        color: #ffffff;
      }
      .ppsgenLegacyUpgradeModalVisual::after {
        content: "";
        position: absolute;
        right: -60px;
        bottom: -70px;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
      }
      .ppsgenLegacyUpgradeModalIcon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 76px;
        height: 76px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
      }
      .ppsgenLegacyUpgradeModalIcon img {
        display: block;
        width: 46px;
        height: 46px;
        object-fit: contain;
      }
      .ppsgenLegacyUpgradeModalMetric {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
      }
      .ppsgenLegacyUpgradeModalMetric strong {
        font-size: 42px;
        line-height: 1;
      }
      .ppsgenLegacyUpgradeModalMetric span {
        max-width: 170px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
        color: rgba(255, 255, 255, 0.86);
      }
      .ppsgenLegacyUpgradeModalContent {
        padding: 42px 42px 34px;
        min-width: 0;
      }
      .ppsgenLegacyUpgradeModalEyebrow {
        margin-bottom: 10px;
        color: #1f5eff;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
      }
      .ppsgenLegacyUpgradeModal h2 {
        margin: 0 28px 12px 0;
        color: #0f1f46;
        font-size: 30px;
        line-height: 1.12;
      }
      .ppsgenLegacyUpgradeModal p {
        margin: 0 0 14px;
        color: #475569;
        font-size: 14px;
        line-height: 1.62;
      }
      .ppsgenLegacyUpgradeModal ul {
        display: grid;
        gap: 9px;
        margin: 0 0 22px;
        padding: 0;
        list-style: none;
      }
      .ppsgenLegacyUpgradeModal li {
        position: relative;
        margin: 0;
        padding-left: 24px;
        color: #26344f;
        font-size: 13px;
        font-weight: 650;
        line-height: 1.45;
      }
      .ppsgenLegacyUpgradeModal li::before {
        content: "✓";
        position: absolute;
        left: 0;
        top: 0;
        color: #1f8f64;
        font-weight: 900;
      }
      .ppsgenLegacyUpgradeModalActions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
      }
      .ppsgenLegacyUpgradeModalBtn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 8px;
        border: 1px solid rgba(31, 94, 255, 0.24);
        cursor: pointer;
        font-size: 13px;
        font-weight: 850;
        line-height: 1;
        text-decoration: none;
      }
      .ppsgenLegacyUpgradeModalBtnPrimary {
        background: #1f5eff;
        color: #ffffff;
        border-color: #1f5eff;
      }
      .ppsgenLegacyUpgradeModalBtnSecondary {
        background: #eef4ff;
        color: #1f3f98;
      }
      .ppsgenLegacyUpgradeModalBtn:hover {
        transform: translateY(-1px);
      }
      .ppsgenLegacyUpgradeModalBtnPrimary:hover {
        background: #174bd0;
        color: #ffffff;
      }
      .ppsgenLegacyUpgradeModalBtnSecondary:hover {
        background: #e2ebff;
        color: #17337e;
      }
      .ppsgenLegacyUpgradeModalBtn[disabled] {
        cursor: wait;
        opacity: 0.72;
      }
      .ppsgenLegacyUpgradeModalBtn .dashicons {
        width: 16px;
        height: 16px;
        font-size: 16px;
        line-height: 16px;
      }
      .ppsgenLegacyUpgradeModalNote {
        margin-top: 14px !important;
        color: #64748b !important;
        font-size: 12px !important;
      }
      @media screen and (max-width: 782px) {
        .ppsgenLegacyAdminSwitchBanner {
          top: 46px;
          margin: 10px 12px 14px 0;
          flex-direction: column;
          align-items: stretch;
        }
        .ppsgenLegacyAdminSwitchBannerActions {
          align-items: stretch;
        }
        .ppsgenLegacyAdminSwitchLink,
        .ppsgenLegacyAdminSwitchBtn {
          flex: 1 1 0;
          min-width: 0;
          padding: 0 10px;
          white-space: nowrap;
        }
        .ppsgenLegacyUpgradeModalOverlay {
          align-items: flex-start;
          overflow-y: auto;
          padding-top: 20px;
        }
        .ppsgenLegacyUpgradeModal {
          grid-template-columns: 1fr;
        }
        .ppsgenLegacyUpgradeModalVisual {
          min-height: 170px;
        }
        .ppsgenLegacyUpgradeModalContent {
          padding: 28px 22px 24px;
        }
        .ppsgenLegacyUpgradeModal h2 {
          font-size: 24px;
        }
        .ppsgenLegacyUpgradeModalActions,
        .ppsgenLegacyUpgradeModalBtn {
          width: 100%;
        }
      }
    </style>
    <script>
      (function () {
        var modal = document.querySelector('.ppsgenLegacyUpgradeModalOverlay');
        var closeBtn = document.querySelector('.ppsgenLegacyUpgradeModalClose');
        var activateBtns = document.querySelectorAll('.ppsgenLegacyAdminActivateBtn, .ppsgenLegacyUpgradeModalBtnPrimary');
        function postDashboardAction(actionName, extra, done, fail) {
          var data = new URLSearchParams();
          data.append('action', actionName);
          data.append('mod', 'dashboard');
          data.append('plugin', 'ppsgen');
          data.append('nonce_code', <?php echo wp_json_encode($nonce); ?>);
          Object.keys(extra || {}).forEach(function (key) {
            data.append(key, extra[key]);
          });
          fetch(ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: data.toString()
          }).then(done).catch(fail || function () {});
        }
        function closeForToday() {
          if (!modal) {
            return;
          }
          modal.style.display = 'none';
          postDashboardAction('dismissLegacyUpgradeModal', {}, function () {}, function () {
            document.cookie = 'ppsgen_legacy_upgrade_modal_closed=1; path=/; max-age=' + (60 * 60 * 24);
          });
        }
        if (modal && closeBtn) {
          closeBtn.addEventListener('click', closeForToday);
          modal.addEventListener('click', function (event) {
            if (event.target === modal) {
              closeForToday();
            }
          });
          document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
              closeForToday();
            }
          });
        }
        activateBtns.forEach(function (activateBtn) {
          activateBtn.addEventListener('click', function () {
            activateBtns.forEach(function (btn) {
              btn.disabled = true;
            });
            postDashboardAction('toggleLegacyMode', {legacy_active: '0'}, function () {
              window.location.href = <?php echo wp_json_encode($activateUrl); ?>;
            }, function () {
              activateBtns.forEach(function (btn) {
                btn.disabled = false;
              });
            });
          });
        });
      }());
    </script>
    <?php
  }
}

if (!function_exists('ppsgen_hide_inactive_mode_admin_menu')) {
  function ppsgen_hide_inactive_mode_admin_menu(): void
  {
    if (!is_admin()) {
      return;
    }
    if (ppsgen_is_legacy_active()) {
      remove_menu_page('ppsgen');
      // The page still opens (Classic PopUp links to it), but without its menu entry WordPress finds no title for
      // it and passes null to strip_tags() in admin-header.php (deprecated since PHP 8.1)
      add_action('current_screen', function () {
        global $title;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only reads which screen is open
        if (empty($title) && isset($_GET['page']) && $_GET['page'] === 'ppsgen') {
          // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the admin page title WordPress lacks here
          $title = __('Customer Engagement System', 'ppsgen');
        }
      });
      return;
    }
    if (ppsgen_is_legacy_user()) {
      remove_menu_page('popup-wp-supsystic');
    }
  }
}

// ECA background tasks. The schedules are always registered, not only when the ECA module is loaded:
// WordPress drops a recurring event whose schedule it does not know, which used to wipe every plugin task
// on the first cron run without PRO (license 'none'). Free and PRO (ecaExt) run the same tasks under their own hooks.
if (!function_exists('ppsgen_get_eca_cron_map')) {
  function ppsgen_get_eca_cron_map(): array
  {
    return [
      'free' => [
        'ppsgen_cron_eca_runEcaFlowCron' => ['ppsgen_eca_flow', 55, 'ECA Flow', 0],
        'ppsgen_cron_eca_runEcaApplyChainToContactsCron' => ['ppsgen_eca_chain', 50, 'ECA Apply Campaign', 1],
        'ppsgen_cron_eca_runEcaSendEmailCron' => ['ppsgen_eca_email', 60, 'ECA Send Email', 2],
        'ppsgen_cron_eca_runEcaAutoBounceCron' => ['ppsgen_eca_auto_bounce', 60, 'ECA Auto Bounce', 3],
      ],
      'pro' => [
        'ppsgen_cron_ecaExt_runEcaFlowCron' => ['ppsgen_eca_pro_flow', 55, 'ECA PRO Flow', 0],
        'ppsgen_cron_ecaExt_runEcaApplyChainToContactsCron' => ['ppsgen_eca_pro_chain', 50, 'ECA PRO Apply Chain', 1],
        'ppsgen_cron_ecaExt_runEcaSendEmailCron' => ['ppsgen_eca_pro_email', 60, 'ECA PRO Send Email', 2],
        'ppsgen_cron_ecaExt_runEcaAutoBounceCron' => ['ppsgen_eca_pro_auto_bounce', 60, 'ECA PRO Auto Bounce', 3],
      ],
    ];
  }
}

if (!function_exists('ppsgen_register_eca_cron_schedules')) {
  function ppsgen_register_eca_cron_schedules($schedules)
  {
    foreach (ppsgen_get_eca_cron_map() as $hooks) {
      foreach ($hooks as $hook) {
        if (!isset($schedules[$hook[0]])) {
          $schedules[$hook[0]] = ['interval' => $hook[1], 'display' => $hook[2]];
        }
      }
    }
    return $schedules;
  }
}

// Keeps exactly one set of tasks scheduled: PRO's while a PRO license is present (active, grace or frozen),
// Free's otherwise. Runs on every request, so tasks come back without anyone opening the admin.
if (!function_exists('ppsgen_repair_eca_cron_events')) {
  function ppsgen_repair_eca_cron_events(): void
  {
    if (!function_exists('wp_next_scheduled')) {
      return;
    }
    $map = ppsgen_get_eca_cron_map();
    $usePro = ppsgen_get_pro_license_state() !== 'none';
    foreach ($map[$usePro ? 'free' : 'pro'] as $hookName => $hook) {
      if (wp_next_scheduled($hookName)) {
        wp_clear_scheduled_hook($hookName);
      }
    }
    foreach ($map[$usePro ? 'pro' : 'free'] as $hookName => $hook) {
      if (!wp_next_scheduled($hookName)) {
        wp_schedule_event(time() + $hook[3], $hook[0], $hookName);
      }
    }
  }
}
