<?php

if (!defined('ABSPATH')) {
  exit();
}

abstract class BaseModulePpsgen
{
  protected FramePpsgen $_frame;
  protected $submodules = [];
  protected $slug;
  protected $parent = null;
  protected $is_submodule = false;
  protected $model;
  protected $view;
  protected $controller;
  protected $settings = [];
  protected $_inited = false;
  protected $_beforeInit;
  protected $token;
  protected $_afterInit = [];

  final public function __construct(FramePpsgen $frame, string $token, $slug, $parent_module = null)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_frame = $frame;
    $this->token = $token;
    $this->slug = $slug;
    $this->is_submodule = $parent_module !== null;
    $this->parent = $parent_module;
    $this->_beforeInit = $this->beforeInit();
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function getBeforeInit()
  {
    return $this->_beforeInit;
  }

  public function beforeInit()
  {
    return true;
  }

  final protected function _getAfterInit(): bool
  {
    return isset($this->_afterInit[$this->slug]);
  }

  final protected function _setAfterInit(): void
  {
    $this->_afterInit[$this->slug] = true;
  }

  final public function afterInit(): void
  {
    $this->_setAfterInit();
  }

  final public function getAfterInit(): bool
  {
    return $this->_getAfterInit();
  }

  public function deactivatePluginHook() {}

  public function registerSubmodule($subSlug)
  {
    $className = self::buildSubmoduleClassName($this->slug, $subSlug);
    if (!class_exists($className)) {
      $submodulePath = PPSGEN_PLUGIN_DIR . 'modules' . PPSGEN_DS . $this->slug . PPSGEN_DS . 'submodules' . PPSGEN_DS . $subSlug . PPSGEN_DS . 'module.php';
      if (file_exists($submodulePath)) {
        require_once $submodulePath;
      }
    }
    if (class_exists($className)) {
      $this->submodules[$subSlug] = new $className($this->_frame, $this->token, $subSlug, $this);
      return true;
    }
    return false;
  }

  public function getSlug()
  {
    return $this->slug;
  }

  public function getSubmod($subSlug)
  {
    return isset($this->submodules[$subSlug]) ? $this->submodules[$subSlug] : null;
  }

  public function getModel()
  {
    if (!isset($this->model)) {
      $class = $this->buildFullClassName('Model');
      if (!class_exists($class)) {
        $path = $this->getComponentFilePath('model');
        if (file_exists($path)) {
          require_once $path;
        }
      }
      $this->model = new $class($this->_frame, $this->token, $this);
    }
    return $this->model;
  }

  public function getView()
  {
    if (!isset($this->view)) {
      $class = $this->buildFullClassName('View');
      if (!class_exists($class)) {
        $path = $this->getComponentFilePath('view');
        if (file_exists($path)) {
          require_once $path;
        }
      }
      $this->view = new $class($this->_frame, $this->token, $this);
    }
    return $this->view;
  }

  public function getController()
  {
    if (!isset($this->controller)) {
      $class = $this->buildFullClassName('Controller');
      if (!class_exists($class)) {
        $path = $this->getComponentFilePath('controller');
        if (file_exists($path)) {
          require_once $path;
        }
      }
      $this->controller = new $class($this->_frame, $this->token, $this);
    }
    return $this->controller;
  }

  protected function getComponentFilePath($type)
  {
    if ($this->is_submodule && $this->parent) {
      return PPSGEN_PLUGIN_DIR . 'modules' . PPSGEN_DS . $this->parent->slug . PPSGEN_DS . 'submodules' . PPSGEN_DS . $this->slug . PPSGEN_DS . $type . '.php';
    } else {
      return PPSGEN_PLUGIN_DIR . 'modules' . PPSGEN_DS . $this->slug . PPSGEN_DS . $type . '.php';
    }
  }

  protected function buildFullClassName($type = '')
  {
    $parts = [];
    // if ($this->is_submodule && isset($this->parent)) {
    //   $parts = array_merge($parts, explode('_', $this->parent->slug));
    // }
    $parts = array_merge($parts, explode('_', $this->slug));
    $class = implode('', array_map('ucfirst', $parts));
    if ($this->is_submodule) {
      $class .= 'Submodule';
    }
    if (!empty($type)) {
      $class .= ucfirst($type);
    }
    $class .= 'Ppsgen';
    return $class;
  }

  protected static function buildSubmoduleClassName($parentSlug, $subSlug)
  {
    $parts = array_merge(explode('_', $subSlug));
    $class = implode('', array_map('ucfirst', $parts));
    return $class . 'SubmodulePpsgen';
  }

  // abstract public function init();
  public function init($context = 'backend')
  {
    if ($this->_inited) {
      return;
    }
    $this->_inited = true;
  }

  public function getIsInited()
  {
    return $this->_inited;
  }
}
