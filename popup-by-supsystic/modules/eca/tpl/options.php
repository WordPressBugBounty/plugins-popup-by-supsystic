<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$ecaOptions = is_array($this->ecaOptions ?? null) ? $this->ecaOptions : [];
$prefix = 'data[eca]';
$trackingPrefix = function_exists('ppsgen_get_eca_tracking_query_prefix') ? ppsgen_get_eca_tracking_query_prefix() : '';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle">
    <span class="dashicons dashicons-admin-settings"></span>
    <?php esc_html_e('Campaign defaults', 'ppsgen'); ?>
  </h2>
  <button class="ppsgenSaveBtn ppsgenEcaOptionsSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
</div>
<div class="ppsgenOptionsWrapper">
  <form class="ppsgenOptionsForm ppsgenEcaOptionsForm" id="ppsgenEcaOptionsForm">
    <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
    <input type="hidden" name="mod" value="eca">
    <input type="hidden" id="action" name="action" value="editEcaOptions">
    <div class="ppsgenComponentsList">
      <h3><?php esc_html_e('Email Tracking', 'ppsgen'); ?></h3>
      <div class="ppsgenComponentRow">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Tracking link prefix', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Controls the public query parameter names used in campaign email tracking URLs. New emails use prefix_a for opens and clicks, and prefix_u for unsubscribe links. Use a short, unique, random-looking value per site to avoid exposing the same campaign fingerprint everywhere. This does not rename database tables, cron hooks, admin pages, or internal records. Already sent emails with old ppsgen_eca_* links are still accepted.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->text('[eca_tracking_query_prefix]', [
            'value' => $component->value($ecaOptions['eca_tracking_query_prefix'] ?? null, $trackingPrefix),
            'attrs' => 'class="ppsgenW250" maxlength="24" pattern="[A-Za-z][A-Za-z0-9_]{2,23}"',
          ]); ?>
          <div class="ppsgenComponentOptionNoticeUnder">
            <?php esc_html_e('Example: abc123_a stores open/click events, abc123_u opens unsubscribe. Change this only before sending a new campaign batch if you want a fresh email URL fingerprint.', 'ppsgen'); ?>
          </div>
        </div>
      </div>
    </div>
    <button class="ppsgenSaveBtn ppsgenEcaOptionsSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
  </form>
</div>
<?php $component->setNamePrefix(''); ?>
