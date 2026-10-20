<?php

if (!defined('ABSPATH')) {
  exit();
}

abstract class BaseControllerPpsgen
{
  protected FramePpsgen $_frame;
  protected $_module;

  public function __construct(FramePpsgen $frame, string $token, $module)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_module = $module;
    $this->_frame = $frame;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function getModule()
  {
    return $this->_module;
  }

  public function getView()
  {
    return $this->_module->getView();
  }

  public function getModel()
  {
    return $this->_module->getModel();
  }
}
