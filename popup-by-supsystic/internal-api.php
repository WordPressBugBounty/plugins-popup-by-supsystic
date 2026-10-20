<?php

if (!defined('ABSPATH')) {
  exit();
}

require_once PPSGEN_PLUGIN_DIR . 'core/InternalApi.php';

if (!function_exists('ppsgen_get_contact_tags_by_email')) {
  function ppsgen_get_contact_tags_by_email($email)
  {
    if (class_exists('InternalApiPpsgen')) {
      return InternalApiPpsgen::_()->getContactTagsByEmail($email);
    }
  }
}

if (!function_exists('ppsgen_delete_contact_tags_by_email')) {
  function ppsgen_delete_contact_tags_by_email($email, $tags)
  {
    if (class_exists('InternalApiPpsgen')) {
      return InternalApiPpsgen::_()->deleteContactTagsByEmail($email, $tags);
    }
  }
}

if (!function_exists('ppsgen_add_contact_tags_by_email')) {
  function ppsgen_add_contact_tags_by_email($email, $tags)
  {
    if (class_exists('InternalApiPpsgen')) {
      return InternalApiPpsgen::_()->addContactTagsByEmail($email, $tags);
    }
  }
}

if (!function_exists('ppsgen_set_eca_conversion')) {
  function ppsgen_set_eca_conversion(array $data)
  {
    if (class_exists('InternalApiPpsgen')) {
      return InternalApiPpsgen::_()->setEcaConversion($data);
    }
    return false;
  }
}

if (!function_exists('ppsgen_get_eca_browser_attribution')) {
  function ppsgen_get_eca_browser_attribution($token = null, int $windowDays = 180)
  {
    if (class_exists('InternalApiPpsgen')) {
      return InternalApiPpsgen::_()->getEcaBrowserAttribution($token, $windowDays);
    }
    return false;
  }
}

if (!function_exists('ppsgen_get_contact_eca_local_storage')) {
  function ppsgen_get_contact_eca_local_storage($token = null, int $windowDays = 180)
  {
    return function_exists('ppsgen_get_eca_browser_attribution') ? ppsgen_get_eca_browser_attribution($token, $windowDays) : false;
  }
}

if (!function_exists('ppsgen_get_contact_eca_attribution')) {
  function ppsgen_get_contact_eca_attribution($emailOrContactId, array $args = []): array
  {
    if (class_exists('InternalApiPpsgen')) {
      return InternalApiPpsgen::_()->getContactEcaAttribution($emailOrContactId, $args);
    }
    return [];
  }
}
