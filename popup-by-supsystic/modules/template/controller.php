<?php

if (!defined('ABSPATH')) {
  exit();
}

class TemplateControllerPpsgen extends BaseControllerPpsgen
{
  public function templateAddSubmit()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $request = $this->getFrame()->core('request');
    $template_name = $request->getVar('template_name', 'post');
    $template_type = $request->getVar('template_type', 'post');
    $template_code = $request->getVar('template_code', 'post');
    $model = $this->getModel();
    $templateTypeList = $model->templateTypeList();
    $template_type = !empty($template_type) ? $template_type : '';
    $template_code = !empty($template_code) ? $template_code : '';
    if (!array_key_exists($template_type, $templateTypeList)) {
      wp_send_json_error(['message' => esc_html__('Undefined template type', 'ppsgen')]);
    }
    $template_name = !empty($template_name) ? $template_name : 'New ' . $template_type . date('d.m.Y H:i:s');
    $model->templateAddSubmit($template_code, $template_type, $template_name);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error([
        'message' => $resModelMsg,
        'show_pro_popup' => $model->getShowProPopup(),
      ]);
    }
    if ($resModel == 'success') {
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
}
