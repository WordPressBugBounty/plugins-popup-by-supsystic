<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$fields = !empty($options['fields']) ? $options['fields'] : '';
$prefix = 'data[form]';
$sprefix = 'data[form][fields]';
$component->setNamePrefix('');
$allContactFields = $this->frame->getModule('contact')->getSubmod('contact_field')->getModel()->getAllContactFields();
$otherFields = [
  'submit' => esc_html__('Submit Button', 'ppsgen'),
  'captcha' => esc_html__('Captcha Button', 'ppsgen'),
  'title' => esc_html__('Title', 'ppsgen'),
  'description' => esc_html__('Description', 'ppsgen'),
  'divider' => esc_html__('Row Divider', 'ppsgen'),
  'empty' => esc_html__('Empty Col', 'ppsgen'),
];
$existFieldTypes = [];
$existFieldIds = [];
if (!empty($fields)) {
  foreach ($fields as $field) {
    $existFieldTypes[] = $field['settings']['field_type'];
    $existFieldIds[] = $field['cf_id'];
  }
}
?>

<div class="ppsgenFormFields">
  <div class="ppsgenFormFieldsLeft">
    <div class="ppsgenCfbContainer">
      <div class="ppsgenCfbAddFieldWrapper">
        <div class="ppsgenCfbAddFieldLabel"><?php esc_html_e('Contact Field', 'ppsgen'); ?></div>
        <select class="ppsgenCfbAddFieldList ppsgenCfbAddContactFieldList">
          <?php if (!empty($allContactFields)) {
            foreach ($allContactFields as $cf) {
              $disabled = in_array($cf['id'], $existFieldIds) ? 'disabled' : '';
              echo '<option ' .
                esc_attr($disabled) .
                ' data-field-label="' .
                esc_attr($cf['label']) .
                '" data-field-type="' .
                esc_attr($cf['field_type']) .
                '" data-field-id="' .
                esc_attr($cf['id']) .
                '" data-field-slug="' .
                esc_attr($cf['slug']) .
                '" value="' .
                esc_attr($cf['id']) .
                '">' .
                esc_html($cf['id']) .
                ' - ' .
                esc_html($cf['field_type']) .
                ' - ' .
                esc_html($cf['slug']) .
                ' - ' .
                esc_html($cf['label']) .
                '</option>';
            }
          } else {
            echo '<option selected disabled>' . esc_html__('First create field in (Contacts > Contact Field)', 'ppsgen') . '</option>';
          } ?>
        </select>
        <div class="ppsgenCfbAddFieldBtn ppsgenCfbAddContactFieldBtn"><?php esc_html_e('Add to Form', 'ppsgen'); ?></div>
      </div>
      <div class="ppsgenCfbAddFieldWrapper">
        <div class="ppsgenCfbAddFieldLabel"><?php esc_html_e('Special Field', 'ppsgen'); ?></div>
        <select class="ppsgenCfbAddFieldList ppsgenCfbAddOtherFieldList">
          <?php if (!empty($otherFields)) {
            foreach ($otherFields as $type => $of) {
              $disabled = '';
              if (in_array($type, ['submit', 'captcha'])) {
                $disabled = in_array($type, $existFieldTypes) ? 'disabled' : '';
              }
              echo '<option ' . esc_attr($disabled) . ' data-field-type="' . esc_attr($type) . '" value="">' . esc_html($of) . '</option>';
            }
          } ?>
        </select>
        <div class="ppsgenCfbAddFieldBtn ppsgenCfbAddOtherFieldBtn"><?php esc_html_e('Add to Form', 'ppsgen'); ?></div>
      </div>

      <div class="ppsgenCfbFieldsWrapperSizeChanger">
        <div class="ppsgenCfbSizeChangerToggle" title="<?php echo esc_attr('Show/hide Field by Screen Rules', 'ppsgen'); ?>"><span class="dashicons dashicons-hidden"></span></div>
        <div class="ppsgenCfbFieldsWrapperSizeChangerIcons">
          <div class="ppsgenCfbSizeChangerBtn ppsgenCfbSizeChangerBtnMob" title="<?php echo esc_attr('Set Mobile Screen Size', 'ppsgen'); ?>"><span class="dashicons dashicons-smartphone"></span></div>
          <div class="ppsgenCfbSizeChangerBtn ppsgenCfbSizeChangerBtnTab" title="<?php echo esc_attr('Set Tablet Screen Size', 'ppsgen'); ?>"><span class="dashicons dashicons-tablet"></span></div>
          <div class="ppsgenCfbSizeChangerBtn ppsgenCfbSizeChangerBtnActive ppsgenCfbSizeChangerBtnPc" title="<?php echo esc_attr('Set Desktop Screen Size', 'ppsgen'); ?>"><span class="dashicons dashicons-desktop"></span></div>
        </div>
      </div>

      <div class="ppsgenCfbFieldsWrapper">
        <?php if (!empty($fields)) {
          foreach ($fields as $field) {
            $settings = $field['settings'];
            $key = esc_attr($settings['field_key'] ?? '');
            $type = esc_attr($settings['field_type'] ?? '');
            $id = esc_attr($settings['field_id'] ?? 0);
            $slug = esc_attr($settings['field_slug'] ?? '');
            $label = esc_attr($settings['field_label'] ?? ($settings['label'] ?? ''));

            $colMob = intval($settings['col_mob'] ?? 12);
            $colTab = intval($settings['col_tab'] ?? 6);
            $colPc = intval($settings['col_pc'] ?? 6);

            $html = '<div';
            $html .= ' data-field-type="' . esc_attr($type) . '"';
            $html .= ' data-field-id="' . esc_attr($id) . '"';
            $html .= ' data-field-slug="' . esc_attr($slug) . '"';
            $html .= ' data-field-label="' . esc_attr($label) . '"';
            $html .= ' data-field-key="' . esc_attr($key) . '"';
            $html .= ' class="' . esc_attr(sprintf('ppsgen-cf-col ppsgen-cf-type-%s ppsgen-col-pc-%d', $type, (int) $colPc)) . '"';
            $html .= '>';
            if ($type !== 'empty') {
              $html .= esc_html($type . ($slug ? ' - ' . $slug : ''));
            } else {
              $html .= '-';
            }
            $html .= '</div>';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->getFrame()->core('helper')->wpKses($html);
          }
        } ?>
      </div>
      <div class="ppsgenCffOptionContainerSubtitle">
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e('Click and hold the mouse button to drag and change the order of fields inside the container.', 'ppsgen'); ?>
        <?php esc_html_e('Click on a field you added to display its options in the right panel.', 'ppsgen'); ?>
      </div>
      <div class="ppsgenCffOptionContainerSubtitle">
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e('Contact fields can be added only once. Special fields can be added an unlimited number of times.', 'ppsgen'); ?>
      </div>
    </div>
  </div>
  <div class="ppsgenFormFieldsRight" style="display:none;">
    <div class="ppsgenComponentsList ppsgenCffOptionContainer">
      <div class="ppsgenCffOptionContainerTitle"><?php esc_html_e('Field Options', 'ppsgen'); ?></div>
      <div class="ppsgenCffOptionContainerSubtitle">
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e('You can override the selected field options below. Changes will apply only to this Form.', 'ppsgen'); ?>
      </div>

      <?php $component->hidden('field_key'); ?>
      <?php $component->hidden('field_type'); ?>
      <?php $component->hidden('field_id'); ?>
      <?php $component->hidden('order_index'); ?>

      <div class="ppsgenCffActionBtns">
        <div class="ppsgenCffDuplicateBtn"><span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Duplicate Selected Field', 'ppsgen'); ?></div>
        <div class="ppsgenCffDeleteBtn"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected Field', 'ppsgen'); ?></div>
      </div>

      <?php
        $html = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.edit.fields_html.options', $options);
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $this->getFrame()->core('helper')->wpKses($html);
?>

    </div>

  </div>
</div>
