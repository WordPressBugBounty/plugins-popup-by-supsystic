<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$fields = !empty($options['fields']) ? $options['fields'] : '';
$prefix = 'data[popup]';
$sprefix = 'data[popup][fields]';
$component->setNamePrefix('');
$element_types = [
  'row' => esc_html__('Row', 'ppsgen'),
  'column' => esc_html__('Column', 'ppsgen'),
  'title' => esc_html__('Title', 'ppsgen'),
  'textarea' => esc_html__('Textarea (Editor)', 'ppsgen'),
  'iframe' => esc_html__('Iframe', 'ppsgen'),
  'button' => esc_html__('Button', 'ppsgen'),
  'icon' => esc_html__('Icon', 'ppsgen'),
  'svg_icon' => esc_html__('SVG Icon', 'ppsgen'),
  'divider' => esc_html__('Divider', 'ppsgen'),
];
$gridAlignContentOptions = [
  '' => '',
  'start' => __('Start – Align content to the start of the container', 'ppsgen'),
  'end' => __('End – Align content to the end of the container', 'ppsgen'),
  'center' => __('Center – Center the content vertically', 'ppsgen'),
  'stretch' => __('Stretch – Stretch content to fill the container', 'ppsgen'),
  'space-between' => __('Space Between – Equal space between rows, first and last at edges', 'ppsgen'),
  'space-around' => __('Space Around – Equal space around each row', 'ppsgen'),
  'space-evenly' => __('Space Evenly – Equal space between and around all rows', 'ppsgen'),
];
$gridAlignContentOptions = [
  '' => '',
  'start' => __('Start – Align content to the start of the container', 'ppsgen'),
  'end' => __('End – Align content to the end of the container', 'ppsgen'),
  'center' => __('Center – Center the content vertically', 'ppsgen'),
  'stretch' => __('Stretch – Stretch content to fill the container', 'ppsgen'),
  'space-between' => __('Space Between – Equal space between rows, first and last at edges', 'ppsgen'),
  'space-around' => __('Space Around – Equal space around each row', 'ppsgen'),
  'space-evenly' => __('Space Evenly – Equal space between and around all rows', 'ppsgen'),
];
$gridJustifyContentOptions = [
  '' => '',
  'start' => __('Start – Align content to the left of the container', 'ppsgen'),
  'end' => __('End – Align content to the right of the container', 'ppsgen'),
  'center' => __('Center – Center the content horizontally', 'ppsgen'),
  'stretch' => __('Stretch – Stretch content to fill the horizontal space', 'ppsgen'),
  'space-between' => __('Space Between – Equal space between columns, first and last at edges', 'ppsgen'),
  'space-around' => __('Space Around – Equal space around each column', 'ppsgen'),
  'space-evenly' => __('Space Evenly – Equal space between and around all columns', 'ppsgen'),
];
$gridAlignItemsOptions = [
  '' => '',
  'start' => __('Start – Align item to the top of the cell', 'ppsgen'),
  'end' => __('End – Align item to the bottom of the cell', 'ppsgen'),
  'center' => __('Center – Center item vertically in the cell', 'ppsgen'),
  'stretch' => __('Stretch – Stretch item to fill vertical cell space', 'ppsgen'),
];
$gridJustifyItemsOptions = [
  '' => '',
  'start' => __('Start – Align item to the left of the cell', 'ppsgen'),
  'end' => __('End – Align item to the right of the cell', 'ppsgen'),
  'center' => __('Center – Center item horizontally in the cell', 'ppsgen'),
  'stretch' => __('Stretch – Stretch item to fill horizontal cell space', 'ppsgen'),
];
if ($this->getFrame()->useModule('form')) {
  $element_types['form'] = esc_html__('Supsystic Form', 'ppsgen');
  $formsList = $this->getFrame()->getModule('form')->getModel()->getAllFormsList(true);
  $formsList = ['' => ''] + $formsList;
}
$parent_types = ['row', 'column'];
?>

<div class="ppsgenPopupFields">
  <div class="ppsgenPopupFieldsLeft">
    <div class="ppsgenCfbContainer">
      <div class="ppsgenCfbAddFieldWrapper">
        <div class="ppsgenCfbAddFieldLabel"><?php esc_html_e('Choose Element', 'ppsgen'); ?></div>
        <select class="ppsgenCfbAddFieldList ppsgenCfbAddOtherFieldList">
          <?php if (!empty($element_types)) {
            foreach ($element_types as $type => $type_name) {
              echo '<option data-field-type="' . esc_attr($type) . '" value="">' . esc_html($type_name) . '</option>';
            }
          } ?>
        </select>
        <div class="ppsgenCfbAddFieldBtn ppsgenCfbAddOtherFieldBtn"><?php esc_html_e('Add to Popup', 'ppsgen'); ?></div>
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
          $tree = $this->popupEditBuildFieldsTree($fields);
          foreach ($tree as $field) {
            $html = $this->popupEditRenderField($field, $parent_types);
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
    </div>
  </div>

  <div class="ppsgenPopupFieldsRight" style="display:none;">
    <div class="ppsgenComponentsList ppsgenCffOptionContainer">
      <div class="ppsgenCffOptionContainerTitle"><?php esc_html_e('Elements Options', 'ppsgen'); ?></div>
      <div class="ppsgenCffOptionContainerSubtitle" style="display:none;">
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e('You can override the selected field options below. Changes will apply only to this Popup.', 'ppsgen'); ?>
      </div>

      <?php $component->hidden('field_key'); ?>
      <?php $component->hidden('field_type'); ?>
      <?php $component->hidden('field_id'); ?>
      <?php $component->hidden('parent_key'); ?>
      <?php $component->hidden('order_index'); ?>

      <div class="ppsgenCffActionBtns">
        <div class="ppsgenCffDuplicateBtn"><span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Duplicate Selected Element', 'ppsgen'); ?></div>
        <div class="ppsgenCffDeleteBtn"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected Element', 'ppsgen'); ?></div>
      </div>

      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentListTitle"><?php esc_html_e('Display Settings', 'ppsgen'); ?></div>
      </div>

      <div class="ppsgenComponentRow">
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

      <div class="ppsgenComponentListTabBtns">
        <div class="ppsgenComponentListTabBtn ppsgenComponentListTabBtnActive" data-tab="general"><?php esc_html_e('General Settings', 'ppsgen'); ?></div>
        <div class="ppsgenComponentListTabBtn" data-tab="design"><?php esc_html_e('Design & Styles', 'ppsgen'); ?></div>
      </div>

      <div class="ppsgenComponentListTab ppsgenComponentListTabActive" data-tab="general">

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentListTitle"><?php esc_html_e('General Settings', 'ppsgen'); ?></div>
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

        <div class="ppsgenComponentRow" data-field-type-hide="divider">
          <div class="ppsgenComponentListTitle"><?php esc_html_e('Specific Element Settings', 'ppsgen'); ?></div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Display Type', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('grid_type', ['options' => ['' => __('Default', 'ppsgen'), 'grid' => __('Grid', 'ppsgen'), 'flex' => __('Flex', 'ppsgen')], 'class' => 'data-option-toggle-trigger', 'selected' => '']); ?>
          </div>
        </div>

        <div data-toggle-name="grid_type" data-toggle-val="flex">
          <div class="ppsgenComponentRow" data-field-type="row, column">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Flex Direction', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->select('grid_flex_direction', [
      'options' => ['row' => __('Row', 'ppsgen'), 'column' => __('Column', 'ppsgen'), 'row-reverse' => __('Row Reverse', 'ppsgen'), 'column-reverse' => __('Column Reverse', 'ppsgen')],
      'class' => '',
      'selected' => 'row',
    ]); ?>
            </div>
          </div>
        </div>

        <div data-toggle-name="grid_type" data-toggle-val="flex">
          <div class="ppsgenComponentRow" data-field-type="row, column">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Flex Wrap', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->select('grid_flex_wrap', ['options' => ['wrap' => __('Wrap', 'ppsgen'), 'nowrap' => __('No Wrap', 'ppsgen')], 'class' => '', 'selected' => 'wrap']); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Align Content', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('grid_align_content', ['options' => $gridAlignContentOptions, 'class' => '', 'selected' => 'px']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Justify Content', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('grid_justify_content', ['options' => $gridJustifyContentOptions, 'class' => '', 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Align Items', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('grid_align_items', ['options' => $gridAlignItemsOptions, 'class' => '', 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Justify Items', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('grid_justify_items', ['options' => $gridJustifyItemsOptions, 'class' => '', 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Gap (indent between inner elements)', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php
$component->number('gap', ['value' => '5', 'attrs' => 'class="ppsgenW60"']);
$component->select('gap_unit', ['options' => $component->getUnits(), 'class' => '', 'selected' => 'px']);
?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="title">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title Text', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('title_text'); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="textarea">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Textarea Content', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php wp_editor('', 'html_textarea_content', [
  'textarea_name' => 'html_textarea_content',
  'textarea_rows' => 6,
  'media_buttons' => true,
  'wpautop' => false,
  'teeny' => false,
  'tinymce' => true,
  'quicktags' => true,
]); ?>
          </div>
        </div>

        <!-- Iframe Specific Options -->
        <div class="ppsgenComponentRow" data-field-type="iframe">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Iframe URL', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enter the URL for the iframe content (e.g., YouTube embed, Google Maps).', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('iframe_url', ['attrs' => 'placeholder="https://"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="iframe">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Iframe Responsive', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('When enabled, the iframe becomes responsive and adapts to the screen size & ratio.', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->checkboxToggle('iframe_responsive');?>
          </div>
        </div>

        <!-- Button Specific Options -->
        <div class="ppsgenComponentRow" data-field-type="button">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Button Text', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('button_text', ['attrs' => 'placeholder="Click Me"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="button">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link URL', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('button_url', ['attrs' => 'placeholder="https://"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="button">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Target', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('button_target', ['options' => ['' => '', '_self' => __('Same Tab', 'ppsgen'), '_blank' => __('New Tab', 'ppsgen')], 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="button">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Rel', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('button_rel', [
              'options' => [
                '' => '',
                'follow' => __('Follow', 'ppsgen'),
                'nofollow' => __('No Follow', 'ppsgen'),
                'nofollow sponsored' => __('No Follow + Sponsored', 'ppsgen'),
                'follow sponsored' => __('Follow + Sponsored', 'ppsgen'),
              ],
              'selected' => '',
            ]); ?>
          </div>
        </div>

        <!-- Form Specific Options -->
        <?php if ($this->getFrame()->useModule('form')) { ?>
        <div class="ppsgenComponentRow" data-field-type="form">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select the ppsgen form to embed.', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('form_id', ['options' => $formsList]); ?>
          </div>
        </div>
        <?php } ?>

        <!-- Icon Specific Options -->
        <!-- Icon Specific Options -->
        <div class="ppsgenComponentRow" data-field-type="icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Icon Class', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
            'Enter the CSS class for the icon (e.g., fa-star for Font Awesome, dashicons-heart for Dashicons).',
            'ppsgen',
          ); ?>"></div>
          <div class="ppsgenComponentOptionInline" style="width:100%;">
            <?php $component->text('icon_class', ['attrs' => 'class="ppsgenIconInput" style="width:100%; max-width:300px;"']); ?>
            <div class="ppsgenShowIconBtn" data-target="ppsgenIconInput"><?php esc_html_e('Show Icons List', 'ppsgen'); ?></div>
          </div>
        </div>

        <!-- Icon Specific Options -->
        <div class="ppsgenComponentRow" data-field-type="svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SVG Icon', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline" style="width:100%;">
            <?php $component->text('svg_icon', ['attrs' => 'class="ppsgenSvgIconInput" style="width:100%; max-width:300px;"']); ?>
            <div class="ppsgenShowUndrIconBtn" data-target="ppsgenSvgIconInput"><?php esc_html_e('Show Icons List', 'ppsgen'); ?></div>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SVG Icon Color', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline" style="width:100%;">
            <?php $component->colorpicker('svg_icon_color', ['value' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="icon, svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link URL', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('icon_url', ['attrs' => 'placeholder="https://"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="icon, svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('icon_title', ['attrs' => 'placeholder="' . __('Follow on Instagram', 'ppsgen') . '"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="icon, svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Target', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('icon_target', ['options' => ['' => '', '_self' => __('Same Tab', 'ppsgen'), '_blank' => __('New Tab', 'ppsgen')], 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="icon, svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Rel', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('icon_rel', [
                      'options' => [
                        '' => '',
                        'follow' => __('Follow', 'ppsgen'),
                        'nofollow' => __('No Follow', 'ppsgen'),
                        'nofollow sponsored' => __('No Follow + Sponsored', 'ppsgen'),
                        'follow sponsored' => __('Follow + Sponsored', 'ppsgen'),
                      ],
                      'selected' => '',
                    ]); ?>
          </div>
        </div>

      </div>

      <div class="ppsgenComponentListTab" data-tab="design">

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentListTitle"><?php esc_html_e('Design & Styles', 'ppsgen'); ?></div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Padding (inner indent)', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php
                      esc_html_e('top', 'ppsgen');
$component->number('padding_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_top_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('right', 'ppsgen');
$component->number('padding_right', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_right_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('bottom', 'ppsgen');
$component->number('padding_bottom', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_bottom_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('left', 'ppsgen');
$component->number('padding_left', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_left_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Margin (outer indent)', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('top', 'ppsgen');
$component->number('margin_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_top_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('right', 'ppsgen');
$component->number('margin_right', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_right_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('bottom', 'ppsgen');
$component->number('margin_bottom', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_bottom_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('left', 'ppsgen');
$component->number('margin_left', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_left_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="title, row, column, button">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Text Align', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('title_text_align', ['options' => $component->getTextAlign()]); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Width', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->select('width_select', [
'options' => ['manual' => 'Manual', 'auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit'],
'class' => 'data-option-toggle-trigger',
'selected' => 'manual',
          ]); ?>
            <span class="ppsgenComponentOptionInline" data-toggle-name="width_select" data-toggle-val="manual">
              <?php $component->number('width', ['value' => '100', 'attrs' => 'min="0" class="ppsgenW60"']); ?>
              <?php $component->select('width_unit', ['options' => $component->getUnits(), 'class' => '', 'selected' => 'percent']); ?>
            </span>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-width', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->select('max_width_select', [
'options' => ['auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit', 'manual' => 'Manual'],
'class' => 'data-option-toggle-trigger',
'selected' => 'auto',
          ]); ?>
            <span class="ppsgenComponentOptionInline" data-toggle-name="max_width_select" data-toggle-val="manual">
              <?php $component->number('max_width', ['value' => '100', 'attrs' => 'min="0" class="ppsgenW60"']); ?>
              <?php $component->select('max_width_unit', ['options' => $component->getUnits(), 'class' => '', 'selected' => 'percent']); ?>
            </span>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Height', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->select('height_select', [
'options' => ['manual' => 'Manual', 'auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit'],
'class' => 'data-option-toggle-trigger',
'selected' => 'manual',
          ]); ?>
            <span class="ppsgenComponentOptionInline" data-toggle-name="height_select" data-toggle-val="manual">
              <?php $component->number('height', ['value' => '100', 'attrs' => 'min="0" class="ppsgenW60"']); ?>
              <?php $component->select('height_unit', ['options' => $component->getUnits(), 'class' => '', 'selected' => 'percent']); ?>
            </span>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-height', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->select('max_height_select', [
'options' => ['auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit', 'manual' => 'Manual'],
'class' => 'data-option-toggle-trigger',
'selected' => 'auto',
          ]); ?>
            <span class="ppsgenComponentOptionInline" data-toggle-name="max_height_select" data-toggle-val="manual">
              <?php $component->number('max_height', ['value' => '100', 'attrs' => 'min="0" class="ppsgenW60"']); ?>
              <?php $component->select('max_height_unit', ['options' => $component->getUnits(), 'class' => '', 'selected' => 'percent']); ?>
            </span>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->number('border', ['value' => '0', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
              <?php $component->select('border_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']); ?>
              <?php $component->select('border_style', [
  'options' => ['solid' => 'solid', 'dotted' => 'dotted', 'dashed' => 'dashed', 'double' => 'double', 'groove' => 'groove', 'ridge' => 'ridge', 'inset' => 'inset', 'outset' => 'outset', 'none' => 'none', 'hidden' => 'hidden'],
  'class' => 'ppsgenSWI90',
  'selected' => 'solid',
]); ?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('border_color', ['value' => '']); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border Radius', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->number('border_radius', ['value' => '0', 'attrs' => 'min="0" class="ppsgenW60"']); ?>
            <?php $component->select('border_radius_unit', ['options' => $component->getUnits(), 'class' => '', 'selected' => 'px']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Overflow', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->select('overflow', [
'options' => ['' => '', 'auto' => 'Auto', 'visible' => 'Visible', 'hidden ' => 'Hidden', 'scroll' => 'Scroll'],
'class' => '',
'selected' => '',
          ]); ?>
            <?php esc_html_e('X', 'ppsgen'); ?>
            <?php $component->select('overflow_x', [
'options' => ['' => '', 'auto' => 'Auto', 'visible' => 'Visible', 'hidden ' => 'Hidden', 'scroll' => 'Scroll'],
'class' => '',
'selected' => '',
          ]); ?>
            <?php esc_html_e('Y', 'ppsgen'); ?>
            <?php $component->select('overflow_y', [
'options' => ['' => '', 'auto' => 'Auto', 'visible' => 'Visible', 'hidden ' => 'Hidden', 'scroll' => 'Scroll'],
'class' => '',
'selected' => '',
          ]); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Background', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('background', ['value' => '']); ?> <?php esc_html_e('Default', 'ppsgen'); ?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('background_hover', ['value' => '']); ?> <?php esc_html_e('Hover', 'ppsgen'); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Box Shadow', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('top', 'ppsgen');
$component->number('box_shadow_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('box_shadow_top_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('right', 'ppsgen');
$component->number('box_shadow_right', ['value' => '0', 'attrs' => ' class="ppsgenSWI55"']);
$component->select('box_shadow_right_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('bottom', 'ppsgen');
$component->number('box_shadow_bottom', ['value' => '0', 'attrs' => ' class="ppsgenSWI55"']);
$component->select('box_shadow_bottom_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('left', 'ppsgen');
$component->number('box_shadow_left', ['value' => '0', 'attrs' => ' class="ppsgenSWI55"']);
$component->select('box_shadow_left_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('box_shadow_color', ['value' => '']); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Text Shadow', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php
$component->number('text_shadow_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('text_shadow_top_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
$component->number('text_shadow_right', ['value' => '0', 'attrs' => ' class="ppsgenSWI55"']);
$component->select('text_shadow_right_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php
          $component->number('text_shadow_left', ['value' => '0', 'attrs' => ' class="ppsgenSWI55"']);
$component->select('text_shadow_left_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php $component->colorpicker('text_shadow_color', ['value' => '']); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Font Styles', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->fontFamilySelect('font_family', ['class' => 'ppsgenSWI160', 'selected' => 'Default']); ?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->number('font_size', ['value' => '14', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
              <?php $component->select('font_size_unit', ['options' => $component->getUnits(), 'class' => 'ppsgenSWI55']); ?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('font_color', ['value' => '']); ?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->select('font_style', ['options' => $component->getFontStyles(), 'class' => 'ppsgenSWI120']); ?>
              <?php $component->select('font_weight', ['options' => $component->getFontWeight(), 'class' => 'ppsgenSWI120']); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Styles', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('link_color', ['value' => '']); ?> <?php esc_html_e('Default Link Color', 'ppsgen'); ?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('link_hover_color', ['value' => '']); ?> <?php esc_html_e('Color on Hover', 'ppsgen'); ?>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

</div>

<div id="ppsgenIconOverlay" style="display:none;">
  <div class="ppsgenIconOverlayBg"></div>
  <div class="ppsgenIconBox">
    <div class="ppsgenIconHeader">
      <input type="text" class="ppsgenIconSearch" placeholder="<?php esc_attr_e('Search icons...', 'ppsgen'); ?>">
      <div class="ppsgenIconClose">&times;</div>
    </div>
    <div class="ppsgenIconTabs">
      <div data-tab="solid" class="active"><?php esc_html_e('Solid', 'ppsgen'); ?></div>
      <div data-tab="brands"><?php esc_html_e('Brands', 'ppsgen'); ?></div>
      <div data-tab="regular"><?php esc_html_e('Regular', 'ppsgen'); ?></div>
    </div>
    <div class="ppsgenIconContent">
      <div class="ppsgenIconTab" data-tab="solid">
        <div class="ppsgenIconGrid"></div>
      </div>
      <div class="ppsgenIconTab" data-tab="brands" style="display:none;">
        <div class="ppsgenIconGrid"></div>
      </div>
      <div class="ppsgenIconTab" data-tab="regular" style="display:none;">
        <div class="ppsgenIconGrid"></div>
      </div>
    </div>
  </div>
</div>

<div id="ppsgenIconSvgOverlay" style="display:none;">
  <div class="ppsgenIconSvgOverlayBg"></div>
  <div class="ppsgenIconSvgBox">
    <div class="ppsgenIconSvgHeader">
      <input type="text" class="ppsgenIconSvgSearch" placeholder="<?php esc_attr_e('Search icons...', 'ppsgen'); ?>">
      <div class="ppsgenIconSvgClose">&times;</div>
    </div>
    <div class="ppsgenIconSvgTabs">
      <div data-tab="undraw" class="active"><?php esc_html_e('Undraw', 'ppsgen'); ?></div>
    </div>
    <div class="ppsgenIconSvgContent">
      <div class="ppsgenIconSvgTab" data-tab="undraw">
        <div class="ppsgenIconSvgGrid"></div>
      </div>
    </div>
    <div class="ppsgenIconSvgPagination"></div>
  </div>
</div>
