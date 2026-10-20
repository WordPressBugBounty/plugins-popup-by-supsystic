<?php

if (!defined('ABSPATH')) {
  exit();
}

abstract class BaseModelPpsgen
{
  protected FramePpsgen $_frame;
  protected $_module;
  protected $_slug;
  protected $_wpdb;
  protected $_table;
  protected $_optionCache = [];
  protected $_useCache = false;
  protected $_status = 'success';
  protected $_message = '';
  protected $_errors = [];

  public function __construct(FramePpsgen $frame, string $token, $module)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    global $wpdb;
    $this->_frame = $frame;
    $this->_module = $module;
    $this->_slug = $module->getSlug();
    $this->_wpdb = $wpdb;
    $this->_table = $wpdb->prefix . 'ppsgen_options';
    $this->_useCache = property_exists($module, 'cacheDb') ? (bool) $module->cacheDb : false;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function getModule()
  {
    return $this->_module;
  }

  public function getView()
  {
    return $this->_module->getView();
  }

  public function getController()
  {
    return $this->_module->getController();
  }

  protected function _makeCacheKey($modSlug, $modObjId)
  {
    return $modSlug . ':' . $modObjId;
  }

  public function _prepareValueToStore($value, $format)
  {
    if (is_array($value) || is_object($value)) {
      return $format === 'json' ? wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : maybe_serialize($value);
    }
    return $value;
  }

  public function _prepareValueFromStore($rawValue)
  {
    if ($this->_isJson($rawValue)) {
      return json_decode($rawValue, true);
    }
    return maybe_unserialize($rawValue);
  }

  public function _isJson($string)
  {
    if (!is_string($string) || strlen($string) < 2) {
      return false;
    }
    $first = $string[0];
    $last = substr($string, -1);
    if (!(($first === '{' && $last === '}') || ($first === '[' && $last === ']'))) {
      return false;
    }
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
  }

  public function setStatus(string $status): void
  {
    $this->_status = $status;
  }

  public function getStatus(): string
  {
    return $this->_status;
  }

  public function setMessage(string $message): void
  {
    $this->_message = $message;
  }

  public function getMessage(): string
  {
    return $this->_message;
  }

  public function addError(string $field, string $error): void
  {
    $this->_errors[$field][] = $error;
    $this->_status = 'error';
  }

  public function getErrors(): array
  {
    return $this->_errors;
  }

  public function hasErrors(): bool
  {
    return !empty($this->_errors);
  }

  public function resetStatus(): void
  {
    $this->_status = 'success';
    $this->_message = '';
    $this->_errors = [];
  }

  public function getOption($optionName, $optionGroup = '', $modObjId = 0, $modSlug = null)
  {
    $slug = $modSlug ?: $this->_slug;
    $cacheKey = $this->_makeCacheKey($slug, $modObjId);

    if ($this->_useCache && isset($this->_optionCache[$cacheKey])) {
      return $this->_optionCache[$cacheKey][$optionName] ?? null;
    }

    $sql = $this->_wpdb->prepare("SELECT option_value FROM {$this->_table} WHERE mod_slug = %s AND mod_obj_id = %d AND option_name = %s AND option_group = %s LIMIT 1", $slug, $modObjId, $optionName, $optionGroup);

    $rawValue = $this->_wpdb->get_var($sql);
    $value = $this->_prepareValueFromStore($rawValue);

    if ($this->_useCache) {
      $this->_optionCache[$cacheKey][$optionName] = $value;
    }

    return $value;
  }

  public function getAllOptions($modObjId = 0, $modSlug = null)
  {
    $slug = $modSlug ?: $this->_slug;
    $cacheKey = $this->_makeCacheKey($slug, $modObjId);

    if ($this->_useCache && isset($this->_optionCache[$cacheKey])) {
      return $this->_optionCache[$cacheKey];
    }

    $sql = $this->_wpdb->prepare("SELECT option_name, option_value FROM {$this->_table} WHERE mod_slug = %s AND mod_obj_id = %d", $slug, $modObjId);

    $results = $this->_wpdb->get_results($sql, ARRAY_A);
    $options = [];

    foreach ($results as $row) {
      $options[$row['option_name']] = $this->_prepareValueFromStore($row['option_value']);
    }

    if ($this->_useCache) {
      $this->_optionCache[$cacheKey] = $options;
    }

    return $options;
  }

  public function updateOption($optionName, $optionGroup, $value, $modObjId = 0, $modSlug = null, $format = 'json')
  {
    $slug = $modSlug ?: $this->_slug;
    $cacheKey = $this->_makeCacheKey($slug, $modObjId);
    $optionGroup = !empty($optionGroup) ? $optionGroup : '';

    $preparedValue = $this->_prepareValueToStore($value, $format);

    $sql = $this->_wpdb->prepare("SELECT option_id FROM {$this->_table} WHERE mod_slug = %s AND mod_obj_id = %d AND option_name = %s AND option_group = %s LIMIT 1", $slug, $modObjId, $optionName, $optionGroup);

    $optionId = $this->_wpdb->get_var($sql);

    if ($optionId) {
      $this->_wpdb->update($this->_table, ['option_value' => $preparedValue], ['option_id' => $optionId], ['%s'], ['%d']);
    } else {
      $this->_wpdb->insert(
        $this->_table,
        [
          'mod_slug' => $slug,
          'mod_obj_id' => $modObjId,
          'option_name' => $optionName,
          'option_value' => $preparedValue,
          'option_group' => $optionGroup,
        ],
        ['%s', '%d', '%s', '%s', '%s'],
      );
    }

    if ($this->_useCache) {
      unset($this->_optionCache[$cacheKey]);
    }

    return true;
  }

  public function deleteOption($optionName, $optionGroup = '', $modObjId = 0, $modSlug = null)
  {
    $slug = $modSlug ?: $this->_slug;
    $cacheKey = $this->_makeCacheKey($slug, $modObjId);

    $this->_wpdb->delete(
      $this->_table,
      [
        'mod_slug' => $slug,
        'mod_obj_id' => $modObjId,
        'option_name' => $optionName,
        'option_group' => $optionGroup,
      ],
      ['%s', '%d', '%s', '%s'],
    );

    if ($this->_useCache) {
      unset($this->_optionCache[$cacheKey][$optionName]);
    }

    return true;
  }

  public function maybeMigrate(): bool
  {
    $currentVersion = defined('PPSGEN_VERSION') ? PPSGEN_VERSION : '0';
    $modelSlug = $this->_slug;
    $optionKey = "last_update_model_version_{$modelSlug}";
    $lastVersion = (string) $this->getOption($optionKey, 'migrate_opts', '0', 'dashboard');
    if (version_compare($lastVersion, $currentVersion, '<')) {
      $this->installTables();
      $this->updateOption($optionKey, 'migrate_opts', $currentVersion, 0, 'dashboard', 'json');
      return true;
    }
    return false;
  }
}
