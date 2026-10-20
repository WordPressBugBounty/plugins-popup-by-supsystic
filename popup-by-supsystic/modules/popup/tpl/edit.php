<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$objId = $options['obj_id'];
$popupType = !empty($options['popup_type']) ? $options['popup_type'] : 'custom';
$activeTab = !empty($this->activeTab) ? $this->activeTab : 'template';
$prefix = 'data[popup]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <?php if ($objId) { ?>
    <span class="dashicons dashicons-testimonial"></span>
    <?php esc_html_e('Popups', 'ppsgen'); ?> /
    <span class="ppsgenModulePageTitleName"><?php echo esc_html(!empty($options['name']) ? $options['name'] : __('Untitled', 'ppsgen')); ?></span>
    <?php $partOfPlay = $this->getFrame()->getModule('dashboard') ? $this->getFrame()->getModule('dashboard')->getPlays()->getPlayByPart('popup', (int) $objId) : []; ?>
    <?php if (!empty($partOfPlay)) { ?><a class="ppsgenPartOfChip" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=' . (in_array($partOfPlay['status'] ?? '', ['live', 'paused'], true) ? 'play_summary' : 'play_review') . '&id=' . $partOfPlay['id'])); ?>"><?php echo esc_html(sprintf(__('Part of: %s', 'ppsgen'), $partOfPlay['name'])); ?></a><?php } ?>
    <span class="ppsgenModulePageTitleShortcode">Shortcode: <code>[supsystic-ces-popup id="<?php echo esc_html($objId); ?>"]</code> (hideoverlay="1", embed="1", forceshow="1") - <?php esc_html_e(' as additional params', 'ppsgen'); ?></span>
    <?php } else { ?>
    <span class="dashicons dashicons-testimonial"></span> <?php esc_html_e('New popup', 'ppsgen'); ?>
    <?php } ?>
  </h2>
  <div class="ppsgenEditorHeaderActions">
    <?php if ($objId) { ?>
    <label class="ppsgenEditorLive<?php echo !empty($component->value($options['active'] ?? null, '1')) ? ' ppsgenEditorLiveOn' : ''; ?>" data-live-target="data[popup][active]" title="<?php esc_attr_e('Switch on to show it on your site. Save to apply.', 'ppsgen'); ?>">
      <input type="checkbox" class="ppsgenEditorLiveInput"<?php echo !empty($component->value($options['active'] ?? null, '1')) ? ' checked' : ''; ?>>
      <span class="ppsgenEditorLiveTrack"></span>
      <span class="ppsgenEditorLiveText" data-on="<?php esc_attr_e('Live', 'ppsgen'); ?>" data-off="<?php esc_attr_e('Off', 'ppsgen'); ?>"><?php echo !empty($component->value($options['active'] ?? null, '1')) ? esc_html__('Live', 'ppsgen') : esc_html__('Off', 'ppsgen'); ?></span>
    </label>
    <?php } ?>
    <button class="ppsgenSaveBtn ppsgenLoadPreview"><span class="dashicons dashicons-visibility"></span><?php esc_html_e('Full preview', 'ppsgen'); ?></button>
    <button class="ppsgenSaveBtn ppsgenObjEditSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </div>
</div>
<form method="POST" id="ppsgenEditObjFormPopup">
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" name="mod" value="popup">
  <input type="hidden" id="action" name="action" value="editPopup">
  <?php if ($objId) { ?>
  <input type="hidden" name="obj_id" value="<?php ppsgen_esc_attr($objId); ?>">
  <?php } ?>
  <input type="hidden" name="obj_edit_active_tab" value="<?php echo esc_attr($activeTab); ?>">

  <div class="ppsgenObjEditWrapper">
    <div class="ppsgenObjEditTabsButtons">
      <?php foreach ($this->tabs as $tabKey => $tabData) {
        if ($tabKey === 'stats' && empty($objId)) {
          continue;
        } ?>
      <div class="ppsgenObjEditTabButton<?php echo $tabKey === $activeTab ? ' ppsgenObjEditTabButtonActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
        <?php echo esc_html($tabData['title']); ?>
      </div>
      <?php
      } ?>
    </div>
    <div class="ppsgenObjEditTabs">
      <?php foreach ($this->tabs as $tabKey => $tabData) {
        if ($tabKey === 'stats' && empty($objId)) {
          continue;
        } ?>
      <div class="ppsgenObjEditTab<?php echo $tabKey === $activeTab ? ' ppsgenObjEditTabActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
        <?php
        // Custom code tabs contain already-escaped textarea values; filtering the whole
        // textarea as HTML strips CSS/JS code before it can be saved again.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo in_array($tabKey, ['custom_css', 'custom_js'], true) ? $tabData['content'] : $this->getFrame()->core('helper')->wpKses($tabData['content']);
        ?>
      </div>
      <?php
      } ?>
    </div>
  </div>
</form>
<?php $component->setNamePrefix(''); ?>

<div id="ppsgenPreviewPopup">
  <div class="ppsgen-popup-inner">
    <div class="ppsgen-popup-header">
      <strong><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Preview', 'ppsgen'); ?></strong>
      <button id="ppsgenPreviewClose">✕</button>
    </div>
    <iframe id="ppsgenPreviewFrame"></iframe>
  </div>
</div>
