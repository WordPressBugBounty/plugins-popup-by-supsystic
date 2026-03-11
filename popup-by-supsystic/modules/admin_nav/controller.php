<?php

class admin_navControllerPps extends controllerPps
{
  public function getPermissions()
  {
    return [
        PPS_USERLEVELS => [
            PPS_ADMIN => []
        ],
    ];
  }
}
