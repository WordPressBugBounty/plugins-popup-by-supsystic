<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactTagSubmoduleControllerPpsgen extends BaseControllerPpsgen
{
  public function editContactTag()
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
    $res = $model->editContactTag($data, $obj_id);
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

  public function deleteContactTag()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteContactTag($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Contact Tag is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteContactTagList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteContactTag((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Contact Tags List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }
}
