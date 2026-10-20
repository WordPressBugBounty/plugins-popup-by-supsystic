<?php
if (!defined('ABSPATH')) {
  exit();
} ?>
<?php
// Each module's overview page (what it does, how to use it, technical notes); PRO parts open the overview of the
// module they extend, Email Providers the email overview (sending), GeoLite the popup overview (targeting)
$overviewTabs = [
  'dashboard' => 'dashboard_overview',
  'contact' => 'contact_overview',
  'contactExt' => 'contact_overview',
  'form' => 'form_overview',
  'formExt' => 'form_overview',
  'popup' => 'popup_overview',
  'popupExt' => 'popup_overview',
  'geolite' => 'popup_overview',
  'eca' => 'eca_overview',
  'ecaExt' => 'eca_overview',
  'providers' => 'eca_overview',
  'license' => 'license_overview',
];
// Files: what the PRO file fields do (Free), the files manager itself once PRO is on
$overviewTabs['files'] = $overviewTabs['files_list'] = $this->getModel()->isProAddonActive() ? 'files_list' : 'dashboard_files_pro';
?>
<div class="ppsgenModulesListWrapper">
  <?php $newModulesList = []; ?>
  <?php foreach ($this->modulesList as $key => $module) { ?>
    <?php $newModulesList[$module['slug']] = $module; ?>
  <?php } ?>
  <?php foreach ($this->modulesInfo as $slug => $module) { ?>
    <?php $moduleInfo = $module; ?>
    <?php $module['available'] = !empty($newModulesList[$slug]['available']) ? $newModulesList[$slug]['available'] : ''; ?>
    <?php $module['active'] = !empty($newModulesList[$slug]['active']) ? $newModulesList[$slug]['active'] : ''; ?>
    <?php $isNotReadyClass = empty($module['is_ready']) ? 'ppsgenModuleBlockWrapperComingSoon' : ''; ?>
    <?php $isProUpsell = !empty($moduleInfo['is_pro_upsell']); ?>
    <?php $isInfoOnly = !empty($moduleInfo['info_only']); ?>
    <div class="ppsgenModuleBlockWrapper <?php ppsgen_esc_attr($isNotReadyClass); ?> <?php echo $isProUpsell ? 'ppsgenModuleBlockWrapperPro' : ''; ?>">
      <div class="ppsgenModuleBlockHead">
        <div class="ppsgenModuleBlockEyebrow">
          <?php if ($moduleInfo['default'] && !$isProUpsell && !$isInfoOnly) { ?>
            <span class="ppsgenModuleBlockDefault"><?php esc_html_e('Core Module', 'ppsgen'); ?></span>
          <?php } ?>
          <?php if ($isProUpsell) { ?>
            <span class="ppsgenModuleBlockProBadge"><?php esc_html_e('PRO', 'ppsgen'); ?></span>
          <?php } elseif ($isInfoOnly) { ?>
            <span class="ppsgenModuleBlockInfoBadge"><?php esc_html_e('Info', 'ppsgen'); ?></span>
          <?php } ?>
          <?php if (empty($module['is_ready'])) { ?>
            <span class="ppsgenModuleBlockComingSoon"><?php esc_html_e('Coming Soon', 'ppsgen'); ?></span>
          <?php } ?>
        </div>
        <div class="ppsgenModuleBlockTitleRow">
          <div class="ppsgenModuleBlockIconWrap">
            <span class="<?php ppsgen_esc_attr($moduleInfo['icon']); ?>"></span>
          </div>
          <div class="ppsgenModuleBlockTitleGroup">
            <div class="ppsgenModuleBlockTitle">
              <?php echo esc_html($moduleInfo['title']); ?>
            </div>
            <div class="ppsgenModuleBlockShortDescription">
              <?php echo esc_html($moduleInfo['short_description']); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="ppsgenModuleBlockBody">
        <div class="ppsgenModuleBlockDescription">
          <?php echo esc_html($moduleInfo['description']); ?>
        </div>
        <?php if (!empty($moduleInfo['highlights']) && is_array($moduleInfo['highlights'])) { ?>
          <ul class="ppsgenModuleBlockFeatureList">
            <?php foreach ($moduleInfo['highlights'] as $highlight) { ?>
              <li><?php echo esc_html($highlight); ?></li>
            <?php } ?>
          </ul>
        <?php } ?>
      </div>
      <div class="ppsgenModuleBlockFooter">
        <?php if (!$moduleInfo['default'] && !$isProUpsell && !$isInfoOnly) { ?>
          <div class="ppsgenModuleBlockAvSt">
            <?php if (!empty($module['available'])) { ?>
              <div class="ppsgenModuleBlockStatusChip ppsgenModuleBlockStatusChipAvailable" title="<?php esc_attr_e('The plugin is available for activation.', 'ppsgen'); ?>">
                <span class="dashicons dashicons-plugins-checked"></span>
                <?php esc_html_e('Available', 'ppsgen'); ?>
              </div>
            <?php } else { ?>
              <div class="ppsgenModuleBlockStatusChip ppsgenModuleBlockStatusChipMuted" title="<?php esc_attr_e('The plugin is not available for activation.', 'ppsgen'); ?>">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php esc_html_e('Locked', 'ppsgen'); ?>
              </div>
            <?php } ?>
            <?php if (!empty($module['active'])) { ?>
              <div class="ppsgenModuleBlockStatusChip ppsgenModuleBlockStatusChipActive" title="<?php esc_attr_e('The plugin is activated.', 'ppsgen'); ?>">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Active', 'ppsgen'); ?>
              </div>
            <?php } else { ?>
              <div class="ppsgenModuleBlockStatusChip ppsgenModuleBlockStatusChipMuted" title="<?php esc_attr_e('The plugin is deactivated.', 'ppsgen'); ?>">
                <span class="dashicons dashicons-dismiss"></span>
                <?php esc_html_e('Inactive', 'ppsgen'); ?>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
        <div class="ppsgenModuleBlockActions">
          <?php if (!empty($overviewTabs[$slug])) { ?>
            <a class="ppsgenModuleBtnOverview" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=' . $overviewTabs[$slug])); ?>">
              <span class="dashicons dashicons-book-alt" aria-hidden="true"></span><?php esc_html_e('Overview', 'ppsgen'); ?>
            </a>
          <?php } ?>
          <?php if ($isProUpsell) { ?>
            <div class="ppsgenModuleBtnActivatePro" data-module="<?php ppsgen_esc_attr($slug); ?>"><?php esc_html_e('Explore PRO', 'ppsgen'); ?></div>
          <?php } elseif (!$moduleInfo['default'] && !$isInfoOnly) { ?>
            <?php if (!empty($module['available']) && empty($module['active'])) { ?>
              <div class="ppsgenModuleBtnActivate" data-module="<?php ppsgen_esc_attr($slug); ?>"><?php esc_html_e('Activate Module', 'ppsgen'); ?></div>
            <?php } ?>
            <?php if (!empty($module['active'])) { ?>
              <div class="ppsgenModuleBtnDeactivate" data-module="<?php ppsgen_esc_attr($slug); ?>"><?php esc_html_e('Deactivate Module', 'ppsgen'); ?></div>
            <?php } ?>
          <?php } ?>
        </div>
      </div>
    </div>
  <?php } ?>
</div>
<div id="ppsgenModuleProOfferTemplate" style="display:none;">
  <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  echo $this->getFrame()->core('helper')->wpKses($this->proModulesOfferHtml ?? ''); ?>
</div>
