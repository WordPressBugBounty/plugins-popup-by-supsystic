<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$prefix = 'data[contact]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenComponentsList">
  <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Is Verified', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('By default verified contact only after Email submission.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[verified]', ['checked' => $component->value($options['verified'] ?? null, '0'), 'class' => '']); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link a contact to a specified WP user', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[wp_user_id]', [
        'options' => $this->allWpUsers,
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($options['wp_user_id'] ?? null),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[wp_user_id]" data-toggle-val="0">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Create WP user for this contact', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->checkboxToggle('[create_wp_user]', ['checked' => '', 'class' => 'data-option-toggle-trigger']); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[create_wp_user]" data-toggle-val="1">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Select role for new WP user', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[create_wp_user_role_id]', [
        'options' => array_reverse(wp_roles()->get_names(), true),

        'selected' => $component->value($options['create_wp_user_role_id'] ?? null),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[create_wp_user]" data-toggle-val="1">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Notify user by email', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[notify_user]', ['checked' => $component->value($options['notify_user'] ?? null), 'class' => '']); ?>
    </div>
  </div>

  <?php if (!empty($options['obj_id'])) { ?>
  <h3><?php esc_html_e('Date Info', 'ppsgen'); ?></h3>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Created', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[created_at]', ['value' => $component->value($options['created_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Updated', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[updated_at]', ['value' => $component->value($options['updated_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <?php } ?>
</div>