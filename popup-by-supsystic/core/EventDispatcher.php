<?php

if (!defined('ABSPATH')) {
  exit();
}

class EventDispatcherPpsgen
{
  protected $actions = [];
  protected $filters = [];
  private static $_instance = null;
  protected FramePpsgen $_frame;

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_frame = $frame;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function addAction(string $event, callable $callback, int $priority = 10): void
  {
    $this->actions[$event][$priority][] = $callback;
    ksort($this->actions[$event]);
  }

  public function doAction(string $event, ...$args): void
  {
    if (empty($this->actions[$event])) {
      return;
    }
    foreach ($this->actions[$event] as $callbacks) {
      foreach ($callbacks as $callback) {
        call_user_func_array($callback, $args);
      }
    }
  }

  public function addFilter(string $event, callable $callback, int $priority = 10): void
  {
    $this->filters[$event][$priority][] = $callback;
    ksort($this->filters[$event]);
  }

  public function applyFilters(string $event, $value, ...$args)
  {
    if (empty($this->filters[$event])) {
      return $value;
    }
    foreach ($this->filters[$event] as $callbacks) {
      foreach ($callbacks as $callback) {
        $value = call_user_func_array($callback, array_merge([$value], $args));
      }
    }
    return $value;
  }

  public function applyFiltersOnce(string $event, $value, ...$args)
  {
    if (empty($this->filters[$event])) {
      return $value;
    }
    foreach ($this->filters[$event] as $callbacks) {
      foreach ($callbacks as $callback) {
        $newValue = call_user_func_array($callback, array_merge([$value], $args));
        if ($newValue !== $value) {
          return $newValue;
        }
      }
    }
    return $value;
  }

  public function clear(): void
  {
    $this->actions = [];
    $this->filters = [];
  }
}
