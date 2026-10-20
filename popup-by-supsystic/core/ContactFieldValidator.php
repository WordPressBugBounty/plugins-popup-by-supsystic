<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactFieldValidatorPpsgen
{
  protected array $errors = [];
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

  public function validate(array $fields)
  {
    $this->errors = [];
    $contactModule = $this->getFrame()->getModule('contact');
    $contactFieldSubMod = $contactModule->getSubmod('contact_field');
    $contactFieldSubModModel = $contactFieldSubMod->getModel();
    foreach ($fields as $slug => $fieldVal) {
      $slug = preg_replace('/[^a-zA-Z0-9_]/', '', $slug);
      $fieldObj = $contactFieldSubModModel->getContactFieldBySlug($slug);
      if (!empty($fieldObj)) {
        $value = $fieldVal ?? null;
        $type = $fieldObj['field_type'];
        $settings = $contactFieldSubModModel->_prepareValueFromStore($fieldObj['settings'], 'json') ?? [];
        $options = $contactFieldSubModModel->_prepareValueFromStore($fieldObj['options'], 'json') ?? [];
        unset($fieldObj);
        $this->validateField($slug, $type, $value, $settings, $options);
      } else {
        $this->errors[$slug] = esc_html__('Specific name field can not be found in system', 'ppsgen');
      }
    }
    return empty($this->errors);
  }

  public function validateFrontend(array $fields, $form_id)
  {
    $this->errors = [];
    $contactModule = $this->getFrame()->getModule('contact');
    $contactFieldSubMod = $contactModule->getSubmod('contact_field');
    $contactFieldSubModModel = $contactFieldSubMod->getModel();
    $formFields = $this->getFrame()->getModule('form')->getModel()->getFormFields($form_id, 'merge');
    $clearedFields = [];
    foreach ($formFields as $key => $formField) {
      $fieldExistInData = false;
      $formFieldSettings = !empty($formField['settings']) ? $formField['settings'] : [];
      $formFieldOptions = !empty($formField['options']) ? $formField['options'] : [];
      $formFieldSlug = !empty($formFieldSettings['field_slug']) ? $formFieldSettings['field_slug'] : '';
      $formFieldType = !empty($formFieldSettings['field_type']) ? $formFieldSettings['field_type'] : '';
      if ($formFieldType == 'captcha') {
        $this->validateCaptcha();
        continue;
      }
      $fieldDataVal = '';
      if (!empty($formFieldSlug) && isset($fields[$formFieldSlug])) {
        $fieldExistInData = true;
        $fieldDataVal = $fields[$formFieldSlug];
      }
      if (!$fieldExistInData) {
        if (!empty($formField['settings']['required'])) {
          $this->errors[$formFieldSlug] = esc_html__('This field is required.', 'ppsgen'); //Specific name field can not be found in your data.
          continue;
        }
        $clearedFields[$formFieldSlug] = '';
      }
      $res = $this->validateField($formFieldSlug, $formFieldType, $fieldDataVal, $formFieldSettings, $formFieldOptions);
      if ($res) {
        $clearedFields[$formFieldSlug] = $fieldDataVal;
      }
    }
    if (empty($this->errors)) {
      return $clearedFields;
    }
    return false;
  }

  protected function validateField(string $name, string $type, $value, $settings = [], $options = [])
  {
    $label = !empty($settings['label']) ? $settings['label'] : $name;
    if (!empty($settings['required']) && $this->isEmptyValue($value)) {
      $this->errors[$name] = esc_html__('This field is required.', 'ppsgen');
      return false;
    }

    if (in_array($type, ['text', 'textarea', 'email', 'phone'])) {
      if (is_string($value) && $value !== null && $value !== '') {
        $len = mb_strlen($value);
        if (!empty($settings['min_length']) && $len < (int) $settings['min_length']) {
          // translators: %d - Minimum length in characters
          $this->errors[$name] = sprintf(esc_html__('Minimum length is %d characters.', 'ppsgen'), $settings['min_length']);
          return false;
        }
        if (!empty($settings['max_length']) && $len > (int) $settings['max_length']) {
          // translators: %d - Maximum length in characters
          $this->errors[$name] = sprintf(esc_html__('Maximum length is %d characters.', 'ppsgen'), $settings['max_length']);
          return false;
        }
      }
    }

    if ($type === 'date' && is_string($value) && $value !== '') {
      $date = DateTime::createFromFormat('Y-m-d', $value);
      if (!$date || $date->format('Y-m-d') !== $value) {
        $this->errors[$name] = esc_html__('Please enter a valid date.', 'ppsgen');
        return false;
      }
    }
    if (in_array($type, ['range', 'rating'], true) && $value !== null && $value !== '' && !is_numeric($value)) {
      $this->errors[$name] = esc_html__('This field must be a number.', 'ppsgen');
      return false;
    }

    if ($type === 'number' && $value !== null && $value !== '') {
      if (!is_numeric($value)) {
        $this->errors[$name] = esc_html__('This field must be a number.', 'ppsgen');
        return false;
      }
      if (isset($settings['min']) && $value < $settings['min']) {
        // translators: %s - Minimum allowed value in numeric
        $this->errors[$name] = sprintf(esc_html__('Minimum allowed value is %s.', 'ppsgen'), $settings['min']);
        return false;
      }
      if (isset($settings['max']) && $value > $settings['max']) {
        // translators: %s - Maximum allowed value in numeric
        $this->errors[$name] = sprintf(esc_html__('Maximum allowed value is %s.', 'ppsgen'), $settings['max']);
        return false;
      }
    }

    if ($type === 'email' && !empty($value)) {
      if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $this->errors[$name] = esc_html__('Invalid email address format.', 'ppsgen');
        return false;
      }
    }

    if (in_array($type, ['password', 'text', 'textarea'])) {
      if (!empty($settings['pattern']) && !empty($value)) {
        $pattern = '/' . str_replace('/', '\/', $settings['pattern']) . '/u';
        if (!preg_match($pattern, $value)) {
          // translators: %s - The field has an invalid format
          $this->errors[$name] = sprintf(esc_html__('The field "%s" has an invalid format.', 'ppsgen'), $label);
          return false;
        }
      }
    }

    if (!empty($settings['mask']) && !$this->validateByInputMask($settings['mask'], (string) $value) && $value !== null && $value !== '') {
      // translators: %s - Field does not match mask format
      $this->errors[$name] = sprintf(esc_html__('Field "%s" does not match mask format.', 'ppsgen'), $label);
      return false;
    }

    if (in_array($type, ['checkbox', 'radio', 'select'], true)) {
      $filteredOptions = array_filter($options, function ($opt) {
        return empty($opt['remove']) || $opt['remove'] === '0';
      });

      $allowedValues = array_column($filteredOptions, 'value');
      $isMultiple = !empty($settings['multiple_select']);
      if ($type == 'checkbox') {
        $isMultiple = true;
      }
      $selected = is_array($value) ? $value : (array) $value;

      foreach ($selected as $selectedValue) {
        if (empty($selectedValue)) {
          continue;
        }
        if (!in_array($selectedValue, $allowedValues, true)) {
          $this->errors[$name] = esc_html__('Selected value is not allowed.', 'ppsgen');
          return false;
        }
      }

      if (!$isMultiple && count($selected) > 1) {
        $this->errors[$name] = esc_html__('Multiple selections are not allowed for this field.', 'ppsgen');
        return false;
      }
    }

    return true;
  }

  protected function validateCaptcha()
  {
    $formModule = $this->getFrame()->getModule('form');
    $formModel = $formModule->getModel();
    $recaptchaType = $formModel->getOption('bot_protection', 'options', 0, 'form');
    if ($recaptchaType == 'google_recaptcha_v2') {
      $skey = $formModel->getOption('google_recaptchav2_secret', 'options', 0, 'form');
    }
    if ($recaptchaType == 'google_recaptcha_v2_hidden') {
      $skey = $formModel->getOption('google_recaptchav2_hidden_secret', 'options', 0, 'form');
    }
    if ($recaptchaType == 'honeypot') {
      $honeypot = $this->getFrame()->core('request')->getVar('hnp', 'post');
      if (!empty($honeypot)) {
        $this->errors['captcha'] = esc_html__('Bot is detected.', 'ppsgen');
        return false;
      }
      return true;
    }
    if (in_array($recaptchaType, ['google_recaptcha_v2', 'google_recaptcha_v2_hidden'])) {
      return $this->verifyGrecaptchaV2($skey);
    }
    return false;
  }

  protected function verifyGrecaptchaV2(string $secretKey = ''): bool
  {
    $captcha = $this->getFrame()->core('request')->getVar('g-recaptcha-response', 'post');
    $captcha = !empty($captcha) ? $captcha : '';
    if (empty($captcha)) {
      $this->errors['captcha'] = esc_html__('Captcha is required.', 'ppsgen');
      return false;
    }
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $response = wp_remote_post($verifyUrl, [
      'body' => [
        'secret' => $secretKey,
        'response' => $captcha,
        'remoteip' => $this->getFrame()->core('request')->getServerVar('REMOTE_ADDR'),
      ],
    ]);
    if (is_wp_error($response)) {
      return false;
    }
    $responseBody = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($responseBody['success']) && $responseBody['success'] === true) {
      return true;
    } else {
      $this->errors['captcha'] = esc_html__('Captcha error.', 'ppsgen');
      return false;
    }
  }

  protected function validateByInputMask(string $mask, string $value): bool
  {
    $map = [
      '9' => '[0-9]',
      'a' => '[a-zA-Z]',
      'A' => '[A-Z]',
      'l' => '[a-z]',
      'c' => '[а-яё]',
      'C' => '[А-ЯЁ]',
      'k' => '[А-Яа-яЁё]',
      'u' => '\p{L}',
      '*' => '[a-zA-ZА-Яа-яЁё0-9]',
      '&' => '[A-ZА-ЯЁ0-9]',
      '#' => '[\p{L}\d!@#$%^&*()\-_=+,.<>?]',
    ];
    $regex = '';
    $len = mb_strlen($mask);
    for ($i = 0; $i < $len; $i++) {
      $char = mb_substr($mask, $i, 1);
      $regex .= $map[$char] ?? preg_quote($char, '/');
    }
    $pattern = '/^' . $regex . '$/u';
    return (bool) preg_match($pattern, $value);
  }

  protected function isEmptyValue($value): bool
  {
    if (is_array($value)) {
      return count($value) === 0 || !array_filter($value, fn($v) => $v !== '0');
    }
    return $value === null || $value === '';
  }

  public function getErrors(): array
  {
    return $this->errors;
  }

  public function hasErrors(): bool
  {
    return !empty($this->errors);
  }
}
