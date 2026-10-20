<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : [];
$objId = $options['obj_id'];
$prefix = 'data[contact_field]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <?php if ($objId) { ?>
      <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Edit Contact Field', 'ppsgen'); ?> - 
      <span class="ppsgenModulePageTitleId">ID: <?php echo esc_html($objId); ?></span> 
      <?php if (!empty($options['slug'])) { ?>- <span class="ppsgenModulePageTitleName"><code><?php echo esc_html($options['slug']); ?></code></span><?php } ?>
    <?php } else { ?>
    <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Add New Contact Field', 'ppsgen'); ?>
    <?php } ?>
  </h2>
  <button class="ppsgenSaveBtn ppsgenObjEditSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
</div>
<form id="ppsgenEditObjForm" method="POST">
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" name="mod" value="contact/contact_field">
  <input type="hidden" id="action" name="action" value="editContactField">
  <?php if ($objId) { ?>
  <input type="hidden" name="obj_id" value="<?php ppsgen_esc_attr($objId); ?>">
  <?php } ?>
  <div class="ppsgenObjEditWrapper">
    <div class="ppsgenObjEditTabsButtons">
      <div class="ppsgenObjEditTabButton ppsgenObjEditTabButtonActive" data-tab="general"><?php esc_html_e('General', 'ppsgen'); ?></div>
      <div class="ppsgenObjEditTabButton" data-tab="field_settings"><?php esc_html_e('Field Settings', 'ppsgen'); ?></div>
    </div>
    <div class="ppsgenObjEditTabs">
      <div class="ppsgenObjEditTab ppsgenObjEditTabActive" data-tab="general">
        <div class="ppsgenComponentsList">
          <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Enabled', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'If disabled, the field is hidden on the frontend but kept in the system and admin interface.',
  'ppsgen',
); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('[is_enabled]', ['checked' => $component->value($options['is_enabled'] ?? null, '1'), 'class' => '']); ?>
            </div>
          </div>

          <?php if (empty($options['is_default'])) { ?>
            <div class="ppsgenComponentRow">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Slug', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Must be unique for example: first_name. Only (a-z,0-9,_) characters.', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('[slug]', ['value' => $component->value($options['slug'] ?? null), 'attrs' => 'pattern="^[a-z0-9_]{1,255}$" required ']); ?>
              </div>
            </div>
          <?php } else { ?>
            <?php $component->hidden('[slug]', ['value' => $component->value($options['slug'] ?? null), 'attrs' => 'pattern="^[a-z0-9_]{1,255}$" required ']); ?>
          <?php } ?>

          <div class="ppsgenComponentRow ppsgenComponentRowDashed">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Label', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('For example: First Name', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption" title="<?php esc_attr_e('This field is available for editing in the form.', 'ppsgen'); ?>">
              <?php $component->text('[label]', ['value' => $component->value($options['label'] ?? null), 'attrs' => 'required ']); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Sort Order', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('For admin area purpose (from -9999 to 9999)', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->number('[order_index]', ['value' => $component->value($options['order_index'] ?? 0), 'attrs' => 'min="-9999" max="9999"']); ?>
            </div>
          </div>

          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Only for Form', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'Enable this option if the field should be used exclusively within forms (e.g., for collecting temporary or auxiliary data). Submitted values will be saved with the form submission but will not be stored in the contact object itself. This is useful when you need additional inputs in a form that aren\'t relevant to the contact\'s profile — for example, survey answers, extra comments, or logic-specific data that shouldn\'t clutter the contact record. ',
  'ppsgen',
); ?>"></div>
            <div class="ppsgenComponentOption">
              <?php $component->checkboxToggle('[only_form]', ['checked' => $component->value($options['only_form'] ?? null), 'class' => '']); ?>
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
      <div class="ppsgenObjEditTab" data-tab="field_settings">
        <div class="ppsgenComponentsList">
          <h3><?php esc_html_e('Field Settings', 'ppsgen'); ?></h3>
          <?php if (empty($options['is_default'])) { ?>
            <div class="ppsgenComponentRow">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Field Type', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('[field_type]', [
                  'options' => $this->contactFieldTypes,
                  'class' => 'data-option-toggle-trigger',
                  'selected' => $component->value($options['field_type'] ?? null),
                ]); ?>
              </div>
            </div>
            <div class="ppsgenContactFieldProBannerWrapper" style="display:none; margin-top: 14px;">
              <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              echo $this->getFrame()->core('helper')->wpKses($this->contactFieldProBannerHtml ?? ''); ?>
            </div>
          <?php } else { ?>
            <input type="hidden" name="<?php ppsgen_esc_attr($prefix); ?>[field_type]" class="data-option-toggle-trigger" value="<?php ppsgen_esc_attr($component->value($options['field_type'])); ?>">
            <?php if (!empty($options['field_type']) && strpos((string) $options['field_type'], 'pro_') === 0) { ?>
              <div class="ppsgenContactFieldProBannerWrapper" style="margin-top: 14px;">
                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $this->getFrame()->core('helper')->wpKses($this->contactFieldProBannerHtml ?? ''); ?>
              </div>
            <?php } ?>
          <?php } ?>

          <?php $html = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('contact.contact_field.edit_field_settings', $options); ?>
          <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          echo $this->getFrame()->core('helper')->wpKses($html); ?>

        </div>
      </div>
    </div>
    <button class="ppsgenSaveBtn ppsgenObjEditSaveBtn" style="margin-top:10px;"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </div>
</form>
<?php $component->setNamePrefix('');
?>
