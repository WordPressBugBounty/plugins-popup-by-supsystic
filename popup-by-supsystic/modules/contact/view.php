<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactViewPpsgen extends BaseViewPpsgen
{
  public function renderContactLookupField(array $args = []): string
  {
    $defaults = [
      'field_name' => 'contact_id',
      'selected_id' => 0,
      'selected_label' => '',
      'placeholder' => __('Search by contact ID or email', 'ppsgen'),
      'input_class' => '',
      'wrapper_class' => '',
      'min_length' => 2,
      'limit' => 10,
    ];
    $args = wp_parse_args($args, $defaults);
    $selectedId = absint($args['selected_id']);
    $selectedLabel = is_string($args['selected_label']) ? trim($args['selected_label']) : '';
    if ($selectedLabel === '' && $selectedId > 0) {
      $selectedItem = $this->getModel()->getContactLookupItemById($selectedId);
      if (!empty($selectedItem['label'])) {
        $selectedLabel = (string) $selectedItem['label'];
      }
    }
    $this->getModule()->enqueueContactLookupAssets();
    $this->assign('contactLookupField', [
      'field_name' => (string) $args['field_name'],
      'selected_id' => $selectedId,
      'selected_label' => $selectedLabel,
      'placeholder' => (string) $args['placeholder'],
      'input_class' => trim((string) $args['input_class']),
      'wrapper_class' => trim((string) $args['wrapper_class']),
      'min_length' => max(1, absint($args['min_length'])),
      'limit' => max(1, min(10, absint($args['limit']))),
      'instance_id' => 'ppsgenContactLookup' . wp_rand(1000, 999999),
    ]);
    return (string) $this->renderTpl('contact_lookup_field', 'contact');
  }

  public function renderContactList($params)
  {
    $request = $this->getFrame()->core('request');
    $page = max(1, intval($request->getVar('page', 'post') ?? 1));
    $perPage = intval($request->getVar('per_page', 'post') ?? 100);
    $orderBy = $request->getVar('order_by', 'post') ?? 'contact_activity_at';
    $orderDirRaw = $request->getVar('order_dir', 'post');
    $orderDir = strtoupper((string) ($orderDirRaw ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $stored_columns = $this->getModel()->getOption('contact_fields_filter_selected', 'list_opts', 0, 'contact');
    if (!is_array($stored_columns)) {
      // Never chosen yet: start with the columns people need to follow up an enquiry
      $stored_columns = array_values(array_intersect(['first_name', 'last_name', 'email', 'phone'], array_keys((array) $this->getModel()->getFieldOptions())));
    }
    $columns = $request->getVar('columns', 'post') ?? $stored_columns;
    $filters = [];
    if (!empty($request->getVar('filter_id', 'post'))) {
      $filters['id'] = intval($request->getVar('filter_id', 'post'));
    }
    if (!empty($request->getVar('filter_wp_user_id', 'post'))) {
      $filters['wp_user_id'] = $request->getVar('filter_wp_user_id', 'post');
    }
    if (!empty($request->getVar('filter_verified', 'post'))) {
      $filters['verified'] = $request->getVar('filter_verified', 'post');
    }
    if (!empty($request->getVar('filter_created_at', 'post'))) {
      $filters['created_at'] = $request->getVar('filter_created_at', 'post');
    }
    if (!empty($request->getVar('filter_updated_at', 'post'))) {
      $filters['updated_at'] = $request->getVar('filter_updated_at', 'post');
    }
    $dynamicFilters = [];
    foreach ($columns as $slug) {
      $key = 'filter_dynamic_' . $slug;
      if (!empty($request->getVar($key, 'post'))) {
        $dynamicFilters[$slug] = $request->getVar($key, 'post');
      }
      $this->getModel()->updateOption('contact_fields_filter_selected', 'list_opts', $columns, 0, 'contact', 'json');
    }
    $selectedTagIds = $request->getVar('filter_dynamic_tag_titles', 'post');
    if (!is_array($selectedTagIds)) {
      $selectedTagIds = [];
    }
    $selectedTagIds = array_values(array_unique(array_filter(array_map('intval', $selectedTagIds))));
    if (!empty($selectedTagIds)) {
      $dynamicFilters['tag_titles'] = $selectedTagIds;
    }
    if (!empty($dynamicFilters)) {
      $filters['dynamic'] = $dynamicFilters;
    }
    $model = $this->getModel();
    $data = $model->getContacts([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'dynamic_fields' => $columns,
      'filters' => $filters,
    ]);
    $contactIds = !empty($data['rows']) ? array_values(array_unique(array_map('intval', array_column($data['rows'], 'id')))) : [];
    $engagementStats = $model->getContactsEngagementStatsByIds($contactIds);
    $fieldOptions = $model->getFieldOptions();
    $contactTagOptions = $this->getModule()->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'columns' => $columns,
      'stored_columns' => $stored_columns,
      'fieldOptions' => $fieldOptions,
      'contactTagOptions' => $contactTagOptions,
      'filters' => $filters,
      'engagementStats' => $engagementStats,
      'isEcaActive' => !empty($this->getFrame()->useModule('eca')),
      'isWooCommerceActive' => $model->isWooCommerceActive(),
      'savedLists' => $model->getSavedContactLists(),
      'activeListId' => sanitize_key((string) $request->getVar('ppsgen_list_id', 'post')),
    ]);
    $html = $this->renderTpl('list', 'contact');
    return $html;
  }

  public function renderContactOverview($params)
  {
    $html = $this->renderTpl('overview', 'contact');
    return $html;
  }

  public function renderContactEdit($params)
  {
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    $contactHeaderMeta = [];
    if (!empty($objId)) {
      $options = $this->getModel()->getAllOptions($objId, 'contact');
      $contactOptions = $this->getModel()->getContactById($objId);
      $options = array_merge((array) $options, (array) $contactOptions);
      $email = $this->getModel()->getContactFieldValueBySlug((int) $objId, 'email');
      $tags = $this->getModule()->getSubmod('contact_tag')->getModel()->getTagsByContactId((int) $objId, true);
      $stats = $this->getModel()->getContactEngagementStats((int) $objId);
      $contactHeaderMeta = [
        'email' => $email,
        'tags' => !empty($tags) ? array_values($tags) : [],
        'stats' => $stats,
        'verified' => !empty($options['verified']),
      ];
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Contact with specific ID for editing', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $this->assign('contactHeaderMeta', $contactHeaderMeta);
      $tabs = [
        'contact_field' => ['title' => __('Fields', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.contact_field.options_html', $options)],
        'contact_tag' => ['title' => __('Tags', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.contact_tag.options_html', $options)],
        'general' => ['title' => __('Options', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.edit.options_html', $options)],
        'stats' => ['title' => __('Stats', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.edit.stats_html', $options)],
        'logs' => ['title' => __('Logs', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.edit.logs_html', $options)],
        'email_chat' => ['title' => __('Email Chat', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.edit.email_chat_html', $options)],
        'form_submits' => ['title' => __('Form Submits', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.form_submits.options_html', $options)],
      ];
    if ($this->getFrame()->useModule('eca')) {
      $tabs['email_campaigns'] = ['title' => __('Email Campaigns', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.edit.email_campaigns_html', $options)];
    }
    $tabs = $this->getFrame()
      ->core('eventDispatcher')
      ->applyFiltersOnce('contact.edit.tabs', ['tabs' => $tabs, 'options' => $options]);
    $this->assign('tabs', $tabs['tabs']);
    $html = $this->renderTpl('edit', 'contact');
    return $html;
  }

  public function renderContactEditStats($params)
  {
    $objId = !empty($params['id']) ? (int) $params['id'] : (!empty($params['obj_id']) ? (int) $params['obj_id'] : 0);
    if (empty($objId)) {
      return esc_html__('Create the contact first to see engagement statistics.', 'ppsgen');
    }
    $stats = $this->getModel()->getContactEngagementStats($objId);
    $this->assign('contactStats', $stats);
    $this->assign('objId', $objId);
    return $this->renderTpl('edit_stats', 'contact');
  }

  public function renderContactEditLogs($params)
  {
    $objId = !empty($params['id']) ? (int) $params['id'] : (!empty($params['obj_id']) ? (int) $params['obj_id'] : 0);
    if (empty($objId)) {
      return esc_html__('Create the contact first to see logs.', 'ppsgen');
    }
    $this->assign('contactLogs', $this->getModel()->getContactTimeline($objId));
    $this->assign('objId', $objId);
    return $this->renderTpl('edit_logs', 'contact');
  }

  public function renderContactEditEmailCampaigns($params)
  {
    $objId = !empty($params['id']) ? (int) $params['id'] : (!empty($params['obj_id']) ? (int) $params['obj_id'] : 0);
    if (empty($objId) || !$this->getFrame()->useModule('eca')) {
      return esc_html__('Create the contact first to see related email campaigns.', 'ppsgen');
    }
    $ecaModel = $this->getFrame()->getModule('eca')->getModel();
    $flowsData = $ecaModel->getFlows([
      'page' => 1,
      'per_page' => 500,
      'order_by' => 'updated_at',
      'order_dir' => 'DESC',
      'filters' => [
        'contact_id' => $objId,
      ],
    ]);
    $flows = !empty($flowsData['rows']) ? $flowsData['rows'] : [];
    if (!empty($flows)) {
      $contactCampaignStatsByChain = [];
      foreach ($flows as $key => $flow) {
        $chainId = (int) ($flow['chain_id'] ?? 0);
        $flows[$key]['chain'] = $ecaModel->getChainById($chainId);
        if ($chainId > 0 && !isset($contactCampaignStatsByChain[$chainId])) {
          $contactStats = $this->getModel()->getContactsEngagementStatsByIds(
            [$objId],
            [
              'chain_id' => $chainId,
              'include_form_submits' => false,
              'include_incoming_mailbox' => false,
              'include_woocommerce' => false,
            ]
          );
          $contactCampaignStatsByChain[$chainId] = is_array($contactStats[$objId] ?? null) ? $contactStats[$objId] : [];
        }
        $flows[$key]['contact_chain_stats'] = $contactCampaignStatsByChain[$chainId] ?? [];
      }
    }
    $this->assign('contactCampaignRows', $flows);
    $this->assign('contactCampaignObjId', $objId);
    $this->assign('contactCampaignFlowStatus', $ecaModel->flowStatus());
    return $this->renderTpl('edit_email_campaigns', 'contact');
  }

  public function renderContactEditEmailChat($params)
  {
    $objId = !empty($params['id']) ? (int) $params['id'] : (!empty($params['obj_id']) ? (int) $params['obj_id'] : 0);
    if (empty($objId)) {
      return esc_html__('Create the contact first to use email chat.', 'ppsgen');
    }
    $contactEmail = $this->getModel()->getContactFieldValueBySlug($objId, 'email');
    $templateOptions = [];
    if ($this->getFrame()->useModule('eca')) {
      $templateOptions = (array) $this->getFrame()->getModule('eca')->getModel()->getTemplatesList(true);
    }
    $mailConfigOptions = $this->getFrame()->getModule('dashboard')->getModel()->getMailConfigOptions(false);
    $defaultMailConfigId = $this->getFrame()->getModule('dashboard')->getModel()->ensureDefaultMailConfigId();
    $incomingSummary = [
      'tracking_enabled' => false,
      'selected_unhandled_total' => 0,
      'selected_last_received_at' => '',
      'all_unhandled_total' => 0,
      'all_last_received_at' => '',
    ];
    if ($this->getFrame()->useModule('eca')) {
      $incomingSummary = $this->getFrame()->getModule('eca')->getModel()->getContactIncomingMailboxSummary($objId, $defaultMailConfigId);
    }
    $editorId = 'ppsgen_contact_email_reply_' . $objId;
    $this->assign('contactEmailChatObjId', $objId);
    $this->assign('contactEmailChatEmail', $contactEmail);
    $this->assign('contactEmailChatTemplates', $templateOptions);
    $this->assign('contactEmailChatMailConfigs', $mailConfigOptions);
    $this->assign('contactEmailChatDefaultMailConfigId', $defaultMailConfigId);
    $this->assign('contactEmailChatIncomingSummaryHtml', $this->renderContactEmailChatIncomingSummary($incomingSummary));
    $this->assign('contactEmailChatEditorId', $editorId);
    return $this->renderTpl('edit_email_chat', 'contact');
  }

  public function renderContactEmailChatThread(array $threadData): string
  {
    $this->assign('contactEmailThreadData', $threadData);
    return (string) $this->renderTpl('edit_email_chat_thread', 'contact');
  }

  public function renderContactEmailChatIncomingSummary(array $summary): string
  {
    $this->assign('contactEmailChatIncomingSummary', $summary);
    return (string) $this->renderTpl('edit_email_chat_incoming_summary', 'contact');
  }

  public function renderContactImport($params)
  {
    $tabs = [
      'generate_contact' => ['title' => __('Generate/Import Contacts from WordPress ', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.import.generate_contact_html', $params)],
      'generate_woo_contact' => ['title' => __('Generate/Import Contacts from WooCommerce', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.import.generate_woo_contact_html', $params)],
      'import_csv' => ['title' => __('Import CSV', 'ppsgen'), 'content' => $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.import.csv_html', $params)],
    ];
    $tabs = $this->getFrame()
      ->core('eventDispatcher')
      ->applyFiltersOnce('contact.import.tabs', ['tabs' => $tabs, 'options' => $params]);
    $this->assign('tabs', $tabs['tabs']);
    $html = $this->renderTpl('import', 'contact');
    return $html;
  }

  public function renderContactImportGenerate($params)
  {
    $allContactFields = $this->getModule()->getSubmod('contact_field')->getModel()->getAllContactFieldsSlugList(true);
    $allContactFields = ['' => ''] + $allContactFields;
    $allContactTags = $this->getModule()->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    $allContactTags = ['' => ''] + $allContactTags;
    $wpAutoImportConfig = $this->getModel()->getWpContactAutoImportConfig();
    $this->assign('allContactTags', $allContactTags);
    $this->assign('allContactFields', $allContactFields);
    $this->assign('wpAutoImportConfig', $wpAutoImportConfig);
    $this->assign('isWpAutoImportEnabled', $this->getModel()->isWpContactAutoImportEnabled());
    $this->renderContactImportGenerateJs();
    $html = $this->renderTpl('import_contact_generate', 'contact');
    return $html;
  }

  public function renderContactImportGenerateWoo($params)
  {
    $allContactFields = $this->getModule()->getSubmod('contact_field')->getModel()->getAllContactFieldsSlugList(true);
    $allContactFields = ['' => ''] + $allContactFields;
    $allContactTags = $this->getModule()->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    $allContactTags = ['' => ''] + $allContactTags;
    $wooOrderStatuses = $this->getModel()->getWooImportOrderStatusOptions();
    $wooAutoImportConfig = $this->getModel()->getWooContactAutoImportConfig();
    $this->assign('allContactTags', $allContactTags);
    $this->assign('allContactFields', $allContactFields);
    $this->assign('wooOrderStatuses', $wooOrderStatuses);
    $this->assign('wooAutoImportConfig', $wooAutoImportConfig);
    $this->assign('isWooAutoImportEnabled', $this->getModel()->isWooContactAutoImportEnabled());
    $this->assign('isWooCommerceActive', $this->getModel()->isWooCommerceActive());
    $this->renderContactImportGenerateWooJs();
    $html = $this->renderTpl('import_contact_generate_woo', 'contact');
    return $html;
  }

  public function renderContactImportGenerateJs()
  {
    wp_register_script('ppsgen-backend-import-contact-generate', PPSGEN_PLUGIN_URL . 'modules/contact/assets/js/backend.import.contact_generate.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-import-contact-generate');
  }

  public function renderContactImportGenerateWooJs()
  {
    wp_register_script('ppsgen-backend-import-contact-generate-woo', PPSGEN_PLUGIN_URL . 'modules/contact/assets/js/backend.import.contact_generate_woo.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-import-contact-generate-woo');
  }

  public function renderContactImportCsv($params)
  {
    $this->renderContactImportCsvJs();
    $html = $this->renderTpl('import_contact_csv', 'contact');
    return $html;
  }

  public function renderContactImportCsvJs()
  {
    wp_register_script('ppsgen-backend-import-contact-csv', PPSGEN_PLUGIN_URL . 'modules/contact/assets/js/backend.import.contact_csv.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-import-contact-csv');
  }

  public function renderBulkAssignTagsModal(array $params = []): string
  {
    $this->getFrame()->core('component')->loadPpsgenselect();
    wp_register_style('ppsgen-backend-contact-bulk-tags', PPSGEN_PLUGIN_URL . 'modules/contact/assets/css/backend.bulk.tags.css', [], PPSGEN_VERSION);
    wp_enqueue_style('ppsgen-backend-contact-bulk-tags');
    wp_register_script('ppsgen-backend-contact-bulk-tags', PPSGEN_PLUGIN_URL . 'modules/contact/assets/js/backend.bulk.tags.js', ['jquery'], PPSGEN_VERSION, ['in_footer' => true]);
    wp_enqueue_script('ppsgen-backend-contact-bulk-tags');

    $tagOptions = $this->getModule()->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    $this->assign('bulkAssignTagOptions', $tagOptions);
    $mode = !empty($params['mode']) && in_array($params['mode'], ['add', 'delete'], true) ? $params['mode'] : 'add';
    $this->assign('bulkAssignTagsModalTitle', !empty($params['title']) ? (string) $params['title'] : __('Add Tag(s) to Selected Contacts', 'ppsgen'));
    $this->assign('bulkAssignTagsModalDescription', !empty($params['description']) ? (string) $params['description'] : __('Choose one or more tags to assign to the selected contacts. Existing contact tags will be kept and not duplicated.', 'ppsgen'));
    $this->assign('bulkAssignTagsModalContext', !empty($params['context']) ? sanitize_key((string) $params['context']) : 'contact_list');
    $this->assign('bulkAssignTagsModalMode', $mode);
    $this->assign('bulkAssignTagsModalSubmitLabel', !empty($params['submit_label']) ? (string) $params['submit_label'] : __('Add Tag(s)', 'ppsgen'));
    $this->assign('bulkAssignTagsModalSubmitIcon', !empty($params['submit_icon']) ? sanitize_html_class((string) $params['submit_icon']) : 'dashicons-tag');
    return (string) $this->renderTpl('bulk_assign_tags_modal', 'contact');
  }

  public function renderContactOptions($params)
  {
    $contactOptions = array_merge(
      $this->getModel()->getEcaAutocheckDetectionDefaults(),
      (array) $this->getModel()->getAllOptions()
    );
    $contactTagOptions = $this->getModule()->getSubmod('contact_tag')->getModel()->getAllContactTags(true);
    $this->assign('contactOptions', $contactOptions);
    $this->assign('contactTagOptions', $contactTagOptions);
    $this->assign('isEcaActive', !empty($this->getFrame()->useModule('eca')));
    $html = $this->renderTpl('options', 'contact');
    return $html;
  }

  public function renderContactEditOptions($params)
  {
    $allWpUsers = get_users(['fields' => ['ID', 'display_name']]);
    $allWpUsers = wp_list_pluck($allWpUsers, 'display_name', 'ID');
    $allWpUsers = [0 => ''] + $allWpUsers;
    $this->assign('allWpUsers', $allWpUsers);
    $html = $this->renderTpl('edit_contact_options', 'contact');
    return $html;
  }

  public function renderContactPreview(array $preview): string
  {
    $this->assign('preview', $preview);
    return $this->renderTpl('preview', 'contact');
  }
}
