<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$dashboardOptions = &$this->dashboardOptions;
$prefix = 'data[dashboard]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-admin-settings"></span>
    <?php esc_html_e('General defaults', 'ppsgen'); ?>
  </h2>
</div>
<?php if (function_exists('ppsgen_is_legacy_user') && ppsgen_is_legacy_user()) { ?>
  <?php $legacyActive = function_exists('ppsgen_is_legacy_active') && ppsgen_is_legacy_active(); ?>
  <div class="ppsgenLegacySwitchBanner ppsgenLegacySwitchBannerPermanent">
    <div class="ppsgenLegacySwitchBannerText">
      <span class="ppsgenLegacySwitchBannerTitle"><?php echo esc_html($legacyActive ? __('Classic PopUp is active', 'ppsgen') : __('New version is active', 'ppsgen')); ?></span>
      <span class="ppsgenLegacySwitchBannerDesc"><?php echo esc_html($legacyActive ? __('Switch to the new Customer Engagement System – Popups, Forms, CRM & Automation admin panel when you are ready.', 'ppsgen') : __('Classic PopUp - admin panel is disabled, but you can turn it back on anytime.', 'ppsgen')); ?></span>
    </div>
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
        <span class="ppsgenLegacyToggleBtnText"><?php echo esc_html($legacyActive ? __('Switch to Customer Engagement System – Popups, Forms, CRM & Automation', 'ppsgen') : __('Enable Classic PopUp', 'ppsgen')); ?></span>
      </button>
    </div>
  </div>
<?php } ?>
<div class="ppsgenOptionsWrapper">
  <form class="ppsgenOptionsForm ppsgenDashboardOptionsForm" id="ppsgenDashboardOptionsForm">
    <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
    <input type="hidden" name="mod" value="dashboard">
    <input type="hidden" id="action" name="action" value="editdashboardOptions">
    <div class="ppsgenComponentsList"></div>
  </form>
</div>
<?php $component->setNamePrefix(''); ?>
