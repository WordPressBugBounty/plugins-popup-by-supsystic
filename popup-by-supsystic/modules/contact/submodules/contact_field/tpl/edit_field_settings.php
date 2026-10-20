
<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->getFrame()->core('component');
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : [];
$objId = $options['obj_id'];
$prefix = 'data[contact_field]';
$sprefix = 'data[contact_field][settings]';
$component->setNamePrefix($sprefix);
?>

          <div class="ppsgenComponentRow ppsgenComponentRowDashed" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, password, textarea, email, phone, number, radio, select, checkbox">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Required', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('You can change this attribute in specific Form.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption" title="<?php esc_attr_e('This field is available for editing in the form.', 'ppsgen'); ?>">
              <?php $component->checkboxToggle('[required]', ['checked' => $component->value($settings['required'] ?? null, '1'), 'class' => '']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowDashed" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, password, textarea, email, phone, number">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Placeholder', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('You can change this attribute in specific Form.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption" title="<?php esc_attr_e('This field is available for editing in the form.', 'ppsgen'); ?>">
              <?php $component->text('[placeholder]', ['value' => $component->value($settings['placeholder'] ?? null), 'attrs' => 'min="0" max="255" ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowDashed" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, password, textarea, email, phone, number, radio, select, checkbox">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Icon', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Show tooltip icon at left side of field with title content.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption" title="<?php esc_attr_e('This field is available for editing in the form.', 'ppsgen'); ?>">
              <?php $component->checkboxToggle('[show_tooltip]', ['checked' => $component->value($settings['show_tooltip'] ?? null), 'class' => '']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowDashed" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, password, textarea, email, phone, number, radio, select, checkbox">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Text', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'The tooltip title attribute will fade in if the input value doesn\'t match the pattern. You can dynamically change this attribute within a specific form.',
  'ppsgen',
); ?>"></div>
            <div class="ppsgenComponentOption" title="<?php esc_attr_e('This field is available for editing in the form.', 'ppsgen'); ?>">
              <?php $component->text('[title]', ['value' => $component->value($settings['title'] ?? null), 'attrs' => 'min="0" max="255" ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, password, textarea, email, phone">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Min Length', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Minimum number of characters allowed.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->number('[min_length]', ['value' => $component->value($settings['min_length'] ?? null, '0'), 'attrs' => 'min="0" max="255" ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, password, email, phone">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max Length', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Maximum number of characters allowed.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->number('[max_length]', ['value' => $component->value($settings['max_length'] ?? null, '255'), 'attrs' => 'min="1" max="255" ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="number, range, rating">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Min Number', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Minimum number.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->number('[min]', ['value' => $component->value($settings['min'] ?? null)]); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="number, range, rating">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max Number', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Maximum number.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->number('[max]', ['value' => $component->value($settings['max'] ?? null)]); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="textarea">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max Length', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Maximum number of characters allowed.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->number('[max_length_textarea]', ['value' => $component->value($settings['max_length_textarea'] ?? 9999999), 'attrs' => 'min="1" max="9999999" ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="password, text, textarea">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field validation pattern', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Regular expression to validate input format.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->text('[pattern]', [
                'value' => $this->getFrame()
                  ->core('helper')
                  ->backslashDecode($component->value($settings['pattern'] ?? '')),
              ]); ?>
              <div class="ppsgenComponentOptionNoticeUnder"><?php esc_html_e('For example: ^[a-zA-Z0-9_]{3,16}$  -  Only letters, numbers, and underscores. 3-16 characters.', 'ppsgen'); ?></div>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, phone">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field mask', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'Input format mask (e.g., phone, date, card, zip). Support client-server side validation.',
  'ppsgen',
); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->text('[mask]', ['value' => $component->value($settings['mask'] ?? null)]); ?>
              <div class="ppsgenComponentOptionNoticeUnder">
                <div><?php esc_html_e('9 — Numeric (0-9)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('a — Latin letter (a-z or A-Z)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('A — Uppercase Latin letter (A-Z)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('l — Lowercase Latin letter (a-z)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('c — Lowercase Cyrillic letter (а-я, ё)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('C — Uppercase Cyrillic letter (А-Я, Ё)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('k — Any Cyrillic letter (upper/lower)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('u — Any Unicode letter (any language)', 'ppsgen'); ?></div>
                <div><?php esc_html_e('* — Latin or Cyrillic letters + digits', 'ppsgen'); ?></div>
                <div><?php esc_html_e('& — Uppercase Latin/Cyrillic letters + digits', 'ppsgen'); ?></div>
                <div><?php esc_html_e('# — Any letter, digit or symbol (!@#$%^&*())', 'ppsgen'); ?></div>
                <div><?php esc_html_e('Examples: ****-****, 99.99.9999, +42(099)999-99-99', 'ppsgen'); ?></div>
              </div>
            </div>
          </div>
          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, textarea, email, phone, number">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Autocomplete', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Suggest previously entered values in the browser.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('[autocomplete]', ['checked' => $component->value($settings['autocomplete'] ?? null, ''), 'class' => '']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowDashed" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="text, textarea, email, phone, number, hidden">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Predefined Value', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('You can change this attribute in specific Form.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption" title="<?php esc_attr_e('This field is available for editing in the form.', 'ppsgen'); ?>">
              <?php $component->text('[predefined_value]', ['value' => $component->value($settings['predefined_value'] ?? null, ''), 'attrs' => 'min="0" max="255" ']); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="textarea">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Rows', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Number of visible text lines.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->text('[rows]', ['value' => $component->value($settings['rows'] ?? null, '')]); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="textarea" >
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Resize', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Textarea resize options.', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->select('[resize]', [
                'options' => ['both' => esc_attr__('Both', 'ppsgen'), 'horizontal' => esc_attr__('Horizontal', 'ppsgen'), 'vertical' => esc_attr__('Vertical', 'ppsgen'), 'none' => esc_attr__('None', 'ppsgen')],
                'class' => '',
                'selected' => $component->value($settings['resize'] ?? null, ''),
              ]); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="select">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Use custom SELECT', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('[custom_select]', ['checked' => $component->value($settings['custom_select'] ?? null, ''), 'class' => '']); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="checkbox, radio">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Horizontal style', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('[horizontal_style]', ['checked' => $component->value($settings['horizontal_style'] ?? null, ''), 'class' => '']); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="select">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Multiple', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('[multiple_select]', ['checked' => $component->value($settings['multiple_select'] ?? null, ''), 'class' => 'data-option-toggle-trigger']); ?>
            </div>
          </div>

          <?php $component->setNamePrefix($prefix); ?>

          <div class="ppsgenComponentRow ppsgenComponentRowDashed" data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" data-toggle-val="checkbox, radio, select">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Select options', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption" title="<?php esc_attr_e('This options is available for editing in the form.', 'ppsgen'); ?>">

              <div class="ppsgenComponentOptionTemplate ppsgenComponentOptionInline ppsgenComponentRowMBT5">
                <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                  <div class="ppsgenComponentOptionsListBtnWrapper">
                    <div class="ppsgenComponentOptionsListBtn ppsgenComponentOptionsListBtnAdd"><span class="dashicons dashicons-plus"></span></div>
                  </div>
                </div>
                <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                  <div class="ppsgenComponentOption">
                    <?php $component->text('[options][@@OPTNUMBER@@][value]', ['value' => '', 'attrs' => 'placeholder="' . esc_attr__('Value', 'ppsgen') . '" ']); ?>
                  </div>
                </div>
                <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                  <div class="ppsgenComponentOption">
                    <?php $component->text('[options][@@OPTNUMBER@@][label]', ['value' => '', 'attrs' => 'placeholder="' . esc_attr__('Label', 'ppsgen') . '" ']); ?>
                  </div>
                </div>
                <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                  <div class="ppsgenComponentLabel">
                    <span title="<?php ppsgen_esc_attr('Checked/selected by default', 'ppsgen'); ?>" class="dashicons dashicons-yes-alt" style="color:#2196f3"></span>
                  </div>
                  <div class="ppsgenComponentOption">
                    <?php $component->checkboxToggle('[options][@@OPTNUMBER@@][checked]', ['checked' => '', 'class' => '']); ?>
                  </div>
                </div>
                <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                  <div class="ppsgenComponentLabel">
                    <span title="<?php ppsgen_esc_attr('Disabled by default', 'ppsgen'); ?>" class="dashicons dashicons-dismiss" style="color:#2196f3"></span>
                  </div>
                  <div class="ppsgenComponentOption">
                    <?php $component->checkboxToggle('[options][@@OPTNUMBER@@][disabled]', ['checked' => '', 'class' => '']); ?>
                  </div>
                </div>
                <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                  <div class="ppsgenComponentOptionsListBtnWrapper">
                  </div>
                </div>
              </div>

              <div class="ppsgenComponentOptionsList">
                <?php if (!empty($options['options'])) { ?>
                  <?php foreach ($options['options'] as $key => $option) { ?>
                  <?php $keyRand = wp_rand('1', '4294967295'); ?>
                  <?php $keyRand = $key . $keyRand; ?>
                  <div class="ppsgenComponentOptionInline ppsgenComponentRowMBT5">
                    <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                      <div class="ppsgenComponentOptionsListBtnWrapper">
                        <div class="ppsgenComponentOptionsListBtn ppsgenComponentOptionsListBtnSort"><span class="dashicons dashicons-sort"></span></div>
                      </div>
                    </div>
                    <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                      <div class="ppsgenComponentOption">
                        <?php $component->text('[options][' . $key . '][value]', ['value' => $component->value($option['value'] ?? null), 'attrs' => 'placeholder="' . esc_attr__('Value', 'ppsgen') . '" ']); ?>
                      </div>
                    </div>
                    <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                      <div class="ppsgenComponentOption">
                        <?php $component->text('[options][' . $key . '][label]', ['value' => $component->value($option['label'] ?? null), 'attrs' => 'placeholder="' . esc_attr__('Label', 'ppsgen') . '" ']); ?>
                      </div>
                    </div>
                    <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                      <div class="ppsgenComponentLabel">
                        <span title="<?php ppsgen_esc_attr('Checked/selected by default', 'ppsgen'); ?>" class="dashicons dashicons-yes-alt" style="color:#2196f3"></span>
                      </div>
                      <div class="ppsgenComponentOption">
                        <?php $component->checkboxToggle('[options][' . $key . '][checked]', ['checked' => $component->value($option['checked'] ?? null), 'class' => '']); ?>
                      </div>
                    </div>
                    <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                      <div class="ppsgenComponentLabel">
                        <span title="<?php ppsgen_esc_attr('Disabled by default', 'ppsgen'); ?>" class="dashicons dashicons-dismiss" style="color:#2196f3"></span>
                      </div>
                      <div class="ppsgenComponentOption">
                        <?php $component->checkboxToggle('[options][' . $key . '][disabled]', ['checked' => $component->value($option['disabled'] ?? null), 'class' => '']); ?>
                      </div>
                    </div>
                    <div class="ppsgenComponentRow ppsgenComponentRowMB0">
                      <div class="ppsgenComponentOptionsListBtnWrapper">
                        <div class="ppsgenComponentOptionsListBtn ppsgenComponentOptionsListBtnDelete"><span class="dashicons dashicons-trash"></span></div>
                      </div>
                    </div>
                  </div>
                  <?php } ?>
                <?php } ?>
              </div>

            </div>
          </div>
