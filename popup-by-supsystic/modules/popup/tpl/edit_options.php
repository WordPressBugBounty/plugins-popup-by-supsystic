<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$prefix = 'data[popup]';
$sprefix = 'data[popup][settings]';
$component->setNamePrefix($prefix);
$popup_id = !empty($options['obj_id']) ? $options['obj_id'] : 0;
?>
<div class="ppsgenComponentsList">
  <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Popup is Enabled', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[active]', ['checked' => $component->value($options['active'] ?? null, '1'), 'class' => 'data-option-toggle-trigger']); ?>
      </div>
    </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Popup Name', 'ppsgen'); ?>"  ></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[name]', ['value' => $component->value($options['name'] ?? null), 'attrs' => ' ']); ?>
    </div>
  </div>

  <?php $component->setNamePrefix($sprefix); ?>

  <h3><?php esc_html_e('When to Show Popup', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('When to Show Popup', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select when the popup should be displayed.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[show_on]', [
        'options' => [
          'page_load' => esc_html__('When page loads', 'ppsgen'),
          'click_on_page' => esc_html__('User click on the page', 'ppsgen'),
          'click_on_element' => esc_html__('User click on element', 'ppsgen'),
          'scroll_window' => esc_html__('Scroll window', 'ppsgen'),
          'on_exit' => esc_html__('On exit from site', 'ppsgen'),
          'page_bottom' => esc_html__('Bottom of the page', 'ppsgen'),
          'after_inactive' => esc_html__('After inactivity', 'ppsgen'),
          'after_comment' => esc_html__('After user comment, review - Wordpress, WooCommerce', 'ppsgen'),
          'after_checkout' => esc_html__('After purchasing (Checkout) - WooCommerce', 'ppsgen'),
          'link_follow' => esc_html__('On link follow', 'ppsgen'),
          'build_in_page' => esc_html__('Build-in page', 'ppsgen'),
          'adblock_detected' => esc_html__('AdBlock detected', 'ppsgen'),
          'visit_several_pages' => esc_html__('Visit several pages', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($settings['show_on'] ?? null, 'page_load'),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="page_load">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Enable Page Load Delay', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable delay before showing the popup on page load.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[show_on_page_load_delay]', [
        'checked' => $component->value($settings['show_on_page_load_delay'] ?? null),
        'class' => 'data-option-toggle-trigger',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on_page_load_delay]" data-toggle-val="1">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Page Load Delay', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Time in seconds before showing the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[show_on_page_load_delay]', [
        'value' => $component->value($settings['show_on_page_load_delay'] ?? null, ''),
        'attrs' => 'placeholder="0" ',
      ]); ?>
      <span><?php esc_html_e('sec', 'ppsgen'); ?></span>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="page_load">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Count Time from First Site Load', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Count delay from the first site load instead of page load.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[page_load_global_delay]', [
        'checked' => $component->value($settings['page_load_global_delay'] ?? null),
        'class' => '',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="click_on_element">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Click on Element Code', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Copy and paste the code into your link or element to open the popup on click.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <span><?php esc_html_e('Use this onclick attribute in your HTML:', 'ppsgen'); ?></span>
      <textarea readonly rows="1" cols="50" style="resize:none;" >onclick="supsysticCesShowPopup(<?php echo esc_html($popup_id); ?>, this);"</textarea>
      <span><?php esc_html_e('Or use this for menu item URL or Title Attribute:', 'ppsgen'); ?></span>
      <input type="text" readonly value="<?php ppsgen_esc_attr('#supsystic-ces-popup-' . $popup_id); ?>">
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="click_on_element">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Click on Element Delay', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable delay before showing the popup on element click.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[show_on_click_on_el_delay]', [
        'checked' => $component->value($settings['show_on_click_on_el_delay'] ?? null),
        'class' => 'data-option-toggle-trigger',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on_click_on_el_delay]" data-toggle-val="1">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Click on Element Delay Time', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Time in seconds before showing the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[show_on_click_on_el_delay]', [
        'value' => $component->value($settings['show_on_click_on_el_delay'] ?? null, '0'),
        'attrs' => 'placeholder="0" ',
      ]); ?>
      <span><?php esc_html_e('sec', 'ppsgen'); ?></span>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="scroll_window">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Scroll Window Delay', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable delay before showing the popup on scroll.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[show_on_scroll_window_delay]', [
        'checked' => $component->value($settings['show_on_scroll_window_delay'] ?? null),
        'class' => 'data-option-toggle-trigger',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on_scroll_window_delay]" data-toggle-val="1">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Scroll Window Delay Time', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Time in seconds after first scroll.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[show_on_scroll_window_delay]', [
        'value' => $component->value($settings['show_on_scroll_window_delay'] ?? null, '0'),
        'attrs' => 'placeholder="0" ',
      ]); ?>
      <span><?php esc_html_e('sec', 'ppsgen'); ?></span>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="scroll_window">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Scroll Window Percentage', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable showing popup after scrolling a percentage of the page.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[show_on_scroll_window_perc_scroll]', [
        'checked' => $component->value($settings['show_on_scroll_window_perc_scroll'] ?? null),
        'class' => 'data-option-toggle-trigger',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on_scroll_window_perc_scroll]" data-toggle-val="1">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Scroll Window Percentage Value', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Percentage of total scroll to trigger the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[show_on_scroll_window_perc_scroll]', [
        'value' => $component->value($settings['show_on_scroll_window_perc_scroll'] ?? null, '0'),
        'attrs' => 'placeholder="0" ',
      ]); ?>
      <span><?php esc_html_e('percent', 'ppsgen'); ?></span>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="after_inactive">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Inactivity Time', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Time in seconds of user inactivity before showing the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[show_on_after_inactive_value]', [
        'value' => $component->value($settings['show_on_after_inactive_value'] ?? null, '10'),
        'attrs' => 'placeholder="10" class=""',
      ]); ?>
      <span><?php esc_html_e('sec', 'ppsgen'); ?></span>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="link_follow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Follow Code', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Copy and paste the code into your link to open the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <input type="text" readonly value="<?php ppsgen_esc_attr('#supsystic-ces-popup-' . $popup_id); ?>">
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="link_follow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Follow Delay', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable delay before showing the popup on link follow.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[show_on_link_follow_delay]', [
        'checked' => $component->value($settings['show_on_link_follow_delay'] ?? null),
        'class' => 'data-option-toggle-trigger',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on_link_follow_delay]" data-toggle-val="1">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Follow Delay Time', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Time in seconds before showing the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->number('[show_on_link_follow_delay]', [
        'value' => $component->value($settings['show_on_link_follow_delay'] ?? null, '0'),
        'attrs' => 'placeholder="0" class=""',
      ]); ?>
      <span><?php esc_html_e('sec', 'ppsgen'); ?></span>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="build_in_page">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Build-in Page Code', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Copy and paste the code into your page to embed the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <input type="text" readonly value="<?php ppsgen_esc_attr('[supsystic-ces-popup id="' . $popup_id . '" embed="1"]'); ?>">
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_on]" data-toggle-val="visit_several_pages">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Pages to Visit', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Number of pages the user must visit to show the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->number('[visit_page_cnt]', [
        'value' => $component->value($settings['visit_page_cnt'] ?? null, ''),
        'attrs' => 'placeholder="1" class=""',
      ]); ?>
    </div>
  </div>

    <h3><?php esc_html_e('When to Close Popup', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('When to Close Popup', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select when the popup should close automatically.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[close_on]', [
          'options' => [
            'user_close' => esc_html__('After user closes it', 'ppsgen'),
            'overlay_click' => esc_html__('Click outside popup', 'ppsgen'),
            'pro_after_action' => esc_html__('Only after submit Supsystic Form (PRO)', 'ppsgen'),
            'pro_after_time' => esc_html__('After time passed (PRO)', 'ppsgen'),
          ],
          'class' => 'ppsgenselect data-option-toggle-trigger',
          'selected' => $component->value($settings['close_on'] ?? null, 'user_close'),
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[close_on]" data-toggle-val="overlay_click">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide Close Button', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Hide the close button.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[hide_close_btn]', [
          'checked' => $component->value($settings['hide_close_btn'] ?? null, '1'),
          'class' => '',
        ]); ?>
      </div>
    </div>

    <h3><?php esc_html_e('Show on Pages', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show on Pages', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select where the popup should be displayed.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[show_pages]', [
          'options' => [
            'all' => esc_html__('All pages', 'ppsgen'),
            'show_on_pages' => esc_html__('Show on specific pages/posts', 'ppsgen'),
            'not_show_on_pages' => esc_html__('Do not show on specific pages/posts', 'ppsgen'),
            'show_on_categories' => esc_html__('Show on specific categories', 'ppsgen'),
            'not_show_on_categories' => esc_html__('Do not show on specific categories', 'ppsgen'),
          ],
          'class' => 'ppsgenselect data-option-toggle-trigger',
          'selected' => $component->value($settings['show_pages'] ?? null, 'all'),
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_pages]" data-toggle-val="show_on_pages">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show on Pages/Posts', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific pages or posts to show the popup.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[show_pages_list][]', [
          'options' => $this->getModel()->getPagesAndPosts(),
          'class' => 'ppsgenselect',
          'selected' => !empty($settings['show_pages_list']) ? $settings['show_pages_list'] : [],
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_pages]" data-toggle-val="not_show_on_pages">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Do Not Show on Pages/Posts', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific pages or posts to hide the popup.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[not_show_pages_list][]', [
          'options' => $this->getModel()->getPagesAndPosts(),
          'class' => 'ppsgenselect',
          'selected' => !empty($settings['not_show_pages_list']) ? $settings['not_show_pages_list'] : [],
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_pages]" data-toggle-val="show_on_categories">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show on Categories', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific categories to show the popup.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[show_categories_list][]', [
          'options' => $this->getModel()->getCategories(),
          'class' => 'ppsgenselect',
          'selected' => !empty($settings['show_categories_list']) ? $settings['show_categories_list'] : [],
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_pages]" data-toggle-val="not_show_on_categories">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Do Not Show on Categories', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific categories to hide the popup.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[not_show_categories_list][]', [
          'options' => $this->getModel()->getCategories(),
          'class' => 'ppsgenselect',
          'selected' => !empty($settings['not_show_categories_list']) ? $settings['not_show_categories_list'] : [],
        ]); ?>
      </div>
    </div>

    <h3><?php esc_html_e('Display Time settings', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Set Display Time', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable to set specific time range for popup display.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[show_time]', [
          'checked' => $component->value($settings['show_time'] ?? null),
          'class' => 'data-option-toggle-trigger',
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_time]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Display Time Range', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select the time range for displaying the popup.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php
        $hours = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
        $minutes = [
          '00',
          '01',
          '02',
          '03',
          '04',
          '05',
          '06',
          '07',
          '08',
          '09',
          '10',
          '11',
          '12',
          '13',
          '14',
          '15',
          '16',
          '17',
          '18',
          '19',
          '20',
          '21',
          '22',
          '23',
          '24',
          '25',
          '26',
          '27',
          '28',
          '29',
          '30',
          '31',
          '32',
          '33',
          '34',
          '35',
          '36',
          '37',
          '38',
          '39',
          '40',
          '41',
          '42',
          '43',
          '44',
          '45',
          '46',
          '47',
          '48',
          '49',
          '50',
          '51',
          '52',
          '53',
          '54',
          '55',
          '56',
          '57',
          '58',
          '59',
        ];
        ?>
        <span><?php esc_html_e('From: ', 'ppsgen'); ?></span>
        <div class="ppsgenComponentOptionInline">
          <span><?php esc_html_e('hours', 'ppsgen'); ?></span>
          <?php $component->select('[show_time_from_hours]', [
            'options' => $hours,
            'class' => 'ppsgenselect',
            'selected' => $component->value($settings['show_time_from_hours'] ?? null, '0'),
          ]); ?>
          <span><?php esc_html_e('minutes', 'ppsgen'); ?></span>
          <?php $component->select('[show_time_from_minutes]', [
            'options' => $minutes,
            'class' => 'ppsgenselect',
            'selected' => $component->value($settings['show_time_from_minutes'] ?? null, '0'),
          ]); ?>
        </div>

        <span><?php esc_html_e('To: ', 'ppsgen'); ?></span>
        <div class="ppsgenComponentOptionInline">
          <span><?php esc_html_e('hours', 'ppsgen'); ?></span>
          <?php $component->select('[show_time_to_hours]', [
            'options' => $hours,
            'class' => 'ppsgenselect',
            'selected' => $component->value($settings['show_time_to_hours'] ?? null, '0'),
          ]); ?>
          <span><?php esc_html_e('minutes', 'ppsgen'); ?></span>
          <?php $component->select('[show_time_to_minutes]', [
            'options' => $minutes,
            'class' => 'ppsgenselect',
            'selected' => $component->value($settings['show_time_to_minutes'] ?? null, '0'),
          ]); ?>
        </div>
      </div>
    </div>

    <h3><?php esc_html_e('Display Date Settings', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Set Display Date', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable to set specific date range for popup display.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[show_date]', [
          'checked' => $component->value($settings['show_date'] ?? null),
          'class' => 'data-option-toggle-trigger',
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_date]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Display Date Range', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select the date range for displaying the popup.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->text('[show_date_from]', [
          'value' => $component->value($settings['show_date_from'] ?? null),
          'attrs' => 'class="hasDatepicker" placeholder="YYYY-MM-DD"',
        ]); ?>
        <span><?php esc_html_e('to', 'ppsgen'); ?></span>
        <?php $component->text('[show_date_to]', [
          'value' => $component->value($settings['show_date_to'] ?? null),
          'attrs' => 'class="hasDatepicker" placeholder="YYYY-MM-DD"',
        ]); ?>
      </div>
    </div>

  <h3><?php esc_html_e('Display Days Settings', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Set Display Days', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enable to set specific days for popup display.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[show_days]', [
          'checked' => $component->value($settings['show_days'] ?? null),
          'class' => 'data-option-toggle-trigger',
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_days]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Display Days', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific days for displaying the popup.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[show_days][]', [
          'options' => [
            'monday' => esc_html__('Monday', 'ppsgen'),
            'tuesday' => esc_html__('Tuesday', 'ppsgen'),
            'wednesday' => esc_html__('Wednesday', 'ppsgen'),
            'thursday' => esc_html__('Thursday', 'ppsgen'),
            'friday' => esc_html__('Friday', 'ppsgen'),
            'saturday' => esc_html__('Saturday', 'ppsgen'),
            'sunday' => esc_html__('Sunday', 'ppsgen'),
          ],
          'class' => 'ppsgenselect',
          'selected' => !empty($settings['show_days']) ? $settings['show_days'] : [],
        ]); ?>
      </div>
    </div>

  <h3><?php esc_html_e('Whom to Show Popup', 'ppsgen'); ?></h3>

  <?php include PPSGEN_PLUGIN_DIR . 'modules/contact/tpl/audience_rows.php'; ?>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show to', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select the audience to show the popup to.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[show_to]', [
        'options' => [
          'everyone' => esc_html__('Everyone', 'ppsgen'),
          'first_time_visit' => esc_html__('For first-time visitors', 'ppsgen'),
          'count_times' => esc_html__('Limited number of times', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($settings['show_to'] ?? null, 'everyone'),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_to]" data-toggle-val="first_time_visit">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Remember for Days', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Number of days to remember first-time visitors.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->text('[show_to_first_time_visit_days]', [
        'value' => $component->value($settings['show_to_first_time_visit_days'] ?? null, '30'),
        'attrs' => 'placeholder="30"  style="width: 50px;"',
      ]); ?>
      <span><?php esc_html_e('days', 'ppsgen'); ?></span>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[show_to]" data-toggle-val="count_times">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show Times', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Number of times to show the popup within a period.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOptionInline">
      <?php $component->text('[count_times_num]', [
        'value' => $component->value($settings['count_times_num'] ?? null, '1'),
        'attrs' => 'placeholder="1"  style="width: 40px;"',
      ]); ?>
      <span><?php esc_html_e('times in a', 'ppsgen'); ?></span>
      <?php $component->select('[count_times_mes]', [
        'options' => [
          'hour' => esc_html__('hour', 'ppsgen'),
          'day' => esc_html__('day', 'ppsgen'),
          'week' => esc_html__('week', 'ppsgen'),
          'month' => esc_html__('month', 'ppsgen'),
        ],
        'class' => 'ppsgenselect',
        'selected' => $component->value($settings['count_times_mes'] ?? null, 'day'),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for Devices', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select whether to hide or show the popup for specific devices.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[hide_for_devices_action]', [
        'options' => [
          '' => esc_html__('Select an option', 'ppsgen'),
          'show' => esc_html__('Show only', 'ppsgen'),
          'hide' => esc_html__('Hide', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($settings['hide_for_devices_action'] ?? null, ''),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[hide_for_devices_action]" data-toggle-val="show,hide">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Devices', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific devices to show or hide the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[hide_for_devices][]', [
        'options' => [
          'mobile' => esc_html__('Mobile', 'ppsgen'),
          'tablet' => esc_html__('Tablet', 'ppsgen'),
          'desktop' => esc_html__('Desktop PC', 'ppsgen'),
        ],
        'class' => 'ppsgenselect',
        'selected' => !empty($settings['hide_for_devices']) ? $settings['hide_for_devices'] : [],
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for Post Types', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select whether to hide or show the popup for specific post types.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[hide_for_post_types_action]', [
        'options' => [
          '' => esc_html__('Select an option', 'ppsgen'),
          'show' => esc_html__('Show only', 'ppsgen'),
          'hide' => esc_html__('Hide', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($settings['hide_for_post_types_action'] ?? null, ''),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[hide_for_post_types_action]" data-toggle-val="show,hide">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Post Types', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific post types to show or hide the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[hide_for_post_types][]', [
        'options' => $this->getModel()->getPostTypes(),
        'class' => 'ppsgenselect',
        'selected' => !empty($settings['hide_for_post_types']) ? $settings['hide_for_post_types'] : [],
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for IPs', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select whether to hide or show the popup for specific IPs.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[hide_for_ips_action]', [
        'options' => [
          '' => esc_html__('Select an option', 'ppsgen'),
          'show' => esc_html__('Show only', 'ppsgen'),
          'hide' => esc_html__('Hide', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($settings['hide_for_ips_action'] ?? null, ''),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[hide_for_ips_action]" data-toggle-val="show,hide">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('IP Addresses', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enter IP addresses to show or hide the popup (comma-separated).', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->textarea('[hide_for_ips]', [
        'value' => $component->value($settings['hide_for_ips'] ?? null, ''),
        'attrs' => 'style="width: 100%;"',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for Languages', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select whether to hide or show the popup for specific languages.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[hide_for_languages_action]', [
        'options' => [
          '' => esc_html__('Select an option', 'ppsgen'),
          'show' => esc_html__('Show only', 'ppsgen'),
          'hide' => esc_html__('Hide', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($settings['hide_for_languages_action'] ?? null, ''),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for Countries (PRO)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select whether to hide or show the popup for specific countries.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[pro_hide_for_countries_action]', [
        'options' => [
          '' => esc_html__('Select an option', 'ppsgen'),
          'show_pro' => esc_html__('Show only', 'ppsgen'),
          'hide_pro' => esc_html__('Hide', 'ppsgen'),
        ],
        'class' => '',
        'selected' => '',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for Search Engines or Social Media', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
      'Select whether to hide or show the popup for specific search engines or social media.',
      'ppsgen',
    ); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[hide_for_search_engines_action]', [
            'options' => [
              '' => esc_html__('Select an option', 'ppsgen'),
              'show' => esc_html__('Show only', 'ppsgen'),
              'hide' => esc_html__('Hide', 'ppsgen'),
            ],
            'class' => 'ppsgenselect data-option-toggle-trigger',
            'selected' => $component->value($settings['hide_for_search_engines_action'] ?? null, ''),
          ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[hide_for_search_engines_action]" data-toggle-val="show,hide">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Search Engines or Social Media', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific search engines or social media to show or hide the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[hide_for_search_engines][]', [
            'options' => $this->getModel()->getSearchEngines(),
            'class' => 'ppsgenselect',
            'selected' => !empty($settings['hide_for_search_engines']) ? $settings['hide_for_search_engines'] : [],
          ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for URL Match', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select whether to hide or show the popup for specific URL patterns.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[hide_preg_url_action]', [
            'options' => [
              '' => esc_html__('Select an option', 'ppsgen'),
              'show' => esc_html__('Show only', 'ppsgen'),
              'hide' => esc_html__('Hide', 'ppsgen'),
            ],
            'class' => 'ppsgenselect data-option-toggle-trigger',
            'selected' => $component->value($settings['hide_preg_url_action'] ?? null, ''),
          ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[hide_preg_url_action]" data-toggle-val="show,hide">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('URL Patterns', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('One per line or comma: /pricing/* (* is a wildcard), a part of the address such as /blog/, or a regular expression.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[hide_preg_url]', [
            'value' => $component->value($settings['hide_preg_url'] ?? null, ''),
            'attrs' => 'placeholder="/page/*" class="" style="width: 100%;"',
          ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide/Show for User Roles', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select whether to hide or show the popup for specific user roles.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[hide_for_user_roles_action]', [
            'options' => [
              '' => esc_html__('Select an option', 'ppsgen'),
              'show' => esc_html__('Show only', 'ppsgen'),
              'hide' => esc_html__('Hide', 'ppsgen'),
            ],
            'class' => 'ppsgenselect data-option-toggle-trigger',
            'selected' => $component->value($settings['hide_for_user_roles_action'] ?? null, ''),
          ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[hide_for_user_roles_action]" data-toggle-val="show,hide">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('User Roles', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific user roles to show or hide the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php
          $userRoles = array_reverse(wp_roles()->get_names(), true);
$component->selectmultiple('[hide_for_user_roles][]', [
  'options' => $userRoles,
  'class' => 'ppsgenselect',
  'selected' => !empty($settings['hide_for_user_roles']) ? $settings['hide_for_user_roles'] : [],
]);
?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[hide_for_languages_action]" data-toggle-val="show,hide">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Languages', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select specific languages to show or hide the popup.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[hide_for_languages][]', [
        'options' => $component->getLanguages(),
        'class' => 'ppsgenselect',
        'selected' => !empty($settings['hide_for_languages']) ? $settings['hide_for_languages'] : [],
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide for Logged-in Users', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Hide the popup for logged-in users.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[hide_for_logged_in]', [
        'checked' => $component->value($settings['hide_for_logged_in'] ?? null, '0'),
        'class' => '',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show Only for Logged-in Users', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Show the popup only for logged-in users.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[show_for_logged_in]', [
        'checked' => $component->value($settings['show_for_logged_in'] ?? null, '0'),
        'class' => '',
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hide Other Popups', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Hide other active popups when this popup is displayed.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[hide_other]', [
        'checked' => $component->value($settings['hide_other'] ?? null, '0'),
        'class' => '',
      ]); ?>
    </div>
  </div>

  <?php if (!empty($options['obj_id'])) { ?>
  <h3><?php esc_html_e('Date Info', 'ppsgen'); ?></h3>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Created', 'ppsgen'); ?>"  ></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[created_at]', ['value' => $component->value($options['created_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Updated', 'ppsgen'); ?>"  ></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[updated_at]', ['value' => $component->value($options['updated_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <?php } ?>

</div>