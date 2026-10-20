<?php
if (!defined('ABSPATH')) {
  exit();
}
$sections = is_array($this->sections ?? null) ? $this->sections : [];
$currentSection = (string) ($this->currentSection ?? '');
$currentItem = (string) ($this->currentSectionItem ?? '');
$sideSection = !empty($sections[$currentSection]) && ($sections[$currentSection]['layout'] ?? '') === 'side' ? $sections[$currentSection] : [];
?>
<div class="ppsgenDashLayoutWrapper ppsgenDashLayoutTopBar">
  <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  echo $this->getFrame()->core('helper')->wpKses($this->navigationHtml); ?>
  <?php // Classic PopUp stays one click away from Home; the switch itself lives in Settings > Classic PopUp ?>
  <?php if (!empty($this->isHome) && function_exists('ppsgen_is_legacy_user') && ppsgen_is_legacy_user()) { ?>
    <?php $legacyActive = function_exists('ppsgen_is_legacy_active') && ppsgen_is_legacy_active(); ?>
    <?php $legacySwitchDismissed = !$legacyActive && function_exists('ppsgen_is_legacy_switch_banner_dismissed') && ppsgen_is_legacy_switch_banner_dismissed(); ?>
    <?php if (!$legacySwitchDismissed) { ?>
    <div class="ppsgenLegacySwitchBanner ppsgenLegacySwitchBannerPermanent">
      <?php if (!$legacyActive) { ?>
        <button type="button" class="ppsgenLegacySwitchBannerClose" aria-label="<?php esc_attr_e('Hide Classic PopUp banner', 'ppsgen'); ?>">
          <span class="dashicons dashicons-no-alt"></span>
        </button>
      <?php } ?>
      <div class="ppsgenLegacySwitchBannerText">
        <span class="ppsgenLegacySwitchBannerTitle"><?php echo esc_html($legacyActive ? __('Classic PopUp is active', 'ppsgen') : __('Prefer Classic PopUp?', 'ppsgen')); ?></span>
        <span class="ppsgenLegacySwitchBannerDesc"><?php echo esc_html($legacyActive ? __('Switch to the Customer Engagement System when you are ready.', 'ppsgen') : __('Your Classic PopUp workspace is still available, and your popups there are safe.', 'ppsgen')); ?></span>
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
          <span class="ppsgenLegacyToggleBtnText"><?php echo esc_html($legacyActive ? __('Switch to Customer Engagement System', 'ppsgen') : __('Switch to Classic PopUp', 'ppsgen')); ?></span>
        </button>
      </div>
    </div>
    <?php } ?>
  <?php } ?>
  <?php if (!empty($sideSection)) { ?>
    <div class="ppsgenSettingsLayout">
      <nav class="ppsgenSettingsNav" aria-label="<?php echo esc_attr($sideSection['title']); ?>">
        <?php foreach ($sideSection['items'] as $item) { ?>
          <?php if (!empty($item['group'])) { ?>
            <div class="ppsgenSettingsNavGroup"><?php echo esc_html($item['group']); ?></div>
          <?php } elseif (!empty($item['button'])) { ?>
            <a class="ppsgenSettingsNavButton<?php echo $item['tab'] === $currentItem ? ' ppsgenSettingsNavButtonOn' : ''; ?>" href="<?php echo esc_url($item['url']); ?>"><span class="dashicons <?php echo esc_attr($item['button']); ?>"></span><?php echo esc_html($item['title']); ?></a>
          <?php } else { ?>
            <a class="ppsgenSettingsNavItem<?php echo $item['tab'] === $currentItem ? ' ppsgenSettingsNavItemOn' : ''; ?>" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
          <?php } ?>
        <?php } ?>
      </nav>
      <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $this->getFrame()->core('helper')->wpKses($this->contentHtml); ?>
    </div>
  <?php } else { ?>
    <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->getFrame()->core('helper')->wpKses($this->contentHtml); ?>
  <?php } ?>
</div>
