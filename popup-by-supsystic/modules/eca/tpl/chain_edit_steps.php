<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$steps = !empty($options['steps']) ? $options['steps'] : [];
if (!empty($steps)) {
  usort($steps, function ($a, $b) {
    return ($a['step_order'] ?? 0) <=> ($b['step_order'] ?? 0);
  });
}
$prefix = 'data[chain]';
$component->setNamePrefix('');
?>

<div class="ppsgenChainStepsWrapper">
<div class="ppsgenChainStepsContainer ppsgenStepsContainerExtFeature" data-ext-label="<?php esc_attr_e('FREE PLAN: up to 4 email steps per automation campaign', 'ppsgen'); ?>">
    <?php foreach ($steps as $step) { ?>
    <div class="ppsgenChainStep" data-step-active="<?php echo esc_attr($step['active']); ?>" data-step-order="<?php echo esc_attr($step['step_order']); ?>" data-step-id="<?php echo esc_attr($step['id']); ?>">
      <span><?php echo esc_html($step['name']); ?></span>
    </div>
    <?php } ?>
  </div>
  <div class="ppsgenChainStepsBtns">
    <div class="ppsgenChainStepsBtn ppsgenChainStepsBtnDelete"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected', 'ppsgen'); ?></div>
    <div class="ppsgenChainStepsBtn ppsgenChainStepsBtnAdd"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add New Step', 'ppsgen'); ?></div>
  </div>
</div>

<div class="ppsgenChainStepWrapper" style="display:none;">

  <?php
  $stepTabs = [
    'stepFields' => [
      'title' => __('Template & Content', 'ppsgen'),
      'content' => '',
    ],
    'stepOptions' => [
      'title' => __('Options', 'ppsgen'),
      'content' => '',
    ],
    'stepStats' => [
      'title' => __('Stats', 'ppsgen'),
      'content' => '<div class="ppsgenChainStepStatsDynamic"></div>',
    ],
  ];
  $stepTabs = $this->getFrame()
    ->core('eventDispatcher')
    ->applyFiltersOnce('eca.chain.step.edit.tabs', ['tabs' => $stepTabs, 'options' => $options]);
  $stepTabs = !empty($stepTabs['tabs']) && is_array($stepTabs['tabs']) ? $stepTabs['tabs'] : $stepTabs;
  ?>

  <div class="ppsgenChainStepBtns">
    <div class="ppsgenChainStepBtnsLeft">
      <?php foreach ($stepTabs as $stepTabKey => $stepTab) { ?>
      <div class="ppsgenChainStepBtnStyle ppsgenChainStepBtn" data-tab="<?php echo esc_attr($stepTabKey); ?>"><?php echo esc_html($stepTab['title'] ?? ''); ?></div>
      <?php } ?>
    </div>
    <div class="ppsgenChainStepBtnsRight">
      <div class="ppsgenChainStepBtnStyle ppsgenLoadChainPreview"><span class="dashicons dashicons-visibility"></span><?php esc_html_e('Full preview', 'ppsgen'); ?></div>
      <div class="ppsgenChainStepBtnStyle ppsgenChainStepBtnSave"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Save Current Step', 'ppsgen'); ?></div>
    </div>
  </div>

  <?php
  $prefix = 'data[chain][step_options]';
$component->setNamePrefix($prefix);
?>

  <div class="ppsgenChainStepTabContent" data-tab="stepOptions">

    <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Step is Enabled', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->checkboxToggle('[active]', ['checked' => '1', 'class' => '']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Step Name', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->text('[name]'); ?>
      </div>
    </div>

    <h3><?php esc_html_e('Email Settings', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Subject', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enter one subject, or several subject variants separated by commas or new lines. Before each email is sent, one non-empty trimmed variant is selected randomly.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->textarea('[email_subject]', ['attrs' => 'rows="4" style="width:100%;"']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Send Without Open Tracking', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Do not add the invisible 1x1 open tracking image to emails sent by this step. This can improve deliverability for sensitive inboxes, but open statistics will not be recorded for these emails.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->checkboxToggle('[send_without_open_tracking]', ['checked' => '', 'class' => '']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Send Without Link Tracking', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Do not rewrite links through the campaign click tracker. Recipients will see and open the original URLs, which can reduce spam-filter risk, but click statistics will not be recorded for this step.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->checkboxToggle('[send_without_link_tracking]', ['checked' => '', 'class' => '']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Send Without Unsubscribe', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Do not add List-Unsubscribe headers and remove links that use the [unsubscribe_url] placeholder from emails sent by this step. Use carefully: this can help test deliverability fingerprints, but marketing emails may still require a clear unsubscribe method.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->checkboxToggle('[send_without_unsubscribe]', ['checked' => '', 'class' => '']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Disable Tracking for Gmail Recipients', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Recipient-aware safe mode for Gmail. When the destination address ends with gmail.com or googlemail.com, this step automatically disables the open pixel, click tracking, List-Unsubscribe headers, and [unsubscribe_url] links only for that recipient. Non-Gmail recipients keep the normal settings selected above.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->checkboxToggle('[send_gmail_without_tracking]', ['checked' => '', 'class' => '']); ?>
      </div>
    </div>

    <h3><?php esc_html_e('Conditions', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Delay & Unit', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('How long to wait before sending this Email.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <span class="ppsgenEcaDelayRelative">
          <?php esc_html_e('Send after ', 'ppsgen'); ?> <?php $component->number('[delay_time]', ['value' => '1', 'attrs' => 'class="ppsgenW60" min="0"']); ?>
        </span>
        <?php $component->select('[delay_unit]', ['options' => ['minutes' => 'Minute(s)', 'hours' => 'Hour(s)', 'days' => 'Day(s)', 'specific_day_of_month' => 'Specific Day of Month', 'specific_date' => 'Specific Date'], 'selected' => 'days']); ?>
        <span class="ppsgenEcaDelayDayOfMonth" hidden>
          <?php esc_html_e('Send on day ', 'ppsgen'); ?> <?php $component->number('[delay_day_of_month]', ['value' => '1', 'attrs' => 'class="ppsgenW60" min="1" max="31"']); ?>
        </span>
        <span class="ppsgenEcaDelaySpecificDate" hidden>
          <?php esc_html_e('Send on ', 'ppsgen'); ?> <?php $component->input('[delay_specific_date]', ['type' => 'date', 'value' => '', 'attrs' => 'class="ppsgenEcaDelayDateInput"']); ?>
        </span>
      </div>
    </div>

    <?php $allContactTags = $this->frame->getModule('contact')->getSubmod('contact_tag')->getModel()->getAllContactTags(true); ?>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Required Tag(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must have (all) selected tags for send this Email.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Tag(s):', 'ppsgen'); ?> <?php $component->selectmultiple('[tag_yes][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => '']); ?>
        </div>
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Condition:', 'ppsgen'); ?> <?php $component->select('[tag_yes_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => 'or']); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Forbidden Tag(s)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact must not have (any of) the selected tags to run this Email.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Tag(s):', 'ppsgen'); ?> <?php $component->selectmultiple('[tag_not][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => '']); ?>
        </div>
        <div class="ppsgenComponentOptionInline">
          <?php esc_html_e('Condition:', 'ppsgen'); ?> <?php $component->select('[tag_not_condition]', ['options' => ['or' => 'At least one', 'and' => 'All from list'], 'selected' => 'or']); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('On False Condition', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('What to do if it\'s time to send the Email but Conditions are not met.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php $component->select('[on_false_condition]', ['options' => ['try_after_hours' => 'Try After 2 Hours', 'try_after_day' => 'Try After One Day', 'skip' => 'Skip Step and Continue the Campaign', 'exit' => 'Stop the Campaign for this Contact'], 'selected' => 'try_after_day']); ?>
        </div>
      </div>
    </div>

    <h4><?php esc_html_e('Email Events', 'ppsgen'); ?></h4>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Add Tag(s) After Open', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Tags to automatically add when the contact opens this email.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php $component->selectmultiple('[add_tag_view][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => '']); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Remove Tag(s) After Open', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Tags to remove when the contact opens this email.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php $component->selectmultiple('[delete_tag_view][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => '']); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Add Tag(s) After Link is Clicked', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Tags to automatically add when a contact clicks a link to your site.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php $component->selectmultiple('[add_tag_click][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => '']); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Remove Tag(s) After Link is Clicked', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Tags to remove when a contact clicks a link to your site.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php $component->selectmultiple('[delete_tag_click][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => '']); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Add Tag(s) on Unsubscribe', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Tags to automatically add when the contact unsubscribes from this email step.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <div class="ppsgenComponentOptionInline">
          <?php $component->selectmultiple('[add_tag_unsubscribe][]', ['class' => 'ppsgenselect', 'options' => $allContactTags, 'selected' => '']); ?>
        </div>
      </div>
    </div>

    <h4><?php esc_html_e('Other', 'ppsgen'); ?></h4>

    <div class="ppsgenComponentRow ppsgenExtFeature" data-ext-label="<?php esc_attr_e('Only in PRO', 'ppsgen'); ?>">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Loop this Step', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
        'Enabling this option makes this step repeat automatically after the specified delay. It can be useful for news, blog, or article subscription email campaigns.',
        'ppsgen',
      ); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->checkboxToggle('[is_loop]', ['checked' => '', 'class' => '']); ?>
      </div>
    </div>

    <?php if (false) { ?>
    <h3><?php esc_html_e('Additional (Expert Level)', 'ppsgen'); ?></h3>
    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Use as Exit Condition Step', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
        'This step will NOT run automatically in sequence. It runs only when its exit condition is triggered.',
        'ppsgen',
      ); ?>"></div>
      <div class="ppsgenComponentOptionInline">
        <?php $component->checkboxToggle('[is_exit_chain]', ['checked' => '', 'class' => '']); ?>
      </div>
    </div>
    <?php } ?>

    <h3><?php esc_html_e('Date Info', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Created', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->text('[created_at]', ['value' => '', 'attrs' => 'readonly ']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Updated', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->text('[updated_at]', ['value' => '', 'attrs' => 'readonly ']); ?>
      </div>
    </div>

    <?php $component->setNamePrefix(''); ?>


  </div>

  <div class="ppsgenChainStepTabContent" data-tab="stepFields">

    <div class="ppsgenChainFieldsWrapper">
      <?php
            $element_types = [
              'row' => esc_html__('Step 1. Row', 'ppsgen'),
              'column' => esc_html__('Step 2. Column', 'ppsgen'),
              'title' => esc_html__('Step 3. Title', 'ppsgen'),
              'textarea' => esc_html__('Step 3. Textarea (Editor)', 'ppsgen'),
              'image' => esc_html__('Step 3. Image', 'ppsgen'),
              'svg_icon' => esc_html__('Step 3. SVG Icon', 'ppsgen'),
  'button' => esc_html__('Step 3. Button', 'ppsgen'),
  'pro' => esc_html__('Step 3. Posts (PRO)', 'ppsgen'),
  'woo_categories' => esc_html__('Step 3. Woo Categories', 'ppsgen'),
  'woo_tags' => esc_html__('Step 3. Woo Tags', 'ppsgen'),
  'woo_attributes' => esc_html__('Step 3. Woo Attributes', 'ppsgen'),
  'woo_products' => esc_html__('Step 3. Woo Products', 'ppsgen'),
];
$parent_types = ['row', 'column'];
?>

      <div class="ppsgenChainFieldsLeft">
        <div class="ppsgenCfbContainer">
          <div class="ppsgenCfbAddFieldWrapper">
            <div class="ppsgenCfbAddFieldLabel"><?php esc_html_e('Choose Element', 'ppsgen'); ?></div>
            <select class="ppsgenCfbAddFieldList ppsgenCfbAddOtherFieldList">
              <?php if (!empty($element_types)) {
                foreach ($element_types as $type => $type_name) {
      $disabledAttr = in_array($type, ['woo_categories', 'woo_tags', 'woo_attributes', 'woo_products'], true) ? ' disabled="disabled"' : '';
                  echo '<option data-field-type="' . esc_attr($type) . '" value=""' . $disabledAttr . '>' . esc_html($type_name) . '</option>';
                }
              } ?>
            </select>
            <div class="ppsgenCfbAddFieldBtn ppsgenCfbAddOtherFieldBtn"><?php esc_html_e('Add to Step', 'ppsgen'); ?></div>
          </div>

          <div class="ppsgenCfbFieldsWrapper">

          </div>
          <div class="ppsgenCffOptionContainerSubtitle">
            <div><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Preview only: Elements are shown as an imitation of email tables for layout purposes and are not the final design.', 'ppsgen'); ?></div>
            <div><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Click and hold the mouse button to drag and change the order of fields inside the container. Row -> Column -> (Other Fields)', 'ppsgen'); ?></div>
            <div><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Click on a field you added to display its options in the right panel.', 'ppsgen'); ?></div>
            <div><span class="dashicons dashicons-editor-help"></span><?php esc_html_e(
              'You can use Contact Field Values placeholders in content. Example: {{contact.first_name|Your Excellency}}, where "Your Excellency" - will be inserted if first_name is empty.',
              'ppsgen',
            ); ?></div>
          </div>
        </div>
      </div>

      <div class="ppsgenChainFieldsRight" style="display:none;">
        <div class="ppsgenComponentsList ppsgenCffOptionContainer">
          <div class="ppsgenCffOptionContainerTitle"><?php esc_html_e('Elements Options', 'ppsgen'); ?></div>
          <div class="ppsgenCffOptionContainerSubtitle" style="display:none;">
            <span class="dashicons dashicons-editor-help"></span>
            <?php esc_html_e('You can override the selected field options below. Changes will apply only to this Step.', 'ppsgen'); ?>
          </div>

          <?php $component->hidden('field_key'); ?>
          <?php $component->hidden('field_type'); ?>
          <?php $component->hidden('field_id'); ?>
          <?php $component->hidden('parent_key'); ?>
          <?php $component->hidden('order_index'); ?>

          <div class="ppsgenCffActionBtns">
            <div class="ppsgenCffDuplicateBtn"><span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Duplicate Selected Element', 'ppsgen'); ?></div>
            <div class="ppsgenCffDeleteBtn"><span class="dashicons dashicons-trash"></span> <?php esc_html_e('Delete Selected Element', 'ppsgen'); ?></div>
          </div>

          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentListTitle"><?php esc_html_e('Display Settings', 'ppsgen'); ?></div>
          </div>

          <div class="ppsgenComponentRow">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Width', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->select('width_select', [
                'options' => ['manual' => 'Manual', 'auto' => 'Auto'],
                'class' => 'data-option-toggle-trigger',
                'selected' => 'auto',
              ]); ?>
              <span class="ppsgenComponentOptionInline" data-toggle-name="width_select" data-toggle-val="manual">
                <?php $component->number('width', ['value' => '100', 'attrs' => 'step=".1" min="0" max="100" class="ppsgenW60"']); ?>

                <span class="ppsgenComponentOptionInline">
                  <?php $component->select('width_unit', ['options' => ['percent' => '%'], 'class' => '', 'selected' => 'percent']); ?>
                </span>

              </span>
            </div>
          </div>

          <div class="ppsgenComponentRow" data-field-type-hide="column">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Max-width', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->select('max_width_select', [
                'options' => ['auto' => 'Auto', 'manual' => 'Manual'],
                'class' => 'data-option-toggle-trigger',
                'selected' => 'auto',
              ]); ?>
              <span class="ppsgenComponentOptionInline" data-toggle-name="max_width_select" data-toggle-val="manual">
                <?php $component->number('max_width', ['value' => '100', 'attrs' => 'step=".1" min="0" class="ppsgenW60"']); ?>
                <?php $component->select('max_width_unit', ['options' => $component->getTableUnits(), 'class' => '', 'selected' => 'percent']); ?>
              </span>
            </div>
          </div>

          <div class="ppsgenComponentRow" data-field-type="title, button, textarea, image">
            <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Display Type', 'ppsgen'); ?>"></div>
            <div class="ppsgenComponentOptionInline">
              <?php $component->select('display_type', ['options' => ['block' => 'block', 'inline-block' => 'inline-block', 'inline' => 'inline'], 'selected' => 'block']); ?>
            </div>
          </div>

          <div class="ppsgenComponentListTabBtns">
            <div class="ppsgenComponentListTabBtn ppsgenComponentListTabBtnActive" data-tab="general"><?php esc_html_e('General Settings', 'ppsgen'); ?></div>
            <div class="ppsgenComponentListTabBtn" data-tab="design"><?php esc_html_e('Design & Styles', 'ppsgen'); ?></div>
          </div>

          <div class="ppsgenComponentListTab ppsgenComponentListTabActive" data-tab="general">

            <div class="ppsgenComponentRow">
              <div class="ppsgenComponentListTitle"><?php esc_html_e('Specific Element Settings', 'ppsgen'); ?></div>
            </div>

            <!-- Icon Specific Options -->
            <div class="ppsgenComponentRow" data-field-type="svg_icon">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SVG Icon', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOptionInline" style="width:100%;">
                <?php $component->text('svg_icon', ['attrs' => 'class="ppsgenSvgIconInput" style="width:100%; max-width:300px;"']); ?>
                <div class="ppsgenShowUndrIconBtn" data-target="ppsgenSvgIconInput"><?php esc_html_e('Show Icons List', 'ppsgen'); ?></div>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="svg_icon">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SVG Icon Color', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOptionInline" style="width:100%;">
                <?php $component->colorpicker('svg_icon_color', ['value' => '']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="svg_icon">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link URL', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('icon_url', ['attrs' => 'placeholder="https://"']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="svg_icon">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('icon_title', ['attrs' => 'placeholder="' . __('Follow on Instagram', 'ppsgen') . '"']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="svg_icon">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Target', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('icon_target', ['options' => ['' => '', '_self' => __('Same Tab', 'ppsgen'), '_blank' => __('New Tab', 'ppsgen')], 'selected' => '']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="svg_icon">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Rel', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('icon_rel', [
                          'options' => [
                            '' => '',
                            'follow' => __('Follow', 'ppsgen'),
                            'nofollow' => __('No Follow', 'ppsgen'),
                            'nofollow sponsored' => __('No Follow + Sponsored', 'ppsgen'),
                            'follow sponsored' => __('Follow + Sponsored', 'ppsgen'),
                          ],
                          'selected' => '',
                        ]); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="column">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Vertical Align Inside Container', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('vertical_align', ['options' => ['' => '', 'top' => __('Top', 'ppsgen'), 'middle' => __('Middle', 'ppsgen'), 'bottom' => __('Bottom', 'ppsgen')], 'selected' => '']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="column">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Horizontal Align Inside Container', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('horizontal_align', ['options' => ['' => '', 'left' => __('Left', 'ppsgen'), 'center' => __('Center', 'ppsgen'), 'right' => __('Right', 'ppsgen')], 'selected' => '']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Text/Img/Svg Align', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('text_align', ['options' => $component->getTextAlign()]); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="title">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title Tag', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('title_tag', ['options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'h5' => 'h5', 'h6' => 'h6'], 'selected' => 'h1']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="title">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title Text', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('title_text'); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="unsubscribe">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Unsubscribe Text', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('unsubscribe_text'); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="textarea">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Textarea Content', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php wp_editor('', 'html_textarea_content', [
                  'textarea_name' => 'html_textarea_content',
                  'textarea_rows' => 6,
                  'media_buttons' => true,
                  'wpautop' => false,
                  'teeny' => false,
                  'tinymce' => true,
                  'quicktags' => true,
                ]); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="image">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Image SRC', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Enter the Source URL for the image.', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenEcaImageSrcPicker">
                  <?php $component->text('image_src', ['attrs' => 'placeholder="https://" class="ppsgen-media-upload ppsgenEcaImageSrcInput"']); ?>
                  <button type="button" class="ppsgen-media-upload-btn ppsgenEcaImageSrcMediaBtn">
                    <span class="dashicons dashicons-format-image"></span>
                  </button>
                  <span class="ppsgenEcaImageSrcPreviewWrap" aria-hidden="true">
                    <img class="ppsgenEcaImageSrcPreview" alt="" />
                  </span>
                </div>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="image">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Title', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('image_title', ['attrs' => 'placeholder="' . __('Follow on Instagram', 'ppsgen') . '"']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="image">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link URL', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('(Optional) Enter the redirect URL after click on image.', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('image_url', ['attrs' => 'placeholder="https://"']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="image">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Target', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('image_target', ['options' => ['' => '', '_self' => __('Same Tab', 'ppsgen'), '_blank' => __('New Tab', 'ppsgen')], 'selected' => '']); ?>
              </div>
            </div>

            <!-- Button Specific Options -->
            <div class="ppsgenComponentRow" data-field-type="button">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Button Text', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('button_text', ['attrs' => 'placeholder="Click Me"']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="button">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link URL', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->text('button_url', ['attrs' => 'placeholder="https://"']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="button">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Target', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <?php $component->select('button_target', ['options' => ['' => '', '_self' => __('Same Tab', 'ppsgen'), '_blank' => __('New Tab', 'ppsgen')], 'selected' => '']); ?>
              </div>
            </div>

          </div>

          <div class="ppsgenComponentListTab" data-tab="design">

            <div class="ppsgenComponentRow">
              <div class="ppsgenComponentListTitle"><?php esc_html_e('Design & Styles', 'ppsgen'); ?></div>
            </div>

            <div class="ppsgenComponentRow" data-field-type-hide="row">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Padding (inner indent)', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenComponentOptionInline">
                  <?php
                  esc_html_e('top', 'ppsgen');
$component->number('padding_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_top_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                  <?php
esc_html_e('right', 'ppsgen');
$component->number('padding_right', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_right_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                </div>
                <div class="ppsgenComponentOptionInline">
                  <?php
esc_html_e('bottom', 'ppsgen');
$component->number('padding_bottom', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_bottom_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                  <?php
esc_html_e('left', 'ppsgen');
$component->number('padding_left', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('padding_left_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                </div>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type-hide="row, column">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Margin (outer indent)', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenComponentOptionInline">
                  <?php
esc_html_e('top', 'ppsgen');
$component->number('margin_top', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_top_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                  <?php
esc_html_e('right', 'ppsgen');
$component->number('margin_right', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_right_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                </div>
                <div class="ppsgenComponentOptionInline">
                  <?php
esc_html_e('bottom', 'ppsgen');
$component->number('margin_bottom', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_bottom_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                  <?php
esc_html_e('left', 'ppsgen');
$component->number('margin_left', ['value' => '0', 'attrs' => 'class="ppsgenSWI55"']);
$component->select('margin_left_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']);
?>
                </div>
              </div>
            </div>

            <div class="ppsgenComponentRow">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenComponentOptionInline">
                  <?php $component->number('border', ['value' => '0', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
                  <?php $component->select('border_unit', ['options' => $component->getTableUnits(), 'class' => 'ppsgenSWI55', 'selected' => 'px']); ?>
                  <?php $component->select('border_style', [
  'options' => ['solid' => 'solid', 'dotted' => 'dotted', 'dashed' => 'dashed', 'double' => 'double', 'groove' => 'groove', 'ridge' => 'ridge', 'inset' => 'inset', 'outset' => 'outset', 'none' => 'none', 'hidden' => 'hidden'],
  'class' => 'ppsgenSWI90',
  'selected' => 'solid',
]); ?>
                </div>
                <div class="ppsgenComponentOptionInline">
                  <?php $component->colorpicker('border_color', ['value' => '']); ?>
                </div>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type-hide="row, column">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Border Radius', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOptionInline">
                <?php $component->number('border_radius', ['value' => '0', 'attrs' => 'min="0" class="ppsgenW60"']); ?>
                <?php $component->select('border_radius_unit', ['options' => $component->getTableUnits(), 'class' => '', 'selected' => 'px']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Background', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenComponentOptionInline">
                  <?php $component->colorpicker('background', ['value' => '']); ?> <?php esc_html_e('Default', 'ppsgen'); ?>
                </div>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="title, button, textarea, unsubscribe">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Font Family', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOptionInline">
                <?php $component->select('font_family', ['options' => $component->getEmailFonts(true), 'class' => '', 'selected' => 'default']); ?>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="title, button, textarea, unsubscribe">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Font Styles', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenComponentOptionInline">
                  <?php esc_html_e('Size', 'ppsgen'); ?>
                  <?php $component->number('font_size', ['value' => '14', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
                  <?php $component->select('font_size_unit', ['options' => $component->getTableFontUnits(), 'class' => 'ppsgenSWI55']); ?>
                </div>
                <div class="ppsgenComponentOptionInline">
                  <?php esc_html_e('Line Height', 'ppsgen'); ?>
                  <?php $component->number('font_lh', ['value' => '18', 'attrs' => 'min="0" class="ppsgenSWI55"']); ?>
                  <?php $component->select('font_lh_unit', ['options' => $component->getTableFontUnits(), 'class' => 'ppsgenSWI55']); ?>
                </div>
                <div class="ppsgenComponentOptionInline">
                  <?php $component->colorpicker('font_color', ['value' => '']); ?>
                </div>
                <div class="ppsgenComponentOptionInline">
                  <?php $component->select('font_style', ['options' => $component->getFontStyles(), 'class' => 'ppsgenSWI120']); ?>
                  <?php $component->select('font_weight', ['options' => $component->getFontWeight(), 'class' => 'ppsgenSWI120']); ?>
                </div>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="textarea">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Color', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenComponentOptionInline">
                  <?php $component->colorpicker('link_color', ['value' => '']); ?>
                </div>
              </div>
            </div>

            <div class="ppsgenComponentRow" data-field-type="textarea, button, unsubscribe">
              <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Link Text Decoration', 'ppsgen'); ?>"></div>
              <div class="ppsgenComponentOption">
                <div class="ppsgenComponentOptionInline">
                  <?php $component->select('link_text_decoration', ['options' => $component->getTextDecoration(), 'class' => 'ppsgenSWI120']); ?>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>

    </div>

  </div>

  <?php foreach ($stepTabs as $stepTabKey => $stepTab) {
    if (in_array($stepTabKey, ['stepFields', 'stepOptions'], true)) {
      continue;
    } ?>
  <div class="ppsgenChainStepTabContent" data-tab="<?php echo esc_attr($stepTabKey); ?>">
    <?php echo $this->getFrame()->core('helper')->wpKses($stepTab['content'] ?? ''); ?>
  </div>
  <?php } ?>

</div>

<div id="ppsgenPreviewPopup">
  <div class="ppsgen-popup-inner">
    <div class="ppsgen-popup-header">
      <strong><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Preview', 'ppsgen'); ?></strong>
      <div id="ppsgenPreviewClose">✕</div>
    </div>
    <iframe id="ppsgenPreviewFrame"></iframe>
  </div>
</div>


<div id="ppsgenIconSvgOverlay" style="display:none;">
  <div class="ppsgenIconSvgOverlayBg"></div>
  <div class="ppsgenIconSvgBox">
    <div class="ppsgenIconSvgHeader">
      <input type="text" class="ppsgenIconSvgSearch" placeholder="<?php esc_attr_e('Search icons...', 'ppsgen'); ?>">
      <div class="ppsgenIconSvgClose">&times;</div>
    </div>
    <div class="ppsgenIconSvgTabs">
      <div data-tab="undraw" class="active"><?php esc_html_e('Undraw', 'ppsgen'); ?></div>
    </div>
    <div class="ppsgenIconSvgContent">
      <div class="ppsgenIconSvgTab" data-tab="undraw">
        <div class="ppsgenIconSvgGrid"></div>
      </div>
    </div>
    <div class="ppsgenIconSvgPagination"></div>
  </div>
</div>
