<?php

if (!defined('ABSPATH')) {
  exit();
}

class PopupControllerPpsgen extends BaseControllerPpsgen
{
  public function editPopup()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();

    $obj_id = $this->getFrame()->core('request')->getVar('obj_id', 'post');
    ppsgen_save_locked_pro_item_status('popup', $obj_id, $data['popup']['active'] ?? null);
    $model = $this->getModel();
    $res = $model->editPopup($data, $obj_id);
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

  public function duplicatePopup()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj_id = $this->getFrame()->core('request')->getVar('obj', 'post');
    ppsgen_reject_locked_pro_item('popup', $obj_id);
    $model = $this->getModel();
    $res = $model->duplicatePopup($obj_id);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success' && !empty($res)) {
      wp_send_json_success(['message' => $resModelMsg, 'obj_id' => $res]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function editPopupOptions()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    if (empty($data)) {
      wp_send_json_error();
      wp_die();
    }
    $res = $this->getModel()->updateOptions($data, 'options');
    if (!empty($res)) {
      wp_send_json_success(['message' => __('Data is updated!', 'ppsgen')]);
    } else {
      wp_send_json_error();
    }
    wp_die();
  }

  public function deletePopup()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deletePopup($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Popup is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deletePopupSubmit()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deletePopupSubmit($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Popup Submit is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function resetPopupStats()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = (int) $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj) && $this->getModel()->resetPopupStats($obj)) {
      wp_send_json_success(['message' => __('Popup statistics were reset.', 'ppsgen')]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function deletePopupList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deletePopup((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Popup List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function frontendPopupStatsSubmit()
  {
    $request = $this->getFrame()->core('request');
    $popup_id = absint($request->getVar('popup_id', 'post'));
    $nonce = sanitize_text_field($request->getVar('nonce_code', 'post'));
    if (!$popup_id || !wp_verify_nonce($nonce, 'ppsgenPopupSubmit' . $popup_id)) {
      wp_send_json_error(['message' => esc_html__('Access restricted', 'ppsgen')]);
    }
    if (!$this->getModel()->popupIsEnabled((int) $popup_id)) {
      wp_send_json_error(['message' => esc_html__('Access for Popup is restricted', 'ppsgen')]);
    }
    $action_type_raw = $request->getVar('action_type', 'post');
    $action_meta_raw = $request->getVar('action_meta', 'post');
    $action_type = is_string($action_type_raw) ? substr(sanitize_text_field($action_type_raw), 0, 255) : '';
    $action_meta = is_string($action_meta_raw) ? substr(sanitize_text_field($action_meta_raw), 0, 255) : '';
    if (empty($action_type)) {
      wp_send_json_error(['message' => esc_html__('Missing action type', 'ppsgen')]);
    }
    $is_bounce = absint($request->getVar('is_bounce', 'post'));
    $is_unique = absint($request->getVar('is_unique', 'post'));
    $time_on_screen = absint($request->getVar('time_on_screen', 'post'));
    $user_agent_raw = $request->getVar('user_agent', 'post');
    $user_agent = is_string($user_agent_raw) ? substr(sanitize_text_field($user_agent_raw), 0, 30) : '';
    $extra_raw = $request->getVar('extra', 'post');
    $extra = null;
    if (is_array($extra_raw)) {
      $allowed_extra = [];
      if (!empty($extra_raw['utm']) && is_array($extra_raw['utm'])) {
        $allowed_extra['utm'] = [];
        foreach ($extra_raw['utm'] as $key => $val) {
          $allowed_extra['utm'][$key] = substr(sanitize_text_field($val), 0, 100);
        }
      }
      if (!empty($extra_raw['url']) && is_string($extra_raw['url'])) {
        $allowed_extra['url'] = substr(esc_url_raw($extra_raw['url']), 0, 300);
      }
      if (!empty($extra_raw['referer']) && is_string($extra_raw['referer'])) {
        $allowed_extra['referer'] = substr(esc_url_raw($extra_raw['referer']), 0, 300);
      }
      $extra = $allowed_extra;
    }
    $wp_user_id = get_current_user_id();
    $model = $this->getModel();
    $result = $model->savePopupAction($popup_id, $action_type, $action_meta, $wp_user_id, [
      'is_bounce' => $is_bounce,
      'is_unique' => $is_unique,
      'time_on_screen' => $time_on_screen,
      'user_agent' => $user_agent,
      'extra' => $extra,
    ]);
    if ($result) {
      wp_send_json_success(['message' => esc_html__('Saved', 'ppsgen')]);
    } else {
      wp_send_json_error(['message' => esc_html__('Save failed', 'ppsgen')]);
    }
  }

  public function renderPreview()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();
    $res = $this->getView()->renderPreview($data['popup']);
    if (!empty($res)) {
      wp_send_json_success(['preview' => $res['html'], 'css' => $res['css'], 'styles' => $res['styles'], 'scripts' => $res['scripts'], 'inline_scripts' => $res['inline_scripts']]);
    }
    wp_send_json_error();
    wp_die();
  }
}
