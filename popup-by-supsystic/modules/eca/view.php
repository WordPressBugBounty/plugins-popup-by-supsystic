<?php

if (!defined('ABSPATH')) {
  exit();
}

class EcaViewPpsgen extends BaseViewPpsgen
{
  public $templateDesignSettings;

  public $loadForPreview = false;

  private function getListRequestValue($request, $name, $default = null)
  {
    $value = $request->getVar($name, 'post', null);
    if ($value !== null && $value !== '') {
      return $value;
    }
    $value = $request->getVar($name, 'get', null);
    if ($value !== null && $value !== '') {
      return $value;
    }
    return $default;
  }

  private function getListRequestInt($request, $name, $default = 0, $min = 0)
  {
    $value = $this->getListRequestValue($request, $name, null);
    if ($value === null || $value === '') {
      return $default;
    }
    return max($min, (int) $value);
  }

  private function getListRequestDate($request, $name)
  {
    $value = $this->getListRequestValue($request, $name, '');
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? $value : '';
  }

  private function getListRequestText($request, $name, $default = '')
  {
    $value = $this->getListRequestValue($request, $name, $default);
    return is_string($value) ? trim($value) : $default;
  }

  private function getListRequestIntArray($request, $name): array
  {
    $value = $request->getVar($name, 'post', null);
    if ($value === null || $value === '') {
      $value = $request->getVar($name, 'get', null);
    }
    if ($value === null || $value === '') {
      return [];
    }
    if (!is_array($value)) {
      $value = array_filter(array_map('trim', explode(',', (string) $value)));
    }
    return array_values(array_unique(array_filter(array_map('intval', (array) $value))));
  }

  public function renderEcaOverview($params)
  {
    $html = $this->renderTpl('overview', 'eca');
    return $html;
  }

  public function renderEcaOptions($params)
  {
    $ecaOptions = $this->getModel()->getAllOptions();
    $this->assign('ecaOptions', $ecaOptions);
    $html = $this->renderTpl('options', 'eca');
    return $html;
  }

  public function renderEcaTemplates($params)
  {
    $request = $this->getFrame()->core('request');
    $page = $this->getListRequestInt($request, 'page', 1, 1);
    $perPage = $this->getListRequestInt($request, 'per_page', 100, 1);
    $orderBy = $this->getListRequestText($request, 'order_by', 'id');
    $orderDir = $this->getListRequestText($request, 'order_dir', 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $filters = [];
    if ($this->getListRequestValue($request, 'filter_id') !== null && $this->getListRequestValue($request, 'filter_id') !== '') {
      $filters['id'] = $this->getListRequestInt($request, 'filter_id');
    }
    if (!empty($this->getListRequestDate($request, 'filter_created_at'))) {
      $filters['created_at'] = $this->getListRequestDate($request, 'filter_created_at');
    }
    if ($this->getListRequestValue($request, 'filter_active') !== null && $this->getListRequestValue($request, 'filter_active') !== '') {
      $filters['active'] = $this->getListRequestText($request, 'filter_active');
    }
    if (!empty($this->getListRequestDate($request, 'filter_updated_at'))) {
      $filters['updated_at'] = $this->getListRequestDate($request, 'filter_updated_at');
    }
    $model = $this->getModel();
    $data = $model->getTemplates([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $html = $this->renderTpl('templates', 'eca');
    return $html;
  }

  public function renderEcaChains($params, string $listType = 'automation')
  {
    $request = $this->getFrame()->core('request');
    $page = $this->getListRequestInt($request, 'page', 1, 1);
    $perPage = $this->getListRequestInt($request, 'per_page', 100, 1);
    $orderBy = $this->getListRequestText($request, 'order_by', 'id');
    $orderDir = $this->getListRequestText($request, 'order_dir', 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $filters = [];
    if ($this->getListRequestValue($request, 'filter_id') !== null && $this->getListRequestValue($request, 'filter_id') !== '') {
      $filters['id'] = $this->getListRequestInt($request, 'filter_id');
    }
    if (!empty($this->getListRequestDate($request, 'filter_created_at'))) {
      $filters['created_at'] = $this->getListRequestDate($request, 'filter_created_at');
    }
    if ($this->getListRequestValue($request, 'filter_active') !== null && $this->getListRequestValue($request, 'filter_active') !== '') {
      $filters['active'] = $this->getListRequestText($request, 'filter_active');
    }
    if (!empty($this->getListRequestDate($request, 'filter_updated_at'))) {
      $filters['updated_at'] = $this->getListRequestDate($request, 'filter_updated_at');
    }
    $model = $this->getModel();
    $data = $model->getChains([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
      'type' => $listType,
    ]);
    if (!empty($data['rows'])) {
      foreach ($data['rows'] as $key => $row) {
        $chainId = (int) ($row['id'] ?? 0);
        $data['rows'][$key]['stats'] = $model->getChainStats($chainId);
        if ($listType === 'newsletter') {
          $data['rows'][$key]['newsletter'] = [
            'state' => $model->getNewsletterState($chainId),
            'audience' => $model->countNewsletterAudience($chainId),
            'tags' => $model->getNewsletterAudienceTagLabels($chainId),
            'label' => $model->getNewsletterAudienceLabel($chainId),
          ];
        }
      }
    }
    $this->assign('listType', $listType);
    $this->assign('automationLimit', $listType === 'automation' ? $model->getAutomationLimitSummary() : []);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $html = $this->renderTpl('chains', 'eca');
    return $html;
  }

  public function renderEcaFlows($params)
  {
    $request = $this->getFrame()->core('request');
    $this->getFrame()->core('component')->loadPpsgenselect();
    $page = $this->getListRequestInt($request, 'page', 1, 1);
    $perPage = $this->getListRequestInt($request, 'per_page', 100, 1);
    $orderBy = $this->getListRequestText($request, 'order_by', 'id');
    $orderDir = $this->getListRequestText($request, 'order_dir', 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $filters = [];
    if ($this->getListRequestValue($request, 'filter_id') !== null && $this->getListRequestValue($request, 'filter_id') !== '') {
      $filters['id'] = $this->getListRequestInt($request, 'filter_id');
    }
    if ($this->getListRequestValue($request, 'filter_contact_id') !== null && $this->getListRequestValue($request, 'filter_contact_id') !== '') {
      $filters['contact_id'] = $this->getListRequestInt($request, 'filter_contact_id');
    }
    $selectedChainIds = $this->getListRequestIntArray($request, 'filter_chain_ids');
    if (empty($selectedChainIds) && $this->getListRequestValue($request, 'filter_chain_id') !== null && $this->getListRequestValue($request, 'filter_chain_id') !== '') {
      $selectedChainIds = [$this->getListRequestInt($request, 'filter_chain_id')];
    }
    if (!empty($selectedChainIds)) {
      $filters['chain_ids'] = $selectedChainIds;
      if (count($selectedChainIds) === 1) {
        $filters['chain_id'] = (int) $selectedChainIds[0];
      }
    }
    if ($this->getListRequestValue($request, 'filter_chain_id') !== null && $this->getListRequestValue($request, 'filter_chain_id') !== '') {
      $filters['chain_id'] = $this->getListRequestInt($request, 'filter_chain_id');
    }
    if ($this->getListRequestValue($request, 'filter_current_step_id') !== null && $this->getListRequestValue($request, 'filter_current_step_id') !== '') {
      $filters['current_step_id'] = $this->getListRequestInt($request, 'filter_current_step_id');
    }
    if ($this->getListRequestValue($request, 'filter_status') !== null && $this->getListRequestValue($request, 'filter_status') !== '') {
      $filters['status'] = $this->getListRequestText($request, 'filter_status');
    }
    if ($this->getListRequestValue($request, 'filter_active') !== null && $this->getListRequestValue($request, 'filter_active') !== '') {
      $filters['active'] = $this->getListRequestText($request, 'filter_active');
    }
    if (!empty($this->getListRequestDate($request, 'filter_next_run_at'))) {
      $filters['next_run_at'] = $this->getListRequestDate($request, 'filter_next_run_at');
    }
    if (!empty($this->getListRequestDate($request, 'filter_created_at'))) {
      $filters['created_at'] = $this->getListRequestDate($request, 'filter_created_at');
    }
    if (!empty($this->getListRequestDate($request, 'filter_updated_at'))) {
      $filters['updated_at'] = $this->getListRequestDate($request, 'filter_updated_at');
    }
    $model = $this->getModel();
    $data = $model->getFlows([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
      'campaignOptions' => $model->getAllCampaignOptions(),
    ]);
    $html = $this->renderTpl('flow_list', 'eca');
    return $html;
  }

  public function renderEcaFlowsLogs($params)
  {
    $request = $this->getFrame()->core('request');
    $this->getFrame()->core('component')->loadPpsgenselect();
    $page = $this->getListRequestInt($request, 'page', 1, 1);
    $perPage = $this->getListRequestInt($request, 'per_page', 100, 1);
    $orderBy = $this->getListRequestText($request, 'order_by', 'id');
    $orderDir = $this->getListRequestText($request, 'order_dir', 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $filters = [];
    if ($this->getListRequestValue($request, 'filter_id') !== null && $this->getListRequestValue($request, 'filter_id') !== '') {
      $filters['id'] = $this->getListRequestInt($request, 'filter_id');
    }
    if ($this->getListRequestValue($request, 'filter_contact_id') !== null && $this->getListRequestValue($request, 'filter_contact_id') !== '') {
      $filters['contact_id'] = $this->getListRequestInt($request, 'filter_contact_id');
    }
    $selectedChainIds = $this->getListRequestIntArray($request, 'filter_chain_ids');
    if (empty($selectedChainIds) && $this->getListRequestValue($request, 'filter_chain_id') !== null && $this->getListRequestValue($request, 'filter_chain_id') !== '') {
      $selectedChainIds = [$this->getListRequestInt($request, 'filter_chain_id')];
    }
    if (!empty($selectedChainIds)) {
      $filters['chain_ids'] = $selectedChainIds;
      if (count($selectedChainIds) === 1) {
        $filters['chain_id'] = (int) $selectedChainIds[0];
      }
    }
    if ($this->getListRequestValue($request, 'filter_chain_id') !== null && $this->getListRequestValue($request, 'filter_chain_id') !== '') {
      $filters['chain_id'] = $this->getListRequestInt($request, 'filter_chain_id');
    }
    if ($this->getListRequestValue($request, 'filter_flow_id') !== null && $this->getListRequestValue($request, 'filter_flow_id') !== '') {
      $filters['flow_id'] = $this->getListRequestInt($request, 'filter_flow_id');
    }
    if ($this->getListRequestValue($request, 'filter_step_id') !== null && $this->getListRequestValue($request, 'filter_step_id') !== '') {
      $filters['step_id'] = $this->getListRequestInt($request, 'filter_step_id');
    }
    if ($this->getListRequestValue($request, 'filter_status') !== null && $this->getListRequestValue($request, 'filter_status') !== '') {
      $filters['status'] = $this->getListRequestText($request, 'filter_status');
    }
    if (!empty($this->getListRequestDate($request, 'filter_run_at'))) {
      $filters['run_at'] = $this->getListRequestDate($request, 'filter_run_at');
    }
    $model = $this->getModel();
    $data = $model->getFlowsLogs([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
      'campaignOptions' => $model->getAllCampaignOptions(),
    ]);
    $html = $this->renderTpl('flows_logs', 'eca');
    return $html;
  }

  public function renderEcaStepEvents($params)
  {
    $request = $this->getFrame()->core('request');
    $this->getFrame()->core('component')->loadPpsgenselect();
    $page = $this->getListRequestInt($request, 'page', 1, 1);
    $perPage = $this->getListRequestInt($request, 'per_page', 100, 1);
    $orderBy = $this->getListRequestText($request, 'order_by', 'id');
    $orderDir = $this->getListRequestText($request, 'order_dir', 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $filters = [];
    if ($this->getListRequestValue($request, 'filter_id') !== null && $this->getListRequestValue($request, 'filter_id') !== '') {
      $filters['id'] = $this->getListRequestInt($request, 'filter_id');
    }
    if ($this->getListRequestValue($request, 'filter_contact_id') !== null && $this->getListRequestValue($request, 'filter_contact_id') !== '') {
      $filters['contact_id'] = $this->getListRequestInt($request, 'filter_contact_id');
    }
    $selectedChainIds = $this->getListRequestIntArray($request, 'filter_chain_ids');
    if (empty($selectedChainIds) && $this->getListRequestValue($request, 'filter_chain_id') !== null && $this->getListRequestValue($request, 'filter_chain_id') !== '') {
      $selectedChainIds = [$this->getListRequestInt($request, 'filter_chain_id')];
    }
    if (!empty($selectedChainIds)) {
      $filters['chain_ids'] = $selectedChainIds;
      if (count($selectedChainIds) === 1) {
        $filters['chain_id'] = (int) $selectedChainIds[0];
      }
    }
    if ($this->getListRequestValue($request, 'filter_chain_id') !== null && $this->getListRequestValue($request, 'filter_chain_id') !== '') {
      $filters['chain_id'] = $this->getListRequestInt($request, 'filter_chain_id');
    }
    if ($this->getListRequestValue($request, 'filter_flow_id') !== null && $this->getListRequestValue($request, 'filter_flow_id') !== '') {
      $filters['flow_id'] = $this->getListRequestInt($request, 'filter_flow_id');
    }
    if ($this->getListRequestValue($request, 'filter_step_id') !== null && $this->getListRequestValue($request, 'filter_step_id') !== '') {
      $filters['step_id'] = $this->getListRequestInt($request, 'filter_step_id');
    }
    if ($this->getListRequestValue($request, 'filter_event_type') !== null && $this->getListRequestValue($request, 'filter_event_type') !== '') {
      $filters['event_type'] = $this->getListRequestText($request, 'filter_event_type');
    }
    if (!empty($this->getListRequestText($request, 'filter_clicked_url'))) {
      $filters['clicked_url'] = $this->getListRequestText($request, 'filter_clicked_url');
    }
    if (!empty($this->getListRequestText($request, 'filter_user_agent'))) {
      $filters['user_agent'] = $this->getListRequestText($request, 'filter_user_agent');
    }
    if (!empty($this->getListRequestText($request, 'filter_referrer'))) {
      $filters['referrer'] = $this->getListRequestText($request, 'filter_referrer');
    }
    if (!empty($this->getListRequestText($request, 'filter_ip_address'))) {
      $filters['ip_address'] = $this->getListRequestText($request, 'filter_ip_address');
    }
    if (!empty($this->getListRequestDate($request, 'filter_created_at'))) {
      $filters['created_at'] = $this->getListRequestDate($request, 'filter_created_at');
    }
    $model = $this->getModel();
    $data = $model->getStepEvents([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
      'campaignOptions' => $model->getAllCampaignOptions(),
    ]);
    $html = $this->renderTpl('step_events', 'eca');
    return $html;
  }

  public function renderEcaConversions($params)
  {
    $request = $this->getFrame()->core('request');
    $this->getFrame()->core('component')->loadPpsgenselect();
    $page = $this->getListRequestInt($request, 'page', 1, 1);
    $perPage = $this->getListRequestInt($request, 'per_page', 100, 1);
    $orderBy = $this->getListRequestText($request, 'order_by', 'converted_at');
    $orderDir = $this->getListRequestText($request, 'order_dir', 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    $filters = [];
    foreach (['filter_id' => 'id', 'filter_contact_id' => 'contact_id', 'filter_step_id' => 'step_id', 'filter_flow_id' => 'flow_id', 'filter_click_event_id' => 'click_event_id'] as $requestKey => $filterKey) {
      if ($this->getListRequestValue($request, $requestKey) !== null && $this->getListRequestValue($request, $requestKey) !== '') {
        $filters[$filterKey] = $this->getListRequestInt($request, $requestKey);
      }
    }
    $selectedChainIds = $this->getListRequestIntArray($request, 'filter_chain_ids');
    if (empty($selectedChainIds) && $this->getListRequestValue($request, 'filter_chain_id') !== null && $this->getListRequestValue($request, 'filter_chain_id') !== '') {
      $selectedChainIds = [$this->getListRequestInt($request, 'filter_chain_id')];
    }
    if (!empty($selectedChainIds)) {
      $filters['chain_ids'] = $selectedChainIds;
      if (count($selectedChainIds) === 1) {
        $filters['chain_id'] = (int) $selectedChainIds[0];
      }
    }
    foreach (['filter_conversion_email' => 'conversion_email', 'filter_attribution_method' => 'attribution_method', 'filter_conversion_key' => 'conversion_key', 'filter_external_id' => 'external_id', 'filter_status' => 'status', 'filter_source' => 'source', 'filter_currency' => 'currency', 'filter_product_id' => 'product_id', 'filter_product_name' => 'product_name'] as $requestKey => $filterKey) {
      $value = $this->getListRequestText($request, $requestKey);
      if ($value !== '') {
        $filters[$filterKey] = $value;
      }
    }
    if (!empty($this->getListRequestDate($request, 'filter_converted_from'))) {
      $filters['converted_from'] = $this->getListRequestDate($request, 'filter_converted_from');
    }
    if (!empty($this->getListRequestDate($request, 'filter_converted_to'))) {
      $filters['converted_to'] = $this->getListRequestDate($request, 'filter_converted_to');
    }
    $model = $this->getModel();
    $data = $model->getConversions([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'summary' => $data['summary'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
      'campaignOptions' => $model->getAllCampaignOptions(),
    ]);
    $html = $this->renderTpl('conversions', 'eca');
    return $html;
  }

  public function renderEcaChainEdit($params)
  {
    $request = $this->getFrame()->core('request');
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    if (!empty($objId)) {
      $ecaOptions = $this->getModel()->getChainById($objId);
      if (!empty($ecaOptions)) {
        $ecaOptions['settings'] = $this->getModel()->_prepareValueFromStore($ecaOptions['settings'], 'json');
        $steps = $this->getModel()->getChainStep($objId);
        // $fieldsByKey = [];
        // if (!empty($fields)) {
        //   foreach ($fields as $key => $field) {
        //     $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
        //   }
        // }
        $ecaOptions['steps'] = $steps;
        $options = $ecaOptions;
        wp_localize_script('ppsgen-backend-eca-chain-step-edit', 'ppsgenEcaChainStepsData', [
          'steps' => $steps,
          'i18n' => [
            'reset_step_logs' => __('Reset Step Logs', 'ppsgen'),
            'reset_step_events' => __('Reset Step Events', 'ppsgen'),
            'current_state' => __('Current State', 'ppsgen'),
            'users_on_this_step' => __('Users on This Step', 'ppsgen'),
            'waiting_right_now' => __('Waiting Right Now', 'ppsgen'),
            'processing_right_now' => __('Processing Right Now', 'ppsgen'),
            'step_progress' => __('Step Progress', 'ppsgen'),
            'passed_this_step' => __('Passed This Step', 'ppsgen'),
            'emails_sent' => __('Emails Sent', 'ppsgen'),
            'finished_on_this_step' => __('Finished on This Step', 'ppsgen'),
            'skipped_this_step' => __('Skipped This Step', 'ppsgen'),
            'failures_and_dropoff' => __('Failures & Drop-Off', 'ppsgen'),
            'condition_false_users' => __('Condition False Users', 'ppsgen'),
            'condition_false_checks' => __('Condition False Checks', 'ppsgen'),
            'exited_on_this_step' => __('Exited on This Step', 'ppsgen'),
            'unsubscribed_on_this_step' => __('Unsubscribed on This Step', 'ppsgen'),
            'errors' => __('Errors', 'ppsgen'),
            'engagement_overview' => __('Engagement Overview', 'ppsgen'),
            'total_events' => __('Total Events', 'ppsgen'),
            'engaged_contacts' => __('Engaged Contacts', 'ppsgen'),
            'opens' => __('Opens', 'ppsgen'),
            'total_opens' => __('Total Opens', 'ppsgen'),
            'suspicious_opens' => __('Suspicious Opens', 'ppsgen'),
            'live_opens' => __('Live Opens', 'ppsgen'),
            'clicks' => __('Clicks', 'ppsgen'),
            'total_clicks' => __('Total Clicks', 'ppsgen'),
            'suspicious_clicks' => __('Suspicious Clicks', 'ppsgen'),
            'live_clicks' => __('Live Clicks', 'ppsgen'),
          ],
        ]);
        wp_enqueue_script('ppsgen-backend-eca-chain-step-edit');
      }
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Campaign with specific ID for editing', 'ppsgen');
    }
    if (empty($objId)) {
      return esc_html__('You must create Campaign before editing', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $activeTab = $this->getListRequestText($request, 'chain_edit_active_tab', 'steps');
    $activeTab = !empty($activeTab) ? $activeTab : 'steps';
    $this->assign('activeTab', $activeTab);
    $tabs = [
      'steps' => ['title' => __('Emails & Steps', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('eca.chain.edit.steps_html', $options)],
      'options' => ['title' => __('Options', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('eca.chain.edit.options_html', $options)],
      'contacts' => ['title' => __('Contacts', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('eca.chain.edit.contacts_html', $options)],
      'stats' => ['title' => __('Stats', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('eca.chain.edit.stats_html', $options)],
    ];
    $tabs = $this->getFrame()
      ->core('eventDispatcher')
      ->applyFiltersOnce('eca.chain.edit.tabs', ['tabs' => $tabs, 'options' => $options]);
    $isNewsletter = $this->getModel()->isNewsletter((int) $objId);
    if ($isNewsletter) {
      $newsletterTitles = [
        'steps' => __('Email', 'ppsgen'),
        'options' => __('Send to & options', 'ppsgen'),
        'contacts' => __('Recipients', 'ppsgen'),
      ];
      foreach ($newsletterTitles as $tabKey => $title) {
        if (!empty($tabs['tabs'][$tabKey])) {
          $tabs['tabs'][$tabKey]['title'] = $title;
        }
      }
      $this->assign('newsletter', [
        'state' => $this->getModel()->getNewsletterState((int) $objId),
        'audience' => $this->getModel()->countNewsletterAudience((int) $objId),
        'tags' => $this->getModel()->getNewsletterAudienceTagLabels((int) $objId),
        'label' => $this->getModel()->getNewsletterAudienceLabel((int) $objId),
        'list_id' => $this->getModel()->getNewsletterListId((int) $objId),
        'click_tag' => $this->getModel()->getNewsletterClickTag((int) $objId),
        'extras' => $this->getModel()->getNewsletterExtras((int) $objId),
        'beyond_limit' => $this->getModel()->countNewsletterAudienceBeyondLimit((int) $objId),
        'can_tag_clicks' => $this->getFrame()->getModule('dashboard')->getModel()->isProAddonActive() && !(function_exists('ppsgen_is_pro_license_frozen') && ppsgen_is_pro_license_frozen()),
        'all_tags' => array_values((array) $this->getFrame()->getModule('contact')->getSubmod('contact_tag')->getModel()->getAllContactTags(true)),
        'lists' => $this->getFrame()->getModule('contact') && method_exists($this->getFrame()->getModule('contact')->getModel(), 'getSavedContactLists')
          ? $this->getFrame()->getModule('contact')->getModel()->getSavedContactLists()
          : [],
      ]);
    }
    $this->assign('isNewsletter', $isNewsletter);
    $this->assign('tabs', $tabs['tabs']);
    $html = $this->renderTpl('chain_edit', 'eca');
    return $html;
  }

  public function templateEditBuildFieldsTree(array $elements, $parent_key = '', array $visited = [])
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
        $children = $this->templateEditBuildFieldsTree($elements, $fieldKey, $newVisited);
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

  public function templateEditRenderField($field, $parent_types, $template = 'template')
  {
    $settings = $field['settings'];
    $key = esc_attr($settings['field_key'] ?? '');
    $type = esc_attr($settings['field_type'] ?? '');
    $classes = sprintf('ppsgen-%s-col ppsgen-%s-type-%s', $template, $template, $type);
    $html = '<div' . ' data-field-type="' . $type . '"' . ' data-field-key="' . $key . '"' . ' class="' . esc_attr($classes) . '"' . ' draggable="false">' . '<div class="ppsgen-' . esc_html($template) . '-label">' . esc_html($type) . '</div>';
    if (in_array($type, $parent_types, true)) {
      $html .= '<div class="ppsgen-' . esc_html($template) . '-col-inner">';
      if (!empty($field['children'])) {
        foreach ($field['children'] as $child) {
          $html .= $this->templateEditRenderField($child, $parent_types, $template);
        }
      }
      $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
  }

  public function renderTemplateById($template_id, $message)
  {
    $template = $this->getModel()->getTemplateById($template_id);
    if (!empty($template)) {
      $template['settings'] = $this->getModel()->_prepareValueFromStore($template['settings'], 'json');
      $template['design'] = $this->getModel()->_prepareValueFromStore($template['design'], 'json');
      $fields = $this->getModel()->getTemplateFields($template_id);
      $fieldsByKey = [];
      if (!empty($fields)) {
        foreach ($fields as $key => $field) {
          $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
        }
      }
      $template['fields'] = $fieldsByKey;
      $templateContent = $this->renderTemplateByData(['template' => $template]);
      $templateContent = str_replace('[email_content]', $message, $templateContent);
      $templateContent = $this->getAdditionalFieldsHtml($templateContent);
      $templateContent .= $this->getPoweredInfo();
      return $templateContent;
    }
    return $message;
  }

  public function renderChainPreviewByData($data)
  {
    if (empty($data['fields'])) {
      return false;
    }

    $component = $this->getComponent();
    $helper = $this->getHelper();

    $template_id = !empty($data['template_id']) ? $data['template_id'] : 1;
    $templateContent = '[email_content]';
    $template = $this->getModel()->getTemplateById($template_id);
    if (!empty($template)) {
      $template['settings'] = $this->getModel()->_prepareValueFromStore($template['settings'], 'json');
      $template['design'] = $this->getModel()->_prepareValueFromStore($template['design'], 'json');
      $fields = $this->getModel()->getTemplateFields($template_id);
      $fieldsByKey = [];
      if (!empty($fields)) {
        foreach ($fields as $key => $field) {
          $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
        }
      }
      $template['fields'] = $fieldsByKey;
      $templateContent = $this->renderTemplateByData(['template' => $template]);
    }

    $stepContent = '';
    $stepFields = $data['fields'];
    if (!empty($stepFields)) {
      foreach ($stepFields as $key => $field) {
        $stepFields[$key]['settings'] = $field;
      }
      $tree = $this->templateEditBuildFieldsTree($stepFields);
      foreach ($tree as $field) {
        $fieldHtml = $this->templateFrontendRenderField($field);
        $fieldHtml = htmlspecialchars_decode(stripslashes($fieldHtml));
        $stepContent .= $helper->wpKses($fieldHtml);
      }
    }

    $templateContent = str_replace('[email_content]', $stepContent, $templateContent);
    $templateContent = $this->getAdditionalFieldsHtml($templateContent);
    $templateContent .= $this->getPoweredInfo();

    return $templateContent;
  }

  public function getPoweredInfo()
  {
    // Free email branding, see ppsgen_get_email_branding_html()
    return function_exists('ppsgen_get_email_branding_html') ? ppsgen_get_email_branding_html() : '';
  }

  public function getAdditionalFieldsHtml($html)
  {
    return $html;
  }

  public function setLoadForPreview($bool)
  {
    $this->loadForPreview = $bool;
  }

  public function renderTemplateByData($data)
  {
    if (empty($data['template']['fields']) || empty($data['template']['design'])) {
      return false;
    }

    $component = $this->getComponent();
    $helper = $this->getHelper();

    $fields = !empty($data['template']['fields']) ? $data['template']['fields'] : [];
    $design = !empty($data['template']['design']) ? $data['template']['design'] : [];

    $this->templateDesignSettings = $design;

    $this->assign('component', $component);
    $this->assign('helper', $helper);
    $this->assign('fields', $fields);
    $this->assign('design', $design);

    $html = $this->renderTpl('template_render', 'eca');

    return $html;
  }

  public function templateFrontendRenderField($field)
  {
    $component = $this->getComponent();
    $fieldKey = $field['settings']['field_key'] ?? '';
    $fieldType = $field['settings']['field_type'] ?? '';

    $html = '';
    $cssWidth = '';
    $cssMaxWidth = '';
    $displayType = '';

    $widthSelect = $component->value($field['settings']['width_select'] ?? null, 'auto');
    $width = $component->value($field['settings']['width'] ?? null, '100');
    $widthUnit = $component->cvalue($field['settings']['width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssWidth .= "width: {$width}{$widthUnit};";
    } elseif ($widthSelect == 'auto') {
      $cssWidth .= 'width: auto;';
    } else {
      $cssWidth .= "width: {$widthSelect};";
    }

    $widthSelect = $component->value($field['settings']['max_width_select'] ?? null, 'auto');
    $width = $component->value($field['settings']['max_width'] ?? null, '100');
    $widthUnit = $component->cvalue($field['settings']['max_width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssMaxWidth .= "max-width: {$width}{$widthUnit};";
    } elseif ($widthSelect == 'auto') {
      $cssMaxWidth .= 'max-width: auto;';
    } else {
      $cssMaxWidth .= "max-width: {$widthSelect};";
    }

    $displayType = $component->value($field['settings']['display_type'] ?? null, '');
    if (!empty($displayType)) {
      $displayType = 'display: ' . esc_attr($displayType) . '; ';
    }
    // Live preview only: lets a click in the preview select the element in the builder. Sent emails stay clean.
    $fkeyAttr = !empty($this->loadForPreview) && $fieldKey !== '' ? ' data-fkey="' . esc_attr($fieldKey) . '"' : '';

    if ($fieldType == 'row') {
      $cssInline = '';
      $fieldStyles = $this->templateBuildFieldStyles($field);
      $html .= '<table' . $fkeyAttr . ' cellspacing="0" cellpadding="0" style="border-collapse:collapse; ' . esc_attr($cssWidth) . ' ' . esc_attr($cssMaxWidth) . ' ' . esc_attr($fieldStyles) . '">';
      $html .= '<tr>';
      if (!empty($field['children'])) {
        foreach ($field['children'] as $child) {
          $html .= $this->templateFrontendRenderField($child);
        }
      }
      $html .= '</tr>';
      $html .= '</table>';
    } elseif ($fieldType == 'column') {
      $cssInline = '';
      $cssColAlign = $component->value($field['settings']['horizontal_align'] ?? null, '');
      $cssColVAlign = $component->value($field['settings']['vertical_align'] ?? null, '');
      if (!empty($cssColVAlign)) {
        $cssColVAlign = 'vertical-align: ' . esc_attr($cssColVAlign) . '; ';
      }
      $fieldStyles = $this->templateBuildFieldStyles($field);
      $html .= '<td' . $fkeyAttr . ' align="' . esc_attr($cssColAlign) . '" style="' . esc_attr($cssWidth) . ' ' . esc_attr($cssColVAlign) . ' ' . esc_attr($fieldStyles) . '">';
      if (!empty($field['children'])) {
        foreach ($field['children'] as $child) {
          $html .= $this->templateFrontendRenderField($child);
        }
      }
      $html .= '</td>';
    } else {
      if ($fieldType == 'svg_icon' || $fieldType == 'image') {
        $cssWidth = 'width:100%;';
        $cssMaxWidth = '';
      }
      $html .= '<div' . $fkeyAttr . ' style="' . esc_attr($displayType) . ' ' . esc_attr($cssWidth) . ' ' . esc_attr($cssMaxWidth) . '">';
      $html .= $this->templateFrontendRenderFieldContent($field);
      $html .= '</div>';
    }

    return $html;
  }

  public function templateFrontendRenderFieldContent($field)
  {
    $component = $this->getComponent();
    $type = $field['settings']['field_type'] ?? '';
    $fieldStyles = $this->templateBuildFieldStyles($field);

    $widthSelect = $component->value($field['settings']['width_select'] ?? null, 'auto');
    $width = $component->value($field['settings']['width'] ?? null, '100');
    $widthUnit = $component->cvalue($field['settings']['width_unit'] ?? null, '%');
    $cssWidth = '';
    $cssMaxWidth = '';
    if ($widthSelect == 'manual') {
      $cssWidth .= "width: {$width}{$widthUnit};";
    } elseif ($widthSelect == 'auto') {
      $cssWidth .= 'width: auto;';
    } else {
      $cssWidth .= "width: {$widthSelect};";
    }
    $widthSelect = $component->value($field['settings']['max_width_select'] ?? null, 'auto');
    $width = $component->value($field['settings']['max_width'] ?? null, '100');
    $widthUnit = $component->cvalue($field['settings']['max_width_unit'] ?? null, '%');
    if ($widthSelect == 'manual') {
      $cssMaxWidth .= "max-width: {$width}{$widthUnit};";
    } elseif ($widthSelect == 'auto') {
      $cssMaxWidth .= 'max-width: auto;';
    } else {
      $cssMaxWidth .= "max-width: {$widthSelect};";
    }
    $alignInContainer = $component->nvalue($field['settings']['text_align'] ?? 'left');
    $formatTemplateUrl = static function ($url) {
      $url = (string) $url;
      if ($url === '') {
        return '';
      }
      if (strpos($url, '[') !== false && strpos($url, ']') !== false) {
        return esc_attr($url);
      }
      return esc_url($url);
    };

    switch ($type) {
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
        $iconHtml = '<div align="' . esc_attr($alignInContainer) . '">';
        if (!empty($iconUrl)) {
          $iconHtml .= '<a style="display: block;width: 100%;height: auto;max-width: 100%;max-height: 100%;" ';
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
          $svgContent = str_replace('<svg', '<svg style="display: block; ' . esc_attr($cssWidth) . ' ' . esc_attr($cssMaxWidth) . ' --primary-svg-color:' . esc_attr($iconColor) . ';"', $svgContent);
          $iconHtml .= $svgContent;
        }
        if (!empty($iconUrl)) {
          $iconHtml .= '</a>';
        }
        $iconHtml .= '</div>';
        return $iconHtml;
      case 'image':
        $src = $component->nvalue($field['settings']['image_src'] ?? '');
        $link = $component->nvalue($field['settings']['image_url'] ?? '');
        $title = $component->nvalue($field['settings']['image_title'] ?? '');
        $target = $component->nvalue($field['settings']['image_target'] ?? '');
        if (!empty($src)) {
          $parsed = wp_parse_url($src);
          if (empty($parsed['scheme'])) {
            $src = home_url($src);
          }
        }
        $html = '<div align="' . esc_attr($alignInContainer) . '">';
        $html .= '<a ';
        if (!empty($target)) {
          $html .= ' target="' . esc_attr($target) . '"';
        }
        if (!empty($link)) {
          $html .= ' href="' . $formatTemplateUrl($link) . '"';
        }
        $html .= '>';
        $html .= '<img style="display: block; ' . esc_attr($cssWidth) . ' ' . esc_attr($cssMaxWidth) . esc_attr($fieldStyles) . '" title="' . esc_attr($title) . '" src="' . esc_url($src) . '"></a>';
        $html .= '</div>';
        return $html;
      case 'title':
        $titleTagArr = ['h1' => ['<h1 style="">', '</h1>'], 'h2' => ['<h2 style="">', '</h2>'], 'h3' => ['<h3 style="">', '</h3>'], 'h4' => ['<h4 style="">', '</h4>'], 'h5' => ['<h5 style="">', '</h5>'], 'h6' => ['<h6 style="">', '</h6>']];
        $titleTagVal = $component->nvalue($field['settings']['title_tag'] ?? 'h1');
        $titleTag = !empty($titleTagArr[$titleTagVal]) ? $titleTagArr[$titleTagVal] : $titleTagArr['h1'];
        $titleTagOpen = $titleTag[0];
        $titleTagClose = $titleTag[1];
        $titleText = $component->nvalue($field['settings']['title_text'] ?? '');
        $titleTextAlign = $component->nvalue($field['settings']['text_align'] ?? 'left');
        $titleTextAlignStyle = 'style="text-align:' . esc_attr($titleTextAlign);
        $titleTagOpen = str_replace('style="', $titleTextAlignStyle, $titleTagOpen);
        $fieldStyles = 'style="' . $fieldStyles;
        $titleTagOpen = str_replace('style="', $fieldStyles, $titleTagOpen);
        $html = $titleTagOpen . $titleText . $titleTagClose;
        return $html;
      case 'textarea':
        $content = $component->nvalue($field['settings']['html_textarea_content'] ?? '');
        $content = apply_filters('the_content', $content);
        $content = '<div style="' . esc_attr($fieldStyles) . '">' . $content . '</div>';
        return $content;
      case 'email_content':
        $content = '<div style="' . esc_attr($fieldStyles) . '">[email_content]</div>';
        return $content;
      case 'unsubscribe':
        $unsubscribeText = $component->nvalue($field['settings']['unsubscribe_text'] ?? '');
        $btnHtml = '<a ';
        if (!empty($iconTarget)) {
          $btnHtml .= ' target="blank"';
        }
        $btnHtml .= ' href="[unsubscribe_url]" style="display:block; width:100%;' . esc_attr($fieldStyles) . '  "';
        $btnHtml .= '>';
        $btnHtml .= esc_html($unsubscribeText);
        $btnHtml .= '</a>';
        return $btnHtml;
      case 'button':
        $buttonText = $component->nvalue($field['settings']['button_text'] ?? '');
        $buttonTarget = $component->nvalue($field['settings']['button_target'] ?? '');
        $buttonUrl = $component->nvalue($field['settings']['button_url'] ?? '');
        $buttonAlign = $component->nvalue($field['settings']['text_align'] ?? '');
        $buttonAlign = !empty($buttonAlign) ? ' text-align:' . esc_attr($buttonAlign) . '; ' : '';
        $btnHtml = '<a ';
        if (!empty($buttonTarget)) {
          $btnHtml .= ' target="' . esc_attr($buttonTarget) . '"';
        }
        if (!empty($buttonUrl)) {
          $btnHtml .= ' href="' . $formatTemplateUrl($buttonUrl) . '"';
        }
        $btnHtml .= ' style="display:block; width:100%; ' . esc_attr($buttonAlign) . ' ' . esc_attr($fieldStyles) . '  "';
        $btnHtml .= '>';
        $btnHtml .= esc_html($buttonText);
        $btnHtml .= '</a>';
        return $btnHtml;
      default:
        return '';
    }
  }

  public function templateBuildFieldStyles($field)
  {
    $component = $this->getComponent();
    $type = $field['settings']['field_type'] ?? '';
    $settings = $field['settings'] ?? [];
    $styles = ' box-sizing: border-box;';

    $paddingT = $component->value($settings['padding_top'] ?? null, '0');
    $paddingTU = $component->cvalue($settings['padding_top_unit'] ?? null, 'px');
    $paddingB = $component->value($settings['padding_bottom'] ?? null, '0');
    $paddingBU = $component->cvalue($settings['padding_bottom_unit'] ?? null, 'px');
    $paddingL = $component->value($settings['padding_left'] ?? null, '0');
    $paddingLU = $component->cvalue($settings['padding_left_unit'] ?? null, 'px');
    $paddingR = $component->value($settings['padding_right'] ?? null, '0');
    $paddingRU = $component->cvalue($settings['padding_right_unit'] ?? null, 'px');
    $styles .= "
      padding-top: {$paddingT}{$paddingTU};
      padding-bottom: {$paddingB}{$paddingBU};
      padding-left: {$paddingL}{$paddingLU};
      padding-right: {$paddingR}{$paddingRU};
      ";

    if (!in_array($type, ['row', 'column'])) {
      $marginT = $component->value($settings['margin_top'] ?? null, '0');
      $marginTU = $component->cvalue($settings['margin_top_unit'] ?? null, 'px');
      $marginB = $component->value($settings['margin_bottom'] ?? null, '0');
      $marginBU = $component->cvalue($settings['margin_bottom_unit'] ?? null, 'px');
      $marginL = $component->value($settings['margin_left'] ?? null, '0');
      $marginLU = $component->cvalue($settings['margin_left_unit'] ?? null, 'px');
      $marginR = $component->value($settings['margin_right'] ?? null, '0');
      $marginRU = $component->cvalue($settings['margin_right_unit'] ?? null, 'px');
      $styles .= "
        margin-top: {$marginT}{$marginTU};
        margin-bottom: {$marginB}{$marginBU};
        margin-left: {$marginL}{$marginLU};
        margin-right: {$marginR}{$marginRU};
        ";
    }

    if (in_array($type, ['title', 'button', 'textarea', 'unsubscribe'])) {
      $fontFamilyKey = $component->value($settings['font_family'] ?? null, '');
      if (!empty($fontFamilyKey)) {
        $styles .= ' ' . $component->getEmailFonts(false, $fontFamilyKey) . ' ';
      }
      $fontSize = $component->value($settings['font_size'] ?? null, '');
      $fontSizeUnit = $component->cvalue($settings['font_size_unit'] ?? null, 'px');
      $fontLhSize = $component->value($settings['font_lh'] ?? null, '');
      $fontLhSizeUnit = $component->cvalue($settings['font_lh_unit'] ?? null, 'px');
      $fontColor = $component->value($settings['font_color'] ?? null, '');
      $fontStyle = $component->value($settings['font_style'] ?? null, '');
      $fontWeight = $component->value($settings['font_weight'] ?? null, '');
      if (!empty($fontSize)) {
        $styles .= "font-size: {$fontSize}{$fontSizeUnit};";
      }
      if (!empty($fontLhSize)) {
        $styles .= "line-height: {$fontLhSize}{$fontLhSizeUnit};";
      }
      if (!empty($fontWeight)) {
        $styles .= "font-weight: {$fontWeight};";
      }
      if (!empty($fontStyle)) {
        $styles .= "font-style: {$fontStyle};";
      }
      if (!empty($fontColor)) {
        $styles .= "color: {$fontColor};";
      }
    }

    if (!in_array($type, ['title', 'button'])) {
      $textAlign = $component->nvalue($settings['text_align'] ?? '');
      if (!empty($textAlign)) {
        $styles .= "text-align: {$textAlign};";
      }
    }

    $border = $component->value($settings['border'] ?? null, '');
    $borderUnit = $component->cvalue($settings['border_unit'] ?? null, 'px');
    $borderStyle = $component->value($settings['border_style'] ?? null, 'solid');
    $borderColor = $component->value($settings['border_color'] ?? null, '#dddddd');
    if (!empty($border)) {
      $styles .= " border: {$border}{$borderUnit} {$borderStyle} {$borderColor};";
    }

    if (!in_array($type, ['row', 'column'])) {
      $border = $component->value($settings['border_radius'] ?? null, '');
      $borderUnit = $component->cvalue($settings['border_radius_unit'] ?? null, 'px');
      if (!empty($border)) {
        $styles .= "border-radius: {$border}{$borderUnit};";
      }
    }

    $background = $component->value($settings['background'] ?? null, '');
    if (!empty($background)) {
      $styles .= " background: {$background}; background-color: {$background};";
    }

    if (in_array($type, ['title', 'button', 'unsubscribe'])) {
      $textDecoration = $component->value($settings['link_text_decoration'] ?? null, '');
      if (!empty($textDecoration)) {
        $styles .= " text-decoration: {$textDecoration};";
      }
    }

    if ($type == 'row' && !empty($this->templateDesignSettings)) {
      $design = $this->templateDesignSettings;
      $fontSize = $component->value($design['template_font_size'] ?? null, '');
      $fontSizeUnit = $component->cvalue($design['template_font_size_unit'] ?? null, 'px');
      $fontColor = $component->value($design['template_font_color'] ?? null, '');
      $fontStyle = $component->value($design['template_font_style'] ?? null, '');
      $fontWeight = $component->value($design['template_font_weight'] ?? null, '');
      $fontLh = $component->value($design['template_font_lh_size'] ?? null, '');
      $fontLhUnit = $component->cvalue($design['template_font_lh_size_unit'] ?? null, 'px');
      if (!empty($fontSize)) {
        $styles .= "font-size: {$fontSize}{$fontSizeUnit};";
      }
      if (!empty($fontLh)) {
        $styles .= "line-height: {$fontLh}{$fontLhUnit};";
      }
      if (!empty($fontWeight)) {
        $styles .= "font-weight: {$fontWeight};";
      }
      if (!empty($fontStyle)) {
        $styles .= "font-style: {$fontStyle};";
      }
      if (!empty($fontColor)) {
        $styles .= "color: {$fontColor};";
      }
    }

    return $styles;
  }

  public function renderEcaTemplateEdit($params)
  {
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    if (!empty($objId)) {
      $ecaOptions = $this->getModel()->getTemplateById($objId);
      if (!empty($ecaOptions)) {
        $ecaOptions['settings'] = $this->getModel()->_prepareValueFromStore($ecaOptions['settings'], 'json');
        $ecaOptions['design'] = $this->getModel()->_prepareValueFromStore($ecaOptions['design'], 'json');
        $fields = $this->getModel()->getTemplateFields($objId);
        $fieldsByKey = [];
        if (!empty($fields)) {
          foreach ($fields as $key => $field) {
            $fieldsByKey[$field['fkey']] = $fields[$key]['settings'];
          }
        }
        $ecaOptions['fields'] = $fields;
        $options = $ecaOptions;
        wp_localize_script('ppsgen-backend-eca-template-edit', 'ppsgenEcaTemplateData', [
          'fields' => $fieldsByKey,
        ]);
        wp_enqueue_script('ppsgen-backend-eca-template-edit');
      }
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Template with specific ID for editing', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $tabs = [
      'template' => ['title' => __('Template', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('eca.template.edit.template_html', $options)],
      'options' => ['title' => __('Options', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('eca.template.edit.options_html', $options)],
      'design' => ['title' => __('Design', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('eca.template.edit.design_html', $options)],
    ];
    $tabs = $this->getFrame()
      ->core('eventDispatcher')
      ->applyFiltersOnce('eca.edit.tabs', ['tabs' => $tabs, 'options' => $options]);
    $this->assign('tabs', $tabs['tabs']);
    $html = $this->renderTpl('template_edit', 'eca');
    return $html;
  }

  private function parseEcaActionHash(string $hash)
  {
    $decoded = base64_decode(strtr($hash, '-_', '+/'));
    if (!$decoded) {
      return false;
    }
    $parts = explode('|', $decoded);
    if (count($parts) !== 8) {
      return false;
    }
    [$contactId, $flowId, $chainId, $stepId, $action, $encodedUrl, $timestamp, $signature] = $parts;
    $payload = implode('|', [$contactId, $flowId, $chainId, $stepId, $action, $encodedUrl, $timestamp]);
    $expectedSignature = hash_hmac('sha256', $payload, wp_salt('auth'));
    if (!hash_equals($expectedSignature, $signature)) {
      return false;
    }
    if ($timestamp < time() - DAY_IN_SECONDS * 7) {
      return false;
    }
    if ($action == 'open') {
      $data = [
        'contactId' => (int) $contactId,
        'flowId' => (int) $flowId,
        'chainId' => (int) $chainId,
        'stepId' => (int) $stepId,
        'action' => $action,
        'timestamp' => (int) $timestamp,
      ];
    } else {
      $data = [
        'contactId' => (int) $contactId,
        'flowId' => (int) $flowId,
        'chainId' => (int) $chainId,
        'stepId' => (int) $stepId,
        'action' => $action,
        'clicked_url' => $encodedUrl !== '-' ? rawurldecode($encodedUrl) : '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
        'timestamp' => (int) $timestamp,
      ];
    }
    return $data;
  }

  public function getEcaUnsubscribeActionFromEmail()
  {
    $hash = function_exists('ppsgen_get_eca_tracking_request_value')
      ? ppsgen_get_eca_tracking_request_value('unsubscribe')
      : $this->getFrame()->core('request')->getVar('ppsgen_eca_unsubscribe');
    if (empty($hash)) {
      return;
    }
    $data = $this->getModel()->parseUnsubscribeHash($hash);
    if ($data === false) {
      return;
    }
    if (empty($this->getModel()->isEcaTrackingRequestAllowedForChain('unsubscribe', (int) $data['chain_id']))) {
      return;
    }
    if (empty($this->getModel()->canUnsubscribeContact($data['contact_id'], $data['chain_id']))) {
      return;
    }
    $moduleAssetsUrl = PPSGEN_MODULES_URL . 'eca/assets/';
    wp_register_style('ppsgen-frontend-eca-unsubscribe', $moduleAssetsUrl . 'css/frontend.unsubscribe.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-frontend-eca-unsubscribe');
    wp_register_script('ppsgen-frontend-eca-unsubscribe', $moduleAssetsUrl . 'js/frontend.unsubscribe.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-frontend-eca-unsubscribe');
    $component = $this->getComponent();
    $this->assign('hash', $hash);
    $html = $this->renderTpl('frontend_unsubscribe', 'eca');
    $component->echoEscapedHtml($html);
  }

  public function getEcaActionFromEmail()
  {
    $ecaActionHash = function_exists('ppsgen_get_eca_tracking_request_value')
      ? ppsgen_get_eca_tracking_request_value('action')
      : $this->getFrame()->core('request')->getVar('ppsgen_eca_action');
    if (empty($ecaActionHash)) {
      return;
    }
    $data = $this->parseEcaActionHash($ecaActionHash);
    if (!empty($data)) {
      if (empty($this->getModel()->isEcaTrackingRequestAllowedForChain('action', (int) $data['chainId']))) {
        return;
      }
      if ($data['action'] === 'open') {
        $this->getModel()->addStepEvents(['contact_id' => $data['contactId'], 'flow_id' => $data['flowId'], 'chain_id' => $data['chainId'], 'step_id' => $data['stepId'], 'event_type' => 'open']);
        $this->sendPixel();
      }
      if ($data['action'] === 'click') {
        $eventId = $this->getModel()->addStepEvents([
          'contact_id' => $data['contactId'],
          'flow_id' => $data['flowId'],
          'chain_id' => $data['chainId'],
          'step_id' => $data['stepId'],
          'referrer' => $data['referrer'],
          'user_agent' => $data['user_agent'],
          'ip_address' => $data['ip_address'],
          'clicked_url' => $data['clicked_url'],
          'event_type' => 'click',
        ]);
        // Whoever clicks a link in our email is that contact on the website too
        if (!empty($eventId) && function_exists('ppsgen_remember_visitor')) {
          ppsgen_remember_visitor((int) $data['contactId']);
        }
        if (!empty($eventId)) {
          $this->getModel()->rememberEcaBrowserAttribution([
            'contact_id' => $data['contactId'],
            'flow_id' => $data['flowId'],
            'chain_id' => $data['chainId'],
            'step_id' => $data['stepId'],
            'click_event_id' => (int) $eventId,
          ]);
        }
      }
    }
  }

  public function sendPixel(): void
  {
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
    exit();
  }

  public function renderEcaEditTemplateTemplate($options)
  {
    $html = $this->renderTpl('template_edit_template', 'eca');
    return $html;
  }
  public function renderEcaEditTemplateOptions($params)
  {
    $html = $this->renderTpl('template_edit_options', 'eca');
    return $html;
  }
  public function renderEcaEditTemplateDesign($params)
  {
    $html = $this->renderTpl('template_edit_design', 'eca');
    return $html;
  }
  public function renderEcaEditChainSteps($params)
  {
    $html = $this->renderTpl('chain_edit_steps', 'eca');
    return $html;
  }
  public function renderEcaEditChainOptions($params)
  {
    $html = $this->renderTpl('chain_edit_options', 'eca');
    return $html;
  }
  public function renderEcaEditChainStats($params)
  {
    $request = $this->getFrame()->core('request');
    $chainId = !empty($params['obj_id']) ? (int) $params['obj_id'] : 0;
    $groupBy = $this->getListRequestText($request, 'chain_stats_group', 'day');
    $range = $this->getListRequestInt($request, 'chain_stats_range', 30, 1);
    $groupBy = in_array($groupBy, ['day', 'week', 'month', 'year'], true) ? $groupBy : 'day';
    $range = in_array($range, [7, 30, 365], true) ? $range : 30;
    $this->assign('chainStats', $this->getModel()->getChainStats($chainId));
    $this->assign('chainTrend', $this->getModel()->getChainTrend($chainId, $groupBy, $range));
    $this->assign('chainTrendGroup', $groupBy);
    $this->assign('chainTrendRange', $range);
    $html = $this->renderTpl('chain_edit_stats', 'eca');
    return $html;
  }

  public function renderEcaEditChainContacts($params)
  {
    $request = $this->getFrame()->core('request');
    $chainId = !empty($params['obj_id']) ? (int) $params['obj_id'] : 0;
    $page = $this->getListRequestInt($request, 'contacts_page', 1, 1);
    $perPage = $this->getListRequestInt($request, 'contacts_per_page', 100, 1);
    $orderBy = $this->getListRequestText($request, 'contacts_order_by', 'contact_activity_at');
    $orderDir = strtoupper($this->getListRequestText($request, 'contacts_order_dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $sortValue = $this->getListRequestText($request, 'contacts_sort', '');
    if (!empty($sortValue) && strpos($sortValue, '|') !== false) {
      [$sortBy, $sortDir] = array_pad(explode('|', $sortValue, 2), 2, '');
      if (!empty($sortBy)) {
        $orderBy = $sortBy;
      }
      $orderDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';
    }
    $filters = [
      'chain_id' => $chainId,
    ];
    if ($this->getListRequestValue($request, 'contacts_filter_id') !== null && $this->getListRequestValue($request, 'contacts_filter_id') !== '') {
      $filters['id'] = $this->getListRequestInt($request, 'contacts_filter_id');
    }
    if ($this->getListRequestValue($request, 'contacts_filter_contact_id') !== null && $this->getListRequestValue($request, 'contacts_filter_contact_id') !== '') {
      $filters['contact_id'] = $this->getListRequestInt($request, 'contacts_filter_contact_id');
    }
    if ($this->getListRequestValue($request, 'contacts_filter_current_step_id') !== null && $this->getListRequestValue($request, 'contacts_filter_current_step_id') !== '') {
      $filters['current_step_id'] = $this->getListRequestInt($request, 'contacts_filter_current_step_id');
    }
    if ($this->getListRequestValue($request, 'contacts_filter_status') !== null && $this->getListRequestValue($request, 'contacts_filter_status') !== '') {
      $filters['status'] = $this->getListRequestText($request, 'contacts_filter_status');
    }
    if ($this->getListRequestValue($request, 'contacts_filter_cron_status') !== null && $this->getListRequestValue($request, 'contacts_filter_cron_status') !== '') {
      $filters['cron_status'] = $this->getListRequestText($request, 'contacts_filter_cron_status');
    }
    if (!empty($this->getListRequestDate($request, 'contacts_filter_next_run_at'))) {
      $filters['next_run_at'] = $this->getListRequestDate($request, 'contacts_filter_next_run_at');
    }
    if (!empty($this->getListRequestDate($request, 'contacts_filter_created_at'))) {
      $filters['created_at'] = $this->getListRequestDate($request, 'contacts_filter_created_at');
    }
    if (!empty($this->getListRequestDate($request, 'contacts_filter_updated_at'))) {
      $filters['updated_at'] = $this->getListRequestDate($request, 'contacts_filter_updated_at');
    }
    $selectedTagIds = $this->getListRequestIntArray($request, 'contacts_filter_contact_tags');
    if (!empty($selectedTagIds)) {
      $filters['contact_tag_ids'] = $selectedTagIds;
    }
    $data = $this->getModel()->getFlows([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $contactIds = !empty($data['rows']) ? array_values(array_unique(array_map('intval', array_column($data['rows'], 'contact_id')))) : [];
    $contactStats = $this->getFrame()->getModule('contact')->getModel()->getContactsEngagementStatsByIds($contactIds, [
      'chain_id' => $chainId,
      // Form submits are stored globally by contact/form and are not linked to a specific ECA chain.
      'include_form_submits' => false,
      'include_incoming_mailbox' => false,
      'include_woocommerce' => false,
    ]);
    $incomingMailboxStats = $this->getModel()->getIncomingEmailStatsByContactIds($contactIds);
    if (!empty($incomingMailboxStats)) {
      foreach ($incomingMailboxStats as $contactId => $incomingStat) {
        $contactId = (int) $contactId;
        if (empty($contactStats[$contactId]) || !is_array($contactStats[$contactId])) {
          continue;
        }
        $contactStats[$contactId]['incoming_email_total'] = (int) ($incomingStat['incoming_email_total'] ?? 0);
        $contactStats[$contactId]['incoming_email_unhandled_total'] = (int) ($incomingStat['incoming_email_unhandled_total'] ?? 0);
        $contactStats[$contactId]['has_unhandled_incoming_email'] = !empty($incomingStat['has_unhandled_incoming_email']);
        $contactStats[$contactId]['last_incoming_email_at'] = (string) ($incomingStat['last_incoming_email_at'] ?? '');
      }
    }
    $contactTagOptions = $this->getFrame()->useModule('contact', 'contact_tag', 'model', 'getAllContactTags', [true]);
    $this->assign([
      'contacts_rows' => $data['rows'],
      'contacts_total' => $data['total'],
      'contacts_page' => $page,
      'contacts_per_page' => $perPage,
      'contacts_order_by' => $orderBy,
      'contacts_order_dir' => $orderDir,
      'contacts_filters' => $filters,
      'contacts_chain_id' => $chainId,
      'contacts_stats' => $contactStats,
      'contacts_contact_tag_options' => is_array($contactTagOptions) ? $contactTagOptions : [],
    ]);
    return $this->renderTpl('chain_edit_contacts', 'eca');
  }

  public function renderEcaNewsletters($params)
  {
    return $this->renderEcaChains($params, 'newsletter');
  }
}
