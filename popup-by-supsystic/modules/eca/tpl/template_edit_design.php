<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$design = !empty($options['design']) ? $options['design'] : '';
$fields = !empty($options['fields']) ? $options['fields'] : '';
$prefix = 'data[template]';
$sprefix = 'data[template][design]';
$component->setNamePrefix($sprefix);
?>
<div class="ppsgenComponentsList">
  <h3><?php esc_html_e('Design Options', 'ppsgen'); ?></h3>
  <p><?php esc_html_e('Warning! Color may be automatically inverted between dark and light tones depending on the recipient’s email client or device settings.', 'ppsgen'); ?></p>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Background', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->colorpicker('[template_email_background]', ['value' => $component->value($design['template_email_background'] ?? null, 'rgba(255,255,255,1)')]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Background', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->colorpicker('[template_container_background]', ['value' => $component->value($design['template_container_background'] ?? null, 'rgba(255,255,255,1)')]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Position', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[template_container_position]', [
        'options' => [
          'left' => 'Left',
          'center' => 'Center',
          'right' => 'Right',
        ],
        'class' => '',
        'selected' => $component->value($design['template_container_position'] ?? null, 'center'),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Template Font Family', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[template_font_family]', ['options' => $component->getEmailFonts(true), 'class' => '', 'selected' => $component->value($design['template_font_family'] ?? null, 'Default')]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Text Position', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[template_text_position]', [
        'options' => [
          'left' => 'Left',
          'center' => 'Center',
          'right' => 'Right',
        ],
        'class' => '',
        'selected' => $component->value($design['template_text_position'] ?? null, 'center'),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Padding (inner indent)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('top', 'ppsgen');
      $component->number('[template_padding][top]', ['value' => $component->value($design['template_padding']['top'] ?? null, '10'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[template_padding][top_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_padding']['top_unit'] ?? null, 'px')]);
      ?>
      <?php
      esc_html_e('right', 'ppsgen');
      $component->number('[template_padding][right]', ['value' => $component->value($design['template_padding']['right'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[template_padding][right_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_padding']['right_unit'] ?? null, 'px')]);
      ?>
      <?php
      esc_html_e('bottom', 'ppsgen');
      $component->number('[template_padding][bottom]', ['value' => $component->value($design['template_padding']['bottom'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[template_padding][bottom_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_padding']['bottom_unit'] ?? null, 'px')]);
      ?>
      <?php
      esc_html_e('left', 'ppsgen');
      $component->number('[template_padding][left]', ['value' => $component->value($design['template_padding']['left'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[template_padding][left_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_padding']['left_unit'] ?? null, 'px')]);
      ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Margin (outer indent)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('top', 'ppsgen');
      $component->number('[template_margin][top]', ['value' => $component->value($design['template_margin']['top'] ?? null, '10'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[template_margin][top_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_margin']['top_unit'] ?? null, 'px')]);
      ?>
      <?php
      esc_html_e('right', 'ppsgen');
      $component->number('[template_margin][right]', ['value' => $component->value($design['template_margin']['right'] ?? null, '10'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[template_margin][right_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_margin']['right_unit'] ?? null, 'px')]);
      ?>
      <?php
      esc_html_e('bottom', 'ppsgen');
      $component->number('[template_margin][bottom]', ['value' => $component->value($design['template_margin']['bottom'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[template_margin][bottom_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_margin']['bottom_unit'] ?? null, 'px')]);
      ?>
      <?php
      esc_html_e('left', 'ppsgen');
      $component->number('[template_margin][left]', ['value' => $component->value($design['template_margin']['left'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[template_margin][left_unit]', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_margin']['left_unit'] ?? null, 'px')]);
      ?>
    </div>
  </div>


  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Width', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[template_width_select]', [
        'options' => ['manual' => 'Manual', 'auto' => 'Auto'],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['template_width_select'] ?? null, 'manual'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[template_width_select]" data-toggle-val="manual">
        <?php $component->number('[template_width]', ['value' => $component->value($design['template_width'] ?? null, '100'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
        <?php $component->select('[template_width_unit]', ['options' => ['percent' => '%'], 'class' => '', 'selected' => $component->value($design['template_width_unit'] ?? null, 'percent')]); ?>
      </span>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Max-width', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[template_max_width_select]', [
        'options' => ['auto' => 'Auto', 'manual' => 'Manual'],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['template_max_width_select'] ?? null, 'auto'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[template_max_width_select]" data-toggle-val="manual">
        <?php $component->number('[template_max_width]', ['value' => $component->value($design['template_max_width'] ?? null, '100'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
        <?php $component->select('[template_max_width_unit]', ['options' => $component->getTableUnits(), 'class' => '', 'selected' => $component->value($design['template_max_width_unit'] ?? null, 'percent')]); ?>
      </span>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Border', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[template_border]', ['value' => $component->value($design['template_border'] ?? null, '1'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[template_border_unit]', ['options' => $component->getTableUnits(), 'class' => '', 'selected' => $component->value($design['template_border_unit'] ?? null, 'px')]); ?>
      <?php $component->select('[template_border_style]', [
        'options' => ['solid' => 'solid', 'dotted' => 'dotted', 'dashed' => 'dashed', 'double' => 'double', 'groove' => 'groove', 'ridge' => 'ridge', 'inset' => 'inset', 'outset' => 'outset', 'none' => 'none', 'hidden' => 'hidden'],
        'class' => '',
        'selected' => $component->value($design['template_border_style'] ?? null, 'solid'),
      ]); ?>
      <?php $component->colorpicker('[template_border_color]', ['value' => $component->value($design['template_border_color'] ?? null, '#ffffff')]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Container Font Styles', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[template_font_size]', ['value' => $component->value($design['template_font_size'] ?? null, '14'), 'attrs' => 'title="Fonst Size" min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[template_font_size_unit]', ['options' => $component->getTableFontUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_font_size_unit'] ?? null, 'px')]); ?>
      <?php $component->number('[template_font_lh_size]', ['value' => $component->value($design['template_font_lh_size'] ?? null, '18'), 'attrs' => 'title="Line Height" min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[template_font_lh_size_unit]', ['options' => $component->getTableFontUnits(), 'class' => 'ppsgenSW55', 'selected' => $component->value($design['template_font_lh_size_unit'] ?? null, 'px')]); ?>
      <?php $component->colorpicker('[template_font_color]', ['value' => $component->value($design['template_font_color'] ?? '#000000')]); ?>
      <?php $component->select('[template_font_style]', ['options' => $component->getFontStyles(), 'class' => '', 'selected' => $component->value($design['template_font_style'] ?? null, 'normal')]); ?>
      <?php $component->select('[template_font_weight]', ['options' => $component->getFontWeight(), 'class' => '', 'selected' => $component->value($design['template_font_weight'] ?? null, 'normal')]); ?>
    </div>
  </div>

</div>