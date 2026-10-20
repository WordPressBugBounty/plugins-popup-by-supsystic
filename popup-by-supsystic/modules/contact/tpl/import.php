<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$prefix = 'data[contact_migration ]';
$component->setNamePrefix($prefix);
?>

<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-database-import"></span> <?php esc_html_e('Import Contacts', 'ppsgen'); ?>
  </h2>
</div>

<div class="ppsgenObjEditWrapper">
  <div class="ppsgenObjEditTabsButtons">
    <?php foreach ($this->tabs as $tabKey => $tabData) { ?>
    <div class="ppsgenObjEditTabButton<?php echo $tabKey === 'generate_contact' ? ' ppsgenObjEditTabButtonActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
      <?php echo esc_html($tabData['title']); ?>
    </div>
    <?php } ?>
  </div>
  <div class="ppsgenObjEditTabs">
    <?php foreach ($this->tabs as $tabKey => $tabData) { ?>
    <div class="ppsgenObjEditTab<?php echo $tabKey === 'generate_contact' ? ' ppsgenObjEditTabActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
      <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

      echo $this->getFrame()->core('helper')->wpKses($tabData['content']); ?>
    </div>
    <?php } ?>
  </div>
</div>

<?php $component->setNamePrefix('');
?>
