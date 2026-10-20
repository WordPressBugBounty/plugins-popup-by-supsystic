<?php
if (!defined('ABSPATH')) {
  exit();
}
$legacyActive = function_exists('ppsgen_is_legacy_active') && ppsgen_is_legacy_active();
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle"><?php esc_html_e('Classic PopUp', 'ppsgen'); ?></h2>
</div>
<div class="ppsgenSettingsCard">
  <p><?php esc_html_e('Classic PopUp is the original Popup by Supsystic interface. Your Classic popups are safe, and you can switch between Classic PopUp and the Customer Engagement System at any time.', 'ppsgen'); ?></p>
  <div class="ppsgenLegacySwitchBannerActions">
    <?php if ($legacyActive) { ?>
      <a class="ppsgenLegacySwitchBannerLink" href="<?php echo esc_url(admin_url('admin.php?page=popup-wp-supsystic')); ?>">
        <span class="dashicons dashicons-external"></span>
        <?php esc_html_e('Open Classic PopUp', 'ppsgen'); ?>
      </a>
    <?php } ?>
    <button
      type="button"
      class="ppsgenLegacySwitchBannerBtn ppsgenLegacyToggleBtn"
      data-next-state="<?php echo esc_attr($legacyActive ? '0' : '1'); ?>"
    >
      <span class="dashicons <?php echo esc_attr($legacyActive ? 'dashicons-migrate' : 'dashicons-admin-tools'); ?>"></span>
      <span class="ppsgenLegacyToggleBtnText"><?php echo esc_html($legacyActive ? __('Switch to Customer Engagement System', 'ppsgen') : __('Switch to Classic PopUp', 'ppsgen')); ?></span>
    </button>
  </div>
</div>
