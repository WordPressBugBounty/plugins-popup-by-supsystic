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
$filters = $this->filters;
$totalPages = ceil($total / $per_page);
$start = ($page - 1) * $per_page + 1;
$end = min($start + $per_page - 1, $total);
$isNewsletterList = ($this->listType ?? 'automation') === 'newsletter';
$automationLimit = (array) ($this->automationLimit ?? []);
$newsletterStatusLabels = [
  'draft' => __('Draft', 'ppsgen'),
  'scheduled' => __('Scheduled', 'ppsgen'),
  'preparing' => __('Preparing', 'ppsgen'),
  'sending' => __('Sending', 'ppsgen'),
  'sent' => __('Sent', 'ppsgen'),
];
$dateFormat = get_option('date_format') . ' ' . get_option('time_format');
?>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <?php if ($isNewsletterList) { ?>
      <span class="dashicons dashicons-email-alt"></span> <?php esc_html_e('Newsletters', 'ppsgen'); ?>
      <div class="ppsgenChainsListExtFeature ppsgenChainsListFreeNote"><?php esc_html_e('One-off emails · not counted as automations', 'ppsgen'); ?></div>
    <?php } else { ?>
      <span class="dashicons dashicons-admin-links"></span> <?php esc_html_e('Automations', 'ppsgen'); ?>
      <?php if (!empty($automationLimit['applies'])) { ?>
      <div class="ppsgenChainsListExtFeature"><?php echo esc_html(sprintf(ppsgen_automation_limit_text('badge'), min(1, (int) $automationLimit['running']))); ?></div>
      <?php } ?>
    <?php } ?>
  </h2>
  <div class="ppsgenChainsListHeaderLinks">
    <?php if (!empty($automationLimit['applies']) && (int) $automationLimit['total'] > 1) { ?>
    <span class="ppsgenMuted"><?php esc_html_e('Switch one off to run another.', 'ppsgen'); ?> <a href="<?php echo esc_url(ppsgen_automation_limit_url()); ?>"><?php echo esc_html(ppsgen_automation_limit_text('more_link')); ?> &rarr;</a></span>
    <?php } ?>
    <?php if (!$isNewsletterList) { ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_flows')); ?>"><?php esc_html_e('People in automations', 'ppsgen'); ?> &rarr;</a>
    <?php } ?>
    <span class="ppsgenChainsListServerTime"><?php esc_html_e('Server Time', 'ppsgen'); ?> : <?php echo current_time('mysql'); ?></span>
  </div>
</div>

<form method="POST">

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
      <?php if ($isNewsletterList) { ?>
      <a class="ppsgenTableListBtn ppsgenTableListAddObj" data-create-action="createNewsletter" data-prompt="<?php esc_attr_e('Name your newsletter, for example "October update":', 'ppsgen'); ?>" data-default-name="<?php esc_attr_e('New newsletter', 'ppsgen'); ?>"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add', 'ppsgen'); ?></a>
      <?php } else { ?>
      <a class="ppsgenTableListBtn ppsgenTableListAddObj"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add', 'ppsgen'); ?></a>
      <?php } ?>
      <a class="ppsgenTableListBtn ppsgenTableListDeleteSelectedObj"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected', 'ppsgen'); ?></a>
    </div>
    <div class="ppsgenTableListTopButtonsRight">
      <?php $component->echoEscapedHtml($component->getAdminTableLimitFilter($order_by, $order_dir, $per_page)); ?>
    </div>
  </div>

  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>

  <table class="widefat fixed striped ppsgenTableList ppsgenModulechainTableList">
    <thead>
      <tr>
        <th class="ppsgenTableListCell50"></th>
        <?php
        $headers = [
          'id' => 'ID',
          'name' => 'Name',
          'active' => __('Status', 'ppsgen'),
          'created_at' => 'Created',
          'updated_at' => 'Edited',
        ];
        foreach ($headers as $key => $label) { ?>
        <th data-key="<?php ppsgen_esc_attr($key); ?>">
          <button class="ppsgenTableListBtn ppsgenTableListBtnCenter ppsgenTableListBtnFilter ppsgenSetTableOrder"
            type="button" data-key="<?php ppsgen_esc_attr($key); ?>">
            <?php echo esc_html($label); ?>
            <?php if ($order_by === $key) {
              echo $order_dir === 'ASC' ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
            } ?>
          </button>
        </th>
        <?php } ?>
        <th class="ppsgenTableListCell120"></th>
      </tr>
      <tr>
        <th class="ppsgenTableListCell50"><input type="checkbox" class="ppsgenTableListSelectAllCheckbox"></th>
        <?php foreach ($headers as $key => $_) { ?>
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
            case 'name':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_name" value="' . esc_attr($filters['name'] ?? '') . '">';
              break;
            case 'order_index':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_order_index" value="' . esc_attr($filters['order_index'] ?? '') . '">';
              break;
            case 'active':
              echo '<select name="filter_' . esc_attr($key) . '" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              echo '<option value="x"' . (!empty($filters[$key]) && $filters[$key] == 'x' ? ' selected' : '') . '>x</option>';
              echo '<option value="y"' . (!empty($filters[$key]) && $filters[$key] == 'y' ? ' selected' : '') . '>&#10003;</option>';
              echo '</select>';
              break;
          } ?>
        </th>
        <?php } ?>
        <th class="ppsgenTableListCell100"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row) {
        $chainStats = !empty($row['stats']) && is_array($row['stats']) ? $row['stats'] : [];
        ?>
      <tr>
        <td class="ppsgenTableListCell50"><input data-obj="<?php ppsgen_esc_attr($row['id']); ?>" type="checkbox" class="ppsgenTableListSelectCheckbox"></td>
        <?php foreach ($headers as $key => $_) { ?>
          <?php if ($key == 'name') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <div class="ppsgenEcaChainListNameCell">
                <a class="ppsgenEcaChainListNameLink" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_chain_edit&id=' . (int) $row['id'])); ?>"><?php echo esc_html($row[$key] ?? ''); ?></a>
                <?php
                $peopleIn = (int) ($chainStats['processing'] ?? 0);
                $leftEarly = (int) ($chainStats['exited'] ?? 0) + (int) ($chainStats['unsubscribed'] ?? 0);
                ?>
                <?php if (!empty($row['newsletter'])) {
                  $nl = $row['newsletter'];
                  $nlState = $nl['state'];
                  $nlSent = in_array($nlState['status'], ['preparing', 'sending', 'sent'], true);
                  ?>
                <div class="ppsgenEcaChainListSummary">
                  <span>
                    <?php esc_html_e('Send to', 'ppsgen'); ?>:
                    <?php echo esc_html($nl['label']); ?>
                    (<?php echo esc_html(number_format_i18n($nlSent ? (int) $nlState['recipients'] : (int) $nl['audience'])); ?>)
                  </span>
                  <?php if ($nlSent) { ?>
                    <span><?php echo esc_html(sprintf(__('%s%% opened', 'ppsgen'), number_format_i18n((float) $nlState['opened_rate'], 1))); ?></span>
                    <span><?php echo esc_html(sprintf(__('%s%% clicked', 'ppsgen'), number_format_i18n((float) $nlState['clicked_rate'], 1))); ?></span>
                    <span><?php echo esc_html(sprintf(__('Sent %s', 'ppsgen'), date_i18n($dateFormat, (int) $nlState['sent_at']))); ?></span>
                  <?php } elseif ($nlState['status'] === 'scheduled') { ?>
                    <span><?php echo esc_html(sprintf(__('Scheduled for %s', 'ppsgen'), date_i18n($dateFormat, (int) $nlState['send_at']))); ?></span>
                  <?php } ?>
                </div>
                <?php } else { ?>
                <div class="ppsgenEcaChainListSummary">
                  <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_chain_edit&id=' . (int) $row['id'] . '&chain_edit_active_tab=contacts')); ?>"><?php echo esc_html(sprintf(_n('%s person in it', '%s people in it', $peopleIn, 'ppsgen'), number_format_i18n($peopleIn))); ?></a>
                  <span><?php echo esc_html(sprintf(__('%s finished', 'ppsgen'), number_format_i18n((int) ($chainStats['finished'] ?? 0)))); ?></span>
                  <span><?php echo esc_html(sprintf(__('%s left early', 'ppsgen'), number_format_i18n($leftEarly))); ?></span>
                  <span><?php echo esc_html(sprintf(__('%s emails sent', 'ppsgen'), number_format_i18n((int) ($chainStats['total_letters'] ?? 0)))); ?></span>
                  <span><?php echo esc_html(sprintf(__('%s opened', 'ppsgen'), number_format_i18n(max(0, (int) ($chainStats['unique_event_open'] ?? 0) - (int) ($chainStats['suspicious_unique_event_open'] ?? 0))))); ?></span>
                  <span><?php echo esc_html(sprintf(__('%s clicked', 'ppsgen'), number_format_i18n((int) ($chainStats['live_event_click'] ?? 0)))); ?></span>
                </div>
                <?php } ?>
                <details class="ppsgenEcaChainListMore">
                  <summary><?php esc_html_e('All numbers', 'ppsgen'); ?></summary>
                <div class="ppsgenEcaChainListStats">
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Total', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['total'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Processing', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['processing'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Finished', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['finished'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Exited', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['exited'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Unsubscribed', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['unsubscribed'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Letters', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['total_letters'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Events', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['total_events'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Unique Opens', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['unique_event_open'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Suspicious Opens', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['suspicious_unique_event_open'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Clicks', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['event_click'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Suspicious Clicks', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['suspicious_event_click'] ?? 0)); ?></span>
                  <span class="ppsgenEcaChainListStat"><strong><?php esc_html_e('Live Clicks', 'ppsgen'); ?>:</strong> <?php echo esc_html((int) ($chainStats['live_event_click'] ?? 0)); ?></span>
                </div>
                </details>
              </div>
            </td>
          <?php } elseif ($key == 'active') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php if (!empty($row['newsletter'])) {
                $nlStatus = $row['newsletter']['state']['status']; ?>
              <span class="ppsgenStatus ppsgenStatus-<?php echo esc_attr($nlStatus); ?>"><?php echo esc_html($newsletterStatusLabels[$nlStatus] ?? $nlStatus); ?></span>
              <?php } elseif (in_array((int) $row['id'], $automationLimit['held_ids'] ?? [], true)) { ?>
              <span class="ppsgenStatus ppsgenStatus-held" title="<?php echo esc_attr(sprintf(ppsgen_automation_limit_text('held_title'), $automationLimit['running_name'])); ?>"><?php echo esc_html(ppsgen_automation_limit_text('held_label')); ?></span>
              <?php } else { ?>
              <span class="ppsgenStatus ppsgenStatus-<?php echo !empty($row[$key]) ? 'on' : 'off'; ?>"><?php echo !empty($row[$key]) ? esc_html__('Running', 'ppsgen') : esc_html__('Off', 'ppsgen'); ?></span>
              <?php } ?>
            </td>
          <?php } else { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($row[$key] ?? ''); ?></td>
          <?php } ?>
        <?php } ?>
        <td class="ppsgenTableListCell120" data-obj="<?php ppsgen_esc_attr($row['id']); ?>">
          <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_chain_edit&id=' . (int) $row['id'])); ?>"
            class="ppsgenTableListBtn ppsgenTableListEditObj "><span class="dashicons dashicons-edit"></span></a>
          <a class="ppsgenTableListBtn ppsgenTableListCopyObj ppsgenTableListCopyObjChain" title="<?php echo __('Duplicate', 'ppsgen'); ?>"><span
              class="dashicons dashicons-admin-page"></span></a>
          <a class="ppsgenTableListBtn ppsgenTableListDeleteObj ppsgenTableListDeleteObjChain"><span
              class="dashicons dashicons-trash"></span></a>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>

  <?php $component->echoEscapedHtml($component->getAdminPaginator($page, $totalPages)); ?>
  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>

</form>
