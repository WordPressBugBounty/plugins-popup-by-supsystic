<?php

if (!defined('ABSPATH')) {
  exit();
}

class DashboardControllerPpsgen extends BaseControllerPpsgen
{
  public function editDashboardOptions()
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

  public function activateModule()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $res = $this->getFrame()->useModule('dashboard', '', 'model', 'activateModule', $this->getFrame()->core('request')->getVar('module'));
    if (!empty($res)) {
      wp_send_json_success(['message' => __('Module is activated!', 'ppsgen')]);
    } else {
      wp_send_json_error();
    }
    wp_die();
  }

  public function deactivateModule()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $res = $this->getFrame()->useModule('dashboard', '', 'model', 'deactivateModule', $this->getFrame()->core('request')->getVar('module'));
    if (!empty($res)) {
      wp_send_json_success(['message' => __('Module is deactivated!', 'ppsgen')]);
    } else {
      wp_send_json_error();
    }
    wp_die();
  }

  public function toggleLegacyMode()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'ppsgen')]);
    }
    if (!function_exists('ppsgen_is_legacy_user') || !ppsgen_is_legacy_user()) {
      wp_send_json_error(['message' => __('Legacy mode is not available for this site.', 'ppsgen')]);
    }
    $active = (int) $this->getFrame()->core('request')->getVar('legacy_active', 'post') === 1;
    ppsgen_set_legacy_active($active);
    wp_send_json_success([
      'message' => __('Legacy mode updated.', 'ppsgen'),
      'legacy_active' => $active ? 1 : 0,
    ]);
  }

  public function dismissLegacySwitchBanner()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'ppsgen')]);
    }
    if (!function_exists('ppsgen_dismiss_legacy_switch_banner')) {
      wp_send_json_error(['message' => __('Legacy switch banner cannot be dismissed.', 'ppsgen')]);
    }
    ppsgen_dismiss_legacy_switch_banner();
    wp_send_json_success(['message' => __('Legacy switch banner hidden.', 'ppsgen')]);
  }

  public function dismissLegacyUpgradeModal()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'ppsgen')]);
    }
    if (!function_exists('ppsgen_dismiss_legacy_upgrade_modal')) {
      wp_send_json_error(['message' => __('Legacy upgrade popup cannot be dismissed.', 'ppsgen')]);
    }
    ppsgen_dismiss_legacy_upgrade_modal();
    wp_send_json_success(['message' => __('Legacy upgrade popup hidden for today.', 'ppsgen')]);
  }

  public function feedbackNoticeAction()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $feedbackAction = sanitize_key((string) $this->getFrame()->core('request')->getVar('feedback_action', 'post'));
    $res = $this->getModel()->saveFeedbackNoticeAction($feedbackAction);
    if (!empty($res)) {
      wp_send_json_success([
        'message' => __('Success!', 'ppsgen'),
        'review_url' => 'https://wordpress.org/support/plugin/popup-by-supsystic/reviews/#new-post',
      ]);
    }
    wp_send_json_error(['message' => __('An error occurred.', 'ppsgen')]);
    wp_die();
  }

  public function proOfferNoticeAction()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $noticeAction = sanitize_key((string) $this->getFrame()->core('request')->getVar('promo_action', 'post'));
    $res = $this->getModel()->saveProOfferNoticeAction($noticeAction);
    if (!empty($res)) {
      wp_send_json_success([
        'message' => __('Success!', 'ppsgen'),
        'offer_url' => 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=dashboard_pro',
      ]);
    }
    wp_send_json_error(['message' => __('An error occurred.', 'ppsgen')]);
    wp_die();
  }

  public function saveMailConfig()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    $configId = (int) $this->getFrame()->core('request')->getVar('config_id', 'post');
    $payload = !empty($data['mail_config']) && is_array($data['mail_config']) ? $data['mail_config'] : [];
    if (empty($payload)) {
      wp_send_json_error(['message' => __('Mail config data is required.', 'ppsgen')]);
      wp_die();
    }
    $resultId = $this->getModel()->saveMailConfig($payload, $configId);
    if ($this->getModel()->getStatus() === 'success' && $resultId > 0) {
      wp_send_json_success([
        'message' => $this->getModel()->getMessage(),
        'config_id' => $resultId,
      ]);
      wp_die();
    }
    wp_send_json_error(['message' => $this->getModel()->getMessage()]);
    wp_die();
  }

  public function deleteMailConfig()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $configId = (int) $this->getFrame()->core('request')->getVar('config_id', 'post');
    if ($configId <= 0) {
      wp_send_json_error(['message' => __('Mail config ID is required.', 'ppsgen')]);
      wp_die();
    }
    $res = $this->getModel()->deleteMailConfig($configId);
    if ($res) {
      wp_send_json_success(['message' => __('Mail config was deleted.', 'ppsgen')]);
      wp_die();
    }
    wp_send_json_error(['message' => __('Failed to delete mail config.', 'ppsgen')]);
    wp_die();
  }

  public function testMailConfigConnection()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    $configId = (int) $this->getFrame()->core('request')->getVar('config_id', 'post');
    $payload = !empty($data['mail_config']) && is_array($data['mail_config']) ? $data['mail_config'] : [];
    if (empty($payload)) {
      wp_send_json_error(['message' => __('Mail config data is required.', 'ppsgen')]);
      wp_die();
    }
    $result = $this->getModel()->testMailConfigConnection($payload, $configId);
    if (($result['status'] ?? 'error') === 'success') {
      wp_send_json_success($result);
      wp_die();
    }
    wp_send_json_error($result);
    wp_die();
  }

  // Optional anonymous usage data: the one-time question and the Settings › Usage data switch
  public function setUsageTracking()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options') || !function_exists('ppsgen_set_usage_tracking')) {
      wp_send_json_error();
    }
    $enabled = (string) $this->getFrame()->core('request')->getVar('value', 'post') === 'yes';
    ppsgen_set_usage_tracking($enabled);
    wp_send_json_success([
      'enabled' => $enabled,
      'message' => $enabled ? __('Thanks! Anonymous counts will be shared.', 'ppsgen') : __('Got it. Nothing will be shared.', 'ppsgen'),
    ]);
  }

  // Home › This month: switch the monthly email on or off, or send a sample now
  public function setMonthlyReport()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $request = $this->getFrame()->core('request');
    if ((string) $request->getVar('sample', 'post') === '1') {
      $to = (string) wp_get_current_user()->user_email;
      if ($this->getModel()->sendMonthlyReport($to)) {
        wp_send_json_success(['message' => sprintf(__('Sample sent to %s.', 'ppsgen'), $to)]);
      }
      wp_send_json_error(['message' => __('The email could not be sent. Check Settings › Email sending.', 'ppsgen')]);
    }
    $enabled = !empty($request->getVar('enabled', 'post'));
    $this->getModel()->setMonthlyReportEnabled($enabled);
    wp_send_json_success(['message' => $enabled
      ? sprintf(__('You will get the summary on the 1st of each month at %s.', 'ppsgen'), $this->getModel()->getMonthlyReportSettings()['to'])
      : __('Monthly summary switched off.', 'ppsgen')]);
  }

  public function recheckEmailDomain()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    wp_send_json_success($this->getModel()->getEmailDomainCheck(true));
  }

  public function dismissLicenseExpiryNotice()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $this->getModel()->dismissProLicenseExpiryNotice((string) $this->getFrame()->core('request')->getVar('milestone', 'post'));
    wp_send_json_success();
  }

  // Settings › Email sending: send a real test email with the default config and remember the result
  public function sendTestEmail()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $result = $this->getModel()->sendTestEmail();
    if (!empty($result['ok'])) {
      wp_send_json_success($result);
    }
    wp_send_json_error($result);
  }

  //=====================================
  //       PLAYS
  //=====================================

  protected function checkPlayRequest(): void
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
  }

  // Save answers; with build=1 also build every part (switched off) and go to Review
  public function playSave()
  {
    $this->checkPlayRequest();
    $request = $this->getFrame()->core('request');
    $plays = $this->getModule()->getPlays();
    $answers = $request->getVar('answers', 'post');
    $result = $plays->saveAnswers(
      sanitize_key((string) $request->getVar('type', 'post')),
      is_array($answers) ? wp_unslash($answers) : [],
      sanitize_key((string) $request->getVar('play_id', 'post'))
    );
    if (empty($result['ok'])) {
      wp_send_json_error($result);
    }
    if (!empty($request->getVar('build', 'post'))) {
      $built = $plays->build($result['id']);
      if (empty($built['ok'])) {
        wp_send_json_error($built);
      }
      wp_send_json_success(['id' => $result['id'], 'url' => admin_url('admin.php?page=ppsgen&tab=play_review&id=' . $result['id'])]);
    }
    wp_send_json_success(['id' => $result['id'], 'url' => admin_url('admin.php?page=ppsgen&tab=plays')]);
  }

  public function playSetLive()
  {
    $this->checkPlayRequest();
    $request = $this->getFrame()->core('request');
    $id = sanitize_key((string) $request->getVar('play_id', 'post'));
    $result = $this->getModule()->getPlays()->setLive($id, !empty($request->getVar('live', 'post')), !empty($request->getVar('replace', 'post')));
    if (empty($result['ok'])) {
      wp_send_json_error($result);
    }
    $play = $this->getModule()->getPlays()->getPlay($id);
    $parts = $play['parts'] ?? [];
    // A play that only prepared a newsletter is sent from the newsletter editor
    $result['url'] = !empty($parts['newsletter_id']) && empty($parts['popup_id']) && empty($parts['chain_id']) && !empty($request->getVar('live', 'post'))
      ? admin_url('admin.php?page=ppsgen&tab=eca_chain_edit&id=' . (int) $parts['newsletter_id'])
      : admin_url('admin.php?page=ppsgen&tab=play_summary&id=' . $id);
    wp_send_json_success($result);
  }

  public function playSendTestEnquiry()
  {
    $this->checkPlayRequest();
    $play = $this->getModule()->getPlays()->getPlay(sanitize_key((string) $this->getFrame()->core('request')->getVar('play_id', 'post')));
    $formId = (int) ($play['parts']['form_id'] ?? 0);
    $form = $this->getFrame()->getModule('form');
    if ($formId <= 0 || !$form) {
      wp_send_json_error(['message' => __('This play has no form to test.', 'ppsgen')]);
    }
    $user = wp_get_current_user();
    $result = $form->getModel()->sendTestEnquiryEmails($formId, (string) $user->user_email);
    if (empty($result['ok'])) {
      wp_send_json_error($result);
    }
    wp_send_json_success($result);
  }

  public function playMarkStopped()
  {
    $this->checkPlayRequest();
    $request = $this->getFrame()->core('request');
    $result = $this->getModule()->getPlays()->markStopped(sanitize_key((string) $request->getVar('play_id', 'post')), (int) $request->getVar('contact_id', 'post'));
    if (empty($result['ok'])) {
      wp_send_json_error($result);
    }
    wp_send_json_success($result);
  }

  public function playDelete()
  {
    $this->checkPlayRequest();
    $this->getModule()->getPlays()->deletePlay(sanitize_key((string) $this->getFrame()->core('request')->getVar('play_id', 'post')));
    wp_send_json_success(['message' => __('Play removed. Its popup, form and emails are still in Popups, Forms and Campaigns.', 'ppsgen')]);
  }
}
