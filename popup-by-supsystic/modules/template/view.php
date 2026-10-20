<?php

if (!defined('ABSPATH')) {
  exit();
}

class TemplateViewPpsgen extends BaseViewPpsgen
{
  public function renderTemplateList($params)
  {
    $templateType = $this->getFrame()->core('request')->getVar('template_type', 'get');
    if (empty($templateType)) {
      return esc_html('Undefined template type', 'ppsgen');
    }
    $this->assign('templateType', $templateType);
    $html = $this->renderTpl('list', 'template');
    return $html;
  }
}
