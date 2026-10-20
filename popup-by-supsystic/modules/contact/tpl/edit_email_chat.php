<?php
if (!defined('ABSPATH')) {
  exit();
}
$contactId = (int) ($this->contactEmailChatObjId ?? 0);
$contactEmail = (string) ($this->contactEmailChatEmail ?? '');
$templates = is_array($this->contactEmailChatTemplates ?? null) ? $this->contactEmailChatTemplates : [];
$mailConfigs = is_array($this->contactEmailChatMailConfigs ?? null) ? $this->contactEmailChatMailConfigs : [];
$defaultMailConfigId = (int) ($this->contactEmailChatDefaultMailConfigId ?? 0);
$incomingSummaryHtml = (string) ($this->contactEmailChatIncomingSummaryHtml ?? '');
$editorId = (string) ($this->contactEmailChatEditorId ?? ('ppsgen_contact_email_reply_' . $contactId));
?>
<div class="ppsgenContactEmailChatPage" data-contact-id="<?php echo esc_attr($contactId); ?>">
  <?php if (empty($contactEmail)) { ?>
    <div class="ppsgenContactCampaignsEmpty ppsgenFormSubmitsWrapperWarning">
      <?php esc_html_e('Add an email address to this contact first to view or send email messages.', 'ppsgen'); ?>
    </div>
  <?php } else { ?>
    <div class="ppsgenContactEmailChatTop">
      <div class="ppsgenContactEmailChatSummary">
        <span class="ppsgenContactCampaignPill"><?php esc_html_e('Contact Email', 'ppsgen'); ?>: <?php echo esc_html($contactEmail); ?></span>
        <span class="ppsgenContactCampaignPill"><?php esc_html_e('Chat Source', 'ppsgen'); ?>: <?php esc_html_e('IMAP Mailbox', 'ppsgen'); ?></span>
      </div>
      <div class="ppsgenContactEmailChatTopActions">
        <?php if (!empty($mailConfigs)) { ?>
          <div class="ppsgenContactEmailComposerField ppsgenContactEmailComposerFieldTemplate">
            <label for="ppsgenContactEmailMailConfig"><?php esc_html_e('SMTP / IMAP Config', 'ppsgen'); ?></label>
            <select id="ppsgenContactEmailMailConfig" name="data[contact_email_chat][mail_config_id]">
              <?php foreach ($mailConfigs as $configId => $configName) { ?>
                <option value="<?php echo esc_attr((int) $configId); ?>" <?php selected((int) $configId, $defaultMailConfigId); ?>><?php echo esc_html($configName); ?></option>
              <?php } ?>
            </select>
          </div>
        <?php } ?>
        <button type="button" class="ppsgenContactCampaignAction ppsgenContactEmailRefreshBtn"><?php esc_html_e('Refresh', 'ppsgen'); ?></button>
      </div>
    </div>

    <div class="ppsgenContactEmailIncomingSummaryWrap">
      <?php echo $this->getFrame()->core('helper')->wpKses($incomingSummaryHtml); ?>
    </div>

    <div class="ppsgenContactEmailThreadWrap">
      <div class="ppsgenContactEmailThread" data-loaded="0">
        <div class="ppsgenContactEmailThreadLoader">
          <span class="dashicons dashicons-update-alt"></span>
          <?php esc_html_e('Open the tab to load the mailbox thread...', 'ppsgen'); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenContactEmailComposer">
      <div class="ppsgenContactEmailComposerTop">
        <div class="ppsgenContactEmailComposerField">
          <label for="ppsgenContactEmailSubject"><?php esc_html_e('Subject', 'ppsgen'); ?></label>
          <input type="text" id="ppsgenContactEmailSubject" name="data[contact_email_chat][subject]" value="" placeholder="<?php esc_attr_e('Type email subject...', 'ppsgen'); ?>">
        </div>
        <?php if (!empty($templates)) { ?>
          <div class="ppsgenContactEmailComposerField ppsgenContactEmailComposerFieldTemplate">
            <label for="ppsgenContactEmailTemplate"><?php esc_html_e('Email template', 'ppsgen'); ?></label>
            <select id="ppsgenContactEmailTemplate" name="data[contact_email_chat][template_id]">
              <option value=""><?php esc_html_e('No Template', 'ppsgen'); ?></option>
              <?php foreach ($templates as $templateId => $templateName) { ?>
                <option value="<?php echo esc_attr((int) $templateId); ?>"><?php echo esc_html($templateName); ?></option>
              <?php } ?>
            </select>
          </div>
        <?php } ?>
      </div>

      <div class="ppsgenContactEmailComposerEditor">
        <?php
        wp_editor('', $editorId, [
          'textarea_name' => 'data[contact_email_chat][html_message]',
          'textarea_rows' => 10,
          'media_buttons' => true,
          'wpautop' => false,
          'teeny' => false,
        ]);
        ?>
      </div>

      <div class="ppsgenContactEmailComposerActions">
        <span class="ppsgenContactEmailComposerHint"><?php esc_html_e('If you select an email template, your editor content will replace [email_content].', 'ppsgen'); ?></span>
        <button type="button" class="ppsgenSaveBtn ppsgenContactEmailSendBtn" data-editor-id="<?php echo esc_attr($editorId); ?>" data-contact-id="<?php echo esc_attr($contactId); ?>">
          <span class="dashicons dashicons-email-alt"></span><?php esc_html_e('Send Email', 'ppsgen'); ?>
        </button>
      </div>
    </div>

    <div class="ppsgenContactEmailModal" aria-hidden="true">
      <div class="ppsgenContactEmailModalBackdrop"></div>
      <div class="ppsgenContactEmailModalDialog" role="dialog" aria-modal="true">
        <div class="ppsgenContactEmailModalTop">
          <div class="ppsgenContactEmailModalTitle"><?php esc_html_e('Email Preview', 'ppsgen'); ?></div>
          <button type="button" class="ppsgenContactEmailModalClose" aria-label="<?php esc_attr_e('Close', 'ppsgen'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
          </button>
        </div>
        <div class="ppsgenContactEmailModalFrameWrap">
          <div class="ppsgenContactEmailModalFrameTarget"></div>
        </div>
      </div>
    </div>
  <?php } ?>
</div>
