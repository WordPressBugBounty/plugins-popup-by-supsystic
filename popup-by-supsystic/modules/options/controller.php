<?php

class optionsControllerPps extends controllerPps
{
  public function saveGroup()
  {
    $res = new responsePps();
    if ($this->getModel()->saveGroup(reqPps::get('post'))) {
      $res->addMessage(__('Done', PPS_LANG_CODE));
    } else {
      $res->pushError($this->getModel('options')->getErrors());
    }
    return $res->ajaxExec();
  }
  public function getNoncedMethods()
  {
    return ['saveGroup'];
  }
  public function getPermissions()
  {
    return [
        PPS_USERLEVELS => [
            PPS_ADMIN => ['saveGroup']
        ],
    ];
  }
}
