<?php

class supsystic_promoControllerPps extends controllerPps
{
  public function welcomePageSaveInfo()
  {
    $res = new responsePps();
    installerPps::setUsed();
    if ($this->getModel()->welcomePageSaveInfo(reqPps::get('get'))) {
      $res->addMessage(__('Information was saved. Thank you!', PPS_LANG_CODE));
    } else {
      $res->pushError($this->getModel()->getErrors());
    }
    $originalPage = reqPps::getVar('original_page');
    $http = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
    if (strpos($originalPage, $http . $_SERVER['HTTP_HOST']) !== 0) {
      $originalPage = '';
    }
    redirectPps($originalPage);
  }
  public function sendSubscribeMail()
  {
    $res = new responsePps();
    $data = reqPps::get('post');
    $apiUrl = 'https://supsystic.com/wp-admin/admin-ajax.php';
    $reqUrl = $apiUrl . '?action=ac_get_plugin_installed';
    $mail = $data['data'];
    $isPro = !empty($this->getModule('suspsystic_promo')->isPro()) ? true : false;
    $data = [
      'body' => [
        'key' => 'kJ#f3(FjkF9fasd124t5t589u9d4389r3r3R#2asdas3(#R03r#(r#t-4t5t589u9d4389r3r3R#$%lfdj',
        'user_name' => $mail['username'],
        'user_email' => $mail['email'],
        'customertype' => $mail['expertise'],
        'site_url' => get_bloginfo('wpurl'),
        'site_name' => get_bloginfo('name'),
        'plugin_code' => 'pps',
        'is_pro' => $isPro,
      ],
    ];
    $response = wp_remote_post($reqUrl, $data);
    if (is_wp_error($response)) {
      $res->pushError('Some errors');
    } else {
      update_option('pps_ac_subscribe', true);
    }
    $res->ajaxExec();
  }
  public function sendSubscribeRemind()
  {
    $res = new responsePps();
    update_option('pps_ac_remind', date('Y-m-d h:i:s', time() + 86400));
    $res->ajaxExec();
  }
  public function sendSubscribeDisable()
  {
    $res = new responsePps();
    update_option('pps_ac_disabled', true);
    $res->ajaxExec();
  }
  public function addNoticeAction()
  {
    $res = new responsePps();
    $code = reqPps::getVar('code', 'post');
    $choice = reqPps::getVar('choice', 'post');
    if (!empty($code) && !empty($choice)) {
      $optModel = framePps::_()->getModule('options')->getModel();
      switch ($choice) {
        case 'hide':
          $optModel->save('hide_' . $code, 1);
          break;
        case 'later':
          $optModel->save('later_' . $code, time());
          break;
        case 'done':
          $optModel->save('done_' . $code, 1);
          break;
      }
      $this->getModel()->saveUsageStat($code . '.' . $choice, true);
      $this->getModel()->checkAndSend(true);
    }
    $res->ajaxExec();
  }
  public function addTourStep()
  {
    $res = new responsePps();
    if ($this->getModel()->addTourStep(reqPps::get('post'))) {
      $res->addMessage(__('Information was saved. Thank you!', PPS_LANG_CODE));
    } else {
      $res->pushError($this->getModel()->getErrors());
    }
    $res->ajaxExec();
  }
  public function closeTour()
  {
    $res = new responsePps();
    if ($this->getModel()->closeTour(reqPps::get('post'))) {
      $res->addMessage(__('Information was saved. Thank you!', PPS_LANG_CODE));
    } else {
      $res->pushError($this->getModel()->getErrors());
    }
    $res->ajaxExec();
  }
  public function addTourFinish()
  {
    $res = new responsePps();
    if ($this->getModel()->addTourFinish(reqPps::get('post'))) {
      $res->addMessage(__('Information was saved. Thank you!', PPS_LANG_CODE));
    } else {
      $res->pushError($this->getModel()->getErrors());
    }
    $res->ajaxExec();
  }
  public function saveDeactivateData()
  {
    $res = new responsePps();
    if ($this->getModel()->saveDeactivateData(reqPps::get('post'))) {
      $res->addMessage(__('Thank you for Feedback!', PPS_LANG_CODE));
    } else {
      $res->pushError($this->getModel()->getErrors());
    }
    $res->ajaxExec();
  }
  public function enbStatsOpt()
  {
    // $res = new responsePps();
    // framePps::_()->getModule('options')->getModel()->save('send_stats', 1);
    // $res->ajaxExec();
  }
  /**
   * @see controller::getPermissions();
   */
  public function getPermissions()
  {
    return [
      PPS_USERLEVELS => [
        PPS_ADMIN => ['welcomePageSaveInfo', 'addNoticeAction', 'addStep', 'closeTour', 'addTourFinish', 'saveDeactivateData', 'enbStatsOpt', 'sendSubscribeMail', 'sendSubscribeRemind', 'sendSubscribeDisable'],
      ],
    ];
  }
  public function getNoncedMethods()
  {
    return ['welcomePageSaveInfo', 'addNoticeAction', 'addStep', 'closeTour', 'addTourFinish', 'saveDeactivateData', 'enbStatsOpt', 'sendSubscribeMail', 'sendSubscribeRemind', 'sendSubscribeDisable'];
  }
}
