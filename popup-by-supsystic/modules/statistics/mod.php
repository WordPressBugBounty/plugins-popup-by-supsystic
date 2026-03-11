<?php

class statisticsPps extends modulePps
{
  private $_types = [];
  public function init()
  {
    parent::init();
    dispatcherPps::addFilter('popupEditTabs', [$this, 'addPopupEditTab'], 10, 2);
  }
  public function getTypes()
  {
    if (empty($this->_types)) {
      $this->_types = [
          'show' => ['id' => 1, 'label' => __('Displayed', PPS_LANG_CODE)],
          'subscribe' => ['id' => 2, 'label' => __('Subscribed', PPS_LANG_CODE)],
          'share' => ['id' => 3, 'label' => __('Shared', PPS_LANG_CODE)],
          'fb_like' => ['id' => 4, 'label' => __('Facebook Liked', PPS_LANG_CODE)],
          'login' => ['id' => 5, 'label' => __('Login', PPS_LANG_CODE)],
          'registration' => ['id' => 6, 'label' => __('Registration', PPS_LANG_CODE)],
          'age_verify' => ['id' => 7, 'label' => __('Verification', PPS_LANG_CODE)],
          'close' => ['id' => 8, 'label' => __('Closed', PPS_LANG_CODE)],
          'subscribe_error' => ['id' => 9, 'label' => __('Subscribe Error', PPS_LANG_CODE)],
      ];
    }
    return $this->_types;
  }
  public function getTypeIdByCode($code)
  {
    $this->getTypes();
    return isset($this->_types[ $code ]) ? $this->_types[ $code ]['id'] : false;
  }
  public function addPopupEditTab($tabs, $popup)
  {
    $tabs['ppsPopupStatistics'] = [
        'title' => __('Statistics', PPS_LANG_CODE),
        'content' => $this->getView()->getPopupEditTab($popup),
        'fa_icon' => 'fa-line-chart',
        'sort_order' => 60,
    ];
    return $tabs;
  }
}
