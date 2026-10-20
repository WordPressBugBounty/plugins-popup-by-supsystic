<?php

if (!defined('ABSPATH')) {
  exit();
}

class FormModelPpsgen extends BaseModelPpsgen
{
  private $redirectUrl = null;

  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $installer = $this->getFrame()->core('installer');
    if (!$installer->exist('ppsgen_forms')) {
      $tableName = $wpdb->prefix . 'ppsgen_forms';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `form_type` VARCHAR(255) NOT NULL DEFAULT 'submit',
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
    if (!$installer->exist('ppsgen_form_fields')) {
      $tableName = $wpdb->prefix . 'ppsgen_form_fields';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `form_id` BIGINT(20) UNSIGNED NOT NULL,
        `cf_id` BIGINT(20) UNSIGNED NOT NULL,
        `fkey` VARCHAR(255) NOT NULL,
        `settings` TEXT NULL,
        `options` TEXT NULL,
        `order_index` INT(11) NOT NULL DEFAULT 0,
        `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`form_id`),
        INDEX (`cf_id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
    if (!$installer->exist('ppsgen_form_submits')) {
      $tableName = $wpdb->prefix . 'ppsgen_form_submits';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `form_id` BIGINT(20) UNSIGNED NOT NULL,
        `wp_user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
        `contact_id` BIGINT(20) UNSIGNED DEFAULT NULL,
        `ip` VARCHAR(255) DEFAULT NULL,
        `fields_values` TEXT NULL,
        `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`form_id`),
        INDEX (`wp_user_id`),
        INDEX (`contact_id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
  }

  public function getForms(array $args): array
  {
    global $wpdb;
    $defaults = [
      'page' => 1,
      'per_page' => 100,
      'order_by' => 'id',
      'order_dir' => 'ASC',
      'columns' => ['id', 'name', 'active', 'created_at', 'updated_at'],
      'filters' => [],
    ];
    $args = wp_parse_args($args, $defaults);
    $whereParts = ['1=1'];
    $params = [];
    if (!empty($args['filters']['id'])) {
      $whereParts[] = 'id = %d';
      $params[] = (int) $args['filters']['id'];
    }
    if (!empty($args['filters']['active'])) {
      $val = $args['filters']['active'] === 'x' ? 0 : 1;
      $whereParts[] = 'active = %d';
      $params[] = $val;
    }
    if (!empty($args['filters']['name'])) {
      $whereParts[] = 'name LIKE %s';
      $params[] = $args['filters']['name'];
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
        FROM {$wpdb->prefix}ppsgen_forms
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
        FROM {$wpdb->prefix}ppsgen_forms
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

  public function getFormStatsSummaryByIds(array $formIds): array
  {
    global $wpdb;

    $formIds = array_values(array_filter(array_map('intval', $formIds)));
    if (empty($formIds)) {
      return [];
    }

    $summaries = [];
    foreach ($formIds as $formId) {
      $summaries[$formId] = [
        'form_view' => 0,
        'submit_total' => 0,
        'submit_error' => 0,
        'submit_success' => 0,
        'ctr' => 0,
        'success_rate' => 0,
        'error_rate' => 0,
        'view_to_success_rate' => 0,
      ];
    }

    $placeholders = implode(',', array_fill(0, count($formIds), '%d'));
    $statNames = ['form_view', 'submit_total', 'submit_error', 'submit_success'];
    $statPlaceholders = implode(',', array_fill(0, count($statNames), '%s'));

    $sql = "
      SELECT mod_obj_id, option_name, option_value
      FROM {$wpdb->prefix}ppsgen_options
      WHERE mod_slug = 'form'
        AND option_group = 'stats'
        AND mod_obj_id IN ($placeholders)
        AND option_name IN ($statPlaceholders)
    ";
    $params = array_merge($formIds, $statNames);
    $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);

    foreach ($rows as $row) {
      $formId = (int) $row['mod_obj_id'];
      if (empty($summaries[$formId])) {
        continue;
      }
      $summaries[$formId][$row['option_name']] = (int) $this->_prepareValueFromStore($row['option_value']);
    }

    foreach ($summaries as $formId => $summary) {
      $views = (int) $summary['form_view'];
      $submits = (int) $summary['submit_total'];
      $success = (int) $summary['submit_success'];
      $errors = (int) $summary['submit_error'];

      $summaries[$formId]['ctr'] = $views > 0 ? round(($submits / $views) * 100, 2) : 0;
      $summaries[$formId]['success_rate'] = $submits > 0 ? round(($success / $submits) * 100, 2) : 0;
      $summaries[$formId]['error_rate'] = $submits > 0 ? round(($errors / $submits) * 100, 2) : 0;
      $summaries[$formId]['view_to_success_rate'] = $views > 0 ? round(($success / $views) * 100, 2) : 0;
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

  public function deleteForm($cf_id)
  {
    global $wpdb;
    if (!empty($cf_id)) {
      $wpdb->delete("{$wpdb->prefix}ppsgen_form_fields", ['form_id' => $cf_id]);
      $wpdb->delete("{$wpdb->prefix}ppsgen_form_submits", ['form_id' => $cf_id]);
      return $wpdb->delete("{$wpdb->prefix}ppsgen_forms", ['id' => $cf_id]);
    }
    return false;
  }

  public function deleteFormSubmit($cf_id)
  {
    global $wpdb;
    if (!empty($cf_id)) {
      return $wpdb->delete("{$wpdb->prefix}ppsgen_form_submits", ['id' => $cf_id]);
    }
    return false;
  }

  public function resetFormStats($formId)
  {
    global $wpdb;

    $formId = (int) $formId;
    if (empty($formId)) {
      return false;
    }

    $wpdb->delete("{$wpdb->prefix}ppsgen_form_submits", ['form_id' => $formId], ['%d']);
    $wpdb->delete(
      "{$wpdb->prefix}ppsgen_options",
      [
        'mod_slug' => 'form',
        'mod_obj_id' => $formId,
        'option_group' => 'stats',
      ],
      ['%s', '%d', '%s'],
    );

    return true;
  }

  public function getFormById($cf_id)
  {
    global $wpdb;
    if (!empty($cf_id)) {
      return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ppsgen_forms WHERE " . $wpdb->prepare('id = %d', [$cf_id]), ARRAY_A);
    }
    return false;
  }

  public function editForm($data, $obj_id = 0)
  {
    global $wpdb;
    if (!empty($data)) {
      $cdata = !empty($data['form']) ? $data['form'] : [];
      $cdataDesign = !empty($data['form']['design']) ? $data['form']['design'] : [];
      $cdataSettings = !empty($data['form']['settings']) ? $data['form']['settings'] : [];
      $cdataFields = !empty($data['form']['fields']) ? $data['form']['fields'] : [];
      if (isset($cdataSettings['custom_css'])) {
        $cdataSettings['custom_css'] = $this->getFrame()->core('helper')->sanitizeCustomCssCode($cdataSettings['custom_css']);
      }
      if (isset($cdataSettings['custom_js'])) {
        $cdataSettings['custom_js'] = $this->getFrame()->core('helper')->sanitizeCustomJsCode($cdataSettings['custom_js']);
      }
      $cdataFormName = !empty($data['form']['name']) ? $data['form']['name'] : 'Form ' . current_time('mysql');
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
      $cdata['name'] = $cdataFormName;
      $cdata['updated_at'] = current_time('mysql');
      $formData = $cdata;
      if (!empty($cdata['addons'])) {
        unset($cdata['addons']);
      }
      $cdata['design'] = $this->_prepareValueToStore($cdataDesign, 'json');
      $cdata['settings'] = $this->_prepareValueToStore($cdataSettings, 'json');
      //$cdata['fields'] = $this->_prepareValueToStore($cdataFields, 'json');
      if (!empty($obj_id)) {
        $res = $wpdb->update("{$wpdb->prefix}ppsgen_forms", $cdata, ['id' => $obj_id]);
        if (!empty($res)) {
          $this->saveFormFields($obj_id, $cdataFields);
          $this->setStatus('success');
          $this->setMessage(__('Form is updated.', 'ppsgen'));
          $this->getFrame()->core('eventDispatcher')->doAction('form.edit.action.model.saved', $obj_id, $formData);
          return $res;
        } else {
          $this->setStatus('error');
          $this->setMessage(__('Error! Form is not updated.', 'ppsgen'));
          return false;
        }
      } else {
        $res = $wpdb->insert("{$wpdb->prefix}ppsgen_forms", $cdata);
        $cf_id = $wpdb->insert_id;
        if (!empty($cf_id)) {
          $this->saveFormFields($cf_id, $cdataFields);
          $this->setStatus('success');
          $this->setMessage(__('Form is added.', 'ppsgen'));
          $this->getFrame()->core('eventDispatcher')->doAction('form.edit.action.model.saved', $cf_id, $formData);
          return $cf_id;
        } else {
          $this->setStatus('error');
          $this->setMessage(__('Error! Form is not added.', 'ppsgen'));
          return false;
        }
      }
    }
  }

  public function changeFormFieldData($cf_id, $data)
  {
    if (!empty($cf_id) && !empty($data)) {
      global $wpdb;
      $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_fields WHERE cf_id = %d", $cf_id), ARRAY_A);
      foreach ($fields as $field) {
        if (!empty($field)) {
          $field['settings'] = $this->_prepareValueFromStore($field['settings'], 'json');
          $field['settings']['field_type'] = $data['type'];
          $field['settings']['field_slug'] = $data['slug'];
          $field['settings'] = $this->_prepareValueToStore($field['settings'], 'json');
          $wpdb->update("{$wpdb->prefix}ppsgen_form_fields", ['settings' => $field['settings']], ['cf_id' => $cf_id, 'form_id' => $field['form_id']]);
        }
      }
      return true;
    }
    return false;
  }

  public function removeContactFieldFromForms($cf_id)
  {
    global $wpdb;
    $cf_id = (int) $cf_id;
    if (empty($cf_id)) {
      return false;
    }
    $wpdb->delete("{$wpdb->prefix}ppsgen_form_fields", ['cf_id' => $cf_id], ['%d']);
    return true;
  }

  public function getFormFields($cf_id, $context = 'merge')
  {
    if (!empty($cf_id)) {
      global $wpdb;
      $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_fields WHERE form_id = %d ORDER BY order_index ASC", $cf_id), ARRAY_A);
      $fields = $this->prepareFormFields($fields, $context);
      return $fields;
    }
    return false;
  }

  public function hasUserSubmittedForm($formId, $userId)
  {
    global $wpdb;
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_form_submits WHERE form_id = %d AND wp_user_id = %d", $formId, $userId));
  }

  public function hasIpSubmittedForm($formId, $ip)
  {
    global $wpdb;
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_form_submits WHERE form_id = %d AND ip = %s", $formId, $ip));
  }

  public function prepareFormFields($fields, $context = 'merge')
  {
    if (!empty($fields)) {
      global $wpdb;
      foreach ($fields as $key => $field) {
        if (!empty($field['cf_id'])) {
          $origCf = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_contact_fields WHERE id = %d", $field['cf_id']), ARRAY_A);
          if (empty($origCf) || !is_array($origCf)) {
            if (!empty($field['settings'])) {
              $fields[$key]['settings'] = $this->_prepareValueFromStore($field['settings'], 'json');
            } else {
              $fields[$key]['settings'] = [];
            }
            $fields[$key]['options'] = !empty($field['options']) ? $this->_prepareValueFromStore($field['options'], 'json') : [];
            continue;
          }
          $origOptions = !empty($origCf['options']) ? $this->_prepareValueFromStore($origCf['options'], 'json') : [];
          $fieldOptions = !empty($field['options']) ? $this->_prepareValueFromStore($field['options'], 'json') : [];
          if (!is_array($origOptions)) {
            $origOptions = [];
          }
          if (!is_array($fieldOptions)) {
            $fieldOptions = [];
          }
          $fields[$key]['options'] = $this->mergeFormFieldOptions($origOptions, $fieldOptions);
          if (!empty($field['settings'])) {
            $origCf['settings'] = !empty($origCf['settings']) ? $this->_prepareValueFromStore($origCf['settings'], 'json') : [];
            $field['settings'] = $this->_prepareValueFromStore($field['settings'], 'json');
            if (!is_array($origCf['settings'])) {
              $origCf['settings'] = [];
            }
            if (!is_array($field['settings'])) {
              $field['settings'] = [];
            }
            $allowedSettingsKeys = [
              'field_key',
              'field_type',
              'field_id',
              'order_index',
              'field_slug',
              'required',
              'col_mob',
              'col_tab',
              'col_pc',
              'show_mob',
              'show_tab',
              'show_pc',
              'class',
              'id',
              'label',
              'hide_label',
              'placeholder',
              'show_tooltip',
              'title',
              'text_align',
              'title_text',
              'html_description_text',
              'submit_text',
              'predefined_value',
              'min',
              'max',
              'include_ids',
              'exclude_ids',
              'multiple_select',
              'custom_select',
              'show_thumbnail_in_custom_select',
              'show_product_price',
              'source_parent_id',
              'source_include_children',
              'show_with_children',
              'show_name_hierarchically',
              'sort_by',
              'sort_order',
              'file_multiple',
              'file_min_size',
              'file_max_size',
              'file_max_qty',
              'file_formats',
              'file_dropbox',
              'icon_class',
              'icon_position',
            ];
            if ($context == 'merge') {
              $fields[$key]['settings'] = array_merge($origCf['settings'], $field['settings']);
            } else {
              $fields[$key]['settings'] = [];
              foreach ($allowedSettingsKeys as $sKey) {
                if (isset($field['settings'][$sKey])) {
                  $fields[$key]['settings'][$sKey] = $field['settings'][$sKey];
                } elseif (isset($origCf['settings'][$sKey])) {
                  $fields[$key]['settings'][$sKey] = $origCf['settings'][$sKey];
                }
              }
            }
          } else {
            $fields[$key]['settings'] = !empty($origCf['settings']) ? $this->_prepareValueFromStore($origCf['settings'], 'json') : [];
            if (!is_array($fields[$key]['settings'])) {
              $fields[$key]['settings'] = [];
            }
          }
        } else {
          if (!empty($field['settings'])) {
            $fields[$key]['settings'] = $this->_prepareValueFromStore($field['settings'], 'json');
            if (!is_array($fields[$key]['settings'])) {
              $fields[$key]['settings'] = [];
            }
          } else {
            $fields[$key]['settings'] = [];
          }
        }
      }
      return $fields;
    }
    return false;
  }

  protected function mergeFormFieldOptions($origOptions, $fieldOptions): array
  {
    $origOptions = is_array($origOptions) ? $origOptions : [];
    $fieldOptions = is_array($fieldOptions) ? $fieldOptions : [];

    if (empty($origOptions)) {
      return $fieldOptions;
    }

    $fieldOptionsByValue = [];
    foreach ($fieldOptions as $fieldOption) {
      if (!is_array($fieldOption) || !array_key_exists('value', $fieldOption)) {
        continue;
      }
      $fieldOptionsByValue[(string) $fieldOption['value']] = $fieldOption;
    }

    $preparedOptions = [];
    foreach ($origOptions as $origKey => $origOption) {
      if (!is_array($origOption) || !array_key_exists('value', $origOption)) {
        continue;
      }

      $valueKey = (string) $origOption['value'];
      $mergedOption = $origOption;

      if (isset($fieldOptionsByValue[$valueKey]) && is_array($fieldOptionsByValue[$valueKey])) {
        foreach ($fieldOptionsByValue[$valueKey] as $optionKey => $optionValue) {
          if ($optionKey === 'value') {
            continue;
          }
          $mergedOption[$optionKey] = $optionValue;
        }
      }

      $mergedOption['value'] = $origOption['value'];
      $preparedOptions[$origKey] = $mergedOption;
    }

    return $preparedOptions;
  }

  public function saveFormFields($cf_id, $fields)
  {
    if (!empty($cf_id) && !empty($fields)) {
      global $wpdb;
      $form_id = (int) $cf_id;
      $processedFkeys = [];

      foreach ($fields as $fkey => $field) {
        if (empty($field['field_key']) && empty($field['field_type']) && empty($field['field_id']) && empty($field['field_slug'])) {
          continue;
        }
        $fkey = sanitize_key($fkey);
        $processedFkeys[] = $fkey;
        $options = !empty($field['options']) ? $field['options'] : [];
        // Clean garbage option keys
        if (!empty($field['options'])) {
          unset($field['options']);
        }
        foreach ($field as $k => $v) {
          if (strpos($k, 'options[') === 0) {
            unset($field[$k]);
          }
        }
        // If user change field type in original field and save Form at the same time
        if (!empty($field['field_type']) && !empty($field['field_id'])) {
          $currentOriginalFieldType = $wpdb->get_var($wpdb->prepare("SELECT field_type FROM {$wpdb->prefix}ppsgen_contact_fields WHERE id = %d", $field['field_id']));
          if ($currentOriginalFieldType != $field['field_type']) {
            $field['field_type'] = $currentOriginalFieldType;
          }
        }
        $settings = $field;
        if (!empty($field['field_type']) && $this->getFrame()->getModule('contact')->getSubmod('contact_field')->getModel()->isObjectFieldType((string) $field['field_type'])) {
          $settings = $this->getFrame()->getModule('contact')->getSubmod('contact_field')->getModel()->normalizeObjectFieldSettings((string) $field['field_type'], $settings);
        }
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_form_fields WHERE form_id = %d AND fkey = %s", $form_id, $fkey));
        $data = [
          'form_id' => $form_id,
          'cf_id' => !empty($field['field_id']) ? $field['field_id'] : 0,
          'fkey' => $fkey,
          'settings' => $this->_prepareValueToStore($settings, 'json'),
          'options' => $this->_prepareValueToStore($options, 'json'),
          'order_index' => !empty($field['order_index']) ? $field['order_index'] : 0,
          'updated_date' => current_time('mysql'),
        ];
        if ($exists) {
          $wpdb->update("{$wpdb->prefix}ppsgen_form_fields", $data, ['form_id' => $form_id, 'fkey' => $fkey]);
        } else {
          $data['created_date'] = current_time('mysql');
          $wpdb->insert("{$wpdb->prefix}ppsgen_form_fields", $data);
        }
      }

      $existingFkeys = $wpdb->get_col($wpdb->prepare("SELECT fkey FROM {$wpdb->prefix}ppsgen_form_fields WHERE form_id = %d", $form_id));
      $toDeleteFkeys = array_diff($existingFkeys, $processedFkeys);
      foreach ($toDeleteFkeys as $fkeyToDelete) {
        $wpdb->delete(
          "{$wpdb->prefix}ppsgen_form_fields",
          [
            'form_id' => $form_id,
            'fkey' => sanitize_key($fkeyToDelete),
          ],
          ['%d', '%s'],
        );
      }
    }
    return false;
  }

  public function setRedirectUrl($url)
  {
    $this->redirectUrl = esc_url_raw($url);
  }

  public function getRedirectUrl()
  {
    return $this->redirectUrl;
  }

  public function getFormSettings($form_id)
  {
    global $wpdb;
    $res = $wpdb->get_var($wpdb->prepare("SELECT settings FROM {$wpdb->prefix}ppsgen_forms WHERE id = %d", $form_id));
    if (!empty($res)) {
      return $this->_prepareValueFromStore($res);
    }
    return false;
  }

  protected function getFormMailConfigId(array $formSettings): int
  {
    return !empty($formSettings['mail_config_id']) ? (int) $formSettings['mail_config_id'] : 0;
  }

  protected function sendFormEmail($to, $subject, $message, array $formSettings, $headers = '', array $attachments = []): bool
  {
    // Subjects take the same placeholders as the message ("New table request: [first_name]"), as plain text
    $subject = $this->replaceSubjectPlaceholders((string) $subject);
    $mailConfigId = $this->getFormMailConfigId($formSettings);
    $sent = $this->getFrame()->core('helper')->sendEmail($to, '', '', $subject, $message, $headers, $attachments, $mailConfigId);
    if ($sent) {
      $this->getFrame()->getModule('dashboard')->getModel()->consumeResolvedMailConfigLimit($mailConfigId);
    }
    return (bool) $sent;
  }

  public function frontendFormSubmitSignup($form_id, $fields)
  {
    global $wpdb;
    $formSettings = $this->getFormSettings($form_id);
    if (!empty($formSettings)) {
      $user_login_key = !empty($formSettings['regform_user_login']) ? $formSettings['regform_user_login'] : 'user_login';
      $email_key = !empty($formSettings['regform_field_email']) ? $formSettings['regform_field_email'] : 'email';
      $password_key = !empty($formSettings['regform_field_password']) ? $formSettings['regform_field_password'] : 'password';
      $first_name_key = !empty($formSettings['regform_first_name']) ? $formSettings['regform_first_name'] : 'first_name';
      $last_name_key = !empty($formSettings['regform_last_name']) ? $formSettings['regform_last_name'] : 'last_name';
      $nick_name_key = !empty($formSettings['regform_nick_name']) ? $formSettings['regform_nick_name'] : 'nick_name';
      $display_name_key = !empty($formSettings['regform_display_name']) ? $formSettings['regform_display_name'] : 'display_name';
      $role = !empty($formSettings['regform_role']) ? $formSettings['regform_role'] : 'subscriber';
      $email = isset($fields[$email_key]) ? sanitize_email($fields[$email_key]) : '';
      $user_login = isset($fields[$user_login_key]) ? $fields[$user_login_key] : '';
      $password = isset($fields[$password_key]) ? $fields[$password_key] : '';
      $first_name = isset($fields[$first_name_key]) ? $fields[$first_name_key] : '';
      $last_name = isset($fields[$last_name_key]) ? $fields[$last_name_key] : '';
      $nick_name = isset($fields[$nick_name_key]) ? $fields[$nick_name_key] : '';
      $display_name = isset($fields[$display_name_key]) ? $fields[$display_name_key] : '';
      $redirectLink = !empty($formSettings['redirect_link']) ? esc_url_raw($formSettings['redirect_link']) : '';
      if (empty($email) || empty($password) || empty($user_login)) {
        $this->addError('submit', __('Error! Required registration fields are missing.', 'ppsgen'));
        return false;
      }
      if (!is_email($email)) {
        $this->addError('submit', __('Error! Invalid email address.', 'ppsgen'));
        return false;
      }
      if (username_exists($user_login)) {
        $this->addError('submit', __('Error! Username already exists.', 'ppsgen'));
        return false;
      }
      if (email_exists($email)) {
        $this->addError('submit', __('Error! Email already registered.', 'ppsgen'));
        return false;
      }
      $user_id = wp_create_user($user_login, $password, $email);
      if (is_wp_error($user_id)) {
        $this->addError('submit', __('Error! Could not create user: ', 'ppsgen') . $user_id->get_error_message());
        return false;
      }
      wp_update_user([
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'nickname' => $nick_name,
        'display_name' => $display_name ?: $user_login,
        'role' => $role,
      ]);
      $contact_id = 0;
      if (!empty($formSettings['contact_handle_signup'])) {
        $data['fields'] = $fields;
        $data['user_id'] = $user_id;
        $data['tag_ids'] = !empty($formSettings['specific_tags']) ? $formSettings['specific_tags'] : [];
        $contact_id = $this->getFrame()->getModule('contact')->getModel()->createContactBySubmit($data);
      }
      if (!empty($formSettings['send_admin'])) {
        $sendToEmail = !empty($formSettings['send_admin_email']) ? $formSettings['send_admin_email'] : '';
        if (!empty($sendToEmail)) {
          $standard_subject = __('Successful registration on the site', 'ppsgen');
          $standard_template = __('You have new registered user on the site: [email]', 'ppsgen');
          $html_template = !empty($formSettings['html_send_admin_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_admin_text']) : '';
          $message = !empty($html_template) ? $html_template : $standard_template;
          $message = $this->replaceEmailPlaceholders($message, $fields);
          $subject = !empty($formSettings['send_admin_subject']) ? $formSettings['send_admin_subject'] : '';
          $subject = !empty($subject) ? $subject : $standard_subject;
          $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
          if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
            $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
          }
          $sendRes = $this->sendFormEmail($sendToEmail, $subject, $message, $formSettings);
        }
      }
      if (!empty($formSettings['send_contact'])) {
        $standard_subject = __('Successful registration on the site', 'ppsgen');
        $standard_template = __('You have just been successfully registered on the site.', 'ppsgen');
        $html_template = !empty($formSettings['html_send_contact_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_contact_text']) : '';
        $message = !empty($html_template) ? $html_template : $standard_template;
        $message = $this->replaceEmailPlaceholders($message, $fields);
        $subject = !empty($formSettings['send_contact_subject']) ? $formSettings['send_contact_subject'] : '';
        $subject = !empty($subject) ? $subject : $standard_subject;
        $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
        if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
          $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
        }
        $sendRes = $this->sendFormEmail($email, $subject, $message, $formSettings);
      }
      $this->setRedirectUrl($redirectLink);
      if (!empty($fields[$password_key])) {
        unset($fields[$password_key]);
      }
      $submit_id = $this->saveFormSubmit($form_id, $fields, $user_id, $contact_id);
      return true;
    }
    return false;
  }

  public function frontendFormSubmitLogin($form_id, $fields)
  {
    global $wpdb;
    $formSettings = $this->getFormSettings($form_id);
    if (!empty($formSettings)) {
      $emailKey = !empty($formSettings['logform_field_email']) ? $formSettings['logform_field_email'] : 'email';
      $passwordKey = !empty($formSettings['logform_field_password']) ? $formSettings['logform_field_password'] : 'password';
      $email = isset($fields[$emailKey]) ? sanitize_email($fields[$emailKey]) : '';
      $password = isset($fields[$passwordKey]) ? $fields[$passwordKey] : '';
      $redirectLink = !empty($formSettings['redirect_link']) ? esc_url_raw($formSettings['redirect_link']) : '';
      if (empty($email) || empty($password)) {
        $this->addError('submit', __('Error! Login Form Fields is not recognized in Data.', 'ppsgen'));
        return false;
      }
      $user = get_user_by('email', $email);
      if (!$user) {
        $this->addError('submit', __('Error! We did not find this email and password combination.', 'ppsgen'));
        return false;
      }
      if (!wp_check_password($password, $user->user_pass, $user->ID)) {
        $this->addError('submit', __('Error! We did not find this email and password combination.', 'ppsgen'));
        return false;
      }
      $submit_id = $this->saveFormSubmit($form_id, [$emailKey => $email], $user->ID, 0);
      wp_set_current_user($user->ID);
      wp_set_auth_cookie($user->ID);
      $this->setRedirectUrl($redirectLink);
      return true;
    }
    return false;
  }

  public function frontendFormSubmitResetPassword($form_id, $fields)
  {
    global $wpdb;
    $formSettings = $this->getFormSettings($form_id);
    if (!empty($formSettings)) {
      $emailKey = !empty($formSettings['resform_field_email']) ? $formSettings['resform_field_email'] : 'email';
      $email = isset($fields[$emailKey]) ? sanitize_email($fields[$emailKey]) : '';
      $redirectLink = !empty($formSettings['redirect_link']) ? esc_url_raw($formSettings['redirect_link']) : '';
      if (empty($email)) {
        $this->addError('submit', __('Error! Email field is required.', 'ppsgen'));
        return false;
      }
      $user = get_user_by('email', $email);
      if (!$user) {
        $this->addError('submit', __('Error! No user found with this email.', 'ppsgen'));
        return false;
      }
      $resetKey = get_password_reset_key($user);
      if (is_wp_error($resetKey)) {
        $this->addError('submit', __('Error! Failed to generate reset key.', 'ppsgen'));
        return false;
      }
      if (!empty($formSettings['send_admin'])) {
        $sendToEmail = !empty($formSettings['send_admin_email']) ? $formSettings['send_admin_email'] : '';
        if (!empty($sendToEmail)) {
          $standard_subject = __('Password Reset Requested for a User Account', 'ppsgen');
          $standard_template = __('This is to inform you that a password reset request was submitted on your site for the following user: [email]', 'ppsgen');
          $html_template = !empty($formSettings['html_send_admin_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_admin_text']) : '';
          $message = !empty($html_template) ? $html_template : $standard_template;
          $message = str_replace('[email]', $email, $message);
          $message = str_replace('[website_name]', get_bloginfo('name'), $message);
          $subject = !empty($formSettings['send_admin_subject']) ? $formSettings['send_admin_subject'] : '';
          $subject = !empty($subject) ? $subject : $standard_subject;
          $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
          if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
            $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
          }
          $sendRes = $this->sendFormEmail($sendToEmail, $subject, $message, $formSettings);
        }
      }
      $resetUrl = network_site_url("wp-login.php?action=rp&key=$resetKey&login=" . rawurlencode($user->user_login), 'login');
      $standard_subject = __('Password Reset Request', 'ppsgen');
      // translators: %s - Reset Password Link
      $standard_template = sprintf(__('To reset your password, click the following link: %s', 'ppsgen'), $resetUrl);
      $html_template = !empty($formSettings['html_send_contact_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_contact_text']) : '';
      $message = !empty($html_template) ? $html_template : $standard_template;
      $message = $this->replaceEmailPlaceholders($message, $fields);
      $message = str_replace('[reset_password_link]', $resetUrl, $message);
      $subject = !empty($formSettings['send_contact_subject']) ? $formSettings['send_contact_subject'] : '';
      $subject = !empty($subject) ? $subject : $standard_subject;
      $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
      if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
        $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
      }
      $sendRes = $this->sendFormEmail($email, $subject, $message, $formSettings);
      if (!empty($redirectLink)) {
        $this->setRedirectUrl($redirectLink);
      }
      $submit_id = $this->saveFormSubmit($form_id, $fields, 0, 0);
      return true;
    }
    return false;
  }

  public function frontendFormSubmitLogout($form_id)
  {
    global $wpdb;
    $formSettings = $this->getFormSettings($form_id);
    if (!empty($formSettings)) {
      $redirectLink = !empty($formSettings['redirect_link']) ? esc_url_raw($formSettings['redirect_link']) : '';
      wp_logout();
      $this->setRedirectUrl($redirectLink);
      return true;
    }
    return false;
  }

  public function frontendFormSubmitSimpleSubmit($form_id, $fields)
  {
    global $wpdb;
    $formSettings = $this->getFormSettings($form_id);
    if (empty($formSettings)) {
      $this->addError('submit', __('Error! Form settings not found.', 'ppsgen'));
      return false;
    }
    $redirectLink = !empty($formSettings['redirect_link']) ? esc_url_raw($formSettings['redirect_link']) : '';
    $user_id = null;
    if (is_user_logged_in()) {
      $current_user = wp_get_current_user();
      $user_id = $current_user->ID;
    }
    $submit_id = $this->saveFormSubmit($form_id, $fields, $user_id, 0);
    if ($submit_id === false) {
      $this->addError('submit', __('Error! Could not save form submission.', 'ppsgen'));
      return false;
    }
    if (!empty($formSettings['send_admin']) && !empty($formSettings['send_admin_email'])) {
      $standard_subject = __('New form submission', 'ppsgen');
      $standard_template = __('New form submission from: [email]', 'ppsgen');
      $html_template = !empty($formSettings['html_send_admin_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_admin_text']) : '';
      $message = !empty($html_template) ? $html_template : $standard_template;
      $message = $this->replaceEmailPlaceholders($message, $fields);
      $subject = !empty($formSettings['send_admin_subject']) ? $formSettings['send_admin_subject'] : $standard_subject;
      $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
      if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
        $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
      }
      $this->sendFormEmail($formSettings['send_admin_email'], $subject, $message, $formSettings);
    }
    $emailFieldKey = !empty($formSettings['send_contact_email_field']) ? $formSettings['send_contact_email_field'] : 'email';
    $email = isset($fields[$emailFieldKey]) ? sanitize_email($fields[$emailFieldKey]) : '';
    if (!empty($formSettings['send_contact']) && !empty($email)) {
      $standard_subject = __('Form submission confirmed', 'ppsgen');
      $standard_template = __('Thank you for submitting the form on [website_name].', 'ppsgen');
      $html_template = !empty($formSettings['html_send_contact_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_contact_text']) : '';
      $message = !empty($html_template) ? $html_template : $standard_template;
      $message = $this->replaceEmailPlaceholders($message, $fields);
      $subject = !empty($formSettings['send_contact_subject']) ? $formSettings['send_contact_subject'] : $standard_subject;
      $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
      if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
        $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
      }
      $this->sendFormEmail($email, $subject, $message, $formSettings);
    }
    $this->setRedirectUrl($redirectLink);
    return true;
  }

  public function frontendFormSubmitCreateContact($form_id, $fields)
  {
    global $wpdb;
    $formSettings = $this->getFormSettings($form_id);
    if (empty($formSettings)) {
      $this->addError('submit', __('Error! Form settings not found.', 'ppsgen'));
      return false;
    }
    $email_key = !empty($formSettings['form_logic_email_field']) ? $formSettings['form_logic_email_field'] : 'email';
    $email = isset($fields[$email_key]) ? sanitize_email($fields[$email_key]) : '';
    $redirectLink = !empty($formSettings['redirect_link']) ? esc_url_raw($formSettings['redirect_link']) : '';
    $form_logic = !empty($formSettings['form_logic']) ? $formSettings['form_logic'] : 'add_submit';
    // Double opt-in: the contact is saved now, its form tags are added once the email is confirmed
    $contact_email_confirmation = !empty($formSettings['contact_email_confirmation']);
    $form_tag_ids = !empty($formSettings['specific_tags']) && is_array($formSettings['specific_tags']) ? $formSettings['specific_tags'] : [];
    $unverified_submit_id = 0;
    $identify_contact_fields = [$email_key];

    if (empty($email) || !is_email($email)) {
      $this->addError('submit', __('Error! Invalid or missing email address.', 'ppsgen'));
      return false;
    }

    $current_user = is_user_logged_in() ? wp_get_current_user() : null;
    $user_id = $current_user ? (int) $current_user->ID : 0;
    $is_same_email = $current_user && strtolower($email) === strtolower(sanitize_email($current_user->user_email));

    $contact_data = [
      'fields' => $fields,
      'tag_ids' => !empty($formSettings['specific_tags']) ? $formSettings['specific_tags'] : [],
      'verified' => 0,
      'user_id' => 0,
    ];

    $auto_verify = false;
    if ($user_id && $is_same_email) {
      $auto_verify = true;
      $contact_data['verified'] = 1;
      $contact_data['user_id'] = $user_id;
    } else {
      $contact_data['verified'] = 0;
      $contact_data['user_id'] = 0;
    }

    $defer_tags = $contact_email_confirmation && !$auto_verify;
    $contact_ids = $this->getContactModel()->getContactIdsByFields($identify_contact_fields, $fields);

    $user_id = $is_same_email ? $user_id : 0;

    // If contact found
    if (!empty($contact_ids)) {
      $contact_id = $contact_ids[0];
      $contact_info = $this->getContactModel()->getContactById($contact_id);
      // Confirmed earlier: their tags apply now; only changes to their details still need the link
      $already_confirmed = !empty($contact_info['verified']);
      if ($already_confirmed) {
        $defer_tags = false;
      }
      if (!empty($form_tag_ids) && !$defer_tags) {
        $this->getContactModel()
          ->addContactTags($formSettings['specific_tags'], (int) $contact_id, 'form_submit');
      }
      // If updates fields of found contact
      if ($form_logic == 'change_contact_field') {
        // If updates fields of found contact and user is verified
        if ($auto_verify) {
          $update_fields = !empty($formSettings['form_logic_update_fields']) ? $formSettings['form_logic_update_fields'] : array_keys($fields);
          $filtered_fields = array_intersect_key($fields, array_flip($update_fields));
          if (!empty($filtered_fields)) {
            $this->getContactModel()
              ->saveContactFieldValues([
                  'fields' => $filtered_fields,
                  'contact_id' => $contact_id,
                  'user_id' => $user_id,
                  'form_id' => $form_id,
                  'initiator' => 'form_submit',
                ]);
          }
          $this->saveFormSubmit($form_id, $fields, $user_id, $contact_id);
        } else {
          // If updates fields of found contact and user is unverified
          $fields['unverified_submit'] = '1';
          $unverified_submit_id = $this->saveFormSubmit($form_id, $fields, 0, $contact_id);
        }
      }
      // If adding only submit to Form/Contact
      if ($form_logic == 'add_submit') {
        // If adding only submit to Form/Contact and user is verified
        if ($auto_verify) {
          $this->saveFormSubmit($form_id, $fields, $user_id, $contact_id);
        } else {
          // If adding only submit to Form/Contact and user is unverified
          $fields['unverified_submit'] = '1';
          $unverified_submit_id = $this->saveFormSubmit($form_id, $fields, 0, $contact_id);
        }
      }
    }

    // If contact not found
    if (empty($contact_ids)) {
      // If update contact fields but contact not found, creating new contact
      if ($form_logic == 'change_contact_field') {
        // If user is verified create new contact
        if ($auto_verify) {
          $contact_data['fields'] = $fields;
          $contact_data['user_id'] = $user_id;
          $contact_data['verified'] = 1;
          $contact_data['tag_ids'] = !empty($formSettings['specific_tags']) ? $formSettings['specific_tags'] : [];
          $contact_id = $this->getContactModel()->createContactBySubmit($contact_data);
          $this->saveFormSubmit($form_id, $fields, $user_id, $contact_id);
        } else {
          // If user is unverified create new contact with verify = 0 and submit with unverified_submit = 1
          $contact_data['fields'] = $fields;
          $contact_data['user_id'] = $user_id;
          $contact_data['verified'] = 0;
          $contact_data['tag_ids'] = $defer_tags ? [] : $form_tag_ids;
          $contact_id = $this->getContactModel()->createContactBySubmit($contact_data);
          // If create new contact and user is unverified, adding submit with flag unverified_submit = 1
          $fields['unverified_submit'] = '1';
          $unverified_submit_id = $this->saveFormSubmit($form_id, $fields, 0, $contact_id);
        }
      }
      // If create new submit to form/contact if contact not created
      if ($form_logic == 'add_submit') {
        // Create new contact if user is verified
        if ($auto_verify) {
          $contact_data['fields'] = $fields;
          $contact_data['user_id'] = $user_id;
          $contact_data['verified'] = 1;
          $contact_data['tag_ids'] = !empty($formSettings['specific_tags']) ? $formSettings['specific_tags'] : [];
          $contact_id = $this->getContactModel()->createContactBySubmit($contact_data);
          $this->saveFormSubmit($form_id, $fields, $user_id, $contact_id);
        } else {
          // If user is unverfied create new contact/submit with unverified flags
          $contact_data['fields'] = $fields;
          $contact_data['user_id'] = $user_id;
          $contact_data['verified'] = 0;
          $contact_data['tag_ids'] = $defer_tags ? [] : $form_tag_ids;
          $contact_id = $this->getContactModel()->createContactBySubmit($contact_data);
          // If user is unverfied create new submit with unverified flag
          $fields['unverified_submit'] = '1';
          $unverified_submit_id = $this->saveFormSubmit($form_id, $fields, 0, $contact_id);
        }
      }
    }

    // Double opt-in: one confirmation email, whatever the form does with the contact
    if ($contact_email_confirmation && !$auto_verify && !empty($unverified_submit_id) && !empty($contact_id) && !(!empty($already_confirmed) && $form_logic == 'add_submit')) {
      $update_fields = [];
      if ($form_logic == 'change_contact_field') {
        $update_fields = !empty($formSettings['form_logic_update_fields']) ? $formSettings['form_logic_update_fields'] : array_keys($fields);
        $update_fields = array_intersect_key($fields, array_flip($update_fields));
        unset($update_fields['unverified_submit']);
      }
      $this->sendContactEmailConfirmation([
        'email' => $email,
        'form_id' => $form_id,
        'submit_id' => $unverified_submit_id,
        'contact_id' => $contact_id,
        'fields' => $fields,
        'update_fields' => $update_fields,
        'tag_ids' => $form_tag_ids,
        'contact_email_confirmation_subject' => $formSettings['contact_email_confirmation_subject'] ?? '',
        'html_contact_email_confirmation_text' => $formSettings['html_contact_email_confirmation_text'] ?? '',
        'template_id' => !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0,
      ]);
    }

    // Admin email notice
    if (!empty($formSettings['send_admin']) && !empty($formSettings['send_admin_email'])) {
      $subject = !empty($formSettings['send_admin_subject']) ? $formSettings['send_admin_subject'] : __('New contact form submission', 'ppsgen');
      $message = !empty($formSettings['html_send_admin_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_admin_text']) : __('New contact form submission: [email]', 'ppsgen');
      $message = $this->replaceEmailPlaceholders($message, $fields);
      $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
      if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
        $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
      }
      $this->sendFormEmail($formSettings['send_admin_email'], $subject, $message, $formSettings);
    }

    // Contact email notice
    // if ($auto_verify) {
    if (!empty($formSettings['send_contact']) && !empty($email)) {
      $subject = !empty($formSettings['send_contact_subject']) ? $formSettings['send_contact_subject'] : __('Thank you for your submission', 'ppsgen');
      $message = !empty($formSettings['html_send_contact_text']) ? $this->getFrame()->core('helper')->wpKses($formSettings['html_send_contact_text']) : __('Your form has been successfully submitted.', 'ppsgen');
      $message = $this->replaceEmailPlaceholders($message, $fields);
      $template_id = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
      if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
        $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
      }
      $this->sendFormEmail($email, $subject, $message, $formSettings);
    }
    // }

    // Form Redirect after Success Submit
    $this->setRedirectUrl($redirectLink);
    return true;
  }

  public function getAllFormsList($asList = false, $asCol = false)
  {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ppsgen_forms WHERE 1 = 1", ARRAY_A);
    if ($asList) {
      return array_column($rows, 'name', 'id');
    } elseif ($asCol) {
      return array_column($rows, 'id');
    }
    return $rows;
  }

  public function saveFormSubmit($form_id, $fields, $wp_user_id = null, $contact_id = null)
  {
    global $wpdb;
    $ip = $this->getFrame()->core('request')->getServerVar('REMOTE_ADDR');
    $fields_values = !empty($fields) ? $this->_prepareValueToStore($fields, 'json') : null;
    if ($fields_values === false) {
      return false;
    }
    $result = $wpdb->insert(
      "{$wpdb->prefix}ppsgen_form_submits",
      [
        'form_id' => $form_id,
        'wp_user_id' => $wp_user_id,
        'contact_id' => $contact_id,
        'ip' => $ip,
        'fields_values' => $fields_values,
        'created_date' => current_time('mysql'),
      ],
      ['%d', '%d', '%d', '%s', '%s', '%s'],
    );
    // The browser that submitted is now a known contact for "Who sees it"
    if ($result !== false && !empty($contact_id) && function_exists('ppsgen_remember_visitor')) {
      ppsgen_remember_visitor((int) $contact_id);
    }
    return $result !== false ? $wpdb->insert_id : false;
  }

  public function frontendFormSubmit($form_id, $fields, $form_type)
  {
    if ($form_type == 'submit') {
      $res = $this->frontendFormSubmitSimpleSubmit($form_id, $fields);
    }
    if ($form_type == 'create_contact') {
      $res = $this->frontendFormSubmitCreateContact($form_id, $fields);
    }
    if ($form_type == 'login') {
      $res = $this->frontendFormSubmitLogin($form_id, $fields);
    }
    if ($form_type == 'logout') {
      $res = $this->frontendFormSubmitLogout($form_id);
    }
    if ($form_type == 'reset_password') {
      $res = $this->frontendFormSubmitResetPassword($form_id, $fields);
    }
    if ($form_type == 'signup') {
      $res = $this->frontendFormSubmitSignup($form_id, $fields);
    }
    return $res;
  }

  public function getFormType($form_id)
  {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT form_type FROM {$wpdb->prefix}ppsgen_forms WHERE id = %d", $form_id));
  }

  public function statsAddSubmitError($form_id)
  {
    global $wpdb;
    $stats = $this->getOption('submit_error', 'stats', $form_id, 'form');
    $newErrorCounter = !empty($stats) ? (int) $stats + 1 : 1;
    return $this->updateOption('submit_error', 'stats', $newErrorCounter, $form_id, 'form', 'json');
  }

  public function statsAddSubmit($form_id)
  {
    global $wpdb;
    $stats = $this->getOption('submit_total', 'stats', $form_id, 'form');
    $newTotalCounter = !empty($stats) ? (int) $stats + 1 : 1;
    return $this->updateOption('submit_total', 'stats', $newTotalCounter, $form_id, 'form', 'json');
  }

  public function statsAddSubmitSuccess($form_id)
  {
    global $wpdb;
    $stats = $this->getOption('submit_success', 'stats', $form_id, 'form');
    $newSuccessCounter = !empty($stats) ? (int) $stats + 1 : 1;
    return $this->updateOption('submit_success', 'stats', $newSuccessCounter, $form_id, 'form', 'json');
  }

  public function statsAddView($form_id)
  {
    global $wpdb;
    $stats = $this->getOption('form_view', 'stats', $form_id, 'form');
    $newViewCounter = !empty($stats) ? (int) $stats + 1 : 1;
    return $this->updateOption('form_view', 'stats', $newViewCounter, $form_id, 'form', 'json');
  }

  public function formIsEnabled($form_id)
  {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT active FROM {$wpdb->prefix}ppsgen_forms WHERE active = 1 AND id = %d", $form_id));
  }

  public function getFormSubmits($form_id)
  {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_submits WHERE form_id = %d", $form_id), ARRAY_A);
  }

  public function getContactFormSubmits($contact_id)
  {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_submits WHERE contact_id = %d", $contact_id), ARRAY_A);
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
          'submits' => 0,
          'unique_contacts' => 0,
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
          'submits' => 0,
          'unique_contacts' => 0,
        ];
      }
      return $buckets;
    }
    for ($i = $range - 1; $i >= 0; $i--) {
      $ts = strtotime("-{$i} day", $today);
      $key = wp_date('Y-m-d', $ts);
      $buckets[$key] = [
        'label' => wp_date('d M', $ts),
        'submits' => 0,
        'unique_contacts' => 0,
      ];
    }
    return $buckets;
  }

  public function getFormSubmitTrend(int $form_id, string $groupBy = 'day', int $range = 30): array
  {
    global $wpdb;
    $form_id = (int) $form_id;
    if (empty($form_id)) {
      return [];
    }
    $groupBy = in_array($groupBy, ['day', 'week', 'month'], true) ? $groupBy : 'day';
    $range = in_array($range, [7, 30, 90, 365], true) ? $range : 30;
    $buckets = $this->getTrendBuckets($groupBy, $groupBy === 'month' ? min(12, max(3, (int) ceil($range / 30))) : ($groupBy === 'week' ? min(16, max(4, (int) ceil($range / 7))) : $range));
    $keys = array_keys($buckets);
    $dateFrom = reset($keys);

    if ($groupBy === 'month') {
      $bucketSql = "DATE_FORMAT(created_date, '%Y-%m-01')";
    } elseif ($groupBy === 'week') {
      $bucketSql = "DATE_SUB(DATE(created_date), INTERVAL WEEKDAY(created_date) DAY)";
    } else {
      $bucketSql = 'DATE(created_date)';
    }

    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT 
          {$bucketSql} AS bucket_key,
          COUNT(*) AS submits,
          COUNT(DISTINCT CASE 
            WHEN contact_id IS NOT NULL AND contact_id > 0 THEN CONCAT('c:', contact_id)
            WHEN wp_user_id IS NOT NULL AND wp_user_id > 0 THEN CONCAT('u:', wp_user_id)
            ELSE CONCAT('ip:', ip)
          END) AS unique_contacts
        FROM {$wpdb->prefix}ppsgen_form_submits
        WHERE form_id = %d AND created_date >= %s
        GROUP BY bucket_key
        ORDER BY bucket_key ASC",
        $form_id,
        $dateFrom . ' 00:00:00',
      ),
      ARRAY_A,
    );

    foreach ($rows as $row) {
      $bucketKey = (string) ($row['bucket_key'] ?? '');
      if (!isset($buckets[$bucketKey])) {
        continue;
      }
      $buckets[$bucketKey]['submits'] = (int) $row['submits'];
      $buckets[$bucketKey]['unique_contacts'] = (int) $row['unique_contacts'];
    }

    return array_values($buckets);
  }

  // Play review "Send me a test enquiry": the form's real owner alert and thank-you email, with sample answers,
  // sent to the site owner. Nothing is saved: no contact, no submission, no tags, no automation.
  public function sendTestEnquiryEmails(int $formId, string $to): array
  {
    $formSettings = $this->getFormSettings($formId);
    if (empty($formSettings) || !is_email($to)) {
      return ['ok' => false, 'message' => __('The form or your email address was not found.', 'ppsgen')];
    }
    $sample = ['email' => $to, 'first_name' => __('Alex', 'ppsgen'), 'last_name' => __('Sample', 'ppsgen'), 'phone' => '555 0100'];
    foreach ((array) $this->getFormFields($formId) as $field) {
      $slug = (string) ($field['field_slug'] ?? $field['slug'] ?? '');
      if ($slug !== '' && !isset($sample[$slug])) {
        $sample[$slug] = __('This is a test enquiry sent from the play review.', 'ppsgen');
      }
    }
    $render = function (string $html, string $fallback) use ($formSettings, $sample) {
      $message = $html !== '' ? $this->getFrame()->core('helper')->wpKses($html) : $fallback;
      $message = $this->replaceEmailPlaceholders($message, $sample);
      $templateId = !empty($formSettings['template_id']) ? $formSettings['template_id'] : 0;
      if (!empty($templateId) && $this->getFrame()->getModule('eca')) {
        $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($templateId, $message);
      }
      return $message;
    };
    $sent = [];
    if (!empty($formSettings['send_admin']) && !empty($formSettings['send_admin_email'])) {
      $subject = '[' . __('Test', 'ppsgen') . '] ' . (!empty($formSettings['send_admin_subject']) ? $formSettings['send_admin_subject'] : __('New form submission', 'ppsgen'));
      if ($this->sendFormEmail($formSettings['send_admin_email'], $subject, $render((string) ($formSettings['html_send_admin_text'] ?? ''), __('New form submission from: [email]', 'ppsgen')), $formSettings)) {
        $sent[] = sprintf(__('the new enquiry alert to %s', 'ppsgen'), $formSettings['send_admin_email']);
      }
    }
    if (!empty($formSettings['send_contact'])) {
      $subject = '[' . __('Test', 'ppsgen') . '] ' . (!empty($formSettings['send_contact_subject']) ? $formSettings['send_contact_subject'] : __('Thank you for your submission', 'ppsgen'));
      if ($this->sendFormEmail($to, $subject, $render((string) ($formSettings['html_send_contact_text'] ?? ''), __('Your form has been successfully submitted.', 'ppsgen')), $formSettings)) {
        $sent[] = sprintf(__('the thank-you email to %s', 'ppsgen'), $to);
      }
    }
    if (empty($sent)) {
      return ['ok' => false, 'message' => __('Nothing was sent. This form sends no emails, or sending failed: check Settings › Email sending.', 'ppsgen')];
    }
    return ['ok' => true, 'message' => sprintf(__('Sent %s. Check your inbox (and spam folder).', 'ppsgen'), implode(__(' and ', 'ppsgen'), $sent))];
  }

  public function replaceEmailPlaceholders($message, $fields)
  {
    // Kept for the subject of the email being built (see sendFormEmail)
    $this->emailPlaceholderFields = is_array($fields) ? $fields : [];
    foreach ($fields as $key => $value) {
      if (!is_scalar($value)) {
        continue;
      }
      $message = str_replace("[$key]", esc_html($value), $message);
    }
    $message = str_replace('[website_name]', esc_html(get_bloginfo('name')), $message);
    // The site's address, for links and buttons in form emails ("back to the shop")
    $message = str_replace('[website_url]', esc_url(home_url('/')), $message);
    return $message;
  }

  /** The submission's fields for the email subject: the last ones replaceEmailPlaceholders() was given */
  protected $emailPlaceholderFields = [];

  protected function replaceSubjectPlaceholders(string $subject): string
  {
    if (strpos($subject, '[') === false) {
      return $subject;
    }
    foreach ($this->emailPlaceholderFields as $key => $value) {
      if (is_scalar($value)) {
        $subject = str_replace("[$key]", wp_strip_all_tags((string) $value), $subject);
      }
    }
    $subject = str_replace('[website_name]', wp_strip_all_tags(get_bloginfo('name')), $subject);
    $subject = str_replace('[website_url]', home_url('/'), $subject);
    // A field the visitor left empty: drop its placeholder rather than send "[form_guests]"
    $subject = preg_replace('/\[[a-z0-9_]+\]/i', '', $subject);
    // (ASCII only: trim() works on bytes and would cut multibyte characters such as emoji)
    return trim(preg_replace('/\s{2,}/', ' ', $subject), " ,:-");
  }

  public function duplicateForm($obj_id)
  {
    global $wpdb;
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_forms WHERE id = %d", $obj_id), ARRAY_A);
    $form_id_new = null;
    $now = current_time('mysql');
    if (!empty($form)) {
      $form_data = [
        'name' => $form['name'] . ' Copy',
        'form_type' => $form['form_type'] ?? 'submit',
        'settings' => $form['settings'] ?? '{}',
        'design' => $form['design'] ?? '{}',
        'active' => $form['active'] ?? 0,
        'created_at' => $now,
        'updated_at' => $now,
      ];
      $wpdb->insert("{$wpdb->prefix}ppsgen_forms", $form_data);
      $form_id_new = $wpdb->insert_id;
      $this->setStatus('success');
      if (!$form_id_new) {
        return false;
      }
      $form_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_fields WHERE form_id = %d", $obj_id), ARRAY_A);
      if (!empty($form_fields) && is_array($form_fields)) {
        foreach ($form_fields as $field) {
          $settings = json_decode($field['settings'] ?? '{}', true);
          $slug = $settings['field_slug'] ?? '';
          $field_data = [
            'form_id' => $form_id_new,
            'cf_id' => $field['cf_id'] ?? '',
            'fkey' => $field['fkey'] ?? '',
            'settings' => $field['settings'] ?? '{}',
            'options' => $field['options'] ?? '[]',
            'order_index' => $field['order_index'] ?? 0,
            'created_date' => $now,
            'updated_date' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_form_fields", $field_data);
        }
      }
      $table_name = $wpdb->prefix . 'ppsgen_form_conditional_logic';
      $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
      if ($table_exists === $table_name) {
        $conditional_logic = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_conditional_logic WHERE form_id = %d", $obj_id), ARRAY_A);
        if (!empty($conditional_logic) && is_array($conditional_logic)) {
          $conditional_logic_data = [
            'form_id' => $form_id_new,
            'logic_data' => $conditional_logic['logic_data'] ?? '[]',
            'hidden_fields' => $conditional_logic['hidden_fields'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_form_conditional_logic", $conditional_logic_data);
        }
      }
    }
    return $form_id_new;
  }

  // The PRO contact model when PRO is active (richer field types), the Free one otherwise
  protected function getContactModel()
  {
    $module = $this->getFrame()->getModule('contactExt') ?: $this->getFrame()->getModule('contact');
    return $module->getModel();
  }

  protected function ensureTempUsersTable(): void
  {
    global $wpdb;
    $installer = $this->getFrame()->core('installer');
    if ($installer->exist('ppsgen_temp_users')) {
      return;
    }
    $tableName = $wpdb->prefix . 'ppsgen_temp_users';
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE $tableName (
      `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `form_id` BIGINT(20) NOT NULL DEFAULT 0,
      `submit_id` BIGINT(20) NOT NULL DEFAULT 0,
      `contact_id` BIGINT(20) NOT NULL DEFAULT 0,
      `data` TEXT NOT NULL,
      `activation_code` VARCHAR(255) NOT NULL,
      `status` ENUM('pending', 'activated', 'expired') NOT NULL DEFAULT 'pending',
      `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `date_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX `activation_code` (`activation_code`)
    ) $charset;");
  }

  // Double opt-in (Free): email with a one-time link; the form tags wait in the pending row until it is clicked
  public function sendContactEmailConfirmation($data)
  {
    global $wpdb;
    $this->ensureTempUsersTable();
    $activation_code = wp_generate_password(20, false);
    $wpdb->insert($wpdb->prefix . 'ppsgen_temp_users', [
      'form_id' => (int) $data['form_id'],
      'submit_id' => (int) $data['submit_id'],
      'contact_id' => (int) $data['contact_id'],
      'data' => (string) wp_json_encode([
        'update_fields' => !empty($data['update_fields']) ? $data['update_fields'] : [],
        'tag_ids' => array_values(array_map('intval', (array) ($data['tag_ids'] ?? []))),
      ]),
      'activation_code' => $activation_code,
      'status' => 'pending',
      'date_created' => current_time('mysql'),
    ]);
    $activation_link = add_query_arg(['ppsgen_action' => 'verify_submit', 'code' => $activation_code, 'tuid' => (int) $wpdb->insert_id], home_url('/'));
    $subject = !empty($data['contact_email_confirmation_subject']) ? $data['contact_email_confirmation_subject'] : __('Please confirm your email', 'ppsgen');
    $message = !empty($data['html_contact_email_confirmation_text'])
      ? $this->getFrame()->core('helper')->wpKses($data['html_contact_email_confirmation_text'])
      : __('Hi,<br><br>Thanks for signing up with [website_name]. Please confirm your email address by clicking the link below:<br><br><a href="[activation_link]">Confirm my email</a>', 'ppsgen');
    if (strpos($message, '[activation_link]') === false) {
      $message .= '<p><a href="[activation_link]">[activation_link]</a></p>';
    }
    $fields = (array) ($data['fields'] ?? []);
    $fields['user_email'] = $data['email'];
    $fields['website_name'] = get_bloginfo('name');
    $fields['activation_link'] = $activation_link;
    $message = $this->replaceEmailPlaceholders($message, $fields);
    $template_id = !empty($data['template_id']) ? $data['template_id'] : 0;
    if (!empty($template_id) && !empty($this->getFrame()->getModule('eca'))) {
      $message = $this->getFrame()->getModule('eca')->getView()->renderTemplateById($template_id, $message);
    }
    $formSettings = $this->getFormSettings((int) ($data['form_id'] ?? 0));
    return $this->sendFormEmail($data['email'], $subject, $message, is_array($formSettings) ? $formSettings : []);
  }

  // The confirmation link: verify the contact and the submit, apply the deferred tags and field updates
  public function verifyContactByLink()
  {
    global $wpdb;
    $request = $this->getFrame()->core('request');
    $temp_user_id = (int) $request->getVar('tuid', 'get');
    $activation_code = sanitize_text_field((string) $request->getVar('code', 'get'));
    $this->ensureTempUsersTable();
    $temp_user = $temp_user_id > 0 && $activation_code !== ''
      ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_temp_users WHERE id = %d AND activation_code = %s AND status = 'pending'", $temp_user_id, $activation_code), ARRAY_A)
      : null;
    if (!$temp_user) {
      $this->showConfirmationMessage(__('This confirmation link is invalid or has already been used.', 'ppsgen'), 'error');
      return false;
    }
    $contact_id = (int) $temp_user['contact_id'];
    $wpdb->update($wpdb->prefix . 'ppsgen_contacts', ['verified' => 1], ['id' => $contact_id]);
    $submit_id = (int) $temp_user['submit_id'];
    $submit_fields = $this->_prepareValueFromStore($wpdb->get_var($wpdb->prepare("SELECT fields_values FROM {$wpdb->prefix}ppsgen_form_submits WHERE id = %d", $submit_id)), 'json') ?: [];
    unset($submit_fields['unverified_submit']);
    $wpdb->update($wpdb->prefix . 'ppsgen_form_submits', ['fields_values' => $this->_prepareValueToStore($submit_fields, 'json')], ['id' => $submit_id]);
    $wpdb->update($wpdb->prefix . 'ppsgen_temp_users', ['status' => 'activated'], ['id' => $temp_user_id]);

    $pending = json_decode((string) $temp_user['data'], true);
    // Rows written by older versions only stored the fields to update
    $update_fields = is_array($pending) && array_key_exists('update_fields', $pending) ? (array) $pending['update_fields'] : (is_array($pending) ? $pending : []);
    $tag_ids = is_array($pending) && !empty($pending['tag_ids']) ? array_map('intval', (array) $pending['tag_ids']) : [];
    $formSettings = $this->getFormSettings((int) $temp_user['form_id']);
    if (!empty($formSettings['form_logic']) && $formSettings['form_logic'] == 'change_contact_field' && !empty($update_fields)) {
      $this->getContactModel()->saveContactFieldValues(['contact_id' => $contact_id, 'fields' => $update_fields, 'initiator' => 'form_submit']);
    }
    if (!empty($tag_ids)) {
      $this->getContactModel()->addContactTags($tag_ids, $contact_id, 'form_submit');
    }
    if (function_exists('ppsgen_remember_visitor')) {
      ppsgen_remember_visitor($contact_id);
    }
    $this->showConfirmationMessage(__('Thanks, your email is confirmed.', 'ppsgen'), 'success');
    return true;
  }

  protected function showConfirmationMessage(string $message, string $type = 'error'): void
  {
    $color = $type === 'success' ? '#2e7d32' : '#d32f2f';
    $html = '<div class="ppsgen-confirmation-popup-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99998;"></div>'
      . '<div class="ppsgen-confirmation-popup" role="status" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px 30px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.2);z-index:99999;width:400px;max-width:90%;text-align:center;font:16px/1.5 -apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;color:' . esc_attr($color) . ';">' . esc_html($message) . '</div>'
      . '<script>setTimeout(function(){document.querySelectorAll(".ppsgen-confirmation-popup,.ppsgen-confirmation-popup-overlay").forEach(function(el){el.remove();});},4000);</script>';
    add_action('wp_footer', static function () use ($html) {
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $html;
    });
  }
}
