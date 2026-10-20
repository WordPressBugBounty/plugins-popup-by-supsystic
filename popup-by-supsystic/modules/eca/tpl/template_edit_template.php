<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$fields = !empty($options['fields']) ? $options['fields'] : '';
$prefix = 'data[template]';
$component->setNamePrefix('');
$element_types = [
  'row' => esc_html__('Step 1. Row', 'ppsgen'),
  'column' => esc_html__('Step 2. Column', 'ppsgen'),
  'email_content' => esc_html__('Step 3. Email Step/Campaign Content', 'ppsgen'),
  'unsubscribe' => esc_html__('Step 3. Unsubscribe Link', 'ppsgen'),
  'title' => esc_html__('Step 3. Title', 'ppsgen'),
  'textarea' => esc_html__('Step 3. Textarea (Editor)', 'ppsgen'),
  'image' => esc_html__('Step 3. Image', 'ppsgen'),
  // 'svg_icon' => esc_html__('Step 3. SVG Icon', 'ppsgen'),
  'button' => esc_html__('Step 3. Button', 'ppsgen'),
  'pro' => esc_html__('Step 3. Posts (PRO)', 'ppsgen'),
  'woo_categories' => esc_html__('Step 3. Woo Categories', 'ppsgen'),
  'woo_tags' => esc_html__('Step 3. Woo Tags', 'ppsgen'),
  'woo_attributes' => esc_html__('Step 3. Woo Attributes', 'ppsgen'),
  'woo_products' => esc_html__('Step 3. Woo Products', 'ppsgen'),
];
$parent_types = ['row', 'column'];
?>

<div class="ppsgenTemplateFields">
  <div class="ppsgenTemplateFieldsLeft">
    <div class="ppsgenCfbContainer">
      <div class="ppsgenCfbAddFieldWrapper">
        <div class="ppsgenCfbAddFieldLabel"><?php esc_html_e('Choose Element', 'ppsgen'); ?></div>
        <select class="ppsgenCfbAddFieldList ppsgenCfbAddOtherFieldList">
          <?php if (!empty($element_types)) {
            foreach ($element_types as $type => $type_name) {
              $disabledAttr = in_array($type, ['woo_categories', 'woo_tags', 'woo_attributes', 'woo_products'], true) ? ' disabled="disabled"' : '';
              echo '<option data-field-type="' . esc_attr($type) . '" value=""' . $disabledAttr . '>' . esc_html($type_name) . '</option>';
            }
          } ?>
        </select>
        <div class="ppsgenCfbAddFieldBtn ppsgenCfbAddOtherFieldBtn"><?php esc_html_e('Add to Template', 'ppsgen'); ?></div>
      </div>

      <div class="ppsgenCfbFieldsWrapper">
        <?php if (!empty($fields)) {
          $tree = $this->templateEditBuildFieldsTree($fields);
          foreach ($tree as $field) {
            $html = $this->templateEditRenderField($field, $parent_types);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->getFrame()->core('helper')->wpKses($html);
          }
        } ?>
      </div>
      <div class="ppsgenCffOptionContainerSubtitle">
        <div><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Preview only: Elements are shown as an imitation of email tables for layout purposes and are not the final design.', 'ppsgen'); ?></div>
        <div><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Click and hold the mouse button to drag and change the order of fields inside the container. Row -> Column -> (Other Fields)', 'ppsgen'); ?></div>
        <div><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Click on a field you added to display its options in the right panel.', 'ppsgen'); ?></div>
      </div>
    </div>
  </div>

  <div class="ppsgenTemplateFieldsRight" style="display:none;">
    <div class="ppsgenComponentsList ppsgenCffOptionContainer">
      <div class="ppsgenCffOptionContainerTitle"><?php esc_html_e('Elements Options', 'ppsgen'); ?></div>
      <div class="ppsgenCffOptionContainerSubtitle" style="display:none;">
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e('You can override the selected field options below. Changes will apply only to this Template.', 'ppsgen'); ?>
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
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Width', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOptionInline">
          <?php $component->select('width_select', [
              'options' => ['manual' => 'Manual', 'auto' => 'Auto'],
              'class' => 'data-option-toggle-trigger',
              'selected' => 'auto',
            ]); ?>
          <span class="ppsgenComponentOptionInline" data-toggle-name="width_select" data-toggle-val="manual">
            <?php $component->number('width', ['value' => '100', 'attrs' => 'step=".1" min="0" max="100" class="ppsgenW60"']); ?>

            <span class="ppsgenComponentOptionInline">
              <?php $component->select('width_unit', ['options' => ['percent' => '%'], 'class' => '', 'selected' => 'percent']); ?>
            </span>

          </span>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type-hide="column">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-width', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOptionInline">
          <?php $component->select('max_width_select', [
              'options' => ['auto' => 'Auto', 'manual' => 'Manual'],
              'class' => 'data-option-toggle-trigger',
              'selected' => 'auto',
            ]); ?>
          <span class="ppsgenComponentOptionInline" data-toggle-name="max_width_select" data-toggle-val="manual">
            <?php $component->number('max_width', ['value' => '100', 'attrs' => 'step=".1" min="0" class="ppsgenW60"']); ?>
            <?php $component->select('max_width_unit', ['options' => $component->getTableUnits(), 'class' => '', 'selected' => 'percent']); ?>
          </span>
        </div>
      </div>

      <div class="ppsgenComponentRow" data-field-type="title, button, textarea, image">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Display Type', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOptionInline">
          <?php $component->select('display_type', ['options' => ['block' => 'block', 'inline-block' => 'inline-block', 'inline' => 'inline'], 'selected' => 'block']); ?>
        </div>
      </div>

      <div class="ppsgenComponentListTabBtns">
        <div class="ppsgenComponentListTabBtn ppsgenComponentListTabBtnActive" data-tab="general"><?php esc_html_e('General Settings', 'ppsgen'); ?></div>
        <div class="ppsgenComponentListTabBtn" data-tab="design"><?php esc_html_e('Design & Styles', 'ppsgen'); ?></div>
      </div>

      <div class="ppsgenComponentListTab ppsgenComponentListTabActive" data-tab="general">

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentListTitle"><?php esc_html_e('Specific Element Settings', 'ppsgen'); ?></div>
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

        <div class="ppsgenComponentRow" data-field-type="svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link URL', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('icon_url', ['attrs' => 'placeholder="https://"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('icon_title', ['attrs' => 'placeholder="' . __('Follow on Instagram', 'ppsgen') . '"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="svg_icon">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Target', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('icon_target', ['options' => ['' => '', '_self' => __('Same Tab', 'ppsgen'), '_blank' => __('New Tab', 'ppsgen')], 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="svg_icon">
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

        <div class="ppsgenComponentRow" data-field-type="column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Vertical Align Inside Container', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('vertical_align', ['options' => ['' => '', 'top' => __('Top', 'ppsgen'), 'middle' => __('Middle', 'ppsgen'), 'bottom' => __('Bottom', 'ppsgen')], 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Horizontal Align Inside Container', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('horizontal_align', ['options' => ['' => '', 'left' => __('Left', 'ppsgen'), 'center' => __('Center', 'ppsgen'), 'right' => __('Right', 'ppsgen')], 'selected' => '']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Text/Img/Svg Align', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('text_align', ['options' => $component->getTextAlign()]); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="title">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title Tag', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('title_tag', ['options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'h5' => 'h5', 'h6' => 'h6'], 'selected' => 'h1']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="title">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title Text', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('title_text'); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="unsubscribe">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Unsubscribe Text', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('unsubscribe_text'); ?>
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

        <div class="ppsgenComponentRow" data-field-type="image">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Image SRC', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enter the Source URL for the image.', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenEcaImageSrcPicker">
              <?php $component->text('image_src', ['attrs' => 'placeholder="https://" class="ppsgen-media-upload ppsgenEcaImageSrcInput"']); ?>
              <button type="button" class="ppsgen-media-upload-btn ppsgenEcaImageSrcMediaBtn">
                <span class="dashicons dashicons-format-image"></span>
              </button>
              <span class="ppsgenEcaImageSrcPreviewWrap" aria-hidden="true">
                <img class="ppsgenEcaImageSrcPreview" alt="" />
              </span>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="image">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('image_title', ['attrs' => 'placeholder="' . __('Follow on Instagram', 'ppsgen') . '"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="image">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link URL', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('(Optional) Enter the redirect URL after click on image.', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->text('image_url', ['attrs' => 'placeholder="https://"']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="image">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Target', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <?php $component->select('image_target', ['options' => ['' => '', '_self' => __('Same Tab', 'ppsgen'), '_blank' => __('New Tab', 'ppsgen')], 'selected' => '']); ?>
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

      </div>

      <div class="ppsgenComponentListTab" data-tab="design">

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentListTitle"><?php esc_html_e('Design & Styles', 'ppsgen'); ?></div>
        </div>

        <div class="ppsgenComponentRow" data-field-type-hide="row">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Padding (inner indent)', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php
              esc_html_e('top', 'ppsgen');
$component->number('padding_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_top_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('right', 'ppsgen');
$component->number('padding_right', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_right_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('bottom', 'ppsgen');
$component->number('padding_bottom', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_bottom_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('left', 'ppsgen');
$component->number('padding_left', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_left_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type-hide="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Margin (outer indent)', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('top', 'ppsgen');
$component->number('margin_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_top_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('right', 'ppsgen');
$component->number('margin_right', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_right_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php
esc_html_e('bottom', 'ppsgen');
$component->number('margin_bottom', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_bottom_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
              <?php
esc_html_e('left', 'ppsgen');
$component->number('margin_left', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_left_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->number('border', ['value' => '0', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
              <?php $component->select('border_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']); ?>
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

        <div class="ppsgenComponentRow" data-field-type-hide="row, column">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border Radius', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->number('border_radius', ['value' => '0', 'attrs' => 'min="0" class="ppsgenW60"']); ?>
            <?php $component->select('border_radius_unit', ['options' => $component->getTableUnits(), 'class' => '', 'selected' => 'px']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Background', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('background', ['value' => '']); ?> <?php esc_html_e('Default', 'ppsgen'); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="title, button, textarea, unsubscribe">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Font Family', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOptionInline">
            <?php $component->select('font_family', ['options' => $component->getEmailFonts(true), 'class' => '', 'selected' => 'default']); ?>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="title, button, textarea, unsubscribe">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Font Styles', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php esc_html_e('Size', 'ppsgen'); ?>
              <?php $component->number('font_size', ['value' => '14', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
              <?php $component->select('font_size_unit', ['options' => $component->getTableFontUnits(), 'class' => 'ppsgenSWI55']); ?>
            </div>
            <div class="ppsgenComponentOptionInline">
              <?php esc_html_e('Line Height', 'ppsgen'); ?>
              <?php $component->number('font_lh', ['value' => '18', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
              <?php $component->select('font_lh_unit', ['options' => $component->getTableFontUnits(), 'class' => 'ppsgenSWI55']); ?>
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

        <div class="ppsgenComponentRow" data-field-type="textarea">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Color', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->colorpicker('link_color', ['value' => '']); ?>
            </div>
          </div>
        </div>

        <div class="ppsgenComponentRow" data-field-type="textarea, button, unsubscribe">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Text Decoration', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <div class="ppsgenComponentOptionInline">
              <?php $component->select('link_text_decoration', ['options' => $component->getTextDecoration(), 'class' => 'ppsgenSWI120']); ?>
            </div>
          </div>
        </div>

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
