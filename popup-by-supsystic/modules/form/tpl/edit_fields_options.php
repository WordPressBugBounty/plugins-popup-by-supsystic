
<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->getFrame()->core('component');
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$fields = !empty($options['fields']) ? $options['fields'] : '';
$prefix = 'data[form]';
$sprefix = 'data[form][fields]';
$component->setNamePrefix('');
?>

     <div class="ppsgenComponentRow" data-field-type-hide="submit, description, divider, empty, title, captcha">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Slug', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('field_slug', ['attrs' => 'readonly']); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="submit, description, divider, empty, title, hidden, captcha">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Required', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('required'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="divider">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Columns (from 1 to 12)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'You have 3 screen sizes available: mobile, tablet, and desktop. There are 12 columns in each row. Choose how many columns the field will occupy for each screen size, from 1 to 12.',
  'ppsgen',
); ?>"></div>
        <div class="ppsgenComponentOptionInline">
          <?php
          esc_html_e('Mobile', 'ppsgen');
          $component->select('col_mob', ['options' => $component->getColumns()]);
          ?>
          <?php
          esc_html_e('Tablet', 'ppsgen');
          $component->select('col_tab', ['options' => $component->getColumns()]);
          ?>
          <?php
          esc_html_e('Desktop', 'ppsgen');
          $component->select('col_pc', ['options' => $component->getColumns()]);
          ?>
        </div>
      </div>

      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show Field Screen Rules', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Hide or show field for specific type of screen.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOptionInline">
          <?php
          esc_html_e('Mobile', 'ppsgen');
          $component->checkboxToggle('show_mob');
          ?>
          <?php
          esc_html_e('Tablet', 'ppsgen');
          $component->checkboxToggle('show_tab');
          ?>
          <?php
          esc_html_e('Desktop', 'ppsgen');
          $component->checkboxToggle('show_pc');
          ?>
        </div>
      </div>

      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Class', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('class'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="submit">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('ID', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('id'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="submit, description, divider, empty, title, captcha">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Label', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('label'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="submit, description, divider, empty, title, captcha">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide label', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('hide_label'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="text, textarea, email, phone, number">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Placeholder', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('placeholder'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="submit, description, divider, empty, title, captcha">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Show icon', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('show_tooltip'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="submit, description, divider, empty, title, captcha">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Text', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('title'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="title">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title Text', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('title_text'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="title">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title Text Align', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('text_align', ['options' => $component->getTextAlign()]); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="captcha">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Captcha Align in Container', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('captcha_align', ['options' => $component->getTextAlign()]); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="description">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Description Text', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php wp_editor('', 'html_description_text', [
            'textarea_name' => 'html_description_text',
            'wpautop' => false,
            'textarea_rows' => 6,
            'media_buttons' => true,
            'teeny' => false,
            'tinymce' => true,
            'quicktags' => true,
          ]); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="submit">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Placeholder Text', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('submit_text'); ?>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="text, textarea, email, phone, number, hidden">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Predefined value', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('predefined_value'); ?>
        </div>
      </div>

      <div style="display:none;">
        <div class="ppsgenComponentOptionTemplate ppsgenComponentOptionInline ppsgenComponentRowMBT5" >
          <div class="ppsgenComponentRow ppsgenComponentRowMB0">
            <div class="ppsgenComponentOptionsListBtnWrapper">
              <div class="ppsgenComponentOptionsListBtn ppsgenComponentOptionsListBtnSort ui-sortable-handle"><span class="dashicons dashicons-sort"></span></div>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowMB0">
            <div class="ppsgenComponentOption">
              <?php $component->text('options[@@OPTNUMBER@@][value]', ['value' => '', 'attrs' => 'readonly placeholder="' . esc_attr__('Value', 'ppsgen') . '" ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowMB0">
            <div class="ppsgenComponentOption">
              <?php $component->text('options[@@OPTNUMBER@@][label]', ['value' => '', 'attrs' => 'placeholder="' . esc_attr__('Label', 'ppsgen') . '" ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowMB0">
            <div class="ppsgenComponentLabel">
              <span title="<?php ppsgen_esc_attr('Checked/selected by default', 'ppsgen'); ?>" class="dashicons dashicons-yes-alt" style="color:#2196f3"></span>
            </div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('options[@@OPTNUMBER@@][checked]', ['checked' => '', 'class' => '']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowMB0">
            <div class="ppsgenComponentLabel">
              <span title="<?php ppsgen_esc_attr('Disabled by default', 'ppsgen'); ?>" class="dashicons dashicons-dismiss" style="color:#2196f3"></span>
            </div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('options[@@OPTNUMBER@@][disabled]', ['checked' => '', 'class' => '']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow ppsgenComponentRowMB0">
            <div class="ppsgenComponentLabel">
              <span title="<?php ppsgen_esc_attr('Remove option from Form', 'ppsgen'); ?>" class="dashicons dashicons-trash" style="color:#2196f3"></span>
            </div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('options[@@OPTNUMBER@@][remove]', ['checked' => '', 'class' => '']); ?>
            </div>
          </div>
        </div>
      </div>
      
      <div class="ppsgenComponentRow" data-field-type="select, radio, checkbox">
        <div class="ppsgenComponentOptionsList"></div>
      </div>