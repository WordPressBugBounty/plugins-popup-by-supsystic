<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = &$this->options;
$prefix = 'data[contact][contact_tags]';
$component->setNamePrefix($prefix);
?>
<h3><?php esc_html_e('Contact Tags', 'ppsgen'); ?></h3>
<div class="ppsgenComponentsList">
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Selected Tag(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific tag(s) for this contact.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption ppsgenW300">
      <?php $component->selectmultiple('[selected_contact_tags][]', [
        'options' => $this->allContactTags,
        'class' => 'ppsgenselect',
        'selected' => !empty($options['selected_contact_tags']) ? $options['selected_contact_tags'] : [],
      ]); ?>
    </div>
  </div>
</div>