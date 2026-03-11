<?php

#[\AllowDynamicProperties]
class validatorPps
{
  public static $errors = [];
  public static function validate($field, $label = '', $validate = [])
  {
    self::$errors = [];
    if (is_object($field) && get_class($field) != 'fieldPps') {
      $value = $field;
      $field = new fieldPpsPps('noMatter');
      $field->label = $label;
      $field->setValue($value);
      $field->setValidation($validate);
    }
    if (!empty($field->validate)) {
      foreach ($field->validate as $v) {
        if (method_exists('validatorPps', $v)) {
          self::$v($field);
        }
      }
    }
    if (method_exists('validatorPps', $field->type)) {
      $validate = $field->type;
      self::$validate($field);
    }
    if ($field->maxlen) {
      self::validLen($field);
    }
    return self::$errors;
  }
  public static function validLen($field, $label = '', $validate = [])
  {
    if (!(bool) (strlen($field->value) <= $field->maxlen)) {
      self::addError(sprintf(__('Invalid length for %s, max length is %s'), $field->label, $field->maxlen), $field->name);
      return false;
    }
    return true;
  }
  public static function _($field)
  {
    return self::validate($field);
  }
  public static function getErrors()
  {
    return self::$errors;
  }
  public static function numeric($field)
  {
    if (!is_numeric($field->value) && !empty($field->value)) {
      self::addError(sprintf(__('Invalid numeric value for %s'), $field->label), $field->name);
      return false;
    }
    return true;
  }
  public static function int($field)
  {
    return self::numeric($field);
  }
  public static function float($field)
  {
    return self::numeric($field);
  }
  public static function double($field)
  {
    return self::numeric($field);
  }
  protected static function _notEmpty($value)
  {
    if (is_array($value)) {
      foreach ($value as $v) {
        if (self::_notEmpty($v)) {       //If at least 1 element of array are not empty - all array will be not empty
          $res = true;
          break;
        }
      }
    } else {
      $res = !empty($value);
    }
    return $res;
  }
  public static function notEmpty($field)
  {
    if (!self::_notEmpty($field->value)) {
      self::addError(sprintf(__('Please enter %s'), $field->label), $field->name);
      return false;
    }
    return true;
  }
  public static function selectNotEmpty($field)
  {
    if (empty($field->value)) {
      self::addError(sprintf(__('Please select %s'), $field->label), $field->name);
      return false;
    }
    return true;
  }
  public static function email($field)
  {
    if (!is_email($field->value)) {
      self::addError(sprintf(__('Invalid %s'), $field->label), $field->name);
      return false;
    } elseif (email_exists($field->value)) {
      self::addError(sprintf(__('%s is already registered'), $field->label), $field->name);
      return false;
    }
    return true;
  }
  public static function addError($error, $key = '')
  {
    if ($key) {
      self::$errors[$key] = $error;
    } else {
      self::$errors[] = $error;
    }
  }
  public static function string($field)
  {
    if (preg_match('/([0-9].*)/', $field->value)) {
      self::addError(sprintf(__('Invalid %s'), $field->label), $field->name);
      return false;
    }
    return true;
  }
  /**
   * Fective method
   */
  /*static public function none($fileld) {
      return true;
  }*/
  public static function getProductValidationMethods()
  {
    $res = [];
    $all = get_class_methods('validatorPps');
    foreach ($all as $m) {
      if (in_array($m, ['int', 'none', 'string'])) {
        $res[$m] = __($m);
      }
    }
    return $res;
  }

  public static function getUserValidationMethods()
  {
    // here validation for user fields
    $res = [];
    $all = get_class_methods('validatorPps');
    foreach ($all as $m) {
      if (in_array($m, ['int', 'none', 'string', 'email', 'validLen'])) {
        $res[$m] = __($m);
      }
    }
    return $res;
  }
  public static function prepareInput($input)
  {
    global $wpdb;
    if (is_array($input)) {
      return array_map([validator, 'prepareInput'], $input);
    } else {
      return $wpdb->_real_escape($input);
    }
  }
}
