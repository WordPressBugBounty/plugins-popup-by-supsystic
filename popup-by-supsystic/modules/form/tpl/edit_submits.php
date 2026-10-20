<?php
if (!defined('ABSPATH')) {
  exit();
}
$options = $this->options;
$objId = $options['obj_id'];
$formSubmits = !empty($this->formSubmits) ? $this->formSubmits : [];
$trend = is_array($this->formTrend ?? null) ? $this->formTrend : [];
$trendGroup = !empty($this->formTrendGroup) ? $this->formTrendGroup : 'day';
$trendRange = !empty($this->formTrendRange) ? (int) $this->formTrendRange : 30;
$stats = [];
if (!empty($objId)) {
  $stats['form_view'] = $this->getModel()->getOption('form_view', 'stats', $objId, 'form');
  $stats['submit_total'] = $this->getModel()->getOption('submit_total', 'stats', $objId, 'form');
  $stats['submit_error'] = $this->getModel()->getOption('submit_error', 'stats', $objId, 'form');
  $stats['submit_success'] = $this->getModel()->getOption('submit_success', 'stats', $objId, 'form');
}
$stats['form_view'] = !empty($stats['form_view']) ? $stats['form_view'] : 0;
$stats['submit_total'] = !empty($stats['submit_total']) ? $stats['submit_total'] : 0;
$stats['submit_error'] = !empty($stats['submit_error']) ? $stats['submit_error'] : 0;
$stats['submit_success'] = !empty($stats['submit_success']) ? $stats['submit_success'] : 0;
$trendBaseUrl = admin_url('admin.php?page=ppsgen&tab=form_edit&id=' . (int) $objId . '&obj_edit_active_tab=submits');
$trendMax = 0;
foreach ($trend as $point) {
  $trendMax = max($trendMax, (int) ($point['submits'] ?? 0), (int) ($point['unique_contacts'] ?? 0));
}
?>
<?php if (!empty($stats) && is_array($stats)) { ?>
  <?php
  $ctr = $stats['form_view'] > 0 ? round(($stats['submit_total'] / $stats['form_view']) * 100, 2) : 0;
  $successRate = $stats['submit_total'] > 0 ? round(($stats['submit_success'] / $stats['submit_total']) * 100, 2) : 0;
  $errorRate = $stats['submit_total'] > 0 ? round(($stats['submit_error'] / $stats['submit_total']) * 100, 2) : 0;
  $viewToSuccessRate = $stats['form_view'] > 0 ? round(($stats['submit_success'] / $stats['form_view']) * 100, 2) : 0;
  ?>
  <div class="ppsgenFormStatsActions">
    <a class="ppsgenTableListBtn ppsgenFormResetStatsBtn" data-obj="<?php echo esc_attr($objId); ?>">
      <span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Reset Stats', 'ppsgen'); ?>
    </a>
  </div>
  <div class="ppsgenFormStatsWrapper">
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Total Views:', 'ppsgen'); ?> <span><?php echo esc_html($stats['form_view']); ?></span></div>
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-button"></span> <?php esc_html_e('Total Submits:', 'ppsgen'); ?> <span><?php echo esc_html($stats['submit_total']); ?></span></div>
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Error Submits:', 'ppsgen'); ?> <span><?php echo esc_html($stats['submit_error']); ?></span></div>
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Success Submits:', 'ppsgen'); ?> <span><?php echo esc_html($stats['submit_success']); ?></span></div>
  </div>
  <div class="ppsgenFormStatsWrapper">
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-feedback"></span> <?php esc_html_e('CTR (View to Submit):', 'ppsgen'); ?> <span><?php echo esc_html($ctr); ?>%</span></div>
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Success Rate:', 'ppsgen'); ?> <span><?php echo esc_html($successRate); ?>%</span></div>
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e('Error Rate:', 'ppsgen'); ?> <span><?php echo esc_html($errorRate); ?>%</span></div>
    <div class="ppsgenFormStatsRow"><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('View-to-Success Rate:', 'ppsgen'); ?> <span><?php echo esc_html($viewToSuccessRate); ?>%</span></div>
  </div>
  <div class="ppsgenStatsTrendPanel">
    <div class="ppsgenStatsTrendToolbar">
      <div class="ppsgenStatsTrendGroup">
        <strong><?php esc_html_e('Group:', 'ppsgen'); ?></strong>
        <?php foreach (['day' => __('Day', 'ppsgen'), 'week' => __('Week', 'ppsgen'), 'month' => __('Month', 'ppsgen')] as $groupKey => $groupLabel) { ?>
          <a class="ppsgenStatsTrendBtn<?php echo $trendGroup === $groupKey ? ' ppsgenStatsTrendBtnActive' : ''; ?>" href="<?php echo esc_url(add_query_arg(['form_stats_group' => $groupKey, 'form_stats_range' => $trendRange], $trendBaseUrl)); ?>"><?php echo esc_html($groupLabel); ?></a>
        <?php } ?>
      </div>
      <div class="ppsgenStatsTrendGroup">
        <strong><?php esc_html_e('Range:', 'ppsgen'); ?></strong>
        <?php foreach ([7, 30, 90, 365] as $rangeOption) { ?>
          <a class="ppsgenStatsTrendBtn<?php echo $trendRange === $rangeOption ? ' ppsgenStatsTrendBtnActive' : ''; ?>" href="<?php echo esc_url(add_query_arg(['form_stats_group' => $trendGroup, 'form_stats_range' => $rangeOption], $trendBaseUrl)); ?>"><?php echo esc_html($rangeOption); ?></a>
        <?php } ?>
      </div>
    </div>
    <div class="ppsgenStatsTrendLegend">
      <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorPrimary"></i><?php esc_html_e('Submits', 'ppsgen'); ?></span>
      <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorSecondary"></i><?php esc_html_e('Unique Contacts', 'ppsgen'); ?></span>
    </div>
    <div class="ppsgenStatsTrendChart">
      <?php if (!empty($trend) && $trendMax > 0) { ?>
        <?php foreach ($trend as $point) {
          $submitHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['submits'] ?? 0) / $trendMax) * 140)) : 4;
          $uniqueHeight = $trendMax > 0 ? max(4, (int) round(((int) ($point['unique_contacts'] ?? 0) / $trendMax) * 140)) : 4;
          ?>
          <div class="ppsgenStatsTrendCol">
            <div class="ppsgenStatsTrendBars">
              <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarPrimary" style="height: <?php echo esc_attr($submitHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Submits: %d', 'ppsgen'), (int) ($point['submits'] ?? 0))); ?>"></span>
              <span class="ppsgenStatsTrendBar ppsgenStatsTrendBarSecondary" style="height: <?php echo esc_attr($uniqueHeight); ?>px;" title="<?php echo esc_attr(sprintf(__('Unique Contacts: %d', 'ppsgen'), (int) ($point['unique_contacts'] ?? 0))); ?>"></span>
            </div>
            <div class="ppsgenStatsTrendLabel"><?php echo esc_html($point['label'] ?? ''); ?></div>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="ppsgenStatsTrendEmpty"><?php esc_html_e('Not enough submit data yet to build a trend chart.', 'ppsgen'); ?></div>
      <?php } ?>
    </div>
  </div>
<?php } ?>
<?php if (!empty($formSubmits) && is_array($formSubmits)) { ?>
  <div class="ppsgenFormSubmitsWrapper">
    <?php foreach ($formSubmits as $submit) { ?>
      <?php
      $contactId = !empty($submit['contact_id']) ? (int) $submit['contact_id'] : 0;
      $contactIsExist = !empty($contactId) ? $this->frame->getModule('contact')->getModel()->contactExist($contactId) : false;
      $contactIsVerified = !empty($contactIsExist) ? $this->frame->getModule('contact')->getModel()->contactIsverified($contactId) : false;
      $submitIsVerified = strpos($submit['fields_values'] ?? '', '"unverified_submit":"1"') === false ? true : false;
      ?>
      <div class="ppsgenFormSubmitsRow">
        <div class="ppsgenFormSubmitsRowTitle">
          <div class="ppsgenFormSubmitsRowTitleEl"><?php esc_html_e('Submit ID:', 'ppsgen'); ?> <span><?php echo esc_html($submit['id']); ?></span></div>
          <?php
          $userId = isset($submit['wp_user_id']) ? (int) $submit['wp_user_id'] : 0;
          $editUserUrl = $userId ? get_edit_user_link($userId) : '';
          ?>
          <div class="ppsgenFormSubmitsRowTitleEl">
              <?php esc_html_e('User ID:', 'ppsgen'); ?>
              <?php if ($editUserUrl) { ?>
                  <a target="_blank" href="<?php echo esc_url($editUserUrl); ?>">
                      <?php echo esc_html($userId); ?>
                  </a>
              <?php } else { ?>
                  <span><?php echo esc_html($userId); ?></span>
              <?php } ?>
          </div>
          <div class="ppsgenFormSubmitsRowTitleEl">
            <?php esc_html_e('Contact ID:', 'ppsgen'); ?> 
            <?php if (!empty($submit['contact_id']) && $contactIsExist) { ?>
              <a target="_blank" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=contact_edit&id=' . (int) $submit['contact_id'])); ?>"><?php echo esc_html($submit['contact_id']); ?></a>
            <?php } else { ?>
              <span><?php echo esc_html($submit['contact_id']); ?></span>
            <?php } ?>
          </div>
          <?php $submitGeo = $this->getFrame()->core('helper')->getGeoLiteLocationLabelByIp($submit['ip'] ?? ''); ?>
          <div class="ppsgenFormSubmitsRowTitleEl"><?php esc_html_e('IP:', 'ppsgen'); ?> <span><?php echo esc_html($submit['ip']); ?></span><?php if (!empty($submitGeo)) { ?> <span>(<?php echo esc_html($submitGeo); ?>)</span><?php } ?></div>
          <div class="ppsgenFormSubmitsRowTitleEl"><span><?php echo esc_html($submit['created_date']); ?></span></div>
          <div class="ppsgenFormSubmitsRowTitleBtns">
            
            <?php if (!$submitIsVerified && $contactIsExist) { ?>
              <a class="ppsgenTableListBtn ppsgenFormSubmitsRowTitleVerifySubmitContactBtn" data-obj="<?php echo esc_attr($submit['id']); ?>" title="<?php echo esc_attr__(
  'Verify Submit',
  'ppsgen',
); ?>"><span class="dashicons dashicons-feedback"></span><span class="dashicons dashicons-yes-alt"></span></a>
            <?php } ?>

            <?php if (!$submitIsVerified && !$contactIsExist) { ?>
              <a class="ppsgenTableListBtn ppsgenFormSubmitsRowTitleVerifySubmitBtn" data-obj="<?php echo esc_attr($submit['id']); ?>" title="<?php echo esc_attr__(
  'Verify Submit',
  'ppsgen',
); ?>"><span class="dashicons dashicons-feedback"></span><span class="dashicons dashicons-yes-alt"></span></a>
            <?php } ?>

            <?php if (!$contactIsVerified && $contactIsExist) { ?>
              <a class="ppsgenTableListBtn ppsgenFormSubmitsRowTitleVerifyContactBtn" data-obj="<?php echo esc_attr($submit['contact_id']); ?>" title="<?php echo esc_attr__(
  'Verify Contact',
  'ppsgen',
); ?>"><span class="dashicons dashicons-admin-users"></span><span class="dashicons dashicons-yes-alt"></span></a>
            <?php } ?>

            <?php if (!$submitIsVerified) { ?>
              <span title="<?php echo esc_attr__('Submit Is Not Verified', 'ppsgen'); ?>" class="ppsgenVerifiedIcon ppsgenFormSubmitsRowVerifySubmitIcon"><span class="dashicons dashicons-feedback"></span><span class="dashicons dashicons-dismiss"></span></span>
            <?php } else { ?>
              <span title="<?php echo esc_attr__('Submit Is Verified', 'ppsgen'); ?>" class="ppsgenVerifiedIcon ppsgenFormSubmitsRowVerifySubmitIcon"><span class="dashicons dashicons-feedback"></span><span class="dashicons dashicons-yes-alt"></span></span>
            <?php } ?>

            <?php if ($contactIsExist) { ?>
              <?php if (!$contactIsVerified) { ?>
                <span title="<?php echo esc_attr__('Contact Is Not Verified', 'ppsgen'); ?>" class="ppsgenVerifiedIcon ppsgenFormSubmitsRowVerifyContactIcon"><span class="dashicons dashicons-admin-users"></span><span class="dashicons dashicons-dismiss"></span></span>
              <?php } else { ?>
                <span title="<?php echo esc_attr__('Contact Is Verified', 'ppsgen'); ?>" class="ppsgenVerifiedIcon ppsgenFormSubmitsRowVerifyContactIcon"><span class="dashicons dashicons-admin-users"></span><span class="dashicons dashicons-yes-alt"></span></span>
              <?php } ?>
            <?php } ?>

            <a class="ppsgenTableListBtn ppsgenFormSubmitsRowTitleDeleteBtn" data-obj="<?php echo esc_attr($submit['id']); ?>" title="<?php echo esc_attr__('Delete Submit', 'ppsgen'); ?>"><span class="dashicons dashicons-trash"></span></a>
          </div>
        </div>
        <div class="ppsgenFormSubmitsRowData">
          <?php if (!empty($submit['fields_values'])) { ?>
            <?php $fields = $this->getModel()->_prepareValueFromStore($submit['fields_values']); ?>
            <?php if (!empty($fields) && is_array($fields)) { ?>
              <?php foreach ($fields as $key => $field) { ?>
                <?php if (empty($key)) {
                  continue;
                } ?>
                <div class="ppsgenFormSubmitsRowDataField">
                  <span class="ppsgenFormSubmitsRowDataFieldKey"><?php echo esc_html($key); ?>:</span>                  
                  <span class="ppsgenFormSubmitsRowDataFieldValue">
                    <?php if (!empty($field) && is_array($field)) { ?>
                      <?php if (isset($field[0]) && empty($field[0])) { ?>
                        <?php unset($field[0]); ?>
                      <?php } ?>
                      <?php $field = $this->getModel()->_prepareValueToStore($field, 'json'); ?>
                      <?php echo esc_html($field); ?>
                    <?php } else { ?>
                      <?php echo esc_html($field); ?>
                    <?php } ?>
                  </span>
                </div>
              <?php } ?>
            <?php } ?>
          <?php } else { ?>
            <?php esc_html_e('There are no available field data in this submission.', 'ppsgen'); ?>
          <?php } ?>    
        </div>
      </div>
    <?php } ?>
  </div>
<?php } else { ?>
  <div class="ppsgenFormSubmitsWrapperWarning">
    <?php esc_html_e('This form does not have any submits yet.', 'ppsgen'); ?>
  </div>
<?php } ?>
