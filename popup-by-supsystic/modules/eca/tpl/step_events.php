<?php
if (!defined('ABSPATH')) {
  exit();
}
global $wpdb;
$component = $this->component;
$rows = $this->rows;
$total = $this->total;
$page = $this->page;
$per_page = $this->per_page;
$order_by = $this->order_by;
$order_dir = $this->order_dir;
$filters = $this->filters;
$campaignOptions = is_array($this->campaignOptions ?? null) ? $this->campaignOptions : [];
$selectedCampaignIds = !empty($filters['chain_ids']) && is_array($filters['chain_ids']) ? array_values(array_map('intval', $filters['chain_ids'])) : [];
$totalPages = ceil($total / $per_page);
$start = ($page - 1) * $per_page + 1;
$end = min($start + $per_page - 1, $total);
$cfEmailId = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = 'email'");
$eventTypeStatus = $this->getModel()->eventTypeStatus();
$bulkAssignTagsModal = $this->getFrame()->useModule('contact', false, 'view', 'renderBulkAssignTagsModal', [['context' => 'eca_step_events']]);
?>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Email activity', 'ppsgen'); ?>
  </h2>
  <?php esc_html_e('Server Time', 'ppsgen'); ?> : <?php echo current_time('mysql'); ?>
</div>

<form method="POST" class="ppsgenBulkAssignTagsScope">

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
      <a class="ppsgenTableListBtn ppsgenTableListDeleteSelectedObj"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected', 'ppsgen'); ?></a>
      <a class="ppsgenTableListBtn ppsgenBulkAssignTagsTrigger"><span class="dashicons dashicons-tag"></span> <?php esc_html_e('Add Tag(s)', 'ppsgen'); ?></a>
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
          'contact_id' => 'Contact',
          'chain_id' => 'Campaigns',
          'flow_id' => 'Flow',
          'step_id' => 'Step',
          'event_type' => 'Event Type',
          'clicked_url' => 'URL',
          'ip_address' => 'IP',
          'user_agent' => 'User Agent',
          'referrer' => 'Referrer',
          'created_at' => 'Created',
        ];
        foreach ($headers as $key => $label) {
          $dir = $order_by === $key && $order_dir === 'ASC' ? 'DESC' : 'ASC'; ?>
        <th data-key="<?php ppsgen_esc_attr($key); ?>">
          <button class="ppsgenTableListBtn ppsgenTableListBtnCenter ppsgenTableListBtnFilter ppsgenSetTableOrder"
            type="button" data-key="<?php ppsgen_esc_attr($key); ?>">
            <?php echo esc_html($label); ?>
            <?php if ($order_by === $key) {
              echo $order_dir === 'ASC' ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
            } ?>
          </button>
        </th>
        <?php
        }
        ?>
        <th class="ppsgenTableListCell100"></th>
      </tr>
      <tr>
        <th class="ppsgenTableListCell50"><input type="checkbox" class="ppsgenTableListSelectAllCheckbox"></th>
        <?php foreach ($headers as $key => $_) { ?>
        <th data-key="<?php ppsgen_esc_attr($key); ?>">
          <?php switch ($key) {
            case 'id':
              echo '<input type="text" class="ppsgenTableListFilterInput" placeholder="ID" name="filter_id" value="' . esc_attr($filters['id'] ?? '') . '">';
              break;
            case 'contact_id':
              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              echo (string) $this->getFrame()->useModule('contact', false, 'view', 'renderContactLookupField', [[
                'field_name' => 'filter_contact_id',
                'selected_id' => (int) ($filters['contact_id'] ?? 0),
                'placeholder' => __('Search by contact ID or email', 'ppsgen'),
                'input_class' => 'ppsgenTableListFilterInput',
                'wrapper_class' => 'ppsgenEcaContactLookupFilter',
              ]]);
              break;
            case 'chain_id':
              $component->selectmultiple('filter_chain_ids[]', [
                'class' => 'ppsgenselect ppsgenTableListFilterInput',
                'options' => $campaignOptions,
                'selected' => $selectedCampaignIds,
              ]);
              break;
            case 'flow_id':
              echo '<input type="text" class="ppsgenTableListFilterInput" placeholder="ID" name="filter_flow_id" value="' . esc_attr($filters['flow_id'] ?? '') . '">';
              break;
            case 'step_id':
              echo '<input type="text" class="ppsgenTableListFilterInput" placeholder="ID" name="filter_step_id" value="' . esc_attr($filters['step_id'] ?? '') . '">';
              break;
            case 'event_type':
              echo '<select name="filter_event_type" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              foreach ($eventTypeStatus as $kSt => $vSt) {
                echo '<option value="' . esc_attr($kSt) . '"' . (!empty($filters[$key]) && $filters[$key] == $kSt ? ' selected' : '') . '>' . esc_attr($vSt) . '</option>';
              }
              echo '</select>';
              break;
            case 'user_agent':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_user_agent" value="' . esc_attr($filters['user_agent'] ?? '') . '">';
              break;
            case 'referrer':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_referrer" value="' . esc_attr($filters['referrer'] ?? '') . '">';
              break;
            case 'clicked_url':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_clicked_url" value="' . esc_attr($filters['clicked_url'] ?? '') . '">';
              break;
            case 'ip_address':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_ip_address" value="' . esc_attr($filters['ip_address'] ?? '') . '">';
              break;
            case 'created_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_created_at" value="' . esc_attr($filters['created_at'] ?? '') . '">';
              break;
          } ?>
        </th>
        <?php } ?>
        <th class="ppsgenTableListCell100"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row) { ?>
      <tr>
        <td class="ppsgenTableListCell50"><input data-obj="<?php ppsgen_esc_attr($row['id']); ?>" data-contact-id="<?php ppsgen_esc_attr((int) ($row['contact_id'] ?? 0)); ?>" type="checkbox" class="ppsgenTableListSelectCheckbox"></td>
        <?php foreach ($headers as $key => $_) { ?>
          <?php if ($key == 'contact_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $contactEmail = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE field_id = %d AND contact_id = %d", (int) $cfEmailId, (int) $row[$key])); ?>
              <?php echo '(' . esc_html((int) $row[$key]) . ') ' . esc_html($contactEmail); ?>
            </td>
          <?php } elseif ($key == 'chain_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $chainTitle = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", (int) $row[$key])); ?>
              <?php echo '(' . esc_html((int) $row[$key]) . ') ' . esc_html($chainTitle); ?>
            </td>
          <?php } elseif ($key == 'step_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $stepTitle = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", (int) $row[$key])); ?>
              <?php echo '(' . esc_html((int) $row[$key]) . ') ' . esc_html($stepTitle); ?>
            </td>
            <?php } elseif ($key == 'flow_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php echo esc_html((int) $row[$key]); ?>
            </td>
          <?php } elseif ($key == 'event_type') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($eventTypeStatus[$row[$key]] ?? ''); ?></td>
          <?php } elseif ($key == 'user_agent' || $key == 'clicked_url' || $key == 'referrer') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><div class="ppsgenTableTdSmallLongText"><?php echo esc_html($row[$key] ?? ''); ?></div></td>
          <?php } else { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($row[$key] ?? ''); ?></td>
          <?php } ?>
        <?php } ?>
        <td class="ppsgenTableListCell100" data-obj="<?php ppsgen_esc_attr($row['id']); ?>">
          <a class="ppsgenTableListBtn ppsgenTableListDeleteObj ppsgenTableListDeleteObjChain"><span
              class="dashicons dashicons-trash"></span></a>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>

  <?php $component->echoEscapedHtml($component->getAdminPaginator($page, $totalPages)); ?>
  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>
  <?php echo $this->getFrame()->core('helper')->wpKses($bulkAssignTagsModal); ?>

</form>
