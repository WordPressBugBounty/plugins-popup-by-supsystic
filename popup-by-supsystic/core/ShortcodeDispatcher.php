<?php

if (!defined('ABSPATH')) {
  exit();
}

class ShortcodeDispatcherPpsgen
{
  protected $frame;
  protected $shortcodeToModuleMap = [];
  protected $frontendRequiredModules = [];

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->frame = $frame;
  }

  public function register($shortcode, $moduleSlug, $method)
  {
    $this->shortcodeToModuleMap[$shortcode] = ['slug' => $moduleSlug, 'method' => $method];
    add_shortcode($shortcode, [$this, 'dispatch']);
  }

  public function dispatch($atts = [], $content = '', $shortcode = '')
  {
    if (!isset($this->shortcodeToModuleMap[$shortcode])) {
      return '';
    }
    $moduleSlug = $this->shortcodeToModuleMap[$shortcode]['slug'];
    $method = $this->shortcodeToModuleMap[$shortcode]['method'];
    $this->frontendRequiredModules[$moduleSlug] = true;
    $module = $this->frame->getModule($moduleSlug, 'frontend');
    if ($module && method_exists($module, $method)) {
      return $module->$method($atts, $content, $shortcode);
    }
    return '';
  }

  public function isModuleRequired($moduleSlug)
  {
    return isset($this->frontendRequiredModules[$moduleSlug]);
  }
}
