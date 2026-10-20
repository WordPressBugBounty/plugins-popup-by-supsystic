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
$flowLogStatus = $this->getModel()->flowLogStatus();
?>

<style>
  .ppsgenDeleteUndeliveredBtn {
    display: none !important;
  }
</style>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Logs & scheduled tasks', 'ppsgen'); ?>
  </h2>
  <?php esc_html_e('Server Time', 'ppsgen'); ?> : <?php echo current_time('mysql'); ?>
</div>

<form method="POST">

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
      <a class="ppsgenTableListBtn ppsgenTableListDeleteSelectedObj"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected', 'ppsgen'); ?></a>
      <a class="ppsgenTableListBtn ppsgenTableListDeleteAll"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete All Logs', 'ppsgen'); ?></a>
      <div class="ppsgenTableListBtn ppsgenDeleteUndeliveredBtn" data-confirm="<?php esc_attr_e(
        'SMTP/IMAP ONLY! All contacts with undelivered emails due to “mailbox size exceeded” or “mailbox does not exist” or 4.x.x / 5.x.x errors will be marked as “email_error.” Bounced emails will be moved to the “Trash“ folder on your server.',
        'ppsgen',
      ); ?>"><span class="dashicons dashicons-tag"></span> <?php esc_html_e('Tag contacts with bounced emails', 'ppsgen'); ?></div>
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
          'step_id' => 'Step',
          'flow_id' => 'Flow',
          'run_at' => 'Run At',
          'status' => 'Status',
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
            case 'step_id':
              echo '<input type="text" class="ppsgenTableListFilterInput" placeholder="ID" name="filter_step_id" value="' . esc_attr($filters['step_id'] ?? '') . '">';
              break;
            case 'flow_id':
              echo '<input type="text" class="ppsgenTableListFilterInput" placeholder="ID" name="filter_flow_id" value="' . esc_attr($filters['flow_id'] ?? '') . '">';
              break;
            case 'run_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_run_at" value="' . esc_attr($filters['run_at'] ?? '') . '">';
              break;
            case 'status':
              echo '<select name="filter_' . esc_attr($key) . '" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              foreach ($flowLogStatus as $kSt => $vSt) {
                echo '<option value="' . esc_attr($kSt) . '"' . (!empty($filters[$key]) && $filters[$key] == $kSt ? ' selected' : '') . '>' . esc_attr($vSt) . '</option>';
              }
              echo '</select>';
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
        <td class="ppsgenTableListCell50"><input data-obj="<?php ppsgen_esc_attr($row['id']); ?>" type="checkbox" class="ppsgenTableListSelectCheckbox"></td>
        <?php foreach ($headers as $key => $_) { ?>
          <?php if ($key == 'contact_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $contactEmail = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}ppsgen_contact_field_values WHERE field_id = %d AND contact_id = %d", (int) $cfEmailId, (int) $row[$key])); ?>
              <?php echo '(' . esc_html($row[$key]) . ') ' . esc_html($contactEmail); ?>
            </td>
          <?php } elseif ($key == 'chain_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $chainTitle = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", (int) $row[$key])); ?>
              <?php echo '(' . esc_html($row[$key]) . ') ' . esc_html($chainTitle); ?>
            </td>
          <?php } elseif ($key == 'step_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $stepTitle = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", (int) $row[$key])); ?>
              <?php echo '(' . esc_html($row[$key]) . ') ' . esc_html($stepTitle); ?>
            </td>
          <?php } elseif ($key == 'status') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($flowLogStatus[$row[$key]] ?? ''); ?></td>
          <?php } elseif ($key == 'additional') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($flowLogStatus[$row[$key]] ?? ''); ?></td>
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

</form>
