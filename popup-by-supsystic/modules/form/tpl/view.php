<?php
if (!defined('ABSPATH')) {
  exit();
}
$options = $this->options;
$fields = !empty($options['fields']) ? $options['fields'] : [];
$settings = !empty($options['settings']) ? $options['settings'] : [];
$design = !empty($options['design']) ? $options['design'] : [];
$obj_id = !empty($options['obj_id']) ? $options['obj_id'] : 0;
$formClass = !empty($settings['form_class']) ? $settings['form_class'] : '';
$formId = !empty($settings['form_id']) ? $settings['form_id'] : '';
$formSuccessText = !empty($settings['html_success_msg']) ? $settings['html_success_msg'] : '';
?>

<div data-form-id="<?php echo esc_attr($obj_id); ?>" class="ppsgenFormWrapper ppsgenFormWrapper-<?php echo esc_attr($obj_id); ?>" style="display:none;">

<form enctype="multipart/form-data" data-form-id="<?php echo esc_attr($obj_id); ?>" data-form-var="ppsgenFrontendForm<?php echo esc_attr($obj_id); ?>" class="ppsgenForm ppsgenForm-<?php echo esc_attr($obj_id); ?> <?php echo esc_attr($formClass); ?>" id="<?php echo esc_attr($formId); ?>">

<?php if (!empty($fields)) { ?>
  <?php foreach ($fields as $field) { ?>
    <?php if (!empty($field['cf_id'])) { ?>
      <?php $fieldIsEnabled = $this->frame->getModule('contact')->getSubmod('contact_field')->getModel()->fieldIsEnabled($field['cf_id']); ?>
      <?php if (empty($fieldIsEnabled)) {
        continue;
      } ?>
    <?php } ?>
    <?php $fieldName = 'ppsgen_form[' . (!empty($field['settings']['field_slug']) ? $field['settings']['field_slug'] : '') . ']'; ?>
    <?php $fieldAttrs = ['value' => '', 'name' => $fieldName, 'class' => !empty($field['settings']['class']) ? $field['settings']['class'] : '', 'id' => $field['settings']['id']]; ?>
    <?php $this->frame->getCfb()->build($field['settings']['field_type'], $field['settings'], $fieldAttrs, $field['options']); ?>
  <?php } ?>
<?php } ?>

<div class="ppsgenFormSubmitWrapper">
  <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

echo $this->frame->core('helper')->wpKses($formSuccessText); ?>
</div>

</form>

</div>