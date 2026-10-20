<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->getFrame()->core('component');
$prefix = 'data[form][addons][conditional_logic]';
$component->setNamePrefix($prefix);
?>
    <div class="ppsgenComponentsList">
      <h3><?php esc_html_e('Conditional Logic', 'ppsgen'); ?></h3>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Hidden Fields by Default', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->selectmultiple('[hidden_fields][]', [
            'options' => [],
            'class' => 'ppsgenClHiddenFieldsSelect',
            'selected' => $this->hidden_fields,
          ]); ?>
        </div>
      </div>
      <div class="ppsgenClContainer">
        <div class="ppsgenClAddLogicBtn"><?php esc_html_e('Add New Conditional Logic', 'ppsgen'); ?></div>
        <div class="ppsgenClLogicsWrapper"></div>
        <input type="hidden" name="<?php echo esc_attr($prefix); ?>[logic_data]" class="ppsgenClLogicDataInput" value="">
      </div>
    </div>