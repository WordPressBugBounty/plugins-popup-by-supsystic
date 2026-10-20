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
$flowStatus = $this->getModel()->flowStatus();
$next1 = wp_next_scheduled('ppsgen_cron_eca_runEcaFlowCron');
$next2 = wp_next_scheduled('ppsgen_cron_eca_runEcaSendEmailCron');
$next11 = wp_next_scheduled('ppsgen_cron_ecaExt_runEcaFlowCron');
$next22 = wp_next_scheduled('ppsgen_cron_ecaExt_runEcaSendEmailCron');
$emailsLeft = $wpdb->get_var("SELECT DISTINCT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_mail_queue WHERE 1=1");
?>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-randomize"></span> <?php esc_html_e('People in automations', 'ppsgen'); ?>
  </h2>
  <div style="text-align:right; font-weight:bold;">
    <span class="dashicons dashicons-clock"></span> <?php esc_html_e('Server Time', 'ppsgen'); ?> : <span style="color:#3f51b5;"><?php echo current_time('mysql'); ?></span>
  </div>
</div>

<form method="POST">

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
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
          'contact_id' => 'Contact',
          'chain_id' => 'Campaigns',
          'current_step_id' => 'Step',
          'next_run_at' => 'Next Run',
          'status' => 'Status',
          'active' => 'Enabled',
          'created_at' => 'Created',
          'updated_at' => 'Edited',
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
            case 'current_step_id':
              echo '<input type="text" class="ppsgenTableListFilterInput" placeholder="ID" name="filter_current_step_id" value="' . esc_attr($filters['current_step_id'] ?? '') . '">';
              break;
            case 'next_run_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_next_run_at" value="' . esc_attr($filters['next_run_at'] ?? '') . '">';
              break;
            case 'created_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_created_at" value="' . esc_attr($filters['created_at'] ?? '') . '">';
              break;
            case 'updated_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_updated_at" value="' . esc_attr($filters['updated_at'] ?? '') . '">';
              break;
            case 'status':
              echo '<select name="filter_' . esc_attr($key) . '" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              foreach ($flowStatus as $kSt => $vSt) {
                echo '<option value="' . esc_attr($kSt) . '"' . (!empty($filters[$key]) && $filters[$key] == $kSt ? ' selected' : '') . '>' . esc_attr($vSt) . '</option>';
              }
              echo '</select>';
              break;
            case 'active':
              echo '<select name="filter_' . esc_attr($key) . '" class="ppsgenTableListFilterInput">';
              echo '<option value=""></option>';
              echo '<option value="x"' . (!empty($filters[$key]) && $filters[$key] == 'x' ? ' selected' : '') . '>x</option>';
              echo '<option value="y"' . (!empty($filters[$key]) && $filters[$key] == 'y' ? ' selected' : '') . '>✓</option>';
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
              <?php echo '(' . esc_html((int) $row[$key]) . ') ' . esc_html($contactEmail); ?>
            </td>
          <?php } elseif ($key == 'chain_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $chainTitle = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", (int) $row[$key])); ?>
              <?php echo '(' . esc_html((int) $row[$key]) . ') ' . esc_html($chainTitle); ?>
            </td>
          <?php } elseif ($key == 'current_step_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $stepTitle = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", (int) $row[$key])); ?>
              <?php echo '(' . esc_html((int) $row[$key]) . ') ' . esc_html($stepTitle); ?>
            </td>
          <?php } elseif ($key == 'status') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($flowStatus[$row[$key]] ?? ''); ?></td>
          <?php } elseif ($key == 'active') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php echo !empty($row[$key]) ? '✓' : 'x'; ?>
            </td>
          <?php } else { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($row[$key] ?? ''); ?></td>
          <?php } ?>
        <?php } ?>
        <td class="ppsgenTableListCell100" data-obj="<?php ppsgen_esc_attr($row['id']); ?>">
          <a class="ppsgenTableListBtn ppsgenTableListDeleteObj ppsgenTableListDeleteObjChain"><span class="dashicons dashicons-trash"></span></a>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>

  <?php $component->echoEscapedHtml($component->getAdminPaginator($page, $totalPages)); ?>
  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>
  
</form>
