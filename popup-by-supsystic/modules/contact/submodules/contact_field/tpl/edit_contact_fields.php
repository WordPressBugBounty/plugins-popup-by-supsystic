<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$prefix = 'data[contact][contact_fields]';
$contactFields = $this->contactFields;
$contactFieldTypes = $this->contactFieldTypes;
$contactFieldLogsGrouped = is_array($this->contactFieldLogsGrouped ?? null) ? $this->contactFieldLogsGrouped : [];
$component->setNamePrefix($prefix);
?>

<h3><?php esc_html_e('Contact Fields', 'ppsgen'); ?></h3>
<?php foreach ($contactFields as $cf) { ?>
<?php if (!array_key_exists($cf['field_type'], $contactFieldTypes)) {
  continue;
} ?>
<div class="ppsgenComponentRow">
  <div class="ppsgenComponentLabel">
    <?php echo esc_html($cf['label']); ?> (<?php echo esc_html($cf['field_type']); ?>)
  </div>
  <div class="ppsgenComponentOption">
    <?php
    $cfValue = $this->getModel()->getContactFieldsValuesByContactId($options['obj_id'], $cf['id']) ?? null;
    $cfValue = $this->getModel()->_prepareValueFromStore($cfValue, 'json');
    if (empty($cfValue)) {
      $cfValue = null;
    } // $cfValue = $component->value($cfValue ?? null);
    $fieldName = $prefix . '[' . $cf['slug'] . ']';
    $fieldOptions = !empty($cf['options']) ? $this->getModel()->_prepareValueFromStore($cf['options']) : [];
    $fieldSettings = !empty($cf['settings']) ? $this->getModel()->_prepareValueFromStore($cf['settings']) : [];
    $fieldSettings['label'] = $cf['label'];
    $fieldAttrs = ['value' => $cfValue, 'name' => $fieldName];
    $fieldHistory = !empty($contactFieldLogsGrouped[$cf['id']]) ? $contactFieldLogsGrouped[$cf['id']] : [];
    ?>
    <div class="ppsgenContactFieldEditWrap">
      <?php $this->frame->getCfb()->build($cf['field_type'], $fieldSettings, $fieldAttrs, $fieldOptions); ?>
      <?php if (!empty($fieldHistory)) { ?>
        <button type="button" class="ppsgenContactFieldHistoryBtn" data-field-id="<?php echo esc_attr((int) $cf['id']); ?>">
          <span class="dashicons dashicons-backup"></span> <?php esc_html_e('History', 'ppsgen'); ?>
        </button>
        <div class="ppsgenContactFieldHistoryPanel" data-field-id="<?php echo esc_attr((int) $cf['id']); ?>" style="display:none;">
          <div class="ppsgenContactFieldHistoryList">
            <?php foreach ($fieldHistory as $historyRow) { ?>
              <div class="ppsgenContactFieldHistoryItem">
                <div class="ppsgenContactFieldHistoryTop">
                  <span class="ppsgenContactFieldHistoryBadge"><?php echo esc_html($historyRow['initiator_label'] ?? ''); ?></span>
                  <span class="ppsgenContactFieldHistoryTime"><?php echo esc_html($historyRow['time'] ?? ''); ?></span>
                </div>
                <div class="ppsgenContactFieldHistoryText">
                  <strong><?php esc_html_e('Was:', 'ppsgen'); ?></strong> <?php echo esc_html($historyRow['before'] ?? ''); ?>
                </div>
                <div class="ppsgenContactFieldHistoryText">
                  <strong><?php esc_html_e('Became:', 'ppsgen'); ?></strong> <?php echo esc_html($historyRow['after'] ?? ''); ?>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
</div>
<?php } ?>
