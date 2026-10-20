<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$design = !empty($options['design']) ? $options['design'] : '';
$fields = !empty($options['fields']) ? $options['fields'] : '';
$prefix = 'data[popup]';
$sprefix = 'data[popup][design]';
$component->setNamePrefix($sprefix);
?>
<div class="ppsgenComponentsList">
  <h3><?php esc_html_e('Design Options', 'ppsgen'); ?></h3>
  <h4><?php esc_html_e('Popup Styles', 'ppsgen'); ?></h4>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show with Overlay', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[show_overlay]', [
        'checked' => $component->value($design['show_overlay'] ?? null, '1'),
        'class' => 'data-option-toggle-trigger',
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_overlay]" data-toggle-val="1">
    <div class="ppsgenComponentLabel">
      <?php esc_html_e('Overlay Background Type', 'ppsgen'); ?>
    </div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[overlay_bg_type]', [
        'options' => [
          'color' => esc_attr__('Color', 'ppsgen'),
          'image' => esc_attr__('Image', 'ppsgen'),
        ],
        'class' => 'ppsgenSW120 data-option-toggle-trigger',
        'selected' => $component->value($design['overlay_bg_type'] ?? null, 'color'),
      ]); ?>
    </div>
  </div>
  <div data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_overlay]" data-toggle-val="1">
    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[overlay_bg_type]" data-toggle-val="color">
      <div class="ppsgenComponentLabel">
        <?php esc_html_e('Overlay Background Color', 'ppsgen'); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->colorpicker('[popup_background_overlay]', [
          'value' => $component->value($design['popup_background_overlay'] ?? null, 'rgba(0,0,0,0.5)'),
        ]); ?>
      </div>
    </div>
  </div>
  <div data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_overlay]" data-toggle-val="1">
    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[overlay_bg_type]" data-toggle-val="image">
      <div class="ppsgenComponentLabel">
        <?php esc_html_e('Overlay Background Image', 'ppsgen'); ?>
      </div>
      <div class="ppsgenComponentOption">
        <div style="margin-bottom:4px;">
          <?php ppsgen_render_background_image_picker(
            $component,
            '[overlay_bg_image]',
            $component->value($design['overlay_bg_image'] ?? null, '')
          ); ?>
        </div>
        <div class="ppsgenComponentOptionInline">
          <?php $component->select('[overlay_bg_size]', [
            'options' => [
              'auto' => 'auto',
              'cover' => 'cover',
              'contain' => 'contain',
            ],
            'selected' => $component->value($design['overlay_bg_size'] ?? null, 'cover'),
          ]); ?>
          <?php $component->select('[overlay_bg_repeat]', [
            'options' => [
              'no-repeat' => 'no-repeat',
              'repeat' => 'repeat',
              'repeat-x' => 'repeat-x',
              'repeat-y' => 'repeat-y',
            ],
            'selected' => $component->value($design['overlay_bg_repeat'] ?? null, 'no-repeat'),
          ]); ?>
          <?php $component->select('[overlay_bg_position]', [
            'options' => [
              'center center' => 'center',
              'top center' => 'top',
              'bottom center' => 'bottom',
              'left center' => 'left',
              'right center' => 'right',
            ],
            'selected' => $component->value($design['overlay_bg_position'] ?? null, 'center center'),
          ]); ?>
        </div>
        <div style="margin:4px 0px; max-width:120px;">
          <div class="ppsgenComponentOptionInline">
            <div class="ppsgenComponentOptionNoticeUnder" style="width:85px;">
              <?php esc_html_e('Opacity', 'ppsgen'); ?>
            </div>
            <?php $component->number('[overlay_bg_opacity]', [
              'value' => $component->value($design['overlay_bg_opacity'] ?? null, '100'),
              'attrs' => 'min="0" max="100" class="ppsgenW60"',
            ]); ?>
            <span>%</span>
          </div>
        </div>
        <div style="margin:4px 0px; max-width:295px;">
          <div class="ppsgenComponentOptionInline">
            <div class="ppsgenComponentOptionNoticeUnder" style="width:110px;">
              <?php esc_html_e('Overlay Image Color', 'ppsgen'); ?>
            </div>
            <?php $component->colorpicker('[overlay_bg_img_color]', [
              'value' => $component->value($design['overlay_bg_img_color'] ?? null, 'rgba(255,255,255,0.2)'),
            ]); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="ppsgenComponentRow"
    data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_overlay]"
    data-toggle-val="1">
    <div class="ppsgenComponentLabel"
      data-notice-label="<?php esc_attr_e('Overlay Backdrop Filter', 'ppsgen'); ?>">
    </div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[overlay_bg_filter]', [
        'options' => $component->backdropFilterUnits(),
        'class' => 'ppsgenSW80 data-option-toggle-trigger',
        'selected' => $component->value($design['overlay_bg_filter'] ?? null, ''),
      ]); ?>
      <?php $component->number('[overlay_bg_filter_size]', [
        'value' => $component->value($design['overlay_bg_filter_size'] ?? null, '0'),
        'attrs' => 'min="0" max="100" class="ppsgenW60"',
      ]); ?>
      <span class="ppsgenComponentOptionInline"
        data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[overlay_bg_filter]"
        data-toggle-val="blur">
        <?php esc_html_e('from 0 to 10 in px', 'ppsgen'); ?>
      </span>
      <span class="ppsgenComponentOptionInline"
        data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[overlay_bg_filter]"
        data-toggle-val="brightness, contrast, grayscale, invert, opacity, sepia, saturate,">
        <?php esc_html_e('from 0 to 100 in %', 'ppsgen'); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Popup Show Animation', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->text('[popup_anim_show]', [
        'value' => $component->value($design['popup_anim_show'] ?? null),
        'attrs' => ' readonly class="popupShowAnimationInput"',
      ]); ?>
      <div class="ppsgenShowAnimationBtn" data-anim-type="in" data-target="popupShowAnimationInput"><?php esc_html_e('Show Animation List', 'ppsgen'); ?></div>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Popup Hide Animation', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->text('[popup_anim_hide]', [
        'value' => $component->value($design['popup_anim_hide'] ?? null),
        'attrs' => ' readonly class="popupHideAnimationInput"',
      ]); ?>
      <div class="ppsgenShowAnimationBtn" data-anim-type="out" data-target="popupHideAnimationInput"><?php esc_html_e('Show Animation List', 'ppsgen'); ?></div>
    </div>
  </div>
  <div id="ppsgenAnimOverlay" style="display:none;">
    <div class="ppsgenAnimOverlayBg"></div>
    <div class="ppsgenAnimOverlayContent">
      <div class="ppsgenAnimClose">&times;</div>
      <h3><?php esc_html_e('Select Animation', 'ppsgen'); ?></h3>
      <p><?php esc_html_e('Press double click to select animation.', 'ppsgen'); ?></p>
      <div class="ppsgenAnimGrid"></div>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Popup Position', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_contaner_position]', [
        'options' => [
          'top-left' => 'Top Left',
          'top-center' => 'Top Center',
          'top-right' => 'Top Right',
          'center-left' => 'Center Left',
          'center' => 'Center',
          'center-right' => 'Center Right',
          'bottom-left' => 'Bottom Left',
          'bottom-center' => 'Bottom Center',
          'bottom-right' => 'Bottom Right',
        ],
        'class' => '',
        'selected' => $component->value($design['popup_contaner_position'] ?? null, 'center'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Popup Padding (inner indent)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      esc_html_e('top', 'ppsgen');
      $component->number('[popup_padding][top]', [
        'value' => $component->value($design['popup_padding']['top'] ?? null, '10'),
        'attrs' => 'class="ppsgenW60"',
      ]);
      $component->select('[popup_padding][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['popup_padding']['top_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('right', 'ppsgen');
      $component->number('[popup_padding][right]', [
        'value' => $component->value($design['popup_padding']['right'] ?? null, '10'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_padding][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['popup_padding']['right_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('bottom', 'ppsgen');
      $component->number('[popup_padding][bottom]', [
        'value' => $component->value($design['popup_padding']['bottom'] ?? null, '10'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_padding][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['popup_padding']['bottom_unit'] ?? null, 'px'),
      ]);
      ?>
      <?php
      esc_html_e('left', 'ppsgen');
      $component->number('[popup_padding][left]', [
        'value' => $component->value($design['popup_padding']['left'] ?? null, '10'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_padding][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['popup_padding']['left_unit'] ?? null, 'px'),
      ]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Width', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_width_select]', [
        'options' => [
          'manual' => 'Manual',
          'auto' => 'Auto',
          'initial' => 'Initial',
          'inherit' => 'Inherit',
        ],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['popup_width_select'] ?? null, 'manual'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_width_select]" data-toggle-val="manual">
        <?php $component->number('[popup_width]', [
          'value' => $component->value($design['popup_width'] ?? null, '100'),
          'attrs' => 'min="0" class="ppsgenW60"',
        ]); ?>
        <?php $component->select('[popup_width_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['popup_width_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-width', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_max_width_select]', [
        'options' => [
          'auto' => 'Auto',
          'initial' => 'Initial',
          'inherit' => 'Inherit',
          'manual' => 'Manual',
        ],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['popup_max_width_select'] ?? null, 'auto'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_max_width_select]" data-toggle-val="manual">
        <?php $component->number('[popup_max_width]', [
          'value' => $component->value($design['popup_max_width'] ?? null, '100'),
          'attrs' => 'min="0" class="ppsgenW60"',
        ]); ?>
        <?php $component->select('[popup_max_width_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['popup_max_width_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Height', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_height_select]', [
        'options' => [
          'manual' => 'Manual',
          'auto' => 'Auto',
          'initial' => 'Initial',
          'inherit' => 'Inherit',
        ],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['popup_height_select'] ?? null, 'auto'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_height_select]" data-toggle-val="manual">
        <?php $component->number('[popup_height]', [
          'value' => $component->value($design['popup_height'] ?? null, '100'),
          'attrs' => 'min="0" class="ppsgenW60"',
        ]); ?>
        <?php $component->select('[popup_height_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['popup_height_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-height', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_max_height_select]', [
        'options' => [
          'auto' => 'Auto',
          'initial' => 'Initial',
          'inherit' => 'Inherit',
          'manual' => 'Manual',
        ],
        'class' => 'data-option-toggle-trigger',
        'selected' => $component->value($design['popup_max_height_select'] ?? null, 'auto'),
      ]); ?>
      <span class="ppsgenComponentOptionInline" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_max_height_select]" data-toggle-val="manual">
        <?php $component->number('[popup_max_height]', [
          'value' => $component->value($design['popup_max_height'] ?? null, '100'),
          'attrs' => 'min="0" class="ppsgenW60"',
        ]); ?>
        <?php $component->select('[popup_max_height_unit]', [
          'options' => $component->getUnits(),
          'class' => '',
          'selected' => $component->value($design['popup_max_height_unit'] ?? null, 'percent'),
        ]); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[popup_border]', [
        'value' => $component->value($design['popup_border'] ?? null, '1'),
        'attrs' => 'min="0" class="ppsgenW60"',
      ]); ?>
      <?php $component->select('[popup_border_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['popup_border_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->select('[popup_border_style]', [
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
        'selected' => $component->value($design['popup_border_style'] ?? null, 'solid'),
      ]); ?>
      <?php $component->colorpicker('[popup_border_color]', [
        'value' => $component->value($design['popup_border_color'] ?? null, '#dddddd'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border Radius', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[popup_border_radius]', [
        'value' => $component->value($design['popup_border_radius'] ?? null, '10'),
        'attrs' => 'min="0" class="ppsgenW60"',
      ]); ?>
      <?php $component->select('[popup_border_radius_unit]', [
        'options' => $component->getUnits(),
        'class' => '',
        'selected' => $component->value($design['popup_border_radius_unit'] ?? null, 'px'),
      ]); ?>
    </div>
  </div>
  <?php if (false) { ?>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Overflow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_overflow]', [
        'options' => [
          '' => '',
          'auto' => 'Auto',
          'visible' => 'Visible',
          'hidden ' => 'Hidden',
          'scroll' => 'Scroll',
        ],
        'class' => '',
        'selected' => $component->value($design['popup_overflow'] ?? null, ''),
      ]); ?>
      <?php esc_html_e('Overflow-X', 'ppsgen'); ?>
      <?php $component->select('[popup_overflow_x]', [
        'options' => [
          '' => '',
          'auto' => 'Auto',
          'visible' => 'Visible',
          'hidden ' => 'Hidden',
          'scroll' => 'Scroll',
        ],
        'class' => '',
        'selected' => $component->value($design['popup_overflow_x'] ?? null, ''),
      ]); ?>
      <?php esc_html_e('Overflow-Y', 'ppsgen'); ?>
      <?php $component->select('[popup_overflow_y]', [
        'options' => [
          '' => '',
          'auto' => 'Auto',
          'visible' => 'Visible',
          'hidden ' => 'Hidden',
          'scroll' => 'Scroll',
        ],
        'class' => '',
        'selected' => $component->value($design['popup_overflow_y'] ?? null, ''),
      ]); ?>
    </div>
  </div>
  <?php } ?>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Scrollbar Size', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_scrollbar_size]', [
        'options' => [
          '' => esc_attr__('Default', 'ppsgen'),
          'xs' => 'XS',
          's' => 'S',
          'm' => 'M',
          'l' => 'L',
          'xl' => 'XL',
        ],
        'class' => 'ppsgenSW80',
        'selected' => $component->value($design['popup_scrollbar_size'] ?? null, 'm'),
      ]); ?>
      <?php esc_html_e('Style', 'ppsgen'); ?>
      <?php $component->select('[popup_scrollbar_style]', [
        'options' => [
          'square' => esc_attr__('Square', 'ppsgen'),
          'rounded' => esc_attr__('Rounded', 'ppsgen'),
          'pill' => esc_attr__('Pill', 'ppsgen'),
        ],
        'class' => 'ppsgenSW120',
        'selected' => $component->value($design['popup_scrollbar_style'] ?? null, 'rounded'),
      ]); ?>
      <?php esc_html_e('Buttons', 'ppsgen'); ?>
      <?php $component->checkboxToggle('[popup_scrollbar_buttons]', [
        'checked' => $component->value($design['popup_scrollbar_buttons'] ?? null, '1'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Scrollbar Colors', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php esc_html_e('Track', 'ppsgen'); ?>
      <?php $component->colorpicker('[popup_scrollbar_track_color]', [
        'value' => $component->value($design['popup_scrollbar_track_color'] ?? null, 'rgba(0,0,0,0.08)'),
      ]); ?>
      <?php esc_html_e('Thumb', 'ppsgen'); ?>
      <?php $component->colorpicker('[popup_scrollbar_thumb_color]', [
        'value' => $component->value($design['popup_scrollbar_thumb_color'] ?? null, '#7c8aa5'),
      ]); ?>
      <?php esc_html_e('Hover', 'ppsgen'); ?>
      <?php $component->colorpicker('[popup_scrollbar_thumb_hover_color]', [
        'value' => $component->value($design['popup_scrollbar_thumb_hover_color'] ?? null, '#4f5f7f'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Scrollbar Button Colors', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php esc_html_e('Button', 'ppsgen'); ?>
      <?php $component->colorpicker('[popup_scrollbar_button_color]', [
        'value' => $component->value($design['popup_scrollbar_button_color'] ?? null, 'rgba(0,0,0,0.12)'),
      ]); ?>
      <?php esc_html_e('Hover', 'ppsgen'); ?>
      <?php $component->colorpicker('[popup_scrollbar_button_hover_color]', [
        'value' => $component->value($design['popup_scrollbar_button_hover_color'] ?? null, 'rgba(0,0,0,0.22)'),
      ]); ?>
      <?php esc_html_e('Corner', 'ppsgen'); ?>
      <?php $component->colorpicker('[popup_scrollbar_corner_color]', [
        'value' => $component->value($design['popup_scrollbar_corner_color'] ?? null, 'rgba(0,0,0,0)'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel"><?php esc_html_e('Background Type', 'ppsgen'); ?></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_bg_type]', [
        'options' => [
          'color' => esc_attr__('Color', 'ppsgen'),
          'image' => esc_attr__('Image', 'ppsgen'),
        ],
        'class' => 'ppsgenSW120 data-option-toggle-trigger',
        'selected' => $component->value($design['popup_bg_type'] ?? null, 'color'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_bg_type]" data-toggle-val="color">
    <div class="ppsgenComponentLabel"><?php esc_html_e('Background Color', 'ppsgen'); ?></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->colorpicker('[popup_background]', [
        'value' => $component->value($design['popup_background'] ?? null, '#ffffff'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_bg_type]" data-toggle-val="image">
    <div class="ppsgenComponentLabel"><?php esc_html_e('Background Image', 'ppsgen'); ?></div>
    <div class="ppsgenComponentOption">
      <div style="margin-bottom:4px;">
        <?php ppsgen_render_background_image_picker(
          $component,
          '[popup_bg_image]',
          $component->value($design['popup_bg_image'] ?? null, '')
        ); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->select('[popup_bg_size]', [
          'options' => [
            'auto' => 'auto',
            'cover' => 'cover',
            'contain' => 'contain',
          ],
          'selected' => $component->value($design['popup_bg_size'] ?? null, 'cover'),
        ]); ?>
        <?php $component->select('[popup_bg_repeat]', [
          'options' => [
            'no-repeat' => 'no-repeat',
            'repeat' => 'repeat',
            'repeat-x' => 'repeat-x',
            'repeat-y' => 'repeat-y',
          ],
          'selected' => $component->value($design['popup_bg_repeat'] ?? null, 'no-repeat'),
        ]); ?>
        <?php $component->select('[popup_bg_position]', [
          'options' => [
            'center center' => 'center',
            'top center' => 'top',
            'bottom center' => 'bottom',
            'left center' => 'left',
            'right center' => 'right',
          ],
          'selected' => $component->value($design['popup_bg_position'] ?? null, 'center center'),
        ]); ?>
      </div>
      <div style="margin:4px 0px; max-width:120px;">
        <div class="ppsgenComponentOptionInline">
          <div class="ppsgenComponentOptionNoticeUnder" style="width:85px;">
            <?php esc_html_e('Opacity', 'ppsgen'); ?>
          </div>
          <?php $component->number('[popup_bg_opacity]', [
            'value' => $component->value($design['popup_bg_opacity'] ?? null, '100'),
            'attrs' => 'min="0" max="100" class="ppsgenW60"',
          ]); ?>
          <span>%</span>
        </div>
      </div>
      <div style="margin:4px 0px; max-width:295px;">
        <div class="ppsgenComponentOptionInline">
          <div class="ppsgenComponentOptionNoticeUnder" style="width:110px;">
            <?php esc_html_e('Overlay Image Color', 'ppsgen'); ?>
          </div>
          <?php $component->colorpicker('[popup_bg_img_color]', [
            'value' => $component->value($design['popup_bg_img_color'] ?? null, 'rgba(255,255,255,0.2)'),
          ]); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel"
      data-notice-label="<?php esc_attr_e('Background Backdrop Filter', 'ppsgen'); ?>">
    </div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->select('[popup_bg_filter]', [
        'options' => $component->backdropFilterUnits(),
        'class' => 'ppsgenSW80 data-option-toggle-trigger',
        'selected' => $component->value($design['popup_bg_filter'] ?? null, ''),
      ]); ?>
      <?php $component->number('[popup_bg_filter_size]', [
        'value' => $component->value($design['popup_bg_filter_size'] ?? null, '0'),
        'attrs' => 'min="0" max="100" placeholder="" class="ppsgenW60"',
      ]); ?>
      <span class="ppsgenComponentOptionInline"
        data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_bg_filter]"
        data-toggle-val="blur">
        <?php esc_html_e('from 0 to 10 in px', 'ppsgen'); ?>
      </span>
      <span class="ppsgenComponentOptionInline"
        data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[popup_bg_filter]"
        data-toggle-val="brightness, contrast, grayscale, invert, opacity, sepia, saturate,">
        <?php esc_html_e('from 0 to 100 in %', 'ppsgen'); ?>
      </span>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Box Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[popup_box_shadow][top]', [
        'value' => $component->value($design['popup_box_shadow']['top'] ?? null, '0'),
        'attrs' => 'class="ppsgenW60"',
      ]);
      $component->select('[popup_box_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['popup_box_shadow']['top_unit']) ? $component->value($design['popup_box_shadow']['top_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[popup_box_shadow][right]', [
        'value' => $component->value($design['popup_box_shadow']['right'] ?? null, '0'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_box_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['popup_box_shadow']['right_unit']) ? $component->value($design['popup_box_shadow']['right_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[popup_box_shadow][bottom]', [
        'value' => $component->value($design['popup_box_shadow']['bottom'] ?? null, '0'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_box_shadow][bottom_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['popup_box_shadow']['bottom_unit']) ? $component->value($design['popup_box_shadow']['bottom_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[popup_box_shadow][left]', [
        'value' => $component->value($design['popup_box_shadow']['left'] ?? null, '0'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_box_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['popup_box_shadow']['left_unit']) ? $component->value($design['popup_box_shadow']['left_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php $component->colorpicker('[popup_box_shadow_color]', [
        'value' => $component->value($design['popup_box_shadow_color'] ?? null),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Text Shadow', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php
      $component->number('[popup_text_shadow][top]', [
        'value' => $component->value($design['popup_text_shadow']['top'] ?? null, '0'),
        'attrs' => 'class="ppsgenW60"',
      ]);
      $component->select('[popup_text_shadow][top_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['popup_text_shadow']['top_unit']) ? $component->value($design['popup_text_shadow']['top_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[popup_text_shadow][right]', [
        'value' => $component->value($design['popup_text_shadow']['right'] ?? null, '0'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_text_shadow][right_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['popup_text_shadow']['right_unit']) ? $component->value($design['popup_text_shadow']['right_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php
      $component->number('[popup_text_shadow][left]', [
        'value' => $component->value($design['popup_text_shadow']['left'] ?? null, '0'),
        'attrs' => ' class="ppsgenW60"',
      ]);
      $component->select('[popup_text_shadow][left_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => !empty($design['popup_text_shadow']['left_unit']) ? $component->value($design['popup_text_shadow']['left_unit'] ?? null) : 'px',
      ]);
      ?>
      <?php $component->colorpicker('[popup_text_shadow_color]', [
        'value' => $component->value($design['popup_text_shadow_color'] ?? null),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Font Styles', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->fontFamilySelect('[popup_font_family]', [
        'class' => 'ppsgenSW160',
        'selected' => $component->value($design['popup_font_family'] ?? null, 'Default'),
      ]); ?>
      <?php $component->number('[popup_font_size]', [
        'value' => $component->value($design['popup_font_size'] ?? null, '14'),
        'attrs' => 'min="0" class="ppsgenW60"',
      ]); ?>
      <?php $component->select('[popup_font_size_unit]', [
        'options' => $component->getUnits(),
        'class' => 'ppsgenSW55',
        'selected' => $component->value($design['popup_font_size_unit'] ?? null, 'px'),
      ]); ?>
      <?php $component->colorpicker('[popup_font_color]', [
        'value' => $component->value($design['popup_font_color'] ?? '#000000'),
      ]); ?>
      <?php $component->select('[popup_font_style]', [
        'options' => $component->getFontStyles(),
        'class' => '',
        'selected' => $component->value($design['popup_font_style'] ?? null, 'normal'),
      ]); ?>
      <?php $component->select('[popup_font_weight]', [
        'options' => $component->getFontWeight(),
        'class' => '',
        'selected' => $component->value($design['popup_font_weight'] ?? null, 'normal'),
      ]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Close Button Style', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Style', 'ppsgen'); ?></span>
        <?php $component->select('[popup_close_style]', [
          'options' => [
            'square' => 'Square',
            'rounded' => 'Rounded',
            'circle' => 'Circle',
          ],
          'class' => 'ppsgenSWI120',
          'selected' => $component->value($design['popup_close_style'] ?? null, 'center'),
        ]); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Icon Sisze', 'ppsgen'); ?></span>
        <?php $component->number('[popup_close_icon_size]', [
          'value' => $component->value($design['popup_close_icon_size'] ?? null, '14'),
          'attrs' => 'min="0" class="ppsgenSWI55"',
        ]); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Background Size', 'ppsgen'); ?></span>
        <?php $component->number('[popup_close_bg_size]', ['value' => $component->value($design['popup_close_bg_size'] ?? null, '30'), 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Icon Color (default/hover)', 'ppsgen'); ?></span>
        <?php $component->colorpicker('[popup_close_icon_color]', ['value' => $component->value($design['popup_close_icon_color'] ?? '#ffffff')]); ?>
        <?php $component->colorpicker('[popup_close_icon_color_hover]', ['value' => $component->value($design['popup_close_icon_color_hover'] ?? '#000000')]); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Background Color (default/hover)', 'ppsgen'); ?></span>
        <?php $component->colorpicker('[popup_close_bg_color]', ['value' => $component->value($design['popup_close_bg_color'] ?? '#000000')]); ?>
        <?php $component->colorpicker('[popup_close_bg_color_hover]', ['value' => $component->value($design['popup_close_bg_color_hover'] ?? '#ffffff')]); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Position (top/right)', 'ppsgen'); ?></span>
        <?php $component->number('[popup_close_position_top]', ['value' => $component->value($design['popup_close_position_top'] ?? null, '-12'), 'attrs' => ' class="ppsgenSWI55"']); ?>
        <?php $component->number('[popup_close_position_right]', ['value' => $component->value($design['popup_close_position_right'] ?? null, '-12'), 'attrs' => ' class="ppsgenSWI55"']); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Border', 'ppsgen'); ?></span>
        <?php $component->number('[popup_close_border]', [
          'value' => $component->value($design['popup_close_border'] ?? null, '1'),
          'attrs' => 'min="0" class="ppsgenSWI55"',
        ]); ?>
        <?php $component->select('[popup_close_border_unit]', [
          'options' => $component->getUnits(),
          'class' => 'ppsgenSWI55',
          'selected' => $component->value($design['popup_close_border_unit'] ?? null, 'px'),
        ]); ?>
        <?php $component->select('[popup_close_border_style]', [
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
          'class' => 'ppsgenSWI90',
          'selected' => $component->value($design['popup_close_border_style'] ?? null, 'solid'),
        ]); ?>
      </div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenSWI120"><?php esc_html_e('Border Color (default/hover)', 'ppsgen'); ?></span>
        <?php $component->colorpicker('[popup_close_border_color]', [
          'value' => $component->value($design['popup_close_border_color'] ?? null, '#000000'),
        ]); ?>
        <?php $component->colorpicker('[popup_close_border_color_hover]', [
          'value' => $component->value($design['popup_close_border_color_hover'] ?? null, '#000000'),
        ]); ?>
      </div>
    </div>
  </div>
</div>
