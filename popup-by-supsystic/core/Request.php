<?php

if (!defined('ABSPATH')) {
  exit();
}

class RequestPpsgen
{
  protected FramePpsgen $_frame;

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_frame = $frame;
    $this->init();
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function init()
  {
    add_filter('sanitize_text_field', [$this, 'sanitizeData'], 999, 2);
  }

  public function sanitizeData($filtered, $value)
  {
    return is_array($value) ? $this->sanitizeArray($value) : $filtered;
  }

  public function sanitizeArray($arr)
  {
    $newArr = [];
    foreach ($arr as $k => $v) {
      if (strpos($k, 'html_') === 0) {
        $newArr[$k] = $this->getFrame()->core('helper')->wpKses($v);
      } else {
        $newArr[$k] = is_array($v) ? $this->sanitizeArray($v) : _sanitize_text_fields($v, false);
      }
    }
    return $newArr;
  }

  public function getVar($name, $from = 'all', $default = null)
  {
    $from = strtolower($from);
    if ('all' == $from) {
      // phpcs:ignore WordPress.Security.NonceVerification
      if (isset($_GET[$name])) {
        $from = 'get';
        // phpcs:ignore WordPress.Security.NonceVerification
      } elseif (isset($_POST[$name])) {
        $from = 'post';
      }
    }
    switch ($from) {
      case 'get':
        // phpcs:ignore WordPress.Security.NonceVerification
        if (isset($_GET[$name])) {
          // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
          return sanitize_text_field($_GET[$name]);
        }
        break;
      case 'post':
        // phpcs:ignore WordPress.Security.NonceVerification
        if (isset($_POST[$name])) {
          // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
          return sanitize_text_field($_POST[$name]);
        }
        break;
    }
    return $default;
  }

  public function getServerVar($key = '')
  {
    if (empty($key) || !isset($_SERVER[$key])) {
      return '';
    }
    $value = sanitize_text_field(wp_unslash($_SERVER[$key]));
    switch ($key) {
      case 'REMOTE_ADDR':
        return filter_var($value, FILTER_VALIDATE_IP) ?: '';
      case 'HTTP_ACCEPT_LANGUAGE':
        return sanitize_text_field($value);
      case 'REQUEST_URI':
        return esc_url_raw($value);
      case 'HTTP_REFERER':
        return esc_url_raw($value);
      default:
        return sanitize_text_field($value);
    }
  }

  public function getMod()
  {
    $mod = $this->getVar('mod');
    if (!$mod) {
      $mod = $this->getVar('page');
    }
    return sanitize_text_field($mod);
  }
}
