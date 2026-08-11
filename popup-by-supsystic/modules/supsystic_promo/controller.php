<?php

class supsystic_promoControllerPps extends controllerPps
{
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
  /**
   * @see controller::getPermissions();
   */
  public function getPermissions()
  {
    return [
      PPS_USERLEVELS => [
        PPS_ADMIN => ['addStep', 'closeTour', 'addTourFinish'],
      ],
    ];
  }
  public function getNoncedMethods()
  {
    return ['addStep', 'closeTour', 'addTourFinish'];
  }
}
