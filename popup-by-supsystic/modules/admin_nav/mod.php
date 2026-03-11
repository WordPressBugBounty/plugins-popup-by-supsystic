<?php

class admin_navPps extends modulePps
{
  public function getBreadcrumbsList()
  {
    $res = [
        ['label' => PPS_WP_PLUGIN_NAME, 'url' => framePps::_()->getModule('adminmenu')->getMainLink()],
    ];
    // Try to get current tab breadcrumb
    $activeTab = framePps::_()->getModule('options')->getActiveTab();
    if (!empty($activeTab) && $activeTab != 'main_page') {
      $tabs = framePps::_()->getModule('options')->getTabs();
      if (!empty($tabs) && isset($tabs[ $activeTab ])) {
        if (isset($tabs[ $activeTab ]['add_bread']) && !empty($tabs[ $activeTab ]['add_bread'])) {
          if (!is_array($tabs[ $activeTab ]['add_bread'])) {
            $tabs[ $activeTab ]['add_bread'] = [ $tabs[ $activeTab ]['add_bread'] ];
          }
          foreach ($tabs[ $activeTab ]['add_bread'] as $addForBread) {
            $res[] = [
                'label' => $tabs[ $addForBread ]['label'], 'url' => $tabs[ $addForBread ]['url'],
            ];
          }
        }
        if ($activeTab == 'popup_edit') {
          $id = (int) reqPps::getVar('id', 'get');
          if ($id) {
            $tabs[ $activeTab ]['url'] .= '&id=' . $id;
          }
        }
        $res[] = [
            'label' => $tabs[ $activeTab ]['label'], 'url' => $tabs[ $activeTab ]['url'],
        ];
        if ($activeTab == 'statistics') {
          $statTabs = framePps::_()->getModule('statistics')->getStatTabs();
          $currentStatTab = framePps::_()->getModule('statistics')->getCurrentStatTab();
          if (isset($statTabs[ $currentStatTab ])) {
            $res[] = [
                'label' => $statTabs[ $currentStatTab ]['label'], 'url' => $statTabs[ $currentStatTab ]['url'],
            ];
          }
        }
      }
    }
    return $res;
  }
}
