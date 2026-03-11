<?php

#[\AllowDynamicProperties]
class datePps
{
  public static function _($time = null)
  {
    if (is_null($time)) {
      $time = time();
    }
    return date(PPS_DATE_FORMAT_HIS, $time);
  }
}
