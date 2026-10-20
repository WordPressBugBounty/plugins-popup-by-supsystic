<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactFieldSubmoduleControllerPpsgen extends BaseControllerPpsgen
{
  public function editContactField()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    $obj_id = $this->getFrame()->core('request')->getVar('obj_id', 'post');
    if (empty($data)) {
      wp_send_json_error();
      wp_die();
    }
    $model = $this->getModel();
    $res = $model->editContactField($data, $obj_id);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success') {
      if (!empty($obj_id)) {
        wp_send_json_success(['message' => $resModelMsg]);
      } else {
        wp_send_json_success(['message' => $resModelMsg, 'obj_id' => $res]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteContactField()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteContactField($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Contact Field is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteContactFieldList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteContactField((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Contact Field List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function getContactFieldSettingJson()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $field_slug = $this->getFrame()->core('request')->getVar('field_slug', 'post');
    if (!empty($field_slug)) {
      $res = $this->getModel()->getContactFieldSettingJson($field_slug);
      if (!empty($res)) {
        wp_send_json_success($res);
      }
    }
    wp_send_json_error();
    wp_die();
  }
}
