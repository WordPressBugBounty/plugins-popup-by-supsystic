<?php

class supsystic_promoModelPps extends modelPps
{
  public function getTourHst()
  {
    $hst = get_user_meta(get_current_user_id(), PPS_CODE . '-tour-hst', true);
    if (empty($hst)) {
      $hst = [];
    }
    if (!isset($hst['passed'])) {
      $hst['passed'] = [];
    }
    return $hst;
  }
  public function setTourHst($hst)
  {
    update_user_meta(get_current_user_id(), PPS_CODE . '-tour-hst', $hst);
  }
  public function clearTourHst()
  {
    delete_user_meta(get_current_user_id(), PPS_CODE . '-tour-hst');
  }
  public function addTourStep($d = [])
  {
    $hst = $this->getTourHst();
    $pointKey = $d['tourId'] . '-' . $d['pointId'];
    $hst['passed'][$pointKey] = 1;
    $this->setTourHst($hst);
  }
  public function closeTour($d = [])
  {
    $hst = $this->getTourHst();
    $pointKey = $d['tourId'] . '-' . $d['pointId'];
    $hst['closed'] = 1;
    $this->setTourHst($hst);
  }
  public function addTourFinish($d = [])
  {
    $hst = $this->getTourHst();
    $pointKey = $d['tourId'] . '-' . $d['pointId'];
    $hst['finished'] = 1;
    $this->setTourHst($hst);
  }
}
