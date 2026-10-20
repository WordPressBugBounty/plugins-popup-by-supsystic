<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$tagOptions = is_array($this->bulkAssignTagOptions ?? null) ? $this->bulkAssignTagOptions : [];
$modalTitle = !empty($this->bulkAssignTagsModalTitle) ? (string) $this->bulkAssignTagsModalTitle : __('Add Tag(s) to Selected Contacts', 'ppsgen');
$modalDescription = !empty($this->bulkAssignTagsModalDescription) ? (string) $this->bulkAssignTagsModalDescription : __('Choose one or more tags to assign to the selected contacts. Existing contact tags will be kept and not duplicated.', 'ppsgen');
$modalContext = !empty($this->bulkAssignTagsModalContext) ? (string) $this->bulkAssignTagsModalContext : 'contact_list';
$modalMode = !empty($this->bulkAssignTagsModalMode) ? (string) $this->bulkAssignTagsModalMode : 'add';
$submitLabel = !empty($this->bulkAssignTagsModalSubmitLabel) ? (string) $this->bulkAssignTagsModalSubmitLabel : __('Add Tag(s)', 'ppsgen');
$submitIcon = !empty($this->bulkAssignTagsModalSubmitIcon) ? (string) $this->bulkAssignTagsModalSubmitIcon : 'dashicons-tag';
?>
<div class="ppsgenBulkAssignTagsModal" data-context="<?php echo esc_attr($modalContext); ?>" data-mode="<?php echo esc_attr($modalMode); ?>" aria-hidden="true">
  <div class="ppsgenBulkAssignTagsModalBackdrop"></div>
  <div class="ppsgenBulkAssignTagsModalDialog" role="dialog" aria-modal="true" aria-labelledby="ppsgenBulkAssignTagsModalTitle-<?php echo esc_attr($modalContext); ?>">
    <div class="ppsgenBulkAssignTagsModalTop">
      <div>
        <h3 class="ppsgenBulkAssignTagsModalTitle" id="ppsgenBulkAssignTagsModalTitle-<?php echo esc_attr($modalContext); ?>"><?php echo esc_html($modalTitle); ?></h3>
        <div class="ppsgenBulkAssignTagsModalDesc"><?php echo esc_html($modalDescription); ?></div>
      </div>
      <a href="#" class="ppsgenBulkAssignTagsModalClose" aria-label="<?php esc_attr_e('Close', 'ppsgen'); ?>">
        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
      </a>
    </div>

    <div class="ppsgenBulkAssignTagsModalBody">
      <div class="ppsgenBulkAssignTagsModalMeta">
        <span class="ppsgenBulkAssignTagsModalMetaLabel"><?php esc_html_e('Selected Contacts', 'ppsgen'); ?></span>
        <strong class="ppsgenBulkAssignTagsSelectedCount">0</strong>
      </div>

      <?php if (!empty($tagOptions)) { ?>
        <div class="ppsgenBulkAssignTagsField">
          <label class="ppsgenBulkAssignTagsFieldLabel"><?php esc_html_e('Contact Tags', 'ppsgen'); ?></label>
          <?php $component->selectmultiple('ppsgen_bulk_assign_tags[]', [
            'class' => 'ppsgenselect ppsgenBulkAssignTagsSelect',
            'options' => $tagOptions,
            'selected' => [],
          ]); ?>
        </div>
      <?php } else { ?>
        <div class="ppsgenBulkAssignTagsEmpty"><?php esc_html_e('No contact tags found yet. Create at least one tag first.', 'ppsgen'); ?></div>
      <?php } ?>
    </div>

    <div class="ppsgenBulkAssignTagsModalActions">
      <a href="#" class="ppsgenTableListBtn ppsgenBulkAssignTagsSubmit<?php echo empty($tagOptions) ? ' is-disabled' : ''; ?><?php echo $modalMode === 'delete' ? ' is-delete-mode' : ''; ?>"<?php echo empty($tagOptions) ? ' aria-disabled="true"' : ''; ?>>
        <span class="dashicons <?php echo esc_attr($submitIcon); ?>"></span> <span class="ppsgenBulkAssignTagsSubmitLabel"><?php echo esc_html($submitLabel); ?></span>
      </a>
      <a href="#" class="ppsgenTableListBtn ppsgenBulkAssignTagsCancel"><?php esc_html_e('Cancel', 'ppsgen'); ?></a>
    </div>
  </div>
</div>
