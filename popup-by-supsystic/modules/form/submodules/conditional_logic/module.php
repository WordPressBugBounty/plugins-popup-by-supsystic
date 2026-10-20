<?php

if (!defined('ABSPATH')) {
  exit();
}

// Form conditional logic (Free since the Free-to-PRO review: it improves results and fits a powerful Free)
class ConditionalLogicSubmodulePpsgen extends BaseModulePpsgen
{
  public function init($context = 'backend')
  {
    parent::init();
    if ($context == 'backend') {
      $this->getModel()->maybeMigrate();
      add_action('admin_enqueue_scripts', [$this, 'registerBackendAssets']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addFilter('form.edit.tabs', [$this, 'renderFormConditionalLogicTab']);
      $this->getFrame()
        ->core('eventDispatcher')
        ->addAction('form.edit.action.model.saved', [$this->getModel(), 'saveCondtitionalLogic']);
    }
    if ($context == 'frontend') {
      $this->getFrame()
        ->core('eventDispatcher')
        ->addAction('form.frontend.render.js', [$this->getView(), 'renderFormConditionalLogicToFrontend']);
    }
  }

  public function renderFormConditionalLogicTab($params = [])
  {
    return $this->getView()->renderFormConditionalLogicTab($params);
  }

  public function registerBackendAssets()
  {
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'form/submodules/conditional_logic/assets/';
    $tab = $this->getFrame()->isPluginAdminPage();
    switch ($tab) {
      case 'form_edit':
        // After the form editor script: the builder reads its fields (window.ppsgenFormBuilder)
        wp_register_script('ppsgen-backend-form-conditional-logic', $moduleAssetsUrl . 'js/backend.edit.js', ['jquery', 'ppsgen-backend-form-edit'], PPSGEN_VERSION, true);
        wp_enqueue_script('ppsgen-backend-form-conditional-logic');
        wp_register_style('ppsgen-backend-form-conditional-logic', $moduleAssetsUrl . 'css/backend.edit.css', [], PPSGEN_VERSION);
        wp_enqueue_style('ppsgen-backend-form-conditional-logic');
        break;
    }
  }
}
