<?php
// "Who sees it" for popups and forms: recognise the visitor as a contact and match them against the audience rule.
// The visitor is recognised automatically, strongest signal first:
//  1. the logged-in WordPress user (the contact linked to the user, or the one with the same email),
//  2. the visitor cookie, set when they submit a form or click a link in one of our emails
//     (ppsgen.frontend.js mirrors it in localStorage and restores the cookie if it was cleared),
//  3. the email-click attribution cookie (ppsgen_eca_attribution),
//  4. the IP address of a recent form submit or email click, only when exactly one contact used that IP.
// The tokens only carry a signed contact ID, never an email address.
if (!defined('ABSPATH')) {
  exit();
}

if (!defined('PPSGEN_VISITOR_COOKIE')) {
  define('PPSGEN_VISITOR_COOKIE', 'ppsgen_visitor');
}
if (!defined('PPSGEN_VISITOR_DAYS')) {
  define('PPSGEN_VISITOR_DAYS', 365);
}
if (!defined('PPSGEN_VISITOR_IP_DAYS')) {
  define('PPSGEN_VISITOR_IP_DAYS', 30);
}

if (!function_exists('ppsgen_build_visitor_token')) {
  function ppsgen_build_visitor_token(int $contactId): string
  {
    $payload = $contactId . '|' . time();
    return rtrim(strtr(base64_encode($payload . '|' . hash_hmac('sha256', $payload, wp_salt('auth'))), '+/', '-_'), '=');
  }
}

if (!function_exists('ppsgen_parse_visitor_token')) {
  function ppsgen_parse_visitor_token(string $token): int
  {
    $decoded = base64_decode(strtr(trim($token), '-_', '+/'), true);
    if (!$decoded) {
      return 0;
    }
    $parts = explode('|', $decoded);
    if (count($parts) !== 3) {
      return 0;
    }
    [$contactId, $createdAt, $signature] = $parts;
    if (!hash_equals(hash_hmac('sha256', $contactId . '|' . $createdAt, wp_salt('auth')), $signature)) {
      return 0;
    }
    if ((int) $createdAt < time() - DAY_IN_SECONDS * PPSGEN_VISITOR_DAYS) {
      return 0;
    }
    return (int) $contactId;
  }
}

if (!function_exists('ppsgen_visitor_contact_exists')) {
  function ppsgen_visitor_contact_exists(int $contactId): bool
  {
    global $wpdb;
    if ($contactId <= 0) {
      return false;
    }
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE id = %d AND deleted_at IS NULL", $contactId));
  }
}

if (!function_exists('ppsgen_get_contact_id_by_email')) {
  function ppsgen_get_contact_id_by_email(string $email): int
  {
    global $wpdb;
    $email = sanitize_email($email);
    if ($email === '') {
      return 0;
    }
    return (int) $wpdb->get_var($wpdb->prepare(
      "SELECT v.contact_id FROM {$wpdb->prefix}ppsgen_contact_field_values v
        INNER JOIN {$wpdb->prefix}ppsgen_contact_fields f ON f.id = v.field_id AND f.slug = 'email'
        INNER JOIN {$wpdb->prefix}ppsgen_contacts c ON c.id = v.contact_id AND c.deleted_at IS NULL
        WHERE v.value = %s ORDER BY v.contact_id DESC LIMIT 1",
      $email
    ));
  }
}

// Remembers the visitor in this browser (cookie now, localStorage on the next page view)
if (!function_exists('ppsgen_remember_visitor')) {
  function ppsgen_remember_visitor(int $contactId): string
  {
    if ($contactId <= 0) {
      return '';
    }
    $token = ppsgen_build_visitor_token($contactId);
    if (!headers_sent()) {
      $cookieArgs = [
        'expires' => time() + DAY_IN_SECONDS * PPSGEN_VISITOR_DAYS,
        'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
        'secure' => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
      ];
      if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
        $cookieArgs['domain'] = COOKIE_DOMAIN;
      }
      setcookie(PPSGEN_VISITOR_COOKIE, $token, $cookieArgs);
    }
    $_COOKIE[PPSGEN_VISITOR_COOKIE] = $token;
    ppsgen_get_visitor_contact(true);
    return $token;
  }
}

if (!function_exists('ppsgen_get_visitor_contact_by_ip')) {
  function ppsgen_get_visitor_contact_by_ip(string $ip): int
  {
    global $wpdb;
    if ($ip === '' || !apply_filters('ppsgen_visitor_match_ip', true)) {
      return 0;
    }
    $since = gmdate('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS * PPSGEN_VISITOR_IP_DAYS);
    $ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
      "SELECT DISTINCT contact_id FROM (
        SELECT contact_id FROM {$wpdb->prefix}ppsgen_form_submits WHERE ip = %s AND contact_id > 0 AND created_date >= %s
        UNION
        SELECT contact_id FROM {$wpdb->prefix}ppsgen_eca_step_events WHERE ip_address = %s AND contact_id > 0 AND event_type = 'click' AND is_suspicious = 0 AND created_at >= %s
      ) recent LIMIT 2",
      $ip,
      $since,
      $ip,
      $since
    )));
    // A shared network (office, café, mobile carrier) is never a match
    return count($ids) === 1 && ppsgen_visitor_contact_exists($ids[0]) ? $ids[0] : 0;
  }
}

// ['id' => contact ID or 0, 'source' => wp_user | cookie | email_link | ip | '']
if (!function_exists('ppsgen_get_visitor_contact')) {
  function ppsgen_get_visitor_contact(bool $refresh = false): array
  {
    static $visitor = null;
    if ($visitor !== null && !$refresh) {
      return $visitor;
    }
    $visitor = ['id' => 0, 'source' => ''];
    global $wpdb;

    if (function_exists('is_user_logged_in') && is_user_logged_in()) {
      $user = wp_get_current_user();
      $contactId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE wp_user_id = %d AND deleted_at IS NULL ORDER BY id DESC LIMIT 1", (int) $user->ID));
      if ($contactId <= 0 && !empty($user->user_email)) {
        $contactId = ppsgen_get_contact_id_by_email((string) $user->user_email);
      }
      if ($contactId > 0) {
        $visitor = ['id' => $contactId, 'source' => 'wp_user'];
        return $visitor;
      }
    }

    if (!empty($_COOKIE[PPSGEN_VISITOR_COOKIE])) {
      $contactId = ppsgen_parse_visitor_token(sanitize_text_field(wp_unslash($_COOKIE[PPSGEN_VISITOR_COOKIE])));
      if (ppsgen_visitor_contact_exists($contactId)) {
        $visitor = ['id' => $contactId, 'source' => 'cookie'];
        return $visitor;
      }
    }

    if (!empty($_COOKIE['ppsgen_eca_attribution'])) {
      $frame = class_exists('FramePpsgen') ? FramePpsgen::getInstance() : null;
      $eca = $frame ? $frame->getModule('eca', 'frontend') : null;
      $attribution = $eca ? $eca->getModel()->parseEcaAttributionToken(sanitize_text_field(wp_unslash($_COOKIE['ppsgen_eca_attribution']))) : false;
      $contactId = is_array($attribution) ? (int) $attribution['contact_id'] : 0;
      if (ppsgen_visitor_contact_exists($contactId)) {
        $visitor = ['id' => $contactId, 'source' => 'email_link'];
        return $visitor;
      }
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $contactId = ppsgen_get_visitor_contact_by_ip($ip);
    if ($contactId > 0) {
      $visitor = ['id' => $contactId, 'source' => 'ip'];
    }
    return $visitor;
  }
}

//=====================================
//       AUDIENCE RULES
//=====================================

if (!function_exists('ppsgen_get_audience_rules')) {
  function ppsgen_get_audience_rules(): array
  {
    return [
      'everyone' => __('Everyone', 'ppsgen'),
      'unknown' => __('Only people who are not contacts yet', 'ppsgen'),
      'known' => __('Only people who are already contacts', 'ppsgen'),
      'in_list' => __('Only contacts on a list', 'ppsgen'),
      'not_in_list' => __('Everyone except contacts on a list', 'ppsgen'),
      'with_tags' => __('Only contacts with a tag', 'ppsgen'),
      'without_tags' => __('Everyone except contacts with a tag', 'ppsgen'),
    ];
  }
}

if (!function_exists('ppsgen_contact_has_any_tag')) {
  function ppsgen_contact_has_any_tag(int $contactId, array $tagIds): bool
  {
    global $wpdb;
    $tagIds = array_values(array_filter(array_map('intval', $tagIds)));
    if ($contactId <= 0 || empty($tagIds)) {
      return false;
    }
    $placeholders = implode(',', array_fill(0, count($tagIds), '%d'));
    return (bool) $wpdb->get_var($wpdb->prepare(
      "SELECT 1 FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id IN ({$placeholders}) LIMIT 1",
      array_merge([$contactId], $tagIds)
    ));
  }
}

if (!function_exists('ppsgen_contact_in_saved_list')) {
  function ppsgen_contact_in_saved_list(int $contactId, string $listId): bool
  {
    if ($contactId <= 0 || $listId === '') {
      return false;
    }
    $frame = class_exists('FramePpsgen') ? FramePpsgen::getInstance() : null;
    $contact = $frame ? $frame->getModule('contact', 'frontend') : null;
    return $contact ? $contact->getModel()->contactMatchesSavedList($contactId, $listId) : false;
  }
}

// Settings keys: audience, audience_list_id, audience_tags[] (popup and form settings alike)
if (!function_exists('ppsgen_visitor_matches_audience')) {
  function ppsgen_visitor_matches_audience($settings): bool
  {
    $rule = is_array($settings) ? (string) ($settings['audience'] ?? '') : '';
    if ($rule === '' || $rule === 'everyone' || !array_key_exists($rule, ppsgen_get_audience_rules())) {
      return true;
    }
    $contactId = (int) ppsgen_get_visitor_contact()['id'];
    switch ($rule) {
      case 'unknown':
        $match = $contactId === 0;
        break;
      case 'known':
        $match = $contactId > 0;
        break;
      case 'in_list':
        $match = ppsgen_contact_in_saved_list($contactId, (string) ($settings['audience_list_id'] ?? ''));
        break;
      case 'not_in_list':
        $match = !ppsgen_contact_in_saved_list($contactId, (string) ($settings['audience_list_id'] ?? ''));
        break;
      case 'with_tags':
        $match = ppsgen_contact_has_any_tag($contactId, (array) ($settings['audience_tags'] ?? []));
        break;
      case 'without_tags':
        $match = !ppsgen_contact_has_any_tag($contactId, (array) ($settings['audience_tags'] ?? []));
        break;
      default:
        $match = true;
    }
    return (bool) apply_filters('ppsgen_visitor_matches_audience', $match, $rule, $settings, $contactId);
  }
}
