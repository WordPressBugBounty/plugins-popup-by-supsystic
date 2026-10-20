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
?>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Tags', 'ppsgen'); ?>
  </h2>
</div>

<form method="POST">

  <div class="ppsgenTableListTopButtons">
    <div class="ppsgenTableListTopButtonsLeft">
      <a class="ppsgenTableListBtn ppsgenTableListAddObj" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_tag_edit')); ?>"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add', 'ppsgen'); ?></a>
      <a class="ppsgenTableListBtn ppsgenTableListDeleteSelectedObj"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected', 'ppsgen'); ?></a>
    </div>
    <div class="ppsgenTableListTopButtonsRight">
      <?php $component->echoEscapedHtml($component->getAdminTableLimitFilter($order_by, $order_dir, $per_page)); ?>
    </div>
  </div>

  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>

  <table class="widefat fixed striped ppsgenTableList ppsgenModuleContactTableList">
    <thead>
      <tr>
        <th class="ppsgenTableListCell50"></th>
        <?php
        $headers = [
          'id' => 'ID',
          'slug' => 'Slug',
          'order_index' => 'Order',
          'created_at' => 'Created',
          'updated_at' => 'Edited',
        ];
        foreach ($headers as $key => $label):
          $dir = $order_by === $key && $order_dir === 'ASC' ? 'DESC' : 'ASC'; ?>
        <th data-key="<?php ppsgen_esc_attr($key); ?>">
          <button class="ppsgenTableListBtn ppsgenTableListBtnCenter ppsgenTableListBtnFilter ppsgenSetTableOrder"
            type="button" data-key="<?php ppsgen_esc_attr($key); ?>">
            <?php echo esc_html($label); ?>
            <?php if ($order_by === $key):
              echo $order_dir === 'ASC' ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
            endif; ?>
          </button>
        </th>
        <?php
        endforeach;
        ?>
        <th class="ppsgenTableListCell100"></th>
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
            case 'slug':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_slug" value="' . esc_attr($filters['slug'] ?? '') . '">';
              break;
            case 'order_index':
              echo '<input type="text" class="ppsgenTableListFilterInput" name="filter_order_index" value="' . esc_attr($filters['order_index'] ?? '') . '">';
              break;
          } ?>
        </th>
        <?php endforeach; ?>
        <th class="ppsgenTableListCell100"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td class="ppsgenTableListCell50"><input data-obj="<?php ppsgen_esc_attr($row['id']); ?>" type="checkbox" class="ppsgenTableListSelectCheckbox"></td>
        <?php foreach ($headers as $key => $_): ?>
        <td data-key="<?php ppsgen_esc_attr($key); ?>"><?php echo esc_html($row[$key] ?? ''); ?></td>
        <?php endforeach; ?>
        <td class="ppsgenTableListCell100" data-obj="<?php ppsgen_esc_attr($row['id']); ?>">
          <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_tag_edit&id=' . (int) $row['id'])); ?>"
            class="ppsgenTableListBtn ppsgenTableListEditObj "><span class="dashicons dashicons-edit"></span></a>
          <a class="ppsgenTableListBtn ppsgenTableListDeleteObj ppsgenTableListDeleteObjContactTag"><span
              class="dashicons dashicons-trash"></span></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php $component->echoEscapedHtml($component->getAdminPaginator($page, $totalPages)); ?>
  <?php $component->echoEscapedHtml($component->getAdminTableTotal($start, $end, $total)); ?>

</form>