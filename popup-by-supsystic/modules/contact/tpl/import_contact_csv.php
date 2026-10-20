<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$prefix = 'data[contact_migration]';
$component->setNamePrefix($prefix);
?>

<form method="POST" id="ppsgenCsvContactSubmitForm">
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" id="action" name="action" value="importCsvContacts">

  <h3><?php esc_html_e('Import Contact List from CSV File', 'ppsgen'); ?></h3>
  <p><?php esc_html_e('Example CSV file: ', 'ppsgen'); ?><a href="<?php echo esc_url(PPSGEN_MODULES_URL . 'contact/assets/js/example.csv'); ?>">Download</a></p>
  <p><?php esc_html_e('Character Set (Format) is Unicode "UTF-8" | Field Delimiter is ";" | Text Delimiter is "', 'ppsgen'); ?></p>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Your CSV File', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <input type="file" name="data[contact_migration][csv_file]" accept=".csv">
      <span>* Maximum file size is 5 MB.</span>
    </div>
  </div>
  <div class="ppsgenComponentsList">
    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('What to do with Contact with same (email/wp_user_id/contact_id)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[duplicate_logic]', ['options' => ['skip' => 'Skip', 'rewrite' => 'Clear and Rewrite from CSV', 'update' => 'Update Existing Fields from CSV'], 'selected' => 'skip']); ?>
      </div>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Import to Tags (optional)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[tags][]', ['options' => $this->allContactTags, 'class' => 'ppsgenselect']); ?>
    </div>
  </div>
  <button class="ppsgenSaveBtn ppsgenCsvContactSubmitBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Import Contacts', 'ppsgen'); ?></button>

</form>

<?php $component->setNamePrefix('');
?>
