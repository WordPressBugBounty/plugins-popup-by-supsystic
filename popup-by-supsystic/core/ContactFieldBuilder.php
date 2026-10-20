<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactFieldBuilderPpsgen
{
  // date, range and rating are Free (basic extra fields in the Free-to-PRO review)
  protected $supportedFields = ['text', 'password', 'textarea', 'number', 'email', 'phone', 'checkbox', 'radio', 'select', 'hidden', 'submit', 'title', 'empty', 'divider', 'description', 'captcha', 'date', 'range', 'rating'];
  protected FramePpsgen $_frame;

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_frame = $frame;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function build($fieldType = 'text', $settings = [], $attrs = [], $options = [])
  {
    if (!in_array($fieldType, $this->supportedFields, true)) {
      return '';
    }
    $method = 'build' . ucfirst($fieldType);
    if (method_exists($this, $method)) {
      if ($fieldType != 'hidden') {
        // if (!is_admin()) {
        $colMob = !empty($settings['col_mob']) ? $settings['col_mob'] : '12';
        $colTab = !empty($settings['col_tab']) ? $settings['col_tab'] : '12';
        $colPc = !empty($settings['col_pc']) ? $settings['col_pc'] : '12';
        $showMob = !empty($settings['show_mob']) ? '' : 'ppsgen-form-field-mob-hide';
        $showTab = !empty($settings['show_tab']) ? '' : 'ppsgen-form-field-tab-hide';
        $showPc = !empty($settings['show_pc']) ? '' : 'ppsgen-form-field-pc-hide';
        $html = '<div class="ppsgen-form-field-container ' . esc_attr($showMob) . ' ' . esc_attr($showTab) . ' ' . esc_attr($showPc) . ' ppsgen-form-field-mob-' . esc_attr($colMob) . ' ppsgen-form-field-tab-' . esc_attr($colTab) . ' ppsgen-form-field-pc-' . esc_attr($colPc) . '">';
        // } else {
        //   $html = '<div class="ppsgen-form-field-container>';
        // }
        if (!empty($settings['field_key'])) {
          $attrs['data-fkey'] = $settings['field_key'];
        }
        $html .= '<div class="ppsgen-form-input-wrapper ppsgen-form-input' . esc_attr($fieldType) . 'Wrap">';
        $html .= $this->buildLabel($settings, $fieldType);
        $html .= '<div class="ppsgen-form-inputFieldWrap">';
        $html .= $this->$method($settings, $attrs, $options);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->getFrame()->core('helper')->wpKses($html);
      }
      if ($fieldType == 'hidden') {
        $html = $this->$method($settings, $attrs, $options);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->getFrame()->core('helper')->wpKses($html);
      }
    }
    return '';
  }

  protected function buildDate($settings, $attrs, $options)
  {
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $attrs['type'] = 'date';
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    return '<input' . $this->renderAttrs($attrs) . ' />';
  }

  protected function buildRange($settings, $attrs, $options)
  {
    $attrs['type'] = 'range';
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    foreach (['min', 'max', 'step'] as $limit) {
      if (isset($settings[$limit]) && $settings[$limit] !== '') {
        $attrs[$limit] = $settings[$limit];
      }
    }
    return '<input' . $this->renderAttrs($attrs) . ' />';
  }

  protected function buildRating($settings, $attrs, $options)
  {
    $stars = !empty($settings['stars_count']) ? (int) $settings['stars_count'] : (!empty($settings['max']) ? (int) $settings['max'] : 5);
    $stars = max(1, min(10, $stars));
    $currentValue = !empty($attrs['value']) ? $attrs['value'] : ($settings['predefined_value'] ?? '');
    $fkey = !empty($settings['field_key']) ? $settings['field_key'] : '';
    $iconClass = !empty($settings['icon_class']) ? $settings['icon_class'] : 'ppsgen-fa-solid fa-star';
    $html = '<div class="ppsgen-form-input-rating-wrapper" data-rating-id="' . esc_attr(uniqid('ppsgen_rating_')) . '">';
    for ($i = 1; $i <= $stars; $i++) {
      $checked = (string) $i === (string) $currentValue ? ' checked' : '';
      $html .= '<label><input style="display:none;" type="radio" data-fkey="' . esc_attr($fkey) . '" name="' . esc_attr($attrs['name'] ?? '') . '" value="' . esc_attr($i) . '"' . $checked . ' /><i class="' . esc_attr($iconClass) . '"></i></label>';
    }
    return $html . '</div>';
  }

  protected function renderAttrs(array $attrs): string
  {
    $html = '';
    foreach ($attrs as $key => $value) {
      if (is_bool($value)) {
        $value = $value ? '1' : '0';
      }
      $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    return $html;
  }

  protected function buildLabel($settings, $fieldType)
  {
    if (in_array($fieldType, ['title', 'description', 'divider', 'empty', 'captcha', 'submit'])) {
      return '';
    }
    if (empty($settings['hide_label'])) {
      $html = '';
      $labelText = isset($settings['label']) ? $settings['label'] : '';
      $html .= '<div class="ppsgen-form-inputLabelWrap">';
      if (!empty($settings['show_tooltip']) && !empty($settings['title'])) {
        $html .= $this->buildTooltip($settings);
      }
      $html .= '<span class="ppsgen-form-inputLabel">';
      $html .= esc_html($labelText);
      $html .= '</span>';
      if (!empty($settings['required'])) {
        $html .= '<span class="ppsgen-form-inputRequired">*</span>';
      }
      $html .= '</div>';
      return $html;
    }
  }

  protected function buildTooltip($settings)
  {
    if (!empty($settings['show_tooltip']) && !empty($settings['title'])) {
      return $html = '<span class="ppsgen-form-inputTooltip"><span class="ppsgen-form-inputTooltipIcon fappsgen9 fappsgen-question"></span><span class="ppsgen-form-inputTooltipText">' . esc_html($settings['title']) . '</span></span>';
    }
  }

  protected function buildDescription($settings, $attrs, $options)
  {
    $descText = !empty($settings['html_description_text']) ? $settings['html_description_text'] : '';
    $html = '<div' . $this->renderAttrs($attrs) . ' >' . $this->getFrame()->core('helper')->wpKses($descText) . '</div>';
    return $html;
  }

  protected function buildEmpty($settings, $attrs, $options)
  {
    if (isset($attrs['name'])) {
      unset($attrs['name']);
    }
    if (isset($attrs['value'])) {
      unset($attrs['value']);
    }
    $html = '<div' . $this->renderAttrs($attrs) . ' ></div>';
    return $html;
  }

  protected function buildDivider($settings, $attrs, $options)
  {
    if (isset($attrs['name'])) {
      unset($attrs['name']);
    }
    if (isset($attrs['value'])) {
      unset($attrs['value']);
    }
    $html = '<div' . $this->renderAttrs($attrs) . ' ></div>';
    return $html;
  }

  protected function buildTitle($settings, $attrs, $options)
  {
    $titleText = !empty($settings['title_text']) ? $settings['title_text'] : '';
    $attrs['class'] = isset($attrs['class']) ? $attrs['class'] : '';
    $attrs['class'] .= ' f_' . ($settings['field_key'] ?? '') . ' ';
    $attrs['class'] .= ' ppsgen-form-field-title';
    if (isset($attrs['name'])) {
      unset($attrs['name']);
    }
    if (isset($attrs['value'])) {
      unset($attrs['value']);
    }
    $html = '<div' . $this->renderAttrs($attrs) . ' >' . esc_html($titleText) . '</div>';
    return $html;
  }

  protected function buildText($settings, $attrs, $options)
  {
    $attrs['type'] = 'text';
    if (!empty($settings['placeholder'])) {
      $attrs['placeholder'] = $settings['placeholder'];
    }
    if (!empty($settings['min_length'])) {
      $attrs['minlength'] = (int) $settings['min_length'];
    }
    if (!empty($settings['max_length'])) {
      $attrs['maxlength'] = (int) $settings['max_length'];
    }
    if (!empty($settings['pattern'])) {
      $attrs['pattern'] = $this->getFrame()->core('helper')->backslashDecode($settings['pattern']);
    }
    if (!empty($settings['title'])) {
      $attrs['title'] = $settings['title'];
    }
    if (!empty($settings['autocomplete'])) {
      $attrs['autocomplete'] = $settings['autocomplete'];
    }
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    if (!empty($settings['mask'])) {
      $attrs['data-mask'] = $settings['mask'];
      $this->getFrame()->core('component')->loadInputmask();
    }
    $html = '<input' . $this->renderAttrs($attrs) . ' />';
    return $html;
  }

  protected function buildPassword($settings, $attrs, $options)
  {
    $attrs['type'] = 'password';
    if (!empty($settings['placeholder'])) {
      $attrs['placeholder'] = $settings['placeholder'];
    }
    if (!empty($settings['min_length'])) {
      $attrs['minlength'] = (int) $settings['min_length'];
    }
    if (!empty($settings['max_length'])) {
      $attrs['maxlength'] = (int) $settings['max_length'];
    }
    if (!empty($settings['pattern'])) {
      $attrs['pattern'] = $this->getFrame()->core('helper')->backslashDecode($settings['pattern']);
    }
    if (!empty($settings['title'])) {
      $attrs['title'] = $settings['title'];
    }
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    $html = '<input' . $this->renderAttrs($attrs) . ' />';
    return $html;
  }

  protected function buildSubmit($settings, $attrs, $options)
  {
    $btnText = !empty($settings['submit_text']) ? $settings['submit_text'] : esc_attr__('Submit', 'ppsgen');
    $attrsNew = [];
    $attrsNew['type'] = 'submit';
    if (!empty($settings['class'])) {
      $attrsNew['class'] = $settings['class'];
    }
    $attrsNew['data-fkey'] = $settings['field_key'] ?? '';
    $html = '<input type="hidden" name="ppsgen_form[empty_field]" value="1">';
    $html .= '<button name="ppsgen_form[submit]" ' . $this->renderAttrs($attrsNew) . '>' . $btnText . '</button>';
    return $html;
  }

  protected function buildNumber($settings, $attrs, $options)
  {
    $attrs['type'] = 'number';
    if (!empty($settings['placeholder'])) {
      $attrs['placeholder'] = $settings['placeholder'];
    }
    if (!empty($settings['min'])) {
      $attrs['min'] = (int) $settings['min'];
    }
    if (!empty($settings['max'])) {
      $attrs['max'] = (int) $settings['max'];
    }
    if (!empty($settings['title'])) {
      $attrs['title'] = $settings['title'];
    }
    if (!empty($settings['autocomplete'])) {
      $attrs['autocomplete'] = $settings['autocomplete'];
    }
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    $html = '<input' . $this->renderAttrs($attrs) . ' />';
    return $html;
  }

  protected function buildEmail($settings, $attrs, $options)
  {
    $attrs['type'] = 'email';
    if (!empty($settings['placeholder'])) {
      $attrs['placeholder'] = $settings['placeholder'];
    }
    if (!empty($settings['min_length'])) {
      $attrs['minlength'] = (int) $settings['min_length'];
    }
    if (!empty($settings['max_length'])) {
      $attrs['maxlength'] = (int) $settings['max_length'];
    }
    if (!empty($settings['title'])) {
      $attrs['title'] = $settings['title'];
    }
    // if (!empty($settings['allow_multiple'])) {
    //   $attrs['multiple'] = $settings['allow_multiple'];
    // }
    if (!empty($settings['autocomplete'])) {
      $attrs['autocomplete'] = $settings['autocomplete'];
    }
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    $html = '<input' . $this->renderAttrs($attrs) . ' />';
    return $html;
  }

  protected function buildPhone($settings, $attrs, $options)
  {
    $attrs['type'] = 'tel';
    if (!empty($settings['placeholder'])) {
      $attrs['placeholder'] = $settings['placeholder'];
    }
    if (!empty($settings['min_length'])) {
      $attrs['minlength'] = (int) $settings['min_length'];
    }
    if (!empty($settings['max_length'])) {
      $attrs['maxlength'] = (int) $settings['max_length'];
    }
    if (!empty($settings['title'])) {
      $attrs['title'] = $settings['title'];
    }
    if (!empty($settings['autocomplete'])) {
      $attrs['autocomplete'] = $settings['autocomplete'];
    }
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    if (!empty($settings['mask'])) {
      $attrs['data-mask'] = $settings['mask'];
      $this->getFrame()->core('component')->loadInputmask();
    }
    $html = '<input' . $this->renderAttrs($attrs) . ' />';
    return $html;
  }

  protected function buildTextarea($settings, $attrs, $options)
  {
    $attrs['type'] = 'text';
    $attrs['style'] = !empty($attrs['style']) ? $attrs['style'] : '';
    if (!empty($settings['placeholder'])) {
      $attrs['placeholder'] = $settings['placeholder'];
    }
    if (!empty($settings['min_length'])) {
      $attrs['minlength'] = (int) $settings['min_length'];
    }
    if (!empty($settings['max_length'])) {
      $attrs['maxlength'] = (int) $settings['max_length'];
    }
    if (!empty($settings['pattern'])) {
      $attrs['pattern'] = $this->getFrame()->core('helper')->backslashDecode($settings['pattern']);
    }
    if (!empty($settings['title'])) {
      $attrs['title'] = $settings['title'];
    }
    if (!empty($settings['autocomplete'])) {
      $attrs['autocomplete'] = $settings['autocomplete'];
    }
    if (!empty($settings['rows'])) {
      $attrs['rows'] = $settings['rows'];
    }
    if (!empty($settings['resize'])) {
      $attrs['style'] .= 'resize: ' . esc_attr($settings['resize']) . ';';
    }
    if (!empty($settings['required'])) {
      $attrs['required'] = $settings['required'];
    }
    $curVal = !empty($attrs['value']) ? $attrs['value'] : '';
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    if (!empty($curVal)) {
      $attrs['value'] = $curVal;
    }
    $value = $attrs['value'] ?? '';
    unset($attrs['value']);
    $html = '<textarea' . $this->renderAttrs($attrs) . '>' . esc_textarea($value) . '</textarea>';
    return $html;
  }

  protected function buildRadio($settings, $attrs, $options)
  {
    $selectedValues = [];
    if (!empty($attrs['value'])) {
      $selectedValues = is_array($attrs['value']) ? $attrs['value'] : [$attrs['value']];
      unset($attrs['value']);
      foreach ($options as &$option) {
        $option['checked'] = in_array((string) ($option['value'] ?? ''), array_map('strval', $selectedValues), true);
      }
      unset($option);
    }
    $class = '';
    $class .= !empty($settings['horizontal_style']) ? ' ppsgenCfCboxHorizStyle ' : 'ppsgenCfCboxVertStyle';
    $html = '<div class="' . esc_attr($class) . '">';
    foreach ($options as $key => $option) {
      if (!empty($option['remove'])) {
        continue;
      }
      $optionValue = isset($option['value']) ? $option['value'] : '';
      $optionLabel = isset($option['label']) ? $option['label'] : $optionValue;
      $html .= '<label><input type="radio"';
      $html .= ' value="' . esc_attr($optionValue) . '"';
      if (!empty($settings['required'])) {
        $html .= ' required ';
      }
      if (!empty($option['checked'])) {
        $html .= ' checked ';
      }
      if (!empty($option['disabled'])) {
        $html .= ' disabled ';
      }
      $html .= $this->renderAttrs($attrs);
      $html .= '/>';
      $html .= '<span>' . esc_html($optionLabel) . '</span>';
      $html .= '</label>';
    }
    $html .= '</div>';
    return $html;
  }

  protected function buildCheckbox($settings, $attrs, $options)
  {
    $selectedValues = [];
    if (!empty($attrs['value'])) {
      $selectedValues = is_array($attrs['value']) ? $attrs['value'] : [$attrs['value']];
      unset($attrs['value']);
      $selectedValues = array_map('strval', $selectedValues);
      foreach ($options as &$option) {
        $option['checked'] = in_array((string) ($option['value'] ?? ''), $selectedValues, true);
      }
      unset($option);
    }
    $attrs['name'] = $attrs['name'] . '[]';
    $class = '';
    $html = '<input type="hidden" name="' . esc_attr($attrs['name']) . '" value="0">';
    $class .= !empty($settings['horizontal_style']) ? ' ppsgenCfCboxHorizStyle ' : 'ppsgenCfCboxVertStyle';
    if (!empty($settings['required'])) {
      $attrs['data-set-required'] = 'true';
    }
    $html .= '<div class="' . esc_attr($class) . '">';
    foreach ($options as $key => $option) {
      if (!empty($option['remove'])) {
        continue;
      }
      $optionValue = isset($option['value']) ? $option['value'] : '';
      $optionLabel = isset($option['label']) ? $option['label'] : $optionValue;
      $html .= '<label><input type="checkbox" ';
      $html .= ' value="' . esc_attr($optionValue) . '"';
      if (!empty($option['checked'])) {
        $html .= ' checked ';
      }
      if (!empty($option['disabled'])) {
        $html .= ' disabled ';
      }
      $html .= $this->renderAttrs($attrs);
      $html .= '/>';
      $html .= '<span>' . esc_html($optionLabel) . '</span>';
      $html .= '</label>';
    }
    $html .= '</div>';
    return $html;
  }

  protected function buildSelect($settings, $attrs, $options)
  {
    $attrs['name'] = $attrs['name'] . '[]';
    if (!empty($settings['multiple_select'])) {
      $settings['custom_select'] = true;
    }
    if (!empty($settings['custom_select'])) {
      $attrs['class'] = !empty($attrs['class']) ? $attrs['class'] . ' ppsgenselect ' : ' ppsgenselect ';
      $this->getFrame()->core('component')->loadPpsgenselect();
    }
    $selectedValues = [];
    if (!empty($attrs['value'])) {
      $selectedValues = is_array($attrs['value']) ? $attrs['value'] : [$attrs['value']];
      unset($attrs['value']);
      $selectedValues = array_map('strval', $selectedValues);
      foreach ($options as &$option) {
        $option['checked'] = in_array((string) ($option['value'] ?? ''), $selectedValues, true);
      }
      unset($option);
    }
    if (!empty($settings['required'])) {
      $attrs['data-set-required'] = 'true';
    }
    $html = '<select';
    $html .= $this->renderAttrs($attrs);
    if (!empty($settings['multiple_select'])) {
      $html .= ' multiple ';
      if (!empty($settings['multiple_select_rows'])) {
        $html .= ' size="' . esc_attr($settings['multiple_select_rows']) . '"';
      }
    }
    $html .= '>';
    foreach ($options as $key => $option) {
      if (!empty($option['remove'])) {
        continue;
      }
      $optionValue = isset($option['value']) ? $option['value'] : '';
      $optionLabel = isset($option['label']) ? $option['label'] : $optionValue;
      $html .= '<option value="' . esc_attr($optionValue) . '"';
      if (!empty($option['checked'])) {
        $html .= ' selected ';
      }
      if (!empty($option['disabled'])) {
        $html .= ' disabled ';
      }
      $html .= '>';
      $html .= esc_html($optionLabel);
      $html .= '</option>';
    }
    $html .= '</select>';
    return $html;
  }

  protected function buildHidden($settings, $attrs, $options)
  {
    if (!empty($settings['predefined_value'])) {
      $attrs['value'] = $settings['predefined_value'];
    }
    $attrs['type'] = 'hidden';
    return '<input' . $this->renderAttrs($attrs) . ' />';
  }

  protected function buildCaptcha($settings, $attrs, $options)
  {
    $recaptchaType = $this->getFrame()->getModule('form')->getModel()->getOption('bot_protection', 'options', 0, 'form');
    if ($recaptchaType == 'none') {
      return '';
    }
    $fkey = 'f_' . esc_attr($settings['field_key'] ?? '');
    $captchaHtml = '';
    if ($recaptchaType == 'google_recaptcha_v2') {
      $publicKey = $this->getFrame()->getModule('form')->getModel()->getOption('google_recaptchav2_public', 'options', 0, 'form');
      if (!empty($publicKey)) {
        $this->getFrame()->core('component')->loadRecaptchaV2();
        $captchaHtml = '<div class="g-recaptcha ppsgen-recaptcha ' . esc_attr($fkey) . '" data-sitekey="' . esc_attr($publicKey) . '"></div>';
        $captchaHtml .= '<input type="hidden" name="form[captcha]" value="1"/>';
        $this->loadCaptchaGoogleCheckboxV2($fkey);
      }
    }
    if ($recaptchaType == 'google_recaptcha_v2_hidden') {
      $publicKey = $this->getFrame()->getModule('form')->getModel()->getOption('google_recaptchav2_hidden_public', 'options', 0, 'form');
      if (!empty($publicKey)) {
        $this->getFrame()->core('component')->loadRecaptchaV2();
        $captchaHtml = '<div class="ppsgen-recaptcha ppsgen-invisible-recaptcha ' . esc_attr($fkey) . '" id="ppsgen-invisible-recaptcha-' . esc_attr($fkey) . '" data-sitekey="' . esc_attr($publicKey) . '"></div>';
        $captchaHtml .= '<input type="hidden" name="form[captcha]" value="1"/>';
        $this->loadCaptchaGoogleHiddenV2($fkey);
      }
    }
    if ($recaptchaType == 'honeypot') {
      $captchaHtml = $this->renderHnp();
      $captchaHtml .= '<input type="hidden" name="form[captcha]" value="1"/>';
    }
    $wrapperKey = 'f_' . ($settings['field_key'] ?? '') . '_wrapper';
    $attrs['class'] = isset($attrs['class']) ? $attrs['class'] . ' ' : ' ';
    $attrs['class'] .= $wrapperKey;
    $attrs['class'] .= ' ppsgenCaptchaField';
    if (isset($attrs['name'])) {
      unset($attrs['name']);
    }
    if (isset($attrs['value'])) {
      unset($attrs['value']);
    }
    $html = '<div' . $this->renderAttrs($attrs) . ' >' . $captchaHtml . '</div>';
    return $html;
  }

  protected function renderHnp()
  {
    $html = '<div class="ppsgenHnp"><label>' . esc_html__('Leave this field empty', 'ppsgen') . '<input type="text" name="hnp" autocomplete="off" /></label></div>';
    return $html;
  }

  protected function loadCaptchaGoogleCheckboxV2($fkey)
  {
    // prettier-ignore
    wp_add_inline_script(
      'ppsgen-recaptcha-v2',
      "
        window.ppsgenRecaptchaCallbacks = window.ppsgenRecaptchaCallbacks || [];
        window.ppsgenRecaptchaCallbacks.push(function() {
          document.querySelectorAll('.g-recaptcha.ppsgen-recaptcha." . esc_attr($fkey) . "').forEach(function(el) {
            if (!el.dataset.rendered) {
              var widgetId = grecaptcha.render(el, { sitekey: el.dataset.sitekey });
              el.dataset.rendered = 1;
              var form = el.closest('form');
              if (form) {
                form.dataset.ppsgenWidgetId = widgetId;
              }
            }
          });
        });
        if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.render === 'function') {
          window.ppsgenRecaptchaCallbacks.forEach(function(cb) { cb(); });
        }
      ",
    );
  }

  protected function loadCaptchaGoogleHiddenV2($fkey)
  {
    // prettier-ignore
    $inlineScript =
      '
              window.ppsgenRecaptchaCallbacks = window.ppsgenRecaptchaCallbacks || [];
              window.ppsgenRecaptchaCallbacks.push(function() {
                  var el = document.getElementById("ppsgen-invisible-recaptcha-' .esc_attr($fkey) .'");
                  if (!el || el.dataset.rendered) return;
                  el.dataset.rendered = true;
                  try {
                      var widgetId = grecaptcha.render(el, {
                          sitekey: el.dataset.sitekey,
                          size: "invisible",
                          callback: function(token) {
                              var form = el.closest("form");
                              if (form) {
                                  var oldTokens = form.querySelectorAll("[name=\'g-recaptcha-response\']");
                                  oldTokens.forEach(function(token) { token.remove(); });
                                  var tokenInput = document.createElement("input");
                                  tokenInput.type = "hidden";
                                  tokenInput.name = "g-recaptcha-response";
                                  tokenInput.value = token;
                                  form.appendChild(tokenInput);
                                  window.ppsgenHandleFormSubmit(form);
                              }
                          },
                          "error-callback": function() {
                              console.error("reCAPTCHA verification failed");
                          }
                      });
                      el.closest("form").dataset.ppsgenWidgetId = widgetId;
                      var form = el.closest("form");
                      if (form) {
                          form.addEventListener("submit", function(e) {
                              if (!form.querySelector("[name=\'g-recaptcha-response\']")) {
                                  e.preventDefault();
                                  grecaptcha.execute(widgetId);
                              }
                          });
                      }
                  } catch (error) {
                      console.error("reCAPTCHA initialization error:", error);
                  }
              });
          ';
    wp_add_inline_script('ppsgen-recaptcha-v2', $inlineScript);
  }
}
