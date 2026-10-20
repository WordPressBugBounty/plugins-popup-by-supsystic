<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$contactOptions = $this->contactOptions;
$contactTagOptions = is_array($this->contactTagOptions ?? null) ? $this->contactTagOptions : [];
$prefix = 'data[contact]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-admin-settings"></span>
    <?php esc_html_e('Contact defaults', 'ppsgen'); ?>
  </h2>
  <button class="ppsgenSaveBtn ppsgenContactOptionsSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
</div>
<div class="ppsgenOptionsWrapper">
  <form class="ppsgenOptionsForm ppsgenContactOptionsForm" id="ppsgenContactOptionsForm">
    <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
    <input type="hidden" name="mod" value="contact">
    <input type="hidden" id="action" name="action" value="editcontactOptions">
    <div class="ppsgenComponentsList">
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Delete related Form Submits when deleting a Contact', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->checkboxToggle('[delete_related_form_submits]', ['checked' => $component->value($contactOptions['delete_related_form_submits'] ?? null, '1'), 'class' => '']); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Suspicious Email Auto-Check Tags', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('When Supsystic detects multiple open/click events for the same contact and campaign step within about one second, these existing contact tags will be added.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->selectmultiple('[eca_autocheck_tag_ids][]', [
            'class' => 'ppsgenselect',
            'options' => $contactTagOptions,
            'selected' => !empty($contactOptions['eca_autocheck_tag_ids']) && is_array($contactOptions['eca_autocheck_tag_ids']) ? $contactOptions['eca_autocheck_tag_ids'] : [],
          ]); ?>
        </div>
      </div>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Additional Suspicious Auto-Check Tag Slugs', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Optional comma-separated tag slugs to auto-create and assign when suspicious auto-check activity is detected. Default: autocheck_detected.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[eca_autocheck_tag_slugs]', ['value' => $component->value($contactOptions['eca_autocheck_tag_slugs'] ?? null, 'autocheck_detected'), 'attrs' => '']); ?>
        </div>
      </div>
      <?php if (!empty($this->isEcaActive)) { ?>
        <div class="ppsgenComponentRow">
          <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Manual Suspicious Activity Scan', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Run a one-time scan across all email campaign open and click events to detect suspicious auto-check activity and apply the configured suspicious tags to matching contacts.', 'ppsgen'); ?>"></div>
          <div class="ppsgenComponentOption">
            <button type="button" class="ppsgenSaveBtn ppsgenContactManualSuspiciousScanBtn"><span class="dashicons dashicons-search"></span><?php esc_html_e('Run Manual Suspicious Scan', 'ppsgen'); ?></button>
          </div>
        </div>
      <?php } ?>
    </div>
    <button class="ppsgenSaveBtn ppsgenContactOptionsSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </form>
</div>
