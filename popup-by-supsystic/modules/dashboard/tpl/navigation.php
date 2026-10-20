<?php
if (!defined('ABSPATH')) {
  exit();
}
$sections = is_array($this->sections ?? null) ? $this->sections : [];
$currentSection = (string) ($this->currentSection ?? '');
$currentItem = (string) ($this->currentSectionItem ?? '');
$emailStatus = is_array($this->emailStatus ?? null) ? $this->emailStatus : [];
// Outcomes | Workspace | System, as in the CES UX architecture
$groups = [
  ['home', 'plays'],
  ['popups', 'forms', 'contacts', 'campaigns', 'reports'],
];
$current = $sections[$currentSection] ?? [];
$subItems = !empty($current['items']) && ($current['layout'] ?? '') !== 'side' ? $current['items'] : [];
?>
<div class="ppsgenDashNavigationWrapper ppsgenTopBarWrapper">
  <nav class="ppsgenTopBar" aria-label="<?php esc_attr_e('Customer Engagement System', 'ppsgen'); ?>">
    <a class="ppsgenTopBarLogo" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen')); ?>">
      <img class="ppsgenTopBarLogoMark" src="<?php echo esc_url(PPSGEN_MODULES_URL . 'dashboard/assets/img/header-logo.webp'); ?>" width="28" height="28" alt="">
      <span class="ppsgenTopBarLogoText">
        <span class="ppsgenTopBarLogoName"><?php esc_html_e('Customer Engagement System', 'ppsgen'); ?></span>
        <span class="ppsgenTopBarLogoBy"><?php esc_html_e('by Supsystic', 'ppsgen'); ?></span>
      </span>
    </a>
    <?php foreach ($groups as $groupIndex => $group) { ?>
      <?php if ($groupIndex > 0) { ?><span class="ppsgenTopBarSep"></span><?php } ?>
      <?php foreach ($group as $key) { ?>
        <?php if (empty($sections[$key])) {
          continue;
        } ?>
        <a class="ppsgenTopBarLink<?php echo $key === $currentSection ? ' ppsgenTopBarLinkOn' : ''; ?>" href="<?php echo esc_url($sections[$key]['url']); ?>"><?php echo esc_html($sections[$key]['title']); ?></a>
      <?php } ?>
    <?php } ?>
    <span class="ppsgenTopBarGrow"></span>
    <?php if (!empty($emailStatus['label'])) { ?>
      <a class="ppsgenTopBarPill ppsgenTopBarPill-<?php echo esc_attr($emailStatus['state']); ?>" href="<?php echo esc_url($emailStatus['url']); ?>" title="<?php echo esc_attr($emailStatus['hint']); ?>">
        <span class="ppsgenTopBarPillDot"></span><?php echo esc_html($emailStatus['label']); ?>
      </a>
    <?php } ?>
    <span class="ppsgenTopBarSep"></span>
    <?php if (!empty($sections['settings'])) { ?>
      <a class="ppsgenTopBarLink<?php echo $currentSection === 'settings' ? ' ppsgenTopBarLinkOn' : ''; ?>" href="<?php echo esc_url($sections['settings']['url']); ?>"><?php echo esc_html($sections['settings']['title']); ?></a>
    <?php } ?>
    <a class="ppsgenTopBarLink" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=dashboard_overview')); ?>"><?php esc_html_e('Help', 'ppsgen'); ?></a>
    <button
      type="button"
      class="ppsgenThemeToggleBtn"
      data-theme-label-dark="<?php echo esc_attr__('Dark Theme', 'ppsgen'); ?>"
      data-theme-label-light="<?php echo esc_attr__('Light Theme', 'ppsgen'); ?>"
      title="<?php echo esc_attr__('Dark Theme', 'ppsgen'); ?>"
    >
      <span class="dashicons dashicons-lightbulb"></span>
      <span class="ppsgenThemeToggleBtnText"><?php esc_html_e('Dark Theme', 'ppsgen'); ?></span>
    </button>
  </nav>
  <?php if (!empty($subItems) && count($subItems) > 1) { ?>
    <nav class="ppsgenSubTabs" aria-label="<?php echo esc_attr($current['title']); ?>">
      <?php foreach ($subItems as $item) { ?>
        <?php if (!empty($item['group'])) {
          continue;
        } ?>
        <a class="ppsgenSubTab<?php echo $item['tab'] === $currentItem ? ' ppsgenSubTabOn' : ''; ?>" href="<?php echo esc_url($item['url']); ?>">
          <?php echo esc_html($item['title']); ?>
          <?php if (!empty($item['pro'])) { ?><span class="ppsgenProChip">PRO</span><?php } ?>
        </a>
      <?php } ?>
    </nav>
  <?php } ?>
</div>
