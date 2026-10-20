<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$prefix = 'data[template]';
$sprefix = 'data[template][settings]';
$component->setNamePrefix($prefix);
$template_id = !empty($options['obj_id']) ? $options['obj_id'] : 0;
?>

<div class="ppsgenComponentsList">
  <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Template is Enabled', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[active]', ['checked' => $component->value($options['active'] ?? null, '1'), 'class' => 'data-option-toggle-trigger']); ?>
      </div>
    </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Template Name', 'ppsgen'); ?>"  ></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[name]', ['value' => $component->value($options['name'] ?? null), 'attrs' => ' ']); ?>
    </div>
  </div>

  <?php if (!empty($options['obj_id'])) { ?>
  <h3><?php esc_html_e('Date Info', 'ppsgen'); ?></h3>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Created', 'ppsgen'); ?>"  ></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[created_at]', ['value' => $component->value($options['created_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Updated', 'ppsgen'); ?>"  ></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[updated_at]', ['value' => $component->value($options['updated_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <?php } ?>

</div>