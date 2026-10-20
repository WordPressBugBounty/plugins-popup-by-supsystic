<?php
if (!defined('ABSPATH')) {
  exit();
}
$items = is_array($this->contactLogs ?? null) ? $this->contactLogs : [];
$objId = !empty($this->objId) ? (int) $this->objId : 0;
?>

<div class="ppsgenContactLogsPage">
  <?php if ($objId > 0) { ?>
    <div class="ppsgenContactLogsActions">
      <button
        type="button"
        class="ppsgenTableListBtn ppsgenContactLogsClearBtn"
        data-contact-id="<?php echo esc_attr($objId); ?>"
        data-confirm="<?php esc_attr_e('Clear fields and tags logs for this contact?', 'ppsgen'); ?>">
        <span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Clear fields & tags logs', 'ppsgen'); ?>
      </button>
    </div>
  <?php } ?>
  <?php if (empty($items)) { ?>
    <div class="ppsgenContactLogsEmpty ppsgenFormSubmitsWrapperWarning">
      <?php esc_html_e('No contact activity has been recorded yet.', 'ppsgen'); ?>
    </div>
  <?php } else { ?>
    <div class="ppsgenContactLogsTimeline">
      <div class="ppsgenContactLogsTimelineInner">
        <?php foreach ($items as $item) { ?>
          <?php
          $side = ($item['side'] ?? '') === 'user' ? 'user' : 'system';
          $title = (string) ($item['title'] ?? '');
          $text = (string) ($item['text'] ?? '');
          $badge = (string) ($item['badge'] ?? '');
          $time = (string) ($item['time'] ?? '');
          ?>
          <div class="ppsgenContactLogsRow ppsgenContactLogsRow<?php echo $side === 'user' ? 'User' : 'System'; ?>">
            <div class="ppsgenContactLogsBubble">
              <div class="ppsgenContactLogsBubbleTop">
                <?php if ($badge !== '') { ?>
                  <span class="ppsgenContactLogsBadge"><?php echo esc_html($badge); ?></span>
                <?php } ?>
                <?php if ($time !== '') { ?>
                  <span class="ppsgenContactLogsTime"><?php echo esc_html($time); ?></span>
                <?php } ?>
              </div>
              <?php if ($title !== '') { ?>
                <div class="ppsgenContactLogsTitle"><?php echo esc_html($title); ?></div>
              <?php } ?>
              <?php if ($text !== '') { ?>
                <div class="ppsgenContactLogsText"><?php echo esc_html($text); ?></div>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
</div>
