<?php

class recapchaPps
{
  private $_publicKey = '6LfUotgSAAAAAL4pqsHxE8sx6Cz8o7AEc_JjtROD';
  private $_privateKey = '6LfUotgSAAAAACFAM1TMpIsLiQsfDmV-mRNfQg1n';

  public function __construct()
  {
    if (!function_exists('recaptcha_get_html')) {	// In case if this lib was already included by another plugin
      importPps(PPS_HELPERS_DIR . 'recaptchalib.php');
    }
  }
  public static function getInstance()
  {
    static $instance = null;
    if (empty($instance)) {
      $instance = new recapchaPps();
    }
    return $instance;
  }
  public static function _()
  {
    return self::getInstance();
  }
  public function getHtml()
  {
    if (reqPps::getVar('reqType') == 'ajax') {
      $divId = 'toeRecapcha' . mt_rand(1, 9999);
      return '<div id="' . $divId . '"></div>' .
          '<script type="text/javascript">
				// <!--
				Recaptcha.create("' . $this->_publicKey . '",
					"' . $divId . '",
					{
					  theme: "red",
					  callback: Recaptcha.focus_response_field
					}
				  );
				// -->
				</script>';
    } else {
      return recaptcha_get_html($this->_publicKey, null, true);
    }
  }
  public function check()
  {
    $resp = recaptcha_check_answer(
      $this->_privateKey,
      reqPps::getVar('REMOTE_ADDR', 'server'),
      reqPps::getVar('recaptcha_challenge_field'),
      reqPps::getVar('recaptcha_response_field')
    );
    return $resp->is_valid;
  }
}
