<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = &$this->options;
$objId = $options['obj_id'];
$prefix = 'data[contact_tag]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <?php if ($objId) { ?>
    <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Edit Contact Tag', 'ppsgen'); ?>
    <?php } else { ?>
    <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Add New Contact Tag', 'ppsgen'); ?>
    <?php } ?>
  </h2>
  <button class="ppsgenSaveBtn ppsgenObjEditSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
</div>
<form id="ppsgenEditObjForm" method="POST">
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" name="mod" value="contact/contact_tag">
  <input type="hidden" id="action" name="action" value="editContactTag">
  <?php if ($objId) { ?>
  <input type="hidden" name="obj_id" value="<?php ppsgen_esc_attr($objId); ?>">
  <?php } ?>
  <div class="ppsgenObjEditWrapper">
    <div class="ppsgenObjEditTabsButtons">
      <div class="ppsgenObjEditTabButton ppsgenObjEditTabButtonActive" data-tab="general"><?php esc_html_e('General', 'ppsgen'); ?></div>
    </div>
    <div class="ppsgenObjEditTabs">
      <div class="ppsgenObjEditTab ppsgenObjEditTabActive" data-tab="general">
        <div class="ppsgenComponentsList">
          <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tag Slug', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->text('[slug]', ['value' => $component->value($options['slug'] ?? null), 'attrs' => 'required ']); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Order', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->number('[order_index]', ['value' => $component->value($options['order_index'] ?? 0), 'attrs' => 'min="-9999" max="9999" ']); ?>
            </div>
          </div>

          <?php if (!empty($options['obj_id'])) { ?>
          <h3><?php esc_html_e('Date Info', 'ppsgen'); ?></h3>
          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Created', 'ppsgen'); ?>"  ></div>
            <div class="ppsgenComponentOption">
              <?php $component->text('[created_at]', ['value' => $component->value($options['created_at'] ?? null), 'attrs' => 'readonly ']); ?>
            </div>
          </div>
          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Updated', 'ppsgen'); ?>"  ></div>
            <div class="ppsgenComponentOption">
              <?php $component->text('[updated_at]', ['value' => $component->value($options['updated_at'] ?? null), 'attrs' => 'readonly ']); ?>
            </div>
          </div>
          <?php } ?>

        </div>
      </div>
    </div>
      <button class="ppsgenSaveBtn ppsgenObjEditSaveBtn" style="margin-top:10px;"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </div>
</form>
<?php $this->getFrame()->core('component')->setNamePrefix('');
?>
