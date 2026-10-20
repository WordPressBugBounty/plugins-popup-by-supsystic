<?php

if (!defined('ABSPATH')) {
  exit();
}

class ConditionalLogicSubmoduleModelPpsgen extends BaseModelPpsgen
{
  public function installTables()
  {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $installer = $this->getFrame()->core('installer');
    if (!$installer->exist('ppsgen_form_conditional_logic')) {
      $tableName = $wpdb->prefix . 'ppsgen_form_conditional_logic';
      $sql = "CREATE TABLE $tableName (
        `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `form_id` BIGINT(20) UNSIGNED NOT NULL,
        `logic_data` TEXT NULL,
        `hidden_fields` TEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`form_id`)
      ) $charset;";
      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta($sql);
    }
  }

  public function saveCondtitionalLogic($form_id, $data)
  {
    global $wpdb;
    $data = !empty($data['addons']['conditional_logic']) ? $data['addons']['conditional_logic'] : [];
    if (empty($form_id) || empty($data)) {
      $this->setStatus('error');
      $this->setMessage(__('Form ID is required', 'ppsgen'));
      return false;
    }
    $existing = $this->getFormConditionalLogic($form_id);
    $logic_data = !empty($data['logic_data']) ? $data['logic_data'] : null;
    $hidden_fields = !empty($data['hidden_fields']) ? $this->_prepareValueToStore($data['hidden_fields'], 'json') : null;
    if (!empty($existing)) {
      $res = $wpdb->update(
        "{$wpdb->prefix}ppsgen_form_conditional_logic",
        [
          'logic_data' => $logic_data,
          'hidden_fields' => $hidden_fields,
        ],
        ['form_id' => $form_id],
        ['%s', '%s'],
        ['%d'],
      );
    } else {
      $res = $wpdb->insert(
        "{$wpdb->prefix}ppsgen_form_conditional_logic",
        [
          'form_id' => $form_id,
          'logic_data' => $logic_data,
          'hidden_fields' => $hidden_fields,
        ],
        ['%d', '%s', '%s'],
      );
    }
    if ($res !== false) {
      $this->setStatus('success');
      $this->setMessage(__('Conditional Logic updated', 'ppsgen'));
      return true;
    }
    $this->setStatus('error');
    $this->setMessage(__('Error updating Conditional Logic', 'ppsgen'));
    return false;
  }

  public function getFormConditionalLogic($form_id)
  {
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ppsgen_form_conditional_logic WHERE form_id = %d", $form_id), ARRAY_A);
    if (!empty($row)) {
      $row['logic_data'] = $row['logic_data'];
      $row['hidden_fields'] = $this->_prepareValueFromStore($row['hidden_fields'], 'json');
    }
    return $row;
  }

  public function applyServerSideLogic($form_id, $fields)
  {
    return $fields;
  }
}
