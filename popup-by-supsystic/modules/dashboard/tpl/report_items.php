<?php
if (!defined('ABSPATH')) {
  exit();
}
$type = (string) ($this->reportType ?? 'popup');
$rows = is_array($this->reportRows ?? null) ? $this->reportRows : [];
$isPopup = $type === 'popup';
$statsTab = $isPopup ? 'stats' : 'submits';
$totals = ['views' => 0, 'unique' => 0, 'results' => 0];
foreach ($rows as $row) {
  $stats = $row['stats'];
  if ($isPopup) {
    $totals['views'] += (int) ($stats['total_views'] ?? 0);
    $totals['unique'] += (int) ($stats['unique_views'] ?? 0);
    $totals['results'] += (int) ($stats['actions'] ?? 0) + (int) ($stats['form_submits'] ?? 0);
  } else {
    $totals['views'] += (int) ($stats['form_view'] ?? 0);
    $totals['results'] += (int) ($stats['submit_success'] ?? 0);
  }
}
$base = $isPopup ? $totals['unique'] : $totals['views'];
$totalRate = $base > 0 ? number_format_i18n($totals['results'] / $base * 100, 1) . '%' : '—';
$percent = function ($value) {
  return number_format_i18n((float) $value, 1) . '%';
};
?>
<div class="ppsgenHome ppsgenReport">
  <div class="ppsgenPageHead">
    <div>
      <h1 class="ppsgenPageTitle"><?php echo esc_html($isPopup ? __('Popups', 'ppsgen') : __('Forms', 'ppsgen')); ?></h1>
      <p class="ppsgenPageSub"><?php echo esc_html($isPopup ? __('How often each popup is seen and how many visitors act on it. All time.', 'ppsgen') : __('How often each form is seen and how many people send it. All time.', 'ppsgen')); ?></p>
    </div>
  </div>

  <div class="ppsgenStatCards">
    <div class="ppsgenCard ppsgenStatCard">
      <div class="ppsgenStatLabel"><?php echo esc_html($isPopup ? __('Times seen', 'ppsgen') : __('Form views', 'ppsgen')); ?></div>
      <div class="ppsgenStatValue"><?php echo esc_html(number_format_i18n($totals['views'])); ?></div>
      <div class="ppsgenStatSub"><?php echo esc_html($isPopup ? sprintf(__('%s unique visitors', 'ppsgen'), number_format_i18n($totals['unique'])) : __('Across all forms', 'ppsgen')); ?></div>
    </div>
    <div class="ppsgenCard ppsgenStatCard">
      <div class="ppsgenStatLabel"><?php echo esc_html($isPopup ? __('Clicks and submissions', 'ppsgen') : __('Successful submissions', 'ppsgen')); ?></div>
      <div class="ppsgenStatValue"><?php echo esc_html(number_format_i18n($totals['results'])); ?></div>
      <div class="ppsgenStatSub"><?php echo esc_html(sprintf(_n('%s item', '%s items', count($rows), 'ppsgen'), number_format_i18n(count($rows)))); ?></div>
    </div>
    <div class="ppsgenCard ppsgenStatCard">
      <div class="ppsgenStatLabel"><?php esc_html_e('Conversion', 'ppsgen'); ?></div>
      <div class="ppsgenStatValue"><?php echo esc_html($totalRate); ?></div>
      <div class="ppsgenStatSub"><?php echo esc_html($isPopup ? __('Clicks and submissions ÷ unique visitors', 'ppsgen') : __('Successful submissions ÷ views', 'ppsgen')); ?></div>
    </div>
  </div>

  <section class="ppsgenCard">
    <?php if (empty($rows)) { ?>
      <p class="ppsgenEmpty">
        <?php echo esc_html($isPopup ? __('No popups yet.', 'ppsgen') : __('No forms yet.', 'ppsgen')); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=template_add&template_type=' . $type)); ?>"><?php echo esc_html($isPopup ? __('Create a popup →', 'ppsgen') : __('Create a form →', 'ppsgen')); ?></a>
      </p>
    <?php } else { ?>
      <table class="ppsgenHomeTable">
        <thead>
          <tr>
            <th><?php echo esc_html($isPopup ? __('Popup', 'ppsgen') : __('Form', 'ppsgen')); ?></th>
            <th><?php esc_html_e('Status', 'ppsgen'); ?></th>
            <?php if ($isPopup) { ?>
              <th><?php esc_html_e('Seen', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Unique visitors', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Clicks', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Submissions', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Conversion', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Closed straight away', 'ppsgen'); ?></th>
            <?php } else { ?>
              <th><?php esc_html_e('Views', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Sent', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Successful', 'ppsgen'); ?></th>
              <th><?php esc_html_e('With errors', 'ppsgen'); ?></th>
              <th><?php esc_html_e('Conversion', 'ppsgen'); ?></th>
            <?php } ?>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row) {
            $stats = $row['stats']; ?>
            <tr>
              <td><a href="<?php echo esc_url($row['edit_url']); ?>"><?php echo esc_html($row['name']); ?></a></td>
              <td><span class="ppsgenStatus ppsgenStatus-<?php echo $row['active'] ? 'on' : 'off'; ?>"><?php echo esc_html($row['active'] ? __('Live', 'ppsgen') : __('Off', 'ppsgen')); ?></span></td>
              <?php if ($isPopup) { ?>
                <td><?php echo esc_html(number_format_i18n((int) ($stats['total_views'] ?? 0))); ?></td>
                <td><?php echo esc_html(number_format_i18n((int) ($stats['unique_views'] ?? 0))); ?></td>
                <td><?php echo esc_html(number_format_i18n((int) ($stats['actions'] ?? 0))); ?></td>
                <td><?php echo esc_html(number_format_i18n((int) ($stats['form_submits'] ?? 0))); ?></td>
                <td><strong><?php echo esc_html($percent($stats['ctr'] ?? 0)); ?></strong></td>
                <td class="ppsgenMuted"><?php echo esc_html($percent($stats['bounce_rate'] ?? 0)); ?></td>
              <?php } else { ?>
                <td><?php echo esc_html(number_format_i18n((int) ($stats['form_view'] ?? 0))); ?></td>
                <td><?php echo esc_html(number_format_i18n((int) ($stats['submit_total'] ?? 0))); ?></td>
                <td><?php echo esc_html(number_format_i18n((int) ($stats['submit_success'] ?? 0))); ?></td>
                <td class="ppsgenMuted"><?php echo esc_html(number_format_i18n((int) ($stats['submit_error'] ?? 0))); ?></td>
                <td><strong><?php echo esc_html($percent($stats['view_to_success_rate'] ?? 0)); ?></strong></td>
              <?php } ?>
              <td class="ppsgenReportLink"><a href="<?php echo esc_url(add_query_arg('obj_edit_active_tab', $statsTab, $row['edit_url'])); ?>"><?php esc_html_e('Details →', 'ppsgen'); ?></a></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    <?php } ?>
  </section>
</div>
