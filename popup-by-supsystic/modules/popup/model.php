<?php

if (!defined('ABSPATH')) {
  exit();
}

class PopupModelPpsgen extends BaseModelPpsgen
{
  private $redirectUrl = null;

  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $installer = $this->getFrame()->core('installer');
    if (!$installer->exist('ppsgen_popups')) {
      $tableName = $wpdb->prefix . 'ppsgen_popups';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `settings` TEXT NULL,
        `design` TEXT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_popup_fields')) {
      $tableName = $wpdb->prefix . 'ppsgen_popup_fields';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `popup_id` BIGINT(20) UNSIGNED NOT NULL,
        `parent_key` VARCHAR(255) NOT NULL,
        `fkey` VARCHAR(255) NOT NULL,
        `type` VARCHAR(255) NOT NULL,
        `settings` TEXT NULL,
        `order_index` INT(11) NOT NULL DEFAULT 0,
        `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_popup_submits')) {
      global $wpdb;
      $charset = $wpdb->get_charset_collate();
      $tableName = $wpdb->prefix . 'ppsgen_popup_submits';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `popup_id` BIGINT(20) UNSIGNED NOT NULL,
        `wp_user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
        `ip` VARCHAR(255) DEFAULT NULL,
        `action_type` VARCHAR(255) DEFAULT NULL,
        `action_meta` VARCHAR(255) DEFAULT NULL,
        `is_unique` TINYINT(1) NOT NULL DEFAULT 0,
        `is_bounce` TINYINT(1) NOT NULL DEFAULT 0,
        `time_on_screen` INT(10) NOT NULL DEFAULT 0,
        `user_agent` ENUM('pc', 'mobile', 'tablet') DEFAULT NULL,
        `utm_source` VARCHAR(100) DEFAULT NULL,
        `utm_medium` VARCHAR(100) DEFAULT NULL,
        `utm_campaign` VARCHAR(100) DEFAULT NULL,
        `utm_term` VARCHAR(100) DEFAULT NULL,
        `utm_content` VARCHAR(100) DEFAULT NULL,
        `referer` VARCHAR(300) DEFAULT NULL,
        `url` VARCHAR(300) DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`popup_id`),
        INDEX (`wp_user_id`),
        INDEX (`action_type`),
        INDEX (`created_at`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
  }

  public function getPopups(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'name', 'created_at', 'updated_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['name'])) {
      $whereParts[] = 'name LIKE %s';
      $params[] = '%' . $wpdb->esc_like($args['filters']['name']) . '%';
    }
    if (!empty($args['filters']['active'])) {
      $val = $args['filters']['active'] === 'x' ? 0 : 1;
      $whereParts[] = 'active = %d';
      $params[] = $val;
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
        SELECT id, name, active, created_at, updated_at
        FROM {$wpdb->prefix}ppsgen_popups
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
        FROM {$wpdb->prefix}ppsgen_popups
        WHERE $whereSql
        LIMIT %d
    ";
    // phpcs:disable WordPress.DB.PreparedSQL
    $total = $wpdb->get_var($wpdb->prepare($sql, ...$params));
    // phpcs:enable
    return [
      'rows' => $rows,
      'total' => (int) $total,
    ];
  }

  public function getPopupStatsSummaryByIds(array $popupIds): array
  {
    global $wpdb;

    $popupIds = array_values(array_filter(array_map('intval', $popupIds)));
    if (empty($popupIds)) {
      return [];
    }

    $summaries = [];
    foreach ($popupIds as $popupId) {
      $summaries[$popupId] = [
        'total_views' => 0,
        'unique_views' => 0,
        'actions' => 0,
        'form_submits' => 0,
        'bounces' => 0,
        'ctr' => 0,
        'bounce_rate' => 0,
        'avg_time_on_screen' => 0,
      ];
    }

    $placeholders = implode(',', array_fill(0, count($popupIds), '%d'));
    $statNames = ['total_views', 'unique_views', 'bounces', 'time_on_screen'];
    $statPlaceholders = implode(',', array_fill(0, count($statNames), '%s'));

    $statsSql = "
      SELECT mod_obj_id, option_name, option_value
      FROM {$wpdb->prefix}ppsgen_options
      WHERE mod_slug = 'popup'
        AND option_group = 'stats'
        AND mod_obj_id IN ($placeholders)
        AND option_name IN ($statPlaceholders)
    ";
    $statsParams = array_merge($popupIds, $statNames);
    $statsRows = $wpdb->get_results($wpdb->prepare($statsSql, ...$statsParams), ARRAY_A);

    foreach ($statsRows as $row) {
      $popupId = (int) $row['mod_obj_id'];
      if (empty($summaries[$popupId])) {
        continue;
      }
      $optionName = $row['option_name'];
      $optionValue = $this->_prepareValueFromStore($row['option_value']);
      if ($optionName === 'time_on_screen') {
        $summaries[$popupId]['time_on_screen_raw'] = (string) $optionValue;
      } else {
        $summaries[$popupId][$optionName] = (int) $optionValue;
      }
    }

    $submitSql = "
      SELECT popup_id,
             SUM(CASE WHEN action_type = 'submit' THEN 1 ELSE 0 END) AS form_submits,
             SUM(CASE WHEN action_type <> 'submit' THEN 1 ELSE 0 END) AS actions
      FROM {$wpdb->prefix}ppsgen_popup_submits
      WHERE popup_id IN ($placeholders)
      GROUP BY popup_id
    ";
    $submitRows = $wpdb->get_results($wpdb->prepare($submitSql, ...$popupIds), ARRAY_A);

    foreach ($submitRows as $row) {
      $popupId = (int) $row['popup_id'];
      if (empty($summaries[$popupId])) {
        continue;
      }
      $summaries[$popupId]['form_submits'] = (int) $row['form_submits'];
      $summaries[$popupId]['actions'] = (int) $row['actions'];
    }

    foreach ($summaries as $popupId => $summary) {
      $interactions = (int) $summary['form_submits'] + (int) $summary['actions'];
      $summaries[$popupId]['ctr'] = (int) $summary['unique_views'] > 0 ? round(($interactions / (int) $summary['unique_views']) * 100, 2) : 0;
      $summaries[$popupId]['bounce_rate'] = (int) $summary['total_views'] > 0 ? round(((int) $summary['bounces'] / (int) $summary['total_views']) * 100, 2) : 0;

      $avgTime = 0;
      $rawTime = $summary['time_on_screen_raw'] ?? '';
      if (!empty($rawTime) && strpos($rawTime, '_') !== false) {
        [$sumTime, $viewCount] = explode('_', $rawTime);
        $avgTime = (int) $viewCount > 0 ? round((int) $sumTime / (int) $viewCount / 1000, 2) : 0;
      }
      $summaries[$popupId]['avg_time_on_screen'] = $avgTime;
      unset($summaries[$popupId]['time_on_screen_raw']);
    }

    return $summaries;
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

  public function deletePopup($fkey)
  {
    global $wpdb;
    if (!empty($fkey)) {
      $wpdb->delete("{$wpdb->prefix}ppsgen_popup_fields", ['popup_id' => $fkey]);
      $wpdb->delete("{$wpdb->prefix}ppsgen_popup_submits", ['popup_id' => $fkey]);
      return $wpdb->delete("{$wpdb->prefix}ppsgen_popups", ['id' => $fkey]);
    }
    return false;
  }

  public function deletePopupSubmit($fkey)
  {
    global $wpdb;
    if (!empty($fkey)) {
      return $wpdb->delete("{$wpdb->prefix}ppsgen_popup_submits", ['id' => $fkey]);
    }
    return false;
  }

  public function resetPopupStats($popupId)
  {
    global $wpdb;

    $popupId = (int) $popupId;
    if (empty($popupId)) {
      return false;
    }

    $wpdb->delete("{$wpdb->prefix}ppsgen_popup_submits", ['popup_id' => $popupId], ['%d']);
    $wpdb->delete(
      "{$wpdb->prefix}ppsgen_options",
      [
        'mod_slug' => 'popup',
        'mod_obj_id' => $popupId,
        'option_group' => 'stats',
      ],
      ['%s', '%d', '%s'],
    );

    return true;
  }

  public function getPopupById($fkey)
  {
    global $wpdb;
    if (!empty($fkey)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_popups WHERE " . $wpdb->prepare('id = %d', [$fkey]), ARRAY_A);
    }
    return false;
  }

  public function editPopup($data, $obj_id = 0)
  {
    global $wpdb;
    if (!empty($data)) {
      $cdata = !empty($data['popup']) ? $data['popup'] : [];
      $cdataDesign = !empty($data['popup']['design']) ? $data['popup']['design'] : [];
      $cdataSettings = !empty($data['popup']['settings']) ? $data['popup']['settings'] : [];
      $cdataFields = !empty($data['popup']['fields']) ? $data['popup']['fields'] : [];
      if (isset($cdataSettings['custom_css'])) {
        $cdataSettings['custom_css'] = $this->getFrame()->core('helper')->sanitizeCustomCssCode($cdataSettings['custom_css']);
      }
      if (isset($cdataSettings['custom_js'])) {
        $cdataSettings['custom_js'] = $this->getFrame()->core('helper')->sanitizeCustomJsCode($cdataSettings['custom_js']);
      }
      $cdataPopupName = !empty($data['popup']['name']) ? $data['popup']['name'] : 'Popup ' . current_time('mysql');
      if (!empty($data)) {
        unset($data);
      }
      if (!empty($cdata['design'])) {
        unset($cdata['design']);
      }
      if (!empty($cdata['settings'])) {
        unset($cdata['settings']);
      }
      if (!empty($cdata['fields'])) {
        unset($cdata['fields']);
      }
      $cdata['name'] = $cdataPopupName;
      $cdata['updated_at'] = current_time('mysql');

      $cdata['design'] = $this->_prepareValueToStore($cdataDesign, 'json');
      $cdata['settings'] = $this->_prepareValueToStore($cdataSettings, 'json');
      //$cdata['fields'] = $this->_prepareValueToStore($cdataFields, 'json');

      if (!empty($obj_id)) {
        $res = $wpdb->update("{$wpdb->prefix}ppsgen_popups", $cdata, ['id' => $obj_id]);
        if (!empty($res)) {
          $this->savePopupFields($obj_id, $cdataFields);
          $this->setStatus('success');
          $this->setMessage(__('Popup is updated.', 'ppsgen'));
          return $res;
        } else {
          $this->setStatus('error');
          $this->setMessage(__('Error! Popup is not updated.', 'ppsgen'));
          return false;
        }
      } else {
        $res = $wpdb->insert("{$wpdb->prefix}ppsgen_popups", $cdata);
        $popup_id = $wpdb->insert_id;
        if (!empty($popup_id)) {
          $this->savePopupFields($popup_id, $cdataFields);
          $this->setStatus('success');
          $this->setMessage(__('Popup is added.', 'ppsgen'));
          return $popup_id;
        } else {
          $this->setStatus('error');
          $this->setMessage(__('Error! Popup is not added.', 'ppsgen'));
          return false;
        }
      }
    }
  }

  public function getPopupFields($fkey)
  {
    if (!empty($fkey)) {
      global $wpdb;
      $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_popup_fields WHERE popup_id = %d ORDER BY order_index ASC", $fkey), ARRAY_A);
      $fields = $this->preparePopupFields($fields);
      return $fields;
    }
    return false;
  }

  public function hasUserSubmittedPopup($popupId, $userId)
  {
    global $wpdb;
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_popup_submits WHERE popup_id = %d AND wp_user_id = %d", $popupId, $userId));
  }

  public function hasIpSubmittedPopup($popupId, $ip)
  {
    global $wpdb;
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_popup_submits WHERE popup_id = %d AND ip = %s", $popupId, $ip));
  }

  public function preparePopupFields($fields)
  {
    if (!empty($fields)) {
      global $wpdb;
      foreach ($fields as $key => $field) {
        $fields[$key]['settings'] = $this->_prepareValueFromStore($field['settings'], 'json');
        if (!empty($fields[$key]['settings']['html_textarea_content'])) {
          $fields[$key]['settings']['html_textarea_content'] = htmlspecialchars_decode(stripslashes($fields[$key]['settings']['html_textarea_content']));
        }
      }
      return $fields;
    }
    return false;
  }

  public function savePopupFields($popup_id, $fields)
  {
    if (empty($popup_id) || empty($fields)) {
      return false;
    }

    global $wpdb;
    $popup_id = (int) $popup_id;
    $processedFkeys = [];
    $existingFkeys = $wpdb->get_col($wpdb->prepare("SELECT fkey FROM {$wpdb->prefix}ppsgen_popup_fields WHERE popup_id = %d", $popup_id));

    foreach ($fields as $fkey => $field) {
      if (empty($field['field_key'])) {
        continue;
      }
      $fkey = sanitize_key($field['field_key']);
      $processedFkeys[] = $fkey;
      $settings = $field;
      $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_popup_fields WHERE popup_id = %d AND fkey = %s", $popup_id, $fkey));
      $data = [
        'popup_id' => $popup_id,
        'fkey' => $fkey,
        'parent_key' => $field['parent_key'],
        'type' => $field['field_type'],
        'settings' => $this->_prepareValueToStore($settings, 'json'),
        'order_index' => !empty($field['order_index']) ? $field['order_index'] : 0,
        'updated_date' => current_time('mysql'),
      ];
      if ($exists) {
        $wpdb->update("{$wpdb->prefix}ppsgen_popup_fields", $data, ['popup_id' => $popup_id, 'fkey' => $fkey]);
      } else {
        $data['created_date'] = current_time('mysql');
        $wpdb->insert("{$wpdb->prefix}ppsgen_popup_fields", $data);
      }
    }

    $toDeleteFkeys = array_diff($existingFkeys, $processedFkeys);
    foreach ($toDeleteFkeys as $fkeyToDelete) {
      $wpdb->delete(
        "{$wpdb->prefix}ppsgen_popup_fields",
        [
          'popup_id' => $popup_id,
          'fkey' => sanitize_key($fkeyToDelete),
        ],
        ['%d', '%s'],
      );
    }
    return true;
  }

  public function getPopupSettings($popup_id)
  {
    global $wpdb;
    $res = $wpdb->get_var($wpdb->prepare("SELECT settings FROM {$wpdb->prefix}ppsgen_popups WHERE id = %d", $popup_id));
    if (!empty($res)) {
      return $this->_prepareValueFromStore($res);
    }
    return false;
  }

  public function savePopupAction($popup_id, $action, $action_meta, $wp_user_id = null, $options = [])
  {
    global $wpdb;
    $ip = $this->getFrame()->core('request')->getServerVar('REMOTE_ADDR');
    $user_agent = !empty($options['user_agent']) ? substr($options['user_agent'], 0, 1000) : null;
    $is_unique = !empty($options['is_unique']) ? 1 : 0;
    $is_bounce = !empty($options['is_bounce']) ? 1 : 0;
    $time_on_screen = !empty($options['time_on_screen']) ? (int) $options['time_on_screen'] : 0;
    $extra = !empty($options['extra']) ? $options['extra'] : null;
    if ($action === 'view') {
      $this->statsAddTotalView($popup_id);
      if ($is_unique) {
        $this->statsAddUniqueView($popup_id);
      }
      return true;
    }
    if ($action === 'close') {
      $this->statsAddTotalView($popup_id);
      if ($is_unique) {
        $this->statsAddUniqueView($popup_id);
      }
      if ($is_bounce) {
        $this->statsAddBounce($popup_id);
      }
      if ($time_on_screen > 0) {
        $this->statsAddTimeOnScreen($popup_id, $time_on_screen);
      }
      return true;
    }
    $data = [
      'popup_id' => $popup_id,
      'action_type' => $action,
      'action_meta' => $action_meta,
      'wp_user_id' => $wp_user_id,
      'ip' => $ip,
      'created_at' => current_time('mysql'),
    ];
    if (!is_null($is_unique)) {
      $data['is_unique'] = $is_unique;
    }
    if (!is_null($is_bounce)) {
      $data['is_bounce'] = $is_bounce;
    }
    if (!empty($time_on_screen)) {
      $data['time_on_screen'] = $time_on_screen;
    }
    if (!empty($user_agent)) {
      $data['user_agent'] = $user_agent;
    }
    if (!empty($extra)) {
      $data['utm_source'] = !empty($extra['utm_source']) ? $extra['utm_source'] : '';
      $data['utm_medium'] = !empty($extra['utm_medium']) ? $extra['utm_medium'] : '';
      $data['utm_campaign'] = !empty($extra['utm_campaign']) ? $extra['utm_campaign'] : '';
      $data['utm_term'] = !empty($extra['utm_term']) ? $extra['utm_term'] : '';
      $data['utm_content'] = !empty($extra['utm_content']) ? $extra['utm_content'] : '';
      $data['referer'] = !empty($extra['referer']) ? $extra['referer'] : '';
      $data['url'] = !empty($extra['url']) ? $extra['url'] : '';
    }
    $result = $wpdb->insert("{$wpdb->prefix}ppsgen_popup_submits", $data, [
      '%d',
      '%s',
      '%s',
      '%d',
      '%s',
      '%s', // popup_id, action_type, action_meta, wp_user_id, ip, created_at
      '%d',
      '%d',
      '%d',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s', // is_unique, is_bounce, time_on_screen, user_agent, extra
    ]);
    return $result !== false ? $wpdb->insert_id : false;
  }

  public function statsAddTotalView($popup_id)
  {
    $stats = $this->getOption('total_views', 'stats', $popup_id, 'popup');
    $newCounter = !empty($stats) ? (int) $stats + 1 : 1;
    return $this->updateOption('total_views', 'stats', $newCounter, $popup_id, 'popup', 'json');
  }

  public function statsAddUniqueView($popup_id)
  {
    $stats = $this->getOption('unique_views', 'stats', $popup_id, 'popup');
    $newCounter = !empty($stats) ? (int) $stats + 1 : 1;
    return $this->updateOption('unique_views', 'stats', $newCounter, $popup_id, 'popup', 'json');
  }

  public function statsAddBounce($popup_id)
  {
    $stats = $this->getOption('bounces', 'stats', $popup_id, 'popup');
    $newCounter = !empty($stats) ? (int) $stats + 1 : 1;
    return $this->updateOption('bounces', 'stats', $newCounter, $popup_id, 'popup', 'json');
  }

  public function statsAddTimeOnScreen($popup_id, $duration)
  {
    $current = $this->getOption('time_on_screen', 'stats', $popup_id, 'popup');
    $avg = 0;
    $views = 0;
    if (!empty($current) && strpos($current, '_') !== false) {
      [$avg, $views] = explode('_', $current);
      $avg = (int) $avg;
      $views = (int) $views;
    }
    $views += 1;
    $newAvg = $views > 1 ? floor(($avg * ($views - 1) + $duration) / $views) : (int) $duration;
    $newValue = $newAvg . '_' . $views;
    return $this->updateOption('time_on_screen', 'stats', $newValue, $popup_id, 'popup', 'json');
  }

  public function getPopupsByShowOn(string $showOn): array
  {
    global $wpdb;
    return array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
      "SELECT id FROM {$wpdb->prefix}ppsgen_popups WHERE active = 1 AND settings LIKE %s",
      '%' . $wpdb->esc_like('"show_on":"' . $showOn . '"') . '%'
    )));
  }

  public function getSearchEngines()
  {
    return [
      'google.com' => esc_html__('Google', 'ppsgen'),
      'yahoo.com' => esc_html__('Yahoo!', 'ppsgen'),
      'youdao.com' => esc_html__('Youdao', 'ppsgen'),
      'yandex' => esc_html__('Yandex', 'ppsgen'),
      'sogou.com' => esc_html__('Sogou', 'ppsgen'),
      'qwant.com' => esc_html__('Qwant', 'ppsgen'),
      'bing.com' => esc_html__('Bing', 'ppsgen'),
      'duckduckgo.com' => esc_html__('DuckDuckGo', 'ppsgen'),
      'munax.com' => esc_html__('Munax', 'ppsgen'),
      'facebook.com' => esc_html__('Facebook', 'ppsgen'),
      'pinterest.com' => esc_html__('Pinterest', 'ppsgen'),
      'instagram.com' => esc_html__('Instagram', 'ppsgen'),
      'yelp.com' => esc_html__('Yelp', 'ppsgen'),
      'vk.com' => esc_html__('VKontakte', 'ppsgen'),
      'myspace.com' => esc_html__('Myspace', 'ppsgen'),
      'linkedin.com' => esc_html__('LinkedIn', 'ppsgen'),
    ];
  }

  public function getAllActivePopups()
  {
    global $wpdb;
    $rows = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}ppsgen_popups WHERE active = 1 AND settings NOT LIKE '%\"show_on\":\"after_checkout\"%' AND settings NOT LIKE '%\"show_on\":\"build_in_page\"%'");
    return $rows;
  }

  public function popupIsEnabled($popup_id)
  {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT active FROM {$wpdb->prefix}ppsgen_popups WHERE active = 1 AND id = %d", $popup_id));
  }

  public function getPopupSubmits($popup_id)
  {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_popup_submits WHERE popup_id = %d", $popup_id), ARRAY_A);
  }

  protected function getTrendBuckets(string $groupBy, int $range): array
  {
    $buckets = [];
    $today = current_time('timestamp');
    if ($groupBy === 'month') {
      for ($i = $range - 1; $i >= 0; $i--) {
        $ts = strtotime("-{$i} month", $today);
        $key = wp_date('Y-m-01', $ts);
        $buckets[$key] = [
          'label' => wp_date('M Y', $ts),
          'entries' => 0,
          'actions' => 0,
          'submits' => 0,
          'bounces' => 0,
        ];
      }
      return $buckets;
    }
    if ($groupBy === 'week') {
      $monday = strtotime('monday this week', $today);
      for ($i = $range - 1; $i >= 0; $i--) {
        $ts = strtotime('-' . $i . ' week', $monday);
        $key = wp_date('Y-m-d', $ts);
        $buckets[$key] = [
          'label' => wp_date('d M', $ts),
          'entries' => 0,
          'actions' => 0,
          'submits' => 0,
          'bounces' => 0,
        ];
      }
      return $buckets;
    }
    for ($i = $range - 1; $i >= 0; $i--) {
      $ts = strtotime("-{$i} day", $today);
      $key = wp_date('Y-m-d', $ts);
      $buckets[$key] = [
        'label' => wp_date('d M', $ts),
        'entries' => 0,
        'actions' => 0,
        'submits' => 0,
        'bounces' => 0,
      ];
    }
    return $buckets;
  }

  public function getPopupTrend(int $popup_id, string $groupBy = 'day', int $range = 30): array
  {
    global $wpdb;
    $popup_id = (int) $popup_id;
    if (empty($popup_id)) {
      return [];
    }
    $groupBy = in_array($groupBy, ['day', 'week', 'month'], true) ? $groupBy : 'day';
    $range = in_array($range, [7, 30, 90, 365], true) ? $range : 30;
    $buckets = $this->getTrendBuckets($groupBy, $groupBy === 'month' ? min(12, max(3, (int) ceil($range / 30))) : ($groupBy === 'week' ? min(16, max(4, (int) ceil($range / 7))) : $range));
    $keys = array_keys($buckets);
    $dateFrom = reset($keys);

    if ($groupBy === 'month') {
      $bucketSql = "DATE_FORMAT(created_at, '%Y-%m-01')";
    } elseif ($groupBy === 'week') {
      $bucketSql = "DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY)";
    } else {
      $bucketSql = 'DATE(created_at)';
    }

    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT 
          {$bucketSql} AS bucket_key,
          COUNT(*) AS entries,
          SUM(CASE WHEN action_type != 'submit' THEN 1 ELSE 0 END) AS actions,
          SUM(CASE WHEN action_type = 'submit' THEN 1 ELSE 0 END) AS submits,
          SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) AS bounces
        FROM {$wpdb->prefix}ppsgen_popup_submits
        WHERE popup_id = %d AND created_at >= %s
        GROUP BY bucket_key
        ORDER BY bucket_key ASC",
        $popup_id,
        $dateFrom . ' 00:00:00',
      ),
      ARRAY_A,
    );

    foreach ($rows as $row) {
      $bucketKey = (string) ($row['bucket_key'] ?? '');
      if (!isset($buckets[$bucketKey])) {
        continue;
      }
      $buckets[$bucketKey]['entries'] = (int) $row['entries'];
      $buckets[$bucketKey]['actions'] = (int) $row['actions'];
      $buckets[$bucketKey]['submits'] = (int) $row['submits'];
      $buckets[$bucketKey]['bounces'] = (int) $row['bounces'];
    }

    return array_values($buckets);
  }

  public function getPostTypes()
  {
    $post_types = get_post_types(['public' => true, 'show_ui' => true], 'objects');
    $options = [];
    $exclude = ['attachment']; // Exclude media/attachments
    foreach ($post_types as $post_type) {
      if (!in_array($post_type->name, $exclude)) {
        $options[$post_type->name] = $post_type->labels->singular_name;
      }
    }
    return $options;
  }

  public function getPagesAndPosts()
  {
    $args = [
      'post_type' => ['page', 'post'], // Can be expanded to include custom post types
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'title',
      'order' => 'ASC',
    ];
    $posts = get_posts($args);
    $options = [];
    foreach ($posts as $post) {
      $options[$post->ID] = $post->post_title;
    }
    return $options;
  }

  public function getCategories()
  {
    $categories = get_categories([
      'taxonomy' => 'category',
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    $options = [];
    foreach ($categories as $category) {
      $options[$category->term_id] = $category->name;
    }
    return $options;
  }

  public function duplicatePopup($obj_id)
  {
    global $wpdb;
    $popup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_popups WHERE id = %d", $obj_id), ARRAY_A);
    $popup_new = null;
    $now = current_time('mysql');
    if (!empty($popup)) {
      $popup_data = [
        'name' => $popup['name'] . ' Copy',
        'settings' => $popup['settings'] ?? '{}',
        'design' => $popup['design'] ?? '{}',
        'active' => $popup['active'] ?? 0,
        'created_at' => $now,
        'updated_at' => $now,
      ];
      $wpdb->insert("{$wpdb->prefix}ppsgen_popups", $popup_data);
      $popup_id_new = $wpdb->insert_id;
      $this->setStatus('success');
      if (!$popup_id_new) {
        return false;
      }
      $popup_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_popup_fields WHERE popup_id = %d", $obj_id), ARRAY_A);
      if (!empty($popup_fields) && is_array($popup_fields)) {
        foreach ($popup_fields as $field) {
          $field_data = [
            'popup_id' => $popup_id_new,
            'parent_key' => $field['parent_key'] ?? '',
            'fkey' => $field['fkey'] ?? '',
            'type' => $field['type'] ?? '',
            'settings' => $field['settings'] ?? '{}',
            'order_index' => $field['order_index'] ?? 0,
            'created_date' => $now,
            'updated_date' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_popup_fields", $field_data);
        }
      }
    }
    return $popup_id_new;
  }
}
