<?php

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Turns one form of Contact Form by Supsystic (a row of {prefix}cfs_forms) into the data of a CES form.
 * It only reads arrays it is given and never touches the database: CfImportSubmoduleModelPpsgen loads the rows,
 * resolves contact fields (the resolver callback) and writes the result.
 */
class CfImportConverterPpsgen
{
  /** Contact Form field type => CES field type in Free (PRO types come from proTypes) */
  protected $freeTypes = [
    'text' => 'text',
    'email' => 'email',
    'textarea' => 'textarea',
    'wptextarea' => 'textarea',
    'number' => 'number',
    'hidden' => 'hidden',
    'selectbox' => 'select',
    'selectlist' => 'select',
    'radiobutton' => 'radio',
    'radiobuttons' => 'radio',
    'checkbox' => 'checkbox',
    'checkboxlist' => 'checkbox',
    'checkboxsubscribe' => 'checkbox',
    'countryList' => 'select',
    'countryListMultiple' => 'select',
    'date' => 'date',
    'range' => 'range',
    'rating' => 'rating',
    'recaptcha' => 'captcha',
    'recaptcha_v3' => 'captcha',
    'hcaptcha' => 'captcha',
    'turnstile' => 'captcha',
    'submit' => 'submit',
    'htmldelim' => 'description',
    'month' => 'text',
    'week' => 'text',
    'time' => 'text',
    'color' => 'text',
    'url' => 'text',
    'address' => 'text',
    'wpcategories' => 'select',
  ];

  /** Types that CES PRO has as its own field types */
  protected $proTypes = [
    'month' => 'month',
    'week' => 'week',
    'time' => 'time',
    'color' => 'color',
    'url' => 'url',
    'address' => 'address',
    'file' => 'file',
    'wpcategories' => 'categories',
    'wooattrs' => 'wooattributes',
  ];

  /** Field names that are the built-in CES contact fields */
  protected $builtinAliases = [
    'email' => 'email',
    'user_email' => 'email',
    'e_mail' => 'email',
    'first_name' => 'first_name',
    'firstname' => 'first_name',
    'fname' => 'first_name',
    'last_name' => 'last_name',
    'lastname' => 'last_name',
    'lname' => 'last_name',
    'phone' => 'phone',
    'tel' => 'phone',
    'telephone' => 'phone',
    'phone_number' => 'phone',
  ];

  /** Slugs CES itself uses inside a submitted form */
  protected $reservedSlugs = ['submit', 'empty_field', 'unverified_submit', 'captcha', 'hnp', 'form_id', 'nonce_code', 'action', 'plugin', 'mod'];

  protected $templateNames = [
    'wefj2' => 'Base Contact',
    'foe42k' => 'Neon',
    'uwi23o' => 'Intransigent',
    'vbn23a' => 'Simple White',
    'bso15i' => 'Time for tea',
    'bjl17b' => 'Opacity Grey',
    'bjl17a' => 'Spearmint',
    'bjl17c' => 'Support Service',
    'ajl17d' => 'Ho Ho Ho',
    'cbrl7b' => 'Merry Christmas',
  ];

  /** @var array isPro, countries (id => name), categories (id => name), cfUrls (placeholder => url), copyAsset (callable) */
  protected $env;
  /** @var callable(array $want): array{slug: string, id: int} */
  protected $resolver;
  protected $moved = [];
  protected $skipped = [];
  /** Something was left out only because CES PRO is not active */
  protected $needsPro = false;

  public function __construct(array $env, callable $resolver)
  {
    $this->env = $env + ['isPro' => false, 'countries' => [], 'categories' => [], 'cfUrls' => [], 'copyAsset' => null, 'senders' => []];
    $this->resolver = $resolver;
  }

  /* ---------------------------------------------------------------- reading */

  /** A row of cfs_forms as Contact Form itself reads it (forms model _afterGetFromTbl) */
  public static function readRow(array $row, array $cfUrls): array
  {
    $params = [];
    $raw = (string) ($row['params'] ?? '');
    if ($raw !== '') {
      $decoded = strpos($raw, ':ORD_ENC') !== false ? self::decodeOrd($raw) : base64_decode($raw, true);
      $params = is_string($decoded) ? @unserialize($decoded, ['allowed_classes' => false]) : false;
      if (!is_array($params)) {
        $params = [];
      }
    }
    $params = self::deepMap($params, static function ($v) use ($cfUrls) {
      return strtr(stripslashes((string) $v), $cfUrls);
    });
    $row['params'] = $params;
    foreach (['html', 'css'] as $key) {
      $row[$key] = strtr((string) ($row[$key] ?? ''), $cfUrls);
    }
    return $row;
  }

  protected static function decodeOrd(string $raw): string
  {
    $out = '';
    foreach (explode('|', str_replace(':ORD_ENC', '', $raw)) as $ord) {
      $out .= chr((int) $ord);
    }
    return $out;
  }

  protected static function deepMap($value, callable $fn)
  {
    if (is_array($value)) {
      foreach ($value as $k => $v) {
        $value[$k] = self::deepMap($v, $fn);
      }
      return $value;
    }
    return is_scalar($value) ? $fn($value) : $value;
  }

  public function getTemplateName(string $uniqueId): string
  {
    return $this->templateNames[$uniqueId] ?? '';
  }

  /** A short view of the form for the list on the import page */
  public function describe(array $form): array
  {
    $params = $form['params'];
    $fields = is_array($params['fields'] ?? null) ? $params['fields'] : [];
    $tpl = is_array($params['tpl'] ?? null) ? $params['tpl'] : [];
    $pro = [];
    $types = [];
    foreach ($fields as $f) {
      $html = (string) ($f['html'] ?? '');
      $types[$html] = true;
    }
    foreach (['file' => __('file upload', 'ppsgen'), 'rating' => __('rating', 'ppsgen'), 'recaptcha_v3' => 'reCAPTCHA v3', 'hcaptcha' => 'hCaptcha', 'turnstile' => 'Turnstile', 'wptextarea' => __('WordPress editor', 'ppsgen'), 'address' => __('address search', 'ppsgen'), 'checkboxsubscribe' => __('subscribe checkbox', 'ppsgen'), 'wpcategories' => __('categories list', 'ppsgen'), 'wooattrs' => __('Woo attributes', 'ppsgen')] as $type => $label) {
      if (isset($types[$type])) {
        $pro[] = $label;
      }
    }
    if (!empty($params['cl']) || !empty($params['cl2'])) {
      $pro[] = __('conditional logic', 'ppsgen');
    }
    if (!empty($tpl['enb_subscribe'])) {
      $pro[] = __('subscription', 'ppsgen');
    }
    if (!empty($tpl['enb_reg'])) {
      $pro[] = __('registration', 'ppsgen');
    }
    if (!empty($tpl['enb_publish'])) {
      $pro[] = __('publish as post', 'ppsgen');
    }
    $count = 0;
    foreach ($fields as $f) {
      if (!in_array((string) ($f['html'] ?? ''), ['submit', 'reset', 'button', 'htmldelim'], true)) {
        $count++;
      }
    }
    return [
      'fields' => $count,
      'template' => $this->getTemplateName((string) ($form['unique_id'] ?? '')),
      'pro' => $pro,
    ];
  }

  /* ---------------------------------------------------------------- converting */

  /**
   * @param array $form    A form from readRow()
   * @param array $options tagIds (tags new contacts get)
   * @return array form (row for ppsgen_forms without id), fields (rows for ppsgen_form_fields without form_id),
   *               logic (row for ppsgen_form_conditional_logic or null), fieldMap (Contact Form name => CES slug),
   *               moved / skipped (what the person reads in the result)
   */
  public function convert(array $form, array $options): array
  {
    $this->moved = [];
    $this->skipped = [];
    $this->needsPro = false;
    $params = $form['params'];
    $tpl = is_array($params['tpl'] ?? null) ? $params['tpl'] : [];
    $cfFields = is_array($params['fields'] ?? null) ? array_values($params['fields']) : [];

    // Fields
    $fields = [];
    $fieldMap = [];
    $fieldKeys = [];
    $fieldTypes = [];
    $emailSlug = '';
    $emailRequired = false;
    $order = 0;
    $htmlDelims = 0;
    $usedSlugs = [];
    $hasCaptcha = false;
    $hasSubmit = false;
    $counts = ['fields' => 0, 'options' => 0];
    foreach ($cfFields as $f) {
      if (!is_array($f)) {
        continue;
      }
      $html = (string) ($f['html'] ?? 'text');
      $name = trim((string) ($f['name'] ?? ''));
      $label = trim((string) ($f['label'] ?? ''));
      $placeholder = trim((string) ($f['placeholder'] ?? ''));
      $title = $label !== '' ? $label : ($placeholder !== '' ? $placeholder : $name);
      if ($name === '' && !in_array($html, ['htmldelim', 'googlemap'], true)) {
        continue;
      }
      if ($html === 'reset' || $html === 'button') {
        $this->skip(sprintf(__('The "%s" button: CES forms have one Submit button only.', 'ppsgen'), $label !== '' ? $label : $html));
        continue;
      }
      if ($html === 'googlemap') {
        $this->skip(__('The Google Map field: add the map shortcode to the page next to the form.', 'ppsgen'));
        continue;
      }
      if ($html === 'file' && empty($this->env['isPro'])) {
        $this->skipPro(sprintf(__('The file upload field "%s": file uploads are in CES PRO.', 'ppsgen'), $title));
        continue;
      }
      if ($html === 'wooattrs' && empty($this->env['isPro'])) {
        $this->skipPro(sprintf(__('The Woo attribute field "%s": it is in CES PRO.', 'ppsgen'), $title));
        continue;
      }
      $cesType = $this->cesType($html);
      if ($cesType === '') {
        $this->skip(sprintf(__('The field "%1$s" (%2$s): CES has no field of this type.', 'ppsgen'), $title, $html));
        continue;
      }
      $fkey = 'cf' . substr(md5($form['id'] . '|' . $order . '|' . $name . '|' . $html), 0, 10);
      $bs = !empty($f['bs_class_id']) ? max(1, min(12, (int) $f['bs_class_id'])) : 12;
      $settings = [
        'field_key' => $fkey,
        'field_type' => $cesType,
        'field_id' => '0',
        'order_index' => (string) ($order + 1),
        'field_slug' => '',
        'required' => !empty($f['mandatory']) ? '1' : '0',
        'col_mob' => '12',
        'col_tab' => (string) $bs,
        'col_pc' => (string) $bs,
        'show_mob' => '1',
        'show_tab' => '1',
        'show_pc' => '1',
        'class' => (string) ($f['add_classes'] ?? ''),
        'id' => '',
        'label' => $label,
        'hide_label' => $label === '' ? '1' : '0',
        'placeholder' => $placeholder,
        'show_tooltip' => '0',
        'title' => '',
        'text_align' => '',
        'title_text' => '',
        'html_description_text' => '',
        'submit_text' => '',
        'predefined_value' => '',
        'min' => '',
        'max' => '',
        'multiple_select' => '0',
        'icon_class' => '',
        'icon_position' => '',
        // Validation as Contact Form did it: these override the defaults of the contact field this field uses
        'pattern' => '',
        'mask' => '',
        'min_length' => '',
        'max_length' => '',
      ];
      $fieldOptions = [];
      $want = null;

      switch ($cesType) {
        case 'submit':
          $settings['submit_text'] = $label !== '' ? $label : __('Send', 'ppsgen');
          $settings['label'] = '';
          $settings['hide_label'] = '1';
          $hasSubmit = true;
          break;
        case 'description':
          $htmlDelims++;
          $settings['html_description_text'] = $this->moveAssets((string) ($f['value'] ?? ''));
          $settings['hide_label'] = '1';
          $settings['label'] = '';
          break;
        case 'captcha':
          $hasCaptcha = true;
          $settings['label'] = '';
          $settings['hide_label'] = '1';
          if ($html !== 'recaptcha') {
            $this->skip(sprintf(__('%s: CES protects forms with reCAPTCHA v2, invisible reCAPTCHA or a honeypot (Settings › Forms). The form uses that protection instead.', 'ppsgen'), $html === 'hcaptcha' ? 'hCaptcha' : ($html === 'turnstile' ? 'Cloudflare Turnstile' : 'reCAPTCHA v3')));
          }
          break;
        default:
          $want = $this->wantContactField($f, $html, $cesType, $name, $title, $usedSlugs, $fieldOptions);
          break;
      }

      if ($want !== null) {
        $resolved = call_user_func($this->resolver, $want);
        if (empty($resolved['slug'])) {
          $this->skip(sprintf(__('The field "%s": its contact field could not be created.', 'ppsgen'), $title));
          continue;
        }
        $settings['field_slug'] = $resolved['slug'];
        $settings['field_id'] = (string) (int) $resolved['id'];
        $usedSlugs[$resolved['slug']] = true;
        $fieldMap[$name] = $resolved['slug'];
        $fieldOptions = $want['options'];
        $this->applyFieldDetails($settings, $f, $html, $cesType, $fieldOptions);
        if ($cesType === 'email' && $emailSlug === '') {
          $emailSlug = $resolved['slug'];
          $emailRequired = $settings['required'] === '1';
        }
        if (!empty($f['add_styles'])) {
          $this->skip(sprintf(__('The inline styles of the field "%s": the look comes from the template you choose in CES.', 'ppsgen'), $title));
        }
        if (!empty($f['add_attr'])) {
          $this->skip(sprintf(__('Extra HTML attributes of the field "%s".', 'ppsgen'), $title));
        }
        if (!empty($f['vn_equal'])) {
          $this->skip(sprintf(__('"%1$s must match %2$s": CES has no such check.', 'ppsgen'), $title, (string) $f['vn_equal']));
        }
        if (!empty($f['value_preset'])) {
          $this->skip(sprintf(__('The dynamic default value of "%s" (%s): the field keeps an empty default.', 'ppsgen'), $title, (string) $f['value_preset']));
        }
        $counts['fields']++;
      }
      if (isset($f['options']) && is_array($f['options'])) {
        $counts['options'] += count($f['options']);
      }
      $fieldKeys[$name !== '' ? $name : $fkey] = $fkey;
      $fieldTypes[$fkey] = $cesType;
      $fields[] = [
        'cf_id' => (int) $settings['field_id'],
        'fkey' => $fkey,
        'settings' => $settings,
        'options' => $fieldOptions,
        'order_index' => $order + 1,
      ];
      $order++;
    }
    if (!$hasSubmit) {
      $fkey = 'cf' . substr(md5($form['id'] . '|submit'), 0, 10);
      $fields[] = [
        'cf_id' => 0,
        'fkey' => $fkey,
        'settings' => ['field_key' => $fkey, 'field_type' => 'submit', 'field_id' => '0', 'order_index' => (string) ($order + 1), 'field_slug' => '', 'required' => '0', 'col_mob' => '12', 'col_tab' => '12', 'col_pc' => '12', 'show_mob' => '1', 'show_tab' => '1', 'show_pc' => '1', 'class' => '', 'id' => '', 'label' => '', 'hide_label' => '1', 'submit_text' => __('Send', 'ppsgen')],
        'options' => [],
        'order_index' => $order + 1,
      ];
    }
    $this->move(sprintf(_n('%d field with its settings and order', '%d fields with their settings and order', $counts['fields'], 'ppsgen'), $counts['fields']));

    // Form type: a contact is created when the form always gets an email
    $formType = $emailSlug !== '' && $emailRequired ? 'create_contact' : 'submit';
    if ($formType === 'create_contact') {
      $this->move(__('Every submission adds the person to Contacts (tag "contact-form")', 'ppsgen'));
    } elseif ($emailSlug !== '') {
      $this->move(__('Submissions are saved in CES. The email field is optional, so the form does not add contacts: make it required in CES to build your list', 'ppsgen'));
    }

    // Settings: messages, emails, subscriptions
    $settings = $this->buildSettings($form, $tpl, $params, $fieldMap, $emailSlug, $options);
    $settings['specific_tags'] = $formType === 'create_contact' ? array_values(array_map('strval', (array) ($options['tagIds'] ?? []))) : [];

    // The look is not moved: the form starts with the standard CES design (an empty design) and the person picks
    // a template in the form editor. The templates of Contact Form are left out on purpose
    $design = [];

    if ($hasCaptcha) {
      $this->move(__('Spam protection: a captcha field', 'ppsgen'));
    }

    // Conditional logic
    $logic = $this->buildLogic($params, $fieldKeys, $fieldTypes, $tpl);

    return [
      'form' => [
        'name' => $this->formName($form),
        'form_type' => $formType,
        'settings' => $settings,
        'design' => $design,
        'active' => !empty($form['active']) ? 1 : 0,
      ],
      'fields' => $fields,
      'logic' => $logic,
      'fieldMap' => $fieldMap,
      'emailSlug' => $emailSlug,
      'hasCaptcha' => $hasCaptcha,
      'captchaKeys' => $this->captchaKeys($cfFields),
      'moved' => $this->moved,
      'skipped' => array_values(array_unique($this->skipped)),
      'needsPro' => $this->needsPro,
    ];
  }

  protected function formName(array $form): string
  {
    $label = trim((string) ($form['label'] ?? ''));
    return $label !== '' ? $label : sprintf(__('Contact Form #%d', 'ppsgen'), (int) $form['id']);
  }

  protected function move(string $text): void
  {
    $this->moved[] = $text;
  }

  protected function skipPro(string $text): void
  {
    $this->needsPro = true;
    $this->skip($text);
  }

  protected function skip(string $text): void
  {
    $this->skipped[] = $text;
  }

  protected function cesType(string $html): string
  {
    if (!empty($this->env['isPro']) && isset($this->proTypes[$html])) {
      return $this->proTypes[$html];
    }
    return $this->freeTypes[$html] ?? '';
  }

  protected function captchaKeys(array $cfFields): array
  {
    foreach ($cfFields as $f) {
      if (($f['html'] ?? '') === 'recaptcha' && !empty($f['recap-sitekey']) && !empty($f['recap-secret'])) {
        return ['public' => (string) $f['recap-sitekey'], 'secret' => (string) $f['recap-secret']];
      }
    }
    return [];
  }

  /** The contact field a Contact Form field needs: a built-in one (email, names, phone) or a new one named after the field */
  protected function wantContactField(array $f, string $html, string $cesType, string $name, string $title, array $usedSlugs, array &$fieldOptions): array
  {
    $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
    $builtin = '';
    if (isset($this->builtinAliases[$key]) && empty($usedSlugs[$this->builtinAliases[$key]])) {
      $builtin = $this->builtinAliases[$key];
    }
    if ($builtin === 'email' && $cesType !== 'email') {
      $builtin = '';
    }
    if ($builtin === '' && $cesType === 'email' && empty($usedSlugs['email'])) {
      $builtin = 'email';
    }
    if (in_array($builtin, ['first_name', 'last_name', 'phone'], true) && !in_array($cesType, ['text'], true)) {
      $builtin = '';
    }
    if ($builtin === '' && $key === 'name' && $cesType === 'text' && empty($usedSlugs['first_name'])) {
      $builtin = 'first_name';
    }
    $options = $this->buildOptions($f, $html);
    $slug = $builtin;
    if ($slug === '') {
      $slug = trim(preg_replace('/_+/', '_', $key), '_');
      if ($slug === '' || is_numeric($slug[0])) {
        $slug = 'cf_' . $slug;
      }
      if (in_array($slug, $this->reservedSlugs, true) || isset($this->builtinAliases[$slug]) || in_array($slug, ['additional_info', 'agreement', 'user_login', 'password'], true)) {
        $slug = 'cf_' . $slug;
      }
      $slug = substr($slug, 0, 60);
    }
    return [
      'slug' => $slug,
      'builtin' => $builtin !== '',
      'label' => $title,
      'field_type' => $cesType === 'phone' ? 'phone' : $cesType,
      'options' => $options,
      'taken' => array_keys($usedSlugs),
    ];
  }

  /** Choices of select, radio and checkbox fields as CES keeps them: key => value, label, checked */
  protected function buildOptions(array $f, string $html): array
  {
    $options = [];
    $add = function ($value, $label, $checked = false) use (&$options) {
      $options['opt_' . (count($options) + 1)] = ['value' => (string) $value, 'label' => (string) $label, 'checked' => $checked ? '1' : '0', 'disabled' => '0'];
    };
    $default = (string) ($f['value'] ?? '');
    switch ($html) {
      case 'selectbox':
      case 'selectlist':
      case 'radiobuttons':
      case 'checkboxlist':
        foreach ((array) ($f['options'] ?? []) as $opt) {
          if (!is_array($opt) || !isset($opt['name'])) {
            continue;
          }
          $value = (string) $opt['name'];
          $add($value, isset($opt['label']) && $opt['label'] !== '' ? $opt['label'] : $value, $default !== '' && in_array($value, array_map('trim', explode(',', $default)), true));
        }
        break;
      case 'checkbox':
      case 'checkboxsubscribe':
      case 'radiobutton':
        $label = (string) ($f['label'] ?? '');
        $add($default !== '' ? $default : 'Yes', $label !== '' ? $label : (string) ($f['placeholder'] ?? $f['name'] ?? ''), !empty($f['def_checked']));
        break;
      case 'countryList':
      case 'countryListMultiple':
        foreach ((array) $this->env['countries'] as $countryName) {
          $add($countryName, $countryName);
        }
        break;
      case 'wpcategories':
        if (empty($this->env['isPro'])) {
          foreach ((array) $this->env['categories'] as $catId => $catName) {
            $add($catId, $catName);
          }
        }
        break;
    }
    return $options;
  }

  protected function applyFieldDetails(array &$settings, array $f, string $html, string $cesType, array $options): void
  {
    $value = (string) ($f['value'] ?? '');
    $min = trim((string) ($f['min_size'] ?? ''));
    $max = trim((string) ($f['max_size'] ?? ''));
    $pattern = trim((string) ($f['vn_pattern'] ?? ''));
    if ($pattern === '0') {
      $pattern = '';
    }
    if (!in_array($html, ['checkbox', 'checkboxsubscribe', 'radiobutton', 'checkboxlist', 'radiobuttons', 'selectbox', 'selectlist', 'countryList', 'countryListMultiple'], true)) {
      $settings['predefined_value'] = $value;
    }
    if (in_array($cesType, ['number', 'range'], true)) {
      $settings['min'] = $min;
      $settings['max'] = $max;
      if ($cesType === 'range' && $settings['min'] === '' && $settings['max'] === '') {
        $settings['min'] = '0';
        $settings['max'] = '100';
      }
    } elseif (in_array($cesType, ['text', 'textarea', 'email', 'phone'], true) && !in_array($html, ['date', 'month', 'week', 'time'], true)) {
      $settings['min_length'] = $min;
      $settings['max_length'] = $max;
    }
    if ($pattern !== '' && in_array($cesType, ['text', 'textarea', 'password'], true)) {
      $settings['pattern'] = $pattern;
    }
    if (in_array($html, ['selectlist', 'countryListMultiple'], true)) {
      $settings['multiple_select'] = '1';
    }
    if (in_array($html, ['checkbox', 'checkboxsubscribe', 'radiobutton'], true)) {
      $settings['hide_label'] = '1';
    }
    if ($html === 'rating') {
      $settings['stars_count'] = (string) max(1, min(10, (int) ($f['rate_num'] ?? 5) ?: 5));
      if (!empty($f['icon_class'])) {
        $settings['icon_class'] = 'ppsgen-fa-solid fa-' . sanitize_html_class(preg_replace('/-o$/', '', (string) $f['icon_class']));
      }
    } elseif (!empty($f['icon_class']) && !in_array($cesType, ['submit', 'checkbox', 'radio'], true)) {
      $icon = sanitize_html_class((string) $f['icon_class']);
      $settings['icon_class'] = (preg_match('/-o$/', $icon) ? 'ppsgen-fa-regular fa-' . preg_replace('/-o$/', '', $icon) : 'ppsgen-fa-solid fa-' . $icon);
      $settings['icon_position'] = 'input_left';
      if (empty($this->env['isPro'])) {
        $this->skipPro(sprintf(__('The icon of the field "%s": field icons show in CES PRO.', 'ppsgen'), $settings['label'] !== '' ? $settings['label'] : $settings['placeholder']));
      }
    }
    if (in_array($html, ['month', 'week', 'time', 'color', 'url', 'address'], true) && empty($this->env['isPro'])) {
      $this->skipPro(sprintf(__('The %1$s field "%2$s" is a text field in CES Free (CES PRO has this field type).', 'ppsgen'), $html, $settings['label'] !== '' ? $settings['label'] : $settings['placeholder']));
    }
    if ($html === 'wptextarea') {
      $this->skip(sprintf(__('The WordPress editor field "%s" is a plain text area in CES.', 'ppsgen'), $settings['label'] !== '' ? $settings['label'] : $settings['placeholder']));
    }
    if ($html === 'checkboxsubscribe') {
      $this->skip(__('The subscribe checkbox is an ordinary checkbox in CES: everyone who sends the form is added to Contacts.', 'ppsgen'));
    }
    if ($html === 'date' && $value !== '') {
      $ts = strtotime($value);
      $settings['predefined_value'] = $ts ? gmdate('Y-m-d', $ts) : '';
    }
  }

  /* ---------------------------------------------------------------- settings */

  protected function buildSettings(array $form, array $tpl, array $params, array $fieldMap, string $emailSlug, array $options): array
  {
    $now = current_time('mysql');
    $replace = $this->placeholderMap($params, $fieldMap);
    $settings = [
      'form_restrictions' => 'show_all',
      'form_restrictions_user_roles' => ['show_all'],
      'form_class' => '',
      'form_id' => '',
      'logform_field_email' => 'email',
      'logform_field_password' => 'password',
      'form_logic_email_field' => $emailSlug !== '' ? $emailSlug : 'email',
      'form_logic' => 'add_submit',
      'form_logic_update_fields' => [],
      'contact_email_confirmation' => '0',
      'contact_email_confirmation_subject' => '',
      'html_contact_email_confirmation_text' => '',
      'signup_email_confirmation' => '0',
      'regform_user_login' => 'user_login',
      'regform_field_email' => 'email',
      'regform_field_password' => 'password',
      'regform_first_name' => 'first_name',
      'regform_last_name' => 'last_name',
      'regform_nick_name' => '',
      'regform_display_name' => '',
      'regform_role' => 'subscriber',
      'resform_field_email' => 'email',
      'redirect_link' => '',
      'html_success_msg' => '',
      'contact_handle_signup' => '0',
      'mail_config_id' => '0',
      'template_id' => '0',
      'send_admin' => '0',
      'send_admin_email' => '',
      'send_admin_subject' => '',
      'html_send_admin_text' => '',
      'send_contact' => '0',
      'send_contact_email_field' => $emailSlug !== '' ? $emailSlug : 'email',
      'send_contact_subject' => '',
      'html_send_contact_text' => '',
      'created_at' => $now,
      'updated_at' => $now,
      'custom_css' => '',
      'custom_js' => '',
    ];

    // After sending
    $msg = trim((string) ($tpl['form_sent_msg'] ?? ''));
    if ($msg === '') {
      $msg = __('Thank you for contacting us!', 'ppsgen');
    }
    $settings['html_success_msg'] = '<p>' . wp_kses_post($msg) . '</p>';
    $this->move(__('The message shown after sending', 'ppsgen'));
    $redirect = trim((string) ($tpl['redirect_on_submit'] ?? ''));
    if ($redirect !== '') {
      $settings['redirect_link'] = esc_url_raw($redirect);
      $this->move(__('The redirect after sending', 'ppsgen'));
      if (!empty($tpl['redirect_on_submit_new_wnd'])) {
        $this->skip(__('Opening the redirect page in a new window: CES opens it in the same window.', 'ppsgen'));
      }
    }
    if (isset($tpl['hide_on_submit']) && !$tpl['hide_on_submit']) {
      $this->skip(__('"Keep the fields after sending": CES shows the message in place of the fields.', 'ppsgen'));
    }
    if (!empty($tpl['field_error_invalid'])) {
      $this->skip(__('Your own text for invalid fields: CES shows its standard messages.', 'ppsgen'));
    }
    if (isset($tpl['save_contacts']) && !$tpl['save_contacts']) {
      $this->skip(__('"Do not save submissions": CES keeps every submission (you can delete them).', 'ppsgen'));
    }

    // Emails
    $this->buildEmails($settings, (array) ($params['submit'] ?? []), $replace, $fieldMap, $params);

    // Subscription, registration, publishing (PRO of Contact Form)
    if (!empty($tpl['enb_subscribe'])) {
      $dest = (string) ($tpl['sub_dest'] ?? '');
      $needConfirm = empty($tpl['sub_ignore_confirm']) && in_array($dest, ['wp_subscribe', ''], true);
      if ($needConfirm && $emailSlug !== '') {
        $settings['contact_email_confirmation'] = '1';
        $settings['contact_email_confirmation_subject'] = $this->convertText((string) ($tpl['sub_txt_confirm_mail_subject'] ?? ''), $replace, true);
        $settings['html_contact_email_confirmation_text'] = $this->convertText((string) ($tpl['sub_txt_confirm_mail_message'] ?? ''), $replace + ['[confirm_link]' => '[activation_link]']);
        $this->move(__('Double opt-in: the confirmation email of the subscription', 'ppsgen'));
      }
      if ($dest === 'wp_subscribe' || $dest === '') {
        $this->skip(__('Subscribers became WordPress users in Contact Form. In CES they are contacts (tag "contact-form"): no user account is created.', 'ppsgen'));
      } else {
        $this->skip(sprintf(__('Sending subscribers to %s: CES keeps the list itself (Contacts, tag "contact-form"), so the connection to that service is not moved. Email them from CES Campaigns or export the contacts.', 'ppsgen'), $this->serviceName($dest)));
      }
    }
    if (!empty($tpl['enb_reg'])) {
      $this->skip(__('Registration of WordPress users: CES does this with a Sign up form (Forms › Add › Sign up), the moved form only saves the contact.', 'ppsgen'));
    }
    if (!empty($tpl['enb_publish'])) {
      $this->skip(__('Publishing submissions as posts: CES has no such option.', 'ppsgen'));
    }
    if (!empty($tpl['enb_fb_convert'])) {
      $this->skip(__('The Facebook conversion: add your pixel event to the page in CES (Custom JS in PRO) or in your theme.', 'ppsgen'));
    }
    if (!empty($params['enableForMembership'])) {
      $this->skip(__('Membership by Supsystic integration.', 'ppsgen'));
    }
    return $settings;
  }

  protected function serviceName(string $dest): string
  {
    $names = ['mailchimp' => 'Mailchimp', 'aweber' => 'AWeber', 'activecampaign' => 'ActiveCampaign', 'get_response' => 'GetResponse', 'constantcontact' => 'Constant Contact', 'campaignmonitor' => 'Campaign Monitor', 'sendinblue' => 'Brevo (Sendinblue)', 'mailpoet' => 'MailPoet', 'convertkit' => 'ConvertKit', 'icontact' => 'iContact', 'infusionsoft' => 'Infusionsoft', 'jetpack' => 'Jetpack', 'sendgrid' => 'SendGrid', 'mailrelay' => 'Mailrelay', 'myemma' => 'Emma', 'salesforce' => 'Salesforce', 'verticalresponse' => 'VerticalResponse', 'benchmarkemail' => 'Benchmark Email', 'feedblitz' => 'FeedBlitz', 'sgautorepondeur' => 'SG Autorepondeur', 'arpreach' => 'arpReach'];
    return $names[$dest] ?? $dest;
  }

  /** Contact Form placeholders => CES ones */
  protected function placeholderMap(array $params, array $fieldMap): array
  {
    $map = ['[sitename]' => '[website_name]', '[siteurl]' => '[website_url]'];
    foreach ($fieldMap as $name => $slug) {
      $map['[user_' . $name . ']'] = '[' . $slug . ']';
      $map['[' . $name . ']'] = '[' . $slug . ']';
    }
    // [form_data]: every field with its label, as Contact Form wrote it
    $rows = [];
    foreach ((array) ($params['fields'] ?? []) as $f) {
      $name = (string) ($f['name'] ?? '');
      if ($name === '' || !isset($fieldMap[$name])) {
        continue;
      }
      $label = trim((string) ($f['label'] ?? '')) !== '' ? (string) $f['label'] : (string) ($f['placeholder'] ?? $name);
      $rows[] = '<b>' . esc_html($label) . '</b>: [' . $fieldMap[$name] . ']';
    }
    $map['[form_data]'] = implode('<br>', $rows);
    return $map;
  }

  protected function convertText(string $text, array $replace, bool $plain = false): string
  {
    $text = strtr($text, $replace);
    return $plain ? wp_strip_all_tags($text) : $text;
  }

  protected function buildEmails(array &$settings, array $submits, array $replace, array $fieldMap, array $params): void
  {
    $admin = [];
    $contact = null;
    $extraTexts = 0;
    foreach ($submits as $s) {
      if (!is_array($s)) {
        continue;
      }
      $to = trim((string) ($s['to'] ?? ''));
      if ($to === '') {
        continue;
      }
      if (preg_match('/^\[user_([^\]]+)\]$/', $to, $m) && isset($fieldMap[$m[1]])) {
        if ($contact === null) {
          $contact = $s + ['slug' => $fieldMap[$m[1]]];
        } else {
          $extraTexts++;
        }
        continue;
      }
      $recipients = array_filter(array_map('trim', preg_split('/[,;]/', $to)));
      $hasPlaceholder = false;
      foreach ($recipients as $i => $r) {
        if (strpos($r, '[') !== false) {
          $hasPlaceholder = true;
          unset($recipients[$i]);
        }
      }
      if ($hasPlaceholder) {
        $this->skip(__('A notification sent to an address taken from the form (for example a chosen department): CES sends the owner email to fixed addresses.', 'ppsgen'));
      }
      if (!empty($s['enb_cc']) && !empty($s['cc'])) {
        foreach (preg_split('/[,;]/', (string) $s['cc']) as $cc) {
          $cc = trim($cc);
          if ($cc !== '' && is_email($cc)) {
            $recipients[] = $cc;
          }
        }
        $this->skip(__('Cc: the copy addresses get the email as ordinary recipients.', 'ppsgen'));
      }
      if (empty($recipients)) {
        continue;
      }
      if (!empty($admin)) {
        if ((string) ($s['subject'] ?? '') !== (string) ($admin['subject'] ?? '') || (string) ($s['msg'] ?? '') !== (string) ($admin['msg'] ?? '')) {
          $extraTexts++;
        }
        $admin['to'] = array_values(array_unique(array_merge($admin['to'], $recipients)));
        continue;
      }
      $admin = $s;
      $admin['to'] = array_values(array_unique($recipients));
      // Only worth a word when the address differs from the one CES sends from anyway
      $from = strtolower(trim((string) ($s['from'] ?? '')));
      $senders = array_map('strtolower', (array) $this->env['senders']);
      if (($from !== '' && !in_array($from, $senders, true)) || trim((string) ($s['reply'] ?? '')) !== '') {
        $this->skip(__('The From and Reply-To addresses of the notifications: CES sends from the address in Settings › Email sending.', 'ppsgen'));
      }
    }
    if (!empty($admin)) {
      $settings['send_admin'] = '1';
      $settings['send_admin_email'] = implode(', ', $admin['to']);
      $settings['send_admin_subject'] = $this->convertText((string) ($admin['subject'] ?? ''), $replace, true);
      $settings['html_send_admin_text'] = $this->convertText((string) ($admin['msg'] ?? ''), $replace);
      $this->move(sprintf(__('The notification email to %s', 'ppsgen'), $settings['send_admin_email']));
    }
    if ($contact !== null) {
      $settings['send_contact'] = '1';
      $settings['send_contact_email_field'] = $contact['slug'];
      $settings['send_contact_subject'] = $this->convertText((string) ($contact['subject'] ?? ''), $replace, true);
      $settings['html_send_contact_text'] = $this->convertText((string) ($contact['msg'] ?? ''), $replace);
      $this->move(__('The email to the person who sent the form', 'ppsgen'));
    }
    if ($extraTexts > 0) {
      $this->skip(sprintf(_n('%d more notification with its own text: CES sends one owner email (now to all the addresses) and one email to the sender.', '%d more notifications with their own texts: CES sends one owner email (now to all the addresses) and one email to the sender.', $extraTexts, 'ppsgen'), $extraTexts));
    }
  }

  /* ---------------------------------------------------------------- conditional logic */

  protected function buildLogic(array $params, array $fieldKeys, array $fieldTypes, array $tpl): ?array
  {
    $rules = [];
    foreach (['cl', 'cl2'] as $clKey) {
      foreach ((array) ($params[$clKey] ?? []) as $cl) {
        if (is_array($cl)) {
          $rules[] = $cl;
        }
      }
    }
    $hidden = [];
    foreach ((array) ($params['cl2_hidden_fields'] ?? []) as $name) {
      if (isset($fieldKeys[$name])) {
        $hidden[] = $fieldKeys[$name];
      }
    }
    $logics = [];
    $lost = [];
    $eqMap = ['eq' => 'Equal', 'like' => 'Like', 'more' => 'More', 'less' => 'Less'];
    foreach ($rules as $cl) {
      $conds = [];
      $prevNext = '';
      foreach ((array) ($cl['cond'] ?? []) as $cond) {
        if (!is_array($cond)) {
          continue;
        }
        $type = (string) ($cond['type'] ?? 'field');
        $c = null;
        if ($type === 'field') {
          $name = (string) ($cond['field'] ?? '');
          if (!isset($fieldKeys[$name])) {
            continue;
          }
          $c = ['type' => 'field', 'subType' => $fieldKeys[$name], 'operator' => $eqMap[(string) ($cond['field_eq'] ?? 'eq')] ?? 'Equal', 'value' => (string) ($cond['field_eq_to'] ?? '')];
        } elseif ($type === 'user') {
          $userType = (string) ($cond['user_type'] ?? '');
          if ($userType === 'url') {
            $c = ['type' => 'user', 'subType' => 'Url', 'operator' => ($cond['url_eq'] ?? 'eq') === 'like' ? 'Like' : 'Equal', 'value' => (string) ($cond['url_eq_to'] ?? '')];
          } elseif ($userType === 'logged_in') {
            $c = ['type' => 'user', 'subType' => 'Logged In', 'operator' => 'Equal', 'value' => ''];
          } elseif ($userType === 'lang') {
            $langs = array_values(array_filter((array) ($cond['user_lang'] ?? [])));
            if (count($langs) === 1) {
              $c = ['type' => 'user', 'subType' => 'Language', 'operator' => 'Equal', 'value' => strtolower(substr((string) $langs[0], 0, 2))];
            } else {
              $lost[] = __('a condition on several visitor languages', 'ppsgen');
            }
          } else {
            $lost[] = __('a condition on the visitor\'s country', 'ppsgen');
          }
        }
        if ($c === null) {
          continue;
        }
        $c['operator_between'] = $prevNext === 'and' ? 'AND' : 'OR';
        $conds[] = $c;
        $prevNext = (string) ($cond['next_eq'] ?? 'or');
      }
      $acts = [];
      foreach ((array) ($cl['logic'] ?? []) as $l) {
        if (!is_array($l)) {
          continue;
        }
        $lType = (string) ($l['type'] ?? 'field');
        if ($lType === 'field') {
          $op = ['show' => 'Show', 'hide' => 'Hide', 'prefill' => 'Set Value', 'add' => 'Add Value', 'substruct' => 'Subtract Value'][(string) ($l['field_act'] ?? '')] ?? '';
          if ($op === '') {
            $lost[] = __('adding or subtracting a currency value', 'ppsgen');
            continue;
          }
          foreach ((array) ($l['fields'] ?? []) as $name) {
            if (!isset($fieldKeys[$name])) {
              continue;
            }
            $acts[] = ['type' => 'field', 'subType' => $fieldKeys[$name], 'operator' => $op, 'value' => (string) ($l['field_prefill'] ?? '')];
            if ($op === 'Show') {
              // Contact Form hides a "show" field until its condition is met
              $hidden[] = $fieldKeys[$name];
            }
          }
        } elseif ($lType === 'redirect' && !empty($l['redirect_url'])) {
          $acts[] = ['type' => 'redirect', 'value' => esc_url_raw((string) $l['redirect_url']), 'afterSubmit' => !empty($l['redirect_after_submit'])];
        } elseif ($lType === 'sendto') {
          $lost[] = __('sending the email to another address by a condition', 'ppsgen');
        }
      }
      if (!empty($acts)) {
        $logics[] = ['conditions' => $conds, 'actions' => $acts];
      }
    }
    foreach (array_unique($lost) as $text) {
      $this->skip(sprintf(__('Conditional logic: %s (CES has no such rule).', 'ppsgen'), $text));
    }
    if (empty($logics) && empty($hidden)) {
      return null;
    }
    $this->move(sprintf(_n('Conditional logic: %d rule', 'Conditional logic: %d rules', count($logics), 'ppsgen'), count($logics)));
    return ['logic_data' => wp_json_encode($logics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'hidden_fields' => array_values(array_unique($hidden))];
  }

  /* ---------------------------------------------------------------- pictures */

  /** A file of the Contact Form plugin is copied to uploads, so it stays when Contact Form is removed */
  protected function asset(string $url): string
  {
    if (is_callable($this->env['copyAsset'])) {
      return (string) call_user_func($this->env['copyAsset'], $url);
    }
    return $url;
  }

  /** Pictures of the Contact Form plugin in the text blocks of a form are copied, so they survive its removal */
  protected function moveAssets(string $text): string
  {
    if (!is_callable($this->env['copyAsset'])) {
      return $text;
    }
    return preg_replace_callback('#(https?:)?//[^\s"\'()<>]+/(contact-form-by-supsystic(-pro)?)/[^\s"\'()<>]+#i', function ($m) {
      return $this->asset($m[0]);
    }, $text);
  }
}
