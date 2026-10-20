<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactControllerPpsgen extends BaseControllerPpsgen
{
  public function editContact()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    $obj_id = $this->getFrame()->core('request')->getVar('obj_id', 'post');
    if (empty($data) || empty($data['contact']['contact_fields'])) {
      wp_send_json_error();
      wp_die();
    }
    $validator = $this->getFrame()->getCfv();
    if (!$validator->validate($data['contact']['contact_fields'])) {
      $errors = $validator->getErrors();
      wp_send_json_error(['fields_error' => $errors]);
    }
    $model = $this->getModel();
    $res = $model->editContact($data, $obj_id);
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

  public function editContactOptions()
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
    $data = $this->getModel()->sanitizeContactOptionsPayload((array) $data);
    $res = $this->getModel()->updateOptions($data, 'options');
    if (!empty($res)) {
      wp_send_json_success(['message' => __('Data is updated!', 'ppsgen')]);
    } else {
      wp_send_json_error();
    }
    wp_die();
  }

  public function runManualSuspiciousActivityScan()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    if (!$this->getFrame()->useModule('eca')) {
      wp_send_json_error(['message' => __('Email Campaigns module is not active.', 'ppsgen')]);
      wp_die();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    $data = is_array($data) ? $this->getModel()->sanitizeContactOptionsPayload($data) : [];
    if (!empty($data)) {
      $this->getModel()->updateOptions($data, 'options');
    }
    $result = $this->getFrame()->getModule('eca')->getModel()->runManualSuspiciousActivityScan();
    $ecaModel = $this->getFrame()->getModule('eca')->getModel();
    if ($ecaModel->getStatus() === 'error') {
      wp_send_json_error(['message' => $ecaModel->getMessage()]);
      wp_die();
    }
    wp_send_json_success([
      'message' => $ecaModel->getMessage(),
      'result' => $result,
    ]);
    wp_die();
  }

  public function importGenerateContacts()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    if (empty($data['contact_migration'])) {
      wp_send_json_error();
      wp_die();
    }
    $model = $this->getModel();
    $res = $model->importGenerateContacts($data['contact_migration']);
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

  public function importGenerateWooContacts()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = isset($_POST['data']['woo_contact_migration']) ? wp_unslash($_POST['data']['woo_contact_migration']) : [];
    if (empty($data) || !is_array($data)) {
      wp_send_json_error();
      wp_die();
    }
    $model = $this->getModel();
    $res = $model->importGenerateWooContacts($data);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel == 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
    }
    if ($resModel == 'success') {
      wp_send_json_success(['message' => $resModelMsg, 'result' => $res]);
    }
    wp_send_json_error();
    wp_die();
  }

  public function saveContactAutoImportSettings()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $source = isset($_POST['autoimport_source']) ? sanitize_key(wp_unslash($_POST['autoimport_source'])) : '';
    $enabled = !empty($_POST['autoimport_enabled']) ? 1 : 0;
    $data = isset($_POST['data']) && is_array($_POST['data']) ? wp_unslash($_POST['data']) : [];
    if (empty($source) || empty($data)) {
      wp_send_json_error();
      wp_die();
    }
    $model = $this->getModel();
    $result = $model->saveContactAutoImportSettings($source, $data, (bool) $enabled);
    if ($model->getStatus() === 'error') {
      wp_send_json_error(['message' => $model->getMessage()]);
      wp_die();
    }
    wp_send_json_success([
      'message' => $model->getMessage(),
      'enabled' => !empty($result['enabled']),
      'source' => $source,
    ]);
    wp_die();
  }

  public function importCsvContacts()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    if (empty($data['contact_migration'])) {
      wp_send_json_error();
      wp_die();
    }
    if (empty($_FILES['data']['name']['contact_migration']['csv_file']) || empty($_FILES['data']['tmp_name']['contact_migration']['csv_file'])) {
      wp_send_json_error(['message' => esc_html__('No CSV uploaded', 'ppsgen')]);
      wp_die();
    }
    // Security checks for uploaded file
    $file['file'] = isset($_FILES['data']['tmp_name']['contact_migration']['csv_file']) ? sanitize_text_field($_FILES['data']['tmp_name']['contact_migration']['csv_file']) : '';
    $file['name'] = isset($_FILES['data']['name']['contact_migration']['csv_file']) ? sanitize_file_name($_FILES['data']['name']['contact_migration']['csv_file']) : '';
    $file['size'] = isset($_FILES['data']['size']['contact_migration']['csv_file']) ? intval($_FILES['data']['size']['contact_migration']['csv_file']) : 0;
    $model = $this->getModel();
    $res = $model->importCsvContacts($data['contact_migration'], $file);
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

  public function deleteContact()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->deleteContact($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Contact is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function verifyContact()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    if (!empty($obj)) {
      $res = $this->getModel()->verifyContact($obj);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Contact Now is Verified', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function verifySubmit()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $obj = $this->getFrame()->core('request')->getVar('obj', 'post');
    $rewrite = $this->getFrame()->core('request')->getVar('rewrite', 'post');
    $rewrite = !empty($rewrite) ? true : false;
    if (!empty($obj)) {
      $res = $this->getModel()->verifySubmit($obj, $rewrite);
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Submit Now is Verified', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function clearContactFieldsAndTagsLogs()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactId = (int) $this->getFrame()->core('request')->getVar('contact_id', 'post');
    if (empty($contactId)) {
      wp_send_json_error(['message' => __('Contact ID is required.', 'ppsgen')]);
      wp_die();
    }
    $res = $this->getModel()->clearContactFieldsAndTagsLogs($contactId);
    if ($res) {
      wp_send_json_success(['message' => __('Fields and tags logs were cleared.', 'ppsgen')]);
      wp_die();
    }
    wp_send_json_error(['message' => __('Failed to clear fields and tags logs.', 'ppsgen')]);
    wp_die();
  }

  public function deleteContactList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    if (!empty($objs) && is_array($objs)) {
      foreach ($objs as $obj) {
        $res = $this->getModel()->deleteContact((int) $obj);
      }
      if (!empty($res)) {
        wp_send_json_success(['message' => __('Contact List is Deleted', 'ppsgen')]);
      }
    }
    wp_send_json_error();
    wp_die();
  }

  public function bulkAddTagsToContacts()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactIds = isset($_POST['contact_ids']) && is_array($_POST['contact_ids']) ? wp_unslash($_POST['contact_ids']) : [];
    $tagIds = isset($_POST['tag_ids']) && is_array($_POST['tag_ids']) ? wp_unslash($_POST['tag_ids']) : [];
    if (empty($contactIds) || empty($tagIds)) {
      wp_send_json_error(['message' => __('Please select contacts and at least one tag.', 'ppsgen')]);
      wp_die();
    }
    $model = $this->getModel();
    $result = $model->bulkAddTagsToContacts($contactIds, $tagIds, 'admin');
    if ($model->getStatus() === 'error') {
      wp_send_json_error(['message' => $model->getMessage()]);
      wp_die();
    }
    wp_send_json_success([
      'message' => $model->getMessage(),
      'result' => $result,
    ]);
    wp_die();
  }

  public function bulkDeleteTagsFromContacts()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactIds = isset($_POST['contact_ids']) && is_array($_POST['contact_ids']) ? wp_unslash($_POST['contact_ids']) : [];
    $tagIds = isset($_POST['tag_ids']) && is_array($_POST['tag_ids']) ? wp_unslash($_POST['tag_ids']) : [];
    if (empty($contactIds) || empty($tagIds)) {
      wp_send_json_error(['message' => __('Please select contacts and at least one tag.', 'ppsgen')]);
      wp_die();
    }
    $model = $this->getModel();
    $result = $model->bulkDeleteTagsFromContacts($contactIds, $tagIds, 'admin');
    if ($model->getStatus() === 'error') {
      wp_send_json_error(['message' => $model->getMessage()]);
      wp_die();
    }
    wp_send_json_success([
      'message' => $model->getMessage(),
      'result' => $result,
    ]);
    wp_die();
  }

  public function searchContactLookup()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $query = $this->getFrame()->core('request')->getVar('query', 'post', '');
    $limit = (int) $this->getFrame()->core('request')->getVar('limit', 'post', 10);
    $items = $this->getModel()->searchContactLookupItems((string) $query, $limit);
    wp_send_json_success([
      'items' => $items,
    ]);
    wp_die();
  }

  public function exportContactList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => esc_html__('Permission denied.', 'ppsgen')]);
      wp_die();
    }
    $objs = $this->getFrame()->core('request')->getVar('objs', 'post');
    $objs = !empty($objs) && is_array($objs) ? array_map('intval', $objs) : [];
    $model = $this->getModel();
    $result = $model->exportContactList($objs);
    $resModel = $model->getStatus();
    $resModelMsg = $model->getMessage();
    if ($resModel === 'error') {
      wp_send_json_error(['message' => $resModelMsg]);
      wp_die();
    }
    if ($resModel === 'success' && !empty($result['file_url']) && !empty($result['file_name'])) {
      $upload_dir = wp_upload_dir();
      $file_path = trailingslashit($upload_dir['path']) . $result['file_name'];
      require_once ABSPATH . 'wp-admin/includes/file.php';
      WP_Filesystem();
      global $wp_filesystem;
      if ($wp_filesystem->exists($file_path)) {
        $content = $wp_filesystem->get_contents($file_path);
        if ($content !== false) {
          // Set headers for file download
          nocache_headers();
          header('Content-Type: text/csv; charset=UTF-8');
          header('Content-Disposition: attachment; filename="' . $result['file_name'] . '"');
          header('Content-Length: ' . strlen($content));
          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe, raw CSV file output
          echo $content;
          $wp_filesystem->delete($file_path);
          wp_die();
        }
      }
    }
    wp_send_json_error(['message' => esc_html__('Failed to export contacts.', 'ppsgen')]);
    wp_die();
  }

  public function loadContactEmailThread()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactId = (int) $this->getFrame()->core('request')->getVar('contact_id', 'post');
    $mailConfigId = (int) $this->getFrame()->core('request')->getVar('mail_config_id', 'post');
    if (empty($contactId)) {
      wp_send_json_error(['message' => __('Contact ID is required.', 'ppsgen')]);
      wp_die();
    }
    $threadData = $this->getModel()->getContactEmailThread($contactId, $mailConfigId);
    $html = $this->getView()->renderContactEmailChatThread($threadData);
    $incomingSummary = [
      'tracking_enabled' => false,
      'selected_unhandled_total' => 0,
      'selected_last_received_at' => '',
      'all_unhandled_total' => 0,
      'all_last_received_at' => '',
    ];
    if ($this->getFrame()->useModule('eca')) {
      $incomingSummary = $this->getFrame()->getModule('eca')->getModel()->getContactIncomingMailboxSummary($contactId, $mailConfigId);
    }
    $incomingSummaryHtml = $this->getView()->renderContactEmailChatIncomingSummary($incomingSummary);
    wp_send_json_success([
      'html' => $html,
      'suggested_subject' => (string) ($threadData['suggested_subject'] ?? ''),
      'incoming_summary' => $incomingSummary,
      'incoming_summary_html' => $incomingSummaryHtml,
    ]);
    wp_die();
  }

  public function getContactEmailMessageView()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactId = (int) $this->getFrame()->core('request')->getVar('contact_id', 'post');
    $mailConfigId = (int) $this->getFrame()->core('request')->getVar('mail_config_id', 'post');
    $mailboxKey = (string) $this->getFrame()->core('request')->getVar('mailbox_key', 'post');
    $messageId = (string) $this->getFrame()->core('request')->getVar('message_id', 'post');
    if (empty($contactId) || empty($mailboxKey) || $messageId === '') {
      wp_send_json_error(['message' => __('Email message parameters are incomplete.', 'ppsgen')]);
      wp_die();
    }
    $message = $this->getModel()->getContactEmailMessageView($contactId, $mailboxKey, $messageId, $mailConfigId);
    if (($message['status'] ?? 'error') !== 'success') {
      wp_send_json_error(['message' => (string) ($message['message'] ?? __('Unable to load email message.', 'ppsgen'))]);
      wp_die();
    }
    if ($this->getFrame()->useModule('eca') && ctype_digit($messageId)) {
      $this->getFrame()->getModule('eca')->getModel()->markIncomingMailboxMessageSeen($contactId, $mailConfigId, $mailboxKey, (int) $messageId);
    }
    wp_send_json_success([
      'html' => (string) ($message['html'] ?? ''),
      'seen' => !empty($message['seen']),
      'reply_subject' => (string) ($message['reply_subject'] ?? ''),
    ]);
    wp_die();
  }

  public function markContactIncomingEmailsHandled()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactId = (int) $this->getFrame()->core('request')->getVar('contact_id', 'post');
    $mailConfigId = (int) $this->getFrame()->core('request')->getVar('mail_config_id', 'post');
    if (empty($contactId) || !$this->getFrame()->useModule('eca')) {
      wp_send_json_error(['message' => __('Incoming email tracking is unavailable.', 'ppsgen')]);
      wp_die();
    }
    $handled = $this->getFrame()->getModule('eca')->getModel()->markContactIncomingEmailsHandled($contactId, $mailConfigId, get_current_user_id());
    $incomingSummary = $this->getFrame()->getModule('eca')->getModel()->getContactIncomingMailboxSummary($contactId, $mailConfigId);
    $incomingSummaryHtml = $this->getView()->renderContactEmailChatIncomingSummary($incomingSummary);
    wp_send_json_success([
      'message' => $handled > 0 ? __('Incoming emails were marked as handled.', 'ppsgen') : __('There are no pending incoming emails for this mailbox.', 'ppsgen'),
      'incoming_summary' => $incomingSummary,
      'incoming_summary_html' => $incomingSummaryHtml,
    ]);
    wp_die();
  }

  public function sendContactEmailMessage()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactId = (int) $this->getFrame()->core('request')->getVar('contact_id', 'post');
    $data = $this->getFrame()->core('request')->getVar('data', 'post');
    $payload = !empty($data['contact_email_chat']) && is_array($data['contact_email_chat']) ? $data['contact_email_chat'] : [];
    if (empty($contactId) || empty($payload)) {
      wp_send_json_error(['message' => __('Email data is required.', 'ppsgen')]);
      wp_die();
    }
    $result = $this->getModel()->sendContactEmailMessage($contactId, $payload);
    if (($result['status'] ?? 'error') !== 'success') {
      wp_send_json_error(['message' => (string) ($result['message'] ?? __('Failed to send the email.', 'ppsgen'))]);
      wp_die();
    }
    wp_send_json_success(['message' => (string) ($result['message'] ?? __('Email has been sent.', 'ppsgen'))]);
    wp_die();
  }

  public function markContactReplied()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactId = (int) $this->getFrame()->core('request')->getVar('contact_id', 'post');
    if (!$this->getModel()->markContactReplied($contactId)) {
      wp_send_json_error(['message' => __('Contact not found.', 'ppsgen')]);
    }
    wp_send_json_success([
      'message' => __('Marked as replied: follow-ups that stop on a reply won’t email this person again.', 'ppsgen'),
      'html' => $this->getView()->renderContactPreview($this->getModel()->getContactPreview($contactId)),
    ]);
  }

  public function addContactToAutomation()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $request = $this->getFrame()->core('request');
    $contactId = (int) $request->getVar('contact_id', 'post');
    $result = $this->getModel()->addContactToAutomation($contactId, (int) $request->getVar('chain_id', 'post'));
    if (empty($result['ok'])) {
      wp_send_json_error($result);
    }
    $result['html'] = $this->getView()->renderContactPreview($this->getModel()->getContactPreview($contactId));
    wp_send_json_success($result);
  }

  public function getContactPreview()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $contactId = (int) $this->getFrame()->core('request')->getVar('contact_id', 'post');
    $preview = $this->getModel()->getContactPreview($contactId);
    if (empty($preview)) {
      wp_send_json_error(['message' => __('Contact not found.', 'ppsgen')]);
    }
    wp_send_json_success(['html' => $this->getView()->renderContactPreview($preview)]);
  }

  public function saveSavedContactList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $request = $this->getFrame()->core('request');
    $filters = json_decode(wp_unslash((string) $request->getVar('filters', 'post')), true);
    $result = $this->getModel()->saveSavedContactList((string) $request->getVar('name', 'post'), $filters);
    if (!empty($result['ok'])) {
      wp_send_json_success($result);
    }
    wp_send_json_error($result);
  }

  public function deleteSavedContactList()
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error();
    }
    $this->getModel()->deleteSavedContactList(sanitize_key((string) $this->getFrame()->core('request')->getVar('list_id', 'post')));
    wp_send_json_success();
  }
}
