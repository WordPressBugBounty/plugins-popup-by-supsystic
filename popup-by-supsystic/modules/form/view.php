<?php

if (!defined('ABSPATH')) {
  exit();
}

class FormViewPpsgen extends BaseViewPpsgen
{
  public $loadForPreview = false;

  protected function getFormCustomCodePromoHtml(string $type): string
  {
    $title = $type === 'css' ? __('Custom CSS for every form.', 'ppsgen') : __('Custom JS for every form.', 'ppsgen');
    $desc = $type === 'css'
      ? '.ppsgenFormWrapper-{{ID}}, .ppsgenForm-{{ID}}, .ppsgenForm-{{ID}} .ppsgen-form-field'
      : "$('.ppsgenForm-{{ID}}') and jQuery(function($){ ... }) wrapper included automatically.";
    $features = [
      __('Object-level Custom JS', 'ppsgen'),
      __('Conditional logic, file uploads, maps, rating, WooCommerce and WordPress dynamic fields', 'ppsgen'),
      __('Advanced email verification and premium templates for forms, popups and emails', 'ppsgen'),
    ];
    return $this->getProUpsellTabBannerHtml($title, __('Unlock advanced form customization and PRO-only tools.', 'ppsgen'), $desc, $features, 'form_custom_code_pro');
  }

  protected function getProUpsellTabBannerHtml(string $title, string $text, string $codeExample, array $features, string $utmContent): string
  {
    $url = add_query_arg([
      'utm_source' => 'ces_plugin',
      'utm_medium' => 'feature',
      'utm_campaign' => $utmContent,
    ], 'https://supsystic.com/plugins/wordpress-popup-plugin/');
    $featureHtml = '';
    foreach ($features as $feature) {
      $featureHtml .= '<li>' . esc_html($feature) . '</li>';
    }
    return '
      <div class="ppsgenProOfferNotice ppsgenProOfferNoticeTab">
        <div class="ppsgenProOfferNoticeHero">
          <div class="ppsgenProOfferNoticeEyebrow">' . esc_html__('PRO FEATURES', 'ppsgen') . '</div>
          <div class="ppsgenProOfferNoticeBadges">
            <span class="ppsgenProOfferNoticeBadge">CRM</span>
            <span class="ppsgenProOfferNoticeBadge">EMAILS</span>
            <span class="ppsgenProOfferNoticeBadge">FORMS</span>
            <span class="ppsgenProOfferNoticeBadge">POPUPS</span>
          </div>
          <h3 class="ppsgenProOfferNoticeTitle">' . esc_html($title) . '</h3>
          <p class="ppsgenProOfferNoticeText">' . esc_html($text) . '</p>
          <div class="ppsgenProOfferNoticeCouponLabel">' . esc_html__('YEARLY LICENCE · 1 SITE', 'ppsgen') . '</div>
          <div class="ppsgenProOfferNoticeCoupon">
            <span><strong>$99</strong> / year</span>
          </div>
          <div class="ppsgenProOfferNoticeSubtext">' . esc_html__('$99 stays $99 when you renew · No contact-based pricing · 30-day money-back guarantee', 'ppsgen') . '</div>
          <div class="ppsgenProOfferNoticeActions">
            <a class="ppsgenProOfferNoticeBtn ppsgenProOfferNoticeBtnPrimary" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">
              <span class="dashicons dashicons-cart"></span>
              ' . esc_html__('Get PRO for $99 a year', 'ppsgen') . '
            </a>
          </div>
        </div>
        <div class="ppsgenProOfferNoticeFeatures">
          <div class="ppsgenProOfferNoticeFeaturesTitle">' . esc_html__('Included in PRO', 'ppsgen') . '</div>
          <ul class="ppsgenProOfferNoticeFeatureList">' . $featureHtml . '</ul>
          <div class="ppsgenProOfferNoticeCode"><code>' . esc_html($codeExample) . '</code></div>
        </div>
      </div>';
  }

  public function renderFormOverview($params)
  {
    $html = $this->renderTpl('overview', 'form');
    return $html;
  }

  public function renderFormList($params)
  {
    $request = $this->getFrame()->core('request');
    $page = max(1, intval($request->getVar('page', 'post') ?? 1));
    $perPage = intval($request->getVar('per_page', 'post') ?? 100);
    $orderBy = $request->getVar('order_by', 'post') ?? 'id';
    $orderDir = $request->getVar('order_dir', 'post') === 'DESC' ? 'DESC' : 'ASC';
    $filters = [];
    if (!empty($request->getVar('filter_id', 'post'))) {
      $filters['id'] = intval($request->getVar('filter_id', 'post'));
    }
    if (!empty($request->getVar('filter_name', 'post'))) {
      $filters['name'] = $request->getVar('filter_name', 'post');
    }
    if (!empty($request->getVar('filter_active', 'post'))) {
      $filters['active'] = $request->getVar('filter_active', 'post');
    }
    if (!empty($request->getVar('filter_order_index', 'post'))) {
      $filters['order_index'] = $request->getVar('filter_order_index', 'post');
    }
    if (!empty($request->getVar('filter_created_at', 'post'))) {
      $filters['created_at'] = $request->getVar('filter_created_at', 'post');
    }
    if (!empty($request->getVar('filter_updated_at', 'post'))) {
      $filters['updated_at'] = $request->getVar('filter_updated_at', 'post');
    }
    $model = $this->getModel();
    $data = $model->getForms([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $formIds = array_map(static function ($row) {
      return (int) ($row['id'] ?? 0);
    }, $data['rows']);
    $statsByForm = $model->getFormStatsSummaryByIds($formIds);
    $this->assign([
      'rows' => $data['rows'],
      'statsByForm' => $statsByForm,
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $html = $this->renderTpl('list', 'form');
    return $html;
  }

  public function renderFormOptions($params)
  {
    $formOptions = $this->getModel()->getAllOptions();
    $this->assign('formOptions', $formOptions);
    $html = $this->renderTpl('options', 'form');
    return $html;
  }

  public function renderFormEdit($params)
  {
    $request = $this->getFrame()->core('request');
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    if (!empty($objId)) {
      $formOptions = $this->getModel()->getFormById($objId);
      if (!empty($formOptions)) {
        $formOptions['settings'] = $this->getModel()->_prepareValueFromStore($formOptions['settings'], 'json');
        if (!empty($formOptions['settings'])) {
          if (!empty($formOptions['settings']['html_success_msg'])) {
            $formOptions['settings']['html_success_msg'] = htmlspecialchars_decode(stripslashes($formOptions['settings']['html_success_msg']));
          }
          if (!empty($formOptions['settings']['html_send_admin_text'])) {
            $formOptions['settings']['html_send_admin_text'] = htmlspecialchars_decode(stripslashes($formOptions['settings']['html_send_admin_text']));
          }
          if (!empty($formOptions['settings']['html_send_contact_text'])) {
            $formOptions['settings']['html_send_contact_text'] = htmlspecialchars_decode(stripslashes($formOptions['settings']['html_send_contact_text']));
          }
        }
        $formOptions['design'] = $this->getModel()->_prepareValueFromStore($formOptions['design'], 'json');
        $fields = $this->getModel()->getFormFields($objId, 'rewrite');
        $fieldsByKey = [];
        if (!empty($fields)) {
          foreach ($fields as $key => $field) {
            $fields[$key]['settings']['options'] = $fields[$key]['options'];
            $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
          }
        }
        $formOptions['fields'] = $fields;
        $options = $formOptions;
        wp_localize_script('ppsgen-backend-form-edit', 'ppsgenFormData', [
          'fields' => $fieldsByKey,
        ]);
        wp_enqueue_script('ppsgen-backend-form-edit');
      }
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Form with specific ID for editing', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $tabs = [
      'fields' => ['title' => __('Fields', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.edit.fields_html', $options)],
      'design' => ['title' => __('Design', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.edit.design_html', $options)],
      'options' => ['title' => __('Options', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.edit.options_html', $options)],
      'submits' => ['title' => __('Submissions', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.edit.submits_html', $options)],
      'custom_css' => ['title' => __('Custom CSS', 'ppsgen'), 'content' => $this->renderTpl('edit_form_custom_css', 'form')],
      'custom_js_pro' => ['title' => __('Custom JS (PRO)', 'ppsgen'), 'content' => $this->getFormCustomCodePromoHtml('js')],
    ];
    $tabs = $this->getFrame()
      ->core('eventDispatcher')
      ->applyFilters('form.edit.tabs', ['tabs' => $tabs, 'options' => $options]);
    $this->assign('tabs', $tabs['tabs']);
    $activeTab = $request->getVar('obj_edit_active_tab', 'post');
    if (empty($activeTab)) {
      $activeTab = $request->getVar('obj_edit_active_tab', 'get');
    }
    $this->assign('activeTab', !empty($activeTab) ? $activeTab : 'fields');
    $html = $this->renderTpl('edit', 'form');
    return $html;
  }

  public function renderFormEditOptions($params)
  {
    $allContactTags = $this->getFrame()->getModule('contact')->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    $allContactFields = $this->getFrame()->getModule('contact')->getSubmod('contact_field')->getModel()->getAllContactFieldsSlugList(true);
    $allContactFields = ['' => ''] + $allContactFields;
    $this->assign('allContactTags', $allContactTags);
    $this->assign('allContactFields', $allContactFields);
    $allWpUsers = get_users(['fields' => ['ID', 'display_name']]);
    $allWpUsers = wp_list_pluck($allWpUsers, 'display_name', 'ID');
    $allWpUsers = [0 => ''] + $allWpUsers;
    $this->assign('allWpUsers', $allWpUsers);
    $html = $this->renderTpl('edit_options', 'form');
    return $html;
  }

  public function renderFormEditFields($params)
  {
    $html = $this->renderTpl('edit_fields', 'form');
    return $html;
  }

  public function renderFormEditFieldsOptions($options)
  {
    $this->assign('options', $options);
    $html = $this->renderTpl('edit_fields_options', 'form');
    return $html;
  }

  public function renderFormEditSubmits($params)
  {
    $request = $this->getFrame()->core('request');
    $objId = !empty($params['id']) ? $params['id'] : false;
    $formSubmits = !empty($objId) ? $this->getModel()->getFormSubmits($objId) : '';
    $groupBy = $request->getVar('form_stats_group', 'get');
    $range = (int) $request->getVar('form_stats_range', 'get');
    $groupBy = in_array($groupBy, ['day', 'week', 'month'], true) ? $groupBy : 'day';
    $range = in_array($range, [7, 30, 90, 365], true) ? $range : 30;
    $trend = !empty($objId) ? $this->getModel()->getFormSubmitTrend((int) $objId, $groupBy, $range) : [];
    $this->assign('formSubmits', $formSubmits);
    $this->assign('formTrend', $trend);
    $this->assign('formTrendGroup', $groupBy);
    $this->assign('formTrendRange', $range);
    $html = $this->renderTpl('edit_submits', 'form');
    return $html;
  }

  public function renderFormEditDesign($params)
  {
    $html = $this->renderTpl('edit_design', 'form');
    return $html;
  }

  public function renderContactFormSubmits($params)
  {
    $objId = !empty($params['id']) ? $params['id'] : false;
    $formSubmits = !empty($objId) ? $this->getModel()->getContactFormSubmits($objId) : '';
    $this->assign('formSubmits', $formSubmits);
    $this->assign('objId', $objId);
    wp_register_style('ppsgen-backend-contact-form-submits-edit', PPSGEN_PLUGIN_URL . 'modules/form/assets/css/backend.contact_form_submits_edit.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-backend-contact-form-submits-edit');
    wp_register_script('ppsgen-backend-contact-form-submits-edit', PPSGEN_PLUGIN_URL . 'modules/form/assets/js/backend.contact_form_submits_edit.js', [], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-contact-form-submits-edit');
    $html = $this->renderTpl('edit_contact_form_submits', 'form');
    return $html;
  }

  public function renderPreview($params, $form_id = 0)
  {
    $popupPreview = false;
    if (!empty($form_id)) {
      $form = $this->getModel()->getFormById($form_id);
    } else {
      $popupPreview = true;
      $form = $params;
    }
    $obj_id = !empty($params['obj_id']) ? $params['obj_id'] : 0;
    if (empty($obj_id) && !empty($form_id)) {
      $obj_id = $form_id;
    }
    if (empty($obj_id)) {
      $obj_id = 999;
    }
    $form_id = $obj_id;
    if (!empty($form)) {
      $form['module_options'] = $this->getModel()->getAllOptions();
      $form['settings'] = $this->getModel()->_prepareValueFromStore($form['settings'], 'json');
      $form['design'] = $this->getModel()->_prepareValueFromStore($form['design'], 'json');
      if (empty($form['fields'])) {
        $fields = $this->getModel()->getFormFields($form_id, 'merge');
      } else {
        $fields = $form['fields'];
      }
      if (!empty($fields)) {
        foreach ($fields as $key => $field) {
          $fieldOptions = !empty($field['options']) && is_array($field['options']) ? $field['options'] : [];
          $fieldSettings = $popupPreview ? $fields[$key] : ($field['settings'] ?? []);
          $fields[$key]['settings'] = is_array($fieldSettings) ? $fieldSettings : [];
          $fields[$key]['settings']['options'] = $fieldOptions;
          $fields[$key]['options'] = $fieldOptions;
        }
        usort($fields, static function ($a, $b) {
          return (int) ($a['order_index'] ?? 0) <=> (int) ($b['order_index'] ?? 0);
        });
        $form['fields'] = $fields;
      } else {
        $form['fields'] = [];
      }
      $options = $form;
    }
    $options['obj_id'] = $form_id;
    if (empty($options)) {
      return esc_html__('Cant find Options', 'ppsgen');
    }
    if (empty($form['fields'])) {
      return esc_html__('Form is empty', 'ppsgen');
    }
    $this->assign('options', $options);

    $html = $this->renderTpl('view', 'form');
    $helper = $this->getFrame()->core('helper');
    $css = $helper->minifyCss($helper->mergeCssRulesBySelector($this->buildFormCss($options)));
    $css .= 'div.ppsgenFormWrapper-' . $form_id . ' {display:flex !important;}';
    $css .= $helper->minifyCss($helper->mergeCssRulesBySelector($helper->prepareFrontendCustomCss($options['settings']['custom_css'] ?? '', $obj_id)));
    $css = $helper->moveCssImportsToTop($css);

    $styles = [PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenfa/ppsgen-fa-all.min.css', PPSGEN_PLUGIN_URL . 'public/assets/css/ppsgen.frontend.css', PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenselect/ppsgenselect.css', PPSGEN_PLUGIN_URL . 'public/assets/css/ppsgen.frontend.forms.css'];
    $scripts = [includes_url('js/jquery/jquery.min.js'), PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenselect/ppsgenselect.js', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.js', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.forms.js', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.forms.logic.js'];
    $additionalScripts = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.preview.load.scripts', []) ?? [];
    $additionalInlineScripts = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.preview.load.inline.scripts', $options) ?? '';
    if (is_array($additionalInlineScripts)) {
      $additionalInlineScripts = '';
    }
    $conditionalLogic = $this->getModule()->getSubmod('conditional_logic');
    if ($conditionalLogic) {
      $additionalInlineScripts .= $conditionalLogic->getView()->getPreviewInlineScript($obj_id);
    }
    $additionalInlineScripts .= $helper->preparePreviewCustomJs($options['settings']['custom_js'] ?? '', $obj_id);
    $additionalInlineScripts .= $helper->getPreviewFormSubmitScript();
    $scripts = array_merge($scripts, $additionalScripts);
    return $data = ['html' => $html, 'css' => $css, 'styles' => $styles, 'scripts' => $scripts, 'inline_scripts' => $additionalInlineScripts];
  }

  public function renderFormView($params)
  {
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    if (!empty($objId)) {
      $form = $this->getModel()->getFormById($objId);
      if (!empty($form)) {
        $form['module_options'] = $this->getModel()->getAllOptions();
        $form['settings'] = $this->getModel()->_prepareValueFromStore($form['settings'], 'json');
        $form['design'] = $this->getModel()->_prepareValueFromStore($form['design'], 'json');
        $formIsEnabled = $this->getModel()->formIsEnabled($objId);
        if (!$this->canShowForm($form) || empty($formIsEnabled)) {
          return ''; //Form not availiable for this user
        }
        $fields = $this->getModel()->getFormFields($objId, 'merge');
        if (!empty($fields)) {
          foreach ($fields as $key => $field) {
            $fields[$key]['settings'] = $this->getModel()->_prepareValueFromStore($fields[$key]['settings'], 'json');
            $fields[$key]['settings']['options'] = $fields[$key]['options'];
          }
          $form['fields'] = $fields;
        } else {
          $form['fields'] = [];
        }
        $options = $form;
      }
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Form with specific ID', 'ppsgen');
    }
    if (empty($form['fields'])) {
      return esc_html__('Form is empty', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $html = $this->renderTpl('view', 'form');
    $helper = $this->getFrame()->core('helper');
    $css = $helper->minifyCss($helper->mergeCssRulesBySelector($this->buildFormCss($options)));
    $css .= $helper->minifyCss($helper->mergeCssRulesBySelector($helper->prepareFrontendCustomCss($options['settings']['custom_css'] ?? '', $objId)));
    $this->loadFrontendFormScripts();
    $this->getModel()->statsAddView($objId);
    $formStyleTitle = 'ppsgen-frontend-forms-' . $objId;
    wp_register_style($formStyleTitle, false, [], PPSGEN_VERSION);
    wp_enqueue_style($formStyleTitle);
    wp_add_inline_style('ppsgen-frontend-forms-' . $objId, $css);
    wp_localize_script('ppsgen-frontend-forms', 'ppsgenFrontendForm' . $objId, [
      'nonce_code' => wp_create_nonce('ppsgenFormSubmit' . $objId),
      'form_id' => $objId,
    ]);
    $customJs = $helper->prepareFrontendCustomJs($options['settings']['custom_js'] ?? '', $objId);
    if ($customJs !== '') {
      wp_add_inline_script('ppsgen-frontend-forms', $customJs);
    }
    $this->getFrame()->core('component')->loadFA();
    $this->getFrame()->core('eventDispatcher')->doAction('form.frontend.render.js', $objId);
    return $html;
  }

  public function canShowForm($form)
  {
    // Who sees it: known contacts, new visitors, a list or a tag
    if (function_exists('ppsgen_visitor_matches_audience') && !ppsgen_visitor_matches_audience($form['settings'] ?? [])) {
      return false;
    }
    if (empty($form['settings']['form_restrictions'])) {
      return true;
    }
    $restriction = $form['settings']['form_restrictions'];
    $user = wp_get_current_user();
    $isUserLoggedIn = is_user_logged_in();
    switch ($restriction) {
      case 'show_logged_in':
        if (!$isUserLoggedIn) {
          return false;
        }
        break;
      case 'show_guests':
        if ($isUserLoggedIn) {
          return false;
        }
        break;
      case 'show_not_submitted':
        $formId = isset($form['id']) ? $form['id'] : 0;
        // Check by user ID
        if ($isUserLoggedIn) {
          $userId = $user->ID;
          if ($this->getModel()->hasUserSubmittedForm($formId, $userId)) {
            return false;
          }
        }
        // Check by IP
        $userIp = $this->getFrame()->core('request')->getServerVar('REMOTE_ADDR');
        if (!empty($userIp) && $this->getModel()->hasIpSubmittedForm($formId, $userIp)) {
          return false;
        }
        // Check by cookie
        if (!empty($_COOKIE['ppsgen_form_' . $formId . '_submitted'])) {
          return false;
        }
        break;
      case 'show_all':
      default:
        break;
    }
    $allowedRoles = $form['settings']['form_restrictions_user_roles'] ?? [];
    if (!empty($allowedRoles) && !in_array('show_all', $allowedRoles)) {
      if (!$isUserLoggedIn) {
        return false;
      }
      $userRoles = (array) $user->roles;
      $intersect = array_intersect($allowedRoles, $userRoles);
      if (empty($intersect)) {
        return false;
      }
    }
    return true;
  }

  public function loadFrontendFormScripts()
  {
    wp_register_style('ppsgen-frontend-forms', PPSGEN_PLUGIN_URL . 'public/assets/css/ppsgen.frontend.forms.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-frontend-forms');
    wp_register_script('ppsgen-frontend-forms', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.forms.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-frontend-forms');
    wp_register_script('ppsgen-frontend-forms-logic', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.forms.logic.js', ['jquery', 'ppsgen-frontend-forms'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-frontend-forms-logic');
  }

  public function renderFormEditDesignOptions($options)
  {
    return '';
  }

  public function buildFormCss($options)
  {
    $design = !empty($options['design']) ? $options['design'] : '';
    $fields = !empty($options['fields']) ? $options['fields'] : '';
    $objId = !empty($options['obj_id']) ? $options['obj_id'] : '';
    $cfClass = 'ppsgenForm-' . $objId;
    $cfClassWrapper = 'ppsgenFormWrapper-' . $objId;
    $component = $this->getFrame()->core('component');
    $getFontFamilyCss = static function (string $key) use ($component, $design): string {
      $fontFamily = $component->value($design[$key] ?? null, 'Default');
      return $component->getFontFamilyCss($fontFamily);
    };

    $selectIconColor = $component->value($design['form_select_icon_color'] ?? null, '#333');
    $cssLine = ".{$cfClass} .ppsgenselect-single, .{$cfClass} select {
      line-height: inherit;
      height: inherit;
      cursor:pointer;
      outline:0;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: none;
     }";
    $cssLine .= ".{$cfClass} .ppsgen-form-inputselectWrap .ppsgen-form-inputFieldWrap::after {
      content: '';
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      border-left: 5px solid transparent;
      border-right: 5px solid transparent;
      border-top: 6px solid {$selectIconColor};
     }";
    $cssLine .= ".{$cfClass} input, .{$cfClass} textarea {
      outline:0;
     }";
    $cssLine .= ".{$cfClass} textarea {
      width:100%;
      outline:0;
     }";
    $component->setCss($cssLine);

    // Form Margin
    $marginT = $component->value($design['form_margin']['top'] ?? null, '0');
    $marginTU = $component->cvalue($design['form_margin']['top_unit'] ?? null, 'px');
    $marginB = $component->value($design['form_margin']['bottom'] ?? null, '0');
    $marginBU = $component->cvalue($design['form_margin']['bottom_unit'] ?? null, 'px');
    $marginL = $component->value($design['form_margin']['left'] ?? null, '0');
    $marginLU = $component->cvalue($design['form_margin']['left_unit'] ?? null, 'px');
    $marginR = $component->value($design['form_margin']['right'] ?? null, '0');
    $marginRU = $component->cvalue($design['form_margin']['right_unit'] ?? null, 'px');
    $cssLine = ".{$cfClass} {
     margin-top: {$marginT}{$marginTU};
     margin-bottom: {$marginB}{$marginBU};
     margin-left: {$marginL}{$marginLU};
     margin-right: {$marginR}{$marginRU};
    }";
    $component->setCss($cssLine);

    // Form Padding
    $paddingT = $component->value($design['form_padding']['top'] ?? null, '10');
    $paddingTU = $component->cvalue($design['form_padding']['top_unit'] ?? null, 'px');
    $paddingB = $component->value($design['form_padding']['bottom'] ?? null, '10');
    $paddingBU = $component->cvalue($design['form_padding']['bottom_unit'] ?? null, 'px');
    $paddingL = $component->value($design['form_padding']['left'] ?? null, '10');
    $paddingLU = $component->cvalue($design['form_padding']['left_unit'] ?? null, 'px');
    $paddingR = $component->value($design['form_padding']['right'] ?? null, '10');
    $paddingRU = $component->cvalue($design['form_padding']['right_unit'] ?? null, 'px');
    $cssLine = ".{$cfClass} {
     padding-top: {$paddingT}{$paddingTU};
     padding-bottom: {$paddingB}{$paddingBU};
     padding-left: {$paddingL}{$paddingLU};
     padding-right: {$paddingR}{$paddingRU};
    }";
    $component->setCss($cssLine);

    // Form Fields Gap
    $formGap = $component->value($design['form_gap'] ?? null, '16');
    $formGapUnit = $component->cvalue($design['form_gap_unit'] ?? null, 'px');
    $cssLine = ".{$cfClass} {
      gap: {$formGap}{$formGapUnit};
      }";
    $component->setCss($cssLine);

    $formAlign = $component->value($design['form_contaner_align'] ?? null, 'left');
    if ($formAlign == 'left') {
      $formAlign = 'flex-start';
    }
    if ($formAlign == 'right') {
      $formAlign = 'flex-end';
    }
    if ($formAlign == 'justify') {
      $formAlign = 'space-between';
    }
    $cssLine = ".{$cfClassWrapper} {
      display:flex;
      position:relative;
      justify-content: {$formAlign};
      }";
    $component->setCss($cssLine);

    // Form Width
    $widthSelect = $component->value($design['form_width_select'] ?? null, 'manual');
    $width = $component->value($design['form_width'] ?? null, '100');
    $widthUnit = $component->cvalue($design['form_width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssLine = ".{$cfClass} {
      width: {$width}{$widthUnit};
      }";
    } elseif ($widthSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$cfClass} {
      width: {$widthSelect};
      }";
    }
    $component->setCss($cssLine);

    // Form Max Width
    $widthSelect = $component->value($design['form_max_width_select'] ?? null, 'auto');
    $width = $component->value($design['form_max_width'] ?? null, '100');
    $widthUnit = $component->cvalue($design['form_max_width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssLine = ".{$cfClass} {
      max-width: {$width}{$widthUnit};
      }";
    } elseif ($widthSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$cfClass} {
      max-width: {$widthSelect};
      }";
    }
    $component->setCss($cssLine);

    // Form Height
    $heightSelect = $component->value($design['form_height_select'] ?? null, 'manual');
    $height = $component->value($design['form_height'] ?? null, '100');
    $heightUnit = $component->cvalue($design['form_height_unit'] ?? null, '%');
    if ($heightSelect == 'manual') {
      $cssLine = ".{$cfClass} {
      height: {$height}{$heightUnit};
      }";
    } elseif ($heightSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$cfClass} {
      height: {$heightSelect};
      }";
    }
    $component->setCss($cssLine);

    // Form Max Height
    $heightSelect = $component->value($design['form_max_height_select'] ?? null, 'auto');
    $height = $component->value($design['form_max_height'] ?? null, '100');
    $heightUnit = $component->cvalue($design['form_max_height_unit'] ?? null, '%');
    if ($heightSelect == 'manual') {
      $cssLine = ".{$cfClass} {
      max-height: {$height}{$heightUnit};
      }";
    } elseif ($heightSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$cfClass} {
      max-height: {$heightSelect};
      }";
    }
    $component->setCss($cssLine);

    // Form Border
    $border = $component->value($design['form_border'] ?? null, '1');
    $borderUnit = $component->cvalue($design['form_border_unit'] ?? null, 'px');
    $borderStyle = $component->value($design['form_border_style'] ?? null, 'solid');
    $borderColor = $component->value($design['form_border_color'] ?? null, '#dddddd');
    if (!empty($border)) {
      $cssLine = ".{$cfClass} {
      border: {$border}{$borderUnit} {$borderStyle} {$borderColor};
      }";
      $component->setCss($cssLine);
    }

    // Form Border Radius
    $border = $component->value($design['form_border_radius'] ?? null, '4');
    $borderUnit = $component->cvalue($design['form_border_radius_unit'] ?? null, 'px');
    if (!empty($border)) {
      $cssLine = ".{$cfClass} {
      border-radius: {$border}{$borderUnit};
      }";
      $component->setCss($cssLine);
    }

    // Form Overflow
    $overflow = $component->value($design['form_overflow'] ?? null, '');
    $overflowX = $component->value($design['form_overflow_x'] ?? null, '');
    $overflowY = $component->value($design['form_overflow_y'] ?? null, '');
    if (!empty($overflow)) {
      $cssLine = ".{$cfClass} {
      overflow: {$overflow};    
      }";
      $component->setCss($cssLine);
    }
    if (!empty($overflowX)) {
      $cssLine = ".{$cfClass} {
      overflow-x: {$overflowX};    
      }";
      $component->setCss($cssLine);
    }
    if (!empty($overflowY)) {
      $cssLine = ".{$cfClass} {
      overflow-y: {$overflowY};    
      }";
      $component->setCss($cssLine);
    }

    // Form Background
    $bgType = $component->value($design['form_bg_type'] ?? null, 'color');
    if ($bgType === 'color') {
      $color = $component->value($design['form_background'] ?? null, '#ffffff');
      $color = esc_attr($color);
      $cssLine = ".{$cfClass} {
            background-color: {$color};
        }";
      $component->setCss($cssLine);
    }

    if ($bgType === 'image') {
      $url = esc_url($component->value($design['form_bg_image'] ?? null, ''));
      $size = esc_attr($component->value($design['form_bg_size'] ?? null, 'cover'));
      $repeat = esc_attr($component->value($design['form_bg_repeat'] ?? null, 'no-repeat'));
      $position = esc_attr($component->value($design['form_bg_position'] ?? null, 'center center'));
      $bgimgcolor = $component->value($design['form_bg_img_color'] ?? null, 'rgba(255,255,255,.0)');
      $opacity = $component->value($design['form_bg_opacity'] ?? null, '100');
      if (!is_numeric($opacity)) {
        $opacity = 100;
      }
      $opacity = (float) $opacity;
      if ($opacity < 0) {
        $opacity = 0;
      }
      if ($opacity > 100) {
        $opacity = 100;
      }
      $opacity = $opacity / 100;
      if (!empty($url)) {
        $cssLine = ".{$cfClass} {
              position: relative;
          }
          .{$cfClass}::before {
              content: '';
              position: absolute;
              inset: 0;
              background-image: url('{$url}');
              background-size: {$size};
              background-repeat: {$repeat};
              background-position: {$position};
              opacity: {$opacity};
              pointer-events: none;
              background-color: {$bgimgcolor};
              background-blend-mode: multiply;
              border-radius: {$border}{$borderUnit};
              z-index: 0;
          }
          .{$cfClass} > * {
              position: relative;
              z-index: 1;
          }";
        $component->setCss($cssLine);
      }
    }

    $bgFilter = $component->value($design['form_bg_filter'] ?? null, 'blur');
    $bgFilterSize = $component->value($design['form_bg_filter_size'] ?? null, '0');
    if (!empty($bgFilterSize)) {
      if ($bgFilter == 'blur') {
        $cssLine = ".{$cfClass} {
          backdrop-filter: {$bgFilter}({$bgFilterSize}px);
          }";
        $component->setCss($cssLine);
      } else {
        $cssLine = ".{$cfClass} {
          backdrop-filter: {$bgFilter}({$bgFilterSize}%);
          }";
        $component->setCss($cssLine);
      }
    }

    // Form Box Shadow
    $boxShadowT = $component->value($design['form_box_shadow']['top'] ?? null, '0');
    $boxShadowTU = $component->cvalue($design['form_box_shadow']['top_unit'] ?? null, 'px');
    $boxShadowB = $component->value($design['form_box_shadow']['bottom'] ?? null, '0');
    $boxShadowBU = $component->cvalue($design['form_box_shadow']['bottom_unit'] ?? null, 'px');
    $boxShadowL = $component->value($design['form_box_shadow']['left'] ?? null, '0');
    $boxShadowLU = $component->cvalue($design['form_box_shadow']['left_unit'] ?? null, 'px');
    $boxShadowR = $component->value($design['form_box_shadow']['right'] ?? null, '0');
    $boxShadowRU = $component->cvalue($design['form_box_shadow']['right_unit'] ?? null, 'px');
    $boxShadowColor = $component->value($design['form_box_shadow_color'] ?? null, '');
    $cssLine = ".{$cfClass} {
    box-shadow: {$boxShadowT}{$boxShadowTU} {$boxShadowR}{$boxShadowRU} {$boxShadowL}{$boxShadowLU}  {$boxShadowB}{$boxShadowBU} {$boxShadowColor};
    }";
    $component->setCss($cssLine);

    // Form Text Shadow
    $textShadowT = $component->value($design['form_text_shadow']['top'] ?? null, '0');
    $textShadowTU = $component->cvalue($design['form_text_shadow']['top_unit'] ?? null, 'px');
    $textShadowL = $component->value($design['form_text_shadow']['left'] ?? null, '0');
    $textShadowLU = $component->cvalue($design['form_text_shadow']['left_unit'] ?? null, 'px');
    $textShadowR = $component->value($design['form_text_shadow']['right'] ?? null, '0');
    $textShadowRU = $component->cvalue($design['form_text_shadow']['right_unit'] ?? null, 'px');
    $textShadowColor = $component->value($design['form_text_shadow_color'] ?? null, '');
    $cssLine = ".{$cfClass} * {
    text-shadow: {$textShadowT}{$textShadowTU} {$textShadowR}{$textShadowRU} {$textShadowL}{$textShadowLU} {$textShadowColor};
    }";
    $component->setCss($cssLine);

    // Form Radio Checkbox Font Style
    $radioStyle = $component->value($design['radio_style'] ?? null, 'regular');
    $checkboxStyle = $component->value($design['cbox_style'] ?? null, 'regular');
    $fontSize = $component->value($design['radcheckcus_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['radcheckcus_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['radcheckcus_font_color'] ?? null, '#000000');
    if ($radioStyle !== 'standard') {
      $css = ".{$cfClass} .ppsgen-form-inputFieldWrap input[type='radio'] {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            font-size: {$fontSize}{$fontSizeUnit};
            color: {$fontColor};
            display: inline-block;
            vertical-align: middle;
            text-align: center;
            line-height: 1;
            background: none;
        }";
      $css .= ".{$cfClass} .ppsgen-form-inputFieldWrap input[type='radio']::before {
            content: '\\f111';
            display: inline-block;
            ";
      if ($radioStyle === 'regular') {
        $css .= "font-family: 'FAPPSGEN';
            font-style: normal;
            font-weight: 400;";
      } elseif ($radioStyle === 'solid') {
        $css .= "font-family: 'FAPPSGEN9';
            font-style: normal;
            font-weight: 900;";
      }
      $css .= "
        }";
      $css .= ".{$cfClass} .ppsgen-form-inputFieldWrap input[type='radio']:checked::before {
            content: '\\f058';
        }";
      $component->setCss($css);
    }
    if ($checkboxStyle !== 'standard') {
      $css = ".{$cfClass} .ppsgen-form-inputFieldWrap input[type='checkbox'] {
          appearance: none;
          -webkit-appearance: none;
          -moz-appearance: none;
          font-size: {$fontSize}{$fontSizeUnit};
          color: {$fontColor};
          display: inline-block;
          vertical-align: middle;
          text-align: center;
          line-height: 1;
          background: none;
      }";
      $css .= ".{$cfClass} .ppsgen-form-inputFieldWrap input[type='checkbox']::before {
          content: '\\f0c8';
          display: inline-block;
          ";
      if ($checkboxStyle === 'regular') {
        $css .= "font-family: 'FAPPSGEN';
          font-style: normal;
          font-weight: 400;";
      } elseif ($checkboxStyle === 'solid') {
        $css .= "font-family: 'FAPPSGEN9';
          font-style: normal;
          font-weight: 900;";
      }
      $css .= "
      }";
      $css .= ".{$cfClass} .ppsgen-form-inputFieldWrap input[type='checkbox']:checked::before {
          content: '\\f14a';
      }";
      $component->setCss($css);
    }

    // Form Font Style
    $fontSize = $component->value($design['form_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['form_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['form_font_color'] ?? null, '#000000');
    $fontStyle = $component->value($design['form_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['form_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('form_font_family');
    $cssLine = ".{$cfClass} {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Label Style
    $fontSize = $component->value($design['label_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['label_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['label_font_color'] ?? null, '#000000');
    $fontStyle = $component->value($design['label_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['label_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('label_font_family');
    $cssLine = ".{$cfClass} .ppsgen-form-inputLabel {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} .ppsgen-form-inputLabel {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Required Icon Style
    $fontSize = $component->value($design['requiredicon_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['requiredicon_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['requiredicon_font_color'] ?? null, '#dd3333');
    $fontStyle = $component->value($design['requiredicon_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['requiredicon_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('requiredicon_font_family');
    $cssLine = ".{$cfClass} .ppsgen-form-inputRequired {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} .ppsgen-form-inputRequired {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Field Error Text Style
    $fontSize = $component->value($design['ferror_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['ferror_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['ferror_font_color'] ?? null, '#dd0000');
    $fontStyle = $component->value($design['ferror_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['ferror_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('ferror_font_family');
    $cssLine = ".{$cfClass} .ppsgen-form-inputError {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} .ppsgen-form-inputError {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Tooltip Text Style
    $fontSize = $component->value($design['tooltiptext_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['tooltiptext_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['tooltiptext_font_color'] ?? null, '#000000');
    $fontStyle = $component->value($design['tooltiptext_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['tooltiptext_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('tooltiptext_font_family');
    $cssLine = ".{$cfClass} .ppsgen-form-inputTooltipText {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} .ppsgen-form-inputTooltipText {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Tooltip Icon Style
    $tooltipFontSize = $component->value($design['tooltipicon_font_size'] ?? null, '10');
    $tooltipBgSize = $component->cvalue($design['tooltipicon_wh_size'] ?? null, '16');
    $tooltipIconColor = $component->value($design['tooltipicon_color_icon'] ?? null, '#ffffff');
    $tooltipBgColor = $component->value($design['tooltipicon_color_bg'] ?? null, '#b9b9b9');
    $cssLine = ".{$cfClass} .ppsgen-form-inputTooltipIcon {
    width: {$tooltipBgSize}px;
    height: {$tooltipBgSize}px;
    font-size: {$tooltipFontSize}px;
    background: {$tooltipBgColor};
    color: {$tooltipIconColor};
    font-style:normal;
    font-weight:normal;
    }";
    $component->setCss($cssLine);

    // Tooltip Border
    $border = $component->value($design['tooltiptext_border'] ?? null, '1');
    $borderUnit = $component->cvalue($design['tooltiptext_border_unit'] ?? null, 'px');
    $borderStyle = $component->value($design['tooltiptext_border_style'] ?? null, 'solid');
    $borderColor = $component->value($design['tooltiptext_border_color'] ?? null, '#b9b9b9');
    if (!empty($border)) {
      $cssLine = ".{$cfClass} .ppsgen-form-inputTooltipText {
      border: {$border}{$borderUnit} {$borderStyle} {$borderColor};
      }";
      $component->setCss($cssLine);
    }

    // Tooltip Border
    $background = $component->value($design['tooltiptext_background'] ?? null, '#ffffff');
    if (!empty($background)) {
      $cssLine = ".{$cfClass} .ppsgen-form-inputTooltipText {
      background: {$background};
      }";
      $component->setCss($cssLine);
    }

    // Field Input Text Style
    $fontSize = $component->value($design['finput_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['finput_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['finput_font_color'] ?? null, '#000000');
    $fontStyle = $component->value($design['finput_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['finput_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('finput_font_family');
    $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-container {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-container, .{$cfClass} .ppsgenselect-single span, .{$cfClass} .ppsgenselect-search .fappsgen-search {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Field Input Placeholder Text Style
    $fontSize = $component->value($design['fplace_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['fplace_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['fplace_font_color'] ?? null, '#919191');
    $fontStyle = $component->value($design['fplace_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['fplace_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('fplace_font_family');
    $cssLine = ".{$cfClass} input::placeholder, .{$cfClass} textarea::placeholder {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} input::placeholder, .{$cfClass} textarea::placeholder {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Field Input Padding
    $paddingT = $component->value($design['finput_padding']['top'] ?? null, '5');
    $paddingTU = $component->cvalue($design['finput_padding']['top_unit'] ?? null, 'px');
    $paddingB = $component->value($design['finput_padding']['bottom'] ?? null, '5');
    $paddingBU = $component->cvalue($design['finput_padding']['bottom_unit'] ?? null, 'px');
    $paddingL = $component->value($design['finput_padding']['left'] ?? null, '5');
    $paddingLU = $component->cvalue($design['finput_padding']['left_unit'] ?? null, 'px');
    $paddingR = $component->value($design['finput_padding']['right'] ?? null, '5');
    $paddingRU = $component->cvalue($design['finput_padding']['right_unit'] ?? null, 'px');
    $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='range']):not([type='color']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-single {
      padding-top: {$paddingT}{$paddingTU};
      padding-bottom: {$paddingB}{$paddingBU};
      padding-left: {$paddingL}{$paddingLU};
      padding-right: {$paddingR}{$paddingRU};
    }";
    $component->setCss($cssLine);

    // Field Input Border
    $border = $component->value($design['finput_border'] ?? null, '1');
    $borderUnit = $component->cvalue($design['finput_border_unit'] ?? null, 'px');
    $borderStyle = $component->value($design['finput_border_style'] ?? null, 'solid');
    $borderColor = $component->value($design['finput_border_color'] ?? null, '#c5c5c5');
    if (!empty($border)) {
      $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='range']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-single, .{$cfClass} .ppsgenselect-drop {
      border: {$border}{$borderUnit} {$borderStyle} {$borderColor};
      }";
      $component->setCss($cssLine);
    }

    // Field Input Border Radius
    $border = $component->value($design['finput_border_radius'] ?? null, '4');
    $borderUnit = $component->cvalue($design['finput_border_radius_unit'] ?? null, 'px');
    if (!empty($border)) {
      $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='range']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-single, .{$cfClass} .ppsgenselect-container {
      border-radius: {$border}{$borderUnit};
      }";
      $component->setCss($cssLine);
    }

    // Field Input Background
    $background = $component->value($design['finput_background'] ?? null, '#f5f5f5');
    if (!empty($background)) {
      $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='range']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-single, .{$cfClass} .ppsgenselect-container {
      background: {$background};
      background-color: {$background};
      }";
      $component->setCss($cssLine);
    }

    // Field Input Box Shadow
    $boxShadowT = $component->value($design['finput_box_shadow']['top'] ?? null, '0');
    $boxShadowTU = $component->cvalue($design['finput_box_shadow']['top_unit'] ?? null, 'px');
    $boxShadowB = $component->value($design['finput_box_shadow']['bottom'] ?? null, '0');
    $boxShadowBU = $component->cvalue($design['finput_box_shadow']['bottom_unit'] ?? null, 'px');
    $boxShadowL = $component->value($design['finput_box_shadow']['left'] ?? null, '0');
    $boxShadowLU = $component->cvalue($design['finput_box_shadow']['left_unit'] ?? null, 'px');
    $boxShadowR = $component->value($design['finput_box_shadow']['right'] ?? null, '0');
    $boxShadowRU = $component->cvalue($design['finput_box_shadow']['right_unit'] ?? null, 'px');
    $boxShadowColor = $component->value($design['finput_box_shadow_color'] ?? null, '');
    $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='range']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-container {
    box-shadow: {$boxShadowT}{$boxShadowTU} {$boxShadowR}{$boxShadowRU} {$boxShadowL}{$boxShadowLU}  {$boxShadowB}{$boxShadowBU} {$boxShadowColor};
    }";
    $component->setCss($cssLine);

    // Field Input Text Shadow
    $textShadowT = $component->value($design['finput_text_shadow']['top'] ?? null, '0');
    $textShadowTU = $component->cvalue($design['finput_text_shadow']['top_unit'] ?? null, 'px');
    $textShadowL = $component->value($design['finput_text_shadow']['left'] ?? null, '0');
    $textShadowLU = $component->cvalue($design['finput_text_shadow']['left_unit'] ?? null, 'px');
    $textShadowR = $component->value($design['finput_text_shadow']['right'] ?? null, '0');
    $textShadowRU = $component->cvalue($design['finput_text_shadow']['right_unit'] ?? null, 'px');
    $textShadowColor = $component->value($design['finput_text_shadow_color'] ?? null, '');
    $cssLine = ".{$cfClass} input:not([type='submit']):not([type='checkbox']):not([type='radio']):not([type='range']), .{$cfClass} textarea, .{$cfClass} select, .{$cfClass} .ppsgenselect-container {
    text-shadow: {$textShadowT}{$textShadowTU} {$textShadowR}{$textShadowRU} {$textShadowL}{$textShadowLU} {$textShadowColor};
    }";
    $component->setCss($cssLine);

    // Field Submit Text Style
    $fontSize = $component->value($design['submit_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['submit_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['submit_font_color'] ?? null, '#333333');
    $fontColorHover = $component->value($design['submit_font_color_hover'] ?? null, '#000000');
    $fontStyle = $component->value($design['submit_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['submit_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('submit_font_family');
    $cssLine = ".{$cfClass} button[type='submit'] {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} button[type='submit'] {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }
    if (!empty($fontColorHover)) {
      $cssLine = ".{$cfClass} button[type='submit']:hover {
      color: {$fontColorHover};
      }";
      $component->setCss($cssLine);
    }

    // Field Submit Padding
    $paddingT = $component->value($design['submit_padding']['top'] ?? null, '10');
    $paddingTU = $component->cvalue($design['submit_padding']['top_unit'] ?? null, 'px');
    $paddingB = $component->value($design['submit_padding']['bottom'] ?? null, '10');
    $paddingBU = $component->cvalue($design['submit_padding']['bottom_unit'] ?? null, 'px');
    $paddingL = $component->value($design['submit_padding']['left'] ?? null, '10');
    $paddingLU = $component->cvalue($design['submit_padding']['left_unit'] ?? null, 'px');
    $paddingR = $component->value($design['submit_padding']['right'] ?? null, '10');
    $paddingRU = $component->cvalue($design['submit_padding']['right_unit'] ?? null, 'px');
    $cssLine = ".{$cfClass} button[type='submit'] {
      padding-top: {$paddingT}{$paddingTU};
      padding-bottom: {$paddingB}{$paddingBU};
      padding-left: {$paddingL}{$paddingLU};
      padding-right: {$paddingR}{$paddingRU};
    }";
    $component->setCss($cssLine);

    // Field Submit Border
    $border = $component->value($design['submit_border'] ?? null, '1');
    $borderUnit = $component->cvalue($design['submit_border_unit'] ?? null, 'px');
    $borderStyle = $component->value($design['submit_border_style'] ?? null, 'solid');
    $borderColor = $component->value($design['submit_border_color'] ?? null, '#d5d5d5');
    $borderColorHover = $component->value($design['submit_border_color_hover'] ?? null, '#eeeeee');
    if (!empty($border)) {
      $cssLine = ".{$cfClass} button[type='submit'] {
      border: {$border}{$borderUnit} {$borderStyle} {$borderColor};
      }";
      $component->setCss($cssLine);
    }
    if (!empty($borderColorHover)) {
      $cssLine = ".{$cfClass} button[type='submit']:hover {
      border: {$border}{$borderUnit} {$borderStyle} {$borderColorHover};
      }";
      $component->setCss($cssLine);
    }

    // Field Submit Border Radius
    $border = $component->value($design['submit_border_radius'] ?? null, '0');
    $borderUnit = $component->cvalue($design['submit_border_radius_unit'] ?? null, 'px');
    if (!empty($border)) {
      $cssLine = ".{$cfClass} button[type='submit'] {
      border-radius: {$border}{$borderUnit};
      }";
      $component->setCss($cssLine);
    }

    // Field Submit Background
    $background = $component->value($design['submit_background'] ?? null, '#eeeeee');
    if (!empty($background)) {
      $cssLine = ".{$cfClass} button[type='submit'] {
      background: {$background};
      background-color: {$background};
      }";
      $component->setCss($cssLine);
    }

    // Field Submit Hover Background
    $background = $component->value($design['submit_background_hover'] ?? null, '#d5d5d5');
    if (!empty($background)) {
      $cssLine = ".{$cfClass} button[type='submit']:hover {
      background: {$background};
      background-color: {$background};
      }";
      $component->setCss($cssLine);
    }

    // Field Submit Box Shadow
    $boxShadowT = $component->value($design['submit_box_shadow']['top'] ?? null, '0');
    $boxShadowTU = $component->cvalue($design['submit_box_shadow']['top_unit'] ?? null, 'px');
    $boxShadowB = $component->value($design['submit_box_shadow']['bottom'] ?? null, '0');
    $boxShadowBU = $component->cvalue($design['submit_box_shadow']['bottom_unit'] ?? null, 'px');
    $boxShadowL = $component->value($design['submit_box_shadow']['left'] ?? null, '0');
    $boxShadowLU = $component->cvalue($design['submit_box_shadow']['left_unit'] ?? null, 'px');
    $boxShadowR = $component->value($design['submit_box_shadow']['right'] ?? null, '0');
    $boxShadowRU = $component->cvalue($design['submit_box_shadow']['right_unit'] ?? null, 'px');
    $boxShadowColor = $component->value($design['submit_box_shadow_color'] ?? null, '');
    $cssLine = ".{$cfClass} button[type='submit'] {
    box-shadow: {$boxShadowT}{$boxShadowTU} {$boxShadowR}{$boxShadowRU} {$boxShadowL}{$boxShadowLU}  {$boxShadowB}{$boxShadowBU} {$boxShadowColor};
    }";
    $component->setCss($cssLine);

    // Field Submit Text Shadow
    $textShadowT = $component->value($design['submit_text_shadow']['top'] ?? null, '0');
    $textShadowTU = $component->cvalue($design['submit_text_shadow']['top_unit'] ?? null, 'px');
    $textShadowL = $component->value($design['submit_text_shadow']['left'] ?? null, '0');
    $textShadowLU = $component->cvalue($design['submit_text_shadow']['left_unit'] ?? null, 'px');
    $textShadowR = $component->value($design['submit_text_shadow']['right'] ?? null, '0');
    $textShadowRU = $component->cvalue($design['submit_text_shadow']['right_unit'] ?? null, 'px');
    $textShadowColor = $component->value($design['submit_text_shadow_color'] ?? null, '');
    $cssLine = ".{$cfClass} button[type='submit'] {
    text-shadow: {$textShadowT}{$textShadowTU} {$textShadowR}{$textShadowRU} {$textShadowL}{$textShadowLU} {$textShadowColor};
    }";
    $component->setCss($cssLine);

    if (!empty($fields)) {
      foreach ($fields as $field) {
        if (empty($field['settings']['field_type'])) {
          continue;
        }
        if ($field['settings']['field_type'] != 'title' && $field['settings']['field_type'] != 'captcha') {
          continue;
        }
        if ($field['settings']['field_type'] == 'title') {
          $fKey = !empty($field['fkey']) ? $field['fkey'] : '';
          $fKey = empty($fKey) ? $field['settings']['field_key'] : $fKey;
          $textAlign = $component->value($field['settings']['text_align'] ?? null, 'left');
          $cssLine = ".{$cfClass} .f_{$fKey}.ppsgen-form-field-title {
            text-align: {$textAlign};
            }";
          $component->setCss($cssLine);
        }
        if ($field['settings']['field_type'] == 'captcha') {
          $fKey = !empty($field['fkey']) ? $field['fkey'] : '';
          $fKey = !empty($fKey) ? $field['settings']['field_key'] : '';
          $textAlign = $component->value($field['settings']['captcha_align'] ?? null, 'center');
          if ($textAlign == 'left') {
            $textAlign = 'flex-start';
          }
          if ($textAlign == 'right') {
            $textAlign = 'flex-end';
          }
          if ($textAlign == 'justify') {
            $textAlign = 'space-between';
          }
          $cssLine = ".{$cfClass} .f_{$fKey}_wrapper {
            justify-content: {$textAlign};
            }";
          $component->setCss($cssLine);
        }
      }
    }

    // Field Title Style
    $fontSize = $component->value($design['ftitle_font_size'] ?? null, '21');
    $fontSizeUnit = $component->cvalue($design['ftitle_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['ftitle_font_color'] ?? null, '#000000');
    $fontStyle = $component->value($design['ftitle_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['ftitle_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('ftitle_font_family');
    $cssLine = ".{$cfClass} .ppsgen-form-field-title {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    width:100%;
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} .ppsgen-form-field-title {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Radio & Checkbox Text Style
    $fontSize = $component->value($design['radcheck_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['radcheck_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['radcheck_font_color'] ?? null, '#000000');
    $fontStyle = $component->value($design['radcheck_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['radcheck_font_weight'] ?? null, 'normal');
    $fontFamilyCss = $getFontFamilyCss('radcheck_font_family');
    $cssLine = ".{$cfClass} .ppsgen-form-inputcheckboxWrap label span, .{$cfClass} .ppsgen-form-inputradioWrap label span {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$cfClass} .ppsgen-form-inputcheckboxWrap label span, .{$cfClass} .ppsgen-form-inputradioWrap label span {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    $css = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('form.view.fields_styles_css', $options);
    if (!is_array($css)) {
      $component->setCss($css);
    }

    $css = $component->getGoogleFontImportCss(true) . $this->getFrame()->core('helper')->mergeCssRulesBySelector($component->getCss());
    $component->cleanCss();
    return $css;
  }
}
