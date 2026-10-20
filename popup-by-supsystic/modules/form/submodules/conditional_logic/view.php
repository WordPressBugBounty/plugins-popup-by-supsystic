<?php

if (!defined('ABSPATH')) {
  exit();
}

class ConditionalLogicSubmoduleViewPpsgen extends BaseViewPpsgen
{
  public function renderFormConditionalLogicTab($params)
  {
    $tabs = !empty($params['tabs']) ? $params['tabs'] : [];
    $options = !empty($params['options']) ? $params['options'] : [];
    $this->assign('options', $params);
    $cl_data = $this->getModel()->getFormConditionalLogic($options['obj_id']);
    $logic_data = !empty($cl_data['logic_data']) ? $this->getModel()->_prepareValueFromStore(stripslashes($cl_data['logic_data']), 'json') : [];
    $hidden_fields = !empty($cl_data['hidden_fields']) ? $cl_data['hidden_fields'] : [];
    $this->assign('cl_data', $cl_data);
    $this->assign('logic_data', $logic_data);
    $this->assign('hidden_fields', $hidden_fields);
    $html = $this->renderTpl('edit_form_conditional_logic', 'form', 'conditional_logic');
    // The real tab replaces the Free promo tab
    unset($tabs['conditional_logic_pro']);
    $tabs['conditional_logic'] = [
      'title' => __('Conditional Logic', 'ppsgen'),
      'content' => $html,
    ];
    wp_localize_script('ppsgen-backend-form-edit', 'ppsgenFormDataConditionaLogic', [
      'logic_data' => $logic_data,
      'hidden_fields' => $hidden_fields,
      'languages' => $this->getFrame()->core('component')->getLanguages(),
    ]);
    $params['tabs'] = $tabs;
    $params['options'] = $options;
    return $params;
  }

  public function renderFormConditionalLogicToFrontend($form_id)
  {
    $cl_data = $this->getModel()->getFormConditionalLogic($form_id);
    if (!empty($cl_data)) {
      $logic_data = !empty($cl_data['logic_data']) ? $this->getModel()->_prepareValueFromStore(stripslashes($cl_data['logic_data']), 'json') : [];
      $hidden_fields = !empty($cl_data['hidden_fields']) ? $cl_data['hidden_fields'] : [];
      $acceptLang = $this->getFrame()->core('request')->getServerVar('HTTP_ACCEPT_LANGUAGE');
      $userLangs = array_map('trim', explode(',', $acceptLang));
      $primaryLang = !empty($userLangs[0]) ? substr($userLangs[0], 0, 2) : '';
      wp_localize_script('ppsgen-frontend-forms-logic', 'ppsgenFormDataConditionaLogic' . $form_id, [
        'logic_data' => $logic_data,
        'hidden_fields' => $hidden_fields,
        'user_logged_in' => is_user_logged_in() ? true : false,
        'user_lang' => $primaryLang,
      ]);
    }
  }

  // Editor preview: the same data as an inline script
  public function getPreviewInlineScript($formId): string
  {
    $formId = (int) $formId;
    $clData = $formId > 0 ? $this->getModel()->getFormConditionalLogic($formId) : [];
    if (empty($clData)) {
      return '';
    }
    $logicData = !empty($clData['logic_data']) ? $this->getModel()->_prepareValueFromStore(stripslashes($clData['logic_data']), 'json') : [];
    $acceptLang = (string) $this->getFrame()->core('request')->getServerVar('HTTP_ACCEPT_LANGUAGE');
    $userLangs = array_map('trim', explode(',', $acceptLang));
    $data = [
      'logic_data' => is_array($logicData) ? $logicData : [],
      'hidden_fields' => !empty($clData['hidden_fields']) ? $clData['hidden_fields'] : [],
      'user_logged_in' => is_user_logged_in(),
      'user_lang' => !empty($userLangs[0]) ? substr($userLangs[0], 0, 2) : '',
      'form_id' => $formId,
    ];
    return sprintf('<script id="ppsgen-cl-%1$d">window.ppsgenFormDataConditionaLogic%1$d = %2$s;</script>', $formId, wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP));
  }
}
