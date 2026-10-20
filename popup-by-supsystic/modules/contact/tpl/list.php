<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$rows = $this->rows;
$total = $this->total;
$page = $this->page;
$per_page = $this->per_page;
$order_by = $this->order_by;
$order_dir = $this->order_dir;
$columns = $this->columns;
$stored_columns = $this->stored_columns;
$filters = $this->filters;
$fieldOptions = $this->fieldOptions;
$contactTagOptions = is_array($this->contactTagOptions ?? null) ? $this->contactTagOptions : [];
$engagementStats = is_array($this->engagementStats ?? null) ? $this->engagementStats : [];
$isEcaActive = !empty($this->isEcaActive);
$isWooCommerceActive = !empty($this->isWooCommerceActive);
$totalPages = ceil($total / $per_page);
$start = ($page - 1) * $per_page + 1;
$end = min($start + $per_page - 1, $total);
$rowIndex = 0;
$selectedTagFilterIds = !empty($filters['dynamic']['tag_titles']) && is_array($filters['dynamic']['tag_titles']) ? array_values(array_map('intval', $filters['dynamic']['tag_titles'])) : [];
$isActivityOrder = $order_by === 'contact_activity_at';
?>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Contacts', 'ppsgen'); ?>
  </h2>
  <?php $limitStatus = function_exists('ppsgen_get_contact_limit_status') ? ppsgen_get_contact_limit_status() : []; ?>
  <?php if (!empty($limitStatus['applies'])) { ?>
    <div class="ppsgenContactLimit<?php echo !empty($limitStatus['warn']) ? ' ppsgenContactLimitWarn' : ''; ?>">
      <span>
        <?php echo esc_html(sprintf(
          /* translators: 1: all contacts, 2: reachable contacts, 3: Free limit */
          __('%1$s contacts — %2$s of %3$s reachable on Free', 'ppsgen'),
          number_format_i18n($limitStatus['total']),
          number_format_i18n($limitStatus['reachable']),
          number_format_i18n($limitStatus['limit'])
        )); ?>
      </span>
      <span class="ppsgenMeter"><span style="width: <?php echo esc_attr($limitStatus['percent']); ?>%;"></span></span>
      <?php if (!empty($limitStatus['warn'])) { ?>
        <span class="ppsgenContactLimitHint">
          <?php echo !empty($limitStatus['over'])
            ? esc_html(sprintf(_n('%s new contact is saved but can’t be emailed on Free.', '%s new contacts are saved but can’t be emailed on Free.', $limitStatus['over'], 'ppsgen'), number_format_i18n($limitStatus['over'])))
            : esc_html__('Your audience is growing. New contacts are always saved; Free emails the first 500.', 'ppsgen'); ?>
          <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=settings_pro')); ?>"><?php esc_html_e('See your options →', 'ppsgen'); ?></a>
        </span>
      <?php } ?>
    </div>
  <?php } ?>
</div>

<form method="POST" class="ppsgenBulkAssignTagsScope">

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
      <a class="ppsgenTableListBtn ppsgenTableListAddObj" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_edit')); ?>"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add', 'ppsgen'); ?></a>
      <a class="ppsgenTableListBtn ppsgenTableListDeleteSelectedObj"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected', 'ppsgen'); ?></a>
      <a class="ppsgenTableListBtn ppsgenTableListContactExport"><span class="dashicons dashicons-upload"></span> <?php esc_html_e('Export', 'ppsgen'); ?></a>
      <a
        class="ppsgenTableListBtn ppsgenBulkAssignTagsTrigger"
        data-mode="add"
        data-modal-title="<?php echo esc_attr__('Add Tag(s) to Selected Contacts', 'ppsgen'); ?>"
        data-modal-description="<?php echo esc_attr__('Choose one or more tags to assign to the selected contacts. Existing contact tags will be kept and not duplicated.', 'ppsgen'); ?>"
        data-submit-label="<?php echo esc_attr__('Add Tag(s)', 'ppsgen'); ?>"
        data-submit-icon="dashicons-tag"
      ><span class="dashicons dashicons-tag"></span> <?php esc_html_e('Add Tag(s)', 'ppsgen'); ?></a>
      <a
        class="ppsgenTableListBtn ppsgenBulkAssignTagsTrigger ppsgenBulkDeleteTagsTrigger"
        data-mode="delete"
        data-modal-title="<?php echo esc_attr__('Delete Tag(s) from Selected Contacts', 'ppsgen'); ?>"
        data-modal-description="<?php echo esc_attr__('Choose one or more tags to remove from the selected contacts. Tags that are not assigned already will be skipped.', 'ppsgen'); ?>"
        data-submit-label="<?php echo esc_attr__('Delete Tag(s)', 'ppsgen'); ?>"
        data-submit-icon="dashicons-dismiss"
      ><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Delete Tag(s)', 'ppsgen'); ?></a>
    </div>
    <div class="ppsgenTableListTopButtonsRight">
      <span class="ppsgenSelectFilterColumnsWrapper">
        <label><?php esc_html_e('Select Specific Contact Fields:', 'ppsgen'); ?></label>
        <select class="ppsgenselect" name="columns[]" multiple size="5">
          <?php if (empty($columns) && is_array($stored_columns)) {
            $columns = $stored_columns;
          } ?>
          <?php foreach ($fieldOptions as $slug => $field): ?>
          <option value="<?php echo esc_attr($slug); ?>" <?php selected(in_array($slug, $columns, true)); ?>>
            <?php echo esc_html($field->label); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </span>
      <?php $component->echoEscapedHtml($component->getAdminTableLimitFilter($order_by, $order_dir, $per_page)); ?>
    </div>
  </div>

  <?php
  $savedLists = is_array($this->savedLists ?? null) ? $this->savedLists : [];
  $activeListId = (string) ($this->activeListId ?? '');
  $hasFilterPost = !empty(array_filter(array_keys($_POST), function ($key) { // phpcs:ignore WordPress.Security.NonceVerification
    return strpos((string) $key, 'filter_') === 0;
  }));
  ?>
  <div class="ppsgenContactListsBar">
    <span class="ppsgenContactListsLabel"><?php esc_html_e('Lists', 'ppsgen'); ?>:</span>
    <a class="ppsgenContactListPill<?php echo $activeListId === '' && !$hasFilterPost ? ' ppsgenContactListPillOn' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_list')); ?>"><?php esc_html_e('All contacts', 'ppsgen'); ?></a>
    <?php foreach ($savedLists as $list) { ?>
      <span class="ppsgenContactListPill<?php echo $activeListId === (string) $list['id'] ? ' ppsgenContactListPillOn' : ''; ?>" data-list-id="<?php echo esc_attr($list['id']); ?>" data-filters="<?php echo esc_attr(wp_json_encode($list['filters'])); ?>">
        <a href="#" class="ppsgenContactListApply"><?php echo esc_html($list['name']); ?></a>
        <a href="#" class="ppsgenContactListDelete" title="<?php esc_attr_e('Delete list', 'ppsgen'); ?>" data-confirm="<?php esc_attr_e('Delete this list? Contacts are not deleted.', 'ppsgen'); ?>">&times;</a>
      </span>
    <?php } ?>
    <a class="ppsgenTableListBtn ppsgenContactListSave" data-prompt="<?php esc_attr_e('Name this list, for example "Enquiries":', 'ppsgen'); ?>"><span class="dashicons dashicons-saved"></span> <?php esc_html_e('Save filters as list', 'ppsgen'); ?></a>
    <span class="ppsgenContactListsHint"><?php esc_html_e('Set filters in the table header, press Apply Filters, then save them as a list.', 'ppsgen'); ?></span>
  </div>

  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>

  <table class="widefat fixed striped ppsgenTableList ppsgenModuleContactTableList">
    <thead>
      <tr>
        <th class="ppsgenTableListCell50"></th>
        <?php
        $headers = [
          'id' => 'ID',
          'tag_titles' => 'Tags',
        ] + array_combine($columns, array_map('ucfirst', $columns)) + [
          'wp_user_id' => 'User',
          'verified' => 'Verified',
          'created_at' => 'Created',
          'updated_at' => 'Edited',
        ];
        foreach ($headers as $key => $label): ?>
        <th data-key="<?php ppsgen_esc_attr($key); ?>">
          <button class="ppsgenTableListBtn ppsgenTableListBtnCenter ppsgenTableListBtnFilter ppsgenSetTableOrder" type="button" data-key="<?php echo esc_attr($key); ?>">
            <?php echo esc_html($label); ?>
            <?php if ($order_by === $key) {
              echo $order_dir === 'ASC' ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
            } ?>
          </button>
        </th>
        <?php endforeach; ?>
        <th class="ppsgenTableListCell120">
          <button
            class="ppsgenTableListBtn ppsgenTableListBtnCenter ppsgenTableListBtnFilter ppsgenSetTableOrder"
            type="button"
            data-key="contact_activity_at"
            data-default-dir="DESC"
            title="<?php esc_attr_e('Sort contacts by the latest engagement and incoming email activity.', 'ppsgen'); ?>"
          >
            <?php esc_html_e('Activity', 'ppsgen'); ?>
            <?php if ($isActivityOrder) {
              echo $order_dir === 'ASC' ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
            } ?>
          </button>
        </th>
      </tr>
      <tr>
        <th class="ppsgenTableListCell50"><input type="checkbox" class="ppsgenTableListSelectAllCheckbox"></th>
        <?php foreach ($headers as $key => $_): ?>
        <th data-key="<?php ppsgen_esc_attr($key); ?>">
          <?php switch ($key) {
            case 'id':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_id" value="' . esc_attr($filters['id'] ?? '') . '">';
              break;
            case 'created_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_created_at" value="' . esc_attr($filters['created_at'] ?? '') . '">';
              break;
            case 'updated_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_updated_at" value="' . esc_attr($filters['updated_at'] ?? '') . '">';
              break;
            case 'wp_user_id':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_wp_user_id" value="' . esc_attr($filters['wp_user_id'] ?? '') . '">';
              break;
            case 'verified':
              echo '<select name="filter_verified" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              echo '<option value="x"' . (!empty($filters[$key]) && $filters[$key] == 'x' ? ' selected' : '') . '>x</option>';
              echo '<option value="y"' . (!empty($filters[$key]) && $filters[$key] == 'y' ? ' selected' : '') . '>&#10003;</option>';
              echo '</select>';
              break;
            case 'tag_titles':
              $component->selectmultiple('filter_dynamic_tag_titles[]', [
                'class' => 'ppsgenselect ppsgenTableListFilterInput',
                'options' => $contactTagOptions,
                'selected' => $selectedTagFilterIds,
              ]);
              break;
            default:
              $slug = $key;
              $fieldVal = $this->getFrame()->core('request')->getVar("filter_dynamic_$slug", 'post');
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_dynamic_' . esc_attr($slug) . '" value="' . esc_attr($fieldVal ?? '') . '">';
              break;
          } ?>
        </th>
        <?php endforeach; ?>
        <th class="ppsgenTableListCell120"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <?php
      $contactId = (int) ($row['id'] ?? 0);
      $contactStats = $engagementStats[$contactId] ?? [];
      $wooTotalSpentFormatted = $isWooCommerceActive && function_exists('wc_price')
        ? wp_strip_all_tags(wc_price((float) ($contactStats['woo_total_spent'] ?? 0)), true)
        : number_format_i18n((float) ($contactStats['woo_total_spent'] ?? 0), 2);
      $wooAovFormatted = $isWooCommerceActive && function_exists('wc_price')
        ? wp_strip_all_tags(wc_price((float) ($contactStats['woo_aov'] ?? 0)), true)
        : number_format_i18n((float) ($contactStats['woo_aov'] ?? 0), 2);
      $incomingUnhandledTotal = (int) ($contactStats['incoming_email_unhandled_total'] ?? 0);
      $incomingIndicatorTitle = $incomingUnhandledTotal > 0
        ? sprintf(
          _n('%d new incoming email needs attention', '%d new incoming emails need attention', $incomingUnhandledTotal, 'ppsgen'),
          $incomingUnhandledTotal,
        )
        : __('No new incoming emails', 'ppsgen');
      $leadClass = 'ppsgenLeadInactive';
      if (($contactStats['label'] ?? '') === __('Hot', 'ppsgen')) {
        $leadClass = 'ppsgenLeadHot';
      } elseif (in_array(($contactStats['label'] ?? ''), [__('Medium', 'ppsgen'), __('Warm', 'ppsgen')], true)) {
        $leadClass = 'ppsgenLeadWarm';
      } elseif (($contactStats['label'] ?? '') === __('Cold', 'ppsgen')) {
        $leadClass = 'ppsgenLeadCold';
      } elseif (($contactStats['label'] ?? '') === __('Unsubscribed', 'ppsgen')) {
        $leadClass = 'ppsgenLeadUnsubscribed';
      }
      ?>
      <?php $rowToneClass = ($rowIndex % 2 === 0) ? 'ppsgenContactRowToneA' : 'ppsgenContactRowToneB'; ?>
      <tr class="<?php echo esc_attr($rowToneClass); ?> ppsgenContactRow" data-contact-id="<?php echo esc_attr($contactId); ?>">
        <td class="ppsgenTableListCell50"><input data-obj="<?php ppsgen_esc_attr($contactId); ?>" type="checkbox" class="ppsgenTableListSelectCheckbox"></td>
        <?php foreach ($headers as $key => $_): ?>
        <td data-key="<?php ppsgen_esc_attr($key); ?>">
          <div class="ppsgenTableListCellContent">
            <?php if ($key === 'id') { ?>
              <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_edit&id=' . $contactId)); ?>"><?php echo esc_html($row[$key] ?? ''); ?></a>
            <?php } elseif ($key === 'verified') { ?>
              <?php echo !empty($row[$key]) ? '&#10003;' : 'x'; ?>
            <?php } else { ?>
              <?php echo esc_html($row[$key] ?? ''); ?>
            <?php } ?>
          </div>
        </td>
        <?php endforeach; ?>
        <td class="ppsgenTableListCell100" data-obj="<?php ppsgen_esc_attr($contactId); ?>">
          <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_edit&id=' . $contactId)); ?>" class="ppsgenTableListBtn ppsgenTableListEditObj"><span class="dashicons dashicons-edit"></span></a>
          <a class="ppsgenTableListBtn ppsgenTableListDeleteObj ppsgenTableListDeleteObjContact"><span class="dashicons dashicons-trash"></span></a>
        </td>
      </tr>
      <tr class="ppsgenContactEngagementRow <?php echo esc_attr($rowToneClass); ?> ppsgenContactRow" data-contact-id="<?php echo esc_attr($contactId); ?>">
        <td colspan="<?php echo esc_attr(count($headers) + 2); ?>">
          <div class="ppsgenContactEngagementMini">
            <span class="ppsgenContactEngagementPill ppsgenContactEngagementScore <?php echo esc_attr($leadClass); ?>"><?php echo esc_html((int) ($contactStats['percent'] ?? 0)); ?>% <?php echo esc_html($contactStats['label'] ?? __('Inactive', 'ppsgen')); ?></span>
            <?php if ($isEcaActive) { ?>
              <span class="ppsgenContactEngagementGroup">
                <span class="ppsgenContactEngagementGroupLabel"><?php esc_html_e('Email', 'ppsgen'); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('State', 'ppsgen'); ?>: <?php echo esc_html($contactStats['activity_state'] ?? __('No Flow', 'ppsgen')); ?></span>
                <span class="ppsgenContactEngagementMetric">
                  <span class="ppsgenContactEngagementMetricTitle"><?php esc_html_e('Opens', 'ppsgen'); ?></span>
                  <span class="ppsgenContactEngagementMetricValue is-total" title="<?php echo esc_attr(sprintf(__('Total Opens: %d', 'ppsgen'), (int) ($contactStats['open_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['open_total'] ?? 0)); ?></span>
                  <span class="ppsgenContactEngagementMetricValue is-suspicious" title="<?php echo esc_attr(sprintf(__('Suspicious Opens: %d', 'ppsgen'), (int) ($contactStats['open_suspicious_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['open_suspicious_total'] ?? 0)); ?></span>
                  <span class="ppsgenContactEngagementMetricValue is-live" title="<?php echo esc_attr(sprintf(__('Live Opens: %d', 'ppsgen'), (int) ($contactStats['open_live_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['open_live_total'] ?? 0)); ?></span>
                </span>
                <span class="ppsgenContactEngagementMetric">
                  <span class="ppsgenContactEngagementMetricTitle"><?php esc_html_e('Clicks', 'ppsgen'); ?></span>
                  <span class="ppsgenContactEngagementMetricValue is-total" title="<?php echo esc_attr(sprintf(__('Total Clicks: %d', 'ppsgen'), (int) ($contactStats['click_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['click_total'] ?? 0)); ?></span>
                  <span class="ppsgenContactEngagementMetricValue is-suspicious" title="<?php echo esc_attr(sprintf(__('Suspicious Clicks: %d', 'ppsgen'), (int) ($contactStats['click_suspicious_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['click_suspicious_total'] ?? 0)); ?></span>
                  <span class="ppsgenContactEngagementMetricValue is-live" title="<?php echo esc_attr(sprintf(__('Live Clicks: %d', 'ppsgen'), (int) ($contactStats['click_live_total'] ?? 0))); ?>"><?php echo esc_html((int) ($contactStats['click_live_total'] ?? 0)); ?></span>
                </span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('Active Flows', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactStats['active_flows'] ?? 0)); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('Finished', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactStats['finished_flows'] ?? 0)); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('Exited', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactStats['exited_flows'] ?? 0)); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('Unsubscribed', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactStats['unsubscribed_flows'] ?? 0)); ?></span>
              </span>
            <?php } ?>
            <?php if (!empty($contactStats['conversion_total'])) { ?>
              <span class="ppsgenContactEngagementGroup">
                <span class="ppsgenContactEngagementGroupLabel"><?php esc_html_e('Conversions', 'ppsgen'); ?></span>
                <span class="ppsgenContactEngagementPill ppsgenContactConversionPill"><?php esc_html_e('Paid', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactStats['conversion_paid_total'] ?? 0)); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('Value', 'ppsgen'); ?>: <?php echo esc_html((string) ($contactStats['conversion_paid_value_label'] ?? '0')); ?></span>
              </span>
            <?php } ?>
            <span class="ppsgenContactEngagementGroup">
              <span class="ppsgenContactEngagementGroupLabel"><?php esc_html_e('Forms', 'ppsgen'); ?></span>
              <span class="ppsgenContactEngagementPill"><?php esc_html_e('Submits', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactStats['total_submits'] ?? 0)); ?></span>
            </span>
            <?php if ($isWooCommerceActive) { ?>
              <span class="ppsgenContactEngagementGroup">
                <span class="ppsgenContactEngagementGroupLabel"><?php esc_html_e('WooCommerce', 'ppsgen'); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('Orders', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactStats['woo_orders_total'] ?? 0)); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('Total Spend', 'ppsgen'); ?>: <?php echo esc_html($wooTotalSpentFormatted); ?></span>
                <span class="ppsgenContactEngagementPill"><?php esc_html_e('AOV', 'ppsgen'); ?>: <?php echo esc_html($wooAovFormatted); ?></span>
              </span>
            <?php } ?>
            <span class="ppsgenContactEngagementGroup ppsgenContactEngagementGroupActivity">
              <span class="ppsgenContactEngagementGroupLabel"><?php esc_html_e('Activity', 'ppsgen'); ?></span>
              <span class="ppsgenContactEngagementPill ppsgenContactActivityPill">
                <span class="ppsgenContactIncomingIndicatorBadge<?php echo !empty($contactStats['has_unhandled_incoming_email']) ? ' is-active' : ''; ?>" title="<?php echo esc_attr($incomingIndicatorTitle); ?>">
                  <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                </span>
                <?php echo esc_html($contactStats['last_activity_text'] ?? __('No activity yet', 'ppsgen')); ?>
              </span>
            </span>
          </div>
        </td>
      </tr>
      <?php $rowIndex++; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php $component->echoEscapedHtml($component->getAdminPaginator($page, $totalPages)); ?>
  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>
  <?php echo $this->getFrame()->core('helper')->wpKses($this->renderBulkAssignTagsModal(['context' => 'contact_list'])); ?>
  <aside class="ppsgenContactDrawer" aria-label="<?php esc_attr_e('Contact details', 'ppsgen'); ?>" data-loading="<?php esc_attr_e('Loading…', 'ppsgen'); ?>" hidden></aside>

</form>
