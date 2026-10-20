<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$prefix = 'data[chain]';
$sprefix = 'data[chain][options]';
$component->setNamePrefix($sprefix);
$chain_id = !empty($options['obj_id']) ? $options['obj_id'] : 0;
$globalTrackingPrefix = function_exists('ppsgen_get_eca_tracking_query_prefix') ? ppsgen_get_eca_tracking_query_prefix() : '';
?>
<div class="ppsgenComponentsList">

  <div align="right">
    <div class="ppsgenChainOptionsBtnSave"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('1. Save Campaign Options', 'ppsgen'); ?></div>
    <div class="ppsgenChainOptionsBtnApplyChain"><div class="ppsgenChainOptionsBarApplyChain" style="display:none"></div><span class="dashicons dashicons-email-alt"></span><?php esc_html_e('2. Apply Campaign to Contacts', 'ppsgen'); ?></div>
    
  </div>

  <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Campaign is Enabled', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[active]', ['checked' => $component->value($options['active'] ?? null, '1'), 'class' => 'data-option-toggle-trigger']); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Campaign Name', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[name]', ['value' => $component->value($options['name'] ?? null), 'attrs' => ' ']); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Campaign Template', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'Which template should be used for emails in this campaign? You can create templates in Campaigns → Email templates.',
  'ppsgen',
); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[template_id]', ['options' => $this->getModel()->getTemplatesList(true), 'class' => '', 'selected' => $component->value($options['template_id'] ?? null, '')]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SMTP / IMAP Config', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Which mail configuration should be used to send emails in this campaign?', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[mail_config_id]', ['options' => $this->getFrame()->getModule('dashboard')->getModel()->getMailConfigOptions(true), 'class' => '', 'selected' => $component->value($settings['mail_config_id'] ?? null, '')]); ?>
    </div>
  </div>

  <h3><?php esc_html_e('Email Tracking', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Campaign Tracking Query Prefix', 'ppsgen'); ?>" data-notice-label-desc="<?php echo esc_attr(sprintf(
  __('Optional campaign-level override for the tracking link fingerprint. Leave empty to use the global prefix (%s). If set to abc123, this campaign sends open/click links with abc123_a and unsubscribe links with abc123_u. Change this only before sending a new campaign batch: after you change it, previously sent emails that used the old campaign prefix may no longer record opens/clicks or process unsubscribe links. The plugin stores only the current campaign prefix, not prefix history.', 'ppsgen'),
  $globalTrackingPrefix
)); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[eca_tracking_query_prefix]', [
        'value' => $component->value($settings['eca_tracking_query_prefix'] ?? null, ''),
        'attrs' => 'placeholder="' . esc_attr($globalTrackingPrefix) . '"',
      ]); ?>
    </div>
  </div>

  <h3><?php esc_html_e('Conditions & Segmentation', 'ppsgen'); ?></h3>

  <h4><?php esc_html_e('Entry Condition - (AND) operator', 'ppsgen'); ?></h4>

  <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Must be Verified', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'Only verified contacts are allowed to enter this automation campaign. A contact can be verified manually or via email verification from the subscription form.',
  'ppsgen',
); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[is_verified]', ['checked' => $component->value($settings['is_verified'] ?? null, '0'), 'class' => 'data-option-toggle-trigger']); ?>
    </div>
  </div>

  <?php $allContactTags = $this->frame->getModule('contact')->getSubmod('contact_tag')->getModel()->getAllContactTags(true); ?>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Has Tag(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must have (all) selected tags to run this Campaign.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Tag(s):', 'ppsgen'); ?> <?php $component->selectmultiple('[entry_tag_yes][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => !empty($settings['entry_tag_yes']) ? $settings['entry_tag_yes'] : []]); ?>
        </div>
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Condition:', 'ppsgen'); ?> <?php $component->select('[entry_tag_yes_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => $component->value($settings['entry_tag_yes_condition'] ?? null, 'or')]); ?>
        </div>
      </div>
    </div>

    <?php
    $savedLists = method_exists($this->frame->getModule('contact')->getModel(), 'getSavedContactLists') ? $this->frame->getModule('contact')->getModel()->getSavedContactLists() : [];
    $savedListOptions = ['' => __('Any contact', 'ppsgen')];
    foreach ($savedLists as $savedList) {
      $savedListOptions[(string) $savedList['id']] = (string) $savedList['name'];
    }
    $entryListId = (string) ($settings['entry_list_id'] ?? '');
    if ($entryListId !== '' && !isset($savedListOptions[$entryListId])) {
      $savedListOptions[$entryListId] = __('Deleted list: nobody enters', 'ppsgen');
    }
    ?>
    <div class="ppsgenComponentRow ppsgenEntryListRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Is on List', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Only contacts that match this saved list from Contacts › Lists enter. Checked whenever a contact changes and when you apply the campaign to contacts.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[entry_list_id]', ['options' => $savedListOptions, 'selected' => $entryListId]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow  ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Has Not Tag(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must not have (any of) the selected tags to run this Campaign.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Tag(s):', 'ppsgen'); ?> <?php $component->selectmultiple('[entry_tag_not][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => !empty($settings['entry_tag_not']) ? $settings['entry_tag_not'] : []]); ?>
        </div>
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Condition:', 'ppsgen'); ?> <?php $component->select('[entry_tag_not_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => $component->value($settings['entry_tag_not_condition'] ?? null, 'or')]); ?>
        </div>
      </div>
    </div>

    <?php $allContactsFields = $this->frame->getModule('contact')->getSubmod('contact_field')->getModel()->getAllContactFieldsSlugList(false, true); ?>


    <div class="ppsgenComponentRow  ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Field Conditions', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must have selected field(s) with conditions to run this Campaign.', 'ppsgen'); ?>"></div>
      
      <div class="ppsgenComponentOption">

        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Field Conditions Operator:', 'ppsgen'); ?> <?php $component->select('[entry_field_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => $component->value($settings['entry_field_condition'] ?? null, 'or')]); ?>
        </div>

        <div class="ppsgenComponentOption">
          <div class="ppsgenComponentOptionInline">
            <?php
            $count = 1;
            if (!empty($settings['entry_field']) && is_array($settings['entry_field'])) {
              $fieldKeys = array_filter(array_map('intval', array_keys($settings['entry_field'])));
              $count = !empty($fieldKeys) ? max($fieldKeys) : count($settings['entry_field']);
            }
            ?>
            <div class="ppsgenComponentOptionInline ppsgenChainOptionFieldsEntryEtalon" data-count="<?php echo esc_attr($count); ?>">
              <?php $component->select('[x][slug]', ['options' => $allContactsFields, 'selected' => '']); ?>
              <?php $component->select('[x][operator]', ['options' => $component->getChainFieldOperators(), 'selected' => '']); ?>
              <?php $component->text('[x][value]', ['value' => '', 'attrs' => '']); ?>
              <div class="ppsgenChainOptionFieldsEntryBtnDelete" style="display:none;"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Delete', 'ppsgen'); ?></div>
            </div>
            <div class="ppsgenChainOptionFieldsEntryBtnAdd"><span class="dashicons dashicons-plus-alt"></span><?php esc_html_e('Add', 'ppsgen'); ?></div>
          </div>
        </div>

        <div class="ppsgenComponentOption ppsgenChainOptionFieldsEntryContainer">
          <?php if (!empty($settings['entry_field'])) { ?>
            <?php foreach ($settings['entry_field'] as $key => $field) { ?>
              <div class="ppsgenComponentOptionInline ppsgenChainOptionFieldsEntryObj" data-count="<?php echo esc_attr($key); ?>">
                <?php
                $fieldName = '[entry_field][' . $key . '][slug]';
                $component->select($fieldName, ['options' => $allContactsFields, 'selected' => $component->value($field['slug'] ?? null, '')]);
                ?>
                <?php
                $fieldName = '[entry_field][' . $key . '][operator]';
                $component->select($fieldName, ['options' => $component->getChainFieldOperators(), 'selected' => $component->value($field['operator'] ?? null, '')]);
                ?>
                <?php
                $fieldName = '[entry_field][' . $key . '][value]';
                $component->text($fieldName, ['value' => $component->value($field['value'] ?? null, ''), 'attrs' => ' ']);
                ?>
                <div class="ppsgenChainOptionFieldsEntryBtnDelete"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Delete', 'ppsgen'); ?></div>
              </div>
            <?php } ?>
          <?php } ?>
        </div>

      </div>
    </div>

  <h4><?php esc_html_e('Exit Condition - (AND) operator', 'ppsgen'); ?></h4>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Has Tag(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must have (all) selected tags to stop this Campaign.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Tag(s):', 'ppsgen'); ?> <?php $component->selectmultiple('[exit_tag_yes][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => !empty($settings['exit_tag_yes']) ? $settings['exit_tag_yes'] : []]); ?>
        </div>
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Condition:', 'ppsgen'); ?> <?php $component->select('[exit_tag_yes_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => $component->value($settings['exit_tag_yes_condition'] ?? null, 'or')]); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Has Not Tag(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must not have (any of) the selected tags to stop this Campaign.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Tag(s):', 'ppsgen'); ?> <?php $component->selectmultiple('[exit_tag_not][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => !empty($settings['exit_tag_not']) ? $settings['exit_tag_not'] : []]); ?>
        </div>
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Condition:', 'ppsgen'); ?> <?php $component->select('[exit_tag_not_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => $component->value($settings['exit_tag_not_condition'] ?? null, 'or')]); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Field Conditions', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must have selected field(s) with conditions to stop this Campaign.', 'ppsgen'); ?>"></div>
      
      <div class="ppsgenComponentOption">

        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Field Conditions Operator:', 'ppsgen'); ?> <?php $component->select('[exit_field_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => $component->value($settings['exit_field_condition'] ?? null, 'or')]); ?>
        </div>

        <div class="ppsgenComponentOption">
          <div class="ppsgenComponentOptionInline">
            <?php
            $count = 1;
            if (!empty($settings['exit_field']) && is_array($settings['exit_field'])) {
              $fieldKeys = array_filter(array_map('intval', array_keys($settings['exit_field'])));
              $count = !empty($fieldKeys) ? max($fieldKeys) : count($settings['exit_field']);
            }
            ?>
            <div class="ppsgenComponentOptionInline ppsgenChainOptionFieldsExitEtalon" data-count="<?php echo esc_attr($count); ?>">
              <?php $component->select('[x][slug]', ['options' => $allContactsFields, 'selected' => '']); ?>
              <?php $component->select('[x][operator]', ['options' => $component->getChainFieldOperators(), 'selected' => '']); ?>
              <?php $component->text('[x][value]', ['value' => '', 'attrs' => '']); ?>
              <div class="ppsgenChainOptionFieldsExitBtnDelete" style="display:none;"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Delete', 'ppsgen'); ?></div>
            </div>
            <div class="ppsgenChainOptionFieldsExitBtnAdd"><span class="dashicons dashicons-plus-alt"></span><?php esc_html_e('Add', 'ppsgen'); ?></div>
          </div>
        </div>

        <div class="ppsgenComponentOption ppsgenChainOptionFieldsExitContainer">
          <?php if (!empty($settings['exit_field'])) { ?>
            <?php foreach ($settings['exit_field'] as $key => $field) { ?>
              <div class="ppsgenComponentOptionInline ppsgenChainOptionFieldsExitObj" data-count="<?php echo esc_attr($key); ?>">
                <?php
                $fieldName = '[exit_field][' . $key . '][slug]';
                $component->select($fieldName, ['options' => $allContactsFields, 'selected' => $component->value($field['slug'] ?? null, '')]);
                ?>
                <?php
                $fieldName = '[exit_field][' . $key . '][operator]';
                $component->select($fieldName, ['options' => $component->getChainFieldOperators(), 'selected' => $component->value($field['operator'] ?? null, '')]);
                ?>
                <?php
                $fieldName = '[exit_field][' . $key . '][value]';
                $component->text($fieldName, ['value' => $component->value($field['value'] ?? null, ''), 'attrs' => ' ']);
                ?>
                <div class="ppsgenChainOptionFieldsExitBtnDelete"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Delete', 'ppsgen'); ?></div>
              </div>
            <?php } ?>
          <?php } ?>
        </div>

      </div>
    </div>

    <h4><?php esc_html_e('Unsubscribe Actions', 'ppsgen'); ?></h4>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Add Tag(s) on Unsubscribe', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Tags to automatically add when a contact unsubscribes from this campaign, regardless of which email step triggered it.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[add_tag_unsubscribe][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => !empty($settings['add_tag_unsubscribe']) ? $settings['add_tag_unsubscribe'] : []]); ?>
      </div>
    </div>

    <h4><?php esc_html_e('Time Rules', 'ppsgen'); ?></h4>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Allowed Day(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The emails in this Campaign will be sent only at this day(s)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[time_days][]', [
          'class' => 'ppsgenselect',
          'options' => [
            'monday' => esc_html__('Monday', 'ppsgen'),
            'tuesday' => esc_html__('Tuesday', 'ppsgen'),
            'wednesday' => esc_html__('Wednesday', 'ppsgen'),
            'thursday' => esc_html__('Thursday', 'ppsgen'),
            'friday' => esc_html__('Friday', 'ppsgen'),
            'saturday' => esc_html__('Saturday', 'ppsgen'),
            'sunday' => esc_html__('Sunday', 'ppsgen'),
          ],
          'selected' => !empty($settings['time_days']) ? $settings['time_days'] : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]); ?>
      </div>
    </div>

    <?php $hours = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23']; ?>
    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Allowed Time', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The emails in this Campaign will be sent only between this time.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php esc_html_e('Hours - From:', 'ppsgen'); ?> <?php $component->select('[time_hours_start]', ['class' => '', 'options' => $hours, 'selected' => $component->value($settings['time_hours_start'] ?? null, '10')]); ?>
        <?php esc_html_e('To:', 'ppsgen'); ?> <?php $component->select('[time_hours_end]', ['class' => '', 'options' => $hours, 'selected' => $component->value($settings['time_hours_end'] ?? null, '19')]); ?>
      </div>
    </div>


  <?php if (!empty($options['obj_id'])) { ?>
  <h3><?php esc_html_e('Date Info', 'ppsgen'); ?></h3>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Created', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[created_at]', ['value' => $component->value($options['created_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Updated', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[updated_at]', ['value' => $component->value($options['updated_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <?php } ?>

  <div align="left"><div class="ppsgenChainOptionsBtnSave"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save Campaign Options', 'ppsgen'); ?></div></div>

</div>
