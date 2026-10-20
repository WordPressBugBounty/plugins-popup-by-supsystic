<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactTagSubmoduleModelPpsgen extends BaseModelPpsgen
{
  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    if (!$this->getFrame()->core('installer')->exist('ppsgen_contact_tags')) {
      $tableName = $wpdb->prefix . 'ppsgen_contact_tags';
      $sql = "CREATE TABLE {$tableName} (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `slug` VARCHAR(255) NOT NULL,
        `order_index` INT(11) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_slug` (`slug`)
      ) {$charset};";
      dbDelta($sql);
    }
    if (!$this->getFrame()->core('installer')->exist('ppsgen_contact_to_tags')) {
      $tableName = $wpdb->prefix . 'ppsgen_contact_to_tags';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `contact_id` BIGINT(20) UNSIGNED NOT NULL,
        `tag_id` BIGINT(20) UNSIGNED NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_contact_id` (`contact_id`),
        KEY `idx_tag_id` (`tag_id`)
      ) $charset;";
      dbDelta($sql);
    }
    if (!$this->getFrame()->core('installer')->exist('ppsgen_contact_tags_logs')) {
      $tableName = $wpdb->prefix . 'ppsgen_contact_tags_logs';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `contact_id` BIGINT(20) UNSIGNED NOT NULL,
        `tag_action` VARCHAR(20) NOT NULL DEFAULT 'added',
        `tag_ids` TEXT NULL,
        `initiator` VARCHAR(50) NOT NULL DEFAULT 'system',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_contact_id` (`contact_id`),
        KEY `idx_created_at` (`created_at`)
      ) $charset;";
      dbDelta($sql);
    }
  }

  public function deleteContactTag($tag_id)
  {
    global $wpdb;
    if (!empty($tag_id)) {
      $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}ppsgen_contact_tags_logs WHERE FIND_IN_SET(%d, REPLACE(REPLACE(tag_ids, ' ', ''), ',,', ','))", (int) $tag_id));
      $wpdb->delete("{$wpdb->prefix}ppsgen_contact_to_tags", ['tag_id' => $tag_id]);
      return $wpdb->delete("{$wpdb->prefix}ppsgen_contact_tags", ['id' => $tag_id]);
    }
    return false;
  }

  public function getContactsTags(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'slug', 'order_index', 'created_at', 'updated_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['slug'])) {
      $whereParts[] = 'slug LIKE %s';
      $params[] = $args['filters']['slug'];
    }
    if (!empty($args['filters']['order_index'])) {
      $whereParts[] = 'order_index = %d';
      $params[] = (int) $args['filters']['order_index'];
    }
    if (!empty($args['filters']['created_at'])) {
      $whereParts[] = 'DATE(created_at) = %s';
      $params[] = $args['filters']['created_at'];
    }
    if (!empty($args['filters']['updated_at'])) {
      $whereParts[] = 'DATE(updated_at) = %s';
      $params[] = $args['filters']['updated_at'];
    }
    $whereSql = implode(' AND ', $whereParts);
    $allowedOrderFields = $defaults['columns'];
    $orderBy = in_array($args['order_by'], $allowedOrderFields, true) ? $args['order_by'] : 'id';
    $orderDir = $args['order_dir'] === 'DESC' ? 'DESC' : 'ASC';
    $limit = (int) $args['per_page'];
    $offset = ($args['page'] - 1) * $limit;
    $params[] = $limit;
    $params[] = $offset;
    $sql = "
      SELECT id, slug, order_index, created_at, updated_at
      FROM {$wpdb->prefix}ppsgen_contact_tags
      WHERE $whereSql
      GROUP BY id
      ORDER BY $orderBy $orderDir
      LIMIT %d OFFSET %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    // phpcs:enable
    $params = array_slice($params, 0, -2);
    $params[] = 9999999999999;
    $sql = "
        SELECT COUNT(DISTINCT id)
        FROM {$wpdb->prefix}ppsgen_contact_tags
        WHERE $whereSql
        LIMIT %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $total = $wpdb->get_var($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    return ['rows' => $rows, 'total' => (int) $total];
  }

  public function getContactTagById($tag_id)
  {
    global $wpdb;
    if (!empty($tag_id)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_contact_tags WHERE " . $wpdb->prepare('id = %d', [$tag_id]), ARRAY_A);
    }
    return false;
  }

  public function getTagsByContactId($contact_id, $asList = false, $asCol = false)
  {
    global $wpdb;
    if (!empty($contact_id)) {
      $rows = $wpdb->get_results("SELECT *, ctt.id as ctt_id, ct.id as ct_id FROM {$wpdb->prefix}ppsgen_contact_to_tags as ctt LEFT JOIN {$wpdb->prefix}ppsgen_contact_tags as ct on ctt.tag_id = ct.id WHERE " . $wpdb->prepare('ctt.contact_id = %d', [$contact_id]), ARRAY_A);
      if ($asList) {
        return array_column($rows, 'slug', 'ct_id');
      }
      if ($asCol) {
        return array_column($rows, 'ct_id');
      }
      return $rows;
    }
    return false;
  }

  public function getAllContactTags($asList = false)
  {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ppsgen_contact_tags", ARRAY_A);
    if ($asList) {
      return array_column($rows, 'slug', 'id');
    }
    return $rows;
  }

  public function editContactTag($data, $obj_id = 0)
  {
    global $wpdb;
    if (!empty($data)) {
      $contactTagData = !empty($data['contact_tag']) ? $data['contact_tag'] : [];
      if (!empty($obj_id)) {
        $contactTagData['updated_at'] = current_time('mysql');
        $res = $wpdb->update("{$wpdb->prefix}ppsgen_contact_tags", $contactTagData, ['id' => $obj_id]);
        if (!empty($res)) {
          $this->setStatus('success');
          $this->setMessage(esc_html__('Contact Tag is updated.', 'ppsgen'));
          return $res;
        }
      } else {
        if (empty($wpdb->get_var("SELECT slug FROM {$wpdb->prefix}ppsgen_contact_tags WHERE " . $wpdb->prepare(' slug = %s', $contactTagData['slug'])))) {
          $res = $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags", $contactTagData);
          if (!empty($res)) {
            $this->setStatus('success');
            $this->setMessage(esc_html__('Contact Tag is added.', 'ppsgen'));
            return $wpdb->insert_id;
          }
        } else {
          $this->setStatus('error');
          $this->setMessage(esc_html__('Error! Contact Tag Slug is duplicated.', 'ppsgen'));
          return false;
        }
      }
    }
  }
}
