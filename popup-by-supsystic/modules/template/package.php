<?php
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Template packages: the rows that create a popup, a form and / or an email template.
 *
 * FREE templates ship inside the plugin as JSON files in modules/template/data. Only the codes listed in the
 * PHP manifest (data/manifest.php) can be read, the file name always comes from the manifest (never from the
 * request), and the file must match the SHA-256 pinned there — editing a JSON file without the plugin's PHP
 * makes it unusable. PRO packages come from the template server through the PRO add-on, which verifies the
 * server's signature first. Every package, local or remote, then passes the same strict schema check before
 * anything is written: known parts only, known columns only, known element types, sane sizes, JSON settings.
 *
 * Asset links are stored as {{ppsgen_url}} / {{ppsgen_pro_url}} and become this site's plugin URLs on install,
 * so templates work in sub-folder installs and emails get absolute image links.
 */
class TemplatePackagePpsgen
{
  const FORMAT = 1;
  const MAX_FILE_BYTES = 2097152;
  const MAX_TEXT = 65000;
  const MAX_ROWS = ['form_fields' => 200, 'popup_fields' => 600, 'email_template_fields' => 600, 'contact_fields' => 40];

  // Parts a package may have and the columns kept from each (ids, owners and dates are never taken from it)
  const COLUMNS = [
    'form' => ['form_type', 'settings', 'design'],
    'form_fields' => ['fkey', 'settings', 'options', 'order_index'],
    'popup' => ['settings', 'design'],
    'popup_fields' => ['parent_key', 'fkey', 'type', 'settings', 'order_index'],
    'email_template' => ['settings', 'design'],
    'email_template_fields' => ['parent_key', 'fkey', 'type', 'settings', 'order_index'],
    'contact_fields' => ['slug', 'label', 'field_type', 'only_form', 'options', 'settings'],
  ];
  const SINGLE = ['form', 'popup', 'email_template'];
  const FORM_TYPES = ['submit', 'create_contact', 'login', 'logout', 'reset_password', 'signup'];
  const POPUP_TYPES = ['row', 'column', 'title', 'textarea', 'iframe', 'button', 'icon', 'svg_icon', 'divider', 'form', 'timer', 'image', 'video'];
  const EMAIL_TYPES = ['row', 'column', 'email_content', 'unsubscribe', 'title', 'textarea', 'image', 'svg_icon', 'button', 'pro', 'woo_categories', 'woo_tags', 'woo_attributes', 'woo_products', 'divider'];
  // Form-only element types (everything else must be a contact field type)
  const FORM_ELEMENT_TYPES = ['title', 'description', 'submit', 'captcha'];

  public static function dataDir(): string
  {
    return PPSGEN_PLUGIN_DIR . 'modules/template/data';
  }

  /** code => ['file' => 'code.json', 'sha256' => '…'] — the only local packages that exist */
  public static function manifest(): array
  {
    $file = self::dataDir() . '/manifest.php';
    if (!is_file($file)) {
      return [];
    }
    $manifest = include $file;
    return is_array($manifest) ? $manifest : [];
  }

  public static function hasLocal(string $code): bool
  {
    return isset(self::manifest()[$code]);
  }

  /** A FREE template's package from the plugin itself, checked; WP_Error when it isn't listed or was changed */
  public static function readLocal(string $code)
  {
    if (!preg_match('/^[a-z0-9_]{1,80}$/', $code)) {
      return new WP_Error('ppsgen_template_code', __('Unknown template.', 'ppsgen'));
    }
    $manifest = self::manifest();
    if (empty($manifest[$code]['file']) || empty($manifest[$code]['sha256'])) {
      return new WP_Error('ppsgen_template_unlisted', __('This template is not part of the plugin.', 'ppsgen'));
    }
    $name = (string) $manifest[$code]['file'];
    if (!preg_match('/^[a-z0-9_]{1,80}\.json$/', $name)) {
      return new WP_Error('ppsgen_template_file', __('This template file is not valid.', 'ppsgen'));
    }
    $dir = realpath(self::dataDir());
    $path = $dir ? realpath($dir . DIRECTORY_SEPARATOR . $name) : false;
    if (!$path || strpos($path, $dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($path) || filesize($path) > self::MAX_FILE_BYTES) {
      return new WP_Error('ppsgen_template_file', __('This template file is missing.', 'ppsgen'));
    }
    $raw = (string) file_get_contents($path);
    if (!hash_equals(strtolower((string) $manifest[$code]['sha256']), hash('sha256', $raw))) {
      return new WP_Error('ppsgen_template_changed', __('This template file was changed and cannot be used. Reinstall the plugin to restore it.', 'ppsgen'));
    }
    return self::decode($raw, $code);
  }

  /** Raw package JSON (local file or verified server response) → checked rows */
  public static function decode(string $raw, string $code)
  {
    if (strlen($raw) > self::MAX_FILE_BYTES) {
      return new WP_Error('ppsgen_template_size', __('This template is too large.', 'ppsgen'));
    }
    $package = json_decode($raw, true, 32);
    if (!is_array($package) || (int) ($package['format'] ?? 0) !== self::FORMAT || ($package['code'] ?? '') !== $code || !is_array($package['template'] ?? null)) {
      return new WP_Error('ppsgen_template_format', __('This template could not be read.', 'ppsgen'));
    }
    return self::validate($package['template']);
  }

  /** Strict schema check: returns only the known parts and columns, or WP_Error */
  public static function validate(array $template)
  {
    $bad = static fn(string $why) => new WP_Error('ppsgen_template_invalid', sprintf(__('This template is not valid (%s).', 'ppsgen'), $why));
    $unknown = array_diff(array_keys($template), array_keys(self::COLUMNS));
    if ($unknown) {
      return $bad('unknown part');
    }
    $cfTypes = array_keys(self::contactFieldTypes());
    $clean = [];
    foreach (self::COLUMNS as $part => $columns) {
      if (!isset($template[$part]) || $template[$part] === false || $template[$part] === null) {
        continue;
      }
      if (!is_array($template[$part])) {
        return $bad($part);
      }
      $rows = in_array($part, self::SINGLE, true) ? [$template[$part]] : $template[$part];
      if (!in_array($part, self::SINGLE, true) && (array_values($rows) !== $rows || count($rows) > self::MAX_ROWS[$part])) {
        return $bad($part . ' list');
      }
      $out = [];
      foreach ($rows as $row) {
        if (!is_array($row)) {
          return $bad($part . ' row');
        }
        $item = [];
        foreach ($columns as $col) {
          if (!array_key_exists($col, $row)) {
            continue;
          }
          $value = $row[$col];
          if ($col === 'order_index' || $col === 'only_form') {
            $item[$col] = (int) $value;
            continue;
          }
          if (!is_string($value) && !is_numeric($value) && $value !== null) {
            return $bad($part . '.' . $col);
          }
          $value = (string) $value;
          if (strlen($value) > self::MAX_TEXT) {
            return $bad($part . '.' . $col . ' size');
          }
          if (in_array($col, ['settings', 'design', 'options'], true) && $value !== '') {
            $decoded = json_decode($value, true, 64);
            if (!is_array($decoded)) {
              return $bad($part . '.' . $col . ' json');
            }
          }
          $item[$col] = $value;
        }
        // Keys, types and slugs
        foreach (['fkey', 'parent_key'] as $key) {
          if (isset($item[$key]) && !preg_match('/^[A-Za-z0-9_-]{0,64}$/', $item[$key])) {
            return $bad($part . '.' . $key);
          }
        }
        if ($part === 'popup_fields' && !in_array($item['type'] ?? '', self::POPUP_TYPES, true)) {
          return $bad('popup element ' . ($item['type'] ?? ''));
        }
        if ($part === 'email_template_fields' && !in_array($item['type'] ?? '', self::EMAIL_TYPES, true)) {
          return $bad('email element ' . ($item['type'] ?? ''));
        }
        if ($part === 'form' && isset($item['form_type']) && !in_array($item['form_type'], self::FORM_TYPES, true)) {
          return $bad('form type');
        }
        if ($part === 'form_fields') {
          $settings = json_decode($item['settings'] ?? '{}', true) ?: [];
          $type = (string) ($settings['field_type'] ?? '');
          if (!in_array($type, self::FORM_ELEMENT_TYPES, true) && !in_array($type, $cfTypes, true)) {
            return $bad('form field ' . $type);
          }
          if (isset($settings['field_slug']) && !preg_match('/^[a-z0-9_]{0,64}$/', (string) $settings['field_slug'])) {
            return $bad('form field slug');
          }
        }
        if ($part === 'contact_fields') {
          if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $item['slug'] ?? '') || !in_array($item['field_type'] ?? '', $cfTypes, true)) {
            return $bad('contact field');
          }
          $item['label'] = sanitize_text_field($item['label'] ?? $item['slug']);
          $item['only_form'] = !empty($item['only_form']) ? 1 : 0;
        }
        $out[] = $item;
      }
      $clean[$part] = in_array($part, self::SINGLE, true) ? $out[0] : $out;
    }
    if (empty($clean['form']) && empty($clean['popup']) && empty($clean['email_template'])) {
      return $bad('empty');
    }
    return $clean;
  }

  /** {{ppsgen_url}} / {{ppsgen_pro_url}} → this site's plugin URLs (absolute, so emails get working images) */
  public static function placeholders(array $template): array
  {
    $map = [
      '{{ppsgen_url}}' => trailingslashit(PPSGEN_PLUGIN_URL),
      '{{ppsgen_pro_url}}' => trailingslashit((string) apply_filters('ppsgen_pro_plugin_url', PPSGEN_PLUGIN_URL)),
      // Form alerts ("new booking") go to the site's admin address until the owner changes it
      '{{admin_email}}' => sanitize_email((string) get_option('admin_email')),
    ];
    array_walk_recursive($template, static function (&$value) use ($map) {
      if (is_string($value) && strpos($value, '{{') !== false) {
        $value = strtr($value, $map);
      }
    });
    return $template;
  }

  private static function contactFieldTypes(): array
  {
    $frame = FramePpsgen::getInstance();
    $model = $frame ? $frame->getModule('contact')->getSubmod('contact_field')->getModel() : null;
    return $model ? (array) $model->getContactFieldTypes() : [];
  }
}
