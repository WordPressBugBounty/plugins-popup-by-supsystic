<?php
if (!defined('ABSPATH')) {
  exit();
}
global $wpdb;
$component = $this->component;
$rows = $this->contacts_rows;
$total = (int) $this->contacts_total;
$page = (int) $this->contacts_page;
$per_page = (int) $this->contacts_per_page;
$order_by = $this->contacts_order_by;
$order_dir = $this->contacts_order_dir;
$filters = is_array($this->contacts_filters) ? $this->contacts_filters : [];
$chainId = (int) $this->contacts_chain_id;
$contactStatsMap = is_array($this->contacts_stats ?? null) ? $this->contacts_stats : [];
$contactTagOptions = is_array($this->contacts_contact_tag_options ?? null) ? $this->contacts_contact_tag_options : [];
$selectedTagFilterIds = !empty($filters['contact_tag_ids']) && is_array($filters['contact_tag_ids']) ? array_values(array_map('intval', $filters['contact_tag_ids'])) : [];
$isActivityOrder = $order_by === 'contact_activity_at';
$totalPages = max(1, (int) ceil($total / max(1, $per_page)));
$start = $total > 0 ? (($page - 1) * $per_page + 1) : 0;
$end = $total > 0 ? min($start + $per_page - 1, $total) : 0;
$flowStatus = $this->getModel()->flowStatus();
$cfEmailId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email'");
$cfFirstNameId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'first_name'");
$cfLastNameId = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'last_name'");

$contactIds = !empty($rows) ? array_values(array_unique(array_map('intval', array_column($rows, 'contact_id')))) : [];
$stepIds = !empty($rows) ? array_values(array_unique(array_map('intval', array_column($rows, 'current_step_id')))) : [];
$contactMeta = [];
$contactTagsMap = [];
$stepNames = [];
$chainStepOptions = [];
$bulkAssignTagsModal = $this->getFrame()->useModule('contact', false, 'view', 'renderBulkAssignTagsModal', [['context' => 'eca_chain_contacts']]);
$component->setNamePrefix('');

if (!empty($contactIds) && (!empty($cfEmailId) || !empty($cfFirstNameId) || !empty($cfLastNameId))) {
  $fieldIds = array_values(array_filter([$cfEmailId, $cfFirstNameId, $cfLastNameId]));
  $contactPlaceholders = implode(',', array_fill(0, count($contactIds), '%d'));
  $fieldPlaceholders = implode(',', array_fill(0, count($fieldIds), '%d'));
  $queryArgs = array_merge($contactIds, $fieldIds);
  $contactRows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT contact_id, field_id, value
      FROM {$wpdb->prefix}ppsgen_contact_field_values
      WHERE contact_id IN ($contactPlaceholders) AND field_id IN ($fieldPlaceholders)",
      ...$queryArgs,
    ),
    ARRAY_A,
  );
  foreach ($contactRows as $metaRow) {
    $contactId = (int) $metaRow['contact_id'];
    if (empty($contactMeta[$contactId])) {
      $contactMeta[$contactId] = [
        'email' => '',
        'first_name' => '',
        'last_name' => '',
      ];
    }
    if ((int) $metaRow['field_id'] === $cfEmailId) {
      $contactMeta[$contactId]['email'] = (string) $metaRow['value'];
    }
    if ((int) $metaRow['field_id'] === $cfFirstNameId) {
      $contactMeta[$contactId]['first_name'] = (string) $metaRow['value'];
    }
    if ((int) $metaRow['field_id'] === $cfLastNameId) {
      $contactMeta[$contactId]['last_name'] = (string) $metaRow['value'];
    }
  }
}

if (!empty($contactIds)) {
  $contactPlaceholders = implode(',', array_fill(0, count($contactIds), '%d'));
  $contactTagRows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT ctt.contact_id, ct.slug
      FROM {$wpdb->prefix}ppsgen_contact_to_tags AS ctt
      LEFT JOIN {$wpdb->prefix}ppsgen_contact_tags AS ct ON ct.id = ctt.tag_id
      WHERE ctt.contact_id IN ($contactPlaceholders)
      ORDER BY ct.slug ASC",
      ...$contactIds,
    ),
    ARRAY_A,
  );
  foreach ($contactTagRows as $contactTagRow) {
    $contactId = (int) ($contactTagRow['contact_id'] ?? 0);
    $tagSlug = (string) ($contactTagRow['slug'] ?? '');
    if ($contactId <= 0 || $tagSlug === '') {
      continue;
    }
    if (empty($contactTagsMap[$contactId])) {
      $contactTagsMap[$contactId] = [];
    }
    $contactTagsMap[$contactId][] = $tagSlug;
  }
}

if (!empty($stepIds)) {
  $stepPlaceholders = implode(',', array_fill(0, count($stepIds), '%d'));
  $stepRows = $wpdb->get_results(
    $wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id IN ($stepPlaceholders)", ...$stepIds),
    ARRAY_A,
  );
  if (!empty($stepRows)) {
    $stepNames = array_column($stepRows, 'name', 'id');
  }
}

if (!empty($chainId)) {
  $chainStepRows = $wpdb->get_results(
    $wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE chain_id = %d ORDER BY step_order ASC, id ASC", $chainId),
    ARRAY_A,
  );
  if (!empty($chainStepRows)) {
    $chainStepOptions = array_column($chainStepRows, 'name', 'id');
  }
}

$headers = [
  'id' => 'Flow ID',
  'contact_id' => 'Email',
  'contact_tags' => 'Tags',
  'current_step_id' => 'Current Step',
  'next_run_at' => 'Next Run',
  'cron_status' => 'Cron',
  'status' => 'Status',
  'created_at' => 'Created',
  'updated_at' => 'Edited',
  'contact_activity_at' => 'Activity',
];
?>

<div class="ppsgenChainContactsPanel ppsgenBulkAssignTagsScope">
  <div class="ppsgenChainContactsIntro">
      <?php esc_html_e('All contacts currently connected to this campaign are listed below. You can filter by flow, contact, step, status and schedule fields.', 'ppsgen'); ?>
  </div>

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
      <a
        class="ppsgenTableListBtn ppsgenBulkAssignTagsTrigger"
        data-mode="add"
        data-modal-title="<?php echo esc_attr__('Add Tag(s) to Campaign Contacts', 'ppsgen'); ?>"
        data-modal-description="<?php echo esc_attr__('Choose one or more tags to assign to the selected campaign contacts. Existing contact tags will be kept and not duplicated.', 'ppsgen'); ?>"
        data-submit-label="<?php echo esc_attr__('Add Tag(s)', 'ppsgen'); ?>"
        data-submit-icon="dashicons-tag"
      ><span class="dashicons dashicons-tag"></span> <?php esc_html_e('Add Tag(s)', 'ppsgen'); ?></a>
      <a
        class="ppsgenTableListBtn ppsgenBulkAssignTagsTrigger ppsgenBulkDeleteTagsTrigger"
        data-mode="delete"
        data-modal-title="<?php echo esc_attr__('Delete Tag(s) from Campaign Contacts', 'ppsgen'); ?>"
        data-modal-description="<?php echo esc_attr__('Choose one or more tags to remove from the selected campaign contacts. Tags that are not assigned already will be skipped.', 'ppsgen'); ?>"
        data-submit-label="<?php echo esc_attr__('Delete Tag(s)', 'ppsgen'); ?>"
        data-submit-icon="dashicons-dismiss"
      ><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Delete Tag(s)', 'ppsgen'); ?></a>
    </div>
    <div class="ppsgenTableListTopButtonsRight">
      <input type="hidden" name="contacts_order_by" value="<?php echo esc_attr($order_by); ?>">
      <input type="hidden" name="contacts_order_dir" value="<?php echo esc_attr($order_dir); ?>">
      <label><?php esc_html_e('Show per page:', 'ppsgen'); ?></label>
      <select name="contacts_per_page" onchange="this.form.submit()">
        <?php foreach ([10, 20, 50, 100, 500] as $n) { ?>
        <option value="<?php echo esc_attr($n); ?>" <?php selected($n, $per_page); ?>><?php echo esc_html($n); ?></option>
        <?php } ?>
      </select>
      <button class="ppsgenTableListBtn ppsgenTableListSearch" type="submit" name="chain_edit_active_tab" value="contacts"><span class="dashicons dashicons-search"></span> <?php esc_html_e('Apply Filters', 'ppsgen'); ?></button>
    </div>
  </div>

  <div class="tablenav top">
    <div class="tablenav-pages">
      <span class="displaying-num">
        <?php
        printf(
          esc_html__('Showing %1$d to %2$d of %3$d entries', 'ppsgen'),
          esc_html($start),
          esc_html($end),
          esc_html($total),
        );
        ?>
      </span>
    </div>
  </div>

  <table class="widefat fixed striped ppsgenTableList ppsgenChainContactsTable">
    <thead>
      <tr>
        <th class="ppsgenTableListCell50"></th>
        <?php foreach ($headers as $key => $label) { ?>
        <th data-key="<?php echo esc_attr($key); ?>">
          <?php
          $nextSortDir = ($order_by === $key && $order_dir === 'ASC') ? 'DESC' : 'ASC';
          if ($key === 'contact_activity_at' && $order_by !== $key) {
            $nextSortDir = 'DESC';
          }
          ?>
          <button class="ppsgenTableListBtn ppsgenTableListBtnCenter ppsgenTableListBtnFilter" type="submit" name="contacts_sort" value="<?php echo esc_attr($key . '|' . $nextSortDir); ?>">
            <?php echo esc_html($label); ?>
            <?php if ($order_by === $key) {
              echo $order_dir === 'ASC' ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
            } ?>
          </button>
        </th>
        <?php } ?>
      </tr>
      <tr>
        <th class="ppsgenTableListCell50"><input type="checkbox" class="ppsgenTableListSelectAllCheckbox"></th>
        <?php foreach ($headers as $key => $_) { ?>
        <th data-key="<?php echo esc_attr($key); ?>">
          <?php switch ($key) {
            case 'id':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="contacts_filter_id" value="' . esc_attr($filters['id'] ?? '') . '">';
              break;
            case 'contact_id':
              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              echo (string) $this->getFrame()->useModule('contact', false, 'view', 'renderContactLookupField', [[
                'field_name' => 'contacts_filter_contact_id',
                'selected_id' => (int) ($filters['contact_id'] ?? 0),
                'placeholder' => __('Search by contact ID or email', 'ppsgen'),
                'input_class' => 'ppsgenTableListFilterInput',
                'wrapper_class' => 'ppsgenEcaContactLookupFilter',
              ]]);
              break;
            case 'contact_tags':
              $component->selectmultiple('contacts_filter_contact_tags[]', [
                'class' => 'ppsgenselect ppsgenTableListFilterInput',
                'options' => $contactTagOptions,
                'selected' => $selectedTagFilterIds,
              ]);
              break;
            case 'current_step_id':
              echo '<select name="contacts_filter_current_step_id" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              foreach ($chainStepOptions as $stepOptionId => $stepOptionName) {
                echo '<option value="' . esc_attr($stepOptionId) . '"' . (!empty($filters['current_step_id']) && (int) $filters['current_step_id'] === (int) $stepOptionId ? ' selected' : '') . '>' . esc_html($stepOptionName) . '</option>';
              }
              echo '</select>';
              break;
            case 'next_run_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="contacts_filter_next_run_at" value="' . esc_attr($filters['next_run_at'] ?? '') . '">';
              break;
            case 'created_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="contacts_filter_created_at" value="' . esc_attr($filters['created_at'] ?? '') . '">';
              break;
            case 'updated_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="contacts_filter_updated_at" value="' . esc_attr($filters['updated_at'] ?? '') . '">';
              break;
            case 'cron_status':
              echo '<select name="contacts_filter_cron_status" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              echo '<option value="waiting"' . (!empty($filters['cron_status']) && $filters['cron_status'] === 'waiting' ? ' selected' : '') . '>waiting</option>';
              echo '<option value="processing"' . (!empty($filters['cron_status']) && $filters['cron_status'] === 'processing' ? ' selected' : '') . '>processing</option>';
              echo '</select>';
              break;
            case 'status':
              echo '<select name="contacts_filter_status" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              foreach ($flowStatus as $statusKey => $statusLabel) {
                echo '<option value="' . esc_attr($statusKey) . '"' . (!empty($filters['status']) && $filters['status'] === $statusKey ? ' selected' : '') . '>' . esc_html($statusLabel) . '</option>';
              }
              echo '</select>';
              break;
            case 'contact_activity_at':
              echo '';
              break;
          } ?>
        </th>
        <?php } ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) { ?>
      <tr>
            <td colspan="<?php echo esc_attr(count($headers) + 1); ?>"><?php esc_html_e('No contacts found for this campaign.', 'ppsgen'); ?></td>
      </tr>
      <?php } ?>
      <?php foreach ($rows as $row) {
        $flowId = (int) ($row['id'] ?? 0);
        $contactId = (int) ($row['contact_id'] ?? 0);
        $currentStepId = (int) ($row['current_step_id'] ?? 0);
        $flowRowStatus = (string) ($row['status'] ?? '');
        $contactStats = $contactStatsMap[$contactId] ?? [];
        $contact = $contactMeta[$contactId] ?? ['email' => '', 'first_name' => '', 'last_name' => ''];
        $contactTags = !empty($contactTagsMap[$contactId]) ? array_values(array_unique($contactTagsMap[$contactId])) : [];
        $contactName = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
        $stepName = !empty($stepNames[$currentStepId]) ? $stepNames[$currentStepId] : '';
        $incomingUnhandledTotal = (int) ($contactStats['incoming_email_unhandled_total'] ?? 0);
        $incomingIndicatorTitle = $incomingUnhandledTotal > 0
          ? sprintf(
            _n('%d new incoming email needs attention', '%d new incoming emails need attention', $incomingUnhandledTotal, 'ppsgen'),
            $incomingUnhandledTotal,
          )
          : __('No new incoming emails', 'ppsgen');
        $contactEditUrl = admin_url('admin.php?page=ppsgen&tab=contact_edit&id=' . $contactId);
        $flowsUrl = admin_url('admin.php?page=ppsgen&tab=eca_flows');
        $flowsUrl = add_query_arg([
          'filter_contact_id' => $contactId,
          'filter_chain_id' => $chainId,
        ], $flowsUrl);
        $flowLogsUrl = admin_url('admin.php?page=ppsgen&tab=eca_flows_logs');
        $flowLogsUrl = add_query_arg([
          'filter_contact_id' => $contactId,
          'filter_chain_id' => $chainId,
        ], $flowLogsUrl);
        $stepEventsUrl = admin_url('admin.php?page=ppsgen&tab=eca_step_events');
        $stepEventsUrl = add_query_arg([
          'filter_contact_id' => $contactId,
          'filter_chain_id' => $chainId,
        ], $stepEventsUrl);
        ?>
      <tr>
        <td class="ppsgenTableListCell50"><input data-contact-id="<?php echo esc_attr($contactId); ?>" data-obj="<?php echo esc_attr($flowId); ?>" type="checkbox" class="ppsgenTableListSelectCheckbox"></td>
        <?php foreach ($headers as $key => $_) { ?>
          <?php if ($key === 'contact_id') { ?>
            <td data-key="<?php echo esc_attr($key); ?>">
              <div class="ppsgenChainContactsContactCell">
                <strong><a href="<?php echo esc_url($contactEditUrl); ?>"><?php echo !empty($contact['email']) ? esc_html($contact['email']) : esc_html(sprintf(__('Contact #%d', 'ppsgen'), $contactId)); ?></a></strong>
                <?php if (!empty($contactName) || $contactId > 0) { ?>
                <span><?php echo esc_html('#' . $contactId . (!empty($contactName) ? ' · ' . $contactName : '')); ?></span>
                <?php } ?>
              </div>
            </td>
          <?php } elseif ($key === 'contact_email') { ?>
            <td data-key="<?php echo esc_attr($key); ?>">
              <span class="ppsgenTableListCellContent"><?php echo !empty($contact['email']) ? esc_html($contact['email']) : '—'; ?></span>
            </td>
          <?php } elseif ($key === 'contact_tags') { ?>
            <td data-key="<?php echo esc_attr($key); ?>">
              <span class="ppsgenTableListCellContent"><?php echo !empty($contactTags) ? esc_html(implode(', ', $contactTags)) : '—'; ?></span>
            </td>
          <?php } elseif ($key === 'current_step_id') { ?>
            <td data-key="<?php echo esc_attr($key); ?>">
              <?php if (!empty($stepName)) { ?>
                <span class="description"><?php echo esc_html($stepName); ?></span>
              <?php } ?>
            </td>
          <?php } elseif ($key === 'contact_activity_at') { ?>
            <td data-key="<?php echo esc_attr($key); ?>">
              <span class="ppsgenChainContactsActivityText">
                <span class="ppsgenContactIncomingIndicatorBadge<?php echo !empty($contactStats['has_unhandled_incoming_email']) ? ' is-active' : ''; ?>" title="<?php echo esc_attr($incomingIndicatorTitle); ?>">
                  <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                </span>
                <?php echo esc_html($contactStats['last_activity_text'] ?? __('No activity yet', 'ppsgen')); ?>
              </span>
            </td>
          <?php } elseif ($key === 'status') { ?>
            <td data-key="<?php echo esc_attr($key); ?>"><?php echo esc_html($flowStatus[$row[$key]] ?? ($row[$key] ?? '')); ?></td>
          <?php } else { ?>
            <td data-key="<?php echo esc_attr($key); ?>"><?php echo esc_html($row[$key] ?? ''); ?></td>
          <?php } ?>
        <?php } ?>
      </tr>
      <tr class="ppsgenChainContactsActionsRow">
        <td colspan="<?php echo esc_attr(count($headers) + 1); ?>">
          <div class="ppsgenChainContactsMetaRow">
            <div class="ppsgenChainContactsStats">
              <span class="ppsgenChainContactsStat ppsgenChainContactsStatScore"><?php echo esc_html((int) ($contactStats['percent'] ?? 0)); ?>% <?php echo esc_html($contactStats['label'] ?? __('Inactive', 'ppsgen')); ?></span>
              <span class="ppsgenChainContactsStat ppsgenChainContactsStatMetric">
                <span class="ppsgenChainContactsStatTitle"><?php esc_html_e('Opens', 'ppsgen'); ?></span>
                <span class="ppsgenChainContactsStatValue is-total" title="<?php echo esc_attr(sprintf(__('Total Opens: %d', 'ppsgen'), (int) ($contactStats['open_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['open_total'] ?? 0)); ?></span>
                <span class="ppsgenChainContactsStatValue is-suspicious" title="<?php echo esc_attr(sprintf(__('Suspicious Opens: %d', 'ppsgen'), (int) ($contactStats['open_suspicious_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['open_suspicious_total'] ?? 0)); ?></span>
                <span class="ppsgenChainContactsStatValue is-live" title="<?php echo esc_attr(sprintf(__('Live Opens: %d', 'ppsgen'), (int) ($contactStats['open_live_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['open_live_total'] ?? 0)); ?></span>
              </span>
              <span class="ppsgenChainContactsStat ppsgenChainContactsStatMetric">
                <span class="ppsgenChainContactsStatTitle"><?php esc_html_e('Clicks', 'ppsgen'); ?></span>
                <span class="ppsgenChainContactsStatValue is-total" title="<?php echo esc_attr(sprintf(__('Total Clicks: %d', 'ppsgen'), (int) ($contactStats['click_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['click_total'] ?? 0)); ?></span>
                <span class="ppsgenChainContactsStatValue is-suspicious" title="<?php echo esc_attr(sprintf(__('Suspicious Clicks: %d', 'ppsgen'), (int) ($contactStats['click_suspicious_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['click_suspicious_total'] ?? 0)); ?></span>
                <span class="ppsgenChainContactsStatValue is-live" title="<?php echo esc_attr(sprintf(__('Live Clicks: %d', 'ppsgen'), (int) ($contactStats['click_live_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['click_live_total'] ?? 0)); ?></span>
              </span>
              <span class="ppsgenChainContactsStat">
                <span class="ppsgenChainContactsActivityText">
                  <span class="ppsgenContactIncomingIndicatorBadge<?php echo !empty($contactStats['has_unhandled_incoming_email']) ? ' is-active' : ''; ?>" title="<?php echo esc_attr($incomingIndicatorTitle); ?>">
                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                  </span>
                  <?php echo esc_html($contactStats['last_activity_text'] ?? __('No activity yet', 'ppsgen')); ?>
                </span>
              </span>
            </div>
            <div class="ppsgenChainContactsActions">
              <a class="ppsgenChainContactsAction" href="<?php echo esc_url($contactEditUrl); ?>"><?php esc_html_e('Open Contact', 'ppsgen'); ?></a>
              <a class="ppsgenChainContactsAction" href="<?php echo esc_url($flowsUrl); ?>"><?php esc_html_e('All Flows', 'ppsgen'); ?></a>
              <a class="ppsgenChainContactsAction" href="<?php echo esc_url($flowLogsUrl); ?>"><?php esc_html_e('Flow Logs', 'ppsgen'); ?></a>
              <a class="ppsgenChainContactsAction" href="<?php echo esc_url($stepEventsUrl); ?>"><?php esc_html_e('Step Events', 'ppsgen'); ?></a>
              <?php if ($flowRowStatus === 'active') { ?>
              <button type="button" class="ppsgenChainContactsAction ppsgenChainContactsActionBtn ppsgenChainContactFlowAction" data-action="unsubscribeChainContactFlow" data-flow-id="<?php echo esc_attr($flowId); ?>" data-confirm="<?php esc_attr_e('Unsubscribe this contact from the current flow?', 'ppsgen'); ?>"><?php esc_html_e('Unsubscribe', 'ppsgen'); ?></button>
              <button type="button" class="ppsgenChainContactsAction ppsgenChainContactsActionBtn ppsgenChainContactFlowAction" data-action="exitChainContactFlow" data-flow-id="<?php echo esc_attr($flowId); ?>" data-confirm="<?php esc_attr_e('Exit this contact from the current flow?', 'ppsgen'); ?>"><?php esc_html_e('Exit Flow', 'ppsgen'); ?></button>
              <?php } ?>
                <button type="button" class="ppsgenChainContactsAction ppsgenChainContactsActionBtn ppsgenChainContactFlowAction ppsgenChainContactsActionDanger" data-action="deleteChainContactFlow" data-flow-id="<?php echo esc_attr($flowId); ?>" data-confirm="<?php esc_attr_e('Delete this flow for the contact? The contact will be removed from this campaign list.', 'ppsgen'); ?>"><?php esc_html_e('Delete Flow', 'ppsgen'); ?></button>
            </div>
          </div>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>

  <?php if ($totalPages > 1) { ?>
  <div class="tablenav ppsgen-pagination">
    <button type="submit" name="contacts_page" value="1" <?php disabled(1, $page); ?>>
      <?php echo esc_html(1); ?>
    </button>
    <?php
    $range = 2;
    $startPage = max(2, $page - $range);
    $endPage = min($totalPages - 1, $page + $range);
    if ($startPage > 2) { ?>
      <span class="ppsgen-pagination-dots">...</span>
    <?php }
    for ($i = $startPage; $i <= $endPage; $i++) { ?>
      <button type="submit" name="contacts_page" value="<?php echo esc_attr($i); ?>" <?php disabled($i, $page); ?>>
        <?php echo esc_html($i); ?>
      </button>
    <?php }
    if ($endPage < $totalPages - 1) { ?>
      <span class="ppsgen-pagination-dots">...</span>
    <?php } ?>
    <button type="submit" name="contacts_page" value="<?php echo esc_attr($totalPages); ?>" <?php disabled($totalPages, $page); ?>>
      <?php echo esc_html($totalPages); ?>
    </button>
  </div>
  <?php } ?>
  <?php echo $this->getFrame()->core('helper')->wpKses($bulkAssignTagsModal); ?>
</div>
