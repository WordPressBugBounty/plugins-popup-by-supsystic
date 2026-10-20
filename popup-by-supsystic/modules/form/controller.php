<?php

if (!defined('ABSPATH')) {
  exit();
}

class FormControllerPpsgen extends BaseControllerPpsgen
{
  public function editForm()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();

    $obj_id = $this->getFrame()->core('request')->getVar('obj_id', 'post');
    ppsgen_save_locked_pro_item_status('form', $obj_id, $data['form']['active'] ?? null);
    $model = $this->getModel();
    $res = $model->editForm($data, $obj_id);
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

  public function editFormOptions()
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

  public function deleteForm()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteForm($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Form is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function duplicateForm()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj_id = $this->getFrame()->core('request')->getVar('obj', 'post');
    ppsgen_reject_locked_pro_item('form', $obj_id);
    $model = $this->getModel();
    $res = $model->duplicateForm($obj_id);
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

  public function deleteFormSubmit()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteFormSubmit($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Form Submit is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function resetFormStats()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = (int) $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj) && $this->getModel()->resetFormStats($obj)) {
      wp_send_json_success(['message' => __('Form statistics were reset.', 'ppsgen')]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteFormList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteForm((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Form List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function frontendFormSubmit()
  {
    $form_id = $this->getFrame()->core('request')->getVar('form_id', 'post');
    $form_id = !empty($form_id) ? absint($form_id) : 0;
    $nonce = $this->getFrame()->core('request')->getVar('nonce_code', 'post');
    $nonce = !empty($nonce) ? $nonce : '';
    if (!wp_verify_nonce($nonce, 'ppsgenFormSubmit' . $form_id)) {
      wp_send_json_error(['message' => esc_html__('Access restricted', 'ppsgen')]);
    }
    $data = $this->getFrame()->core('request')->getVar('ppsgen_form', 'post');
    $formIsEnabled = $this->getModel()->formIsEnabled($form_id);
    if (empty($data) || empty($formIsEnabled)) {
      wp_send_json_error();
      wp_die();
    }
    $this->getModel()->statsAddSubmit($form_id);
    $validator = $this->getFrame()->getCfv();
    $clearedFields = $validator->validateFrontend($data, $form_id);
    if (empty($clearedFields)) {
      $errors = $validator->getErrors();
      $errors['submit'] = esc_html__('Some fields contain errors. Please correct them and then submit the form again.', 'ppsgen');
      $this->getModel()->statsAddSubmitError($form_id);
      wp_send_json_error(['fields_error' => $errors]);
    }
    $model = $this->getModel();
    $formType = $model->getFormType($form_id);
    $res = $model->frontendFormSubmit($form_id, $clearedFields, $formType);
    $errors = $model->getErrors();
    if (empty($res) || !empty($errors)) {
      $this->getModel()->statsAddSubmitError($form_id);
      wp_send_json_error(['fields_error' => $errors]);
    }
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success') {
      $this->getModel()->statsAddSubmitSuccess($form_id);
      $redirectUrl = $model->getRedirectUrl();
      if (!empty($redirectUrl)) {
        wp_send_json_success(['message' => $resModelMsg, 'redirect' => $redirectUrl]);
      } else {
        wp_send_json_success(['message' => $resModelMsg]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function renderPreview()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();
    if (!empty($this->getFrame()->core('request')->getVar('obj_id', 'post'))) {
      $data['form']['obj_id'] = $this->getFrame()->core('request')->getVar('obj_id', 'post');
    }
    $res = $this->getView()->renderPreview($data['form']);
    if (!empty($res)) {
      wp_send_json_success(['preview' => $res['html'], 'css' => $res['css'], 'styles' => $res['styles'], 'scripts' => $res['scripts'], 'inline_scripts' => $res['inline_scripts']]);
    }
    wp_send_json_error();
    wp_die();
  }
}
