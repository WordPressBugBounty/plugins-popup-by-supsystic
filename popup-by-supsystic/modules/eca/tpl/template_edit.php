<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$objId = $options['obj_id'];
$prefix = 'data[template]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <?php if ($objId) { ?>
    <span class="dashicons dashicons-admin-appearance"></span>
    <?php esc_html_e('Email templates', 'ppsgen'); ?> /
    <span class="ppsgenModulePageTitleName"><?php echo esc_html(!empty($options['name']) ? $options['name'] : __('Untitled', 'ppsgen')); ?></span>
    <?php } else { ?>
    <span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e('Add New Template', 'ppsgen'); ?>
    <?php } ?>
  </h2>
  <div>
    <button class="ppsgenSaveBtn ppsgenLoadTemplatePreview"><span class="dashicons dashicons-visibility"></span><?php esc_html_e('Full preview', 'ppsgen'); ?></button>
    <button class="ppsgenSaveBtn ppsgenObjEditSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </div>
</div>
<form method="POST" id="ppsgenEditObjFormTemplate">
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" name="mod" value="eca">
  <input type="hidden" id="action" name="action" value="editTemplate">
  <?php if ($objId) { ?>
  <input type="hidden" name="obj_id" value="<?php ppsgen_esc_attr($objId); ?>">
  <?php } ?>

  <div class="ppsgenObjEditWrapper">
    <div class="ppsgenObjEditTabsButtons">
      <?php foreach ($this->tabs as $tabKey => $tabData) {
        if ($tabKey === 'stats' && empty($objId)) {
          continue;
        } ?>
      <div class="ppsgenObjEditTabButton<?php echo $tabKey === 'template' ? ' ppsgenObjEditTabButtonActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
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
      <div class="ppsgenObjEditTab<?php echo $tabKey === 'template' ? ' ppsgenObjEditTabActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo $this->getFrame()->core('helper')->wpKses($tabData['content']); ?>
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