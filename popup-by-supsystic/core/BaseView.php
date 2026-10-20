<?php

if (!defined('ABSPATH')) {
  exit();
}

#[AllowDynamicProperties]
abstract class BaseViewPpsgen
{
  protected $_module;
  protected $_helper;
  protected $_component;
  protected FramePpsgen $_frame;

  public function __construct(FramePpsgen $frame, string $token, $module)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_module = $module;
    $this->_frame = $frame;
    $this->_helper = $frame->core('helper');
    $this->_component = $frame->core('component');
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function getModule()
  {
    return $this->_module;
  }

  public function getModel()
  {
    return $this->_module->getModel();
  }

  public function getController()
  {
    return $this->_module->getController();
  }

  public function getComponent()
  {
    return $this->_component;
  }

  public function getHelper()
  {
    return $this->_helper;
  }

  public function assign($name, $value = null)
  {
    if (is_array($name)) {
      foreach ($name as $key => $val) {
        $this->$key = $val;
      }
    } else {
      $this->$name = $value;
    }
  }

  public function renderTpl($tplName, $module = '', $submodule = '')
  {
    if (empty($tplName) || empty($module)) {
      return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $tplName)) {
      return false;
    }
    $frame = $this->getFrame();
    $this->assign('frame', $frame);
    $this->assign('component', $frame->core('component'));
    // $module = strtolower($module);
    $tplPath = PPSGEN_PLUGIN_DIR . 'modules' . PPSGEN_DS . $module . PPSGEN_DS;
    if (!empty($submodule)) {
      $tplPath .= 'submodules' . PPSGEN_DS . strtolower($submodule) . PPSGEN_DS;
    }
    $tplPath .= 'tpl' . PPSGEN_DS . $tplName . '.php';
    if (!file_exists($tplPath)) {
      return false;
    }
    ob_start();
    include $tplPath;
    return ob_get_clean();
  }
}
