<?php

if (!defined('ABSPATH')) {
  exit();
}

class PopupViewPpsgen extends BaseViewPpsgen
{
  /** Default step of the popup being rendered: other steps start hidden (the PRO script switches them) */
  public $defaultStepSlug = '';

  protected bool $renderPreview = false;
  public $previewInlineCss = '';

  protected function getPopupCustomCodePromoHtml(string $type): string
  {
    $title = $type === 'css' ? __('Custom CSS for every popup.', 'ppsgen') : __('Custom JS for every popup.', 'ppsgen');
    $desc = $type === 'css'
      ? '.ppsgenPopupOverlay-{{ID}}, .ppsgenPopupWrapper-{{ID}}, .ppsgenPopup-{{ID}}, .ppsgenPopupContent-{{ID}}'
      : "$('.ppsgenPopup-{{ID}}') and jQuery(function($){ ... }) wrapper included automatically.";
    $features = [
      __('Object-level Custom JS', 'ppsgen'),
      __('Advanced popup triggers: inactivity, after checkout, after comment, AdBlock and more', 'ppsgen'),
      __('GeoLite targeting, user-role conditions and premium popup templates', 'ppsgen'),
    ];
    return $this->getProUpsellTabBannerHtml($title, __('Design and automate high-converting popups with full PRO control.', 'ppsgen'), $desc, $features, 'popup_custom_code_pro');
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

  public function renderPopupOverview($params)
  {
    $html = $this->renderTpl('overview', 'popup');
    return $html;
  }

  public function renderPopupList($params)
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
    if (!empty($request->getVar('filter_order_index', 'post'))) {
      $filters['order_index'] = $request->getVar('filter_order_index', 'post');
    }
    if (!empty($request->getVar('filter_created_at', 'post'))) {
      $filters['created_at'] = $request->getVar('filter_created_at', 'post');
    }
    if (!empty($request->getVar('filter_active', 'post'))) {
      $filters['active'] = $request->getVar('filter_active', 'post');
    }
    if (!empty($request->getVar('filter_updated_at', 'post'))) {
      $filters['updated_at'] = $request->getVar('filter_updated_at', 'post');
    }
    $model = $this->getModel();
    $data = $model->getPopups([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $popupIds = array_map(static function ($row) {
      return (int) ($row['id'] ?? 0);
    }, $data['rows']);
    $statsByPopup = $model->getPopupStatsSummaryByIds($popupIds);
    $this->assign([
      'rows' => $data['rows'],
      'statsByPopup' => $statsByPopup,
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $html = $this->renderTpl('list', 'popup');
    return $html;
  }

  public function renderPopupOptions($params)
  {
    $popupOptions = $this->getModel()->getAllOptions();
    $this->assign('popupOptions', $popupOptions);
    $html = $this->renderTpl('options', 'popup');
    return $html;
  }

  public function renderPopupEdit($params)
  {
    $request = $this->getFrame()->core('request');
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    if (!empty($objId)) {
      $popupOptions = $this->getModel()->getPopupById($objId);
      if (!empty($popupOptions)) {
        $popupOptions['settings'] = $this->getModel()->_prepareValueFromStore($popupOptions['settings'], 'json');
        $popupOptions['design'] = $this->getModel()->_prepareValueFromStore($popupOptions['design'], 'json');
        $fields = $this->getModel()->getPopupFields($objId);
        $fieldsByKey = [];
        if (!empty($fields)) {
          foreach ($fields as $key => $field) {
            $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
          }
        }
        $popupOptions['fields'] = $fields;
        $options = $popupOptions;
        wp_localize_script('ppsgen-backend-popup-edit', 'ppsgenPopupData', [
          'fields' => $fieldsByKey,
        ]);
        wp_enqueue_script('ppsgen-backend-popup-edit');
      }
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Popup with specific ID for editing', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $tabs = [
      'template' => ['title' => __('Content', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('popup.edit.template_html', $options)],
      'design' => ['title' => __('Design', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('popup.edit.design_html', $options)],
      'options' => ['title' => __('When & where', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('popup.edit.options_html', $options)],
      'stats' => ['title' => __('Stats', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('popup.edit.stats_html', $options)],
      'custom_css' => ['title' => __('Custom CSS', 'ppsgen'), 'content' => $this->renderTpl('edit_popup_custom_css', 'popup')],
      'custom_js_pro' => ['title' => __('Custom JS (PRO)', 'ppsgen'), 'content' => $this->getPopupCustomCodePromoHtml('js')],
    ];
    $tabs = $this->getFrame()
      ->core('eventDispatcher')
      ->applyFiltersOnce('popup.edit.tabs', ['tabs' => $tabs, 'options' => $options]);
    $this->assign('tabs', $tabs['tabs']);
    $activeTab = $request->getVar('obj_edit_active_tab', 'post');
    if (empty($activeTab)) {
      $activeTab = $request->getVar('obj_edit_active_tab', 'get');
    }
    $this->assign('activeTab', !empty($activeTab) ? $activeTab : 'template');
    $html = $this->renderTpl('edit', 'popup');
    return $html;
  }

  public function renderPopupEditStats($params)
  {
    $request = $this->getFrame()->core('request');
    $objId = !empty($params['id']) ? $params['id'] : false;
    $popupSubmits = !empty($objId) ? $this->getModel()->getPopupSubmits($objId) : '';
    $groupBy = $request->getVar('popup_stats_group', 'get');
    $range = (int) $request->getVar('popup_stats_range', 'get');
    $groupBy = in_array($groupBy, ['day', 'week', 'month'], true) ? $groupBy : 'day';
    $range = in_array($range, [7, 30, 90, 365], true) ? $range : 30;
    $trend = !empty($objId) ? $this->getModel()->getPopupTrend((int) $objId, $groupBy, $range) : [];
    wp_register_style('ppsgen-backend-popup-submits-edit', PPSGEN_PLUGIN_URL . 'modules/popup/assets/css/backend.submits_edit.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-backend-popup-submits-edit');
    wp_register_script('ppsgen-backend-popup-submits-edit', PPSGEN_PLUGIN_URL . 'modules/popup/assets/js/backend.submits_edit.js', [], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-popup-submits-edit');
    $this->assign('popupSubmits', $popupSubmits);
    $this->assign('popupTrend', $trend);
    $this->assign('popupTrendGroup', $groupBy);
    $this->assign('popupTrendRange', $range);
    $html = $this->renderTpl('edit_stats', 'popup');
    return $html;
  }

  public function renderPopupEditOptions($params)
  {
    $html = $this->renderTpl('edit_options', 'popup');
    return $html;
  }

  public function renderPopupEditTemplate($params)
  {
    $html = $this->renderTpl('edit_template', 'popup');
    return $html;
  }

  public function renderPopupEditDesign($params)
  {
    $html = $this->renderTpl('edit_design', 'popup');
    return $html;
  }

  public function renderPreview($params)
  {
    $popup = $params;
    if (!empty($popup)) {
      $popup['module_options'] = $this->getModel()->getAllOptions();
      $popup['settings'] = $this->getModel()->_prepareValueFromStore($popup['settings'], 'json');
      $popup['design'] = $this->getModel()->_prepareValueFromStore($popup['design'], 'json');
      $fields = $popup['fields'];
      $forms = [];
      if (!empty($fields)) {
        foreach ($fields as $key => $field) {
          $fields[$key]['settings'] = $fields[$key];
          if ($field['field_type'] == 'form') {
            if (!empty($field['form_id'])) {
              $forms[] = $field['form_id'];
            }
          }
        }
        usort($fields, static function ($a, $b) {
          return (int) ($a['order_index'] ?? 0) <=> (int) ($b['order_index'] ?? 0);
        });
        $popup['fields'] = $fields;
      } else {
        $popup['fields'] = [];
      }
      $options = $popup;
    }
    $options['obj_id'] = '999';
    if (empty($options)) {
      return esc_html__('Cant find Options', 'ppsgen');
    }
    if (empty($popup['fields'])) {
      return esc_html__('Popup is empty', 'ppsgen');
    }
    $this->assign('options', $options);
    $this->previewInlineCss = '';
    $this->renderPreview = true;
    $html = $this->renderTpl('view', 'popup');
    $helper = $this->getFrame()->core('helper');
    $css = $helper->minifyCss($this->buildPopupCss($options));
    $css .= 'div.ppsgenPopupOverlay-999 {display:flex !important;} div.ppsgenPopupWrapper-999 {display:flex !important;}';
    $css .= $helper->minifyCss($helper->mergeCssRulesBySelector($helper->prepareFrontendCustomCss($options['settings']['custom_css'] ?? '', $options['obj_id'])));
    $css .= $this->previewInlineCss;

    // Versioned by file time so the preview never shows a stale cached stylesheet
    $ver = static fn(string $rel): string => PPSGEN_PLUGIN_URL . $rel . '?ver=' . (int) @filemtime(PPSGEN_PLUGIN_DIR . $rel);
    $styles = [$ver('public/assets/libs/ppsgenfa/ppsgen-fa-all.min.css'), $ver('public/assets/css/ppsgen.frontend.css'), $ver('public/assets/libs/ppsgenselect/ppsgenselect.css'), $ver('public/assets/css/ppsgen.frontend.popups.css')];
    $scripts = [includes_url('js/jquery/jquery.min.js'), PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenselect/ppsgenselect.js', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.js', $ver('public/assets/js/ppsgen.frontend.popups.js')];
    $additionalScripts = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('popup.preview.load.scripts', []) ?? [];
    $additionalInlineScripts = '';
    $additionalInlineScripts .= $helper->preparePreviewCustomJs($options['settings']['custom_js'] ?? '', $options['obj_id']);
    // The preview has no popup instance for the PRO step handler to bind to, so "Go to step" buttons get the
    // same behaviour here: hide every step, show the target one
    $additionalInlineScripts .= "<script>document.addEventListener('click', function (e) {\n"
      . "  var b = e.target.closest ? e.target.closest('[data-type=\"to_step\"]') : null;\n"
      . "  var p = b ? b.closest('.ppsgenPopup') : null;\n"
      . "  var slug = b ? b.getAttribute('data-to-step') : '';\n"
      . "  if (!p || !slug) { return; }\n"
      . "  e.preventDefault();\n"
      . "  p.querySelectorAll('[data-step-slug]').forEach(function (s) { s.classList.toggle('ppsgen-step-hidden', s.getAttribute('data-step-slug') !== slug); });\n"
      . "});</script>";
    $scripts = array_merge($scripts, $additionalScripts);

    if (!empty($forms)) {
      $stylesForms = [$ver('public/assets/libs/ppsgenfa/ppsgen-fa-all.min.css'), $ver('public/assets/css/ppsgen.frontend.css'), $ver('public/assets/libs/ppsgenselect/ppsgenselect.css'), $ver('public/assets/css/ppsgen.frontend.forms.css')];
      $scriptsForms = [includes_url('js/jquery/jquery.min.js'), PPSGEN_PLUGIN_URL . 'public/assets/libs/ppsgenselect/ppsgenselect.js', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.js', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.forms.js'];
      $styles = array_merge($styles, $stylesForms);
      $scripts = array_merge($scripts, $scriptsForms);
    }

    $forms = array_values(array_unique(array_filter(array_map('intval', $forms))));
    foreach ($forms as $form_id) {
      $formExists = $this->getFrame()->getModule('form')->getModel()->getFormById($form_id);
      if (empty($formExists)) {
        continue;
      }
      $formData = $this->getFrame()->getModule('form')->getView()->renderPreview([], $form_id);
      if (empty($formData) || !is_array($formData)) {
        continue;
      }
      $css .= $formData['css'];
      $additionalInlineScripts .= $formData['inline_scripts'];
      $styles = array_merge($styles, $formData['styles']);
      $scripts = array_merge($scripts, $formData['scripts']);
    }

    $css = $helper->moveCssImportsToTop($css);
    $this->renderPreview = false;
    // Popup and form assets overlap; loading jQuery again would drop what the earlier scripts attached to it
    $styles = array_values(array_unique($styles));
    $scripts = array_values(array_unique($scripts));
    return $data = ['html' => $html, 'css' => $css, 'styles' => $styles, 'scripts' => $scripts, 'inline_scripts' => $additionalInlineScripts];
  }

  public function renderPopupView($params)
  {
    static $renderedIds = [];
    $objId = !empty($params['id']) ? $params['id'] : false;
    if (in_array($objId, $renderedIds)) {
      return '';
    }
    $renderedIds[] = $objId;
    $options = [];
    if (!empty($objId)) {
      $popup = $this->getModel()->getPopupById($objId);
      if (!empty($popup)) {
        $embed = !empty($params['embed']) ? true : false;
        $this->assign('embed', $embed);
        $hideoverlay = !empty($params['hideoverlay']) ? true : false;
        $this->assign('hideoverlay', $hideoverlay);
        $forceshow = !empty($params['forceshow']) ? true : false;
        $this->assign('forceshow', $forceshow);
        $popup['module_options'] = $this->getModel()->getAllOptions();
        $popup['settings'] = $this->getModel()->_prepareValueFromStore($popup['settings'], 'json');
        $popup['design'] = $this->getModel()->_prepareValueFromStore($popup['design'], 'json');
        if (!empty($popup['design']['show_overlay'])) {
          $popup['settings']['show_overlay'] = $popup['design']['show_overlay'];
        }
        if (!empty($popup['design']['popup_anim_show'])) {
          $popup['settings']['popup_anim_show'] = $popup['design']['popup_anim_show'];
        }
        if (!empty($popup['design']['popup_anim_hide'])) {
          $popup['settings']['popup_anim_hide'] = $popup['design']['popup_anim_hide'];
        }
        $popupIsEnabled = $this->getModel()->popupIsEnabled($objId);
        if (empty($popupIsEnabled)) {
          return '';
        }
        if (!$forceshow) {
          if (!$this->canShowPopup($popup)) {
            return '';
          }
        }
        $fields = $this->getModel()->getPopupFields($objId);
        if (!empty($fields)) {
          $popup['fields'] = $fields;
        } else {
          $popup['fields'] = [];
        }
        $options = $popup;
      }
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Popup with specific ID', 'ppsgen');
    }
    if (empty($popup['fields'])) {
      return esc_html__('Popup is empty', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $html = $this->renderTpl('view', 'popup');
    $helper = $this->getFrame()->core('helper');
    $css = $helper->minifyCss($this->buildPopupCss($options));
    $css .= $helper->minifyCss($helper->mergeCssRulesBySelector($helper->prepareFrontendCustomCss($options['settings']['custom_css'] ?? '', $objId)));
    $this->loadFrontendPopupScripts();
    $popupStyleTitle = 'ppsgen-frontend-popups-' . $objId;
    wp_register_style($popupStyleTitle, false, [], PPSGEN_VERSION);
    wp_enqueue_style($popupStyleTitle);
    wp_add_inline_style($popupStyleTitle, $css);
    wp_localize_script('ppsgen-frontend-popups', 'ppsgenFrontendPopup' . $objId, [
      'nonce_code' => wp_create_nonce('ppsgenPopupSubmit' . $objId),
      'popup_id' => $objId,
    ]);
    $inlineScript = sprintf('var ppsgenPopupSettings_%d = %s;', $objId, json_encode($popup['settings']));
    wp_add_inline_script('ppsgen-frontend-popups', $inlineScript);
    $customJs = $helper->prepareFrontendCustomJs($options['settings']['custom_js'] ?? '', $objId);
    if ($customJs !== '') {
      wp_add_inline_script('ppsgen-frontend-popups', $customJs);
    }
    $this->getFrame()->core('component')->loadAnimate();
    $this->getFrame()->core('component')->loadFA();
    return $html;
  }

  public function canShowPopup($popup)
  {
    if (empty($popup['active'])) {
      return false;
    }
    $res = true;
    $settings = $popup['settings'] ?? [];
    // $isAdmin = is_admin();
    // if ($isAdmin && empty($settings['show_in_admin_area'])) {
    //     return false;
    // }
    // Check pages
    global $post;
    if (!empty($settings['show_pages']) && $settings['show_pages'] !== 'all' && $post) {
      if (in_array($settings['show_pages'], ['show_on_pages', 'not_show_on_pages'])) {
        $pagesList = !empty($settings['show_pages_list']) ? $settings['show_pages_list'] : [];
        $match = in_array($post->ID, $pagesList);
        if ($settings['show_pages'] === 'show_on_pages' && !$match) {
          $res = false;
        }
        if ($settings['show_pages'] === 'not_show_on_pages' && $match) {
          $res = false;
        }
      }
    }
    // Check categories
    if (!empty($settings['show_pages']) && in_array($settings['show_pages'], ['show_on_categories', 'not_show_on_categories']) && $post) {
      $postCats = wp_get_post_categories($post->ID);
      $catsList = !empty($settings['show_categories_list']) ? array_map('intval', explode(',', $settings['show_categories_list'])) : [];
      $intersect = array_intersect($postCats, $catsList);
      if ($settings['show_pages'] === 'show_on_categories' && empty($intersect)) {
        $res = false;
      }
      if ($settings['show_pages'] === 'not_show_on_categories' && !empty($intersect)) {
        $res = false;
      }
    }
    // Check post types
    if (!empty($settings['hide_for_post_types_action']) && !empty($settings['hide_for_post_types']) && $post) {
      $action = $settings['hide_for_post_types_action'];
      $types = $settings['hide_for_post_types'];
      $postType = $post->post_type;
      $match = in_array($postType, $types);
      if ($action === 'show' && !$match) {
        $res = false;
      }
      if ($action === 'hide' && $match) {
        $res = false;
      }
    }
    // Check IPs
    $userIp = $this->getFrame()->core('request')->getServerVar('REMOTE_ADDR');
    if (!empty($settings['hide_for_ips_action']) && !empty($settings['hide_for_ips'])) {
      $action = $settings['hide_for_ips_action'];
      $ips = array_map('trim', explode(',', $settings['hide_for_ips']));
      $match = in_array($userIp, $ips);
      if ($action === 'show' && !$match) {
        $res = false;
      }
      if ($action === 'hide' && $match) {
        $res = false;
      }
    }
    // Check languages
    $acceptLang = $this->getFrame()->core('request')->getServerVar('HTTP_ACCEPT_LANGUAGE');
    $userLangs = array_map('trim', explode(',', $acceptLang));
    $primaryLang = !empty($userLangs[0]) ? substr($userLangs[0], 0, 2) : '';
    if (!empty($settings['hide_for_languages_action']) && !empty($settings['hide_for_languages'])) {
      $action = $settings['hide_for_languages_action'];
      $langs = $settings['hide_for_languages'];
      $match = in_array($primaryLang, $langs);
      if ($action === 'show' && !$match) {
        $res = false;
      }
      if ($action === 'hide' && $match) {
        $res = false;
      }
    }
    // Check user roles
    if (!empty($settings['hide_for_user_roles_action']) && !empty($settings['hide_for_user_roles'])) {
      $match = !empty(array_intersect((array) $settings['hide_for_user_roles'], (array) wp_get_current_user()->roles));
      if (($settings['hide_for_user_roles_action'] === 'show' && !$match) || ($settings['hide_for_user_roles_action'] === 'hide' && $match)) {
        $res = false;
      }
    }
    // Check URL match
    if (!empty($settings['hide_preg_url_action']) && !empty($settings['hide_preg_url'])) {
      $match = $this->urlMatchesPatterns((string) $this->getFrame()->core('request')->getServerVar('REQUEST_URI'), (string) $settings['hide_preg_url']);
      if (($settings['hide_preg_url_action'] === 'show' && !$match) || ($settings['hide_preg_url_action'] === 'hide' && $match)) {
        $res = false;
      }
    }
    // Check search engines / social media the visitor came from
    if (!empty($settings['hide_for_search_engines_action']) && !empty($settings['hide_for_search_engines'])) {
      $referer = (string) $this->getFrame()->core('request')->getServerVar('HTTP_REFERER');
      $match = false;
      foreach ((array) $settings['hide_for_search_engines'] as $engine) {
        if ($referer !== '' && stripos($referer, (string) $engine) !== false) {
          $match = true;
          break;
        }
      }
      if (($settings['hide_for_search_engines_action'] === 'show' && !$match) || ($settings['hide_for_search_engines_action'] === 'hide' && $match)) {
        $res = false;
      }
    }
    // Who sees it: known contacts, new visitors, a list or a tag
    if (function_exists('ppsgen_visitor_matches_audience') && !ppsgen_visitor_matches_audience($settings)) {
      $res = false;
    }
    // Check logged in
    $isLoggedIn = is_user_logged_in();
    if (!empty($settings['hide_for_logged_in']) && $isLoggedIn) {
      $res = false;
    }
    if (!empty($settings['show_for_logged_in']) && !$isLoggedIn) {
      $res = false;
    }
    // Check date
    $now = current_time('timestamp');
    if (!empty($settings['show_date'])) {
      $from = strtotime($settings['show_date_from'] ?? '1970-01-01');
      $to = strtotime($settings['show_date_to'] ?? '2038-01-01');
      if ($now < $from || $now > $to) {
        $res = false;
      }
    }
    // Check time
    if (!empty($settings['show_time'])) {
      $fromH = (int) ($settings['show_time_from_hours'] ?? 0);
      $fromM = (int) ($settings['show_time_from_minutes'] ?? 0);
      $toH = (int) ($settings['show_time_to_hours'] ?? 23);
      $toM = (int) ($settings['show_time_to_minutes'] ?? 59);

      $timestamp = current_time('timestamp');
      $currentH = (int) gmdate('H', $timestamp);
      $currentM = (int) gmdate('i', $timestamp);

      $fromTime = $fromH * 60 + $fromM;
      $toTime = $toH * 60 + $toM;
      $currentTime = $currentH * 60 + $currentM;

      if ($currentTime < $fromTime || $currentTime > $toTime) {
        $res = false;
      }
    }
    // Check days
    if (!empty($settings['show_days'])) {
      $timestamp = current_time('timestamp');
      $currentDay = strtolower(gmdate('l', $timestamp));
      if (!in_array($currentDay, $settings['show_days'])) {
        return false;
      }
    }

    if ($res) {
      $popup['res'] = $res;
      $res = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('popup.view.canShowPopup', $popup);
    }

    return $res;
  }

  // One pattern per line or comma. A valid regular expression ("#^/blog/#") is used as is; otherwise
  // "*" is a wildcard ("/pricing/*") and a pattern without "*" matches any URL that contains it.
  public function urlMatchesPatterns(string $uri, string $patterns): bool
  {
    $path = (string) wp_parse_url($uri, PHP_URL_PATH);
    foreach (preg_split('/[
,]+/', $patterns) as $pattern) {
      $pattern = trim($pattern);
      if ($pattern === '') {
        continue;
      }
      if (@preg_match($pattern, '') !== false) {
        if (preg_match($pattern, $uri)) {
          return true;
        }
        continue;
      }
      if (strpos($pattern, '*') === false) {
        if (stripos($uri, $pattern) !== false) {
          return true;
        }
        continue;
      }
      $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#i';
      if (preg_match($regex, $uri) || preg_match($regex, $path)) {
        return true;
      }
    }
    return false;
  }

  public function loadFrontendPopupScripts()
  {
    wp_register_style('ppsgen-frontend-popups', PPSGEN_PLUGIN_URL . 'public/assets/css/ppsgen.frontend.popups.css', [], PPSGEN_VERSION . '.' . (int) @filemtime(PPSGEN_PLUGIN_DIR . 'public/assets/css/ppsgen.frontend.popups.css'));
    wp_enqueue_style('ppsgen-frontend-popups');
    wp_register_script('ppsgen-frontend-popups', PPSGEN_PLUGIN_URL . 'public/assets/js/ppsgen.frontend.popups.js', ['jquery'], PPSGEN_VERSION . '.' . (int) @filemtime(PPSGEN_PLUGIN_DIR . 'public/assets/js/ppsgen.frontend.popups.js'), ['in_footer' => true]);
    wp_enqueue_script('ppsgen-frontend-popups');
  }

  public function buildPopupCss($options)
  {
    $design = !empty($options['design']) ? $options['design'] : '';
    $fields = !empty($options['fields']) ? $options['fields'] : '';
    $objId = !empty($options['obj_id']) ? $options['obj_id'] : '';
    $overlayClass = 'ppsgenPopupOverlay-' . $objId;
    $popupClass = 'ppsgenPopup-' . $objId;
    $component = $this->getComponent();

    // Padding
    $paddingT = $component->value($design['popup_padding']['top'] ?? null, '10');
    $paddingTU = $component->cvalue($design['popup_padding']['top_unit'] ?? null, 'px');
    $paddingB = $component->value($design['popup_padding']['bottom'] ?? null, '10');
    $paddingBU = $component->cvalue($design['popup_padding']['bottom_unit'] ?? null, 'px');
    $paddingL = $component->value($design['popup_padding']['left'] ?? null, '10');
    $paddingLU = $component->cvalue($design['popup_padding']['left_unit'] ?? null, 'px');
    $paddingR = $component->value($design['popup_padding']['right'] ?? null, '10');
    $paddingRU = $component->cvalue($design['popup_padding']['right_unit'] ?? null, 'px');
    $cssLine = ".{$popupClass} {
     padding-top: {$paddingT}{$paddingTU};
     padding-bottom: {$paddingB}{$paddingBU};
     padding-left: {$paddingL}{$paddingLU};
     padding-right: {$paddingR}{$paddingRU};
    }";
    $component->setCss($cssLine);

    // Popup Width
    $widthSelect = $component->value($design['popup_width_select'] ?? null, 'manual');
    $width = $component->value($design['popup_width'] ?? null, '100');
    $widthUnit = $component->cvalue($design['popup_width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssLine = ".{$popupClass} {
      width: {$width}{$widthUnit};
      }";
    } elseif ($widthSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$popupClass} {
      width: {$widthSelect};
      }";
    }
    $component->setCss($cssLine);

    // Popup Max Width
    $widthSelect = $component->value($design['popup_max_width_select'] ?? null, 'auto');
    $width = $component->value($design['popup_max_width'] ?? null, '100');
    $widthUnit = $component->cvalue($design['popup_max_width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssLine = ".{$popupClass} {
      max-width: {$width}{$widthUnit};
      }";
    } elseif ($widthSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$popupClass} {
      max-width: {$widthSelect};
      }";
    }
    $component->setCss($cssLine);

    // Popup Height
    $heightSelect = $component->value($design['popup_height_select'] ?? null, 'auto');
    $height = $component->value($design['popup_height'] ?? null, '100');
    $heightUnit = $component->cvalue($design['popup_height_unit'] ?? null, '%');
    if ($heightSelect == 'manual') {
      $cssLine = ".{$popupClass} {
      height: {$height}{$heightUnit};
      }";
    } elseif ($heightSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$popupClass} {
      height: {$heightSelect};
      }";
    }
    $component->setCss($cssLine);

    // Popup Max Height
    $heightSelect = $component->value($design['popup_max_height_select'] ?? null, 'auto');
    $height = $component->value($design['popup_max_height'] ?? null, '100');
    $heightUnit = $component->cvalue($design['popup_max_height_unit'] ?? null, '%');
    if ($heightSelect == 'manual') {
      $cssLine = ".{$popupClass} {
      max-height: {$height}{$heightUnit};
      }";
    } elseif ($heightSelect == 'auto') {
      $cssLine = '';
    } else {
      $cssLine = ".{$popupClass} {
      max-height: {$heightSelect};
      }";
    }
    $component->setCss($cssLine);
    // The popup is content-box: its padding comes on top of the max height. The scroll box takes the max
    // height minus that padding, so on a short screen the last field and the submit button stay reachable
    $boxMaxHeight = $heightSelect == 'manual' ? $height . $heightUnit : '90vh';
    $component->setCss(".{$popupClass} .ppsgenPopupScrollable {
      max-height: calc({$boxMaxHeight} - {$paddingT}{$paddingTU} - {$paddingB}{$paddingBU});
      }");

    // Popup Border
    $border = $component->value($design['popup_border'] ?? null, '');
    $borderUnit = $component->cvalue($design['popup_border_unit'] ?? null, 'px');
    $borderStyle = $component->value($design['popup_border_style'] ?? null, 'solid');
    $borderColor = $component->value($design['popup_border_color'] ?? null, '#dddddd');
    if (!empty($border)) {
      $cssLine = ".{$popupClass} {
      border: {$border}{$borderUnit} {$borderStyle} {$borderColor};
      }";
      $component->setCss($cssLine);
    }

    // Popup Border Radius
    $border = $component->value($design['popup_border_radius'] ?? null, '');
    $borderUnit = $component->cvalue($design['popup_border_radius_unit'] ?? null, 'px');
    if (!empty($border)) {
      $cssLine = ".{$popupClass} {
      border-radius: {$border}{$borderUnit};
      }";
      $component->setCss($cssLine);
    }

    // Popup Scrollbar Style
    $scrollableClass = 'ppsgenPopupScrollable-' . $objId;
    $scrollbarSize = $component->value($design['popup_scrollbar_size'] ?? null, '');
    $scrollbarSizeMap = [
      'xs' => '4px',
      's' => '6px',
      'm' => '8px',
      'l' => '10px',
      'xl' => '14px',
    ];
    if (isset($scrollbarSizeMap[$scrollbarSize])) {
      $scrollbarSizeValue = $scrollbarSizeMap[$scrollbarSize];
      $scrollbarStyle = $component->value($design['popup_scrollbar_style'] ?? null, 'rounded');
      $scrollbarRadiusMap = [
        'square' => '0',
        'rounded' => '6px',
        'pill' => '999px',
      ];
      $scrollbarRadius = $scrollbarRadiusMap[$scrollbarStyle] ?? $scrollbarRadiusMap['rounded'];
      $trackColor = esc_attr($component->value($design['popup_scrollbar_track_color'] ?? null, 'rgba(0,0,0,0.08)'));
      $thumbColor = esc_attr($component->value($design['popup_scrollbar_thumb_color'] ?? null, '#7c8aa5'));
      $thumbHoverColor = esc_attr($component->value($design['popup_scrollbar_thumb_hover_color'] ?? null, '#4f5f7f'));
      $buttonColor = esc_attr($component->value($design['popup_scrollbar_button_color'] ?? null, 'rgba(0,0,0,0.12)'));
      $buttonHoverColor = esc_attr($component->value($design['popup_scrollbar_button_hover_color'] ?? null, 'rgba(0,0,0,0.22)'));
      $cornerColor = esc_attr($component->value($design['popup_scrollbar_corner_color'] ?? null, 'rgba(0,0,0,0)'));
      $showButtons = $component->value($design['popup_scrollbar_buttons'] ?? null, '1') === '1';
      $buttonDisplay = $showButtons ? 'block' : 'none';
      $firefoxScrollbarWidth = in_array($scrollbarSize, ['xs', 's', 'm'], true) ? 'thin' : 'auto';
      $cssLine = ".{$scrollableClass} {
        scrollbar-color: {$thumbColor} {$trackColor};
        scrollbar-width: {$firefoxScrollbarWidth};
      }
      .{$scrollableClass}::-webkit-scrollbar {
        width: {$scrollbarSizeValue};
        height: {$scrollbarSizeValue};
      }
      .{$scrollableClass}::-webkit-scrollbar-track {
        background: {$trackColor};
        border-radius: {$scrollbarRadius};
      }
      .{$scrollableClass}::-webkit-scrollbar-thumb {
        background: {$thumbColor};
        border-radius: {$scrollbarRadius};
      }
      .{$scrollableClass}::-webkit-scrollbar-thumb:hover {
        background: {$thumbHoverColor};
      }
      .{$scrollableClass}::-webkit-scrollbar-button {
        display: {$buttonDisplay};
        width: {$scrollbarSizeValue};
        height: {$scrollbarSizeValue};
        background: {$buttonColor};
      }
      .{$scrollableClass}::-webkit-scrollbar-button:hover {
        background: {$buttonHoverColor};
      }
      .{$scrollableClass}::-webkit-scrollbar-corner {
        background: {$cornerColor};
      }";
      $component->setCss($cssLine);
    }

    // Popup Background
    $bgType = $component->value($design['popup_bg_type'] ?? null, 'color');
    if ($bgType === 'color') {
      $color = esc_attr($component->value($design['popup_background'] ?? null, '#ffffff'));
      $cssLine = ".{$popupClass} {
            background-color: {$color};
        }";
      $component->setCss($cssLine);
    }
    if ($bgType === 'image') {
      $url = esc_url($component->value($design['popup_bg_image'] ?? null, ''));
      $size = esc_attr($component->value($design['popup_bg_size'] ?? null, 'cover'));
      $repeat = esc_attr($component->value($design['popup_bg_repeat'] ?? null, 'no-repeat'));
      $position = esc_attr($component->value($design['popup_bg_position'] ?? null, 'center center'));
      $overlay = $component->value($design['popup_bg_img_color'] ?? null, 'rgba(255,255,255,0)');
      $opacity = $component->value($design['popup_bg_opacity'] ?? null, '100');
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
        $cssLine = ".{$popupClass} {
            }
            .{$popupClass}::before {
                content:'';
                position:absolute;
                inset:0;
                background-image:url('{$url}');
                background-size:{$size};
                background-repeat:{$repeat};
                background-position:{$position};
                opacity:{$opacity};
                background-color:{$overlay};
                background-blend-mode:multiply;
                border-radius: {$border}{$borderUnit};
                pointer-events:none;
                z-index:0;
            }
            .{$popupClass} > * {
                z-index:1;
            }";
        $component->setCss($cssLine);
      }
    }
    // Popup Backdrop Filter
    $bgFilter = $component->value($design['popup_bg_filter'] ?? null, 'blur');
    $bgFilterSize = $component->value($design['popup_bg_filter_size'] ?? null, '0');
    if (!empty($bgFilterSize)) {
      if ($bgFilter == 'blur') {
        $cssLine = ".{$popupClass} {
                backdrop-filter: {$bgFilter}({$bgFilterSize}px);
            }";
      } else {
        $cssLine = ".{$popupClass} {
                backdrop-filter: {$bgFilter}({$bgFilterSize}%);
            }";
      }
      $component->setCss($cssLine);
    }

    // Popup Overlay
    $bgType = $component->value($design['overlay_bg_type'] ?? null, 'color');
    if ($bgType === 'color') {
      $color = esc_attr($component->value($design['popup_background_overlay'] ?? null, 'rgba(0,0,0,0.5)'));
      $cssLine = ".{$overlayClass} {
            background: {$color};
        }";
      $component->setCss($cssLine);
    }
    if ($bgType === 'image') {
      $url = esc_url($component->value($design['overlay_bg_image'] ?? null, ''));
      $size = esc_attr($component->value($design['overlay_bg_size'] ?? null, 'cover'));
      $repeat = esc_attr($component->value($design['overlay_bg_repeat'] ?? null, 'no-repeat'));
      $position = esc_attr($component->value($design['overlay_bg_position'] ?? null, 'center center'));
      $overlay = $component->value($design['overlay_bg_img_color'] ?? null, 'rgba(255,255,255,0)');
      $opacity = $component->value($design['overlay_bg_opacity'] ?? null, '100');
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
        $cssLine = ".{$overlayClass}::before {
                content:'';
                position:absolute;
                inset:0;
                background-image: url('{$url}');
                background-size: {$size};
                background-repeat: {$repeat};
                background-position: {$position};
                background-color:{$overlay};
                background-blend-mode:multiply;
                opacity: {$opacity};
                z-index:0;
            }";
        $component->setCss($cssLine);
      }
    }
    // Overlay Backdrop Filter
    $bgFilter = $component->value($design['overlay_bg_filter'] ?? null, 'blur');
    $bgFilterSize = $component->value($design['overlay_bg_filter_size'] ?? null, '0');
    if (!empty($bgFilterSize)) {
      if ($bgFilter == 'blur') {
        $cssLine = ".{$overlayClass} {
                backdrop-filter: {$bgFilter}({$bgFilterSize}px);
            }";
      } else {
        $cssLine = ".{$overlayClass} {
                backdrop-filter: {$bgFilter}({$bgFilterSize}%);
            }";
      }
      $component->setCss($cssLine);
    }

    // Popup Box Shadow
    $boxShadowT = $component->value($design['popup_box_shadow']['top'] ?? null, '0');
    $boxShadowTU = $component->cvalue($design['popup_box_shadow']['top_unit'] ?? null, 'px');
    $boxShadowB = $component->value($design['popup_box_shadow']['bottom'] ?? null, '0');
    $boxShadowBU = $component->cvalue($design['popup_box_shadow']['bottom_unit'] ?? null, 'px');
    $boxShadowL = $component->value($design['popup_box_shadow']['left'] ?? null, '0');
    $boxShadowLU = $component->cvalue($design['popup_box_shadow']['left_unit'] ?? null, 'px');
    $boxShadowR = $component->value($design['popup_box_shadow']['right'] ?? null, '0');
    $boxShadowRU = $component->cvalue($design['popup_box_shadow']['right_unit'] ?? null, 'px');
    $boxShadowColor = $component->value($design['popup_box_shadow_color'] ?? null, '');
    $cssLine = ".{$popupClass} {
    box-shadow: {$boxShadowT}{$boxShadowTU} {$boxShadowR}{$boxShadowRU} {$boxShadowL}{$boxShadowLU}  {$boxShadowB}{$boxShadowBU} {$boxShadowColor};
    }";
    $component->setCss($cssLine);

    // Popup Text Shadow
    $textShadowT = $component->value($design['popup_text_shadow']['top'] ?? null, '0');
    $textShadowTU = $component->cvalue($design['popup_text_shadow']['top_unit'] ?? null, 'px');
    $textShadowL = $component->value($design['popup_text_shadow']['left'] ?? null, '0');
    $textShadowLU = $component->cvalue($design['popup_text_shadow']['left_unit'] ?? null, 'px');
    $textShadowR = $component->value($design['popup_text_shadow']['right'] ?? null, '0');
    $textShadowRU = $component->cvalue($design['popup_text_shadow']['right_unit'] ?? null, 'px');
    $textShadowColor = $component->value($design['popup_text_shadow_color'] ?? null, '');
    $cssLine = ".{$popupClass} * {
    text-shadow: {$textShadowT}{$textShadowTU} {$textShadowR}{$textShadowRU} {$textShadowL}{$textShadowLU} {$textShadowColor};
    }";
    $component->setCss($cssLine);

    // Popup Font Style
    $fontFamily = $component->value($design['popup_font_family'] ?? null, 'Default');
    $fontFamilyCss = $component->getFontFamilyCss($fontFamily);
    $fontSize = $component->value($design['popup_font_size'] ?? null, '14');
    $fontSizeUnit = $component->cvalue($design['popup_font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($design['popup_font_color'] ?? null, '#000000');
    $fontStyle = $component->value($design['popup_font_style'] ?? null, 'normal');
    $fontWeight = $component->value($design['popup_font_weight'] ?? null, 'normal');
    $cssLine = ".{$popupClass} {
    {$fontFamilyCss}
    font-size: {$fontSize}{$fontSizeUnit};
    font-weight: {$fontWeight};
    font-style: {$fontStyle};
    }";
    $component->setCss($cssLine);
    if (!empty($fontColor)) {
      $cssLine = ".{$popupClass} {
      color: {$fontColor};
      }";
      $component->setCss($cssLine);
    }

    // Popup Close Style
    $closeStyle = $component->value($design['popup_close_style'] ?? null, 'square');
    $closeIconSize = $component->value($design['popup_close_icon_size'] ?? null, '14');
    $closeBgSize = $component->value($design['popup_close_bg_size'] ?? null, '30');
    $closeTop = $component->value($design['popup_close_position_top'] ?? null, '-12');
    $closeRight = $component->value($design['popup_close_position_right'] ?? null, '-12');
    $closeIconColor = $component->value($design['popup_close_icon_color'] ?? null, '#ffffff');
    $closeIconColorHover = $component->value($design['popup_close_icon_color_hover'] ?? null, '#000000');
    $closeBgColor = $component->value($design['popup_close_bg_color'] ?? null, '#000000');
    $closeBgColorHover = $component->value($design['popup_close_bg_color_hover'] ?? null, '#ffffff');
    $closeBorder = $component->value($design['popup_close_border'] ?? null, '1');
    $closeBorderUnit = $component->value($design['popup_close_border_unit'] ?? null, 'px');
    $closeBorderStyle = $component->value($design['popup_close_border_style'] ?? null, 'solid');
    $closeBorderColor = $component->value($design['popup_close_border_color'] ?? null, '#000000');
    $closeBorderColorHover = $component->value($design['popup_close_border_color_hover'] ?? null, '#000000');

    // The offsets also go into variables: on phones ppsgen.frontend.popups.css keeps a close button that sits
    // outside the card (negative offsets) inside it, so it never ends up off the screen
    $cssLine = ".{$popupClass} .ppsgenPopupClose {
      --ppsgen-close-right:{$closeRight}px;
      --ppsgen-close-top:{$closeTop}px;
      position:absolute;
      display:flex;
      right:{$closeRight}px;
      top:{$closeTop}px;
      width: {$closeBgSize}px;
      height: {$closeBgSize}px;
      font-size: {$closeIconSize}px;
      color: {$closeIconColor};
      background: {$closeBgColor};
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index:99999999;
      cursor:pointer;
      border:{$closeBorder}{$closeBorderUnit} {$closeBorderStyle} {$closeBorderColor};
      transition:.2s all;";
    if ($closeStyle == 'square') {
      $cssLine .= 'border-radius:0px;';
    }
    if ($closeStyle == 'rounded') {
      $cssLine .= 'border-radius:20%;';
    }
    if ($closeStyle == 'circle') {
      $cssLine .= 'border-radius:50%;';
    }
    $cssLine .= '}';
    $component->setCss($cssLine);

    $cssLine = ".{$popupClass} .ppsgenPopupClose:hover {
      color: {$closeIconColorHover};
      background: {$closeBgColorHover};
      border:{$closeBorder}{$closeBorderUnit} {$closeBorderStyle} {$closeBorderColorHover};
    }";
    $component->setCss($cssLine);

    $css = $component->getGoogleFontImportCss(true) . $this->getFrame()->core('helper')->mergeCssRulesBySelector($component->getCss());
    $component->cleanCss();
    return $css;
  }

  public function popupEditBuildFieldsTree(array $elements, $parent_key = '', array $visited = [])
  {
    $branch = [];
    foreach ($elements as $element) {
      $fieldKey = $element['settings']['field_key'] ?? '';
      $elParentKey = $element['settings']['parent_key'] ?? '';
      if ($fieldKey === $elParentKey) {
        continue;
      }
      if (in_array($fieldKey, $visited, true)) {
        continue;
      }
      if ($elParentKey === $parent_key) {
        $newVisited = array_merge($visited, [$fieldKey]);
        $children = $this->popupEditBuildFieldsTree($elements, $fieldKey, $newVisited);
        if ($children) {
          $element['children'] = $children;
        }
        $branch[] = $element;
      }
    }
    usort($branch, function ($a, $b) {
      return intval($a['settings']['order_index'] ?? 0) <=> intval($b['settings']['order_index'] ?? 0);
    });
    return $branch;
  }

  public function popupEditRenderField($field, $parent_types)
  {
    $settings = $field['settings'];
    $key = esc_attr($settings['field_key'] ?? '');
    $type = esc_attr($settings['field_type'] ?? '');
    $colPc = intval($settings['col_pc'] ?? 12);
    $classes = sprintf('ppsgen-popup-col ppsgen-popup-type-%s ppsgen-col-pc-%d', $type, $colPc);
    $html = '<div' . ' data-field-type="' . $type . '"' . ' data-field-key="' . $key . '"' . ' class="' . esc_attr($classes) . '"' . ' draggable="false">' . '<div class="ppsgen-popup-label">' . esc_html($type) . '</div>';
    if (in_array($type, $parent_types, true)) {
      $html .= '<div class="ppsgen-popup-col-inner">';
      if (!empty($field['children'])) {
        foreach ($field['children'] as $child) {
          $html .= $this->popupEditRenderField($child, $parent_types);
        }
      }
      $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
  }

  public function popupFrontendRenderField($field)
  {
    $component = $this->getComponent();
    $fieldKey = $field['settings']['field_key'] ?? '';
    $fieldAttrId = $field['settings']['id'] ?? '';
    $fieldAttrClass = $field['settings']['class'] ?? '';
    $fieldShowMob = !empty($field['settings']['show_mob']) ? '' : 'ppsgen-popup-field-mob-hide';
    $fieldShowTab = !empty($field['settings']['show_tab']) ? '' : 'ppsgen-popup-field-tab-hide';
    $fieldShowPc = !empty($field['settings']['show_pc']) ? '' : 'ppsgen-popup-field-pc-hide';

    $fieldColMob = !empty($field['settings']['col_mob']) ? 'ppsgen-popup-field-mob-' . intval($field['settings']['col_mob']) : 'ppsgen-popup-field-mob-12';
    $fieldColTab = !empty($field['settings']['col_tab']) ? 'ppsgen-popup-field-tab-' . intval($field['settings']['col_tab']) : 'ppsgen-popup-field-tab-12';
    $fieldColPc = !empty($field['settings']['col_pc']) ? 'ppsgen-popup-field-pc-' . intval($field['settings']['col_pc']) : 'ppsgen-popup-field-pc-12';

    $fieldType = !empty($field['settings']['field_type']) ? 'ppsgen-popup-field-' . $field['settings']['field_type'] : '';

    $fieldClass = 'ppsgen-popup-field ' . $fieldAttrClass . ' ' . $fieldType . ' ' . $fieldShowMob . ' ' . $fieldShowTab . ' ' . $fieldShowPc . ' ' . $fieldColMob . ' ' . $fieldColTab . ' ' . $fieldColPc;

    $stepSlug = '';
    if ($field['settings']['field_type'] == 'column' || $field['settings']['field_type'] == 'row') {
      $stepSlug = $component->nvalue($field['settings']['step_slug'] ?? '');
    }
    if (!empty($stepSlug)) {
      // Steps other than the default one are hidden from the first paint (previews, slow scripts)
      if ($this->defaultStepSlug !== '' && $stepSlug !== $this->defaultStepSlug) {
        $fieldClass .= ' ppsgen-step-hidden';
      }
      $stepSlug = ' data-step-slug="' . esc_attr($stepSlug) . '" ';
    }

    $html = '';
    // $stepSlug is a ready attribute (its value is escaped above); escaping it again turned the quotes into
    // &quot; and the step value into "slug" with quotes, so steps never matched
    $html .= '<div ' . $stepSlug . ' data-ftype="' . esc_attr($field['settings']['field_type'] ?? '') . '" data-fkey="' . esc_attr($fieldKey) . '" id="' . esc_attr($fieldAttrId) . '" class="' . esc_attr($fieldClass) . '">';
    $fieldContent = $this->popupFrontendRenderFieldContent($field);
    $this->popupFrontendFieldBuildStyles($field);
    if (!empty($fieldContent)) {
      //$html .= '<div class="ppsgen-popup-field-content">'.$fieldContent.'</div>';
      $html .= $fieldContent;
    }
    if (!empty($field['children'])) {
      foreach ($field['children'] as $child) {
        $html .= $this->popupFrontendRenderField($child);
      }
    }
    $html .= '</div>';
    return $html;
  }

  public function popupFrontendRenderFieldContent($field)
  {
    $component = $this->getComponent();
    $type = $field['settings']['field_type'] ?? '';
    switch ($type) {
      case 'title':
        $text = $component->nvalue($field['settings']['title_text'] ?? '');
        return esc_html($text);
      case 'textarea':
        $content = $component->nvalue($field['settings']['html_textarea_content'] ?? '');
        $content = apply_filters('the_content', $content);
        return $this->getFrame()->core('helper')->wpKses($content);
      case 'iframe':
        $frameUrl = $component->nvalue($field['settings']['iframe_url'] ?? '');
        if (!$frameUrl) {
          return '';
        }
        if (strpos($frameUrl, 'youtube.com') !== false || strpos($frameUrl, 'youtu.be') !== false) {
          if (strpos($frameUrl, 'enablejsapi=1') === false) {
            if (strpos($frameUrl, '?') !== false) {
              $frameUrl .= '&enablejsapi=1';
            } else {
              $frameUrl .= '?enablejsapi=1';
            }
          }
        }
        if (strpos($frameUrl, 'facebook.com') !== false) {
          return '<div class="ppsgenPopupFbWrapper" data-href="' . esc_url($frameUrl) . '"></div>';
        }
        return '<iframe src="' . esc_url($frameUrl) . '" allowfullscreen frameborder="0"></iframe>';
      case 'form':
        if ($this->getFrame()->useModule('form')) {
          $form_id = $component->nvalue($field['settings']['form_id'] ?? 0);
          $form_id = intval($form_id);
          if (!empty($form_id)) {
            return do_shortcode('[supsystic-ces-form id="' . $form_id . '"]');
          }
        }
        // no break
      case 'button':
        $buttonText = $component->nvalue($field['settings']['button_text'] ?? '');
        $buttonRel = $component->nvalue($field['settings']['button_rel'] ?? '');
        $buttonTarget = $component->nvalue($field['settings']['button_target'] ?? '');
        $buttonUrl = $component->nvalue($field['settings']['button_url'] ?? '');
        $buttonType = $component->nvalue($field['settings']['button_type'] ?? 'default');
        $buttonStepSlug = $component->nvalue($field['settings']['btn_to_step'] ?? 'default');
        $btnHtml = '<a ';
        if (!empty($buttonRel)) {
          $btnHtml .= ' rel="' . esc_attr($buttonRel) . '"';
        }
        if (!empty($iconTarget)) {
          $btnHtml .= ' target="' . esc_attr($buttonTarget) . '"';
        }
        if (!empty($buttonUrl)) {
          $btnHtml .= ' href="' . esc_url($buttonUrl) . '"';
        }
        $btnHtml .= '>';
        $btnHtml .= '<button data-type="' . esc_attr($buttonType) . '" data-to-step="' . esc_attr($buttonStepSlug) . '">';
        $btnHtml .= esc_html($buttonText);
        $btnHtml .= '</button></a>';
        return $btnHtml;
      case 'svg_icon':
        $iconTitle = $component->nvalue($field['settings']['icon_title'] ?? '');
        $iconRel = $component->nvalue($field['settings']['icon_rel'] ?? '');
        $iconTarget = $component->nvalue($field['settings']['icon_target'] ?? '');
        $iconUrl = $component->nvalue($field['settings']['icon_url'] ?? '');
        $iconColor = $component->nvalue($field['settings']['svg_icon_color'] ?? '');
        $svgIcon = $component->nvalue($field['settings']['svg_icon'] ?? '');
        if (empty($svgIcon)) {
          return '';
        }
        $iconHtml = '';
        if (!empty($iconUrl)) {
          $iconHtml .= '<a ';
          if (!empty($iconTitle)) {
            $iconHtml .= ' title="' . esc_attr($iconTitle) . '"';
          }
          if (!empty($iconRel)) {
            $iconHtml .= ' rel="' . esc_attr($iconRel) . '"';
          }
          if (!empty($iconTarget)) {
            $iconHtml .= ' target="' . esc_attr($iconTarget) . '"';
          }
          $iconHtml .= ' href="' . esc_url($iconUrl) . '"';
          $iconHtml .= '>';
        }
        // Read from disk, not over HTTP (see HelperPpsgen::getSvgIconMarkup)
        $svgContent = $this->getFrame()->core('helper')->getSvgIconMarkup((string) $svgIcon);
        if (!empty($svgContent)) {
          $svgContent = str_replace('viewBox', 'viewbox', $svgContent);
          $svgContent = str_replace('<svg', '<svg style="--primary-svg-color:' . esc_attr($iconColor) . ';"', $svgContent);
          $iconHtml .= $svgContent;
        }
        if (!empty($iconUrl)) {
          $iconHtml .= '</a>';
        }
        return $iconHtml;
      case 'icon':
        $iconTitle = $component->nvalue($field['settings']['icon_title'] ?? '');
        $iconRel = $component->nvalue($field['settings']['icon_rel'] ?? '');
        $iconTarget = $component->nvalue($field['settings']['icon_target'] ?? '');
        $iconUrl = $component->nvalue($field['settings']['icon_url'] ?? '');
        $iconClass = $component->nvalue($field['settings']['icon_class'] ?? '');
        if (empty($iconClass)) {
          return '';
        }
        $iconHtml = '<a ';
        if (!empty($iconTitle)) {
          $iconHtml .= ' title="' . esc_attr($iconTitle) . '"';
        }
        if (!empty($iconRel)) {
          $iconHtml .= ' rel="' . esc_attr($iconRel) . '"';
        }
        if (!empty($iconTarget)) {
          $iconHtml .= ' target="' . esc_attr($iconTarget) . '"';
        }
        if (!empty($iconUrl)) {
          $iconHtml .= ' href="' . esc_url($iconUrl) . '"';
        }
        $iconHtml .= '>';
        $iconHtml .= '<i class="' . esc_attr($iconClass) . '"></i>';
        $iconHtml .= '</a>';
        return $iconHtml;
      case 'divider':
        return '<div style="width:100%;"></div>';
      case 'timer':
        $timerType = $component->nvalue($field['settings']['timer_type'] ?? 'datetime');
        $timerDate = $component->nvalue($field['settings']['timer_date'] ?? '');
        $timerDuration = intval($component->nvalue($field['settings']['timer_duration'] ?? 10));
        $timerOnExpire = $component->nvalue($field['settings']['timer_on_expire'] ?? 'none');
        $showYear = $component->value($field['settings']['timer_show_year'] ?? null, '');
        $showMonth = $component->value($field['settings']['timer_show_month'] ?? null, '');
        $showDays = $component->value($field['settings']['timer_show_days'] ?? null, '1');
        $showHours = $component->value($field['settings']['timer_show_hours'] ?? null, '1');
        $showMinutes = $component->value($field['settings']['timer_show_minutes'] ?? null, '1');
        $showSeconds = $component->value($field['settings']['timer_show_seconds'] ?? null, '1');
        $showLabel = $component->nvalue($field['settings']['timer_show_label'] ?? 'under');
        $labelYear = $component->nvalue($field['settings']['timer_label_year'] ?? '') ?: __('Years', 'ppsgen');
        $labelMonth = $component->nvalue($field['settings']['timer_label_month'] ?? '') ?: __('Months', 'ppsgen');
        $labelDay = $component->nvalue($field['settings']['timer_label_day'] ?? '') ?: __('Days', 'ppsgen');
        $labelHour = $component->nvalue($field['settings']['timer_label_hour'] ?? '') ?: __('Hours', 'ppsgen');
        $labelMinute = $component->nvalue($field['settings']['timer_label_minute'] ?? '') ?: __('Minutes', 'ppsgen');
        $labelSecond = $component->nvalue($field['settings']['timer_label_second'] ?? '') ?: __('Seconds', 'ppsgen');
        $timerSize = $component->nvalue($field['settings']['timer_size'] ?? 'm');
        $timerStyle = $component->nvalue($field['settings']['timer_style'] ?? 'black');
        $timerShape = $component->nvalue($field['settings']['timer_shape'] ?? 'rounded');
        $timerGap = intval($component->nvalue($field['settings']['timer_gap'] ?? 8));
        $units = [
          'year' => ['show' => $showYear,    'label' => $labelYear],
          'month' => ['show' => $showMonth,   'label' => $labelMonth],
          'day' => ['show' => $showDays,    'label' => $labelDay],
          'hour' => ['show' => $showHours,   'label' => $labelHour],
          'minute' => ['show' => $showMinutes, 'label' => $labelMinute],
          'second' => ['show' => $showSeconds, 'label' => $labelSecond],
        ];
        $dataAttrs = ' data-timer-type="' . esc_attr($timerType) . '"';
        $dataAttrs .= ' data-timer-date="' . esc_attr($timerDate) . '"';
        $dataAttrs .= ' data-timer-duration="' . esc_attr($timerDuration) . '"';
        $dataAttrs .= ' data-timer-on-expire="' . esc_attr($timerOnExpire) . '"';
        $dataAttrs .= ' data-timer-style="' . esc_attr($timerStyle) . '"';
        $dataAttrs .= ' data-timer-size="' . esc_attr($timerSize) . '"';
        $dataAttrs .= ' data-timer-shape="' . esc_attr($timerShape) . '"';
        $html = '<div class="ppsgen-timer ppsgen-timer--' . esc_attr($timerStyle) . ' ppsgen-timer--' . esc_attr($timerSize) . ' ppsgen-timer--' . esc_attr($timerShape) . '"' . $dataAttrs . ' style="gap:' . esc_attr($timerGap) . 'px;">';
        foreach ($units as $unitKey => $unit) {
          if (empty($unit['show'])) {
            continue;
          }
          $html .= '<div class="ppsgen-timer__unit ppsgen-timer__unit--' . esc_attr($unitKey) . '" data-unit="' . esc_attr($unitKey) . '">';
          if ($showLabel === 'upper') {
            $html .= '<span class="ppsgen-timer__label ppsgen-timer__label--upper">' . esc_html($unit['label']) . '</span>';
          }
          $html .= '<div class="ppsgen-timer__value">00</div>';
          if ($showLabel === 'under') {
            $html .= '<span class="ppsgen-timer__label ppsgen-timer__label--under">' . esc_html($unit['label']) . '</span>';
          }
          $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
      default:
        return '';
    }
  }

  public function popupFrontendFieldBuildStyles($field)
  {
    $type = $field['settings']['field_type'] ?? '';
    $fkey = $field['settings']['field_key'] ?? '';
    $settings = $field['settings'];
    $fkeyClass = '[data-fkey="' . esc_attr($fkey) . '"]';
    if (empty($this->renderPreview)) {
      $popupId = $field['popup_id'] ?? '';
    } else {
      $popupId = 999;
    }

    $class = 'ppsgenPopup-' . $popupId . ' .ppsgen-popup-field' . $fkeyClass;
    $component = $this->getComponent();
    $helper = $this->getHelper();

    if ($type == 'button') {
      $cssLine = ".{$class} a button {";
    } elseif ($type == 'iframe') {
      $cssLine = ".{$class} {";
      $iframeResponsive = $component->value($settings['iframe_responsive'] ?? null, '');
      if (!empty($iframeResponsive)) {
        $cssLine .= "position: relative;width: 100%;padding-bottom: 56.25%;height: 0;overflow: hidden;";
      } else {
        $cssLine .= "position: relative;width: 100%;height:100%;overflow: hidden;";
      }
      $cssLine .= "}";
      $cssLine .= ".{$class} iframe {";
      $iframeResponsive = $component->value($settings['iframe_responsive'] ?? null, '');
      if (!empty($iframeResponsive)) {
        $cssLine .= "position: absolute;top: 0;left: 0;width: 100% !important;height: 100% !important;border: 0;";
      } else {
        $cssLine .= "width:100%; height: 100%;";
      }
      $cssLine .= "}";
    } elseif ($type == 'timer') {
      // The timer's own rules come first; the element rule (margin, padding, border…) is opened after them
      $cssLine = '';
    } else {
      $cssLine = ".{$class} {";
    }

    if ($type == 'title') {
      $titleTextAlign = $component->value($settings['title_text_align'] ?? null, '');
      if (!empty($titleTextAlign)) {
        $cssLine .= "text-align: {$titleTextAlign};";
      }
    }

    if ($type == 'timer') {
      $timerStyle = $component->nvalue($settings['timer_style'] ?? 'black');
      // Timer block styles
      $cssLine .= ".{$class} .ppsgen-timer {";
      $cssLine .= "display:flex;flex-wrap:wrap;align-items:center;justify-content:center;";
      $cssLine .= "}";
      // Unit styles
      $cssLine .= ".{$class} .ppsgen-timer__unit {";
      $cssLine .= "display:flex;flex-direction:column;align-items:center;justify-content:center;";
      // Size
      $timerSize = $component->nvalue($settings['timer_size'] ?? 'm');
      $sizeMap = ['s' => '36px', 'm' => '52px', 'l' => '68px', 'xl' => '88px'];
      $fontMap = ['s' => '18px', 'm' => '26px', 'l' => '34px', 'xl' => '46px'];
      $unitSize = $sizeMap[$timerSize] ?? '52px';
      $cssLine .= "width:{$unitSize};height:{$unitSize};";
      // Shape
      $timerShape = $component->nvalue($settings['timer_shape'] ?? 'rounded');
      if ($timerStyle === 'custom') {
        $shapeMap = ['square' => '0px', 'rounded' => '8px', 'circle' => '50%'];
        $cssLine .= "border-radius:" . ($shapeMap[$timerShape] ?? '8px') . ";";
      } elseif ($timerStyle === 'black') {
        $cssLine .= "border-radius:6px;";
      } elseif ($timerStyle === 'white') {
        $cssLine .= "border-radius:6px;";
      }
      // Background color
      if ($timerStyle === 'black') {
        $cssLine .= "background:#1a1a1a;";
      } elseif ($timerStyle === 'white') {
        $cssLine .= "background:#ffffff;border:1px solid #e0e0e0;";
      } elseif ($timerStyle === 'custom') {
        $timerBg = $component->nvalue($settings['timer_bg_color'] ?? '');
        if (!empty($timerBg)) {
          $cssLine .= "background:" . esc_attr($timerBg) . ";";
        }
      }
      $cssLine .= "}";
      $cssLine .= ".{$class} .ppsgen-timer__value {";
      $fontSize = $fontMap[$timerSize] ?? '26px';
      $cssLine .= "font-size:{$fontSize};font-weight:700;line-height:1;";
      if ($timerStyle === 'black') {
        $cssLine .= "color:#ffffff;";
      } elseif ($timerStyle === 'white') {
        $cssLine .= "color:#1a1a1a;";
      } elseif ($timerStyle === 'custom') {
        $timerTextColor = $component->nvalue($settings['timer_text_color'] ?? '');
        if (!empty($timerTextColor)) {
          $cssLine .= "color:" . esc_attr($timerTextColor) . ";";
        }
      }
      $cssLine .= "}";
      $cssLine .= ".{$class} .ppsgen-timer__label {";
      $cssLine .= "font-size:11px;line-height:1.2;text-align:center;";
      if ($timerStyle === 'black') {
        $cssLine .= "color:#ffffff;";
      } elseif ($timerStyle === 'white') {
        $cssLine .= "color:#1a1a1a;";
      } elseif ($timerStyle === 'custom') {
        $timerTextColor = $component->nvalue($settings['timer_text_color'] ?? '');
        if (!empty($timerTextColor)) {
          $cssLine .= "color:" . esc_attr($timerTextColor) . ";";
        }
      }
      $cssLine .= "}";
      $cssLine .= ".{$class} {";
    }

    // Margin
    $marginT = $component->value($settings['margin_top'] ?? null, '0');
    $marginTU = $component->cvalue($settings['margin_top_unit'] ?? null, 'px');
    $marginB = $component->value($settings['margin_bottom'] ?? null, '0');
    $marginBU = $component->cvalue($settings['margin_bottom_unit'] ?? null, 'px');
    $marginL = $component->value($settings['margin_left'] ?? null, '0');
    $marginLU = $component->cvalue($settings['margin_left_unit'] ?? null, 'px');
    $marginR = $component->value($settings['margin_right'] ?? null, '0');
    $marginRU = $component->cvalue($settings['margin_right_unit'] ?? null, 'px');
    $cssLine .= "
     margin-top: {$marginT}{$marginTU};
     margin-bottom: {$marginB}{$marginBU};
     margin-left: {$marginL}{$marginLU};
     margin-right: {$marginR}{$marginRU};
    ";

    // Padding
    $paddingT = $component->value($settings['padding_top'] ?? null, '0');
    $paddingTU = $component->cvalue($settings['padding_top_unit'] ?? null, 'px');
    $paddingB = $component->value($settings['padding_bottom'] ?? null, '0');
    $paddingBU = $component->cvalue($settings['padding_bottom_unit'] ?? null, 'px');
    $paddingL = $component->value($settings['padding_left'] ?? null, '0');
    $paddingLU = $component->cvalue($settings['padding_left_unit'] ?? null, 'px');
    $paddingR = $component->value($settings['padding_right'] ?? null, '0');
    $paddingRU = $component->cvalue($settings['padding_right_unit'] ?? null, 'px');
    $cssLine .= "
      padding-top: {$paddingT}{$paddingTU};
      padding-bottom: {$paddingB}{$paddingBU};
      padding-left: {$paddingL}{$paddingLU};
      padding-right: {$paddingR}{$paddingRU};
     ";

    // Grid or Flex
    if ($type == 'row' || $type == 'column') {
      $grid_type = $component->nvalue($settings['grid_type'] ?? null, 'grid');
      $grid_type = !empty($grid_type) ? $grid_type : 'grid';
      $gac = $component->value($settings['grid_align_content'] ?? null, '');
      $gjc = $component->value($settings['grid_justify_content'] ?? null, '');
      $gai = $component->value($settings['grid_align_items'] ?? null, '');
      $gji = $component->value($settings['grid_justify_items'] ?? null, '');
      $gap = $component->value($settings['gap'] ?? null, '');
      $gap_unit = $component->value($settings['gap_unit'] ?? null, 'px');
      $valid_align_content = ['flex-start', 'flex-end', 'center', 'stretch', 'space-between', 'space-around', 'space-evenly', 'start', 'end'];
      $valid_justify_content = ['flex-start', 'flex-end', 'center', 'stretch', 'space-between', 'space-around', 'space-evenly', 'start', 'end'];
      $valid_align_items = ['flex-start', 'flex-end', 'center', 'stretch', 'start', 'end'];
      $valid_justify_items = ['start', 'end', 'center', 'stretch'];
      if (!empty($grid_type)) {
        $cssLine .= "  display: {$grid_type}; "; // !important
        if ($grid_type === 'flex') {
          $flex_direction = $component->value($settings['grid_flex_direction'] ?? null, 'row');
          $flex_wrap = $component->value($settings['grid_flex_wrap'] ?? null, 'wrap');
          if (in_array($flex_direction, ['row', 'column', 'row-reverse', 'column-reverse'])) {
            $cssLine .= "  flex-direction: {$flex_direction}; ";
          }
          if (in_array($flex_wrap, ['wrap', 'nowrap'])) {
            $cssLine .= "  flex-wrap: {$flex_wrap}; ";
          }
          $flex_gac = str_replace(['start', 'end'], ['flex-start', 'flex-end'], $gac);
          $flex_gjc = str_replace(['start', 'end'], ['flex-start', 'flex-end'], $gjc);
          $flex_gai = str_replace(['start', 'end'], ['flex-start', 'flex-end'], $gai);
          $flex_gji = str_replace(['start', 'end'], ['flex-start', 'flex-end'], $gji);
        } else {
          $flex_gac = $gac;
          $flex_gjc = $gjc;
          $flex_gai = $gai;
          $flex_gji = $gji;
        }
      }
      if (!empty($flex_gac) && in_array($flex_gac, $valid_align_content)) {
        $cssLine .= "  align-content: {$flex_gac}; ";
      }
      if (!empty($flex_gjc) && in_array($flex_gjc, $valid_justify_content)) {
        $cssLine .= "  justify-content: {$flex_gjc}; ";
      }
      if (!empty($flex_gai) && in_array($flex_gai, $valid_align_items)) {
        $cssLine .= "  align-items: {$flex_gai}; ";
      }
      if (!empty($flex_gji) && $grid_type !== 'flex' && in_array($flex_gji, $valid_justify_items)) {
        $cssLine .= "  justify-items: {$flex_gji}; ";
      } elseif (!empty($flex_gji) && $grid_type === 'flex' && in_array($flex_gji, $valid_justify_content)) {
        $cssLine .= "  justify-content: {$flex_gji}; ";
      }
      if (!empty($gap) && is_numeric($gap)) {
        $cssLine .= "  gap: {$gap}{$gap_unit}; ";
      }
    }

    // Box Shadow
    $boxShadowT = $component->value($settings['box_shadow_top'] ?? null, '0');
    $boxShadowTU = $component->cvalue($settings['box_shadow_top_unit'] ?? null, 'px');
    $boxShadowB = $component->value($settings['box_shadow_bottom'] ?? null, '0');
    $boxShadowBU = $component->cvalue($settings['box_shadow_bottom_unit'] ?? null, 'px');
    $boxShadowL = $component->value($settings['box_shadow_left'] ?? null, '0');
    $boxShadowLU = $component->cvalue($settings['box_shadow_left_unit'] ?? null, 'px');
    $boxShadowR = $component->value($settings['box_shadow_right'] ?? null, '0');
    $boxShadowRU = $component->cvalue($settings['box_shadow_right_unit'] ?? null, 'px');
    $boxShadowColor = $component->value($settings['box_shadow_color'] ?? null, '');
    // Only when a shadow is actually set: an all-zero rule here outranks classes from Custom CSS
    // (e.g. frames drawn with box-shadow) and silently removes them, like borders are only written when set
    if ((float) $boxShadowT || (float) $boxShadowB || (float) $boxShadowL || (float) $boxShadowR) {
      $cssLine .= "
      box-shadow: {$boxShadowT}{$boxShadowTU} {$boxShadowR}{$boxShadowRU} {$boxShadowL}{$boxShadowLU}  {$boxShadowB}{$boxShadowBU} {$boxShadowColor};
    ";
    }

    // Text Shadow
    $textShadowT = $component->value($settings['text_shadow_top'] ?? null, '0');
    $textShadowTU = $component->cvalue($settings['text_shadow_top_unit'] ?? null, 'px');
    $textShadowL = $component->value($settings['text_shadow_left'] ?? null, '0');
    $textShadowLU = $component->cvalue($settings['text_shadow_left_unit'] ?? null, 'px');
    $textShadowR = $component->value($settings['text_shadow_right'] ?? null, '0');
    $textShadowRU = $component->cvalue($settings['text_shadow_right_unit'] ?? null, 'px');
    $textShadowColor = $component->value($settings['text_shadow_color'] ?? null, '');
    if ((float) $textShadowT || (float) $textShadowL || (float) $textShadowR) {
      $cssLine .= "
      text-shadow: {$textShadowT}{$textShadowTU} {$textShadowR}{$textShadowRU} {$textShadowL}{$textShadowLU} {$textShadowColor};
    ";
    }

    // Border
    $border = $component->value($settings['border'] ?? null, '0');
    $borderUnit = $component->cvalue($settings['border_unit'] ?? null, 'px');
    $borderStyle = $component->value($settings['border_style'] ?? null, 'solid');
    $borderColor = $component->value($settings['border_color'] ?? null, '');
    if (!empty($border)) {
      $cssLine .= "
       border: {$border}{$borderUnit} {$borderStyle} {$borderColor};
       ";
    }

    // Border Radius
    $border = $component->value($settings['border_radius'] ?? null, '');
    $borderUnit = $component->cvalue($settings['border_radius_unit'] ?? null, 'px');
    if (!empty($border)) {
      $cssLine .= "
       border-radius: {$border}{$borderUnit};
       ";
    }

    // Background
    $background = $component->value($settings['background'] ?? null, '');
    if (!empty($background)) {
      $cssLine .= "
       background: {$background};
       background-color: {$background};
       ";
    }

    // Text Style
    $fontFamily = $component->value($settings['font_family'] ?? null, '');
    $fontFamilyCss = $component->getFontFamilyCss($fontFamily);
    $fontSize = $component->value($settings['font_size'] ?? null, '');
    $fontSizeUnit = $component->cvalue($settings['font_size_unit'] ?? null, 'px');
    $fontColor = $component->value($settings['font_color'] ?? null, '');
    $fontColorHover = $component->value($settings['font_color_hover'] ?? null, '');
    $fontStyle = $component->value($settings['font_style'] ?? null, '');
    $fontWeight = $component->value($settings['font_weight'] ?? null, '');
    if (!empty($fontSize)) {
      $cssLine .= "font-size: {$fontSize}{$fontSizeUnit};";
    }
    if (!empty($fontWeight)) {
      $cssLine .= "font-weight: {$fontWeight};";
    }
    if (!empty($fontStyle)) {
      $cssLine .= "font-style: {$fontStyle};";
    }
    if (!empty($fontFamilyCss)) {
      $cssLine .= $fontFamilyCss;
    }
    if (!empty($fontColor)) {
      $cssLine .= "color: {$fontColor};";
    }

    // Overflow
    $overflow = $component->value($settings['overflow'] ?? null, '');
    $overflowX = $component->value($settings['overflow_x'] ?? null, '');
    $overflowY = $component->value($settings['overflow_y'] ?? null, '');
    if (!empty($overflow)) {
      $cssLine .= "overflow: {$overflow};";
    }
    if (!empty($overflowX)) {
      $cssLine .= "overflow-x: {$overflowX};";
    }
    if (!empty($overflowY)) {
      $cssLine .= "overflow-y: {$overflowY};";
    }

    // Width
    $widthSelect = $component->value($settings['width_select'] ?? null, 'auto');
    $width = $component->value($settings['width'] ?? null, '100');
    $widthUnit = $component->cvalue($settings['width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssLine .= "width: {$width}{$widthUnit};";
    } elseif ($widthSelect == 'auto') {
      $cssLine .= '';
    } else {
      $cssLine .= "width: {$widthSelect};";
    }

    // Max-Width
    $widthSelect = $component->value($settings['max_width_select'] ?? null, 'auto');
    $width = $component->value($settings['max_width'] ?? null, '100');
    $widthUnit = $component->cvalue($settings['max_width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssLine .= "max-width: {$width}{$widthUnit};";
    } elseif ($widthSelect == 'auto') {
      $cssLine .= '';
    } else {
      $cssLine .= "max-width: {$widthSelect};";
    }

    // Height
    $heightSelect = $component->value($settings['height_select'] ?? null, 'auto');
    $height = $component->value($settings['height'] ?? null, '100');
    $heightUnit = $component->cvalue($settings['height_unit'] ?? null, '%');
    if ($heightSelect == 'manual') {
      $cssLine .= "height: {$height}{$heightUnit};";
    } elseif ($heightSelect == 'auto') {
      $cssLine .= '';
    } else {
      $cssLine .= "height: {$heightSelect};";
    }

    // Max Height
    $heightSelect = $component->value($settings['max_height_select'] ?? null, 'auto');
    $height = $component->value($settings['max_height'] ?? null, '100');
    $heightUnit = $component->cvalue($settings['max_height_unit'] ?? null, '%');
    if ($heightSelect == 'manual') {
      $cssLine .= "max-height: {$height}{$heightUnit};";
    } elseif ($heightSelect == 'auto') {
      $cssLine .= '';
    } else {
      $cssLine .= "max-height: {$heightSelect};";
    }

    $cssLine .= '}';

    $fontColor = $component->value($settings['link_color'] ?? null, '');
    $fontColorHover = $component->value($settings['link_hover_color'] ?? null, '');
    if (!empty($fontColor)) {
      $cssLine .= ".{$class} a {";
      $cssLine .= "color:{$fontColor};";
      if (!empty($fontSize)) {
        $cssLine .= "font-size: {$fontSize}{$fontSizeUnit};";
      }
      $cssLine .= '}';
    }
    if (!empty($fontColorHover)) {
      $cssLine .= ".{$class} a:hover {";
      $cssLine .= "color:{$fontColorHover};";
      if (!empty($fontSize)) {
        $cssLine .= "font-size: {$fontSize}{$fontSizeUnit};";
      }
      $cssLine .= '}';
    }

    // Background
    $background = $component->value($settings['background_hover'] ?? null, '');
    if (!empty($background)) {
      if ($type == 'button') {
        $cssLine .= ".{$class} a:hover button {";
      } elseif ($type == 'iframe') {
        $cssLine .= ".{$class} iframe:hover {";
      } else {
        $cssLine .= ".{$class} {";
      }
      $cssLine .= "
        background: {$background};
        background-color: {$background};
        ";
      $cssLine .= '}';
    }

    $component->setCss($cssLine);
    if (empty($this->renderPreview)) {
      $css = $component->getGoogleFontImportCss(true) . $helper->minifyCss($helper->mergeCssRulesBySelector($component->getCss()));
      $component->cleanCss();
      $popupStyleTitle = 'ppsgen-frontend-popups-' . $popupId . '-' . $fkey;
      wp_register_style($popupStyleTitle, false, [], PPSGEN_VERSION);
      wp_enqueue_style($popupStyleTitle);
      wp_add_inline_style($popupStyleTitle, $css);
    } else {
      $this->previewInlineCss .= $component->getGoogleFontImportCss(true) . $helper->minifyCss($helper->mergeCssRulesBySelector($component->getCss()));
      $component->cleanCss();
    }
  }
}
