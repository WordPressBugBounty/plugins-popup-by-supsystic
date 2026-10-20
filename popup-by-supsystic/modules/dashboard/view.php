<?php

if (!defined('ABSPATH')) {
  exit();
}

class DashboardViewPpsgen extends BaseViewPpsgen
{
  public function renderDashboardLayout($params)
  {
    if (empty($params['content']) && empty($params['tab'])) {
      return '';
    }
    $moduleContent = $params['content'];
    $moduleTab = $params['tab'];
    $dashboardModule = $this->getFrame()->getModule('dashboard');
    $sections = $dashboardModule->getAdminSections();
    $current = $dashboardModule->getAdminSectionForTab((string) $moduleTab, $sections);
    $isHome = $current['section'] === 'home';
    $this->assign('moduleTab', $moduleTab);
    $this->assign('sections', $sections);
    $this->assign('currentSection', $current['section']);
    $this->assign('currentSectionItem', $current['item']);
    $this->assign('isHome', $isHome);
    $this->assign('emailStatus', $this->getModel()->getEmailSendingStatus());
    $this->assign('proLicenseNotice', $this->getModel()->getProLicenseNoticeData());
    // Ask for a review only on Home, never on top of the user's work
    $this->assign('feedbackNotice', $isHome ? $this->getModel()->getFeedbackNoticeData() : []);
    $navigationHtml = $this->renderTpl('navigation', 'dashboard');
    $this->assign('moduleContent', $moduleContent);
    $contentHtml = $this->renderTpl('content', 'dashboard');
    $this->assign('navigationHtml', $navigationHtml);
    $this->assign('contentHtml', $contentHtml);
    $html = $this->renderTpl('layout', 'dashboard');
    return $html;
  }

  public function renderDashboardModules($params)
  {
    $modulesList = $this->getFrame()->core('moduleManager')->getModulesList();
    $modulesInfo = $this->getModel()->getModulesInfo();
    if (!$this->getModel()->isProAddonActive()) {
      $modulesInfo = array_merge($modulesInfo, $this->getModel()->getProModulesInfo());
    }
    uasort($modulesInfo, function ($a, $b) {
      return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });
    $this->assign('modulesList', $modulesList);
    $this->assign('modulesInfo', $modulesInfo);
    $this->assign('proModulesOfferHtml', $this->getModel()->getProModulesOfferHtml());
    $html = $this->renderTpl('modules', 'dashboard');
    return $html;
  }

  public function renderDashboardOverview($params)
  {
    $html = $this->renderTpl('overview', 'dashboard');
    return $html;
  }

  public function renderDashboardHome($params)
  {
    $this->assign('home', $this->getModel()->getHomeData());
    $this->assign('monthly', $this->getModel()->getMonthlySummary());
    $this->assign('monthlyReport', $this->getModel()->getMonthlyReportSettings());
    $plays = $this->getFrame()->getModule('dashboard')->getPlays();
    $homePlays = [];
    foreach (array_slice($plays->getPlays(), 0, 5) as $play) {
      $play['stats'] = in_array($play['status'] ?? '', ['live', 'paused'], true) ? $plays->getStats($play) : [];
      $play['chips'] = array_column($plays->getParts($play), 'type');
      $homePlays[] = $play;
    }
    $this->assign('homePlays', $homePlays);
    $notLive = array_values(array_filter($plays->getPlays(), function ($play) {
      return in_array($play['status'] ?? 'draft', ['draft', 'built'], true);
    }));
    if (!empty($notLive)) {
      $home = $this->home;
      array_unshift($home['attention'], [
        'title' => sprintf(_n('%d play not live yet', '%d plays not live yet', count($notLive), 'ppsgen'), count($notLive)),
        'text' => sprintf(__('%s is still waiting for you.', 'ppsgen'), $notLive[0]['name'] ?? ''),
        'link' => __('Finish →', 'ppsgen'),
        'url' => admin_url('admin.php?page=ppsgen&tab=' . (($notLive[0]['status'] ?? '') === 'built' ? 'play_review' : 'play_setup') . '&id=' . $notLive[0]['id']),
      ]);
      $this->assign('home', $home);
    }
    $this->assign('playDefinitions', $plays->getDefinitions());
    return $this->renderTpl('home', 'dashboard');
  }

  // The PRO offer that used to sit on top of every page now lives here
  public function renderSettingsPro($params)
  {
    $this->assign('proOfferUrl', $this->getModel()->getProOfferNoticeData()['offer_url'] ?? '');
    return $this->renderTpl('settings_pro', 'dashboard');
  }

  public function renderReportPopups($params)
  {
    $this->assign('reportType', 'popup');
    $this->assign('reportRows', $this->getModel()->getReportRows('popup'));
    return $this->renderTpl('report_items', 'dashboard');
  }

  public function renderReportForms($params)
  {
    $this->assign('reportType', 'form');
    $this->assign('reportRows', $this->getModel()->getReportRows('form'));
    return $this->renderTpl('report_items', 'dashboard');
  }

  public function renderSettingsUsage($params)
  {
    $this->assign('usageSnapshot', function_exists('ppsgen_collect_usage_snapshot') ? ppsgen_collect_usage_snapshot() : []);
    return $this->renderTpl('settings_usage', 'dashboard');
  }

  public function renderSettingsClassic($params)
  {
    if (!function_exists('ppsgen_is_legacy_user') || !ppsgen_is_legacy_user()) {
      return '';
    }
    return $this->renderTpl('settings_classic', 'dashboard');
  }

  public function renderDashboardFilesPro($params)
  {
    $url = 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=files_pro';
    $html = '<div class="ppsgenProOfferNotice ppsgenProOfferNoticeTab">'
      . '<div class="ppsgenProOfferNoticeHero">'
      . '<div class="ppsgenProOfferNoticeEyebrow">' . esc_html__('Special PRO feature', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeBadges">'
      . '<span class="ppsgenProOfferNoticeBadge">CRM</span>'
      . '<span class="ppsgenProOfferNoticeBadge">FILES</span>'
      . '<span class="ppsgenProOfferNoticeBadge">FORMS</span>'
      . '<span class="ppsgenProOfferNoticeBadge">UPLOADS</span>'
      . '</div>'
      . '<h3 class="ppsgenProOfferNoticeTitle">' . esc_html__('Advanced File Fields in PRO', 'ppsgen') . '</h3>'
      . '<p class="ppsgenProOfferNoticeText">' . esc_html__('Collect files inside your CRM with secure uploads, detailed validation rules, and a dedicated files manager linked to forms, contacts, users, and submits.', 'ppsgen') . '</p>'
      . '<div class="ppsgenProOfferNoticeCouponLabel">' . esc_html__('Yearly licence · 1 site', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeCoupon"><span><strong>$99</strong> / year</span></div>'
      . '<div class="ppsgenProOfferNoticeSubtext">' . esc_html__('$99 stays $99 when you renew · No contact-based pricing · 30-day money-back guarantee', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeActions">'
      . '<a class="ppsgenProOfferNoticeBtn ppsgenProOfferNoticeBtnPrimary" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Unlock Advanced File Fields and PRO features', 'ppsgen') . '</a>'
      . '</div>'
      . '</div>'
      . '<div class="ppsgenProOfferNoticeFeatures">'
      . '<div class="ppsgenProOfferNoticeFeaturesTitle">' . esc_html__('What you unlock in PRO', 'ppsgen') . '</div>'
      . '<ul class="ppsgenProOfferNoticeFeatureList">'
      . '<li>' . esc_html__('File upload contact field for forms and CRM flows', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Multiple file uploads with max quantity control', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Min / max file size validation', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Allowed file format rules for safer uploads', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Dedicated Files manager with download and cleanup actions', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Files linked to form submits, contacts, users, and specific fields', 'ppsgen') . '</li>'
      . '</ul>'
      . '</div>'
      . '</div>';
    return $html;
  }

  public function renderDashboardOptions($params)
  {
    if (!function_exists('ppsgen_is_legacy_user') || !ppsgen_is_legacy_user()) {
      return '';
    }
    $dashboardOptions = $this->getModel()->getAllOptions();
    $this->assign('dashboardOptions', $dashboardOptions);
    $html = $this->renderTpl('options', 'dashboard');
    return $html;
  }

  public function renderDashboardMailConfigs($params)
  {
    $this->assign('domainCheck', $this->getModel()->getEmailDomainCheck());
    $rows = $this->getModel()->getMailConfigs();
    foreach ($rows as $key => $row) {
      $resolved = $this->getModel()->getResolvedMailConfigRemaining((int) ($row['id'] ?? 0));
      if (!empty($resolved['settings']) && is_array($resolved['settings'])) {
        $rows[$key]['settings'] = $resolved['settings'];
      }
    }
    $this->assign('mailConfigs', $rows);
    $this->assign('emailStatus', $this->getModel()->getEmailSendingStatus());
    $this->assign('lastTestEmail', $this->getModel()->getLastTestEmail());
    $this->assign('defaultMailConfigId', $this->getModel()->getDefaultMailConfigId());
    return $this->renderTpl('mail_configs', 'dashboard');
  }

  public function renderDashboardMailConfigEdit($params)
  {
    $this->getFrame()->core('component')->loadPpsgenselect();
    $configId = !empty($params['id']) ? (int) $params['id'] : 0;
    $mailConfig = $configId > 0 ? $this->getModel()->getMailConfigById($configId) : [
      'id' => 0,
      'name' => '',
      'settings' => $this->getModel()->getMailConfigDefaultSettings(),
      'is_default' => $this->getModel()->getDefaultMailConfigId() <= 0,
    ];
    if ($configId > 0 && empty($mailConfig)) {
      return esc_html__('Cant find Mail Config with specific ID for editing', 'ppsgen');
    }
    $contactTagOptions = [];
    if ($this->getFrame()->useModule('contact')) {
      $contactTagOptions = $this->getFrame()->getModule('contact')->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    }
    $this->assign('mailConfig', $mailConfig);
    $this->assign('contactTagOptions', $contactTagOptions);
    return $this->renderTpl('mail_config_edit', 'dashboard');
  }

  //=====================================
  //       PLAYS
  //=====================================

  protected function getPlaysService(): PlaysPpsgen
  {
    return $this->getFrame()->getModule('dashboard')->getPlays();
  }

  public function renderPlays($params)
  {
    $plays = $this->getPlaysService();
    $availability = [];
    foreach (array_keys($plays->getDefinitions()) as $type) {
      $availability[$type] = $plays->isAvailable($type);
    }
    $this->assign('definitions', $plays->getDefinitions());
    $this->assign('availability', $availability);
    $this->assign('plays', $plays->getPlays());
    return $this->renderTpl('plays', 'dashboard');
  }

  public function renderPlaySetup($params)
  {
    $plays = $this->getPlaysService();
    $id = sanitize_key((string) ($params['id'] ?? ''));
    $play = $id !== '' ? $plays->getPlay($id) : [];
    $type = !empty($play['type']) ? $play['type'] : sanitize_key((string) ($params['type'] ?? 'get_more_enquiries'));
    $definitions = $plays->getDefinitions();
    if (empty($definitions[$type])) {
      return '<p class="ppsgenEmpty">' . esc_html__('Play not found.', 'ppsgen') . '</p>';
    }
    $this->assign('play', $play);
    $this->assign('type', $type);
    $this->assign('definition', $definitions[$type]);
    $this->assign('available', $plays->isAvailable($type));
    $this->assign('answers', !empty($play['answers']) ? array_merge($plays->getDefaultAnswers($type), $play['answers']) : $plays->getDefaultAnswers($type));
    $this->assign('existingTags', $plays->getExistingTags());
    $this->assign('hasAutomation', (bool) $this->getFrame()->getModule('eca'));
    $this->assign('emailStatus', $this->getModel()->getEmailSendingStatus());
    $this->assign('isPro', $this->getModel()->isProAddonActive());
    return $this->renderTpl('play_setup', 'dashboard');
  }

  public function renderPlayReview($params)
  {
    $plays = $this->getPlaysService();
    $play = $plays->getPlay(sanitize_key((string) ($params['id'] ?? '')));
    if (empty($play) || ($play['status'] ?? 'draft') === 'draft') {
      return '<p class="ppsgenEmpty">' . esc_html__('Answer the questions first.', 'ppsgen') . ' <a href="' . esc_url(admin_url('admin.php?page=ppsgen&tab=plays')) . '">' . esc_html__('All plays', 'ppsgen') . '</a></p>';
    }
    $this->assign('play', $play);
    $this->assign('parts', $plays->getParts($play));
    $this->assign('automationConflict', $plays->getAutomationConflict($play));
    $this->assign('emailStatus', $this->getModel()->getEmailSendingStatus());
    $this->assign('isPro', $this->getModel()->isProAddonActive());
    return $this->renderTpl('play_review', 'dashboard');
  }

  public function renderPlaySummary($params)
  {
    $plays = $this->getPlaysService();
    $play = $plays->getPlay(sanitize_key((string) ($params['id'] ?? '')));
    if (empty($play) || ($play['status'] ?? 'draft') === 'draft') {
      return '<p class="ppsgenEmpty">' . esc_html__('Play not found.', 'ppsgen') . ' <a href="' . esc_url(admin_url('admin.php?page=ppsgen&tab=plays')) . '">' . esc_html__('All plays', 'ppsgen') . '</a></p>';
    }
    $this->assign('play', $play);
    $this->assign('definition', $plays->getDefinitions()[$play['type']] ?? []);
    $this->assign('parts', $plays->getParts($play));
    $this->assign('stats', $plays->getStats($play));
    $this->assign('people', $plays->getPeople($play));
    $this->assign('automationConflict', ($play['status'] ?? '') === 'live' ? [] : $plays->getAutomationConflict($play));
    $this->assign('isPro', $this->getModel()->isProAddonActive());
    return $this->renderTpl('play_summary', 'dashboard');
  }
}
