<?php

if (!defined('ABSPATH')) {
  exit();
}

final class InternalApiPpsgen
{
  private static ?self $instance = null;

  private function __construct() {}
  private function __clone() {}
  public function __wakeup()
  {
    throw new \Exception('Not allowed');
  }

  /**
   * Singleton accessor
   */
  public static function _(): self
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function getEcaModel()
  {
    if (class_exists('FramePpsgen') && method_exists('FramePpsgen', 'getInstance') && FramePpsgen::getInstance() === null) {
      FramePpsgen::_init(true);
    }
    if (!class_exists('FramePpsgen') || !method_exists('FramePpsgen', 'getInstance')) {
      return null;
    }
    $frame = FramePpsgen::getInstance();
    if (empty($frame) || !$frame->useModule('eca')) {
      return null;
    }
    return $frame->getModule('eca')->getModel();
  }

  public function setEcaConversion(array $data)
  {
    $model = $this->getEcaModel();
    if (empty($model) || !method_exists($model, 'setEcaConversion')) {
      return false;
    }
    return $model->setEcaConversion($data);
  }

  public function getEcaBrowserAttribution($token = null, int $windowDays = 180)
  {
    $model = $this->getEcaModel();
    if (empty($model) || !method_exists($model, 'getEcaBrowserAttribution')) {
      return false;
    }
    return $model->getEcaBrowserAttribution($token, $windowDays);
  }

  public function getContactEcaAttribution($emailOrContactId, array $args = []): array
  {
    $model = $this->getEcaModel();
    if (empty($model) || !method_exists($model, 'getContactEcaAttribution')) {
      return [];
    }
    return $model->getContactEcaAttribution($emailOrContactId, $args);
  }

  public function getContactTagsByEmail(string $email): array
  {
    global $wpdb;
    $email = sanitize_email($email);
    if ($email === '') {
      return [];
    }
    $contactFieldId = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email'");
    if (empty($contactFieldId)) {
      return false;
    }
    $contactId = $wpdb->get_var($wpdb->prepare("SELECT contact_id FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE field_id = %d AND value = %s", (int) $contactFieldId, $email));
    if (empty($contactId)) {
      return false;
    }
    $contactTags = $wpdb->get_col($wpdb->prepare("SELECT tag_id FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d", (int) $contactId));
    if (empty($contactTags)) {
      return [];
    }
    $placeholders = implode(',', array_fill(0, count($contactTags), '%d'));
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id, slug FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id IN ($placeholders)", ...array_map('intval', $contactTags)), ARRAY_A);
    $result = [];
    foreach ($rows as $row) {
      $result[(int) $row['id']] = $row['slug'];
    }
    return $result;
  }

  public function deleteContactTagsByEmail(string $email, array $tagsSlug): bool
  {
    global $wpdb;
    $email = sanitize_email($email);
    if ($email === '' || empty($tagsSlug)) {
      return false;
    }
    // contact_id
    $contactFieldId = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email'");
    if (empty($contactFieldId)) {
      return false;
    }
    $contactId = $wpdb->get_var($wpdb->prepare("SELECT contact_id FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE field_id = %d AND value = %s", (int) $contactFieldId, $email));
    if (empty($contactId)) {
      return false;
    }
    // SLUG → ID
    $placeholders = implode(',', array_fill(0, count($tagsSlug), '%s'));
    $tagIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug IN ($placeholders)", ...array_map('sanitize_key', $tagsSlug)));
    if (empty($tagIds)) {
      return false;
    }
    foreach ($tagIds as $tagId) {
      $wpdb->delete("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => (int) $contactId, 'tag_id' => (int) $tagId], ['%d', '%d']);
    }
    do_action('ppsgen_contact_updated');
    return true;
  }

  public function addContactTagsByEmail(string $email, array $tagsSlug): bool
  {
    global $wpdb;
    $email = sanitize_email($email);
    if ($email === '' || empty($tagsSlug)) {
      return false;
    }
    $contactFieldId = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email'");
    if (empty($contactFieldId)) {
      return false;
    }
    $contactId = $wpdb->get_var($wpdb->prepare("SELECT contact_id FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE field_id = %d AND value = %s", (int) $contactFieldId, $email));
    if (empty($contactId)) {
      $res = $wpdb->insert("{$wpdb->prefix}ppsgen_contacts", ['wp_user_id' => 0, 'verified' => 0, 'cron_status' => 'waiting', 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')]);
      if (!empty($res)) {
        $contactId = (int) $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}ppsgen_contact_field_values", ['contact_id' => $contactId, 'field_id' => $contactFieldId, 'value' => $email]);
      } else {
        return false;
      }
    }
    $tagsSlug = array_unique(array_filter(array_map([$this, 'normalizeTagSlug'], $tagsSlug)));
    $placeholders = implode(',', array_fill(0, count($tagsSlug), '%s'));
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id, slug FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug IN ($placeholders)", ...$tagsSlug), ARRAY_A);
    $existing = [];
    foreach ($rows as $row) {
      $existing[$row['slug']] = (int) $row['id'];
    }
    foreach ($tagsSlug as $slug) {
      if (!isset($existing[$slug])) {
        $wpdb->insert(
          "{$wpdb->prefix}ppsgen_contact_tags",
          [
            'slug' => $slug,
            'order_index' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
          ],
          ['%s', '%d', '%s', '%s'],
        );
        $existing[$slug] = (int) $wpdb->insert_id;
      }
    }
    foreach ($existing as $tagId) {
      $exists = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id = %d", $contactId, $tagId));
      if (!$exists) {
        $wpdb->insert("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => $contactId, 'tag_id' => $tagId], ['%d', '%d']);
      }
    }
    do_action('ppsgen_contact_updated');
    return true;
  }
  private function normalizeTagSlug(string $slug): string
  {
    $slug = strtolower($slug);
    $slug = preg_replace('/\s+/', '_', $slug);
    $slug = preg_replace('/[^a-z0-9_]/', '', $slug);
    $slug = preg_replace('/_+/', '_', $slug);
    return trim($slug, '_');
  }
}
