<?php

if (!defined('ABSPATH')) {
  exit();
}

class EcaControllerPpsgen extends BaseControllerPpsgen
{
  protected function getChainLimitUpsellBannerHtml(): string
  {
    $url = 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=email_campaigns_pro';
    return '
      <div class="ppsgenProOfferNotice ppsgenProOfferNoticeTab">
        <div class="ppsgenProOfferNoticeHero">
          <div class="ppsgenProOfferNoticeEyebrow">' . esc_html__('YOUR FREE AUTOMATION IS RUNNING', 'ppsgen') . '</div>
          <div class="ppsgenProOfferNoticeBadges">
            <span class="ppsgenProOfferNoticeBadge">CRM</span>
            <span class="ppsgenProOfferNoticeBadge">EMAILS</span>
            <span class="ppsgenProOfferNoticeBadge">FORMS</span>
            <span class="ppsgenProOfferNoticeBadge">POPUPS</span>
          </div>
          <h3 class="ppsgenProOfferNoticeTitle">' . esc_html__('Run a second automation with PRO', 'ppsgen') . '</h3>
          <p class="ppsgenProOfferNoticeText">' . esc_html__('Free runs 1 fully working automation at a time. Build as many as you like and switch between them, or run them side by side with PRO: a welcome series next to your enquiry follow-up, with advanced logic and premium templates.', 'ppsgen') . '</p>
          <div class="ppsgenProOfferNoticeCouponLabel">' . esc_html__('YEARLY LICENCE · 1 SITE', 'ppsgen') . '</div>
          <div class="ppsgenProOfferNoticeCoupon"><span><strong>$99</strong> / year</span></div>
          <div class="ppsgenProOfferNoticeSubtext">' . esc_html__('$99 stays $99 when you renew · No contact-based pricing · 30-day money-back guarantee', 'ppsgen') . '</div>
          <div class="ppsgenProOfferNoticeActions">
            <a class="ppsgenProOfferNoticeBtn ppsgenProOfferNoticeBtnPrimary" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">
              <span class="dashicons dashicons-cart"></span>
              ' . esc_html__('Get PRO for $99 a year', 'ppsgen') . '
            </a>
          </div>
        </div>
        <div class="ppsgenProOfferNoticeFeatures">
          <div class="ppsgenProOfferNoticeFeaturesTitle">' . esc_html__('Included in PRO', 'ppsgen') . '</div>
          <ul class="ppsgenProOfferNoticeFeatureList">
            <li>' . esc_html__('Unlimited automation campaigns and advanced loop logic', 'ppsgen') . '</li>
            <li>' . esc_html__('Premium forms, popups and email templates', 'ppsgen') . '</li>
            <li>' . esc_html__('Advanced CRM field types, WooCommerce and WordPress dynamic sources', 'ppsgen') . '</li>
            <li>' . esc_html__('Email verification, GeoLite targeting and internal API support', 'ppsgen') . '</li>
          </ul>
        </div>
      </div>';
  }

  public function editTemplate()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();

    $obj_id = $this->getFrame()->core('request')->getVar('obj_id', 'post');
    ppsgen_reject_locked_pro_item('eca_template', $obj_id);
    $model = $this->getModel();
    $res = $model->editTemplate($data, $obj_id);
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

  public function renderTemplatePreview()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();
    $this->getView()->setLoadForPreview(true);
    $res = $this->getView()->renderTemplateByData($data);
    if (!empty($res)) {
      wp_send_json_success(['preview' => $res]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function renderChainPreview()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $template_id = $this->getFrame()->core('request')->getVar('template_id', 'post');
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();
    $data['template_id'] = !empty($template_id) ? $template_id : 1;
    $this->getView()->setLoadForPreview(true);
    $res = $this->getView()->renderChainPreviewByData($data);
    if (!empty($res)) {
      wp_send_json_success(['preview' => $res]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function editTemplateOptions()
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

  public function editEcaOptions()
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
    if (!empty($data['eca']['eca_tracking_query_prefix']) && function_exists('ppsgen_sanitize_eca_tracking_query_prefix')) {
      $data['eca']['eca_tracking_query_prefix'] = ppsgen_sanitize_eca_tracking_query_prefix($data['eca']['eca_tracking_query_prefix']);
    }
    if (empty($data['eca']['eca_tracking_query_prefix']) && function_exists('ppsgen_get_default_eca_tracking_query_prefix')) {
      $data['eca']['eca_tracking_query_prefix'] = ppsgen_get_default_eca_tracking_query_prefix();
    }
    $res = $this->getModel()->updateOptions($data, 'options');
    if (!empty($res)) {
      wp_send_json_success(['message' => __('Data is updated!', 'ppsgen')]);
    } else {
      wp_send_json_error();
    }
    wp_die();
  }

  public function updateChainStepsOrder()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $steps = $this->getFrame()->core('request')->getVar('steps', 'post');
    if (empty($steps)) {
      wp_send_json_error();
      wp_die();
    }
    foreach ((array) $steps as $step) {
      ppsgen_reject_locked_pro_item('eca_chain', $this->getModel()->getChainIdByStepId($step['id'] ?? 0));
    }
    $res = $this->getModel()->updateChainStepsOrder($steps, 'options');
    if (!empty($res)) {
      wp_send_json_success(['message' => __('Data is updated!', 'ppsgen')]);
    } else {
      wp_send_json_error();
    }
    wp_die();
  }

  public function deleteTemplate()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteTemplate($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Template is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteTemplateList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteTemplate((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Template List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteFlow()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteFlows($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Flow is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteFlowsList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteFlows((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Flow List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteFlowsLogs()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteFlowLog($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Flow Log is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteFlowLogAll()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $res = $this->getModel()->deleteFlowLogAll();
    if ($res === true) {
      wp_send_json_success(['message' => __('Flows Logs is Deleted', 'ppsgen')]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteStepsEventList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteStepsEvent((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Steps Event Log List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteStepsEvent()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteStepsEvent($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Steps Event Log is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteConversionList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      $res = false;
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteConversion((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Conversion List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteConversion()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteConversion($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Conversion is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteFlowsLogsList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteFlowLog((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Flow Logs List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function deleteChainStep()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $step_id = $this->getFrame()->core('request')->getVar('step_id', 'post');
    ppsgen_reject_locked_pro_item('eca_chain', $this->getModel()->getChainIdByStepId($step_id));
    if (!empty($step_id)) {
      $res = $this->getModel()->deleteStep($step_id);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Step is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function addNewChainStep()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $canCreateChainStep = $this->getModel()->canCreateChainStep();
    if (empty($canCreateChainStep)) {
      wp_send_json_error(['message' => __('<b>FREE Plan limit reached 😔</b> <br> Your plan allows 4 Steps (Emails) in 1 Campaign (Sequence) only. <br> Upgrade your plan to create UNLIMITED email steps, sequences and unlock full access. 🚀', 'ppsgen')]);
    }
    $chain_id = $this->getFrame()->core('request')->getVar('chain_id', 'post');
    ppsgen_reject_locked_pro_item('eca_chain', $chain_id);
    if ($this->getModel()->isNewsletter($chain_id) && !empty($this->getModel()->getChainStep($chain_id))) {
      wp_send_json_error(['message' => __('A newsletter is a single email. Create an automation for a sequence of emails.', 'ppsgen')]);
    }
    $step_name = $this->getFrame()->core('request')->getVar('step_name', 'post');
    $step_name = !empty($step_name) ? $step_name : __('Step Name', 'ppsgen');
    if (!empty($chain_id)) {
      $res = $this->getModel()->addNewChainStep($chain_id, $step_name);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Step is Created', 'ppsgen'), 'step_id' => $res]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function saveChainStepFields()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $step_id = $this->getFrame()->core('request')->getVar('step_id', 'post');
    ppsgen_reject_locked_pro_item('eca_chain', $this->getModel()->getChainIdByStepId($step_id));
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();

    $model = $this->getModel();
    $res = $model->saveChainStepFields($step_id, $data);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success') {
      wp_send_json_success(['message' => $resModelMsg]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function unsubscribeContactAction()
  {
    check_ajax_referer('ppsgenAjaxNonceUnsubscribe', 'nonce_code');
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    if (empty($data['hash']) || empty($data['unsubscribe'])) {
      wp_send_json_error(['message' => __('Invalid request', 'ppsgen')]);
    }
    $decodedHash = $this->getModel()->parseUnsubscribeHash($data['hash']);
    if ($decodedHash === false) {
      wp_send_json_error(['message' => __('Invalid Hash', 'ppsgen')]);
    }
    $this->getModel()->unsubscribeContact($decodedHash['contact_id'], $decodedHash['chain_id'], $decodedHash['step_id'] ?? 0);
    wp_send_json_success(['message' => __('You have been successfully unsubscribed!', 'ppsgen')]);
  }

  public function saveChainStepOptions()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $step_id = $this->getFrame()->core('request')->getVar('step_id', 'post');
    ppsgen_reject_locked_pro_item('eca_chain', $this->getModel()->getChainIdByStepId($step_id));
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();
    $model = $this->getModel();
    $res = $model->saveChainStepOptions($step_id, $data);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success') {
      wp_send_json_success(['message' => $resModelMsg]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function saveChainOptions()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $chain_id = $this->getFrame()->core('request')->getVar('chain_id', 'post');
    $data = $this->getFrame()->core('helper')->prepareRequestPayloadData();
    $model = $this->getModel();
    // Free runs 1 automation at a time: switching on a second one is the PRO moment
    if ((string) ($data['chain']['options']['active'] ?? '1') === '1') {
      $conflict = $model->getAutomationConflict((int) $chain_id);
      if (!empty($conflict)) {
        if (function_exists('ppsgen_track_gateway')) {
          ppsgen_track_gateway('second_automation');
        }
        wp_send_json_error([
          'message' => sprintf(ppsgen_automation_limit_text('conflict_save'), $conflict['name']),
          'conflict' => $conflict,
        ]);
      }
    }
    ppsgen_save_locked_pro_item_status('eca_chain', $chain_id, $data['chain']['options']['active'] ?? null);
    $res = $model->saveChainOptions($chain_id, $data);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success') {
      wp_send_json_success(['message' => $resModelMsg]);
    }
    wp_send_json_error();
    wp_die();
  }

    public function getChainStepData()
    {
      check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
      if (!current_user_can('manage_options')) {
        wp_send_json_error();
      }
      $request = $this->getFrame()->core('request');
      $step_id = $request->getVar('step_id', 'post');
      if (empty($step_id)) {
        wp_send_json_error();
      }
      $trendGroup = $request->getVar('step_stats_group', 'post');
      $trendRange = (int) $request->getVar('step_stats_range', 'post');
      $trendGroup = in_array($trendGroup, ['day', 'week', 'month', 'year'], true) ? $trendGroup : 'day';
      $trendRange = in_array($trendRange, [7, 30, 365], true) ? $trendRange : 30;
      $model = $this->getModel();
      $res = $model->getChainStepData($step_id, $trendGroup, $trendRange);
      $resModel = $model->getStatus();
      $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success') {
      wp_send_json_success(['message' => $resModelMsg, 'data' => $res]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function resetChainStepLogs()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $step_id = (int) $this->getFrame()->core('request')->getVar('step_id', 'post');
    if (empty($step_id)) {
      wp_send_json_error();
    }
    $res = $this->getModel()->resetChainStepLogs($step_id);
    if ($res !== false) {
      wp_send_json_success(['message' => __('Step logs reset successfully.', 'ppsgen')]);
    }
    wp_send_json_error(['message' => __('Failed to reset step logs.', 'ppsgen')]);
    wp_die();
  }

  public function resetChainStepEvents()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $step_id = (int) $this->getFrame()->core('request')->getVar('step_id', 'post');
    if (empty($step_id)) {
      wp_send_json_error();
    }
    $res = $this->getModel()->resetChainStepEvents($step_id);
    if ($res !== false) {
      wp_send_json_success(['message' => __('Step events reset successfully.', 'ppsgen')]);
    }
    wp_send_json_error(['message' => __('Failed to reset step events.', 'ppsgen')]);
    wp_die();
  }

  public function resetChainLogs()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $chain_id = (int) $this->getFrame()->core('request')->getVar('chain_id', 'post');
    if (empty($chain_id)) {
      wp_send_json_error();
    }
    $res = $this->getModel()->resetChainLogs($chain_id);
    if ($res !== false) {
      wp_send_json_success(['message' => __('Campaign logs reset successfully.', 'ppsgen')]);
    }
    wp_send_json_error(['message' => __('Failed to reset campaign logs.', 'ppsgen')]);
    wp_die();
  }

  public function resetChainEvents()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $chain_id = (int) $this->getFrame()->core('request')->getVar('chain_id', 'post');
    if (empty($chain_id)) {
      wp_send_json_error();
    }
    $res = $this->getModel()->resetChainEvents($chain_id);
    if ($res !== false) {
      wp_send_json_success(['message' => __('Campaign events reset successfully.', 'ppsgen')]);
    }
    wp_send_json_error(['message' => __('Failed to reset campaign events.', 'ppsgen')]);
    wp_die();
  }

  public function exitChainContactFlow()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $flow_id = (int) $this->getFrame()->core('request')->getVar('flow_id', 'post');
    if (empty($flow_id)) {
      wp_send_json_error(['message' => __('Invalid flow.', 'ppsgen')]);
    }
    $res = $this->getModel()->exitFlowById($flow_id);
    if ($res) {
      wp_send_json_success(['message' => __('Contact flow exited successfully.', 'ppsgen')]);
    }
    wp_send_json_error(['message' => __('Unable to exit this flow.', 'ppsgen')]);
  }

  public function unsubscribeChainContactFlow()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $flow_id = (int) $this->getFrame()->core('request')->getVar('flow_id', 'post');
    if (empty($flow_id)) {
      wp_send_json_error(['message' => __('Invalid flow.', 'ppsgen')]);
    }
    $res = $this->getModel()->unsubscribeFlowById($flow_id);
    if ($res) {
      wp_send_json_success(['message' => __('Contact unsubscribed from flow successfully.', 'ppsgen')]);
    }
    wp_send_json_error(['message' => __('Unable to unsubscribe this flow.', 'ppsgen')]);
  }

  public function deleteChainContactFlow()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $flow_id = (int) $this->getFrame()->core('request')->getVar('flow_id', 'post');
    if (empty($flow_id)) {
      wp_send_json_error(['message' => __('Invalid flow.', 'ppsgen')]);
    }
    $res = $this->getModel()->deleteFlowByIdWithLog($flow_id);
    if ($res) {
      wp_send_json_success(['message' => __('Contact flow deleted successfully.', 'ppsgen')]);
    }
    wp_send_json_error(['message' => __('Unable to delete this flow.', 'ppsgen')]);
  }

  public function createChain()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $canCreateChain = $this->getModel()->canCreateChain();
    if (empty($canCreateChain)) {
      wp_send_json_error([
        'message' => __('Free runs 1 automation at a time. PRO runs as many as you need.', 'ppsgen'),
        'banner_html' => $this->getChainLimitUpsellBannerHtml(),
      ]);
    }
    $chain_name = $this->getFrame()->core('request')->getVar('chain_name', 'post');
    if (!empty($chain_name)) {
      $res = $this->getModel()->createChain($chain_name);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Campaign is Created', 'ppsgen'), 'obj_id' => $res]);
      }
    }
    wp_send_json_error();
    wp_die();
  }
  public function deleteChainList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteChain((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Campaign List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }
  public function deleteChain()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteChain($obj);
      if (!empty($res)) {
      wp_send_json_success(['message' => __('Campaign is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function duplicateChain()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj_id = $this->getFrame()->core('request')->getVar('obj', 'post');
    ppsgen_reject_locked_pro_item('eca_chain', $obj_id);
    $model = $this->getModel();
    $res = $model->duplicateChain($obj_id);
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

  // CRON - Run Flow Main Cron Logic 'ppsgen_cron_eca_runEcaFlowCron'
  public function runEcaFlowCron()
  {
    if (!(defined('DOING_CRON') && DOING_CRON)) {
      return;
    }
    $this->getModel()->runEcaFlowCron();
  }

  // CRON - Apply Chain Conditions to Contacts (Entry/Exit) 'ppsgen_cron_eca_runEcaApplyChainToContactsCron'
  public function runEcaApplyChainToContactsCron()
  {
    if (!(defined('DOING_CRON') && DOING_CRON)) {
      return;
    }
    $this->getModel()->runEcaApplyChainToContactsCron();
  }

  // CRON - Apply Chain Conditions to Contacts (Entry/Exit) 'ppsgen_cron_eca_runEcaSendEmailCron'
  public function runEcaSendEmailCron()
  {
    if (!(defined('DOING_CRON') && DOING_CRON)) {
      return;
    }
    $this->getModel()->runEcaSendEmailCron();
  }

  // CRON - Scan mailbox for bounced emails 'ppsgen_cron_eca_runEcaAutoBounceCron'
  public function runEcaAutoBounceCron()
  {
    if (!(defined('DOING_CRON') && DOING_CRON)) {
      return;
    }
    $this->getModel()->runEcaAutoBounceCron();
  }

  public function applyChainToContacts()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $chainId = (int) $this->getFrame()->core('request')->getVar('chain_id', 'post');
    if ($chainId <= 0) {
      wp_send_json_error();
    }
    $cronChainId = $this->getModel()->getOption('ppsgen_eca_apply_chain_id', 'cron', 0, 'eca');
    if (!empty($cronChainId) && $cronChainId != $chainId) {
      wp_send_json_error(['message' => 'wait_for_other_chain']);
    }
    $cronStatus = $this->getModel()->getOption('ppsgen_eca_apply_chain_status', 'cron', 0, 'eca');
    if (!empty($cronStatus) && $cronStatus == 'processing') {
      wp_send_json_error(['message' => 'already_running']);
    }
    $this->getModel()->updateOption('ppsgen_eca_apply_chain_id', 'cron', $chainId, 0, 'eca', 'json');
    $this->getModel()->updateOption('ppsgen_eca_apply_chain_status', 'cron', 'processing', 0, 'eca', 'json');
    wp_send_json_success();
  }

  public function applyChainToContactsProgress()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $chainId = (int) $this->getFrame()->core('request')->getVar('chain_id', 'post');
    if ($chainId <= 0) {
      wp_send_json_error();
    }
    $cronChainId = $this->getModel()->getOption('ppsgen_eca_apply_chain_id', 'cron', 0, 'eca');
    if (!empty($cronChainId) && $cronChainId != $chainId) {
      wp_send_json_error(['message' => 'wait_for_other_chain']);
    }
    $cronStatus = $this->getModel()->getOption('ppsgen_eca_apply_chain_status', 'cron', 0, 'eca');
    if (!empty($cronStatus) && $cronStatus == 'processing') {
      $progress = $this->getModel()->applyChainGetProgress();
      wp_send_json_success(['status' => $progress['status'], 'total' => $progress['total'], 'processed' => $progress['processed']]);
    }
    if (!empty($cronStatus) && $cronStatus == 'done') {
      wp_send_json_success(['status' => 'done']);
    }
  }

  public function deleteUndeliveredEmails()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    if (empty($this->getFrame()->getModule('dashboard')->getModel()->getBounceMailConfigs())) {
      wp_send_json_error(['message' => __('Bounce email processing requires a mail config that can read the mailbox. PHP mail() does not support bounce tracking.', 'ppsgen')]);
    }
    $res = $this->getModel()->deleteUndeliveredEmails();
    if (!empty($res['error'])) {
      wp_send_json_error(['message' => esc_html($res['error'])]);
    }
    wp_send_json_success(['data' => $res]);
  }

  public function duplicateTemplate()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj_id = $this->getFrame()->core('request')->getVar('obj', 'post');
    ppsgen_reject_locked_pro_item('eca_template', $obj_id);
    $model = $this->getModel();
    $res = $model->duplicateTemplate($obj_id);
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

  //=====================================
  //       NEWSLETTERS
  //=====================================

  protected function checkNewsletterRequest(): int
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $chainId = (int) $this->getFrame()->core('request')->getVar('chain_id', 'post');
    if ($chainId <= 0 || !$this->getModel()->isNewsletter($chainId)) {
      wp_send_json_error(['message' => __('Newsletter not found.', 'ppsgen')]);
    }
    // A frozen PRO newsletter can still be sent, scheduled and tested; only changing it needs a renewal
    return $chainId;
  }

  protected function sendNewsletterResult(array $result): void
  {
    if (!empty($result['ok'])) {
      wp_send_json_success(['message' => $result['message'] ?? '']);
    }
    wp_send_json_error(['message' => $result['message'] ?? __('An error occurred.', 'ppsgen')]);
  }

  // Newsletters never count toward the Free automation limit
  public function createNewsletter()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $name = sanitize_text_field((string) $this->getFrame()->core('request')->getVar('chain_name', 'post'));
    $chainId = $this->getModel()->createNewsletter($name);
    if (empty($chainId)) {
      wp_send_json_error(['message' => __('The newsletter could not be created.', 'ppsgen')]);
    }
    wp_send_json_success(['message' => __('Newsletter is created', 'ppsgen'), 'obj_id' => $chainId]);
  }

  public function sendNewsletterNow()
  {
    $chainId = $this->checkNewsletterRequest();
    $this->sendNewsletterResult($this->getModel()->sendNewsletterNow($chainId));
  }

  public function scheduleNewsletter()
  {
    $chainId = $this->checkNewsletterRequest();
    $sendAt = (string) $this->getFrame()->core('request')->getVar('send_at', 'post');
    // datetime-local value in the site's timezone
    $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $sendAt, wp_timezone());
    if (!$date) {
      wp_send_json_error(['message' => __('Pick a date and time.', 'ppsgen')]);
    }
    $timestamp = strtotime($date->format('Y-m-d H:i:s'));
    $this->sendNewsletterResult($this->getModel()->scheduleNewsletter($chainId, (int) $timestamp));
  }

  public function setNewsletterList()
  {
    $chainId = $this->checkNewsletterRequest();
    ppsgen_reject_locked_pro_item('eca_chain', $chainId);
    $listId = sanitize_key((string) $this->getFrame()->core('request')->getVar('list_id', 'post'));
    $this->sendNewsletterResult($this->getModel()->setNewsletterList($chainId, $listId));
  }

  // Tagging clickers is a PRO step option today; Free sees it as an upgrade in the panel
  public function setNewsletterClickTag()
  {
    $chainId = $this->checkNewsletterRequest();
    ppsgen_reject_locked_pro_item('eca_chain', $chainId);
    $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
    $frozen = function_exists('ppsgen_is_pro_license_frozen') && ppsgen_is_pro_license_frozen();
    if (!$dashboardModel->isProAddonActive() || $frozen) {
      wp_send_json_error(['message' => __('Tagging people who click is a PRO feature.', 'ppsgen')]);
    }
    $this->sendNewsletterResult($this->getModel()->setNewsletterClickTag($chainId, (string) $this->getFrame()->core('request')->getVar('tag', 'post')));
  }

  public function setNewsletterExtras()
  {
    $chainId = $this->checkNewsletterRequest();
    ppsgen_reject_locked_pro_item('eca_chain', $chainId);
    $request = $this->getFrame()->core('request');
    $this->sendNewsletterResult($this->getModel()->setNewsletterExtras(
      $chainId,
      (string) $request->getVar('preview_text', 'post'),
      (string) $request->getVar('reply_to', 'post'),
      !empty($request->getVar('utm', 'post'))
    ));
  }

  public function cancelNewsletterSchedule()
  {
    $chainId = $this->checkNewsletterRequest();
    $this->sendNewsletterResult($this->getModel()->cancelNewsletterSchedule($chainId));
  }

  public function sendNewsletterTest()
  {
    $chainId = $this->checkNewsletterRequest();
    $user = wp_get_current_user();
    $to = !empty($user->user_email) ? (string) $user->user_email : (string) get_bloginfo('admin_email');
    $this->sendNewsletterResult($this->getModel()->sendNewsletterTest($chainId, $to));
  }
}
