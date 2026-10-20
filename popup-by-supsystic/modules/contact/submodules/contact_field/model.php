<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactFieldSubmoduleModelPpsgen extends BaseModelPpsgen
{
  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    if (!$this->getFrame()->core('installer')->exist('ppsgen_contact_fields')) {
      $tableName = $wpdb->prefix . 'ppsgen_contact_fields';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `slug` VARCHAR(255) NOT NULL,
        `label` VARCHAR(255) NOT NULL,
        `field_type` VARCHAR(255) NOT NULL,
        `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
        `is_default` TINYINT(1) NOT NULL DEFAULT 0,
        `only_form` TINYINT(1) NOT NULL DEFAULT 0,
        `options` TEXT NULL,
        `settings` TEXT NULL,
        `order_index` INT(11) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_slug` (`slug`)
      ) $charset;";
      dbDelta($sql);
      $builtinFields = [
        ['slug' => 'first_name', 'label' => 'First Name', 'field_type' => 'text', 'is_default' => 1, 'only_form' => 0, 'settings' => $this->getDefaultFieldSettings('first_name')],
        ['slug' => 'last_name', 'label' => 'Last Name', 'field_type' => 'text', 'is_default' => 1, 'only_form' => 0, 'settings' => $this->getDefaultFieldSettings('last_name')],
        ['slug' => 'email', 'label' => 'Email', 'field_type' => 'email', 'is_default' => 1, 'only_form' => 0, 'settings' => $this->getDefaultFieldSettings('email')],
        ['slug' => 'phone', 'label' => 'Phone', 'field_type' => 'phone', 'is_default' => 1, 'only_form' => 0, 'settings' => $this->getDefaultFieldSettings('phone')],
        ['slug' => 'additional_info', 'label' => 'Additional Info', 'field_type' => 'textarea', 'is_default' => 1, 'only_form' => 0, 'settings' => $this->getDefaultFieldSettings('additional_info')],
        [
          'slug' => 'agreement',
          'label' => 'User Agreement',
          'field_type' => 'checkbox',
          'only_form' => 0,
          'is_default' => 0,
          'settings' => $this->getDefaultFieldSettings('agreement'),
          'options' => '[{"key_927319622":{"value":"user_agreement","label":"' . __('Agree to all the terms described in the User Agreement.', 'ppsgen') . '","checked":"0","disabled":"0"}}]',
        ],
        ['slug' => 'user_login', 'label' => 'User Login', 'field_type' => 'text', 'is_default' => 1, 'settings' => $this->getDefaultFieldSettings('user_login'), 'only_form' => 1],
        ['slug' => 'password', 'label' => 'Password', 'field_type' => 'password', 'is_default' => 1, 'settings' => $this->getDefaultFieldSettings('password'), 'only_form' => 1],
      ];
      foreach ($builtinFields as $index => $field) {
        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = %s", $field['slug']));
        if (!$existing) {
          $wpdb->insert("{$wpdb->prefix}ppsgen_contact_fields", [
            'slug' => $field['slug'],
            'label' => $field['label'],
            'field_type' => $field['field_type'],
            'is_enabled' => 1,
            'only_form' => $field['only_form'],
            'is_default' => $field['is_default'],
            'options' => isset($field['options']) ? $field['options'] : null,
            'settings' => $field['settings'],
            'order_index' => $index + 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
          ]);
        }
      }
    }
    if (!$this->getFrame()->core('installer')->exist('ppsgen_contact_field_values')) {
      $tableName = $wpdb->prefix . 'ppsgen_contact_field_values';
      $sql = "CREATE TABLE $tableName (
        `contact_id` BIGINT(20) UNSIGNED NOT NULL,
        `field_id` BIGINT(20) UNSIGNED NOT NULL,
        `value` LONGTEXT NULL,
        PRIMARY KEY (`contact_id`, `field_id`)
      ) $charset;";
      dbDelta($sql);
    }
    if (!$this->getFrame()->core('installer')->exist('ppsgen_contact_fields_logs')) {
      $tableName = $wpdb->prefix . 'ppsgen_contact_fields_logs';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `contact_id` BIGINT(20) UNSIGNED NOT NULL,
        `field_id` BIGINT(20) UNSIGNED NOT NULL,
        `field_before_value` LONGTEXT NULL,
        `field_value` LONGTEXT NULL,
        `initiator` VARCHAR(50) NOT NULL DEFAULT 'system',
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_contact_id` (`contact_id`),
        KEY `idx_field_id` (`field_id`),
        KEY `idx_updated_at` (`updated_at`),
        KEY `idx_contact_field_updated` (`contact_id`, `field_id`, `updated_at`)
      ) $charset;";
      dbDelta($sql);
    }
  }

  public function deleteContactField($cf_id)
  {
    global $wpdb;
    if (!empty($cf_id)) {
      $isDefault = $wpdb->get_var("SELECT is_default FROM {$wpdb->prefix}ppsgen_contact_fields WHERE " . $wpdb->prepare(' id = %d ', [$cf_id]));
      if (empty($isDefault)) {
        if ($this->getFrame()->useModule('form')) {
          $this->getFrame()->getModule('form')->getModel()->removeContactFieldFromForms((int) $cf_id);
        }
        $wpdb->delete("{$wpdb->prefix}ppsgen_contact_fields_logs", ['field_id' => $cf_id]);
        $wpdb->delete("{$wpdb->prefix}ppsgen_contact_field_values", ['field_id' => $cf_id]);
        return $wpdb->delete("{$wpdb->prefix}ppsgen_contact_fields", ['id' => $cf_id]);
      }
    }
    return false;
  }

  public function getContactFieldSettingJson($field_slug)
  {
    global $wpdb;
    if (!empty($field_slug)) {
      $res = $wpdb->get_row("SELECT label, options, settings FROM {$wpdb->prefix}ppsgen_contact_fields WHERE " . $wpdb->prepare(' slug = %s ', [$field_slug]), ARRAY_A);
      if (!empty($res) && !empty($res['settings'])) {
        $settings = $this->_prepareValueFromStore($res['settings']);
        $settings['label'] = !empty($res['label']) ? $res['label'] : '';
        if (!empty($res['options'])) {
          $settings['options'] = $this->_prepareValueFromStore($res['options']);
        }
        return $settings;
      }
    }
    return false;
  }

  public function getContactFields(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'slug', 'label', 'field_type', 'is_enabled', 'is_default', 'order_index', 'created_at', 'updated_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $where = ['1=1'];
    $whereParams = [];
    if (!empty($args['filters']['id'])) {
      $where[] = 'id = %d';
      $whereParams[] = $args['filters']['id'];
    }
    if (!empty($args['filters']['slug'])) {
      $where[] = 'slug LIKE %s';
      $whereParams[] = '%' . $args['filters']['slug'] . '%';
    }
    if (!empty($args['filters']['label'])) {
      $where[] = 'label LIKE %s';
      $whereParams[] = '%' . $args['filters']['label'] . '%';
    }
    if (!empty($args['filters']['field_type'])) {
      $where[] = 'field_type LIKE %s';
      $whereParams[] = '%' . $args['filters']['field_type'] . '%';
    }
    if (isset($args['filters']['is_enabled'])) {
      $val = $args['filters']['is_enabled'] === 'x' ? 0 : 1;
      $where[] = 'is_enabled = %d';
      $whereParams[] = $val;
    }
    if (isset($args['filters']['is_default'])) {
      $val = $args['filters']['is_default'] === 'x' ? 0 : 1;
      $where[] = 'is_default = %d';
      $whereParams[] = $val;
    }
    if (!empty($args['filters']['order_index'])) {
      $where[] = 'order_index = %d';
      $whereParams[] = $args['filters']['order_index'];
    }
    if (!empty($args['filters']['created_at'])) {
      $where[] = 'DATE(created_at) = %s';
      $whereParams[] = $args['filters']['created_at'];
    }
    if (!empty($args['filters']['updated_at'])) {
      $where[] = 'DATE(updated_at) = %s';
      $whereParams[] = $args['filters']['updated_at'];
    }
    // Secure validation ORDER BY
    $allowedOrderBy = $defaults['columns'];
    $orderBy = in_array($args['order_by'], $allowedOrderBy, true) ? $args['order_by'] : 'id';
    // Secure validation ORDER DIR
    $orderDir = strtoupper($args['order_dir']) === 'DESC' ? 'DESC' : 'ASC';
    $limit = intval($args['per_page']);
    $offset = max(0, ($args['page'] - 1) * $limit);
    // Prepare SQL placeholders only for values
    $sql =
      "
          SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields
          WHERE " .
      implode(' AND ', $where) .
      "
          GROUP BY id
          ORDER BY {$orderBy} {$orderDir}
          LIMIT %d OFFSET %d
      ";
    $mainParams = array_merge($whereParams, [$limit, $offset]);
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$mainParams), ARRAY_A);
    // phpcs:enable
    $totalSql =
      "
          SELECT COUNT(DISTINCT id)
          FROM {$wpdb->prefix}ppsgen_contact_fields
          WHERE " . implode(' AND ', $where);
    if (!empty($whereParams)) {
      // phpcs:disable WordPress.DB.PreparedSQL
      $total = $wpdb->get_var($wpdb->prepare($totalSql, ...$whereParams));
      // phpcs:enable
    } else {
      // phpcs:disable WordPress.DB.PreparedSQL
      $total = $wpdb->get_var($totalSql);
      // phpcs:enable
    }
    return ['rows' => $rows, 'total' => intval($total)];
  }

  public function getAllContactFields($forFormAlso = true, $asList = false, $asCol = false)
  {
    global $wpdb;
    if ($forFormAlso) {
      $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields WHERE 1 = 1", ARRAY_A);
    } else {
      $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields WHERE only_form = 0", ARRAY_A);
    }
    if ($asList) {
      return array_column($rows, 'slug', 'id');
    } elseif ($asCol) {
      return array_column($rows, 'id');
    }
    return $rows;
  }

  public function getAllContactFieldsSlugList($forFormAlso = true)
  {
    global $wpdb;
    if ($forFormAlso) {
      $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields WHERE 1 = 1", ARRAY_A);
    } else {
      $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields WHERE only_form = 0", ARRAY_A);
    }
    return array_column($rows, 'slug', 'slug');
    //return $rows;
  }

  public function getContactFieldsValuesByContactId($contact_id, $field_id)
  {
    global $wpdb;
    if (!empty($contact_id)) {
      return $wpdb->get_var("SELECT value FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE " . $wpdb->prepare('contact_id = %d AND field_id = %d', [$contact_id, $field_id]));
    }
    return false;
  }

  public function getContactFieldById($field_id)
  {
    global $wpdb;
    if (!empty($field_id)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields WHERE " . $wpdb->prepare('id = %d', [$field_id]), ARRAY_A);
    }
    return false;
  }

  public function getContactFieldBySlug($slug)
  {
    global $wpdb;
    if (!empty($slug)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields WHERE " . $wpdb->prepare('slug = %s', [$slug]), ARRAY_A);
    }
    return false;
  }

  public function getContactFieldIdBySlug($slug)
  {
    global $wpdb;
    if (!empty($slug)) {
      return $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE " . $wpdb->prepare('slug = %s', [$slug]));
    }
    return false;
  }

  public function getContactFieldTypeBySlug($slug)
  {
    global $wpdb;
    if (!empty($slug)) {
      return $wpdb->get_var("SELECT field_type FROM {$wpdb->prefix}ppsgen_contact_fields WHERE " . $wpdb->prepare('slug = %s', [$slug]));
    }
    return false;
  }

  public function editContactField($data, $obj_id = 0)
  {
    global $wpdb;
    if (!empty($data)) {
      $contactFieldData = !empty($data['contact_field']) ? $data['contact_field'] : [];
      $fieldType = !empty($contactFieldData['field_type']) ? (string) $contactFieldData['field_type'] : '';
      if ($this->isWooObjectFieldType($fieldType) && !$this->isWooCommerceActive()) {
        $this->setStatus('error');
        $this->setMessage(esc_html__('WooCommerce must be active to use this field type.', 'ppsgen'));
        return false;
      }
      if (!empty($contactFieldData['options'])) {
        if (!empty($contactFieldData['options']['@@OPTNUMBER@@'])) {
          unset($contactFieldData['options']['@@OPTNUMBER@@']);
        }
        $contactFieldData['options'] = !empty($contactFieldData['options']) ? $this->_prepareValueToStore($contactFieldData['options'], 'json') : '';
      }
      if (!empty($contactFieldData['settings'])) {
        if (is_array($contactFieldData['settings'])) {
          $contactFieldData['settings'] = $this->normalizeObjectFieldSettings($fieldType, $contactFieldData['settings']);
        }
        $contactFieldData['settings'] = $this->_prepareValueToStore($contactFieldData['settings'], 'json');
      }
      if (!empty($obj_id)) {
        $contactFieldData['updated_at'] = current_time('mysql');
        $res = $wpdb->update("{$wpdb->prefix}ppsgen_contact_fields", $contactFieldData, ['id' => $obj_id]);
        if (!empty($res)) {
          $this->getFrame()
            ->getModule('form')
            ->getModel()
            ->changeFormFieldData($obj_id, ['type' => $contactFieldData['field_type'], 'slug' => $contactFieldData['slug']]);
          $this->setStatus('success');
          $this->setMessage(esc_html__('Contact Field is updated.', 'ppsgen'));
          return $res;
        }
      } else {
        if (empty($wpdb->get_var($wpdb->prepare("SELECT slug FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = %s", $contactFieldData['slug'])))) {
          $res = $wpdb->insert("{$wpdb->prefix}ppsgen_contact_fields", $contactFieldData);
          if (!empty($res)) {
            $this->setStatus('success');
            $this->setMessage(esc_html__('Contact Field is added.', 'ppsgen'));
            return $wpdb->insert_id;
          }
        } else {
          $this->setStatus('error');
          $this->setMessage(esc_html__('Error! Contact Field Slug is duplicated.', 'ppsgen'));
          return false;
        }
      }
    }
  }

  public function fieldIsEnabled($field_id)
  {
    global $wpdb;
    if (!empty($field_id)) {
      return $wpdb->get_var("SELECT is_enabled FROM {$wpdb->prefix}ppsgen_contact_fields WHERE " . $wpdb->prepare('id = %d', [$field_id]));
    }
    return false;
  }

  public function getContactFieldTypes()
  {
    $types = [
      'text' => 'Text',
      'password' => 'Password',
      'textarea' => 'Text Area',
      'number' => 'Number',
      'email' => 'Email',
      'phone' => 'Phone',
      'checkbox' => 'Checkbox',
      'radio' => 'Radio',
      'select' => 'Select',
      'hidden' => 'Hidden',
      'pro_1' => 'File (PRO)',
      'pro_14' => 'Multiple Files (PRO)',
      'pro_2' => 'Date Time (PRO)',
      'date' => 'Date',
      'pro_4' => 'Month (PRO)',
      'pro_5' => 'Week (PRO)',
      'pro_6' => 'Time (PRO)',
      'pro_7' => 'URL (PRO)',
      'range' => 'Range',
      'pro_9' => 'Color (PRO)',
      'rating' => 'Rating',
      'pro_11' => 'Address Search (Google API) (PRO)',
      'pro_12' => 'Languages List (PRO)',
      'pro_13' => 'Countries List (PRO)',
      'pro_15' => 'Posts List (PRO)',
      'pro_16' => 'Pages List (PRO)',
      'pro_17' => 'Categories List (PRO)',
      'pro_18' => 'Tags List (PRO)',
      'pro_19' => 'Woo Categories (PRO)',
      'pro_20' => 'Woo Tags (PRO)',
      'pro_21' => 'Woo Attributes (PRO)',
      'pro_22' => 'Woo Products (PRO)',
    ];
    $types = $this->getFrame()->core('eventDispatcher')->applyFilters('contact.contact_field.types', $types);
    return $types;
  }

  public function getObjectFieldTypes(): array
  {
    return [
      'post' => [
        'label' => __('Posts List', 'ppsgen'),
        'source' => 'post_type',
        'post_type' => 'post',
        'hierarchical' => false,
        'requires_woo' => false,
        'source_parent_taxonomy' => 'category',
        'source_parent_mode' => 'taxonomy_root',
      ],
      'pages' => [
        'label' => __('Pages List', 'ppsgen'),
        'source' => 'post_type',
        'post_type' => 'page',
        'hierarchical' => true,
        'requires_woo' => false,
      ],
      'categories' => [
        'label' => __('Categories List', 'ppsgen'),
        'source' => 'taxonomy',
        'taxonomy' => 'category',
        'hierarchical' => true,
        'requires_woo' => false,
      ],
      'tags' => [
        'label' => __('Tags List', 'ppsgen'),
        'source' => 'taxonomy',
        'taxonomy' => 'post_tag',
        'hierarchical' => false,
        'requires_woo' => false,
      ],
      'woocategories' => [
        'label' => __('Woo Categories', 'ppsgen'),
        'source' => 'taxonomy',
        'taxonomy' => 'product_cat',
        'hierarchical' => true,
        'requires_woo' => true,
      ],
      'wootags' => [
        'label' => __('Woo Tags', 'ppsgen'),
        'source' => 'taxonomy',
        'taxonomy' => 'product_tag',
        'hierarchical' => false,
        'requires_woo' => true,
      ],
      'wooattributes' => [
        'label' => __('Woo Attributes', 'ppsgen'),
        'source' => 'woo_attributes',
        'hierarchical' => true,
        'requires_woo' => true,
        'source_parent_mode' => 'woo_attribute_root',
      ],
      'wooproducts' => [
        'label' => __('Woo Products', 'ppsgen'),
        'source' => 'post_type',
        'post_type' => 'product',
        'hierarchical' => false,
        'requires_woo' => true,
        'source_parent_taxonomy' => 'product_cat',
        'source_parent_mode' => 'taxonomy_root',
      ],
    ];
  }

  public function isObjectFieldType(string $fieldType): bool
  {
    return isset($this->getObjectFieldTypes()[$fieldType]);
  }

  public function isWooObjectFieldType(string $fieldType): bool
  {
    $config = $this->getObjectFieldTypeConfig($fieldType);
    return !empty($config['requires_woo']);
  }

  public function isWooCommerceActive(): bool
  {
    return class_exists('WooCommerce') || function_exists('WC');
  }

  public function getObjectFieldTypeConfig(string $fieldType): array
  {
    $types = $this->getObjectFieldTypes();
    return !empty($types[$fieldType]) && is_array($types[$fieldType]) ? $types[$fieldType] : [];
  }

  public function getObjectFieldUiOptions(): array
  {
    static $cache = null;
    if (is_array($cache)) {
      return $cache;
    }
    $cache = [];
    foreach (array_keys($this->getObjectFieldTypes()) as $fieldType) {
      $cache[$fieldType] = $this->getObjectFieldSourceOptions($fieldType, [
        'show_name_hierarchically' => '1',
        'sort_by' => 'name',
        'sort_order' => 'ASC',
      ]);
    }
    return $cache;
  }

  public function getObjectFieldSourceOptions(string $fieldType, array $settings = []): array
  {
    $config = $this->getObjectFieldTypeConfig($fieldType);
    if (empty($config)) {
      return [];
    }
    if (!empty($config['requires_woo']) && !$this->isWooCommerceActive()) {
      return [];
    }
    if (!empty($config['source']) && $config['source'] === 'post_type') {
      return $this->getObjectPostOptions($config, $settings);
    }
    if (!empty($config['source']) && $config['source'] === 'taxonomy') {
      return $this->getObjectTermOptions($config, $settings);
    }
    if (!empty($config['source']) && $config['source'] === 'woo_attributes') {
      return $this->getWooAttributeOptions($settings);
    }
    return [];
  }

  public function getObjectFieldSelectableOptions(string $fieldType, array $settings = []): array
  {
    $options = $this->getObjectFieldSourceOptions($fieldType, $settings);
    if (empty($options)) {
      return [];
    }
    $includeIds = $this->normalizeObjectIds($settings['include_ids'] ?? []);
    $excludeIds = $this->normalizeObjectIds($settings['exclude_ids'] ?? []);
    $config = $this->getObjectFieldTypeConfig($fieldType);
    $showWithChildren = !empty($settings['show_with_children']);

    if (!empty($includeIds)) {
      if ($showWithChildren && !empty($config['hierarchical'])) {
        $includeIds = $this->expandHierarchicalObjectIds($fieldType, $includeIds);
      }
      $options = array_intersect_key($options, array_flip(array_map('strval', $includeIds)));
    }

    if (!empty($excludeIds)) {
      if ($showWithChildren && !empty($config['hierarchical'])) {
        $excludeIds = $this->expandHierarchicalObjectIds($fieldType, $excludeIds);
      }
      foreach ($excludeIds as $excludeId) {
        unset($options[(string) $excludeId]);
      }
    }

    return $this->sortObjectOptions($options, $settings);
  }

  public function getObjectFieldSourceParentOptions(string $fieldType, array $settings = []): array
  {
    $config = $this->getObjectFieldTypeConfig($fieldType);
    if (empty($config)) {
      return [];
    }
    if (!empty($config['requires_woo']) && !$this->isWooCommerceActive()) {
      return [];
    }
    if (!empty($config['source_parent_mode']) && $config['source_parent_mode'] === 'taxonomy_root') {
      return ['' => __('All Parent Categories', 'ppsgen')] + $this->getRootTermOptions((string) ($config['source_parent_taxonomy'] ?? ''), $settings);
    }
    if (!empty($config['source_parent_mode']) && $config['source_parent_mode'] === 'woo_attribute_root') {
      return ['' => __('All Attributes', 'ppsgen')] + $this->getWooAttributeTaxonomyOptions();
    }
    return [];
  }

  public function normalizeObjectFieldSettings(string $fieldType, array $settings): array
  {
    if (!$this->isObjectFieldType($fieldType)) {
      return $settings;
    }
    $includeKey = 'include_ids_' . $fieldType;
    $excludeKey = 'exclude_ids_' . $fieldType;
    if (array_key_exists($includeKey, $settings)) {
      $settings['include_ids'] = $this->normalizeObjectIds($settings[$includeKey]);
    } elseif (!empty($settings['include_ids'])) {
      $settings['include_ids'] = $this->normalizeObjectIds($settings['include_ids']);
    } else {
      $settings['include_ids'] = [];
    }
    if (array_key_exists($excludeKey, $settings)) {
      $settings['exclude_ids'] = $this->normalizeObjectIds($settings[$excludeKey]);
    } elseif (!empty($settings['exclude_ids'])) {
      $settings['exclude_ids'] = $this->normalizeObjectIds($settings['exclude_ids']);
    } else {
      $settings['exclude_ids'] = [];
    }
    $sourceParentKey = 'source_parent_id_' . $fieldType;
    if (array_key_exists($sourceParentKey, $settings)) {
      $settings['source_parent_id'] = (int) $settings[$sourceParentKey];
    } elseif (!empty($settings['source_parent_id'])) {
      $settings['source_parent_id'] = (int) $settings['source_parent_id'];
    } else {
      $settings['source_parent_id'] = 0;
    }
    foreach (array_keys($this->getObjectFieldTypes()) as $objectFieldType) {
      unset($settings['include_ids_' . $objectFieldType], $settings['exclude_ids_' . $objectFieldType], $settings['source_parent_id_' . $objectFieldType]);
    }
    $settings['sort_by'] = !empty($settings['sort_by']) && in_array($settings['sort_by'], ['default', 'name', 'ids'], true) ? $settings['sort_by'] : 'default';
    $settings['sort_order'] = !empty($settings['sort_order']) && strtoupper((string) $settings['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
    $settings['multiple_select'] = !empty($settings['multiple_select']) ? '1' : '0';
    $settings['custom_select'] = !empty($settings['custom_select']) ? '1' : '0';
    $settings['show_thumbnail_in_custom_select'] = !empty($settings['show_thumbnail_in_custom_select']) ? '1' : '0';
    $settings['show_product_price'] = !empty($settings['show_product_price']) ? '1' : '0';
    $settings['show_with_children'] = !empty($settings['show_with_children']) ? '1' : '0';
    $settings['show_name_hierarchically'] = !empty($settings['show_name_hierarchically']) ? '1' : '0';
    $settings['source_include_children'] = !empty($settings['source_include_children']) ? '1' : '0';
    return $settings;
  }

  public function getObjectFieldRenderableItems(string $fieldType, array $settings = []): array
  {
    $options = $this->getObjectFieldSelectableOptions($fieldType, $settings);
    if (empty($options)) {
      return [];
    }
    $items = [];
    foreach ($options as $value => $label) {
      $items[] = [
        'value' => (string) $value,
        'label' => (string) $label,
        'thumb' => $this->getObjectFieldItemThumbnailUrl($fieldType, (int) $value),
        'price_data' => $fieldType === 'wooproducts' && !empty($settings['show_product_price']) ? $this->getWooProductPriceData((int) $value) : [],
      ];
    }
    return $items;
  }

  protected function getObjectPostOptions(array $config, array $settings = []): array
  {
    $postType = !empty($config['post_type']) ? $config['post_type'] : 'post';
    $queryArgs = [
      'post_type' => $postType,
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'date',
      'order' => 'DESC',
      'fields' => 'all',
      'suppress_filters' => false,
    ];
    $taxonomy = !empty($config['source_parent_taxonomy']) ? (string) $config['source_parent_taxonomy'] : '';
    if ($taxonomy && taxonomy_exists($taxonomy)) {
      $taxQuery = $this->buildObjectSourceTaxQuery($taxonomy, $settings);
      if (!empty($taxQuery)) {
        $queryArgs['tax_query'] = $taxQuery;
      }
    }
    $posts = get_posts($queryArgs);
    $options = [];
    foreach ($posts as $post) {
      if (!($post instanceof WP_Post)) {
        continue;
      }
      $label = $this->formatObjectPostLabel($post, $settings, !empty($config['hierarchical']));
      $options[(string) $post->ID] = $label;
    }
    return $options;
  }

  protected function getObjectTermOptions(array $config, array $settings = []): array
  {
    $taxonomy = !empty($config['taxonomy']) ? $config['taxonomy'] : '';
    if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
      return [];
    }
    $terms = get_terms([
      'taxonomy' => $taxonomy,
      'hide_empty' => false,
    ]);
    if (is_wp_error($terms) || empty($terms)) {
      return [];
    }
    $options = [];
    foreach ($terms as $term) {
      if (!($term instanceof WP_Term)) {
        continue;
      }
      $options[(string) $term->term_id] = $this->formatObjectTermLabel($term, $taxonomy, $settings);
    }
    return $options;
  }

  protected function getWooAttributeOptions(array $settings = []): array
  {
    if (!$this->isWooCommerceActive() || !function_exists('wc_get_attribute_taxonomies')) {
      return [];
    }
    $taxonomies = wc_get_attribute_taxonomies();
    if (empty($taxonomies)) {
      return [];
    }
    $options = [];
    $selectedParentId = (int) ($settings['source_parent_id'] ?? 0);
    $includeChildren = !empty($settings['source_include_children']);
    foreach ($taxonomies as $taxonomy) {
      $attributeId = !empty($taxonomy->attribute_id) ? (int) $taxonomy->attribute_id : 0;
      if ($selectedParentId > 0 && $attributeId !== $selectedParentId) {
        continue;
      }
      $attributeName = !empty($taxonomy->attribute_label) ? $taxonomy->attribute_label : $taxonomy->attribute_name;
      $taxonomyName = wc_attribute_taxonomy_name($taxonomy->attribute_name);
      if (!taxonomy_exists($taxonomyName)) {
        continue;
      }
      $termArgs = [
        'taxonomy' => $taxonomyName,
        'hide_empty' => false,
      ];
      $selectedTermId = (int) ($settings['attribute_parent_term_id'] ?? 0);
      if ($selectedTermId > 0) {
        $selectedParent = get_term($selectedTermId, $taxonomyName);
        if (!($selectedParent instanceof WP_Term) || is_wp_error($selectedParent)) {
          continue;
        }
        if ($includeChildren) {
          $allowedIds = array_merge([$selectedTermId], get_term_children($selectedTermId, $taxonomyName));
          $termArgs['include'] = array_map('intval', $allowedIds);
        } else {
          $termArgs['parent'] = $selectedTermId;
        }
      } elseif (!$includeChildren) {
        $termArgs['parent'] = 0;
      }
      $terms = get_terms($termArgs);
      if (is_wp_error($terms) || empty($terms)) {
        continue;
      }
      foreach ($terms as $term) {
        if (!($term instanceof WP_Term)) {
          continue;
        }
        $label = $this->formatObjectTermLabel($term, $taxonomyName, $settings);
        $options[(string) $term->term_id] = $attributeName . ' / ' . $label;
      }
    }
    return $options;
  }

  protected function buildObjectSourceTaxQuery(string $taxonomy, array $settings = []): array
  {
    if (!taxonomy_exists($taxonomy)) {
      return [];
    }
    $selectedParentId = (int) ($settings['source_parent_id'] ?? 0);
    $includeChildren = !empty($settings['source_include_children']);
    if ($selectedParentId > 0) {
      return [
        [
          'taxonomy' => $taxonomy,
          'field' => 'term_id',
          'terms' => [$selectedParentId],
          'include_children' => $includeChildren,
          'operator' => 'IN',
        ],
      ];
    }
    $rootIds = array_keys($this->getRootTermOptions($taxonomy, $settings));
    $rootIds = array_values(array_filter(array_map('intval', $rootIds)));
    if (empty($rootIds)) {
      return [];
    }
    return [
      [
        'taxonomy' => $taxonomy,
        'field' => 'term_id',
        'terms' => $rootIds,
        'include_children' => $includeChildren,
        'operator' => 'IN',
      ],
    ];
  }

  protected function getRootTermOptions(string $taxonomy, array $settings = []): array
  {
    if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
      return [];
    }
    $terms = get_terms([
      'taxonomy' => $taxonomy,
      'hide_empty' => false,
      'parent' => 0,
    ]);
    if (is_wp_error($terms) || empty($terms)) {
      return [];
    }
    $options = [];
    foreach ($terms as $term) {
      if (!($term instanceof WP_Term)) {
        continue;
      }
      $options[(string) $term->term_id] = $this->formatObjectTermLabel($term, $taxonomy, $settings);
    }
    return $options;
  }

  protected function getWooAttributeTaxonomyOptions(): array
  {
    if (!$this->isWooCommerceActive() || !function_exists('wc_get_attribute_taxonomies')) {
      return [];
    }
    $taxonomies = wc_get_attribute_taxonomies();
    if (empty($taxonomies)) {
      return [];
    }
    $options = [];
    foreach ($taxonomies as $taxonomy) {
      $attributeId = !empty($taxonomy->attribute_id) ? (int) $taxonomy->attribute_id : 0;
      if ($attributeId <= 0) {
        continue;
      }
      $attributeName = !empty($taxonomy->attribute_label) ? $taxonomy->attribute_label : $taxonomy->attribute_name;
      $options[(string) $attributeId] = $attributeName;
    }
    return $options;
  }

  protected function sortObjectOptions(array $options, array $settings = []): array
  {
    $sortBy = !empty($settings['sort_by']) ? (string) $settings['sort_by'] : 'default';
    $sortOrder = !empty($settings['sort_order']) && strtoupper((string) $settings['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
    if ($sortBy === 'name') {
      uasort($options, static function ($labelA, $labelB) use ($sortOrder) {
        $result = strnatcasecmp((string) $labelA, (string) $labelB);
        return $sortOrder === 'DESC' ? -$result : $result;
      });
      return $options;
    }
    if ($sortBy === 'ids') {
      uksort($options, static function ($idA, $idB) use ($sortOrder) {
        $result = ((int) $idA <=> (int) $idB);
        return $sortOrder === 'DESC' ? -$result : $result;
      });
      return $options;
    }
    if ($sortOrder === 'DESC') {
      return array_reverse($options, true);
    }
    return $options;
  }

  protected function normalizeObjectIds($ids): array
  {
    if (!is_array($ids)) {
      $ids = strlen((string) $ids) ? [$ids] : [];
    }
    $ids = array_filter(array_map('intval', $ids), static function ($id) {
      return $id > 0;
    });
    return array_values(array_unique($ids));
  }

  protected function expandHierarchicalObjectIds(string $fieldType, array $ids): array
  {
    $config = $this->getObjectFieldTypeConfig($fieldType);
    if (empty($config) || empty($config['hierarchical'])) {
      return $ids;
    }
    $expanded = $ids;
    if (!empty($config['source']) && $config['source'] === 'taxonomy' && !empty($config['taxonomy']) && taxonomy_exists($config['taxonomy'])) {
      foreach ($ids as $id) {
        $expanded = array_merge($expanded, get_term_children($id, $config['taxonomy']));
      }
    } elseif (!empty($config['source']) && $config['source'] === 'post_type') {
      foreach ($ids as $id) {
        $expanded = array_merge($expanded, get_pages([
          'post_type' => $config['post_type'],
          'child_of' => $id,
          'fields' => 'ids',
        ]));
      }
    }
    return $this->normalizeObjectIds($expanded);
  }

  protected function formatObjectPostLabel(WP_Post $post, array $settings = [], bool $hierarchical = false): string
  {
    if (empty($settings['show_name_hierarchically']) || !$hierarchical) {
      return get_the_title($post);
    }
    $parts = [];
    $ancestorIds = array_reverse(get_post_ancestors($post));
    foreach ($ancestorIds as $ancestorId) {
      $parts[] = get_the_title((int) $ancestorId);
    }
    $parts[] = get_the_title($post);
    return implode(' / ', array_filter($parts));
  }

  protected function formatObjectTermLabel(WP_Term $term, string $taxonomy, array $settings = []): string
  {
    if (empty($settings['show_name_hierarchically'])) {
      return $term->name;
    }
    $parts = [$term->name];
    $parentId = (int) $term->parent;
    while ($parentId > 0) {
      $parent = get_term($parentId, $taxonomy);
      if (!($parent instanceof WP_Term) || is_wp_error($parent)) {
        break;
      }
      array_unshift($parts, $parent->name);
      $parentId = (int) $parent->parent;
    }
    return implode(' / ', $parts);
  }

  protected function getObjectFieldItemThumbnailUrl(string $fieldType, int $objectId): string
  {
    if ($objectId <= 0) {
      return '';
    }
    if (in_array($fieldType, ['post', 'wooproducts'], true)) {
      $thumbId = get_post_thumbnail_id($objectId);
      return $thumbId > 0 ? (string) wp_get_attachment_image_url($thumbId, 'thumbnail') : '';
    }
    if (in_array($fieldType, ['categories', 'woocategories'], true)) {
      return $this->getTermThumbnailUrl($objectId);
    }
    return '';
  }

  protected function getTermThumbnailUrl(int $termId): string
  {
    $thumbnailId = (int) get_term_meta($termId, 'thumbnail_id', true);
    if ($thumbnailId > 0) {
      $url = wp_get_attachment_image_url($thumbnailId, 'thumbnail');
      if (!empty($url)) {
        return (string) $url;
      }
    }
    $imageId = (int) get_term_meta($termId, 'image_id', true);
    if ($imageId > 0) {
      $url = wp_get_attachment_image_url($imageId, 'thumbnail');
      if (!empty($url)) {
        return (string) $url;
      }
    }
    $image = get_term_meta($termId, 'image', true);
    if (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
      return $image;
    }
    return '';
  }

  protected function getWooProductPriceData(int $productId): array
  {
    if ($productId <= 0 || !$this->isWooCommerceActive() || !function_exists('wc_get_product')) {
      return [];
    }
    $product = wc_get_product($productId);
    if (!$product) {
      return [];
    }
    if ($product->is_type('variable') && method_exists($product, 'get_variation_price')) {
      $minPrice = (float) $product->get_variation_price('min', true);
      $maxPrice = (float) $product->get_variation_price('max', true);
      $minRegular = (float) $product->get_variation_regular_price('min', true);
      $maxRegular = (float) $product->get_variation_regular_price('max', true);
      $current = $minPrice === $maxPrice ? wp_strip_all_tags(wc_price($minPrice), true) : wp_strip_all_tags(wc_price($minPrice), true) . ' - ' . wp_strip_all_tags(wc_price($maxPrice), true);
      $old = '';
      if ($minRegular > 0 && ($minRegular !== $minPrice || $maxRegular !== $maxPrice)) {
        $old = $minRegular === $maxRegular ? wp_strip_all_tags(wc_price($minRegular), true) : wp_strip_all_tags(wc_price($minRegular), true) . ' - ' . wp_strip_all_tags(wc_price($maxRegular), true);
      }
      return [
        'current' => trim((string) preg_replace('/\s+/', ' ', html_entity_decode($current, ENT_QUOTES, get_bloginfo('charset')))),
        'old' => trim((string) preg_replace('/\s+/', ' ', html_entity_decode($old, ENT_QUOTES, get_bloginfo('charset')))),
      ];
    }
    $current = wp_strip_all_tags(wc_price((float) wc_get_price_to_display($product)), true);
    $old = '';
    $regular = (float) $product->get_regular_price();
    $sale = (float) $product->get_sale_price();
    if ($regular > 0 && $sale > 0 && $regular > $sale) {
      $old = wp_strip_all_tags(wc_price($regular), true);
    }
    return [
      'current' => trim((string) preg_replace('/\s+/', ' ', html_entity_decode($current, ENT_QUOTES, get_bloginfo('charset')))),
      'old' => trim((string) preg_replace('/\s+/', ' ', html_entity_decode($old, ENT_QUOTES, get_bloginfo('charset')))),
    ];
  }

  public function getDefaultFieldSettings($field_slug)
  {
    $settings = [];
    $settings['first_name'] =
      '{"required":"0","placeholder":"' .
      __('First name', 'ppsgen') .
      '","show_tooltip":"1","title":"' .
      __('Enter your first name from 1 to 255 symbols support a-Z letters', 'ppsgen') .
      '","min_length":"0","max_length":"255","pattern":"^[a-zA-Z]{1,255}$","autocomplete":"1"}';
    $settings['last_name'] =
      '{"required":"0","placeholder":"' .
      __('Last name', 'ppsgen') .
      '","show_tooltip":"1","title":"' .
      __('Enter your last name from 1 to 255 symbols support a-Z letters', 'ppsgen') .
      '","min_length":"0","max_length":"255","pattern":"^[a-zA-Z]{1,255}$","autocomplete":"1"}';
    $settings['email'] = '{"required":"1","placeholder":"' . __('Email', 'ppsgen') . '","show_tooltip":"1","title":"' . __('Enter your email', 'ppsgen') . '","min_length":"0","max_length":"255","pattern":"^[a-zA-Z]{1,255}$","autocomplete":"1"}';
    $settings['phone'] = '{"required":"0","placeholder":"' . __('Phone number', 'ppsgen') . '","show_tooltip":"1","title":"' . __('Enter your phone number', 'ppsgen') . '","min_length":"0","max_length":"255","mask":"+42(099)999-99-99","autocomplete":"1"}';
    $settings['additional_info'] =
      '{"required":"0","placeholder":"' . __('Additional info', 'ppsgen') . '","show_tooltip":"1","title":"' . __('You can enter here additional info', 'ppsgen') . '","min_length":"0","max_length":"255","max_length_textarea":"9999999"}';
    $settings['agreement'] = '{"required":"0","placeholder":"","show_tooltip":"0","title":"' . __('User Agreement', 'ppsgen') . '"}';
    $settings['user_login'] = '{"required":"0","placeholder":"' . __('User Login', 'ppsgen') . '","show_tooltip":"1","title":"' . __('Enter your User Login name from 1 to 255 symbols', 'ppsgen') . '","min_length":"0","max_length":"255"}';
    $settings['password'] = '{"required":"0","placeholder":"' . __('Password', 'ppsgen') . '","show_tooltip":"1","title":"' . __('Enter your User Password from 1 to 255 symbols', 'ppsgen') . '","min_length":"0","max_length":"255"}';
    return !empty($settings[$field_slug]) ? $settings[$field_slug] : '';
  }
}
