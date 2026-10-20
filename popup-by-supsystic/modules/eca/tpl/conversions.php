<?php
if (!defined('ABSPATH')) {
  exit();
}
global $wpdb;
$component = $this->component;
$rows = $this->rows;
$total = $this->total;
$summary = is_array($this->summary ?? null) ? $this->summary : ['conversions_total' => 0, 'value_total' => 0];
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
$conversionStatus = $this->getModel()->conversionStatus();
$attributionMethods = $this->getModel()->attributionMethodStatus();
?>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-chart-line"></span> <?php esc_html_e('Conversions', 'ppsgen'); ?>
  </h2>
  <?php esc_html_e('Server Time', 'ppsgen'); ?> : <?php echo current_time('mysql'); ?>
</div>

<form method="POST">

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
      <a class="ppsgenTableListBtn ppsgenTableListDeleteSelectedObj"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected', 'ppsgen'); ?></a>
      <span class="ppsgenTableListBtn">
        <?php esc_html_e('Conversions:', 'ppsgen'); ?> <?php echo esc_html((int) ($summary['conversions_total'] ?? 0)); ?>
      </span>
      <span class="ppsgenTableListBtn">
        <?php esc_html_e('Total Value:', 'ppsgen'); ?> <?php echo esc_html(number_format_i18n((float) ($summary['value_total'] ?? 0), 2)); ?>
      </span>
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
          'conversion_email' => 'Email',
          'contact_id' => 'Contact',
          'chain_id' => 'Campaign',
          'step_id' => 'Step',
          'attribution_method' => 'Attribution',
          'conversion_key' => 'Key',
          'external_id' => 'External ID',
          'status' => 'Status',
          'value' => 'Value',
          'currency' => 'Currency',
          'source' => 'Source',
          'product_name' => 'Product',
          'converted_at' => 'Converted',
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
            case 'conversion_email':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_conversion_email" value="' . esc_attr($filters['conversion_email'] ?? '') . '">';
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
            case 'attribution_method':
              echo '<select name="filter_attribution_method" class="ppsgenTableListFilterInput"><option value=""></option>';
              foreach ($attributionMethods as $kSt => $vSt) {
                echo '<option value="' . esc_attr($kSt) . '"' . (!empty($filters[$key]) && $filters[$key] == $kSt ? ' selected' : '') . '>' . esc_html($vSt) . '</option>';
              }
              echo '</select>';
              break;
            case 'conversion_key':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_conversion_key" value="' . esc_attr($filters['conversion_key'] ?? '') . '">';
              break;
            case 'external_id':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_external_id" value="' . esc_attr($filters['external_id'] ?? '') . '">';
              break;
            case 'status':
              echo '<select name="filter_status" class="ppsgenTableListFilterInput"><option value=""></option>';
              foreach ($conversionStatus as $kSt => $vSt) {
                echo '<option value="' . esc_attr($kSt) . '"' . (!empty($filters[$key]) && $filters[$key] == $kSt ? ' selected' : '') . '>' . esc_html($vSt) . '</option>';
              }
              echo '</select>';
              break;
            case 'currency':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_currency" value="' . esc_attr($filters['currency'] ?? '') . '">';
              break;
            case 'source':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_source" value="' . esc_attr($filters['source'] ?? '') . '">';
              break;
            case 'product_name':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_product_name" value="' . esc_attr($filters['product_name'] ?? '') . '">';
              break;
            case 'converted_at':
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_converted_from" value="' . esc_attr($filters['converted_from'] ?? '') . '">';
              echo '<input type="date" class="ppsgenTableListFilterInput" name="filter_converted_to" value="' . esc_attr($filters['converted_to'] ?? '') . '">';
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
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html((int) $row[$key]); ?></td>
          <?php } elseif ($key == 'chain_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $chainTitle = !empty($row[$key]) ? $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", (int) $row[$key])) : ''; ?>
              <?php echo !empty($row[$key]) ? '(' . esc_html((int) $row[$key]) . ') ' . esc_html($chainTitle) : ''; ?>
            </td>
          <?php } elseif ($key == 'step_id') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>">
              <?php $stepTitle = !empty($row[$key]) ? $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE id = %d", (int) $row[$key])) : ''; ?>
              <?php echo !empty($row[$key]) ? '(' . esc_html((int) $row[$key]) . ') ' . esc_html($stepTitle) : ''; ?>
            </td>
          <?php } elseif ($key == 'attribution_method') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($attributionMethods[$row[$key]] ?? $row[$key]); ?></td>
          <?php } elseif ($key == 'status') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($conversionStatus[$row[$key]] ?? $row[$key]); ?></td>
          <?php } elseif ($key == 'value') { ?>
            <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html(number_format_i18n((float) $row[$key], 2)); ?></td>
          <?php } elseif (in_array($key, ['conversion_email', 'external_id', 'product_name'], true)) { ?>
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

</form>
