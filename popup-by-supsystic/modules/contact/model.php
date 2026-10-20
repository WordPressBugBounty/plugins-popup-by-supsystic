<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactModelPpsgen extends BaseModelPpsgen
{
  protected array $autoImportedWooOrderIds = [];

  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    if (!$this->getFrame()->core('installer')->exist('ppsgen_contacts')) {
      $tableName = $wpdb->prefix . 'ppsgen_contacts';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `wp_user_id` BIGINT(20) UNSIGNED NULL,
        `verified` TINYINT(1) NOT NULL DEFAULT 0,
        `cron_status` ENUM('waiting','processing','done') NOT NULL DEFAULT 'waiting',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `idx_user` (`wp_user_id`)
      ) $charset;";
      dbDelta($sql);
    }
  }

  public function updateOptions($data, $group = '')
  {
    global $wpdb;
    if (!empty($data)) {
      foreach ($data as $slug => $module) {
        if (!empty($group)) {
          $wpdb->update("{$wpdb->prefix}ppsgen_options", ['option_value' => ''], ['option_group' => $group, 'mod_slug' => $slug, 'mod_obj_id' => 0]);
        }
        foreach ($module as $keyOpt => $keyVal) {
          $this->updateOption($keyOpt, $group, $keyVal, 0, $slug, 'json');
        }
      }
      return true;
    }
  }

  public function getEcaAutocheckDetectionDefaults(): array
  {
    return [
      'eca_autocheck_tag_ids' => [],
      'eca_autocheck_tag_slugs' => 'autocheck_detected',
    ];
  }

  public function sanitizeContactOptionsPayload(array $data): array
  {
    if (empty($data['contact']) || !is_array($data['contact'])) {
      return $data;
    }
    $defaults = $this->getEcaAutocheckDetectionDefaults();
    $contactOptions = $data['contact'];
    $selectedTagIds = !empty($contactOptions['eca_autocheck_tag_ids']) && is_array($contactOptions['eca_autocheck_tag_ids'])
      ? $contactOptions['eca_autocheck_tag_ids']
      : [];
    $contactOptions['eca_autocheck_tag_ids'] = $this->filterExistingContactTagIds($selectedTagIds);
    $contactOptions['eca_autocheck_tag_slugs'] = $this->sanitizeContactTagSlugsInput(
      !empty($contactOptions['eca_autocheck_tag_slugs']) ? (string) $contactOptions['eca_autocheck_tag_slugs'] : '',
      (string) $defaults['eca_autocheck_tag_slugs']
    );
    $data['contact'] = $contactOptions;
    return $data;
  }

  public function getEcaAutocheckDetectionTagIds(): array
  {
    $defaults = $this->getEcaAutocheckDetectionDefaults();
    $selectedTagIds = $this->getOption('eca_autocheck_tag_ids', 'options', 0, 'contact');
    $selectedTagIds = is_array($selectedTagIds) ? $selectedTagIds : [];
    $resolvedTagIds = $this->filterExistingContactTagIds($selectedTagIds);
    $tagSlugs = $this->getOption('eca_autocheck_tag_slugs', 'options', 0, 'contact');
    $tagSlugs = is_string($tagSlugs) ? $tagSlugs : '';
    foreach ($this->parseContactTagSlugsInput($tagSlugs, (string) $defaults['eca_autocheck_tag_slugs']) as $tagSlug) {
      $tagId = $this->getOrCreateContactTagIdBySlug($tagSlug);
      if ($tagId > 0) {
        $resolvedTagIds[] = $tagId;
      }
    }
    return array_values(array_unique(array_filter(array_map('intval', $resolvedTagIds))));
  }

  protected function filterExistingContactTagIds(array $tagIds): array
  {
    global $wpdb;
    $tagIds = array_values(array_unique(array_filter(array_map('absint', $tagIds))));
    if (empty($tagIds)) {
      return [];
    }
    $placeholders = implode(',', array_fill(0, count($tagIds), '%d'));
    return array_map('intval', (array) $wpdb->get_col(
      $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id IN ($placeholders)",
        ...$tagIds
      )
    ));
  }

  protected function sanitizeContactTagSlugsInput(string $tagSlugs, string $fallbackSlug = ''): string
  {
    $slugs = $this->parseContactTagSlugsInput($tagSlugs, $fallbackSlug);
    return implode(', ', $slugs);
  }

  protected function parseContactTagSlugsInput(string $tagSlugs, string $fallbackSlug = ''): array
  {
    if ($tagSlugs === '' && $fallbackSlug !== '') {
      $tagSlugs = $fallbackSlug;
    }
    if ($tagSlugs === '') {
      return [];
    }
    $parts = preg_split('/[\r\n,]+/', $tagSlugs);
    if (!is_array($parts)) {
      return [];
    }
    return array_values(array_unique(array_filter(array_map(static function ($slug) {
      return sanitize_key((string) $slug);
    }, $parts))));
  }

  protected function getOrCreateContactTagIdBySlug(string $tagSlug): int
  {
    global $wpdb;
    $tagSlug = sanitize_key($tagSlug);
    if ($tagSlug === '') {
      return 0;
    }
    $tagId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug = %s", $tagSlug));
    if ($tagId > 0) {
      return $tagId;
    }
    $inserted = $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags", ['slug' => $tagSlug], ['%s']);
    if (empty($inserted)) {
      return 0;
    }
    return (int) $wpdb->insert_id;
  }

  public function getAllowedDynamicFieldSlugs(): array
  {
    global $wpdb;
    $results = $wpdb->get_results("SELECT slug, id FROM {$wpdb->prefix}ppsgen_contact_fields", OBJECT_K);
    $slugs = [];
    foreach ($results as $slug => $row) {
      $slugs[$slug] = (int) $row->id;
    }
    return $slugs;
  }

  public function getContacts(array $args): array
  {
    global $wpdb;
    // Get all allowed dynamic fields: [slug => id]
    $allDynamicFields = $this->getAllowedDynamicFieldSlugs();
    // Default arguments
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'c.id',
      'order_dir' => 'ASC',
      'columns' => ['c.id', 'c.wp_user_id', 'c.verified', 'c.created_at', 'tag_titles'],
      'filters' => [],
      'dynamic_fields' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $page = max(1, (int) $args['page']);
    $limit = max(1, (int) $args['per_page']);
    $offset = ($page - 1) * $limit;
    // Static fields allowed for ordering
    $staticOrderFields = ['c.id', 'c.wp_user_id', 'c.verified', 'c.created_at', 'c.updated_at'];
    $virtualOrderFields = ['contact_activity_at'];
    $validatedDynamicFields = [];
    // Validate dynamic fields for selection and ordering
    if (!empty($args['dynamic_fields']) && is_array($args['dynamic_fields'])) {
      foreach ($args['dynamic_fields'] as $slug) {
        if (isset($allDynamicFields[$slug]) && preg_match('/^[a-zA-Z0-9_\-]+$/', $slug)) {
          $validatedDynamicFields[] = $slug;
        }
      }
    }
    // Allowed order fields without backticks (for validation)
    $allowedOrderFieldsRaw = array_merge($staticOrderFields, $virtualOrderFields, $validatedDynamicFields);
    $requestedOrderBy = (string) $args['order_by'];
    $activityOrderSql = ['select' => '', 'joins' => ''];
    if ($requestedOrderBy === 'contact_activity_at') {
      $activityOrderSql = $this->getContactActivityOrderSql();
      $orderBy = 'contact_activity_at';
    } elseif (in_array($requestedOrderBy, $allowedOrderFieldsRaw, true)) {
      $orderBy = in_array($requestedOrderBy, $staticOrderFields, true) ? $requestedOrderBy : "`$requestedOrderBy`";
    } else {
      $orderBy = 'c.id';
    }
    $orderDir = strtoupper($args['order_dir']) === 'DESC' ? 'DESC' : 'ASC';
    // Build WHERE clauses and values
    $where = ['1=1'];
    $where_values = [];
    if (!empty($args['filters']['id'])) {
      $where[] = 'c.id = %d';
      $where_values[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['wp_user_id'])) {
      $where[] = 'c.wp_user_id = %d';
      $where_values[] = (int) $args['filters']['wp_user_id'];
    }
    if (!empty($args['filters']['verified'])) {
      $val = $args['filters']['verified'] === 'x' ? 0 : 1;
      $where[] = 'c.verified = %d';
      $where_values[] = $val;
    }
    if (!empty($args['filters']['created_at'])) {
      $where[] = 'DATE(c.created_at) = %s';
      $where_values[] = sanitize_text_field($args['filters']['created_at']);
    }
    $selectedTagIds = [];
    if (!empty($args['filters']['dynamic']['tag_titles'])) {
      $selectedTagIds = $args['filters']['dynamic']['tag_titles'];
      if (!is_array($selectedTagIds)) {
        $selectedTagIds = [$selectedTagIds];
      }
      $selectedTagIds = array_values(array_unique(array_filter(array_map('intval', $selectedTagIds))));
      if (!empty($selectedTagIds)) {
        $tagPlaceholders = implode(',', array_fill(0, count($selectedTagIds), '%d'));
        $where[] = "c.id IN (
          SELECT ctt_filter.contact_id
          FROM {$wpdb->prefix}ppsgen_contact_to_tags AS ctt_filter
          WHERE ctt_filter.tag_id IN ($tagPlaceholders)
          GROUP BY ctt_filter.contact_id
          HAVING COUNT(DISTINCT ctt_filter.tag_id) = %d
        )";
        $where_values = array_merge($where_values, $selectedTagIds, [count($selectedTagIds)]);
      }
    }
    // Prepare dynamic SELECT columns with placeholders and values
    $dynamicSelectParts = [];
    $dynamic_values = [];
    foreach ($validatedDynamicFields as $slug) {
      $dynamicSelectParts[] = "MAX(CASE WHEN f.slug = %s THEN v.value END) AS `$slug`";
      $dynamic_values[] = $slug;
    }
    $dynamicSelect = $dynamicSelectParts ? ', ' . implode(', ', $dynamicSelectParts) : '';
    // Prepare HAVING conditions and values
    $having = [];
    $having_values = [];
    if (!empty($args['filters']['dynamic']) && is_array($args['filters']['dynamic'])) {
      foreach ($args['filters']['dynamic'] as $slug => $value) {
        if ($value !== '' && in_array($slug, $validatedDynamicFields, true)) {
          $having[] = "`$slug` LIKE %s";
          $having_values[] = '%' . $wpdb->esc_like($value) . '%';
        }
      }
    }
    $whereSql = implode(' AND ', $where);
    $havingSql = $having ? 'HAVING ' . implode(' AND ', $having) : '';
    $params = array_merge($dynamic_values, $where_values, $having_values, [$limit, $offset]);
    $sql = "
        SELECT 
            c.id,
            c.created_at,
            c.updated_at,
            c.verified,
            c.wp_user_id,
            GROUP_CONCAT(DISTINCT t.slug SEPARATOR ', ') AS tag_titles
            $dynamicSelect
            {$activityOrderSql['select']}
        FROM {$wpdb->prefix}ppsgen_contacts AS c
        LEFT JOIN {$wpdb->prefix}ppsgen_contact_to_tags AS ct ON ct.contact_id = c.id
        LEFT JOIN {$wpdb->prefix}ppsgen_contact_tags AS t ON t.id = ct.tag_id
        LEFT JOIN {$wpdb->prefix}ppsgen_contact_field_values AS v ON v.contact_id = c.id
        LEFT JOIN {$wpdb->prefix}ppsgen_contact_fields AS f ON f.id = v.field_id
        {$activityOrderSql['joins']}
        WHERE $whereSql
        GROUP BY c.id
        $havingSql
        ORDER BY $orderBy $orderDir
        LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    if (!empty($params)) {
      $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    } else {
      $rows = $wpdb->get_results($sql, ARRAY_A);
    }
    // phpcs:enable
    $totalParams = array_merge($dynamic_values, $where_values, $having_values);
    $sql = "
        SELECT COUNT(*) FROM (
            SELECT c.id,
                GROUP_CONCAT(DISTINCT t.slug SEPARATOR ', ') AS tag_titles
                $dynamicSelect
            FROM {$wpdb->prefix}ppsgen_contacts AS c
            LEFT JOIN {$wpdb->prefix}ppsgen_contact_to_tags AS ct ON ct.contact_id = c.id
            LEFT JOIN {$wpdb->prefix}ppsgen_contact_tags AS t ON t.id = ct.tag_id
            LEFT JOIN {$wpdb->prefix}ppsgen_contact_field_values AS v ON v.contact_id = c.id
            LEFT JOIN {$wpdb->prefix}ppsgen_contact_fields AS f ON f.id = v.field_id
            WHERE $whereSql
            GROUP BY c.id
            $havingSql
        ) AS subquery
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    if (!empty($totalParams)) {
      $total = $wpdb->get_var($wpdb->prepare($sql, ...$totalParams));
    } else {
      $total = $wpdb->get_var($sql);
    }
    // phpcs:enable
    return [
      'rows' => $rows,
      'total' => (int) $total,
    ];
  }

  protected function getContactActivityOrderSql(): array
  {
    global $wpdb;
    $fallbackDate = '1970-01-01 00:00:00';
    $selectParts = [];
    $joins = [];

    if ($this->getFrame()->useModule('eca')) {
      $joins[] = "LEFT JOIN (
        SELECT contact_id, MAX(created_at) AS last_event_at
        FROM {$wpdb->prefix}ppsgen_eca_step_events
        GROUP BY contact_id
      ) AS ecaEventSort ON ecaEventSort.contact_id = c.id";
      $joins[] = "LEFT JOIN (
        SELECT contact_id, MAX(updated_at) AS last_flow_at
        FROM {$wpdb->prefix}ppsgen_eca_flow
        GROUP BY contact_id
      ) AS ecaFlowSort ON ecaFlowSort.contact_id = c.id";
      $joins[] = "LEFT JOIN (
        SELECT contact_id, MAX(run_at) AS last_log_at
        FROM {$wpdb->prefix}ppsgen_eca_flow_logs
        GROUP BY contact_id
      ) AS ecaLogSort ON ecaLogSort.contact_id = c.id";
      $selectParts[] = "COALESCE(MAX(ecaEventSort.last_event_at), '{$fallbackDate}')";
      $selectParts[] = "COALESCE(MAX(ecaFlowSort.last_flow_at), '{$fallbackDate}')";
      $selectParts[] = "COALESCE(MAX(ecaLogSort.last_log_at), '{$fallbackDate}')";

      if ($this->getFrame()->core('installer')->exist('ppsgen_eca_contact_incoming_emails')) {
        $joins[] = "LEFT JOIN (
          SELECT contact_id, MAX(received_at) AS last_incoming_email_at
          FROM {$wpdb->prefix}ppsgen_eca_contact_incoming_emails
          GROUP BY contact_id
        ) AS ecaIncomingSort ON ecaIncomingSort.contact_id = c.id";
        $selectParts[] = "COALESCE(MAX(ecaIncomingSort.last_incoming_email_at), '{$fallbackDate}')";
      }
    }

    if ($this->getFrame()->useModule('form')) {
      $joins[] = "LEFT JOIN (
        SELECT contact_id, MAX(created_date) AS last_submit_at
        FROM {$wpdb->prefix}ppsgen_form_submits
        GROUP BY contact_id
      ) AS formSubmitSort ON formSubmitSort.contact_id = c.id";
      $selectParts[] = "COALESCE(MAX(formSubmitSort.last_submit_at), '{$fallbackDate}')";
    }

    if (empty($selectParts)) {
      return [
        'select' => ", NULL AS contact_activity_at",
        'joins' => '',
      ];
    }

    $activitySql = count($selectParts) === 1 ? $selectParts[0] : 'GREATEST(' . implode(', ', $selectParts) . ')';

    return [
      'select' => ", NULLIF({$activitySql}, '{$fallbackDate}') AS contact_activity_at",
      'joins' => "\n        " . implode("\n        ", $joins),
    ];
  }

  public function deleteContact($cg_id)
  {
    global $wpdb;
    if (!empty($cg_id)) {
      if ($this->getFrame()->useModule('eca')) {
        $this->getFrame()->getModule('eca')->getModel()->deleteContactCascadeById((int) $cg_id);
      }
      $wpdb->delete("{$wpdb->prefix}ppsgen_contact_field_values", ['contact_id' => $cg_id]);
      $wpdb->delete("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => $cg_id]);
      $deleteSubmits = !empty($this->getOption('delete_related_form_submits', 'options', 0, 'contact')) ? $this->getOption('delete_related_form_submits', 'options', 0, 'contact') : true;
      if (!empty($deleteSubmits)) {
        $wpdb->delete("{$wpdb->prefix}ppsgen_form_submits", ['contact_id' => $cg_id]);
      }
      return $wpdb->delete('wp_ppsgen_contacts', ['id' => $cg_id]);
    }
    return false;
  }

  public function getFieldOptions(): array
  {
    global $wpdb;
    return $wpdb->get_results("SELECT slug, label FROM {$wpdb->prefix}ppsgen_contact_fields ORDER BY order_index", OBJECT_K);
  }

  public function getContactById($contact_id)
  {
    global $wpdb;
    if (!empty($contact_id)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_contacts WHERE " . $wpdb->prepare('id = %d', [$contact_id]), ARRAY_A);
    }
    return false;
  }

  public function getContactIdByUserId($user_id)
  {
    global $wpdb;
    if (!empty($user_id)) {
      return $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE " . $wpdb->prepare('wp_user_id = %d', [$user_id]));
    }
    return false;
  }

  public function getContactIdByEmail($email)
  {
    global $wpdb;
    if (!empty($email)) {
      $emailFieldId = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email'");
      if (!empty($emailFieldId)) {
        return $wpdb->get_var("SELECT contact_id FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE " . $wpdb->prepare('field_id = %d AND value = %s', [$emailFieldId, $email]));
      }
    }
    return false;
  }

  public function getContactLookupItemById(int $contactId): array
  {
    global $wpdb;
    $contactId = absint($contactId);
    if ($contactId <= 0) {
      return [];
    }
    $emailFieldId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email' LIMIT 1");
    $emailValue = '';
    if ($emailFieldId > 0) {
      $emailValue = (string) $wpdb->get_var($wpdb->prepare(
        "SELECT value
        FROM {$wpdb->prefix}ppsgen_contact_field_values
        WHERE contact_id = %d AND field_id = %d
        LIMIT 1",
        $contactId,
        $emailFieldId
      ));
    }
    $contactRow = $this->getContactById($contactId);
    if (empty($contactRow)) {
      return [];
    }
    return $this->buildContactLookupItem([
      'id' => $contactId,
      'email' => $emailValue,
      'wp_user_id' => (int) ($contactRow['wp_user_id'] ?? 0),
    ]);
  }

  public function searchContactLookupItems(string $query, int $limit = 10): array
  {
    global $wpdb;
    $query = trim(sanitize_text_field($query));
    $limit = max(1, min(10, absint($limit)));
    $queryLength = function_exists('mb_strlen') ? mb_strlen($query) : strlen($query);
    if ($query === '' || $queryLength < 2) {
      return [];
    }
    $emailFieldId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email' LIMIT 1");
    $queryLower = function_exists('mb_strtolower') ? mb_strtolower($query) : strtolower($query);
    $idLike = '%' . $wpdb->esc_like($query) . '%';
    $idPrefixLike = $wpdb->esc_like($query) . '%';
    $emailLike = '%' . $wpdb->esc_like($queryLower) . '%';
    $emailPrefixLike = $wpdb->esc_like($queryLower) . '%';
    $exactId = ctype_digit($query) ? absint($query) : 0;
    $sql = "
      SELECT lookup.id, lookup.wp_user_id, lookup.email
      FROM (
        SELECT c.id, c.wp_user_id,
          COALESCE((
            SELECT v.value
            FROM {$wpdb->prefix}ppsgen_contact_field_values AS v
            WHERE v.contact_id = c.id AND v.field_id = %d
            LIMIT 1
          ), '') AS email
        FROM {$wpdb->prefix}ppsgen_contacts AS c
      ) AS lookup
      WHERE CAST(lookup.id AS CHAR) LIKE %s
         OR LOWER(lookup.email) LIKE %s
      ORDER BY
        CASE
          WHEN %d > 0 AND lookup.id = %d THEN 0
          WHEN LOWER(lookup.email) = %s THEN 1
          WHEN LOWER(lookup.email) LIKE %s THEN 2
          WHEN CAST(lookup.id AS CHAR) LIKE %s THEN 3
          ELSE 4
        END,
        lookup.id ASC
      LIMIT %d
    ";
    $rows = $wpdb->get_results($wpdb->prepare(
      $sql,
      $emailFieldId,
      $idLike,
      $emailLike,
      $exactId,
      $exactId,
      $queryLower,
      $emailPrefixLike,
      $idPrefixLike,
      $limit
    ), ARRAY_A);
    if (empty($rows)) {
      return [];
    }
    return array_values(array_filter(array_map([$this, 'buildContactLookupItem'], $rows)));
  }

  protected function buildContactLookupItem(array $row): array
  {
    $contactId = absint($row['id'] ?? 0);
    if ($contactId <= 0) {
      return [];
    }
    $email = sanitize_email((string) ($row['email'] ?? ''));
    $label = $email !== ''
      ? sprintf(
        /* translators: 1: contact id, 2: contact email */
        __('(%1$d) %2$s', 'ppsgen'),
        $contactId,
        $email
      )
      : sprintf(
        /* translators: %d: contact id */
        __('Contact #%d', 'ppsgen'),
        $contactId
      );
    return [
      'id' => $contactId,
      'email' => $email,
      'label' => $label,
      'wp_user_id' => absint($row['wp_user_id'] ?? 0),
    ];
  }

  public function getContactIdByFieldSlugValue(string $slug, string $value)
  {
    global $wpdb;
    $slug = sanitize_key($slug);
    $value = is_string($value) ? trim($value) : '';
    if ($slug === '' || $value === '') {
      return false;
    }
    $fieldId = $this->getFrame()->useModule('contact', 'contact_field', 'model', 'getContactFieldIdBySlug', ['slug' => $slug]);
    if (empty($fieldId)) {
      return false;
    }
    return $wpdb->get_var($wpdb->prepare("SELECT contact_id FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE field_id = %d AND value = %s LIMIT 1", (int) $fieldId, $value));
  }

  public function getContactFieldValueBySlug(int $contact_id, string $slug): string
  {
    global $wpdb;
    if (empty($contact_id) || empty($slug)) {
      return '';
    }
    $value = $wpdb->get_var($wpdb->prepare(
      "SELECT v.value
      FROM {$wpdb->prefix}ppsgen_contact_field_values AS v
      INNER JOIN {$wpdb->prefix}ppsgen_contact_fields AS f ON f.id = v.field_id
      WHERE v.contact_id = %d AND f.slug = %s
      LIMIT 1",
      $contact_id,
      $slug
    ));
    return is_string($value) ? $value : '';
  }

  protected function getDefaultEngagementStats(): array
  {
    return [
      'score' => 0,
      'percent' => 0,
      'label' => 'Inactive',
      'activity_state' => 'No Flow',
      'last_activity_at' => '',
      'last_activity_text' => __('No activity yet', 'ppsgen'),
      'open_total' => 0,
      'open_suspicious_total' => 0,
      'open_live_total' => 0,
      'open_unique_steps' => 0,
      'click_total' => 0,
      'click_suspicious_total' => 0,
      'click_live_total' => 0,
      'click_unique_links' => 0,
      'event_total' => 0,
      'engaged_chains' => 0,
      'flows_total' => 0,
      'active_flows' => 0,
      'waiting_flows' => 0,
      'processing_flows' => 0,
      'finished_flows' => 0,
      'exited_flows' => 0,
      'unsubscribed_flows' => 0,
      'entries_total' => 0,
      'steps_success' => 0,
      'steps_finished' => 0,
      'steps_skipped' => 0,
      'condition_false' => 0,
      'errors_total' => 0,
      'logs_total' => 0,
      'total_submits' => 0,
      'forms_used' => 0,
      'last_submit_at' => '',
      'form_ids' => [],
      'incoming_email_total' => 0,
      'incoming_email_unhandled_total' => 0,
      'has_unhandled_incoming_email' => false,
      'last_incoming_email_at' => '',
      'conversion_total' => 0,
      'conversion_paid_total' => 0,
      'conversion_paid_value_label' => '0',
      'conversion_last_at' => '',
      'conversion_last_text' => __('No conversions yet', 'ppsgen'),
      'conversion_last_product_name' => '',
      'conversion_value_by_currency' => [],
      'woo_orders_total' => 0,
      'woo_total_spent' => 0.0,
      'woo_aov' => 0.0,
    ];
  }

  protected function finalizeEngagementStats(array $stats): array
  {
    $score = 0;
    $score += min((int) $stats['open_unique_steps'] * 6, 18);
    $score += min((int) $stats['click_unique_links'] * 14, 32);
    $score += min(max(0, (int) $stats['click_total'] - (int) $stats['click_unique_links']) * 2, 8);
    $score += min((int) $stats['total_submits'] * 10, 20);
    $score += min(((int) $stats['steps_success'] + (int) $stats['steps_finished']) * 2, 14);
    $score += min((int) $stats['active_flows'] * 4, 8);
    $score += min((int) $stats['finished_flows'] * 3, 9);
    if ((int) $stats['click_total'] > 0 && (int) $stats['total_submits'] > 0) {
      $score += 8;
    } elseif ((int) $stats['click_total'] > 0 && (int) $stats['open_total'] > 0) {
      $score += 4;
    }
    if ((int) ($stats['conversion_paid_total'] ?? 0) > 0) {
      $score += min(60 + (((int) $stats['conversion_paid_total'] - 1) * 5), 70);
    } elseif ((int) ($stats['conversion_total'] ?? 0) > 0) {
      $score += 12;
    }
    $score -= min((int) $stats['errors_total'] * 3, 12);
    $score -= min((int) $stats['condition_false'] * 2, 8);
    $score -= min((int) $stats['steps_skipped'], 4);
    $score -= min((int) $stats['exited_flows'] * 3, 9);
    if ((int) $stats['unsubscribed_flows'] > 0) {
      $score -= 25;
    }

    if (!empty($stats['last_activity_at'])) {
      $lastTs = strtotime((string) $stats['last_activity_at']);
      if (!empty($lastTs)) {
        $days = (int) floor((current_time('timestamp') - $lastTs) / DAY_IN_SECONDS);
        if ($days <= 3) {
          $score += 15;
        } elseif ($days <= 7) {
          $score += 10;
        } elseif ($days <= 14) {
          $score += 5;
        } elseif ($days <= 60) {
          $score -= 10;
        } elseif ($days > 60) {
          $score -= 20;
        }
        $stats['last_activity_text'] = sprintf(
          /* translators: %s: human readable time diff */
          __('%s ago', 'ppsgen'),
          human_time_diff($lastTs, current_time('timestamp')),
        );
      }
    }

    $score = max(0, min(100, (int) round($score)));
    $stats['score'] = $score;
    $stats['percent'] = $score;

    if ((int) $stats['active_flows'] > 0) {
      $stats['activity_state'] = __('Active Flow', 'ppsgen');
    } elseif ((int) $stats['finished_flows'] > 0) {
      $stats['activity_state'] = __('Finished', 'ppsgen');
    } elseif ((int) $stats['unsubscribed_flows'] > 0) {
      $stats['activity_state'] = __('Unsubscribed', 'ppsgen');
    } elseif ((int) $stats['exited_flows'] > 0) {
      $stats['activity_state'] = __('Exited', 'ppsgen');
    }

    if ((int) $stats['unsubscribed_flows'] > 0 && (int) $stats['active_flows'] === 0 && $score < 20) {
      $stats['label'] = __('Unsubscribed', 'ppsgen');
    } elseif ($score >= 75) {
      $stats['label'] = __('Hot', 'ppsgen');
    } elseif ($score >= 45) {
      $stats['label'] = __('Medium', 'ppsgen');
    } elseif ($score >= 20) {
      $stats['label'] = __('Cold', 'ppsgen');
    } else {
      $stats['label'] = __('Inactive', 'ppsgen');
    }

    return $stats;
  }

  protected function formatConversionValueSummary(array $valuesByCurrency): string
  {
    $parts = [];
    foreach ($valuesByCurrency as $currency => $value) {
      $value = (float) $value;
      if (abs($value) < 0.00001) {
        continue;
      }
      $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $currency)));
      $parts[] = trim(number_format_i18n($value, 2) . ' ' . $currency);
    }
    return !empty($parts) ? implode(' | ', $parts) : '0';
  }

  protected function appendEcaConversionEngagementStats(array $statsByContact, array $contactIds, int $chainId = 0): array
  {
    global $wpdb;
    if (empty($contactIds) || !$this->getFrame()->useModule('eca')) {
      return $statsByContact;
    }
    if (!$this->getFrame()->core('installer')->exist('ppsgen_eca_conversions')) {
      return $statsByContact;
    }

    $contactPlaceholders = implode(',', array_fill(0, count($contactIds), '%d'));
    $emailFieldId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email' LIMIT 1");
    $contactIdsByEmail = [];
    if ($emailFieldId > 0) {
      $emailRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT contact_id, value
          FROM {$wpdb->prefix}ppsgen_contact_field_values
          WHERE field_id = %d AND contact_id IN ($contactPlaceholders)",
          $emailFieldId,
          ...$contactIds,
        ),
        ARRAY_A,
      );
      foreach ($emailRows as $row) {
        $contactId = (int) ($row['contact_id'] ?? 0);
        $email = sanitize_email((string) ($row['value'] ?? ''));
        if ($contactId > 0 && $email !== '' && is_email($email)) {
          $contactIdsByEmail[strtolower($email)][] = $contactId;
        }
      }
    }

    $whereParts = ["contact_id IN ($contactPlaceholders)"];
    $params = $contactIds;
    $emails = array_keys($contactIdsByEmail);
    if (!empty($emails)) {
      $emailPlaceholders = implode(',', array_fill(0, count($emails), '%s'));
      $whereParts[] = "conversion_email IN ($emailPlaceholders)";
      $params = array_merge($params, $emails);
    }
    if ($chainId > 0) {
      $whereParts = array_map(static function ($where) {
        return '(' . $where . ')';
      }, $whereParts);
      $whereSql = '(' . implode(' OR ', $whereParts) . ') AND chain_id = %d';
      $params[] = $chainId;
    } else {
      $whereSql = implode(' OR ', $whereParts);
    }

    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT id, contact_id, conversion_email, status, value, currency, product_name, converted_at, created_at
        FROM {$wpdb->prefix}ppsgen_eca_conversions
        WHERE $whereSql
        ORDER BY converted_at DESC, id DESC",
        ...$params,
      ),
      ARRAY_A,
    );
    if (empty($rows)) {
      return $statsByContact;
    }

    $countedByContact = [];
    foreach ($rows as $row) {
      $matchedContactIds = [];
      $rowContactId = (int) ($row['contact_id'] ?? 0);
      if ($rowContactId > 0 && isset($statsByContact[$rowContactId])) {
        $matchedContactIds[] = $rowContactId;
      }
      $email = sanitize_email((string) ($row['conversion_email'] ?? ''));
      $emailKey = strtolower($email);
      if ($emailKey !== '' && !empty($contactIdsByEmail[$emailKey])) {
        $matchedContactIds = array_merge($matchedContactIds, $contactIdsByEmail[$emailKey]);
      }
      $matchedContactIds = array_values(array_unique(array_filter(array_map('intval', $matchedContactIds))));
      if (empty($matchedContactIds)) {
        continue;
      }

      $conversionId = (int) ($row['id'] ?? 0);
      foreach ($matchedContactIds as $contactId) {
        if (empty($statsByContact[$contactId]) || isset($countedByContact[$contactId][$conversionId])) {
          continue;
        }
        $countedByContact[$contactId][$conversionId] = true;

        $statsByContact[$contactId]['conversion_total']++;
        $status = sanitize_key((string) ($row['status'] ?? ''));
        if ($status === 'paid') {
          $statsByContact[$contactId]['conversion_paid_total']++;
          $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($row['currency'] ?? ''))));
          if (!isset($statsByContact[$contactId]['conversion_value_by_currency'][$currency])) {
            $statsByContact[$contactId]['conversion_value_by_currency'][$currency] = 0.0;
          }
          $statsByContact[$contactId]['conversion_value_by_currency'][$currency] += (float) ($row['value'] ?? 0);
        }

        $convertedAt = (string) ($row['converted_at'] ?? $row['created_at'] ?? '');
        if ($convertedAt !== '' && (empty($statsByContact[$contactId]['conversion_last_at']) || strtotime($convertedAt) > strtotime((string) $statsByContact[$contactId]['conversion_last_at']))) {
          $statsByContact[$contactId]['conversion_last_at'] = $convertedAt;
          $statsByContact[$contactId]['conversion_last_product_name'] = (string) ($row['product_name'] ?? '');
        }
        if ($convertedAt !== '' && (empty($statsByContact[$contactId]['last_activity_at']) || strtotime($convertedAt) > strtotime((string) $statsByContact[$contactId]['last_activity_at']))) {
          $statsByContact[$contactId]['last_activity_at'] = $convertedAt;
        }
      }
    }

    foreach ($contactIds as $contactId) {
      $contactId = (int) $contactId;
      if (empty($statsByContact[$contactId])) {
        continue;
      }
      $statsByContact[$contactId]['conversion_paid_value_label'] = $this->formatConversionValueSummary((array) ($statsByContact[$contactId]['conversion_value_by_currency'] ?? []));
      if (!empty($statsByContact[$contactId]['conversion_last_at'])) {
        $lastTs = strtotime((string) $statsByContact[$contactId]['conversion_last_at']);
        if (!empty($lastTs)) {
          $statsByContact[$contactId]['conversion_last_text'] = sprintf(
            __('%s ago', 'ppsgen'),
            human_time_diff($lastTs, current_time('timestamp')),
          );
        }
      }
    }

    return $statsByContact;
  }

  protected function appendWooCommerceEngagementStats(array $statsByContact, array $contactIds): array
  {
    global $wpdb;
    if (empty($contactIds) || !$this->isWooCommerceActive() || !function_exists('wc_get_orders')) {
      return $statsByContact;
    }

    $placeholders = implode(',', array_fill(0, count($contactIds), '%d'));
    $contactRows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT id, wp_user_id
        FROM {$wpdb->prefix}ppsgen_contacts
        WHERE id IN ($placeholders)",
        ...$contactIds,
      ),
      ARRAY_A,
    );

    $emailFieldId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email' LIMIT 1");
    $emailRows = [];
    if ($emailFieldId > 0) {
      $emailRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT contact_id, value
          FROM {$wpdb->prefix}ppsgen_contact_field_values
          WHERE field_id = %d AND contact_id IN ($placeholders)",
          $emailFieldId,
          ...$contactIds,
        ),
        ARRAY_A,
      );
    }

    $contactUsers = [];
    $contactEmails = [];
    $contactIdsByUser = [];
    $contactIdsByEmail = [];
    $customerFilters = [];

    foreach ($contactRows as $row) {
      $contactId = (int) ($row['id'] ?? 0);
      $wpUserId = (int) ($row['wp_user_id'] ?? 0);
      if ($contactId <= 0) {
        continue;
      }
      $contactUsers[$contactId] = $wpUserId;
      if ($wpUserId > 0) {
        $contactIdsByUser[$wpUserId][] = $contactId;
        $customerFilters['user_' . $wpUserId] = $wpUserId;
      }
    }

    foreach ($emailRows as $row) {
      $contactId = (int) ($row['contact_id'] ?? 0);
      $email = sanitize_email((string) ($row['value'] ?? ''));
      if ($contactId <= 0 || $email === '' || !is_email($email)) {
        continue;
      }
      $emailKey = strtolower($email);
      $contactEmails[$contactId] = $emailKey;
      $contactIdsByEmail[$emailKey][] = $contactId;
      $customerFilters['email_' . $emailKey] = $emailKey;
    }

    if (empty($customerFilters)) {
      return $statsByContact;
    }

    $orders = wc_get_orders([
      'customer' => array_values($customerFilters),
      'status' => array_keys(wc_get_order_statuses()),
      'limit' => -1,
      'return' => 'objects',
    ]);

    if (empty($orders) || !is_array($orders)) {
      return $statsByContact;
    }

    foreach ($orders as $order) {
      if (!is_object($order) || !method_exists($order, 'get_customer_id') || !method_exists($order, 'get_billing_email')) {
        continue;
      }

      $matchedContactIds = [];
      $customerId = absint($order->get_customer_id());
      if ($customerId > 0 && !empty($contactIdsByUser[$customerId])) {
        $matchedContactIds = array_merge($matchedContactIds, $contactIdsByUser[$customerId]);
      }

      $billingEmail = sanitize_email((string) $order->get_billing_email());
      if ($billingEmail !== '' && is_email($billingEmail)) {
        $emailKey = strtolower($billingEmail);
        if (!empty($contactIdsByEmail[$emailKey])) {
          $matchedContactIds = array_merge($matchedContactIds, $contactIdsByEmail[$emailKey]);
        }
      }

      $matchedContactIds = array_values(array_unique(array_filter(array_map('intval', $matchedContactIds))));
      if (empty($matchedContactIds)) {
        continue;
      }

      $orderTotal = (float) $order->get_total();
      foreach ($matchedContactIds as $contactId) {
        if (empty($statsByContact[$contactId])) {
          continue;
        }
        $statsByContact[$contactId]['woo_orders_total']++;
        $statsByContact[$contactId]['woo_total_spent'] += $orderTotal;
      }
    }

    foreach ($contactIds as $contactId) {
      $contactId = (int) $contactId;
      if (empty($statsByContact[$contactId])) {
        continue;
      }
      $wooOrdersTotal = (int) ($statsByContact[$contactId]['woo_orders_total'] ?? 0);
      $wooTotalSpent = (float) ($statsByContact[$contactId]['woo_total_spent'] ?? 0);
      $statsByContact[$contactId]['woo_total_spent'] = $wooTotalSpent;
      $statsByContact[$contactId]['woo_aov'] = $wooOrdersTotal > 0 ? round($wooTotalSpent / $wooOrdersTotal, 2) : 0.0;
    }

    return $statsByContact;
  }

  protected function appendIncomingMailboxEngagementStats(array $statsByContact, array $contactIds): array
  {
    if (empty($contactIds) || !$this->getFrame()->useModule('eca')) {
      return $statsByContact;
    }
    $ecaModel = $this->getFrame()->getModule('eca')->getModel();
    $incomingStats = $ecaModel->getIncomingEmailStatsByContactIds($contactIds);
    if (empty($incomingStats)) {
      return $statsByContact;
    }
    foreach ($incomingStats as $contactId => $incomingStat) {
      $contactId = (int) $contactId;
      if (empty($statsByContact[$contactId])) {
        continue;
      }
      $statsByContact[$contactId]['incoming_email_total'] = (int) ($incomingStat['incoming_email_total'] ?? 0);
      $statsByContact[$contactId]['incoming_email_unhandled_total'] = (int) ($incomingStat['incoming_email_unhandled_total'] ?? 0);
      $statsByContact[$contactId]['has_unhandled_incoming_email'] = !empty($incomingStat['has_unhandled_incoming_email']);
      $statsByContact[$contactId]['last_incoming_email_at'] = (string) ($incomingStat['last_incoming_email_at'] ?? '');
      $incomingAt = (string) ($incomingStat['last_incoming_email_at'] ?? '');
      if ($incomingAt !== '' && (empty($statsByContact[$contactId]['last_activity_at']) || strtotime($incomingAt) > strtotime((string) $statsByContact[$contactId]['last_activity_at']))) {
        $statsByContact[$contactId]['last_activity_at'] = $incomingAt;
      }
    }
    return $statsByContact;
  }

  public function getContactsEngagementStatsByIds(array $contactIds, array $args = []): array
  {
    global $wpdb;
    $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds))));
    if (empty($contactIds)) {
      return [];
    }
    $args = wp_parse_args($args, [
      'chain_id' => 0,
      'include_form_submits' => true,
      'include_incoming_mailbox' => true,
      'include_woocommerce' => true,
    ]);
    $chainId = max(0, (int) ($args['chain_id'] ?? 0));
    $includeFormSubmits = !empty($args['include_form_submits']);
    $includeIncomingMailbox = !empty($args['include_incoming_mailbox']);
    $includeWooCommerce = !empty($args['include_woocommerce']);

    $statsByContact = [];
    foreach ($contactIds as $contactId) {
      $statsByContact[$contactId] = $this->getDefaultEngagementStats();
    }

    $placeholders = implode(',', array_fill(0, count($contactIds), '%d'));
    if ($this->getFrame()->useModule('eca')) {
      $eventWhereSql = "contact_id IN ($placeholders)";
      $eventParams = $contactIds;
      if (!empty($chainId)) {
        $eventWhereSql .= ' AND chain_id = %d';
        $eventParams[] = $chainId;
      }
      $eventRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT 
            contact_id,
            COUNT(*) AS event_total,
            SUM(CASE WHEN event_type = 'open' THEN 1 ELSE 0 END) AS open_total,
            SUM(CASE WHEN event_type = 'open' AND is_suspicious = 1 THEN 1 ELSE 0 END) AS open_suspicious_total,
            SUM(CASE WHEN event_type = 'open' AND is_suspicious = 0 THEN 1 ELSE 0 END) AS open_live_total,
            COUNT(DISTINCT CASE WHEN event_type = 'open' THEN step_id END) AS open_unique_steps,
            SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) AS click_total,
            SUM(CASE WHEN event_type = 'click' AND is_suspicious = 1 THEN 1 ELSE 0 END) AS click_suspicious_total,
            SUM(CASE WHEN event_type = 'click' AND is_suspicious = 0 THEN 1 ELSE 0 END) AS click_live_total,
            COUNT(DISTINCT CASE WHEN event_type = 'click' THEN CONCAT(step_id, '|', clicked_url) END) AS click_unique_links,
            COUNT(DISTINCT chain_id) AS engaged_chains,
            MAX(created_at) AS last_event_at
          FROM {$wpdb->prefix}ppsgen_eca_step_events
          WHERE $eventWhereSql
          GROUP BY contact_id",
          ...$eventParams,
        ),
        ARRAY_A,
      );
      foreach ($eventRows as $row) {
        $contactId = (int) $row['contact_id'];
        if (empty($statsByContact[$contactId])) {
          continue;
        }
        $statsByContact[$contactId]['event_total'] = (int) $row['event_total'];
        $statsByContact[$contactId]['open_total'] = (int) $row['open_total'];
        $statsByContact[$contactId]['open_suspicious_total'] = (int) $row['open_suspicious_total'];
        $statsByContact[$contactId]['open_live_total'] = (int) $row['open_live_total'];
        $statsByContact[$contactId]['open_unique_steps'] = (int) $row['open_unique_steps'];
        $statsByContact[$contactId]['click_total'] = (int) $row['click_total'];
        $statsByContact[$contactId]['click_suspicious_total'] = (int) $row['click_suspicious_total'];
        $statsByContact[$contactId]['click_live_total'] = (int) $row['click_live_total'];
        $statsByContact[$contactId]['click_unique_links'] = (int) $row['click_unique_links'];
        $statsByContact[$contactId]['engaged_chains'] = (int) $row['engaged_chains'];
        $statsByContact[$contactId]['last_activity_at'] = (string) ($row['last_event_at'] ?? '');
      }

      $flowWhereSql = "contact_id IN ($placeholders)";
      $flowParams = $contactIds;
      if (!empty($chainId)) {
        $flowWhereSql .= ' AND chain_id = %d';
        $flowParams[] = $chainId;
      }
      $flowRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT 
            contact_id,
            COUNT(*) AS flows_total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_flows,
            SUM(CASE WHEN status = 'active' AND cron_status = 'waiting' THEN 1 ELSE 0 END) AS waiting_flows,
            SUM(CASE WHEN status = 'active' AND cron_status = 'processing' THEN 1 ELSE 0 END) AS processing_flows,
            SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) AS finished_flows,
            SUM(CASE WHEN status = 'exited' THEN 1 ELSE 0 END) AS exited_flows,
            SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) AS unsubscribed_flows,
            MAX(updated_at) AS last_flow_at
          FROM {$wpdb->prefix}ppsgen_eca_flow
          WHERE $flowWhereSql
          GROUP BY contact_id",
          ...$flowParams,
        ),
        ARRAY_A,
      );
      foreach ($flowRows as $row) {
        $contactId = (int) $row['contact_id'];
        if (empty($statsByContact[$contactId])) {
          continue;
        }
        $statsByContact[$contactId]['flows_total'] = (int) $row['flows_total'];
        $statsByContact[$contactId]['active_flows'] = (int) $row['active_flows'];
        $statsByContact[$contactId]['waiting_flows'] = (int) $row['waiting_flows'];
        $statsByContact[$contactId]['processing_flows'] = (int) $row['processing_flows'];
        $statsByContact[$contactId]['finished_flows'] = (int) $row['finished_flows'];
        $statsByContact[$contactId]['exited_flows'] = (int) $row['exited_flows'];
        $statsByContact[$contactId]['unsubscribed_flows'] = (int) $row['unsubscribed_flows'];
        if (empty($statsByContact[$contactId]['last_activity_at']) || (!empty($row['last_flow_at']) && strtotime((string) $row['last_flow_at']) > strtotime((string) $statsByContact[$contactId]['last_activity_at']))) {
          $statsByContact[$contactId]['last_activity_at'] = (string) ($row['last_flow_at'] ?? '');
        }
      }

      $logWhereSql = "contact_id IN ($placeholders)";
      $logParams = $contactIds;
      if (!empty($chainId)) {
        $logWhereSql .= ' AND chain_id = %d';
        $logParams[] = $chainId;
      }
      $logRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT
            contact_id,
            COUNT(*) AS logs_total,
            SUM(CASE WHEN status = 'entry' THEN 1 ELSE 0 END) AS entries_total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS steps_success,
            SUM(CASE WHEN status = 'success_finished' THEN 1 ELSE 0 END) AS steps_finished,
            SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) AS steps_skipped,
            SUM(CASE WHEN status = 'condition_false' THEN 1 ELSE 0 END) AS condition_false,
            SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS errors_total,
            MAX(run_at) AS last_log_at
          FROM {$wpdb->prefix}ppsgen_eca_flow_logs
          WHERE $logWhereSql
          GROUP BY contact_id",
          ...$logParams,
        ),
        ARRAY_A,
      );
      foreach ($logRows as $row) {
        $contactId = (int) $row['contact_id'];
        if (empty($statsByContact[$contactId])) {
          continue;
        }
        $statsByContact[$contactId]['logs_total'] = (int) $row['logs_total'];
        $statsByContact[$contactId]['entries_total'] = (int) $row['entries_total'];
        $statsByContact[$contactId]['steps_success'] = (int) $row['steps_success'];
        $statsByContact[$contactId]['steps_finished'] = (int) $row['steps_finished'];
        $statsByContact[$contactId]['steps_skipped'] = (int) $row['steps_skipped'];
        $statsByContact[$contactId]['condition_false'] = (int) $row['condition_false'];
        $statsByContact[$contactId]['errors_total'] = (int) $row['errors_total'];
        if (empty($statsByContact[$contactId]['last_activity_at']) || (!empty($row['last_log_at']) && strtotime((string) $row['last_log_at']) > strtotime((string) $statsByContact[$contactId]['last_activity_at']))) {
          $statsByContact[$contactId]['last_activity_at'] = (string) ($row['last_log_at'] ?? '');
        }
      }
    }

    if ($includeFormSubmits && $this->getFrame()->useModule('form')) {
      $submitRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT
            contact_id,
            COUNT(*) AS total_submits,
            COUNT(DISTINCT form_id) AS forms_used,
            GROUP_CONCAT(DISTINCT form_id) AS form_ids,
            MAX(created_date) AS last_submit_at
          FROM {$wpdb->prefix}ppsgen_form_submits
          WHERE contact_id IN ($placeholders)
          GROUP BY contact_id",
          ...$contactIds,
        ),
        ARRAY_A,
      );
      foreach ($submitRows as $row) {
        $contactId = (int) $row['contact_id'];
        if (empty($statsByContact[$contactId])) {
          continue;
        }
        $statsByContact[$contactId]['total_submits'] = (int) $row['total_submits'];
        $statsByContact[$contactId]['forms_used'] = (int) $row['forms_used'];
        $statsByContact[$contactId]['form_ids'] = !empty($row['form_ids']) ? array_values(array_filter(array_map('intval', explode(',', (string) $row['form_ids'])))) : [];
        $statsByContact[$contactId]['last_submit_at'] = (string) ($row['last_submit_at'] ?? '');
        if (empty($statsByContact[$contactId]['last_activity_at']) || (!empty($row['last_submit_at']) && strtotime((string) $row['last_submit_at']) > strtotime((string) $statsByContact[$contactId]['last_activity_at']))) {
          $statsByContact[$contactId]['last_activity_at'] = (string) ($row['last_submit_at'] ?? '');
        }
      }
    }

    if ($includeIncomingMailbox) {
      $statsByContact = $this->appendIncomingMailboxEngagementStats($statsByContact, $contactIds);
    }
    $statsByContact = $this->appendEcaConversionEngagementStats($statsByContact, $contactIds, $chainId);
    if ($includeWooCommerce) {
      $statsByContact = $this->appendWooCommerceEngagementStats($statsByContact, $contactIds);
    }

    foreach ($statsByContact as $contactId => $stats) {
      $statsByContact[$contactId] = $this->finalizeEngagementStats($stats);
    }

    return $statsByContact;
  }

  public function getContactEngagementStats(int $contact_id): array
  {
    $stats = $this->getContactsEngagementStatsByIds([$contact_id]);
    return $stats[$contact_id] ?? $this->getDefaultEngagementStats();
  }

  public function getContactTimeline(int $contact_id): array
  {
    global $wpdb;
    if ($contact_id <= 0) {
      return [];
    }

    $timeline = [];
    $contact = $this->getContactById($contact_id);
      if (!empty($contact)) {
        $timeline[] = [
          'side' => 'system',
        'type' => 'contact_created',
        'badge' => __('System', 'ppsgen'),
        'title' => __('Contact profile was created', 'ppsgen'),
        'text' => !empty($contact['wp_user_id'])
          ? sprintf(__('Linked WordPress user ID: %d', 'ppsgen'), (int) $contact['wp_user_id'])
          : __('The contact record was added to the CRM.', 'ppsgen'),
        'time' => (string) ($contact['created_at'] ?? ''),
        'time_ts' => !empty($contact['created_at']) ? strtotime($contact['created_at']) : 0,
        'meta' => [],
        ];
      }

      $fieldLogs = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT l.*, f.label AS field_label, f.slug AS field_slug
          FROM {$wpdb->prefix}ppsgen_contact_fields_logs AS l
          LEFT JOIN {$wpdb->prefix}ppsgen_contact_fields AS f ON f.id = l.field_id
          WHERE l.contact_id = %d
          ORDER BY l.updated_at ASC, l.id ASC",
          $contact_id,
        ),
        ARRAY_A,
      );
      $fieldLogGroups = [];
      foreach ($fieldLogs as $row) {
        $initiator = (string) ($row['initiator'] ?? 'system');
        $updatedAt = (string) ($row['updated_at'] ?? '');
        $groupKey = $initiator . '|' . $updatedAt;
        if (empty($fieldLogGroups[$groupKey])) {
          $fieldLogGroups[$groupKey] = [
            'initiator' => $initiator,
            'updated_at' => $updatedAt,
            'changes' => [],
          ];
        }
        $fieldLabel = !empty($row['field_label']) ? $row['field_label'] : (!empty($row['field_slug']) ? $row['field_slug'] : ('#' . (int) ($row['field_id'] ?? 0)));
        $fieldLogGroups[$groupKey]['changes'][] = [
          'field_id' => (int) ($row['field_id'] ?? 0),
          'label' => $fieldLabel,
          'before' => $this->formatTimelineValue($row['field_before_value'] ?? ''),
          'after' => $this->formatTimelineValue($row['field_value'] ?? ''),
        ];
      }
      foreach ($fieldLogGroups as $group) {
        $initiator = (string) ($group['initiator'] ?? 'system');
        $side = $initiator === 'form_submit' ? 'user' : 'system';
        $badge = $initiator === 'form_submit'
          ? __('User', 'ppsgen')
          : ($initiator === 'admin' ? __('Admin', 'ppsgen') : __('System', 'ppsgen'));
        $changes = !empty($group['changes']) && is_array($group['changes']) ? $group['changes'] : [];
        if (empty($changes)) {
          continue;
        }
        $textParts = [];
        $fieldIds = [];
        foreach ($changes as $change) {
          $fieldIds[] = (int) ($change['field_id'] ?? 0);
          $textParts[] = sprintf(
            __('%1$s: %2$s -> %3$s', 'ppsgen'),
            (string) ($change['label'] ?? ''),
            (string) ($change['before'] ?? ''),
            (string) ($change['after'] ?? ''),
          );
        }
        $timeline[] = [
          'side' => $side,
          'type' => 'field_change',
          'badge' => $badge,
          'title' => count($changes) > 1
            ? sprintf(__('Updated %d fields', 'ppsgen'), count($changes))
            : sprintf(__('Field updated: %s', 'ppsgen'), (string) ($changes[0]['label'] ?? '')),
          'text' => implode('. ', $textParts) . '.',
          'time' => (string) ($group['updated_at'] ?? ''),
          'time_ts' => !empty($group['updated_at']) ? strtotime($group['updated_at']) : 0,
          'meta' => [
            'field_ids' => array_values(array_filter(array_unique($fieldIds))),
          ],
        ];
      }

      if ($this->getFrame()->useModule('form')) {
      $formRows = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT s.id, s.form_id, s.created_date, f.name AS form_name, s.fields_values
          FROM {$wpdb->prefix}ppsgen_form_submits AS s
          LEFT JOIN {$wpdb->prefix}ppsgen_forms AS f ON f.id = s.form_id
          WHERE s.contact_id = %d
          ORDER BY s.created_date ASC, s.id ASC",
          $contact_id,
        ),
        ARRAY_A,
      );
      foreach ($formRows as $row) {
        $fieldCount = 0;
        $fields = !empty($row['fields_values']) ? $this->_prepareValueFromStore($row['fields_values'], 'json') : [];
        if (is_array($fields)) {
          $fieldCount = count(array_filter(array_keys($fields), static function ($key) {
            return $key !== 'unverified_submit';
          }));
        }
        $formName = !empty($row['form_name']) ? $row['form_name'] : ('#' . (int) $row['form_id']);
        $timeline[] = [
          'side' => 'user',
          'type' => 'form_submit',
          'badge' => __('User', 'ppsgen'),
          'title' => sprintf(__('Submitted form: %s', 'ppsgen'), $formName),
          'text' => $fieldCount > 0
            ? sprintf(__('Submission contained %d field values.', 'ppsgen'), $fieldCount)
            : __('A form submission was recorded for this contact.', 'ppsgen'),
          'time' => (string) ($row['created_date'] ?? ''),
          'time_ts' => !empty($row['created_date']) ? strtotime($row['created_date']) : 0,
          'meta' => [
            'submit_id' => (int) ($row['id'] ?? 0),
          ],
        ];
        }
      }

      $tagLogs = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT *
          FROM {$wpdb->prefix}ppsgen_contact_tags_logs
          WHERE contact_id = %d
          ORDER BY created_at ASC, id ASC",
          $contact_id,
        ),
        ARRAY_A,
      );
      foreach ($tagLogs as $row) {
        $initiator = (string) ($row['initiator'] ?? 'system');
        $side = $initiator === 'form_submit' ? 'user' : 'system';
        $badge = $initiator === 'form_submit'
          ? __('User', 'ppsgen')
          : ($initiator === 'admin' ? __('Admin', 'ppsgen') : ($initiator === 'eca' ? __('Automation', 'ppsgen') : __('System', 'ppsgen')));
        $tagIds = array_values(array_filter(array_map('intval', explode(',', (string) ($row['tag_ids'] ?? '')))));
        $tagNames = $this->getContactTagNamesByIds($tagIds);
        $action = (string) ($row['tag_action'] ?? 'added');
        $timeline[] = [
          'side' => $side,
          'type' => 'tag_log',
          'badge' => $badge,
          'title' => $action === 'removed' ? __('Removed tags', 'ppsgen') : __('Added tags', 'ppsgen'),
          'text' => !empty($tagNames) ? implode(', ', $tagNames) : __('Tag list was updated.', 'ppsgen'),
          'time' => (string) ($row['created_at'] ?? ''),
          'time_ts' => !empty($row['created_at']) ? strtotime($row['created_at']) : 0,
          'meta' => [],
        ];
      }

      if ($this->getFrame()->useModule('eca')) {
      $flowLogs = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT l.id, l.chain_id, l.step_id, l.flow_id, l.status, l.message, l.run_at,
                  c.name AS chain_name, s.name AS step_name
          FROM {$wpdb->prefix}ppsgen_eca_flow_logs AS l
          LEFT JOIN {$wpdb->prefix}ppsgen_eca_chains AS c ON c.id = l.chain_id
          LEFT JOIN {$wpdb->prefix}ppsgen_eca_chain_step AS s ON s.id = l.step_id
          WHERE l.contact_id = %d
          ORDER BY l.run_at ASC, l.id ASC",
          $contact_id,
        ),
        ARRAY_A,
      );
      $ecaModel = $this->getFrame()->getModule('eca')->getModel();
      foreach ($flowLogs as $row) {
        $status = (string) ($row['status'] ?? '');
        $side = in_array($status, ['unsubscribed'], true) ? 'user' : 'system';
        $badge = $side === 'user' ? __('User', 'ppsgen') : __('System', 'ppsgen');
        $chainName = !empty($row['chain_name']) ? $row['chain_name'] : ('#' . (int) $row['chain_id']);
        $stepName = !empty($row['step_name']) ? $row['step_name'] : '';
        $statusLabel = $ecaModel->flowLogStatus($status);
        $statusLabel = is_string($statusLabel) ? $statusLabel : '';
        $textParts = [sprintf(__('Campaign: %s', 'ppsgen'), $chainName)];
        if (!empty($stepName)) {
          $textParts[] = sprintf(__('Step: %s', 'ppsgen'), $stepName);
        }
        if (!empty($row['message'])) {
          $textParts[] = trim((string) $row['message']);
        }
        $timeline[] = [
          'side' => $side,
          'type' => 'eca_flow_log',
          'badge' => $badge,
          'title' => !empty($statusLabel) ? $statusLabel : __('Email campaign event', 'ppsgen'),
          'text' => implode('. ', array_filter($textParts)),
          'time' => (string) ($row['run_at'] ?? ''),
          'time_ts' => !empty($row['run_at']) ? strtotime($row['run_at']) : 0,
          'meta' => [
            'chain_id' => (int) ($row['chain_id'] ?? 0),
            'flow_id' => (int) ($row['flow_id'] ?? 0),
          ],
        ];
      }

      $stepEvents = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT e.id, e.chain_id, e.step_id, e.flow_id, e.event_type, e.clicked_url, e.created_at,
                  c.name AS chain_name, s.name AS step_name
          FROM {$wpdb->prefix}ppsgen_eca_step_events AS e
          LEFT JOIN {$wpdb->prefix}ppsgen_eca_chains AS c ON c.id = e.chain_id
          LEFT JOIN {$wpdb->prefix}ppsgen_eca_chain_step AS s ON s.id = e.step_id
          WHERE e.contact_id = %d
          ORDER BY e.created_at ASC, e.id ASC",
          $contact_id,
        ),
        ARRAY_A,
      );
      foreach ($stepEvents as $row) {
        $eventType = (string) ($row['event_type'] ?? '');
        $chainName = !empty($row['chain_name']) ? $row['chain_name'] : ('#' . (int) $row['chain_id']);
        $stepName = !empty($row['step_name']) ? $row['step_name'] : '';
        $eventLabel = $ecaModel->eventTypeStatus($eventType);
        $eventLabel = is_string($eventLabel) ? $eventLabel : '';
        $textParts = [sprintf(__('Campaign: %s', 'ppsgen'), $chainName)];
        if (!empty($stepName)) {
          $textParts[] = sprintf(__('Step: %s', 'ppsgen'), $stepName);
        }
        if ($eventType === 'click' && !empty($row['clicked_url'])) {
          $textParts[] = sprintf(__('URL: %s', 'ppsgen'), $row['clicked_url']);
        }
        $timeline[] = [
          'side' => 'user',
          'type' => 'eca_step_event',
          'badge' => __('User', 'ppsgen'),
          'title' => !empty($eventLabel) ? $eventLabel : __('Email engagement', 'ppsgen'),
          'text' => implode('. ', array_filter($textParts)),
          'time' => (string) ($row['created_at'] ?? ''),
          'time_ts' => !empty($row['created_at']) ? strtotime($row['created_at']) : 0,
          'meta' => [
            'chain_id' => (int) ($row['chain_id'] ?? 0),
            'flow_id' => (int) ($row['flow_id'] ?? 0),
          ],
        ];
      }
    }

    usort($timeline, static function ($a, $b) {
      $aTs = (int) ($a['time_ts'] ?? 0);
      $bTs = (int) ($b['time_ts'] ?? 0);
      if ($aTs === $bTs) {
        return strcmp((string) ($a['type'] ?? ''), (string) ($b['type'] ?? ''));
      }
      return $aTs <=> $bTs;
    });

    return $timeline;
  }

  public function clearContactFieldsAndTagsLogs(int $contact_id): bool
  {
    global $wpdb;
    if ($contact_id <= 0) {
      return false;
    }
    $deletedFields = $wpdb->delete("{$wpdb->prefix}ppsgen_contact_fields_logs", ['contact_id' => $contact_id], ['%d']);
    $deletedTags = $wpdb->delete("{$wpdb->prefix}ppsgen_contact_tags_logs", ['contact_id' => $contact_id], ['%d']);
    return $deletedFields !== false && $deletedTags !== false;
  }

  public function getContactEmailThread(int $contact_id, int $mailConfigId = 0, int $limit = 60): array
  {
    $contactEmail = sanitize_email($this->getContactFieldValueBySlug($contact_id, 'email'));
    $result = [
      'contact_id' => $contact_id,
      'email' => $contactEmail,
      'items' => [],
      'error' => '',
      'suggested_subject' => '',
    ];
    if ($contact_id <= 0 || empty($contactEmail)) {
      $result['error'] = __('This contact does not have an email address yet.', 'ppsgen');
      return $result;
    }
    $this->getFrame()->core('helper')->bootProMailProviders();
    $providerResult = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.email.thread_data', null, [
      'contact_id' => $contact_id,
      'contact_email' => $contactEmail,
      'mail_config_id' => $mailConfigId,
      'limit' => $limit,
    ]);
    if (is_array($providerResult)) {
      return wp_parse_args($providerResult, $result);
    }
    $config = $this->getContactEmailMailboxConfig($mailConfigId);
    if (($config['status'] ?? 'error') !== 'success') {
      $result['error'] = (string) ($config['message'] ?? __('IMAP settings are incomplete.', 'ppsgen'));
      return $result;
    }
    if (!function_exists('imap_open')) {
      $result['error'] = __('The PHP IMAP extension is not available on this server.', 'ppsgen');
      return $result;
    }
    $imap = $this->openContactEmailMailbox((string) $config['root'] . 'INBOX', $config);
    if (!$imap) {
      $result['error'] = $this->getContactEmailImapLastError();
      return $result;
    }
    $mailboxes = $this->discoverContactEmailMailboxes($imap, $config);
    $ownEmails = $this->getContactEmailOwnAddresses($config);
    $byMessageId = [];
    foreach ($mailboxes as $mailboxFull => $mailboxType) {
      $items = $this->searchContactMessagesInMailbox($imap, $mailboxFull, (string) $mailboxType, $contactEmail, $ownEmails, $limit);
      foreach ($items as $item) {
        $uniqueKey = !empty($item['message_id']) ? 'mid:' . $item['message_id'] : ('box:' . $item['mailbox_key'] . '|uid:' . $item['uid']);
        if (empty($byMessageId[$uniqueKey])) {
          $byMessageId[$uniqueKey] = $item;
          continue;
        }
        if (!empty($item['timestamp']) && (int) $item['timestamp'] > (int) ($byMessageId[$uniqueKey]['timestamp'] ?? 0)) {
          $byMessageId[$uniqueKey] = $item;
        }
      }
    }
    imap_close($imap);
    $items = array_values($byMessageId);
    usort($items, static function ($a, $b) {
      return (int) ($b['timestamp'] ?? 0) <=> (int) ($a['timestamp'] ?? 0);
    });
    $items = array_slice($items, 0, max(1, $limit));
    usort($items, static function ($a, $b) {
      return (int) ($a['timestamp'] ?? 0) <=> (int) ($b['timestamp'] ?? 0);
    });
    $result['items'] = $items;
    $result['suggested_subject'] = $this->buildSuggestedReplySubject($items);
    return $result;
  }

  public function getContactEmailMessageView(int $contact_id, string $mailboxKey, string $messageId, int $mailConfigId = 0): array
  {
    $contactEmail = sanitize_email($this->getContactFieldValueBySlug($contact_id, 'email'));
    if ($contact_id <= 0 || empty($contactEmail) || empty($mailboxKey) || $messageId === '') {
      return [
        'status' => 'error',
        'message' => __('Email message parameters are invalid.', 'ppsgen'),
      ];
    }
    $this->getFrame()->core('helper')->bootProMailProviders();
    $providerResult = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.email.message_view', null, [
      'contact_id' => $contact_id,
      'contact_email' => $contactEmail,
      'mail_config_id' => $mailConfigId,
      'mailbox_key' => $mailboxKey,
      'message_id' => $messageId,
    ]);
    if (is_array($providerResult)) {
      if (($providerResult['status'] ?? 'error') === 'success' && empty($providerResult['reply_subject'])) {
        $providerResult['reply_subject'] = $this->normalizeContactReplySubject((string) ($providerResult['subject'] ?? ''));
      }
      return $providerResult;
    }
    $config = $this->getContactEmailMailboxConfig($mailConfigId);
    if (($config['status'] ?? 'error') !== 'success') {
      return [
        'status' => 'error',
        'message' => (string) ($config['message'] ?? __('IMAP settings are incomplete.', 'ppsgen')),
      ];
    }
    if (!function_exists('imap_open')) {
      return [
        'status' => 'error',
        'message' => __('The PHP IMAP extension is not available on this server.', 'ppsgen'),
      ];
    }
    $imap = $this->openContactEmailMailbox((string) $config['root'] . 'INBOX', $config);
    if (!$imap) {
      return [
        'status' => 'error',
        'message' => $this->getContactEmailImapLastError(),
      ];
    }
    $mailboxes = $this->discoverContactEmailMailboxes($imap, $config);
    $mailbox = $this->resolveContactEmailMailboxByKey($mailboxes, $mailboxKey);
    if (empty($mailbox) || !imap_reopen($imap, $mailbox)) {
      imap_close($imap);
      return [
        'status' => 'error',
        'message' => __('The selected mailbox is unavailable.', 'ppsgen'),
      ];
    }
    $overviewRows = @imap_fetch_overview($imap, $messageId, FT_UID);
    $overview = !empty($overviewRows[0]) ? $overviewRows[0] : null;
    if (empty($overview)) {
      imap_close($imap);
      return [
        'status' => 'error',
        'message' => __('Unable to load this email message.', 'ppsgen'),
      ];
    }
    $messageBody = $this->getContactEmailMessageBody($imap, (int) $messageId);
    $subject = $this->decodeContactEmailHeader((string) ($overview->subject ?? ''));
    $html = $this->buildContactEmailMessageDocument([
      'subject' => $subject,
      'from' => $this->decodeContactEmailHeader((string) ($overview->from ?? '')),
      'to' => $this->decodeContactEmailHeader((string) ($overview->to ?? $contactEmail)),
      'date' => (string) ($overview->date ?? ''),
      'body' => (string) ($messageBody['body'] ?? ''),
      'is_html' => !empty($messageBody['is_html']),
    ]);
    $seen = !empty($overview->seen);
    if (!$seen) {
      @imap_setflag_full($imap, $messageId, '\\Seen', ST_UID);
      $seen = true;
    }
    imap_close($imap);
    return [
      'status' => 'success',
      'seen' => $seen,
      'html' => $html,
      'reply_subject' => $this->normalizeContactReplySubject($subject),
    ];
  }

  public function sendContactEmailMessage(int $contact_id, array $payload): array
  {
    $contactEmail = sanitize_email($this->getContactFieldValueBySlug($contact_id, 'email'));
    if ($contact_id <= 0 || empty($contactEmail)) {
      return [
        'status' => 'error',
        'message' => __('This contact does not have an email address yet.', 'ppsgen'),
      ];
    }
    $subject = !empty($payload['subject']) ? sanitize_text_field($payload['subject']) : '';
    $subject = !empty($subject) ? $subject : sprintf(__('Message from %s', 'ppsgen'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
    $message = !empty($payload['html_message']) ? (string) $payload['html_message'] : '';
    $message = $this->getFrame()->core('helper')->wpKses($message);
    if (trim(wp_strip_all_tags($message)) === '') {
      return [
        'status' => 'error',
        'message' => __('Please enter an email message before sending.', 'ppsgen'),
      ];
    }
    $templateId = !empty($payload['template_id']) ? (int) $payload['template_id'] : 0;
    $mailConfigId = !empty($payload['mail_config_id']) ? (int) $payload['mail_config_id'] : 0;
    if ($templateId > 0 && $this->getFrame()->useModule('eca')) {
      $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($templateId, $message);
    }
    $remaining = $this->getContactEmailRemainingLimit($mailConfigId);
    if ($remaining <= 0) {
      return [
        'status' => 'error',
        'message' => __('Daily email limit has been reached. Please wait until it resets.', 'ppsgen'),
      ];
    }
    $sendResult = $this->getFrame()->core('helper')->sendEmail($contactEmail, '', '', $subject, $message, '', [], $mailConfigId);
    if (!$sendResult) {
      $providerError = trim((string) $this->getFrame()->core('helper')->getLastMailProviderError());
      return [
        'status' => 'error',
        'message' => $providerError !== '' ? $providerError : __('Failed to send the email. Please check your mail settings.', 'ppsgen'),
      ];
    }
    $this->decreaseContactEmailRemainingLimit($remaining, $mailConfigId);
    if ($this->getFrame()->useModule('eca')) {
      $this->getFrame()->getModule('eca')->getModel()->markContactIncomingEmailsHandled($contact_id, $mailConfigId, get_current_user_id());
    }
    return [
      'status' => 'success',
      'message' => __('Email has been sent.', 'ppsgen'),
    ];
  }

  protected function getContactEmailMailboxConfig(int $mailConfigId = 0): array
  {
    $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
    $mailConfig = $dashboardModel->getResolvedMailConfigData($mailConfigId);
    $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
    $host = (string) ($mailSettings['imap_server_name'] ?? '');
    $user = (string) ($mailSettings['smtp_login'] ?? '');
    $pass = (string) ($mailSettings['smtp_password'] ?? '');
    $port = (int) ($mailSettings['imap_port'] ?? 0);
    $secure = !empty($mailSettings['smtp_secure']) ? (string) $mailSettings['smtp_secure'] : 'ssl';
    $fromEmail = (string) ($mailSettings['mail_from_email'] ?? '');
    $fromName = (string) ($mailSettings['mail_from_name'] ?? '');
    if (empty($host) || empty($user) || empty($pass) || empty($port)) {
      return [
        'status' => 'error',
        'message' => __('IMAP settings are incomplete. Please check Dashboard mail settings.', 'ppsgen'),
      ];
    }
    return [
      'status' => 'success',
      'host' => $host,
      'user' => $user,
      'pass' => $pass,
      'port' => $port,
      'secure' => $secure,
      'root' => sprintf('{%s:%d/imap/%s}', $host, $port, $secure),
      'from_email' => $fromEmail,
      'from_name' => $fromName,
      'config_id' => (int) ($mailConfig['id'] ?? 0),
    ];
  }

  protected function openContactEmailMailbox(string $mailbox, array $config)
  {
    imap_timeout(IMAP_OPENTIMEOUT, 20);
    imap_timeout(IMAP_READTIMEOUT, 20);
    return @imap_open($mailbox, (string) $config['user'], (string) $config['pass']);
  }

  protected function discoverContactEmailMailboxes($imap, array $config): array
  {
    $root = (string) ($config['root'] ?? '');
    $mailboxes = [
      $root . 'INBOX' => 'inbox',
    ];
    $allMailboxes = @imap_list($imap, $root, '*');
    if (empty($allMailboxes) || !is_array($allMailboxes)) {
      return $mailboxes;
    }
    foreach ($allMailboxes as $box) {
      if (!is_string($box) || isset($mailboxes[$box])) {
        continue;
      }
      $boxName = strtolower(str_replace($root, '', $box));
      if (preg_match('/trash|deleted|junk|spam|draft/i', $boxName)) {
        continue;
      }
      if (preg_match('/sent|sent items|sent messages|sent mail|отправ|gesendet|enviado|enviad/i', $boxName)) {
        $mailboxes[$box] = 'sent';
      }
    }
    return $mailboxes;
  }

  protected function resolveContactEmailMailboxByKey(array $mailboxes, string $mailboxKey): string
  {
    foreach ($mailboxes as $mailbox => $type) {
      if (bin2hex($mailbox) === $mailboxKey) {
        return $mailbox;
      }
    }
    return '';
  }

  protected function searchContactMessagesInMailbox($imap, string $mailbox, string $mailboxType, string $contactEmail, array $ownEmails, int $limit): array
  {
    if (!@imap_reopen($imap, $mailbox)) {
      return [];
    }
    $uids = $this->searchContactMailboxUids($imap, $contactEmail);
    if (empty($uids) || !is_array($uids)) {
      return [];
    }
    rsort($uids, SORT_NUMERIC);
    $uids = array_slice($uids, 0, max(1, $limit));
    $items = [];
    foreach ($uids as $uid) {
      $overviewRows = @imap_fetch_overview($imap, (string) $uid, FT_UID);
      $overview = !empty($overviewRows[0]) ? $overviewRows[0] : null;
      if (empty($overview)) {
        continue;
      }
      $subject = $this->decodeContactEmailHeader((string) ($overview->subject ?? ''));
      $from = $this->decodeContactEmailHeader((string) ($overview->from ?? ''));
      $to = $this->decodeContactEmailHeader((string) ($overview->to ?? ''));
      $date = (string) ($overview->date ?? '');
      $timestamp = !empty($date) ? strtotime($date) : time();
      $outgoing = $mailboxType === 'sent' || $this->contactEmailHeaderContainsOwnAddress($from, $ownEmails);
      $items[] = [
        'uid' => (int) $uid,
        'mailbox_key' => bin2hex($mailbox),
        'mailbox_type' => $mailboxType,
        'direction' => $outgoing ? 'outgoing' : 'incoming',
        'side' => $outgoing ? 'system' : 'user',
        'subject' => $subject !== '' ? $subject : __('(No subject)', 'ppsgen'),
        'from' => $from,
        'to' => $to,
        'date' => $date,
        'timestamp' => $timestamp ?: time(),
        'seen' => !empty($overview->seen),
        'message_id' => !empty($overview->message_id) ? trim((string) $overview->message_id, '<>') : '',
      ];
    }
    return $items;
  }

  protected function searchContactMailboxUids($imap, string $contactEmail): array
  {
    $contactEmail = sanitize_email($contactEmail);
    if (empty($contactEmail)) {
      return [];
    }
    $criteriaList = [];
    $needle = addcslashes($contactEmail, '\\"');
    $criteriaList[] = 'FROM "' . $needle . '"';
    $criteriaList[] = 'TO "' . $needle . '"';
    $criteriaList[] = 'CC "' . $needle . '"';
    $criteriaList[] = 'BCC "' . $needle . '"';
    $criteriaList[] = 'TEXT "' . $needle . '"';
    $uids = [];
    foreach ($criteriaList as $criteria) {
      $found = @imap_search($imap, $criteria, SE_UID, 'UTF-8');
      if ($found === false) {
        $found = @imap_search($imap, $criteria, SE_UID);
      }
      if (!empty($found) && is_array($found)) {
        $uids = array_merge($uids, $found);
      }
    }
    $uids = array_values(array_unique(array_map('intval', $uids)));
    rsort($uids, SORT_NUMERIC);
    return $uids;
  }

  protected function getContactEmailOwnAddresses(array $config): array
  {
    $emails = [];
    if (!empty($config['user'])) {
      $emails[] = sanitize_email((string) $config['user']);
    }
    if (!empty($config['from_email'])) {
      $emails[] = sanitize_email((string) $config['from_email']);
    }
    return array_values(array_unique(array_filter($emails)));
  }

  protected function contactEmailHeaderContainsOwnAddress(string $header, array $ownEmails): bool
  {
    $header = strtolower($header);
    foreach ($ownEmails as $email) {
      if (!empty($email) && strpos($header, strtolower($email)) !== false) {
        return true;
      }
    }
    return false;
  }

  protected function buildSuggestedReplySubject(array $items): string
  {
    foreach (array_reverse($items) as $item) {
      if (($item['direction'] ?? '') !== 'incoming') {
        continue;
      }
      $subject = $this->normalizeContactReplySubject((string) ($item['subject'] ?? ''));
      if ($subject !== '') {
        return $subject;
      }
    }
    foreach (array_reverse($items) as $item) {
      $subject = $this->normalizeContactReplySubject((string) ($item['subject'] ?? ''));
      if ($subject !== '') {
        return $subject;
      }
    }
    return '';
  }

  protected function normalizeContactReplySubject(string $subject): string
  {
    $subject = trim($subject);
    if ($subject === '' || $subject === __('(No subject)', 'ppsgen')) {
      return '';
    }
    return stripos($subject, 're:') === 0 ? $subject : 'Re: ' . $subject;
  }

  protected function getContactEmailMessageBody($imap, int $uid): array
  {
    $structure = @imap_fetchstructure($imap, $uid, FT_UID);
    if (empty($structure)) {
      $rawBody = @imap_body($imap, $uid, FT_UID);
      return [
        'body' => is_string($rawBody) ? $rawBody : '',
        'is_html' => false,
      ];
    }
    $htmlPart = $this->extractContactEmailMimePart($imap, $uid, $structure, 'text/html');
    if (!empty($htmlPart['body'])) {
      return [
        'body' => (string) $htmlPart['body'],
        'is_html' => true,
      ];
    }
    $plainPart = $this->extractContactEmailMimePart($imap, $uid, $structure, 'text/plain');
    if (!empty($plainPart['body'])) {
      return [
        'body' => (string) $plainPart['body'],
        'is_html' => false,
      ];
    }
    $rawBody = @imap_body($imap, $uid, FT_UID);
    $decoded = $this->decodeContactEmailBody((string) $rawBody, (int) ($structure->encoding ?? 0));
    return [
      'body' => $decoded,
      'is_html' => false,
    ];
  }

  protected function extractContactEmailMimePart($imap, int $uid, $structure, string $targetMime, string $partNumber = ''): array
  {
    $mime = $this->buildContactEmailMimeType($structure);
    if ($mime === $targetMime) {
      $fetchPart = $partNumber !== '' ? $partNumber : '1';
      $body = @imap_fetchbody($imap, $uid, $fetchPart, FT_UID);
      if ($body === false || $body === '') {
        $body = @imap_body($imap, $uid, FT_UID);
      }
      $body = $this->decodeContactEmailBody((string) $body, (int) ($structure->encoding ?? 0));
      $charset = $this->getContactEmailPartCharset($structure);
      $body = $this->convertContactEmailToUtf8($body, $charset);
      return [
        'body' => $body,
        'charset' => $charset,
      ];
    }
    if (!empty($structure->parts) && is_array($structure->parts)) {
      foreach ($structure->parts as $index => $part) {
        $subPartNumber = $partNumber === '' ? (string) ($index + 1) : $partNumber . '.' . ($index + 1);
        $found = $this->extractContactEmailMimePart($imap, $uid, $part, $targetMime, $subPartNumber);
        if (!empty($found['body'])) {
          return $found;
        }
      }
    }
    return [];
  }

  protected function buildContactEmailMimeType($structure): string
  {
    $primary = [
      0 => 'text',
      1 => 'multipart',
      2 => 'message',
      3 => 'application',
      4 => 'audio',
      5 => 'image',
      6 => 'video',
      7 => 'other',
    ];
    $type = isset($primary[(int) ($structure->type ?? 0)]) ? $primary[(int) ($structure->type ?? 0)] : 'text';
    $subtype = !empty($structure->subtype) ? strtolower((string) $structure->subtype) : 'plain';
    return strtolower($type . '/' . $subtype);
  }

  protected function decodeContactEmailBody(string $body, int $encoding): string
  {
    switch ($encoding) {
      case 3:
        $decoded = base64_decode($body, true);
        return $decoded !== false ? $decoded : $body;
      case 4:
        return quoted_printable_decode($body);
      default:
        return $body;
    }
  }

  protected function getContactEmailPartCharset($structure): string
  {
    $params = [];
    if (!empty($structure->parameters) && is_array($structure->parameters)) {
      $params = array_merge($params, $structure->parameters);
    }
    if (!empty($structure->dparameters) && is_array($structure->dparameters)) {
      $params = array_merge($params, $structure->dparameters);
    }
    foreach ($params as $param) {
      if (!empty($param->attribute) && strtolower((string) $param->attribute) === 'charset') {
        return (string) $param->value;
      }
    }
    return 'UTF-8';
  }

  protected function convertContactEmailToUtf8(string $value, string $charset): string
  {
    $charset = trim($charset);
    if ($value === '' || $charset === '' || strtoupper($charset) === 'UTF-8') {
      return $value;
    }
    if (function_exists('mb_convert_encoding')) {
      $converted = @mb_convert_encoding($value, 'UTF-8', $charset);
      if (is_string($converted) && $converted !== '') {
        return $converted;
      }
    }
    return $value;
  }

  protected function decodeContactEmailHeader(string $value): string
  {
    if ($value === '') {
      return '';
    }
    $decodedParts = @imap_mime_header_decode($value);
    if (empty($decodedParts) || !is_array($decodedParts)) {
      return wp_strip_all_tags($value);
    }
    $decoded = '';
    foreach ($decodedParts as $part) {
      $text = (string) ($part->text ?? '');
      $charset = (string) ($part->charset ?? 'default');
      if ($charset !== 'default') {
        $text = $this->convertContactEmailToUtf8($text, $charset);
      }
      $decoded .= $text;
    }
    return trim(wp_strip_all_tags($decoded));
  }

  protected function buildContactEmailMessageDocument(array $message): string
  {
    $subject = !empty($message['subject']) ? (string) $message['subject'] : __('(No subject)', 'ppsgen');
    $from = (string) ($message['from'] ?? '');
    $to = (string) ($message['to'] ?? '');
    $date = (string) ($message['date'] ?? '');
    $body = (string) ($message['body'] ?? '');
    $isHtml = !empty($message['is_html']);
    $content = $isHtml
      ? $this->getFrame()->core('helper')->wpKses($body)
      : '<pre style="white-space:pre-wrap;font-family:inherit;font-size:14px;line-height:1.7;margin:0;">' . esc_html($body) . '</pre>';
    return '<!doctype html><html><head><meta charset="utf-8"><base target="_blank"><style>
      html,body{margin:0;padding:0;background:#ffffff !important;color:#0f172a !important;font:14px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
      .wrap{padding:18px;background:#ffffff;}
      .card{background:#ffffff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden;}
      .head{padding:18px 20px;border-bottom:1px solid #e5e7eb;background:#ffffff;}
      .subject{margin:0 0 12px;font-size:22px;line-height:1.3;color:#0f172a;}
      .meta{display:flex;flex-wrap:wrap;gap:8px;}
      .pill{display:inline-flex;align-items:center;min-height:30px;padding:4px 10px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:600;}
      .body{padding:22px;overflow-wrap:anywhere;background:#ffffff !important;color:#0f172a !important;}
      .body,.body p,.body div,.body span,.body td,.body th,.body li,.body blockquote,.body pre,.body h1,.body h2,.body h3,.body h4,.body h5,.body h6{color:#0f172a !important;}
      .body img{max-width:100%;height:auto;}
      .body table{max-width:100%;}
      .body a{color:#1d4ed8;}
    </style></head><body><div class="wrap"><div class="card"><div class="head"><h1 class="subject">' . esc_html($subject) . '</h1><div class="meta">'
      . ($date !== '' ? '<span class="pill">' . esc_html($date) . '</span>' : '')
      . ($from !== '' ? '<span class="pill">' . esc_html__('From:', 'ppsgen') . ' ' . esc_html($from) . '</span>' : '')
      . ($to !== '' ? '<span class="pill">' . esc_html__('To:', 'ppsgen') . ' ' . esc_html($to) . '</span>' : '')
      . '</div></div><div class="body">' . $content . '</div></div></div></body></html>';
  }

  protected function getContactEmailImapLastError(): string
  {
    $error = function_exists('imap_last_error') ? imap_last_error() : '';
    return !empty($error) ? (string) $error : __('Unable to connect to the mailbox.', 'ppsgen');
  }

  protected function getContactEmailRemainingLimit(int $mailConfigId = 0): int
  {
    $config = $this->getFrame()->getModule('dashboard')->getModel()->getResolvedMailConfigRemaining($mailConfigId);
    $settings = is_array($config['settings'] ?? null) ? $config['settings'] : [];
    return max(0, (int) ($settings['mail_remaining'] ?? 0));
  }

  protected function decreaseContactEmailRemainingLimit(int $currentRemaining, int $mailConfigId = 0): void
  {
    $newRemaining = max(0, $currentRemaining - 1);
    $mailConfig = $this->getFrame()->getModule('dashboard')->getModel()->getResolvedMailConfigData($mailConfigId);
    $this->getFrame()->getModule('dashboard')->getModel()->updateResolvedMailConfigSettings((int) ($mailConfig['id'] ?? 0), ['mail_remaining' => $newRemaining]);
  }

  protected function appendContactEmailToSentMailbox(string $toEmail, string $subject, string $htmlMessage, int $mailConfigId = 0): bool
  {
    $config = $this->getContactEmailMailboxConfig($mailConfigId);
    if (($config['status'] ?? 'error') !== 'success' || !function_exists('imap_open') || !function_exists('imap_append')) {
      return false;
    }
    $imap = $this->openContactEmailMailbox((string) $config['root'] . 'INBOX', $config);
    if (!$imap) {
      return false;
    }
    $mailboxes = $this->discoverContactEmailMailboxes($imap, $config);
    $sentMailbox = '';
    foreach ($mailboxes as $mailbox => $type) {
      if ($type === 'sent') {
        $sentMailbox = $mailbox;
        break;
      }
    }
    if ($sentMailbox === '') {
      imap_close($imap);
      return false;
    }
    $fromEmail = !empty($config['from_email']) ? sanitize_email((string) $config['from_email']) : sanitize_email((string) $config['user']);
    $fromName = !empty($config['from_name']) ? (string) $config['from_name'] : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $rfc822 = $this->buildContactSentEmailRfc822($fromEmail, $fromName, $toEmail, $subject, $htmlMessage);
    $result = @imap_append($imap, $sentMailbox, $rfc822, '\\Seen');
    imap_close($imap);
    return (bool) $result;
  }

  protected function buildContactSentEmailRfc822(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlMessage): string
  {
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = $fromName !== '' ? '=?UTF-8?B?' . base64_encode($fromName) . '?=' : '';
    $fromHeader = $encodedFromName !== '' ? $encodedFromName . ' <' . $fromEmail . '>' : $fromEmail;
    $messageIdDomain = wp_parse_url(home_url(), PHP_URL_HOST);
    $messageIdDomain = !empty($messageIdDomain) ? $messageIdDomain : 'localhost';
    $headers = [
      'Date: ' . date('r'),
      'From: ' . $fromHeader,
      'To: ' . $toEmail,
      'Subject: ' . $encodedSubject,
      'Message-ID: <' . wp_generate_uuid4() . '@' . $messageIdDomain . '>',
      'MIME-Version: 1.0',
      'Content-Type: text/html; charset=UTF-8',
      'Content-Transfer-Encoding: base64',
      'X-Mailer: WP Supsystic',
    ];
    $body = chunk_split(base64_encode($htmlMessage));
    return implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n";
  }

  public function getContactFieldLogsGrouped(int $contact_id): array
  {
    global $wpdb;
    if ($contact_id <= 0) {
      return [];
    }
    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT l.*, f.label AS field_label, f.slug AS field_slug
        FROM {$wpdb->prefix}ppsgen_contact_fields_logs AS l
        LEFT JOIN {$wpdb->prefix}ppsgen_contact_fields AS f ON f.id = l.field_id
        WHERE l.contact_id = %d
        ORDER BY l.updated_at DESC, l.id DESC",
        $contact_id,
      ),
      ARRAY_A,
    );
    $grouped = [];
    foreach ($rows as $row) {
      $fieldId = (int) ($row['field_id'] ?? 0);
      if ($fieldId <= 0) {
        continue;
      }
      $initiator = (string) ($row['initiator'] ?? 'system');
      $grouped[$fieldId][] = [
        'before' => $this->formatTimelineValue($row['field_before_value'] ?? ''),
        'after' => $this->formatTimelineValue($row['field_value'] ?? ''),
        'initiator' => $initiator,
        'initiator_label' => $initiator === 'form_submit'
          ? __('User', 'ppsgen')
          : ($initiator === 'admin' ? __('Admin', 'ppsgen') : __('System', 'ppsgen')),
        'time' => (string) ($row['updated_at'] ?? ''),
      ];
    }
    return $grouped;
  }

  protected function formatTimelineValue($value): string
  {
    $value = (string) $value;
    if ($value === '') {
      return __('empty', 'ppsgen');
    }
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      $parts = [];
      array_walk_recursive($decoded, static function ($item) use (&$parts) {
        if (is_scalar($item) && $item !== '') {
          $parts[] = (string) $item;
        }
      });
      $value = !empty($parts) ? implode(', ', array_slice($parts, 0, 6)) : __('structured data', 'ppsgen');
    }
    $value = wp_strip_all_tags($value);
    if (mb_strlen($value) > 120) {
      $value = mb_substr($value, 0, 117) . '...';
    }
    return $value;
  }

  protected function getContactTagNamesByIds(array $tagIds): array
  {
    global $wpdb;
    $tagIds = array_values(array_filter(array_map('intval', $tagIds)));
    if (empty($tagIds)) {
      return [];
    }
    $placeholders = implode(',', array_fill(0, count($tagIds), '%d'));
    $rows = $wpdb->get_results(
      $wpdb->prepare("SELECT id, slug FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id IN ($placeholders)", ...$tagIds),
      ARRAY_A,
    );
    if (empty($rows)) {
      return [];
    }
    $map = array_column($rows, 'slug', 'id');
    $names = [];
    foreach ($tagIds as $tagId) {
      if (!empty($map[$tagId])) {
        $names[] = $map[$tagId];
      }
    }
    return $names;
  }

  protected function prepareCsvChoiceFieldValue($value, array $validOptions, bool $allowMultiple)
  {
    $value = trim((string) $value);
    if ($value === '') {
      return '';
    }

    $validOptions = array_map('strval', $validOptions);
    if (in_array($value, $validOptions, true)) {
      return $value;
    }

    $selectedValues = null;
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      $selectedValues = $decoded;
    } elseif ($allowMultiple && strpos($value, ',') !== false) {
      $selectedValues = explode(',', $value);
    }

    if (!is_array($selectedValues)) {
      return '';
    }

    $selectedValues = array_values(array_unique(array_filter(array_map(static function ($selectedValue) {
      return trim((string) $selectedValue);
    }, $selectedValues), static function ($selectedValue) {
      return $selectedValue !== '';
    })));

    if (empty($selectedValues) || (!$allowMultiple && count($selectedValues) > 1)) {
      return '';
    }

    foreach ($selectedValues as $selectedValue) {
      if (!in_array($selectedValue, $validOptions, true)) {
        return '';
      }
    }

    return $allowMultiple ? $selectedValues : $selectedValues[0];
  }

  public function editContact($data, $obj_id = 0)
  {
    global $wpdb;
    if (!empty($data)) {
      $cdata = !empty($data['contact']) ? $data['contact'] : [];
      $cdataFields = !empty($data['contact']['contact_fields']) ? $data['contact']['contact_fields'] : [];
      $cdataTags = !empty($data['contact']['contact_tags']) ? $data['contact']['contact_tags'] : [];
      if (!empty($data)) {
        unset($data);
      }
      if (!empty($cdata['contact_fields'])) {
        unset($cdata['contact_fields']);
      }
      if (!empty($cdata['contact_tags'])) {
        unset($cdata['contact_tags']);
      }
      // REGISTER WP USER START
      if (empty($cdata['wp_user_id']) && !empty($cdata['create_wp_user'])) {
        if (!empty($cdataFields['email'])) {
          $name = !empty($cdataFields['first_name']) && !empty($cdataFields['last_name']) ? $cdataFields['first_name'] . ' ' . $cdataFields['last_name'] : '';
          if (empty($name)) {
            $emailName = explode('@', $cdataFields['email']);
            $name = $emailName[0];
          }
          $cdataFields['first_name'] = !empty($cdataFields['first_name']) ? $cdataFields['first_name'] : '';
          $cdataFields['last_name'] = !empty($cdataFields['last_name']) ? $cdataFields['last_name'] : '';
          $new_wp_user_id = $this->registerWpUser(['email' => $cdataFields['email'], 'first_name' => $cdataFields['first_name'], 'last_name' => $cdataFields['last_name'], 'name' => $name, 'role' => $cdata['create_wp_user_role_id'], 'notify' => $cdata['notify_user']]);
        }
      }
      if ($this->getStatus() == 'error') {
        return false;
      }
      // REGISTER WP USER END
      if (empty($cdata['wp_user_id']) && !empty($cdata['create_wp_user'])) {
        $cdata['wp_user_id'] = $new_wp_user_id;
      }
      $cdata['updated_at'] = current_time('mysql');
      $contactData = [
        'wp_user_id' => $cdata['wp_user_id'],
        'verified' => $cdata['verified'],
        'created_at' => !empty($cdata['created_at']) ? $cdata['created_at'] : current_time('mysql'),
        'updated_at' => $cdata['updated_at'],
      ];
        if (!empty($obj_id)) {
          $res = $wpdb->update("{$wpdb->prefix}ppsgen_contacts", $contactData, ['id' => $obj_id]);
          if ($res !== false) {
            $this->saveContactFieldValues(['fields' => $cdataFields, 'contact_id' => $obj_id, 'initiator' => 'admin']);
              @$this->saveContactTags($cdataTags['selected_contact_tags'], $obj_id, 'admin');
            $this->setStatus('success');
            $this->setMessage(esc_html__('Contact is updated.', 'ppsgen'));
            return !empty($res) ? $res : true;
        } else {
          $this->setStatus('error');
          $this->setMessage(esc_html__('Error! Contact is not updated.', 'ppsgen'));
          return false;
        }
        } else {
          $res = $wpdb->insert("{$wpdb->prefix}ppsgen_contacts", $contactData);
          $contact_id = $wpdb->insert_id;
          if (!empty($contact_id)) {
            $this->saveContactFieldValues(['fields' => $cdataFields, 'contact_id' => $contact_id, 'initiator' => 'admin']);
              @$this->saveContactTags($cdataTags['selected_contact_tags'], $contact_id, 'admin');
            $this->setStatus('success');
            $this->setMessage(esc_html__('Contact is added.', 'ppsgen'));
            return $contact_id;
        } else {
          $this->setStatus('error');
          $this->setMessage(esc_html__('Error! Contact is not added.', 'ppsgen'));
          return false;
        }
      }
    }
  }

  public function registerWpUser($data)
  {
    $userData = [
      'user_login' => sanitize_user($data['email']),
      'user_email' => sanitize_email($data['email']),
      'user_pass' => wp_generate_password(),
      'first_name' => sanitize_text_field($data['first_name']),
      'last_name' => sanitize_text_field($data['last_name']),
      'display_name' => sanitize_text_field($data['name']),
      'role' => sanitize_text_field($data['role']),
    ];
    $userId = wp_insert_user($userData);
    if (!is_wp_error($userId)) {
      $wp_user_id = $userId;
      if (!empty($data['notify'])) {
        wp_new_user_notification($userId, null, 'both');
      }
    } else {
      $errorText = esc_html__('User registration error: ', 'ppsgen') . $userId->get_error_message();
      $this->setStatus('error');
      $this->setMessage($errorText);
    }
    return !empty($wp_user_id) ? $wp_user_id : false;
  }

  public function linkContactToUser($contact_id, $user_id)
  {
    global $wpdb;
    return $wpdb->update("{$wpdb->prefix}ppsgen_contacts", ['wp_user_id' => $user_id], ['id' => $contact_id]);
  }

  public function saveContactFieldValues($data)
  {
    $contactFieldValues = $data['fields'];
    $obj_id = !empty($data['contact_id']) ? $data['contact_id'] : 0;
    global $wpdb;
    if (!empty($obj_id) && !empty($contactFieldValues)) {
      $initiator = $this->resolveContactFieldLogInitiator($data);
      foreach ($contactFieldValues as $slug => $value) {
        $cfId = $this->getFrame()->useModule('contact', 'contact_field', 'model', 'getContactFieldIdBySlug', ['slug' => $slug]);
        $cfType = $this->getFrame()->useModule('contact', 'contact_field', 'model', 'getContactFieldTypeBySlug', ['slug' => $slug]);
        if (empty($cfId)) {
          continue;
        }
        // if ($cfType == 'phone') {
        //   $value = preg_replace('/\D+/', '', $value);
        // }
        if (is_array($value)) {
          $value = $this->_prepareValueToStore($value, 'json');
        }
        $value = $this->prepareContactFieldLogValue($value);
        $existingRow = $wpdb->get_row("SELECT value FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE " . $wpdb->prepare(' contact_id = %d AND field_id = %d ', [$obj_id, $cfId]), ARRAY_A);
        $beforeValue = is_array($existingRow) && array_key_exists('value', $existingRow) ? $existingRow['value'] : null;
        $fieldExist = !empty($existingRow) && is_array($existingRow);
        if ($fieldExist) {
          $wpdb->update("{$wpdb->prefix}ppsgen_contact_field_values", ['value' => $value], ['contact_id' => $obj_id, 'field_id' => $cfId]);
        } else {
          $wpdb->insert("{$wpdb->prefix}ppsgen_contact_field_values", ['value' => $value, 'contact_id' => $obj_id, 'field_id' => $cfId]);
        }
        $this->logContactFieldValueChange((int) $obj_id, (int) $cfId, $beforeValue, $value, $initiator);
      }
      $this->triggerContactUpdated($obj_id);
      return true;
    }
    return false;
  }

  protected function prepareContactFieldLogValue($value): string
  {
    if (is_array($value)) {
      $value = $this->_prepareValueToStore($value, 'json');
    }
    if (is_bool($value)) {
      return $value ? '1' : '0';
    }
    if ($value === null) {
      return '';
    }
    if (is_scalar($value)) {
      return (string) $value;
    }
    return wp_json_encode($value);
  }

  protected function resolveContactFieldLogInitiator(array $data): string
  {
    $initiator = !empty($data['initiator']) ? sanitize_key((string) $data['initiator']) : '';
    if (empty($initiator) && !empty($data['form_id'])) {
      $initiator = 'form_submit';
    }
    if (empty($initiator)) {
      $initiator = 'system';
    }
    return substr($initiator, 0, 50);
  }

  protected function logContactFieldValueChange(int $contactId, int $fieldId, $beforeValue, $afterValue, string $initiator = 'system'): void
  {
    global $wpdb;
    if ($contactId <= 0 || $fieldId <= 0) {
      return;
    }
    $beforeValue = $beforeValue === null ? '' : $this->prepareContactFieldLogValue($beforeValue);
    $afterValue = $this->prepareContactFieldLogValue($afterValue);
    if ($beforeValue === $afterValue) {
      return;
    }
    $wpdb->insert(
      "{$wpdb->prefix}ppsgen_contact_fields_logs",
      [
        'contact_id' => $contactId,
        'field_id' => $fieldId,
        'field_before_value' => $beforeValue,
        'field_value' => $afterValue,
        'initiator' => substr(sanitize_key($initiator), 0, 50) ?: 'system',
        'updated_at' => current_time('mysql'),
      ],
      ['%d', '%d', '%s', '%s', '%s', '%s'],
    );
  }

  public function contactExist($contact_id)
  {
    global $wpdb;
    if (!empty($contact_id)) {
      return $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE " . $wpdb->prepare('id = %d', [$contact_id]));
    }
    return false;
  }

  public function contactIsverified($contact_id)
  {
    global $wpdb;
    if (!empty($contact_id)) {
      return $wpdb->get_var("SELECT verified FROM {$wpdb->prefix}ppsgen_contacts WHERE " . $wpdb->prepare('id = %d', [$contact_id]));
    }
    return false;
  }

  public function saveContactTags(?array $contactTags, int $obj_id, string $initiator = 'system')
  {
    global $wpdb;
    if ($obj_id <= 0) {
      return;
    }
    $contactTags = array_values(array_filter(array_map('intval', (array) $contactTags)));
    $existing = $wpdb->get_col($wpdb->prepare("SELECT tag_id FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d", $obj_id));
    $existing = array_map('intval', $existing);
    $toInsert = array_diff($contactTags, $existing);
    $toDelete = array_diff($existing, $contactTags);
    if (!empty($toDelete)) {
      $placeholders = implode(',', array_fill(0, count($toDelete), '%d'));
      $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id IN ({$placeholders})", array_merge([$obj_id], $toDelete)));
      $this->logContactTagChange($obj_id, 'removed', $toDelete, $initiator);
    }
    foreach ($toInsert as $tagId) {
      $wpdb->insert("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => $obj_id, 'tag_id' => $tagId], ['%d', '%d']);
    }
    if (!empty($toInsert)) {
      $this->logContactTagChange($obj_id, 'added', $toInsert, $initiator);
    }
    if ($toInsert || $toDelete) {
      $this->triggerContactUpdated($obj_id);
    }
  }

  public function deleteContactTags($contactTags, $obj_id, string $initiator = 'system')
  {
    global $wpdb;
    if (!empty($obj_id)) {
      if (!empty($contactTags)) {
        $deleted = [];
        foreach ($contactTags as $tagId) {
          $tagId = (int) $tagId;
          $res = $wpdb->delete("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => (int) $obj_id, 'tag_id' => $tagId]);
          if (!empty($res)) {
            $deleted[] = $tagId;
          }
        }
        if (!empty($deleted)) {
          $this->logContactTagChange((int) $obj_id, 'removed', $deleted, $initiator);
          $this->triggerContactUpdated($obj_id);
        }
        return $deleted;
      }
    }
    return [];
  }

  public function addContactTags($contactTags, $obj_id, string $initiator = 'system')
  {
    global $wpdb;
    if (!empty($obj_id)) {
      if (!empty($contactTags)) {
        $contactId = (int) $obj_id;
        $contactTags = array_values(array_unique(array_filter(array_map('intval', (array) $contactTags))));
        if (empty($contactTags)) {
          return [];
        }
        $placeholders = implode(',', array_fill(0, count($contactTags), '%d'));
        $existingTagIds = $wpdb->get_col(
          $wpdb->prepare(
            "SELECT tag_id
            FROM {$wpdb->prefix}ppsgen_contact_to_tags
            WHERE contact_id = %d AND tag_id IN ($placeholders)",
            $contactId,
            ...$contactTags,
          ),
        );
        $existingTagIds = array_map('intval', (array) $existingTagIds);
        $tagsToAdd = array_values(array_diff($contactTags, $existingTagIds));
        if (empty($tagsToAdd)) {
          return [];
        }
        $added = [];
        foreach ($tagsToAdd as $key => $value) {
          $value = (int) $value;
          $res = @$wpdb->insert("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => $contactId, 'tag_id' => $value]);
          if (!empty($res)) {
            $added[] = $value;
          }
        }
        if (!empty($added)) {
          $this->logContactTagChange($contactId, 'added', $added, $initiator);
          $this->triggerContactUpdated($contactId);
        }
        return $added;
      }
    }
    return [];
  }

  protected function logContactTagChange(int $contactId, string $action, array $tagIds, string $initiator = 'system'): void
  {
    global $wpdb;
    $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
    if ($contactId <= 0 || empty($tagIds)) {
      return;
    }
    $action = $action === 'removed' ? 'removed' : 'added';
    $initiator = substr(sanitize_key($initiator), 0, 50) ?: 'system';
    $wpdb->insert(
      "{$wpdb->prefix}ppsgen_contact_tags_logs",
      [
        'contact_id' => $contactId,
        'tag_action' => $action,
        'tag_ids' => implode(',', $tagIds),
        'initiator' => $initiator,
        'created_at' => current_time('mysql'),
      ],
      ['%d', '%s', '%s', '%s', '%s'],
    );
  }

  public function verifyContact($obj_id)
  {
    global $wpdb;
    if (!empty($obj_id)) {
      return $wpdb->update("{$wpdb->prefix}ppsgen_contacts", ['verified' => 1], ['id' => $obj_id]);
    }
  }

  public function exportContactList($ids)
  {
    global $wpdb;
    // Init WP_Filesystem
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
    global $wp_filesystem;
    // Prepare file path
    $upload_dir = wp_upload_dir();
    $file_name = 'ppsgen_contacts_export_' . gmdate('Y-m-d_H-i-s') . '.csv';
    $file_path = trailingslashit($upload_dir['path']) . $file_name;
    $file_url = trailingslashit($upload_dir['url']) . $file_name;
    // Get allowed dynamic fields
    $field_slugs = $this->getAllowedDynamicFieldSlugs();
    $field_types = $this->getModule()->getSubmod('contact_field')->getModel()->getContactFieldTypes();
    $valid_field_slugs = array_keys(array_intersect_key($field_slugs, $field_types));
    // Groups & tags
    $all_tags = $this->getModule()->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    // Headers
    $headers = ['contact_id', 'wp_user_id', 'verified', 'date_created', 'date_updated'];
    foreach ($valid_field_slugs as $slug) {
      $headers[] = 'field_' . $slug;
    }
    $headers[] = 'tag_ids';
    $headers[] = 'tag_names';
    // Write file with WP_Filesystem
    $output = "\xEF\xBB\xBF"; // BOM
    $output .= $this->arrayToCsv($headers);
    // Build WHERE
    $where = ['c.deleted_at IS NULL'];
    $params = [];
    if (!empty($ids) && is_array($ids)) {
      $sanitized_ids = array_map('intval', (array) $ids);
      $sanitized_ids = array_filter($sanitized_ids, function ($id) {
        return $id > 0;
      });
      if (!empty($sanitized_ids)) {
        $placeholders = implode(',', array_fill(0, count($sanitized_ids), '%d'));
        $params = $sanitized_ids;
      }
    }
    // Build query safely
    if (empty($params)) {
      $contacts = $wpdb->get_results("SELECT c.id AS contact_id, c.wp_user_id, c.verified, c.created_at AS date_created, c.updated_at AS date_updated FROM {$wpdb->prefix}ppsgen_contacts AS c WHERE 1=1", ARRAY_A);
    }
    if (!empty($params)) {
      // phpcs:disable WordPress.DB.PreparedSQL
      $contacts = $wpdb->get_results($wpdb->prepare("SELECT c.id AS contact_id, c.wp_user_id, c.verified, c.created_at AS date_created, c.updated_at AS date_updated FROM {$wpdb->prefix}ppsgen_contacts AS c WHERE c.id IN ($placeholders)", ...$params), ARRAY_A);
      // phpcs:enable
    }
    if (empty($contacts)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('No contacts found to export.', 'ppsgen'));
      return false;
    }
    foreach ($contacts as $contact) {
      $row = [$contact['contact_id'], $contact['wp_user_id'] ?: '', $contact['verified'], $contact['date_created'], $contact['date_updated']];
      // Dynamic fields
      foreach ($valid_field_slugs as $slug) {
        $field_id = $field_slugs[$slug];
        $value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE contact_id = %d AND field_id = %d", $contact['contact_id'], $field_id));
        $row[] = $value ?: '';
      }
      // Tags
      $tag_ids = $this->getModule()->getSubmod('contact_tag')->getModel()->getTagsByContactId($contact['contact_id'], false, true);
      $tag_names = [];
      foreach ($tag_ids as $tag_id) {
        if (isset($all_tags[$tag_id])) {
          $tag_names[] = $all_tags[$tag_id];
        }
      }
      $row[] = implode(',', $tag_ids);
      $row[] = implode(',', $tag_names);
      $output .= $this->arrayToCsv($row);
    }
    // Save via WP_Filesystem
    $wp_filesystem->put_contents($file_path, $output, FS_CHMOD_FILE);
    $this->setStatus('success');
    $this->setMessage(esc_html__('Contacts exported successfully.', 'ppsgen'));
    return [
      'file_url' => $file_url,
      'file_name' => $file_name,
    ];
  }

  private function arrayToCsv(array $fields, $delimiter = ';', $enclosure = '"')
  {
    $escaped = [];
    foreach ($fields as $field) {
      $field = (string) $field;
      $field = str_replace($enclosure, $enclosure . $enclosure, $field);
      if (preg_match("/[$delimiter\r\n$enclosure]/", $field)) {
        $field = $enclosure . $field . $enclosure;
      }
      $escaped[] = $field;
    }
    return implode($delimiter, $escaped) . "\n";
  }

  public function verifySubmit($obj_id, $rewrite = false)
  {
    global $wpdb;
    if (!empty($obj_id)) {
      $submit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_submits WHERE id = %d", (int) $obj_id), ARRAY_A);
      if (!empty($submit)) {
        $submit['fields_values'] = !empty($submit['fields_values']) ? $this->_prepareValueFromStore($submit['fields_values'], 'json') : [];
        if (!empty($submit['fields_values']['unverified_submit'])) {
          unset($submit['fields_values']['unverified_submit']);
        }
        if (!empty($submit['contact_id']) && !empty($submit['fields_values']) && $rewrite) {
          $this->saveContactFieldValues(['fields' => $submit['fields_values'], 'contact_id' => $submit['contact_id'], 'initiator' => 'form_submit']);
        }
        $submit['fields_values'] = !empty($submit['fields_values']) ? $this->_prepareValueToStore($submit['fields_values'], 'json') : '';
        return $wpdb->update("{$wpdb->prefix}ppsgen_form_submits", ['fields_values' => $submit['fields_values']], ['id' => $obj_id]);
      }
      return false;
    }
  }

  public function createContactBySubmit($data)
  {
    global $wpdb;
    $user_id = !empty($data['user_id']) ? $data['user_id'] : 0;
    $verified = !empty($data['verified']) ? $data['verified'] : 0;
    $res = $wpdb->insert("{$wpdb->prefix}ppsgen_contacts", [
      'wp_user_id' => $user_id,
      'verified' => $verified,
      'created_at' => current_time('mysql'),
      'updated_at' => current_time('mysql'),
    ]);
    if (empty($res)) {
      return false;
    } else {
      $contactId = $wpdb->insert_id;
    }
    if (!empty($data['tag_ids'])) {
      $this->saveContactTags($data['tag_ids'], $contactId, !empty($data['form_id']) ? 'form_submit' : (!empty($data['initiator']) ? (string) $data['initiator'] : 'system'));
    }
    if (!empty($data['fields'])) {
      $this->saveContactFieldValues(['fields' => $data['fields'], 'contact_id' => $contactId, 'user_id' => $user_id, 'form_id' => !empty($data['form_id']) ? (int) $data['form_id'] : 0, 'initiator' => !empty($data['form_id']) ? 'form_submit' : (!empty($data['initiator']) ? (string) $data['initiator'] : 'system')]);
    }
    return $contactId;
  }

  public function getContactIdsByFields($find_fields, $submitted_fields): array
  {
    global $wpdb;
    if (!is_array($find_fields) || !is_array($submitted_fields) || empty($find_fields)) {
      return [];
    }
    $field_ids = [];
    foreach ($find_fields as $slug) {
      $slug = sanitize_text_field($slug);
      $field_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = %s", $slug));
      if ($field_id) {
        $field_ids[$slug] = (int) $field_id;
      }
    }
    if (empty($field_ids)) {
      return [];
    }
    $joins = [];
    $params = [];
    $join_count = 0;
    foreach ($find_fields as $slug) {
      if (!isset($field_ids[$slug], $submitted_fields[$slug])) {
        continue;
      }
      $field_id = $field_ids[$slug];
      $value = $submitted_fields[$slug];
      if ($slug === 'email') {
        $value = sanitize_email($value);
      } else {
        $value = mb_strtolower(trim(sanitize_text_field($value)));
      }
      $alias = 'fv' . $join_count;
      $joins[] = "INNER JOIN {$wpdb->prefix}ppsgen_contact_field_values AS $alias 
                    ON c.id = $alias.contact_id 
                    AND $alias.field_id = %d 
                    AND LOWER(TRIM($alias.value)) = %s";
      $params[] = $field_id;
      $params[] = $value;
      $join_count++;
    }
    if (empty($joins)) {
      return [];
    }
    $joinSql = implode(' ', $joins);
    $sql = "
        SELECT c.id
        FROM {$wpdb->prefix}ppsgen_contacts AS c
        $joinSql
        GROUP BY c.id
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $results = $wpdb->get_col($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    if ($wpdb->last_error) {
      return [];
    }
    return array_map('intval', $results);
  }

  public function importCsvContacts($data, $csv_file)
  {
    // Validate input data
    if (empty($data) || empty($csv_file) || !is_array($data)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Import CSV Contacts Data is Empty', 'ppsgen'));
      return false;
    }
    // Security checks for uploaded file
    $file = !empty($csv_file['file']) ? $csv_file['file'] : '';
    $file_name = !empty($csv_file['name']) ? $csv_file['name'] : '';
    $file_size = !empty($csv_file['size']) ? $csv_file['size'] : '';
    if (empty($file) || empty($file_name) || empty($file_size)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('File is undefined', 'ppsgen'));
      return false;
    }
    // Validate file size (5MB limit)
    if ($file_size > 5 * 1024 * 1024) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('File size exceeds 5MB limit', 'ppsgen'));
      return false;
    }
    // Validate file type using wp_check_filetype
    $file_type_info = wp_check_filetype($file_name, ['csv' => 'text/csv']);
    if (!$file_type_info['type'] || !in_array($file_type_info['type'], ['text/csv'])) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Invalid file type. Please upload a valid CSV file', 'ppsgen'));
      return false;
    }
    global $wpdb;
    // Sanitize input parameters
    $duplicate_logic = !empty($data['duplicate_logic']) ? sanitize_text_field($data['duplicate_logic']) : 'skip';
    $import_tags = !empty($data['tags']) && is_array($data['tags']) ? array_map('intval', $data['tags']) : [];
    // Validate duplicate logic
    if (!in_array($duplicate_logic, ['skip', 'rewrite', 'update'])) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Invalid duplicate logic specified', 'ppsgen'));
      return false;
    }
    // Get allowed field slugs
    $allowed_fields = $this->getAllowedDynamicFieldSlugs();
    $valid_import_tags = [];
    if (!empty($import_tags)) {
      $placeholders = implode(',', array_fill(0, count($import_tags), '%d'));
      // phpcs:disable WordPress.DB.PreparedSQL
      $valid_import_tags = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id IN ($placeholders)", ...$import_tags));
      // phpcs:enable
      $valid_import_tags = array_map('intval', $valid_import_tags);
    }
    // Read and parse CSV file
    $csv_content = file_get_contents($file);
    if ($csv_content === false) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Failed to read CSV file', 'ppsgen'));
      return false;
    }
    // Strip UTF-8 BOM (added by our export and by Excel "CSV UTF-8")
    if (strncmp($csv_content, "\xEF\xBB\xBF", 3) === 0) {
      $csv_content = substr($csv_content, 3);
    }
    // Parse CSV with proper delimiter and encoding
    $csv_rows = preg_split("/\r\n|\n|\r/", trim($csv_content));
    if (empty($csv_rows)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Empty or invalid CSV file', 'ppsgen'));
      return false;
    }
    // Get headers from first row
    $headers = str_getcsv(array_shift($csv_rows), ';');
    $headers = array_map('trim', $headers);
    $created_count = 0;
    $limited_count = 0;
    $updated_count = 0;
    $skip_count = 0;
    // Validate required headers
    $required_headers = ['contact_id', 'wp_user_id', 'verified'];
    $missing_headers = array_diff($required_headers, $headers);
    if (!empty($missing_headers)) {
      $this->setStatus('error');
      $errorMsg = esc_html__('Missing required headers: ', 'ppsgen') . implode(', ', $missing_headers);
      $this->setMessage($errorMsg);
      return false;
    }
    $contactFieldModel = $this->getFrame()->getModule('contact')->getSubmod('contact_field')->getModel();
    foreach ($csv_rows as $row_index => $row) {
      $row = str_getcsv($row, ';');
      if (count($row) < count($headers)) {
        continue; // Skip malformed rows
      }
      $row_data = array_combine($headers, array_map('trim', $row));
      // Sanitize and validate core fields
      $contact_id = isset($row_data['contact_id']) ? (int) $row_data['contact_id'] : 0;
      $wp_user_id = isset($row_data['wp_user_id']) ? (int) $row_data['wp_user_id'] : 0;
      $verified = isset($row_data['verified']) ? (int) $row_data['verified'] : 0;
      // Extract group and tag IDs from CSV
      $csv_tag_ids = isset($row_data['tag_ids']) ? array_filter(array_map('intval', explode(',', $row_data['tag_ids']))) : [];
      // Validate CSV group and tag IDs
      $valid_csv_tag_ids = [];
      if (!empty($csv_tag_ids)) {
        $placeholders = implode(',', array_fill(0, count($csv_tag_ids), '%d'));
        // phpcs:disable WordPress.DB.PreparedSQL
        $valid_csv_tag_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id IN ($placeholders)", ...$csv_tag_ids));
        // phpcs:enable
        $valid_csv_tag_ids = array_map('intval', $valid_csv_tag_ids);
      }
      // Merge group and tag IDs (CSV + form inputs)
      $merged_tag_ids = array_unique(array_merge($valid_csv_tag_ids, $valid_import_tags));
      // Extract all dynamic fields (field_*)
      $fields = [];
      foreach ($row_data as $key => $value) {
        if (strpos($key, 'field_') === 0 && !empty($value)) {
          $slug = substr($key, 6); // Remove 'field_' prefix
          if (isset($allowed_fields[$slug])) {
            // Get field type for proper sanitization
            $field_type = $contactFieldModel->getContactFieldTypeBySlug($slug);
            switch ($field_type) {
              case 'email':
                $fields[$slug] = sanitize_email($value);
                break;
              case 'text':
              case 'textarea':
              case 'password':
              case 'hidden':
                $fields[$slug] = sanitize_text_field($value);
                break;
              case 'number':
                $fields[$slug] = (float) $value;
                break;
              case 'checkbox':
              case 'radio':
              case 'select':
                // For select/checkbox/radio, ensure value matches valid options
                $field_settings = $contactFieldModel->getContactFieldSettingJson($slug);
                $valid_options = !empty($field_settings['options']) ? array_column($field_settings['options'], 'value') : [];
                $allow_multiple = $field_type === 'checkbox' || !empty($field_settings['multiple_select']);
                $fields[$slug] = $this->prepareCsvChoiceFieldValue($value, $valid_options, $allow_multiple);
                break;
              default:
                $fields[$slug] = sanitize_text_field($value);
                break;
            }
          }
        }
      }
      // Skip if no valid data to process
      if (empty($fields) && empty($merged_tag_ids) && !$verified) {
        continue;
      }
      // Find existing contact
      $existing_contact_id = 0;
      if ($contact_id > 0) {
        $existing_contact_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE id = %d", $contact_id));
      } elseif ($wp_user_id > 0) {
        $existing_contact_id = $this->getContactIdByUserId($wp_user_id);
      } elseif (!empty($fields['email'])) {
        $existing_contact_id = (int) $wpdb->get_var($wpdb->prepare("SELECT c.id FROM {$wpdb->prefix}ppsgen_contacts c INNER JOIN {$wpdb->prefix}ppsgen_contact_field_values v ON c.id = v.contact_id WHERE v.field_id = %d AND v.value = %s", $allowed_fields['email'], $fields['email']));
      }
      // Prepare data structure for editContact
      $contact_data_edit = [
        'contact' => [
          'contact_fields' => $fields,
          'contact_tags' => ['selected_contact_tags' => $merged_tag_ids],
          'wp_user_id' => $wp_user_id,
          'verified' => $verified,
        ],
      ];
      $contact_data_create = [
        'fields' => $fields,
        'tag_ids' => $merged_tag_ids,
        'user_id' => $wp_user_id,
        'verified' => $verified,
      ];
      if ($existing_contact_id && $duplicate_logic === 'skip') {
        $skip_count++;
        continue;
      } elseif ($existing_contact_id && $duplicate_logic === 'rewrite') {
        // Delete existing field values
        $wpdb->delete("{$wpdb->prefix}ppsgen_contact_field_values", ['contact_id' => $existing_contact_id], ['%d']);
        // Delete existing group and tag associations
        $wpdb->delete("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => $existing_contact_id], ['%d']);
        // Update contact
        $result = $this->editContact($contact_data_edit, $existing_contact_id);
        if ($result) {
          $updated_count++;
        }
      } elseif ($existing_contact_id && $duplicate_logic === 'update') {
        // Update only provided fields
        $result = $this->editContact($contact_data_edit, $existing_contact_id);
        if ($result) {
          $updated_count++;
        }
      } elseif (function_exists('ppsgen_contact_import_allows_new') && !ppsgen_contact_import_allows_new()) {
        $limited_count++;
      } else {
        // Create new contact
        $new_contact_id = $this->createContactBySubmit($contact_data_create);
        if ($new_contact_id) {
          $created_count++;
        }
      }
    }
    // Clean up
    unset($csv_content, $csv_rows);
    if ($created_count > 0 || $updated_count > 0 || $skip_count > 0 || $limited_count > 0) {
      $this->setStatus('success');
      // translators: %1$1d - Created contacts count, %2$2d - Updated contacts count, %3$3d - Skipped contacts count
      $msg = sprintf(esc_html__('Successfully created %1$1d, updated %2$2d, skipped %3$3d contacts.', 'ppsgen'), $created_count, $updated_count, $skip_count);
      if ($limited_count > 0) {
        $msg .= ' ' . esc_html(ppsgen_get_contact_import_limit_message($limited_count));
      }
      $this->setMessage($msg);
      return true;
    } else {
      $this->setStatus('error');
      $this->setMessage(esc_html__('No new contacts were created or updated.', 'ppsgen'));
      return false;
    }
  }

  public function importGenerateContacts($data)
  {
    if (empty($data)) {
      $this->setStatus('error');
      $this->setMessage('Import Generate Contacts Data is Empty');
      return false;
    }
    global $wpdb;
    $user_email = !empty($data['user_email']) ? sanitize_text_field($data['user_email']) : '';
    $user_nick_name = !empty($data['user_nick_name']) ? sanitize_text_field($data['user_nick_name']) : '';
    $user_display_name = !empty($data['user_display_name']) ? sanitize_text_field($data['user_display_name']) : '';
    $user_role = !empty($data['user_role']) ? sanitize_text_field($data['user_role']) : '';
    $import_tags = !empty($data['tags']) && is_array($data['tags']) ? array_map('intval', $data['tags']) : [];
    $import_roles = !empty($data['roles']) && is_array($data['roles']) ? array_map('sanitize_text_field', $data['roles']) : ['all_roles'];
    $user_args = [
      'fields' => ['ID', 'user_email', 'user_nicename', 'display_name', 'roles'],
    ];
    if (!in_array('all_roles', $import_roles)) {
      $user_args['role__in'] = $import_roles;
    }
    $users = get_users($user_args);
    if (empty($users)) {
      $this->setStatus('error');
      $this->setMessage('No users found for the specified criteria');
      return false;
    }
    $created_count = 0;
    $limited_count = 0;
    foreach ($users as $user) {
      $existing_contact = $this->getContactIdByUserId($user->ID);
      if ($existing_contact) {
        continue;
      }
      $fields = [];
      if ($user_email && !empty($user->user_email)) {
        $fields[$user_email] = $user->user_email;
      }
      if ($user_nick_name && !empty($user->user_nicename)) {
        $fields[$user_nick_name] = $user->user_nicename;
      }
      if ($user_display_name && !empty($user->display_name)) {
        $fields[$user_display_name] = $user->display_name;
      }
      if ($user_role && !empty($user->roles)) {
        $fields[$user_role] = $user->roles[0];
      }
      // Prepare data for createContactBySubmit
      $contact_data = [
        'user_id' => $user->ID,
        'verified' => 1,
        'fields' => $fields,
        'tag_ids' => $import_tags,
      ];
      // Bulk import of existing users stops at the Free limit
      if (function_exists('ppsgen_contact_import_allows_new') && !ppsgen_contact_import_allows_new()) {
        $limited_count++;
        continue;
      }
      // Create contact
      $contact_id = $this->createContactBySubmit($contact_data);
      if ($contact_id) {
        $created_count++;
      }
    }
    if ($created_count > 0 || $limited_count > 0) {
      $this->setStatus('success');
      // translators: %d - Created contacts count
      $message = sprintf(esc_html__('Successfully created %d contacts.', 'ppsgen'), $created_count);
      if ($limited_count > 0) {
        $message .= ' ' . esc_html(ppsgen_get_contact_import_limit_message($limited_count));
      }
      $this->setMessage($message);
      return $created_count;
    } else {
      $this->setStatus('error');
      $this->setMessage(esc_html__('No new contacts were created.', 'ppsgen'));
      return false;
    }
  }

  protected function getContactAutoImportOptionGroup(): string
  {
    return 'autoimport_opts';
  }

  protected function getContactFieldSubmoduleModel()
  {
    if (!$this->getModule()->getSubmod('contact_field')) {
      $this->getModule()->registerSubmodule('contact_field');
    }
    return $this->getModule()->getSubmod('contact_field')->getModel();
  }

  protected function sanitizeValidContactTagIds(array $tagIds): array
  {
    global $wpdb;
    $tagIds = array_values(array_filter(array_map('absint', $tagIds)));
    if (empty($tagIds)) {
      return [];
    }
    $placeholders = implode(',', array_fill(0, count($tagIds), '%d'));
    // phpcs:disable WordPress.DB.PreparedSQL
    $validTagIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id IN ($placeholders)", ...$tagIds));
    // phpcs:enable WordPress.DB.PreparedSQL
    return array_map('intval', $validTagIds);
  }

  protected function sanitizeExistingContactIds(array $contactIds): array
  {
    global $wpdb;
    $contactIds = array_values(array_filter(array_map('absint', $contactIds)));
    if (empty($contactIds)) {
      return [];
    }
    $placeholders = implode(',', array_fill(0, count($contactIds), '%d'));
    // phpcs:disable WordPress.DB.PreparedSQL
    $validContactIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contacts WHERE id IN ($placeholders)", ...$contactIds));
    // phpcs:enable WordPress.DB.PreparedSQL
    return array_map('intval', $validContactIds);
  }

  public function bulkAddTagsToContacts(array $contactIds, array $tagIds, string $initiator = 'admin'): array
  {
    $selectedContactIds = array_values(array_unique(array_filter(array_map('absint', $contactIds))));
    $selectedTagIds = array_values(array_unique(array_filter(array_map('absint', $tagIds))));
    $validContactIds = $this->sanitizeExistingContactIds($selectedContactIds);
    $validTagIds = $this->sanitizeValidContactTagIds($selectedTagIds);

    if (empty($validContactIds)) {
      $this->setStatus('error');
      $this->setMessage(__('No valid contacts were selected.', 'ppsgen'));
      return [];
    }
    if (empty($validTagIds)) {
      $this->setStatus('error');
      $this->setMessage(__('Please select at least one valid tag.', 'ppsgen'));
      return [];
    }

    $updatedContacts = 0;
    $skippedContacts = 0;
    foreach ($validContactIds as $contactId) {
      $addedTagIds = $this->addContactTags($validTagIds, $contactId, $initiator);
      if (!empty($addedTagIds)) {
        $updatedContacts++;
      } else {
        $skippedContacts++;
      }
    }

    $skippedContacts += max(0, count($selectedContactIds) - count($validContactIds));
    $result = [
      'selected_contacts' => count($selectedContactIds),
      'updated_contacts' => $updatedContacts,
      'skipped_contacts' => $skippedContacts,
      'tag_ids' => $validTagIds,
    ];

    if ($updatedContacts > 0) {
      $message = sprintf(
        __('Tags were added to %1$d contact(s). Skipped: %2$d.', 'ppsgen'),
        $updatedContacts,
        $skippedContacts,
      );
    } else {
      $message = __('No new tags were added. Selected contacts already have these tags.', 'ppsgen');
    }

    $this->setStatus('success');
    $this->setMessage($message);
    return $result;
  }

  public function bulkDeleteTagsFromContacts(array $contactIds, array $tagIds, string $initiator = 'admin'): array
  {
    $selectedContactIds = array_values(array_unique(array_filter(array_map('absint', $contactIds))));
    $selectedTagIds = array_values(array_unique(array_filter(array_map('absint', $tagIds))));
    $validContactIds = $this->sanitizeExistingContactIds($selectedContactIds);
    $validTagIds = $this->sanitizeValidContactTagIds($selectedTagIds);

    if (empty($validContactIds)) {
      $this->setStatus('error');
      $this->setMessage(__('No valid contacts were selected.', 'ppsgen'));
      return [];
    }
    if (empty($validTagIds)) {
      $this->setStatus('error');
      $this->setMessage(__('Please select at least one valid tag.', 'ppsgen'));
      return [];
    }

    $updatedContacts = 0;
    $skippedContacts = 0;
    foreach ($validContactIds as $contactId) {
      $deletedTagIds = $this->deleteContactTags($validTagIds, $contactId, $initiator);
      if (!empty($deletedTagIds)) {
        $updatedContacts++;
      } else {
        $skippedContacts++;
      }
    }

    $skippedContacts += max(0, count($selectedContactIds) - count($validContactIds));
    $result = [
      'selected_contacts' => count($selectedContactIds),
      'updated_contacts' => $updatedContacts,
      'skipped_contacts' => $skippedContacts,
      'tag_ids' => $validTagIds,
    ];

    if ($updatedContacts > 0) {
      $message = sprintf(
        __('Tags were removed from %1$d contact(s). Skipped: %2$d.', 'ppsgen'),
        $updatedContacts,
        $skippedContacts,
      );
    } else {
      $message = __('No selected tags were removed. Selected contacts do not have these tags.', 'ppsgen');
    }

    $this->setStatus('success');
    $this->setMessage($message);
    return $result;
  }

  public function isWpContactAutoImportEnabled(): bool
  {
    return !empty($this->getOption('wp_user_autoimport_enabled', $this->getContactAutoImportOptionGroup(), 0, 'contact'));
  }

  public function isWooContactAutoImportEnabled(): bool
  {
    return !empty($this->getOption('woo_autoimport_enabled', $this->getContactAutoImportOptionGroup(), 0, 'contact'));
  }

  public function getWpContactAutoImportDefaults(): array
  {
    return [
      'user_email' => 'email',
      'user_nick_name' => 'first_name',
      'user_display_name' => 'last_name',
      'user_role' => '',
      'tags' => [],
      'roles' => ['all_roles'],
    ];
  }

  public function getWooContactAutoImportDefaults(): array
  {
    return [
      'sources' => ['orders', 'customers'],
      'billing_email' => 'email',
      'billing_first_name' => 'first_name',
      'billing_last_name' => 'last_name',
      'billing_phone' => 'phone',
      'billing_company' => '',
      'billing_city' => '',
      'billing_country' => '',
      'billing_postcode' => '',
      'customer_id' => '',
      'order_count' => '',
      'total_spent' => '',
      'tags' => [],
      'order_statuses' => $this->getWooImportDefaultOrderStatuses(),
      'date_from' => '',
      'date_to' => '',
    ];
  }

  public function getWpContactAutoImportConfig(): array
  {
    $stored = $this->getOption('wp_user_autoimport_settings', $this->getContactAutoImportOptionGroup(), 0, 'contact');
    $stored = is_array($stored) ? $stored : [];
    return array_merge($this->getWpContactAutoImportDefaults(), $stored);
  }

  public function getWooContactAutoImportConfig(): array
  {
    $stored = $this->getOption('woo_autoimport_settings', $this->getContactAutoImportOptionGroup(), 0, 'contact');
    $stored = is_array($stored) ? $stored : [];
    $fieldMap = !empty($stored['field_map']) && is_array($stored['field_map']) ? $stored['field_map'] : [];
    $renderConfig = [];
    foreach ([
      'billing_email',
      'billing_first_name',
      'billing_last_name',
      'billing_phone',
      'billing_company',
      'billing_city',
      'billing_country',
      'billing_postcode',
      'customer_id',
      'order_count',
      'total_spent',
    ] as $sourceField) {
      if (array_key_exists($sourceField, $fieldMap)) {
        $renderConfig[$sourceField] = (string) $fieldMap[$sourceField];
      }
    }
    if (array_key_exists('import_tags', $stored)) {
      $renderConfig['tags'] = is_array($stored['import_tags']) ? array_map('intval', $stored['import_tags']) : [];
    }
    foreach (['sources', 'order_statuses'] as $arrayKey) {
      if (array_key_exists($arrayKey, $stored)) {
        $renderConfig[$arrayKey] = is_array($stored[$arrayKey]) ? $stored[$arrayKey] : [];
      }
    }
    foreach (['date_from', 'date_to'] as $stringKey) {
      if (array_key_exists($stringKey, $stored)) {
        $renderConfig[$stringKey] = (string) $stored[$stringKey];
      }
    }
    $config = array_merge($this->getWooContactAutoImportDefaults(), $renderConfig);
    $config['order_statuses'] = $this->normalizeWooConfiguredOrderStatuses(!empty($config['order_statuses']) && is_array($config['order_statuses']) ? $config['order_statuses'] : []);
    return $config;
  }

  public function saveContactAutoImportSettings(string $source, array $data, bool $enabled)
  {
    $this->resetStatus();
    $source = sanitize_key($source);
    if ($source === 'wp_user') {
      $configData = !empty($data['contact_migration']) && is_array($data['contact_migration']) ? $data['contact_migration'] : [];
      $config = $this->sanitizeWpContactAutoImportConfig($configData, $enabled);
      if ($config === false) {
        return false;
      }
      $this->updateOption('wp_user_autoimport_settings', $this->getContactAutoImportOptionGroup(), $config, 0, 'contact', 'json');
      $this->updateOption('wp_user_autoimport_enabled', $this->getContactAutoImportOptionGroup(), $enabled ? 1 : 0, 0, 'contact', 'json');
      $this->setStatus('success');
      $this->setMessage($enabled
        ? esc_html__('WordPress user contact autoimport is enabled and settings were saved.', 'ppsgen')
        : esc_html__('WordPress user contact autoimport is disabled and settings were saved.', 'ppsgen'));
      return [
        'enabled' => $enabled,
        'config' => $config,
      ];
    }

    if ($source === 'woo') {
      if ($enabled && !$this->isWooCommerceActive()) {
        $this->setStatus('error');
        $this->setMessage(esc_html__('WooCommerce must be active to enable contact autoimport from orders and customers.', 'ppsgen'));
        return false;
      }
      $configData = !empty($data['woo_contact_migration']) && is_array($data['woo_contact_migration']) ? $data['woo_contact_migration'] : [];
      $config = $this->sanitizeWooContactMigration($configData, $enabled);
      if ($config === false) {
        return false;
      }
      $this->updateOption('woo_autoimport_settings', $this->getContactAutoImportOptionGroup(), $config, 0, 'contact', 'json');
      $this->updateOption('woo_autoimport_enabled', $this->getContactAutoImportOptionGroup(), $enabled ? 1 : 0, 0, 'contact', 'json');
      $this->setStatus('success');
      $this->setMessage($enabled
        ? esc_html__('WooCommerce contact autoimport is enabled and settings were saved.', 'ppsgen')
        : esc_html__('WooCommerce contact autoimport is disabled and settings were saved.', 'ppsgen'));
      return [
        'enabled' => $enabled,
        'config' => $config,
      ];
    }

    $this->setStatus('error');
    $this->setMessage(esc_html__('Invalid autoimport source provided.', 'ppsgen'));
    return false;
  }

  protected function sanitizeWpContactAutoImportConfig(array $data, bool $requireEmailMapping = true)
  {
    $allowedFields = $this->getAllowedDynamicFieldSlugs();
    $contactFieldModel = $this->getContactFieldSubmoduleModel();
    $mappedSources = ['user_email', 'user_nick_name', 'user_display_name', 'user_role'];
    $fieldMap = [];
    foreach ($mappedSources as $sourceField) {
      $targetSlug = isset($data[$sourceField]) ? sanitize_key((string) $data[$sourceField]) : '';
      $fieldMap[$sourceField] = isset($allowedFields[$targetSlug]) ? $targetSlug : '';
    }
    if ($requireEmailMapping && empty($fieldMap['user_email'])) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('User Email mapping is required to enable WordPress user contact autoimport.', 'ppsgen'));
      return false;
    }
    if (!empty($fieldMap['user_email'])) {
      $emailFieldType = (string) $contactFieldModel->getContactFieldTypeBySlug($fieldMap['user_email']);
      if ($emailFieldType !== 'email') {
        $this->setStatus('error');
        $this->setMessage(esc_html__('User Email must be mapped to a Contact Field with email type.', 'ppsgen'));
        return false;
      }
    }

    $importTags = !empty($data['tags']) && is_array($data['tags']) ? $this->sanitizeValidContactTagIds($data['tags']) : [];
    $allowedRoles = array_map('sanitize_key', array_keys(wp_roles()->get_names()));
    $allowedRoles[] = 'all_roles';
    $importRoles = !empty($data['roles']) && is_array($data['roles']) ? array_map('sanitize_key', $data['roles']) : ['all_roles'];
    $importRoles = array_values(array_unique(array_intersect($importRoles, $allowedRoles)));
    if (empty($importRoles)) {
      $importRoles = ['all_roles'];
    }

    return array_merge($fieldMap, [
      'tags' => $importTags,
      'roles' => $importRoles,
    ]);
  }

  protected function isAllowedWpAutoImportRole(array $userRoles, array $allowedRoles): bool
  {
    $allowedRoles = array_values(array_unique(array_map('sanitize_key', $allowedRoles)));
    if (empty($allowedRoles) || in_array('all_roles', $allowedRoles, true)) {
      return true;
    }
    $userRoles = array_values(array_unique(array_map('sanitize_key', $userRoles)));
    return !empty(array_intersect($userRoles, $allowedRoles));
  }

  public function autoImportWpUserById(int $userId): bool
  {
    if ($userId <= 0 || !$this->isWpContactAutoImportEnabled()) {
      return false;
    }
    $config = $this->getWpContactAutoImportConfig();
    if (empty($config['user_email'])) {
      return false;
    }
    $user = get_userdata($userId);
    if (!$user instanceof WP_User) {
      return false;
    }
    $userRoles = is_array($user->roles) ? $user->roles : [];
    if (!$this->isAllowedWpAutoImportRole($userRoles, !empty($config['roles']) && is_array($config['roles']) ? $config['roles'] : ['all_roles'])) {
      return false;
    }
    $userEmail = sanitize_email((string) $user->user_email);
    if ($userEmail === '' || !is_email($userEmail)) {
      return false;
    }
    $userData = [
      'user_email' => $userEmail,
      'user_nick_name' => sanitize_text_field((string) $user->user_nicename),
      'user_display_name' => sanitize_text_field((string) $user->display_name),
      'user_role' => !empty($userRoles) ? sanitize_text_field((string) reset($userRoles)) : '',
      'wp_user_id' => $userId,
    ];
    return $this->saveAutoImportedWpUserContact($userData, $config) !== 'error';
  }

  protected function prepareWpImportMappedFields(array $userData, array $fieldMap): array
  {
    $fields = [];
    foreach ($fieldMap as $sourceField => $targetSlug) {
      if (empty($targetSlug) || !array_key_exists($sourceField, $userData)) {
        continue;
      }
      $value = $userData[$sourceField];
      if ($value === '' || $value === null) {
        continue;
      }
      $sanitizedValue = $this->sanitizeContactFieldImportValue($targetSlug, $value);
      if ($sanitizedValue === '') {
        continue;
      }
      $fields[$targetSlug] = $sanitizedValue;
    }
    return $fields;
  }

  protected function findExistingWpAutoImportedContactId(int $userId, string $email, string $emailTarget): int
  {
    $contactId = (int) $this->getContactIdByUserId($userId);
    if (!empty($contactId)) {
      return $contactId;
    }
    $contactId = (int) $this->getContactIdByEmail($email);
    if (empty($contactId) && $emailTarget !== 'email') {
      $contactId = (int) $this->getContactIdByFieldSlugValue($emailTarget, $email);
    }
    return $contactId;
  }

  protected function contactNeedsImportTagSave(int $contactId, array $importTags): bool
  {
    global $wpdb;
    $importTags = array_values(array_unique(array_filter(array_map('intval', $importTags))));
    if ($contactId <= 0 || empty($importTags)) {
      return false;
    }
    $existingTags = $wpdb->get_col($wpdb->prepare("SELECT tag_id FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d", $contactId));
    $existingTags = array_map('intval', $existingTags);
    return !empty(array_diff($importTags, $existingTags));
  }

  protected function saveAutoImportedWpUserContact(array $userData, array $config): string
  {
    global $wpdb;
    $fieldMap = [
      'user_email' => !empty($config['user_email']) ? (string) $config['user_email'] : '',
      'user_nick_name' => !empty($config['user_nick_name']) ? (string) $config['user_nick_name'] : '',
      'user_display_name' => !empty($config['user_display_name']) ? (string) $config['user_display_name'] : '',
      'user_role' => !empty($config['user_role']) ? (string) $config['user_role'] : '',
    ];
    $preparedFields = $this->prepareWpImportMappedFields($userData, $fieldMap);
    if (empty($preparedFields)) {
      return 'skipped';
    }

    $userId = !empty($userData['wp_user_id']) ? absint($userData['wp_user_id']) : 0;
    $userEmail = sanitize_email((string) ($userData['user_email'] ?? ''));
    if ($userEmail === '' || !is_email($userEmail)) {
      return 'skipped';
    }

    $emailTarget = !empty($fieldMap['user_email']) ? $fieldMap['user_email'] : 'email';
    $existingContactId = $this->findExistingWpAutoImportedContactId($userId, $userEmail, $emailTarget);
    if (empty($existingContactId)) {
      $contactId = $this->createContactBySubmit([
        'user_id' => $userId,
        'verified' => 1,
        'fields' => $preparedFields,
        'tag_ids' => !empty($config['tags']) ? (array) $config['tags'] : [],
        'initiator' => 'wp_user_autoimport',
      ]);
      return !empty($contactId) ? 'created' : 'error';
    }

    $existingContact = $this->getContactById($existingContactId);
    if (empty($existingContact)) {
      return 'error';
    }

    $rowUpdateData = [];
    if (empty($existingContact['wp_user_id']) && $userId > 0) {
      $rowUpdateData['wp_user_id'] = $userId;
    }
    if (empty($existingContact['verified'])) {
      $rowUpdateData['verified'] = 1;
    }

    $fieldsToSave = [];
    foreach ($preparedFields as $slug => $value) {
      $existingValue = $this->getContactFieldValueBySlug($existingContactId, $slug);
      if ($existingValue === '' && $value !== '') {
        $fieldsToSave[$slug] = $value;
      }
    }

    $importTags = !empty($config['tags']) ? (array) $config['tags'] : [];
    $hasTagChanges = $this->contactNeedsImportTagSave($existingContactId, $importTags);
    if (empty($rowUpdateData) && empty($fieldsToSave) && !$hasTagChanges) {
      return 'skipped';
    }

    $rowUpdateData['updated_at'] = current_time('mysql');
    if ($wpdb->update("{$wpdb->prefix}ppsgen_contacts", $rowUpdateData, ['id' => $existingContactId]) === false) {
      return 'error';
    }
    if (!empty($fieldsToSave)) {
      $this->saveContactFieldValues([
        'fields' => $fieldsToSave,
        'contact_id' => $existingContactId,
        'user_id' => $userId,
        'initiator' => 'wp_user_autoimport',
      ]);
    }
    if ($hasTagChanges) {
      $this->addContactTags($importTags, $existingContactId, 'wp_user_autoimport');
    }
    return 'updated';
  }

  public function isWooCommerceActive(): bool
  {
    return class_exists('WooCommerce') && function_exists('wc_get_orders');
  }

  public function getWooImportOrderStatusOptions(): array
  {
    if (function_exists('wc_get_order_statuses')) {
      return (array) wc_get_order_statuses();
    }
    return [
      'wc-pending' => __('Pending payment', 'ppsgen'),
      'wc-processing' => __('Processing', 'ppsgen'),
      'wc-on-hold' => __('On hold', 'ppsgen'),
      'wc-completed' => __('Completed', 'ppsgen'),
      'wc-cancelled' => __('Cancelled', 'ppsgen'),
      'wc-refunded' => __('Refunded', 'ppsgen'),
      'wc-failed' => __('Failed', 'ppsgen'),
    ];
  }

  public function getWooImportDefaultOrderStatuses(): array
  {
    return ['wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending'];
  }

  protected function getWooLegacyImportDefaultOrderStatuses(): array
  {
    return ['wc-processing', 'wc-completed', 'wc-on-hold'];
  }

  protected function normalizeWooConfiguredOrderStatuses(array $orderStatuses): array
  {
    $allowedStatuses = array_keys($this->getWooImportOrderStatusOptions());
    $orderStatuses = array_values(array_unique(array_intersect(array_map('sanitize_key', $orderStatuses), $allowedStatuses)));
    if (empty($orderStatuses)) {
      return $this->getWooImportDefaultOrderStatuses();
    }
    $legacyDefaults = $this->getWooLegacyImportDefaultOrderStatuses();
    $sortedStatuses = $orderStatuses;
    sort($sortedStatuses);
    $sortedLegacy = $legacyDefaults;
    sort($sortedLegacy);
    if ($sortedStatuses === $sortedLegacy && !in_array('wc-pending', $orderStatuses, true) && in_array('wc-pending', $allowedStatuses, true)) {
      $orderStatuses[] = 'wc-pending';
    }
    return array_values(array_unique($orderStatuses));
  }

  public function autoImportWooCustomerById(int $customerId): bool
  {
    if ($customerId <= 0 || !$this->isWooContactAutoImportEnabled() || !$this->isWooCommerceActive()) {
      return false;
    }
    $config = $this->getWooContactAutoImportConfig();
    if (!in_array('customers', (array) ($config['sources'] ?? []), true)) {
      return false;
    }
    $user = get_userdata($customerId);
    $customerCreatedAt = $user instanceof WP_User && !empty($user->user_registered) ? (string) $user->user_registered : current_time('mysql');
    if (!$this->isDateWithinConfiguredRange($customerCreatedAt, (string) ($config['date_from'] ?? ''), (string) ($config['date_to'] ?? ''))) {
      return false;
    }

    try {
      $customer = new WC_Customer($customerId);
    } catch (Exception $e) {
      return false;
    }

    $email = sanitize_email((string) $customer->get_billing_email());
    if ($email === '') {
      $email = sanitize_email((string) $customer->get_email());
    }
    if ($email === '' || !is_email($email)) {
      return false;
    }

    $customerData = [
      'billing_email' => $email,
      'billing_first_name' => sanitize_text_field((string) $customer->get_billing_first_name()),
      'billing_last_name' => sanitize_text_field((string) $customer->get_billing_last_name()),
      'billing_phone' => sanitize_text_field((string) $customer->get_billing_phone()),
      'billing_company' => sanitize_text_field((string) $customer->get_billing_company()),
      'billing_city' => sanitize_text_field((string) $customer->get_billing_city()),
      'billing_country' => sanitize_text_field((string) $customer->get_billing_country()),
      'billing_postcode' => sanitize_text_field((string) $customer->get_billing_postcode()),
      'customer_id' => $customerId,
      'order_count' => function_exists('wc_get_customer_order_count') ? (int) wc_get_customer_order_count($customerId) : 0,
      'total_spent' => function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($customerId) : 0.0,
      'wp_user_id' => $customerId,
      'has_order_source' => false,
    ];

    return $this->saveWooImportedContact($customerData, $config, 'woo_autoimport') !== 'error';
  }

  public function autoImportWooOrderById(int $orderId, $order = null): bool
  {
    if ($orderId <= 0 || !$this->isWooContactAutoImportEnabled() || !$this->isWooCommerceActive()) {
      return false;
    }
    $config = $this->getWooContactAutoImportConfig();
    if (!in_array('orders', (array) ($config['sources'] ?? []), true)) {
      return false;
    }
    if (!is_object($order) || !method_exists($order, 'get_billing_email')) {
      $order = function_exists('wc_get_order') ? wc_get_order($orderId) : null;
    }
    if (!is_object($order) || !method_exists($order, 'get_billing_email')) {
      return false;
    }
    if ($this->hasWooOrderAutoImportBeenProcessed($order)) {
      return false;
    }

    $orderStatus = $this->normalizeWooImportOrderStatus((string) $order->get_status());
    $allowedStatuses = $this->normalizeWooConfiguredOrderStatuses(!empty($config['order_statuses']) && is_array($config['order_statuses']) ? $config['order_statuses'] : []);
    if (!in_array($orderStatus, $allowedStatuses, true)) {
      return false;
    }

    $orderCreatedAt = '';
    $orderDateCreated = $order->get_date_created();
    if ($orderDateCreated && method_exists($orderDateCreated, 'date')) {
      $orderCreatedAt = (string) $orderDateCreated->date('Y-m-d H:i:s');
    }
    if (!$this->isDateWithinConfiguredRange($orderCreatedAt, (string) ($config['date_from'] ?? ''), (string) ($config['date_to'] ?? ''))) {
      return false;
    }

    $email = sanitize_email((string) $order->get_billing_email());
    if ($email === '' || !is_email($email)) {
      return false;
    }

    $customerData = [
      'billing_email' => $email,
      'billing_first_name' => sanitize_text_field((string) $order->get_billing_first_name()),
      'billing_last_name' => sanitize_text_field((string) $order->get_billing_last_name()),
      'billing_phone' => sanitize_text_field((string) $order->get_billing_phone()),
      'billing_company' => sanitize_text_field((string) $order->get_billing_company()),
      'billing_city' => sanitize_text_field((string) $order->get_billing_city()),
      'billing_country' => sanitize_text_field((string) $order->get_billing_country()),
      'billing_postcode' => sanitize_text_field((string) $order->get_billing_postcode()),
      'customer_id' => absint($order->get_customer_id()),
      'order_count' => 1,
      'total_spent' => (float) $order->get_total(),
      'wp_user_id' => absint($order->get_customer_id()),
      'has_order_source' => true,
    ];

    $saveResult = $this->saveWooImportedContact($customerData, $config, 'woo_autoimport');
    if (in_array($saveResult, ['created', 'updated', 'skipped'], true)) {
      $this->markWooOrderAutoImportProcessed($order);
      return true;
    }
    return false;
  }

  protected function normalizeWooImportOrderStatus(string $status): string
  {
    $status = sanitize_key($status);
    if ($status === '') {
      return '';
    }
    return strpos($status, 'wc-') === 0 ? $status : 'wc-' . ltrim($status, '-');
  }

  protected function isDateWithinConfiguredRange(string $dateValue, string $dateFrom, string $dateTo): bool
  {
    if ($dateFrom === '' && $dateTo === '') {
      return true;
    }
    $timestamp = strtotime($dateValue);
    if (empty($timestamp)) {
      return false;
    }
    if ($dateFrom !== '' && $timestamp < strtotime($dateFrom . ' 00:00:00')) {
      return false;
    }
    if ($dateTo !== '' && $timestamp > strtotime($dateTo . ' 23:59:59')) {
      return false;
    }
    return true;
  }

  protected function hasWooOrderAutoImportBeenProcessed($order): bool
  {
    $orderId = method_exists($order, 'get_id') ? (int) $order->get_id() : 0;
    if ($orderId <= 0) {
      return true;
    }
    if (!empty($this->autoImportedWooOrderIds[$orderId])) {
      return true;
    }
    return !empty($order->get_meta('_ppsgen_contact_autoimport_processed', true));
  }

  protected function markWooOrderAutoImportProcessed($order): void
  {
    if (!is_object($order) || !method_exists($order, 'get_id')) {
      return;
    }
    $orderId = (int) $order->get_id();
    if ($orderId <= 0) {
      return;
    }
    $this->autoImportedWooOrderIds[$orderId] = true;
    if (method_exists($order, 'update_meta_data')) {
      $order->update_meta_data('_ppsgen_contact_autoimport_processed', current_time('mysql'));
      if (method_exists($order, 'save_meta_data')) {
        $order->save_meta_data();
      }
    }
  }

  public function importGenerateWooContacts($data)
  {
    if (empty($data) || !is_array($data)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('WooCommerce import data is empty.', 'ppsgen'));
      return false;
    }
    if (!$this->isWooCommerceActive()) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('WooCommerce must be active to import contacts from orders.', 'ppsgen'));
      return false;
    }

    $config = $this->sanitizeWooContactMigration($data);
    if ($config === false) {
      return false;
    }

    $batchSize = 100;
    $skippedCount = 0;
    $errorsCount = 0;
    $customers = [];
    if (in_array('orders', $config['sources'], true)) {
      $collectionResult = $this->collectWooOrderImportCustomers($customers, $config, $batchSize, $skippedCount, $errorsCount);
      if ($collectionResult === false) {
        return false;
      }
    }
    if (in_array('customers', $config['sources'], true)) {
      $this->collectWooRegisteredImportCustomers($customers, $batchSize, $skippedCount, $errorsCount);
    }

    if (empty($customers)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('No WooCommerce customers found for the selected filters.', 'ppsgen'));
      return false;
    }

    $createdCount = 0;
    $updatedCount = 0;
    $limitedCount = 0;
    foreach ($customers as $customer) {
      $saveResult = $this->saveWooImportedContact($customer, $config);
      if ($saveResult === 'created') {
        $createdCount++;
      } elseif ($saveResult === 'updated') {
        $updatedCount++;
      } elseif ($saveResult === 'skipped') {
        $skippedCount++;
      } elseif ($saveResult === 'limited') {
        $limitedCount++;
      } else {
        $errorsCount++;
      }
    }

    $message = sprintf(
      /* translators: 1: created count, 2: updated count, 3: skipped count, 4: errors count */
      esc_html__('WooCommerce import finished. created: %1$d updated: %2$d skipped: %3$d errors: %4$d', 'ppsgen'),
      $createdCount,
      $updatedCount,
      $skippedCount,
      $errorsCount,
    );
    if ($limitedCount > 0) {
      $message .= ' ' . esc_html(ppsgen_get_contact_import_limit_message($limitedCount));
    }

    if ($createdCount > 0 || $updatedCount > 0 || $skippedCount > 0 || $limitedCount > 0) {
      $this->setStatus('success');
      $this->setMessage($message);
      return [
        'created' => $createdCount,
        'updated' => $updatedCount,
        'skipped' => $skippedCount,
        'errors' => $errorsCount,
      ];
    }

    $this->setStatus('error');
    $this->setMessage($message);
    return false;
  }

  protected function sanitizeWooContactMigration(array $data, bool $requireEmailMapping = true)
  {
    $allowedFields = $this->getAllowedDynamicFieldSlugs();
    $contactFieldModel = $this->getContactFieldSubmoduleModel();
    $mappedSources = [
      'billing_email',
      'billing_first_name',
      'billing_last_name',
      'billing_phone',
      'billing_company',
      'billing_city',
      'billing_country',
      'billing_postcode',
      'customer_id',
      'order_count',
      'total_spent',
    ];
    $fieldMap = [];
    foreach ($mappedSources as $sourceField) {
      $targetSlug = isset($data[$sourceField]) ? sanitize_key((string) $data[$sourceField]) : '';
      $fieldMap[$sourceField] = isset($allowedFields[$targetSlug]) ? $targetSlug : '';
    }
    if ($requireEmailMapping && empty($fieldMap['billing_email'])) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Billing Email mapping is required.', 'ppsgen'));
      return false;
    }
    $billingEmailFieldType = !empty($fieldMap['billing_email']) ? (string) $contactFieldModel->getContactFieldTypeBySlug($fieldMap['billing_email']) : '';
    if (!empty($fieldMap['billing_email']) && $billingEmailFieldType !== 'email') {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Billing Email must be mapped to a Contact Field with email type.', 'ppsgen'));
      return false;
    }

    $validImportTags = !empty($data['tags']) && is_array($data['tags']) ? $this->sanitizeValidContactTagIds($data['tags']) : [];

    $allowedStatuses = array_keys($this->getWooImportOrderStatusOptions());
    $orderStatuses = !empty($data['order_statuses']) && is_array($data['order_statuses']) ? array_map('sanitize_key', $data['order_statuses']) : [];
    $orderStatuses = array_values(array_intersect($orderStatuses, $allowedStatuses));
    if (empty($orderStatuses)) {
      $orderStatuses = $this->getWooImportDefaultOrderStatuses();
    }

    $sources = !empty($data['sources']) && is_array($data['sources']) ? array_map('sanitize_key', $data['sources']) : ['orders', 'customers'];
    $sources = array_values(array_intersect($sources, ['orders', 'customers']));
    if (empty($sources)) {
      $sources = ['orders', 'customers'];
    }

    $dateFrom = isset($data['date_from']) ? sanitize_text_field((string) $data['date_from']) : '';
    $dateTo = isset($data['date_to']) ? sanitize_text_field((string) $data['date_to']) : '';
    if ($dateFrom !== '' && !$this->isValidImportDate($dateFrom)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Date From is invalid. Use YYYY-MM-DD format.', 'ppsgen'));
      return false;
    }
    if ($dateTo !== '' && !$this->isValidImportDate($dateTo)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Date To is invalid. Use YYYY-MM-DD format.', 'ppsgen'));
      return false;
    }
    if ($dateFrom !== '' && $dateTo !== '' && strtotime($dateFrom) > strtotime($dateTo)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Date From cannot be later than Date To.', 'ppsgen'));
      return false;
    }

    return [
      'field_map' => $fieldMap,
      'import_tags' => $validImportTags,
      'sources' => $sources,
      'order_statuses' => $orderStatuses,
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
    ];
  }

  protected function collectWooOrderImportCustomers(array &$customers, array $config, int $batchSize, int &$skippedCount, int &$errorsCount): bool
  {
    $page = 1;
    $dateCreated = $this->buildWooImportDateCreatedQuery($config['date_from'], $config['date_to']);

    do {
      $queryArgs = [
        'type' => 'shop_order',
        'status' => $config['order_statuses'],
        'limit' => $batchSize,
        'paged' => $page,
        'paginate' => true,
        'return' => 'objects',
        'orderby' => 'date',
        'order' => 'ASC',
      ];
      if ($dateCreated !== '') {
        $queryArgs['date_created'] = $dateCreated;
      }
      $results = wc_get_orders($queryArgs);
      if (!is_object($results) || !isset($results->orders)) {
        $this->setStatus('error');
        $this->setMessage(esc_html__('Failed to load WooCommerce orders for import.', 'ppsgen'));
        return false;
      }

      foreach ((array) $results->orders as $order) {
        if (!is_object($order) || !method_exists($order, 'get_billing_email')) {
          $errorsCount++;
          continue;
        }
        $email = sanitize_email((string) $order->get_billing_email());
        if (empty($email) || !is_email($email)) {
          $skippedCount++;
          continue;
        }
        $customerData = [
          'billing_email' => $email,
          'billing_first_name' => '',
          'billing_last_name' => '',
          'billing_phone' => '',
          'billing_company' => '',
          'billing_city' => '',
          'billing_country' => '',
          'billing_postcode' => '',
          'customer_id' => absint($order->get_customer_id()),
          'order_count' => 1,
          'total_spent' => (float) $order->get_total(),
          'wp_user_id' => absint($order->get_customer_id()),
          'has_order_source' => true,
        ];
        foreach (['billing_first_name', 'billing_last_name', 'billing_phone', 'billing_company', 'billing_city', 'billing_country', 'billing_postcode'] as $sourceField) {
          $getter = 'get_' . $sourceField;
          if (!method_exists($order, $getter)) {
            continue;
          }
          $value = sanitize_text_field((string) $order->{$getter}());
          if ($value !== '') {
            $customerData[$sourceField] = $value;
          }
        }
        $this->mergeWooImportCustomer($customers, $customerData, 'order');
      }

      $maxPages = isset($results->max_num_pages) ? max(1, (int) $results->max_num_pages) : 1;
      $page++;
    } while ($page <= $maxPages);

    return true;
  }

  protected function collectWooRegisteredImportCustomers(array &$customers, int $batchSize, int &$skippedCount, int &$errorsCount): void
  {
    $offset = 0;
    do {
      $userQuery = new WP_User_Query([
        'fields' => 'ID',
        'number' => $batchSize,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
      ]);
      $userIds = $userQuery->get_results();
      if (empty($userIds)) {
        break;
      }
      foreach ($userIds as $userId) {
        $userId = absint($userId);
        if ($userId <= 0) {
          continue;
        }
        if (!$this->isWooRegisteredCustomerUser($userId)) {
          continue;
        }
        try {
          $customer = new WC_Customer($userId);
        } catch (Exception $e) {
          $errorsCount++;
          continue;
        }
        $email = sanitize_email((string) $customer->get_billing_email());
        if ($email === '') {
          $email = sanitize_email((string) $customer->get_email());
        }
        if (empty($email) || !is_email($email)) {
          $skippedCount++;
          continue;
        }
        $customerData = [
          'billing_email' => $email,
          'billing_first_name' => sanitize_text_field((string) $customer->get_billing_first_name()),
          'billing_last_name' => sanitize_text_field((string) $customer->get_billing_last_name()),
          'billing_phone' => sanitize_text_field((string) $customer->get_billing_phone()),
          'billing_company' => sanitize_text_field((string) $customer->get_billing_company()),
          'billing_city' => sanitize_text_field((string) $customer->get_billing_city()),
          'billing_country' => sanitize_text_field((string) $customer->get_billing_country()),
          'billing_postcode' => sanitize_text_field((string) $customer->get_billing_postcode()),
          'customer_id' => $userId,
          'order_count' => function_exists('wc_get_customer_order_count') ? (int) wc_get_customer_order_count($userId) : 0,
          'total_spent' => function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($userId) : 0.0,
          'wp_user_id' => $userId,
          'has_order_source' => false,
        ];
        $this->mergeWooImportCustomer($customers, $customerData, 'customer');
      }
      $offset += $batchSize;
    } while (count($userIds) === $batchSize);
  }

  protected function isWooRegisteredCustomerUser(int $userId): bool
  {
    if ($userId <= 0) {
      return false;
    }
    $user = get_userdata($userId);
    if (!$user instanceof WP_User) {
      return false;
    }
    $roles = is_array($user->roles) ? array_map('sanitize_key', $user->roles) : [];
    if (in_array('customer', $roles, true)) {
      return true;
    }

    $orderCount = function_exists('wc_get_customer_order_count') ? (int) wc_get_customer_order_count($userId) : 0;
    $totalSpent = function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($userId) : 0.0;
    if ($orderCount > 0 || $totalSpent > 0) {
      return true;
    }

    try {
      $customer = new WC_Customer($userId);
    } catch (Exception $e) {
      return false;
    }

    if ($customer->get_is_paying_customer()) {
      return true;
    }

    foreach ($this->getWooCustomerProfileValues($customer) as $value) {
      if ($value !== '') {
        return true;
      }
    }
    return false;
  }

  protected function getWooCustomerProfileValues(WC_Customer $customer): array
  {
    return array_filter([
      sanitize_email((string) $customer->get_billing_email()),
      sanitize_text_field((string) $customer->get_billing_first_name()),
      sanitize_text_field((string) $customer->get_billing_last_name()),
      sanitize_text_field((string) $customer->get_billing_phone()),
      sanitize_text_field((string) $customer->get_billing_company()),
      sanitize_text_field((string) $customer->get_billing_city()),
      sanitize_text_field((string) $customer->get_billing_country()),
      sanitize_text_field((string) $customer->get_billing_postcode()),
    ]);
  }

  protected function mergeWooImportCustomer(array &$customers, array $customerData, string $source): void
  {
    $email = sanitize_email((string) ($customerData['billing_email'] ?? ''));
    if ($email === '' || !is_email($email)) {
      return;
    }
    $emailKey = strtolower($email);
    if (empty($customers[$emailKey])) {
      $customers[$emailKey] = [
        'billing_email' => $email,
        'billing_first_name' => '',
        'billing_last_name' => '',
        'billing_phone' => '',
        'billing_company' => '',
        'billing_city' => '',
        'billing_country' => '',
        'billing_postcode' => '',
        'customer_id' => '',
        'order_count' => 0,
        'total_spent' => 0.0,
        'wp_user_id' => 0,
        'has_order_source' => false,
      ];
    }

    $existing = &$customers[$emailKey];
    $existing['billing_email'] = $email;
    if (empty($existing['customer_id']) && !empty($customerData['customer_id'])) {
      $existing['customer_id'] = absint($customerData['customer_id']);
    }
    if (empty($existing['wp_user_id']) && !empty($customerData['wp_user_id'])) {
      $existing['wp_user_id'] = absint($customerData['wp_user_id']);
    }
    foreach (['billing_first_name', 'billing_last_name', 'billing_phone', 'billing_company', 'billing_city', 'billing_country', 'billing_postcode'] as $sourceField) {
      $value = isset($customerData[$sourceField]) ? sanitize_text_field((string) $customerData[$sourceField]) : '';
      if ($value !== '') {
        $existing[$sourceField] = $value;
      }
    }

    if ($source === 'order') {
      $existing['has_order_source'] = true;
      $existing['order_count'] += (int) ($customerData['order_count'] ?? 0);
      $existing['total_spent'] += (float) ($customerData['total_spent'] ?? 0);
    } elseif (empty($existing['has_order_source'])) {
      $existing['order_count'] = (int) ($customerData['order_count'] ?? 0);
      $existing['total_spent'] = (float) ($customerData['total_spent'] ?? 0);
    }
  }

  protected function isValidImportDate(string $date): bool
  {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      return false;
    }
    [$year, $month, $day] = array_map('intval', explode('-', $date));
    return checkdate($month, $day, $year);
  }

  protected function buildWooImportDateCreatedQuery(string $dateFrom, string $dateTo): string
  {
    if ($dateFrom !== '' && $dateTo !== '') {
      return $dateFrom . '...' . $dateTo;
    }
    if ($dateFrom !== '') {
      return '>=' . $dateFrom;
    }
    if ($dateTo !== '') {
      return '<=' . $dateTo;
    }
    return '';
  }

  protected function saveWooImportedContact(array $customer, array $config, string $initiator = 'woo_import'): string
  {
    global $wpdb;
    $fieldMap = !empty($config['field_map']) && is_array($config['field_map']) ? $config['field_map'] : [];
    $preparedFields = $this->prepareWooImportMappedFields($customer, $fieldMap);
    if (empty($preparedFields)) {
      return 'skipped';
    }

    $billingEmail = sanitize_email((string) ($customer['billing_email'] ?? ''));
    if ($billingEmail === '' || !is_email($billingEmail)) {
      return 'skipped';
    }

    $billingEmailTarget = !empty($fieldMap['billing_email']) ? $fieldMap['billing_email'] : 'email';
    $existingContactId = (int) $this->getContactIdByEmail($billingEmail);
    if (empty($existingContactId) && $billingEmailTarget !== 'email') {
      $existingContactId = (int) $this->getContactIdByFieldSlugValue($billingEmailTarget, $billingEmail);
    }

    if (empty($existingContactId) && $initiator === 'woo_import' && function_exists('ppsgen_contact_import_allows_new') && !ppsgen_contact_import_allows_new()) {
      // Past orders are a bulk import; new customers (woo_autoimport) are never blocked
      return 'limited';
    }
    if (empty($existingContactId)) {
      $contactId = $this->createContactBySubmit([
        'user_id' => !empty($customer['wp_user_id']) ? absint($customer['wp_user_id']) : 0,
        'verified' => 1,
        'fields' => $preparedFields,
        'tag_ids' => !empty($config['import_tags']) ? $config['import_tags'] : [],
        'initiator' => $initiator,
      ]);
      return !empty($contactId) ? 'created' : 'error';
    }

    $existingContact = $this->getContactById($existingContactId);
    if (empty($existingContact)) {
      return 'error';
    }

    $rowUpdateData = ['updated_at' => current_time('mysql')];
    $hasRowChanges = false;
    if (empty($existingContact['wp_user_id']) && !empty($customer['wp_user_id'])) {
      $rowUpdateData['wp_user_id'] = absint($customer['wp_user_id']);
      $hasRowChanges = true;
    }
    if (empty($existingContact['verified'])) {
      $rowUpdateData['verified'] = 1;
      $hasRowChanges = true;
    }

    $fieldsToSave = [];
    foreach ($preparedFields as $slug => $value) {
      $existingValue = $this->getContactFieldValueBySlug($existingContactId, $slug);
      $shouldAlwaysUpdate = in_array($slug, $this->getWooImportAlwaysUpdateFieldTargets($fieldMap), true);
      if ($shouldAlwaysUpdate) {
        if ((string) $existingValue !== (string) $value) {
          $fieldsToSave[$slug] = $value;
        }
        continue;
      }
      if ($existingValue === '' && $value !== '') {
        $fieldsToSave[$slug] = $value;
      }
    }

    $hasTagChanges = false;
    $importTags = !empty($config['import_tags']) ? $config['import_tags'] : [];
    if (!empty($importTags)) {
      global $wpdb;
      $existingTags = $wpdb->get_col($wpdb->prepare("SELECT tag_id FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d", $existingContactId));
      $existingTags = array_map('intval', $existingTags);
      $hasTagChanges = !empty(array_diff($importTags, $existingTags));
    }

    if (!$hasRowChanges && empty($fieldsToSave) && !$hasTagChanges) {
      return 'skipped';
    }

    if ($wpdb->update("{$wpdb->prefix}ppsgen_contacts", $rowUpdateData, ['id' => $existingContactId]) === false) {
      return 'error';
    }
    if (!empty($fieldsToSave)) {
      $this->saveContactFieldValues([
        'fields' => $fieldsToSave,
        'contact_id' => $existingContactId,
        'user_id' => !empty($customer['wp_user_id']) ? absint($customer['wp_user_id']) : 0,
        'initiator' => $initiator,
      ]);
    }
    if ($hasTagChanges) {
      $this->addContactTags($importTags, $existingContactId, $initiator);
    }

    return 'updated';
  }

  protected function prepareWooImportMappedFields(array $customer, array $fieldMap): array
  {
    $fields = [];
    foreach ($fieldMap as $sourceField => $targetSlug) {
      if (empty($targetSlug) || !array_key_exists($sourceField, $customer)) {
        continue;
      }
      $value = $customer[$sourceField];
      if ($value === '' || $value === null) {
        continue;
      }
      if ($sourceField === 'total_spent') {
        $decimals = function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 2;
        $value = function_exists('wc_format_decimal') ? wc_format_decimal($value, $decimals) : (string) round((float) $value, $decimals);
      } elseif ($sourceField === 'order_count' || $sourceField === 'customer_id') {
        $value = (int) $value;
      }
      $sanitizedValue = $this->sanitizeContactFieldImportValue($targetSlug, $value);
      if ($sanitizedValue === '' && $sourceField !== 'customer_id' && $sourceField !== 'order_count' && $sourceField !== 'total_spent') {
        continue;
      }
      $fields[$targetSlug] = $sanitizedValue;
    }
    return $fields;
  }

  protected function sanitizeContactFieldImportValue(string $targetSlug, $value)
  {
    $contactFieldModel = $this->getContactFieldSubmoduleModel();
    $fieldType = (string) $contactFieldModel->getContactFieldTypeBySlug($targetSlug);
    switch ($fieldType) {
      case 'email':
        $value = sanitize_email((string) $value);
        return is_email($value) ? $value : '';
      case 'number':
      case 'range':
      case 'rating':
        return is_numeric($value) ? (string) $value : '';
      case 'checkbox':
      case 'radio':
      case 'select':
        $fieldSettings = $contactFieldModel->getContactFieldSettingJson($targetSlug);
        $validOptions = !empty($fieldSettings['options']) && is_array($fieldSettings['options']) ? array_column($fieldSettings['options'], 'value') : [];
        $value = sanitize_text_field((string) $value);
        return in_array($value, $validOptions, true) ? $value : '';
      default:
        return sanitize_text_field((string) $value);
    }
  }

  protected function getWooImportAlwaysUpdateFieldTargets(array $fieldMap): array
  {
    $alwaysUpdateSources = ['customer_id', 'order_count', 'total_spent'];
    $targets = [];
    foreach ($alwaysUpdateSources as $sourceField) {
      if (!empty($fieldMap[$sourceField])) {
        $targets[] = $fieldMap[$sourceField];
      }
    }
    return array_values(array_unique($targets));
  }

  public function contactHasTags($contact_id, $tags, $condition = 'or')
  {
    global $wpdb;
    $contact_id = (int) $contact_id;
    if ($contact_id <= 0) {
      return false;
    }
    if (!is_array($tags)) {
      $tags = [$tags];
    }
    $tags = array_filter(array_map('intval', $tags));
    if (empty($tags)) {
      return false;
    }
    $placeholders = implode(',', array_fill(0, count($tags), '%d'));
    if ($condition === 'and') {
      return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT tag_id) FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id IN ($placeholders)", $contact_id, ...$tags)) === count($tags);
    }
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id IN ($placeholders) LIMIT 1", $contact_id, ...$tags));
  }
  public function contactHasNotTags(int $contact_id, $tags, string $condition = 'or'): bool
  {
    global $wpdb;
    if ($contact_id <= 0) {
      return false;
    }
    if (!is_array($tags)) {
      $tags = [$tags];
    }
    $tags = array_values(array_filter(array_map('intval', $tags)));
    if (empty($tags)) {
      return false;
    }
    $placeholders = implode(',', array_fill(0, count($tags), '%d'));
    if ($condition === 'and') {
      $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT tag_id) FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id IN ($placeholders)", $contact_id, ...$tags));
      return $count !== count($tags);
    }
    return !(bool) $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id IN ($placeholders) LIMIT 1", $contact_id, ...$tags));
  }
  public function triggerContactUpdated($contact_id)
  {
    $module = $this->getFrame()->getModule('eca');
    if ($module) {
      $module->getModel()->triggerChangeContactFlow($contact_id);
    }
  }

  // Contacts list details panel: who they are, tags and the latest events
  public function getContactPreview(int $contactId): array
  {
    $contact = $this->getContactById($contactId);
    if (empty($contact)) {
      return [];
    }
    $name = trim($this->getContactFieldValueBySlug($contactId, 'first_name') . ' ' . $this->getContactFieldValueBySlug($contactId, 'last_name'));
    $tags = [];
    $tagModule = $this->getFrame()->getModule('contact')->getSubmod('contact_tag');
    if ($tagModule) {
      $tags = array_values(array_filter((array) $tagModule->getModel()->getTagsByContactId($contactId, true)));
    }
    $timeline = array_reverse($this->getContactTimeline($contactId));
    return [
      'id' => $contactId,
      'name' => $name,
      'email' => $this->getContactFieldValueBySlug($contactId, 'email'),
      'phone' => $this->getContactFieldValueBySlug($contactId, 'phone'),
      'verified' => !empty($contact['verified']),
      'created_at' => (string) ($contact['created_at'] ?? ''),
      'tags' => $tags,
      'replied' => in_array('replied', $tags, true),
      'automations' => $this->getContactAutomationChoices($contactId),
      'timeline' => array_slice($timeline, 0, 6),
      'timeline_total' => count($timeline),
      'edit_url' => admin_url('admin.php?page=ppsgen&tab=contact_edit&id=' . $contactId),
    ];
  }

  // Details panel "Add to automation": every automation (not newsletters), and whether this contact is in it now
  public function getContactAutomationChoices(int $contactId): array
  {
    global $wpdb;
    $eca = $this->getFrame()->getModule('eca');
    if (!$eca || $contactId <= 0) {
      return [];
    }
    $newsletters = array_map('intval', (array) $eca->getModel()->getNewsletterIds());
    $inFlow = array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT DISTINCT chain_id FROM {$wpdb->prefix}ppsgen_eca_flow WHERE contact_id = %d AND status = 'active'", $contactId)));
    $choices = [];
    foreach ((array) $wpdb->get_results("SELECT id, name, active FROM {$wpdb->prefix}ppsgen_eca_chains ORDER BY name ASC", ARRAY_A) as $chain) {
      $chainId = (int) $chain['id'];
      if (in_array($chainId, $newsletters, true)) {
        continue;
      }
      $choices[] = ['id' => $chainId, 'name' => (string) $chain['name'], 'running' => !empty($chain['active']), 'in_it' => in_array($chainId, $inFlow, true)];
    }
    return $choices;
  }

  // "Mark replied": the replied tag stops the follow-ups of plays and of automations that exit on it
  public function markContactReplied(int $contactId): bool
  {
    global $wpdb;
    if ($contactId <= 0 || empty($this->getContactById($contactId))) {
      return false;
    }
    $tagId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug = %s", 'replied'));
    if ($tagId <= 0) {
      $now = current_time('mysql');
      $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags", ['slug' => 'replied', 'order_index' => 0, 'created_at' => $now, 'updated_at' => $now]);
      $tagId = (int) $wpdb->insert_id;
    }
    $this->addContactTags([$tagId], $contactId, 'admin');
    return true;
  }

  // "Add to automation": starts the automation for this contact from its first step
  public function addContactToAutomation(int $contactId, int $chainId): array
  {
    global $wpdb;
    $eca = $this->getFrame()->getModule('eca');
    $chain = $eca ? $wpdb->get_row($wpdb->prepare("SELECT id, name, active FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", $chainId), ARRAY_A) : null;
    if (!$chain || $eca->getModel()->isNewsletter($chainId) || empty($this->getContactById($contactId))) {
      return ['ok' => false, 'message' => __('Automation not found.', 'ppsgen')];
    }
    $already = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE contact_id = %d AND chain_id = %d AND status = 'active'", $contactId, $chainId));
    if ($already > 0) {
      return ['ok' => false, 'message' => sprintf(__('Already in “%s”.', 'ppsgen'), $chain['name'])];
    }
    $eca->getModel()->contactFlowEntry($contactId, $chainId);
    // A switched-off automation keeps the contact waiting at the start until it is switched on
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ppsgen_eca_flow SET active = %d WHERE contact_id = %d AND chain_id = %d AND status = 'active'", empty($chain['active']) ? 0 : 1, $contactId, $chainId));
    return [
      'ok' => true,
      'message' => empty($chain['active'])
        ? sprintf(__('Added to “%s”. It is switched off, so nothing is sent until you switch it on.', 'ppsgen'), $chain['name'])
        : sprintf(__('Added to “%s”. The first email follows its schedule.', 'ppsgen'), $chain['name']),
    ];
  }

  // Lists: named, saved filters of the contacts table (the same filters as the table header)
  public function getSavedContactLists(): array
  {
    $lists = $this->getOption('saved_lists', 'lists', 0, 'contact');
    return is_array($lists) ? array_values($lists) : [];
  }

  public function sanitizeSavedContactListFilters($filters): array
  {
    $clean = [];
    if (!is_array($filters)) {
      return $clean;
    }
    foreach ($filters as $name => $value) {
      $name = (string) $name;
      // Field filters only apply to visible columns, so the column choice is saved with the list
      if (!preg_match('/^(filter_[a-z0-9_]+(\[\])?|columns\[\])$/i', $name)) {
        continue;
      }
      if (is_array($value)) {
        $value = array_values(array_filter(array_map('sanitize_text_field', array_map('strval', $value)), 'strlen'));
        if (!empty($value)) {
          $clean[$name] = $value;
        }
      } else {
        $value = sanitize_text_field((string) $value);
        if ($value !== '') {
          $clean[$name] = $value;
        }
      }
    }
    return $clean;
  }

  public function saveSavedContactList(string $name, $filters): array
  {
    $name = trim(sanitize_text_field($name));
    $filters = $this->sanitizeSavedContactListFilters($filters);
    if ($name === '') {
      return ['ok' => false, 'message' => __('Give the list a name.', 'ppsgen')];
    }
    $hasFilter = !empty(array_filter(array_keys($filters), function ($key) {
      return strpos($key, 'filter_') === 0;
    }));
    if (!$hasFilter) {
      return ['ok' => false, 'message' => __('Set at least one filter in the table header first, then save it as a list.', 'ppsgen')];
    }
    $lists = $this->getSavedContactLists();
    $id = substr(md5($name . microtime()), 0, 10);
    $lists[] = ['id' => $id, 'name' => $name, 'filters' => $filters];
    $this->updateOption('saved_lists', 'lists', $lists, 0, 'contact', 'json');
    return ['ok' => true, 'id' => $id, 'message' => __('List saved.', 'ppsgen')];
  }

  public function getSavedContactList(string $id): array
  {
    foreach ($this->getSavedContactLists() as $list) {
      if (($list['id'] ?? '') === $id) {
        return $list;
      }
    }
    return [];
  }

  // The contacts a saved list shows in the table, as ids (used by newsletters "Send to")
  public function getContactIdsBySavedList(string $id): array
  {
    $args = $this->getSavedListQueryArgs($id);
    if (empty($args)) {
      return [];
    }
    $result = $this->getContacts($args);
    return array_map('intval', array_column($result['rows'] ?? [], 'id'));
  }

  // Automations "Contact is on list": the same filters, checked for one contact
  public function contactMatchesSavedList(int $contactId, string $id): bool
  {
    $args = $this->getSavedListQueryArgs($id);
    if (empty($args) || $contactId <= 0) {
      return false;
    }
    if (!empty($args['filters']['id']) && (int) $args['filters']['id'] !== $contactId) {
      return false;
    }
    $args['filters']['id'] = $contactId;
    $args['per_page'] = 1;
    $result = $this->getContacts($args);
    return !empty($result['rows']);
  }

  // Saved list filters (stored as table form fields) turned into getContacts() arguments
  protected function getSavedListQueryArgs(string $id): array
  {
    $list = $this->getSavedContactList($id);
    if (empty($list['filters']) || !is_array($list['filters'])) {
      return [];
    }
    $saved = $list['filters'];
    $filters = [];
    $dynamic = [];
    foreach (['id', 'wp_user_id', 'verified', 'created_at'] as $key) {
      if (isset($saved['filter_' . $key]) && $saved['filter_' . $key] !== '') {
        $filters[$key] = $saved['filter_' . $key];
      }
    }
    foreach ($saved as $name => $value) {
      if ($name === 'filter_dynamic_tag_titles[]') {
        $dynamic['tag_titles'] = (array) $value;
      } elseif (strpos($name, 'filter_dynamic_') === 0) {
        $dynamic[substr($name, strlen('filter_dynamic_'))] = (string) $value;
      }
    }
    if (!empty($dynamic)) {
      $filters['dynamic'] = $dynamic;
    }
    $dynamicFields = array_values(array_unique(array_merge((array) ($saved['columns[]'] ?? []), array_diff(array_keys($dynamic), ['tag_titles']))));
    return [
      'page' => 1,
      'per_page' => 1000000,
      'order_by' => 'c.id',
      'order_dir' => 'ASC',
      'filters' => $filters,
      'dynamic_fields' => $dynamicFields,
    ];
  }

  public function deleteSavedContactList(string $id): bool
  {
    $lists = array_values(array_filter($this->getSavedContactLists(), function ($list) use ($id) {
      return ($list['id'] ?? '') !== $id;
    }));
    $this->updateOption('saved_lists', 'lists', $lists, 0, 'contact', 'json');
    return true;
  }
}
