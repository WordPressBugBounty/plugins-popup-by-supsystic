<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$design = !empty($options['design']) ? $options['design'] : '';
$fields = !empty($options['fields']) ? $options['fields'] : '';
$prefix = 'data[form]';
$sprefix = 'data[form][design]';
$component->setNamePrefix($sprefix);
?>
<div class="ppsgenComponentsList">
  <h3><?php esc_html_e('Design Options', 'ppsgen'); ?></h3>
  <h4><?php esc_html_e('Form ', 'ppsgen'); ?></h4>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Margin (outer indent)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('top', 'ppsgen');
      $component->number('[form_margin][top]', ['value' => $component->value($design['form_margin']['top'] ?? null, '0'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[form_margin][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('right', 'ppsgen');
      $component->number('[form_margin][right]', ['value' => $component->value($design['form_margin']['right'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_margin][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('bottom', 'ppsgen');
      $component->number('[form_margin][bottom]', ['value' => $component->value($design['form_margin']['bottom'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_margin][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['bottom_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('left', 'ppsgen');
      $component->number('[form_margin][left]', ['value' => $component->value($design['form_margin']['left'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_margin][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['left_unit'] ?? null, 'px'),
      ]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Padding (inner indent)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('top', 'ppsgen');
      $component->number('[form_padding][top]', ['value' => $component->value($design['form_padding']['top'] ?? null, '10'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[form_margin][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('right', 'ppsgen');
      $component->number('[form_padding][right]', ['value' => $component->value($design['form_padding']['right'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_margin][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('bottom', 'ppsgen');
      $component->number('[form_padding][bottom]', ['value' => $component->value($design['form_padding']['bottom'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_margin][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['bottom_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('left', 'ppsgen');
      $component->number('[form_padding][left]', ['value' => $component->value($design['form_padding']['left'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_margin][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_margin']['left_unit'] ?? null, 'px'),
      ]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form Fields Gap', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[form_gap]', ['value' => $component->value($design['form_gap'] ?? null, '16'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[form_gap_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_gap_unit'] ?? null, 'px'),
      ]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form Container Align', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_contaner_align]', [
        'options' => ['left' => 'left', 'center' => 'center', 'right' => 'right'],
        'class' => '',
        'selected' => $component->value($design['form_contaner_align'] ?? null, 'left'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Width', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_width_select]', [
        'options' => ['manual' => 'Manual', 'auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit'],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['form_width_select'] ?? null, 'manual'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_width_select]" data-toggle-val="manual">
        <?php $component->number('[form_width]', ['value' => $component->value($design['form_width'] ?? null, '100'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
        <?php $component->select('[form_width_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['form_width_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-width', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_max_width_select]', [
        'options' => ['auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit', 'manual' => 'Manual'],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['form_max_width_select'] ?? null, 'auto'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_max_width_select]" data-toggle-val="manual">
        <?php $component->number('[form_max_width]', ['value' => $component->value($design['form_max_width'] ?? null, '100'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
        <?php $component->select('[form_max_width_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['form_max_width_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Height', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_height_select]', [
        'options' => ['manual' => 'Manual', 'auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit'],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['form_height_select'] ?? null, 'manual'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_height_select]" data-toggle-val="manual">
        <?php $component->number('[form_height]', ['value' => $component->value($design['form_height'] ?? null, '100'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
        <?php $component->select('[form_height_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['form_height_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-height', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_max_height_select]', [
        'options' => ['auto' => 'Auto', 'initial' => 'Initial', 'inherit' => 'Inherit', 'manual' => 'Manual'],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['form_max_height_select'] ?? null, 'auto'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_max_height_select]" data-toggle-val="manual">
        <?php $component->number('[form_max_height]', ['value' => $component->value($design['form_max_height'] ?? null, '100'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
        <?php $component->select('[form_max_height_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['form_max_height_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[form_border]', ['value' => $component->value($design['form_border'] ?? null, '1'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[form_border_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['form_border_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->select('[form_border_style]', [
        'options' => [
          'solid' => 'solid',
          'dotted' => 'dotted',
          'dashed' => 'dashed',
          'double' => 'double',
          'groove' => 'groove',
          'ridge' => 'ridge',
          'inset' => 'inset',
          'outset' => 'outset',
          'none' => 'none',
          'hidden' => 'hidden',
        ],
        'class' => '',
        'selected' => $component->value($design['form_border_style'] ?? null, 'solid'),
      ]); ?>
      <?php $component->colorpicker('[form_border_color]', ['value' => $component->value($design['form_border_color'] ?? null, '#dddddd')]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border Radius', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[form_border_radius]', ['value' => $component->value($design['form_border_radius'] ?? null, '4'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[form_border_radius_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['form_border_radius_unit'] ?? null, 'px'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Overflow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_overflow]', [
        'options' => ['' => '', 'auto' => 'Auto', 'visible' => 'Visible', 'hidden ' => 'Hidden', 'scroll' => 'Scroll'],
        'class' => '',
        'selected' => $component->value($design['form_overflow'] ?? null, ''),
      ]); ?>
      <?php esc_html_e('Overflow-X', 'ppsgen'); ?>
      <?php $component->select('[form_overflow_x]', [
        'options' => ['' => '', 'auto' => 'Auto', 'visible' => 'Visible', 'hidden ' => 'Hidden', 'scroll' => 'Scroll'],
        'class' => '',
        'selected' => $component->value($design['form_overflow_x'] ?? null, ''),
      ]); ?>
      <?php esc_html_e('Overflow-Y', 'ppsgen'); ?>
      <?php $component->select('[form_overflow_y]', [
        'options' => ['' => '', 'auto' => 'Auto', 'visible' => 'Visible', 'hidden ' => 'Hidden', 'scroll' => 'Scroll'],
        'class' => '',
        'selected' => $component->value($design['form_overflow_y'] ?? null, ''),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel"><?php esc_html_e('Background Type', 'ppsgen'); ?></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_bg_type]', [
        'options' => [
          'color' => esc_attr__('Color', 'ppsgen'),
          'image' => esc_attr__('Image', 'ppsgen'),
        ],
        'class' => 'ppsgenSW120 data-option-toggle-trigger',
        'selected' => $component->value($design['form_bg_type'] ?? null, 'color'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_bg_type]" data-toggle-val="color">
    <div class="ppsgenComponentLabel"><?php esc_html_e('Background Color', 'ppsgen'); ?></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->colorpicker('[form_background]', [
        'value' => $component->value($design['form_background'] ?? null, '#ffffff'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_bg_type]" data-toggle-val="image">
    <div class="ppsgenComponentLabel"><?php esc_html_e('Background Image', 'ppsgen'); ?></div>
    <div class="ppsgenComponentOption">
      <div style="margin-bottom:4px;">
        <?php ppsgen_render_background_image_picker(
          $component,
          '[form_bg_image]',
          $component->value($design['form_bg_image'] ?? null, '')
        ); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->select('[form_bg_size]', [
          'options' => [
            'auto' => 'auto',
            'cover' => 'cover',
            'contain' => 'contain',
          ],
          'selected' => $component->value($design['form_bg_size'] ?? null, 'cover'),
        ]); ?>
        <?php $component->select('[form_bg_repeat]', [
          'options' => [
            'no-repeat' => 'no-repeat',
            'repeat' => 'repeat',
            'repeat-x' => 'repeat-x',
            'repeat-y' => 'repeat-y',
          ],
          'selected' => $component->value($design['form_bg_repeat'] ?? null, 'no-repeat'),
        ]); ?>
        <?php $component->select('[form_bg_position]', [
          'options' => [
            'center center' => 'center',
            'top center' => 'top',
            'bottom center' => 'bottom',
            'left center' => 'left',
            'right center' => 'right',
          ],
          'selected' => $component->value($design['form_bg_position'] ?? null, 'center center'),
        ]); ?>
      </div>
      <div style="margin:4px 0px; max-width: 120px;">
        <div class="ppsgenComponentOptionInline">
          <div class="ppsgenComponentOptionNoticeUnder" style="width: 85px;"><?php esc_html_e('Opacity', 'ppsgen'); ?></div>
          <?php $component->number('[form_bg_opacity]', [
            'value' => $component->value($design['form_bg_opacity'] ?? null, '100'),
            'attrs' => 'min="0" max="100" class="ppsgenW60"',
          ]); ?>
          <span>%</span>
        </div>
      </div>
      <div style="margin:4px 0px; max-width:295px;">
        <div class="ppsgenComponentOptionInline">
          <div class="ppsgenComponentOptionNoticeUnder" style="width: 110px;"><?php esc_html_e('Overlay Image Color', 'ppsgen'); ?></div>
          <?php $component->colorpicker('[form_bg_img_color]', [
            'value' => $component->value($design['form_bg_img_color'] ?? null, 'rgba(255, 255, 255, 0.2)'),
          ]); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Background Backdrop Filter', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[form_bg_filter]', [
        'options' => $component->backdropFilterUnits(),
        'class' => 'ppsgenSW80 data-option-toggle-trigger',
        'selected' => $component->value($design['form_bg_filter'] ?? null, ''),
      ]); ?>
      <?php $component->number('[form_bg_filter_size]', [
        'value' => $component->value($design['form_bg_filter_size'] ?? null, '0'),
        'attrs' => 'min="0" max="100" placeholder="" class="ppsgenW60"',
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_bg_filter]" data-toggle-val="blur">
        <?php esc_html_e('from 0 to 10 in px', 'ppsgen'); ?>
      </span>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_bg_filter]" data-toggle-val="brightness, contrast, grayscale, invert, opacity, sepia, saturate,">
        <?php esc_html_e('from 0 to 100 in %', 'ppsgen'); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Box Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[form_box_shadow][top]', ['value' => $component->value($design['form_box_shadow']['top'] ?? null, '0'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[form_box_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['form_box_shadow']['top_unit']) ? $component->value($design['form_box_shadow']['top_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[form_box_shadow][right]', ['value' => $component->value($design['form_box_shadow']['right'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_box_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['form_box_shadow']['right_unit']) ? $component->value($design['form_box_shadow']['right_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[form_box_shadow][bottom]', ['value' => $component->value($design['form_box_shadow']['bottom'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_box_shadow][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['form_box_shadow']['bottom_unit']) ? $component->value($design['form_box_shadow']['bottom_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[form_box_shadow][left]', ['value' => $component->value($design['form_box_shadow']['left'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_box_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['form_box_shadow']['left_unit']) ? $component->value($design['form_box_shadow']['left_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php $component->colorpicker('[form_box_shadow_color]', ['value' => $component->value($design['form_box_shadow_color'] ?? null)]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Text Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[form_text_shadow][top]', ['value' => $component->value($design['form_text_shadow']['top'] ?? null, '0'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[form_text_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['form_text_shadow']['top_unit']) ? $component->value($design['form_text_shadow']['top_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[form_text_shadow][right]', ['value' => $component->value($design['form_text_shadow']['right'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_text_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['form_text_shadow']['right_unit']) ? $component->value($design['form_text_shadow']['right_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[form_text_shadow][left]', ['value' => $component->value($design['form_text_shadow']['left'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[form_text_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['form_text_shadow']['left_unit']) ? $component->value($design['form_text_shadow']['left_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php $component->colorpicker('[form_text_shadow_color]', ['value' => $component->value($design['form_text_shadow_color'] ?? null)]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Font ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[form_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['form_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[form_font_size]', ['value' => $component->value($design['form_font_size'] ?? null, '14'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[form_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['form_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[form_font_color]', ['value' => $component->value($design['form_font_color'] ?? '#000000')]); ?>
      <?php $component->select('[form_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['form_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[form_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['form_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form Select Icon Color', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->colorpicker('[form_select_icon_color]', ['value' => $component->value($design['form_select_icon_color'] ?? null, '#333')]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form Title Field ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[ftitle_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['ftitle_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[ftitle_font_size]', ['value' => $component->value($design['ftitle_font_size'] ?? null, '21'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[ftitle_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['ftitle_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[ftitle_font_color]', ['value' => $component->value($design['ftitle_font_color'] ?? null, '#000000')]); ?>
      <?php $component->select('[ftitle_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['ftitle_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[ftitle_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['ftitle_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <h4><?php esc_html_e('Field', 'ppsgen'); ?></h4>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Text ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[finput_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['finput_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[finput_font_size]', ['value' => $component->value($design['finput_font_size'] ?? null, '14'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[finput_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[finput_font_color]', ['value' => $component->value($design['finput_font_color'] ?? null, '#000000')]); ?>
      <?php $component->select('[finput_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['finput_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[finput_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['finput_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Placeholder ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[fplace_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['fplace_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[fplace_font_size]', ['value' => $component->value($design['fplace_font_size'] ?? null, '14'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[fplace_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['fplace_font_size_unit']) ? $component->value($design['fplace_font_size_unit'] ?? null) : 'px',
      ]); ?>
      <?php $component->colorpicker('[fplace_font_color]', ['value' => $component->value($design['fplace_font_color'] ?? null, '#919191')]); ?>
      <?php $component->select('[fplace_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['fplace_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[fplace_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['fplace_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Padding', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('top', 'ppsgen');
      $component->number('[finput_padding][top]', ['value' => $component->value($design['finput_padding']['top'] ?? null, '5'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[finput_padding][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_padding']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('right', 'ppsgen');
      $component->number('[finput_padding][right]', ['value' => $component->value($design['finput_padding']['right'] ?? null, '5'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_padding][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_padding']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('bottom', 'ppsgen');
      $component->number('[finput_padding][bottom]', ['value' => $component->value($design['finput_padding']['bottom'] ?? null, '5'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_padding][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_padding']['bottom_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('left', 'ppsgen');
      $component->number('[finput_padding][left]', ['value' => $component->value($design['finput_padding']['left'] ?? null, '5'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_padding][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_padding']['left_unit'] ?? null, 'px'),
      ]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Border', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[finput_border]', ['value' => $component->value($design['finput_border'] ?? null, '1'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[finput_border_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['finput_border_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->select('[finput_border_style]', [
        'options' => [
          'solid' => 'solid',
          'dotted' => 'dotted',
          'dashed' => 'dashed',
          'double' => 'double',
          'groove' => 'groove',
          'ridge' => 'ridge',
          'inset' => 'inset',
          'outset' => 'outset',
          'none' => 'none',
          'hidden' => 'hidden',
        ],
        'class' => '',
        'selected' => $component->value($design['finput_border_style'] ?? null, 'solid'),
      ]); ?>
      <?php $component->colorpicker('[finput_border_color]', ['value' => $component->value($design['finput_border_color'] ?? null, '#c5c5c5')]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Border Radius', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[finput_border_radius]', ['value' => $component->value($design['finput_border_radius'] ?? null, '4'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[finput_border_radius_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['finput_border_radius_unit'] ?? null, 'px'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Background', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->colorpicker('[finput_background]', ['value' => $component->value($design['finput_background'] ?? null, '#f5f5f5')]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Box Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[finput_box_shadow][top]', ['value' => $component->value($design['finput_box_shadow']['top'] ?? null, '0'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[finput_box_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_box_shadow']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[finput_box_shadow][right]', ['value' => $component->value($design['finput_box_shadow']['right'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_box_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_box_shadow']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[finput_box_shadow][bottom]', ['value' => $component->value($design['finput_box_shadow']['bottom'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_box_shadow][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_box_shadow']['bottom_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[finput_box_shadow][left]', ['value' => $component->value($design['finput_box_shadow']['left'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_box_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_box_shadow']['left_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php $component->colorpicker('[finput_box_shadow_color]', ['value' => $component->value($design['finput_box_shadow_color'] ?? null)]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Input Text Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[finput_text_shadow][top]', ['value' => $component->value($design['finput_text_shadow']['top'] ?? null, '0'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[finput_text_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_text_shadow']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[finput_text_shadow][right]', ['value' => $component->value($design['finput_text_shadow']['right'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_text_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_text_shadow']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[finput_text_shadow][left]', ['value' => $component->value($design['finput_text_shadow']['left'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[finput_text_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['finput_text_shadow']['left_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php $component->colorpicker('[finput_text_shadow_color]', ['value' => $component->value($design['finput_text_shadow_color'] ?? null)]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Error Text ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[ferror_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['ferror_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[ferror_font_size]', ['value' => $component->value($design['ferror_font_size'] ?? null, '14'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[ferror_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['ferror_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[ferror_font_color]', ['value' => $component->value($design['ferror_font_color'] ?? null, '#dd0000')]); ?>
      <?php $component->select('[ferror_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['ferror_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[ferror_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['ferror_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Label Font ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[label_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['label_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[label_font_size]', ['value' => $component->value($design['label_font_size'] ?? null, '14'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[label_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['label_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[label_font_color]', ['value' => $component->value($design['label_font_color'] ?? null, '#000000')]); ?>
      <?php $component->select('[label_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['label_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[label_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['label_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Required Symbol ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[requiredicon_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['requiredicon_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[requiredicon_font_size]', ['value' => $component->value($design['requiredicon_font_size'] ?? 14), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[requiredicon_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['requiredicon_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[requiredicon_font_color]', ['value' => $component->value($design['requiredicon_font_color'] ?? null, '#dd3333')]); ?>
      <?php $component->select('[requiredicon_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['requiredicon_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[requiredicon_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['requiredicon_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <h4><?php esc_html_e('Submit Button ', 'ppsgen'); ?></h4>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Button Text ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[submit_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['submit_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[submit_font_size]', ['value' => $component->value($design['submit_font_size'] ?? null, '14'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[submit_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php
      esc_html_e('default', 'ppsgen');
      $component->colorpicker('[submit_font_color]', ['value' => $component->value($design['submit_font_color'] ?? null, '#333333')]);
      ?>
      <?php
      esc_html_e('hover', 'ppsgen');
      $component->colorpicker('[submit_font_color_hover]', ['value' => $component->value($design['submit_font_color_hover'] ?? null, '#000000')]);
      ?>
      <?php $component->select('[submit_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['submit_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[submit_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['submit_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Button Padding', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('top', 'ppsgen');
      $component->number('[submit_padding][top]', ['value' => $component->value($design['submit_padding']['top'] ?? null, '10'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[submit_padding][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_padding']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('right', 'ppsgen');
      $component->number('[submit_padding][right]', ['value' => $component->value($design['submit_padding']['right'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_padding][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_padding']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('bottom', 'ppsgen');
      $component->number('[submit_padding][bottom]', ['value' => $component->value($design['submit_padding']['bottom'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_padding][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_padding']['bottom_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('left', 'ppsgen');
      $component->number('[submit_padding][left]', ['value' => $component->value($design['submit_padding']['left'] ?? null, '10'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_padding][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_padding']['left_unit'] ?? null, 'px'),
      ]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Button Border', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[submit_border]', ['value' => $component->value($design['submit_border'] ?? null, '1'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[submit_border_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['submit_border_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->select('[submit_border_style]', [
        'options' => [
          'solid' => 'solid',
          'dotted' => 'dotted',
          'dashed' => 'dashed',
          'double' => 'double',
          'groove' => 'groove',
          'ridge' => 'ridge',
          'inset' => 'inset',
          'outset' => 'outset',
          'none' => 'none',
          'hidden' => 'hidden',
        ],
        'class' => '',
        'selected' => $component->value($design['submit_border_style'] ?? null, 'solid'),
      ]); ?>
      <?php
      esc_html_e('default', 'ppsgen');
      $component->colorpicker('[submit_border_color]', ['value' => $component->value($design['submit_border_color'] ?? null, '#d5d5d5')]);
      ?>
      <?php
      esc_html_e('hover', 'ppsgen');
      $component->colorpicker('[submit_border_color_hover]', ['value' => $component->value($design['submit_border_color_hover'] ?? null, '#eeeeee')]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Button Border Radius', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[submit_border_radius]', ['value' => $component->value($design['submit_border_radius'] ?? null, '1'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[submit_border_radius_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['submit_border_radius_unit'] ?? null, 'px'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Button Background', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('default', 'ppsgen');
      $component->colorpicker('[submit_background]', ['value' => $component->value($design['submit_background'] ?? null, '#eeeeee')]);
      ?>
      <?php
      esc_html_e('hover', 'ppsgen');
      $component->colorpicker('[submit_background_hover]', ['value' => $component->value($design['submit_background_hover'] ?? null, '#d5d5d5')]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Button Box Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[submit_box_shadow][top]', ['value' => $component->value($design['submit_box_shadow']['top'] ?? null, '0'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[submit_box_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_box_shadow']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[submit_box_shadow][right]', ['value' => $component->value($design['submit_box_shadow']['right'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_box_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_box_shadow']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[submit_box_shadow][bottom]', ['value' => $component->value($design['submit_box_shadow']['bottom'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_box_shadow][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_box_shadow']['bottom_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[submit_box_shadow][left]', ['value' => $component->value($design['submit_box_shadow']['left'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_box_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_box_shadow']['left_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php $component->colorpicker('[submit_box_shadow_color]', ['value' => $component->value($design['submit_box_shadow_color'] ?? null)]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Submit Button Text Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[submit_text_shadow][top]', ['value' => $component->value($design['submit_text_shadow']['top'] ?? null, '0'), 'attrs' => 'class="ppsgenW60"']);
      $component->select('[submit_text_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_text_shadow']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[submit_text_shadow][right]', ['value' => $component->value($design['submit_text_shadow']['right'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_text_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_text_shadow']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      $component->number('[submit_text_shadow][left]', ['value' => $component->value($design['submit_text_shadow']['left'] ?? null, '0'), 'attrs' => ' class="ppsgenW60"']);
      $component->select('[submit_text_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['submit_text_shadow']['left_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php $component->colorpicker('[submit_text_shadow_color]', ['value' => $component->value($design['submit_text_shadow_color'] ?? null)]); ?>
    </div>
  </div>
  <h4><?php esc_html_e('Tooltip ', 'ppsgen'); ?></h4>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Text ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[tooltiptext_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['tooltiptext_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[tooltiptext_font_size]', [
        'value' => $component->value($design['tooltiptext_font_size'] ?? null, '14'),
        'attrs' => 'min="0" class="ppsgenW60"',
      ]); ?>
      <?php $component->select('[tooltiptext_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['tooltiptext_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[tooltiptext_font_color]', ['value' => $component->value($design['tooltiptext_font_color'] ?? null, '#000000')]); ?>
      <?php $component->select('[tooltiptext_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['tooltiptext_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[tooltiptext_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['tooltiptext_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Border', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[tooltiptext_border]', ['value' => $component->value($design['tooltiptext_border'] ?? null, '1'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[tooltiptext_border_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['tooltiptext_border_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->select('[tooltiptext_border_style]', [
        'options' => [
          'solid' => 'solid',
          'dotted' => 'dotted',
          'dashed' => 'dashed',
          'double' => 'double',
          'groove' => 'groove',
          'ridge' => 'ridge',
          'inset' => 'inset',
          'outset' => 'outset',
          'none' => 'none',
          'hidden' => 'hidden',
        ],
        'class' => '',
        'selected' => $component->value($design['tooltiptext_border_style'] ?? null, 'solid'),
      ]); ?>
      <?php $component->colorpicker('[tooltiptext_border_color]', ['value' => $component->value($design['tooltiptext_border_color'] ?? null, '#b9b9b9')]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Background', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->colorpicker('[tooltiptext_background]', ['value' => $component->value($design['tooltiptext_background'] ?? null, '#ffffff')]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tooltip Icon ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('Icon Size (px)', 'ppsgen');
      $component->number('[tooltipicon_font_size]', ['value' => $component->value($design['tooltipicon_font_size'] ?? null, '10'), 'attrs' => 'min="0" class="ppsgenW60"']);
      ?>
      <?php
      esc_html_e('Icon BG Size (px)', 'ppsgen');
      $component->number('[tooltipicon_wh_size]', ['value' => $component->value($design['tooltipicon_wh_size'] ?? null, '16'), 'attrs' => 'min="10" class="ppsgenW60"']);
      ?>
      <?php
      esc_html_e('Icon Color', 'ppsgen');
      $component->colorpicker('[tooltipicon_color_icon]', ['value' => $component->value($design['tooltipicon_color_icon'] ?? null, '#ffffff')]);
      ?>
      <?php
      esc_html_e('Icon BG Color', 'ppsgen');
      $component->colorpicker('[tooltipicon_color_bg]', ['value' => $component->value($design['tooltipicon_color_bg'] ?? null, '#b9b9b9')]);
      ?>
    </div>
  </div>
  <h4><?php esc_html_e('Radiobutton and Checkbox ', 'ppsgen'); ?></h4>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Radiobutton and Checkbox - Label ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[radcheck_font_family]', ['class' => 'ppsgenSW160', 'selected' => $component->value($design['radcheck_font_family'] ?? null, 'Default')]); ?>
      <?php $component->number('[radcheck_font_size]', ['value' => $component->value($design['radcheck_font_size'] ?? null, '14'), 'attrs' => 'min="0" class="ppsgenW60"']); ?>
      <?php $component->select('[radcheck_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['radcheck_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[radcheck_font_color]', ['value' => $component->value($design['radcheck_font_color'] ?? null, '#000000')]); ?>
      <?php $component->select('[radcheck_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['radcheck_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[radcheck_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['radcheck_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Radiobutton and Checkbox - Custom ', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[radcheckcus_font_size]', [
        'value' => $component->value($design['radcheckcus_font_size'] ?? null, '14'),
        'attrs' => 'min="0" class="ppsgenW60"',
      ]); ?>
      <?php $component->select('[radcheckcus_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['radcheckcus_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[radcheckcus_font_color]', ['value' => $component->value($design['radcheckcus_font_color'] ?? null, '#000000')]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Radiobutton', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Custom Styles Support Custom Colors and Size', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentList">
      <div class="ppsgenComponentRow" style="margin-top:10px;">
        <div class="ppsgenComponentOptionInline">
          <label class="ppsgenComponentOptionInline"> <?php $component->radio('[radio_style]', [
            'options' => ['standard' => ''],
            'checked' => $component->value($design['radio_style'] ?? null, 'regular'),
            'attrs' => '',
          ]); ?>
            <span><?php esc_html_e('Default Theme/Browser Icon', 'ppsgen'); ?></span>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentOptionInline">
          <label class="ppsgenComponentOptionInline"> <?php $component->radio('[radio_style]', [
            'options' => ['regular' => ''],
            'checked' => $component->value($design['radio_style'] ?? null, 'regular'),
            'attrs' => '',
          ]); ?>
            <span><span class="fappsgen fappsgen-circle-check"></span> <span class="fappsgen fappsgen-circle"></span> <?php esc_html_e('Custom - Regular', 'ppsgen'); ?> </span>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentOptionInline">
          <label class="ppsgenComponentOptionInline"> <?php $component->radio('[radio_style]', [
            'options' => ['solid' => ''],
            'checked' => $component->value($design['radio_style'] ?? null, 'regular'),
            'attrs' => '',
          ]); ?>
            <span><span class="fappsgen9 fappsgen-circle-check"></span> <span class="fappsgen9 fappsgen-circle"></span> <?php esc_html_e('Custom - Solid', 'ppsgen'); ?> </span>
        </div>
      </div>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Checkbox', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Custom Styles Support Custom Colors and Size', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentList">
      <div class="ppsgenComponentRow" style="margin-top:10px;">
        <div class="ppsgenComponentOptionInline">
          <label class="ppsgenComponentOptionInline"> <?php $component->radio('[cbox_style]', [
            'options' => ['standard' => ''],
            'checked' => $component->value($design['radio_style'] ?? null, 'regular'),
            'attrs' => '',
          ]); ?>
            <span><?php esc_html_e('Default Theme/Browser Icon', 'ppsgen'); ?></span>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentOptionInline">
          <label class="ppsgenComponentOptionInline"> <?php $component->radio('[cbox_style]', [
            'options' => ['regular' => ''],
            'checked' => $component->value($design['radio_style'] ?? null, 'regular'),
            'attrs' => '',
          ]); ?>
            <span><span class="fappsgen fappsgen-square-check"></span> <span class="fappsgen fappsgen-square"></span> <?php esc_html_e('Custom - Regular', 'ppsgen'); ?> </span>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentOptionInline">
          <label class="ppsgenComponentOptionInline"> <?php $component->radio('[cbox_style]', [
            'options' => ['solid' => ''],
            'checked' => $component->value($design['radio_style'] ?? null, 'regular'),
            'attrs' => '',
          ]); ?>
            <span><span class="fappsgen9 fappsgen-square-check"></span> <span class="fappsgen9 fappsgen-square"></span> <?php esc_html_e('Custom - Solid', 'ppsgen'); ?> </span>
        </div>
      </div>
    </div>
  </div>
  <?php
  $html = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.edit.design_html.options', $options);
  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  echo $this->getFrame()->core('helper')->wpKses($html);
  ?>
</div>
