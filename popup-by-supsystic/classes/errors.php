<?php

#[\AllowDynamicProperties]
class errorsPps
{
  const FATAL = 'fatal';
  const MOD_INSTALL = 'mod_install';
  private static $errors = [];
  private static $haveErrors = false;

  public static $current = [];
  public static $displayed = false;

  public static function push($error, $type = 'common')
  {
    if (!isset(self::$errors[$type])) {
      self::$errors[$type] = [];
    }
    if (is_array($error)) {
      self::$errors[$type] = array_merge(self::$errors[$type], $error);
    } else {
      self::$errors[$type][] = $error;
    }
    self::$haveErrors = true;

    if ($type == 'session') {
      self::setSession(self::$errors[$type]);
    }
  }
  public static function setSession($error)
  {
    $sesErrors = self::getSession();
    if (empty($sesErrors)) {
      $sesErrors = [];
    }
    if (is_array($error)) {
      $sesErrors = array_merge($sesErrors, $error);
    } else {
      $sesErrors[] = $error;
    }
    reqPps::setVar('sesErrors', $sesErrors, 'session');
  }
  public static function init()
  {
    $ppsErrors = reqPps::getVar('ppsErrors');
    if (!empty($ppsErrors)) {
      if (!is_array($ppsErrors)) {
        $ppsErrors = [ $ppsErrors ];
      }
      $ppsErrors = array_map('htmlspecialchars', array_map('stripslashes', array_map('trim', $ppsErrors)));
      if (!empty($ppsErrors)) {
        self::$current = $ppsErrors;
        if (is_admin()) {
          add_action('admin_notices', ['errorsPps', 'showAdminErrors']);
        } else {
          add_filter('the_content', ['errorsPps', 'appendErrorsContent'], 99999);
        }
      }
    }
  }
  public static function showAdminErrors()
  {
    if (self::$current) {
      $html = '';
      foreach (self::$current as $error) {
        $html .= '<div class="error"><p><strong style="font-size: 15px;">' . $error . '</strong></p></div>';
      }
      echo viewPps::ksesString($html);
    }
  }
  public static function appendErrorsContent($content)
  {
    if (!self::$displayed && !empty(self::$current)) {
      $content = '<div class="toeErrorMsg">' . implode('<br />', self::$current) . '</div>' . $content;
      self::$displayed = true;
    }
    return $content;
  }
  public static function getSession()
  {
    return reqPps::getVar('sesErrors', 'session');
  }
  public static function clearSession()
  {
    reqPps::clearVar('sesErrors', 'session');
  }
  public static function get($type = '')
  {
    $res = [];
    if (!empty(self::$errors)) {
      if (empty($type)) {
        foreach (self::$errors as $e) {
          foreach ($e as $error) {
            $res[] = $error;
          }
        }
      } else {
        $res = self::$errors[$type];
      }
    }
    return $res;
  }
  public static function haveErrors($type = '')
  {
    if (empty($type)) {
      return self::$haveErrors;
    } else {
      return isset(self::$errors[$type]);
    }
  }
}
