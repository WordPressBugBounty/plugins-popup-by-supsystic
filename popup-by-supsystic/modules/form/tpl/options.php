<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$formOptions = $this->formOptions;
$prefix = 'data[form]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-admin-settings"></span>
    <?php esc_html_e('Form defaults', 'ppsgen'); ?>
  </h2>
  <button class="ppsgenSaveBtn ppsgenFormOptionsSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
</div>
<div class="ppsgenOptionsWrapper">
  <form class="ppsgenOptionsForm ppsgenFormOptionsForm" id="ppsgenFormOptionsForm">
    <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
    <input type="hidden" name="mod" value="form">
    <input type="hidden" id="action" name="action" value="editFormOptions">
    <div class="ppsgenComponentsList">
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Select bot protection type', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select type of protection from bots.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('[bot_protection]', [
            'options' => [
              'none' => __('None', 'ppsgen'),
              'honeypot' => __('Honeypot (Hidden field)', 'ppsgen'),
              'google_recaptcha_v2' => __('Google reCAPTCHA v2 (Submit Checkbox)', 'ppsgen'),
              'google_recaptcha_v2_hidden' => __('Google reCAPTCHA v2 (Hidden Badge)', 'ppsgen'),
            ],
            'class' => 'data-option-toggle-trigger',
            // select() needs a string; an empty array triggered "Array to string conversion" before the option was saved
            'selected' => !empty($formOptions['bot_protection']) ? (string) $component->value($formOptions['bot_protection'] ?? null) : 'none',
          ]); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[bot_protection]" data-toggle-val="google_recaptcha_v2, google_recaptcha_v2_hidden">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Google reCAPTCHA Public Key', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[bot_protection]" data-toggle-val="google_recaptcha_v2_hidden">
          <?php $component->text('[google_recaptchav2_hidden_public]', ['value' => $component->value($formOptions['google_recaptchav2_hidden_public'] ?? null), 'attrs' => '']); ?>
        </div>
        <div class="ppsgenComponentOption" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[bot_protection]" data-toggle-val="google_recaptcha_v2">
          <?php $component->text('[google_recaptchav2_public]', ['value' => $component->value($formOptions['google_recaptchav2_public'] ?? null), 'attrs' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[bot_protection]" data-toggle-val="google_recaptcha_v2, google_recaptcha_v2_hidden">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Google reCAPTCHA Secret Key', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[bot_protection]" data-toggle-val="google_recaptcha_v2_hidden">
          <?php $component->text('[google_recaptchav2_hidden_secret]', ['value' => $component->value($formOptions['google_recaptchav2_hidden_secret'] ?? null)]); ?>
        </div>
        <div class="ppsgenComponentOption" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[bot_protection]" data-toggle-val="google_recaptcha_v2">
          <?php $component->text('[google_recaptchav2_secret]', ['value' => $component->value($formOptions['google_recaptchav2_secret'] ?? null)]); ?>
        </div>
      </div>
    </div>
    <button class="ppsgenSaveBtn ppsgenFormOptionsSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </form>
</div>