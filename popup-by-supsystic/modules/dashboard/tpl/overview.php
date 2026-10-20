<?php
if (!defined('ABSPATH')) {
  exit();
}
$isLegacyUser = function_exists('ppsgen_is_legacy_user') && ppsgen_is_legacy_user();
$isLegacyActive = function_exists('ppsgen_is_legacy_active') && ppsgen_is_legacy_active();
?>
<div class="ppsgenOverviewPage">
  <section class="ppsgenOverviewHero">
    <div class="ppsgenOverviewHeroContent">
      <div class="ppsgenOverviewEyebrow"><?php esc_html_e('Customer Engagement System – Popups, Forms, CRM & Automation', 'ppsgen'); ?></div>
      <h2><?php esc_html_e('One workspace for popups, forms, CRM contacts, and email campaigns.', 'ppsgen'); ?></h2>
      <p><?php esc_html_e('The new Supsystic interface is built around complete marketing flows: collect a lead, store it in CRM, tag it, trigger email campaigns, and measure what happened from the same plugin.', 'ppsgen'); ?></p>
      <div class="ppsgenOverviewActions">
        <button type="button" class="ppsgenOverviewPrimaryBtn ppsgenTourStartBtn" data-tour-id="dashboard">
          <span class="dashicons dashicons-controls-play"></span>
          <?php esc_html_e('Start tutorial', 'ppsgen'); ?>
        </button>
        <a class="ppsgenOverviewSecondaryBtn" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=dashboard_modules')); ?>">
          <span class="dashicons dashicons-admin-plugins"></span>
          <?php esc_html_e('Manage modules', 'ppsgen'); ?>
        </a>
      </div>
    </div>
    <div class="ppsgenOverviewHeroPanel">
      <div class="ppsgenOverviewFlowCard ppsgenOverviewFlowCardActive">
        <span class="dashicons dashicons-testimonial"></span>
        <strong><?php esc_html_e('Popup', 'ppsgen'); ?></strong>
        <small><?php esc_html_e('Show the right offer', 'ppsgen'); ?></small>
      </div>
      <div class="ppsgenOverviewFlowLine"></div>
      <div class="ppsgenOverviewFlowCard">
        <span class="dashicons dashicons-format-aside"></span>
        <strong><?php esc_html_e('Form', 'ppsgen'); ?></strong>
        <small><?php esc_html_e('Capture the lead', 'ppsgen'); ?></small>
      </div>
      <div class="ppsgenOverviewFlowLine"></div>
      <div class="ppsgenOverviewFlowCard">
        <span class="dashicons dashicons-admin-users"></span>
        <strong><?php esc_html_e('CRM', 'ppsgen'); ?></strong>
        <small><?php esc_html_e('Segment and enrich', 'ppsgen'); ?></small>
      </div>
      <div class="ppsgenOverviewFlowLine"></div>
      <div class="ppsgenOverviewFlowCard">
        <span class="dashicons dashicons-email-alt2"></span>
        <strong><?php esc_html_e('Email', 'ppsgen'); ?></strong>
        <small><?php esc_html_e('Follow up automatically', 'ppsgen'); ?></small>
      </div>
    </div>
  </section>

  <?php if ($isLegacyUser) { ?>
    <section class="ppsgenOverviewLegacyNotice">
      <div>
        <h3><?php esc_html_e('Your Classic PopUp setup is safe.', 'ppsgen'); ?></h3>
        <p><?php echo esc_html($isLegacyActive ? __('Preview the new admin panel without turning Classic PopUp off. When you activate the new interface, your existing Classic popups continue working on the site and remain editable from Classic PopUp.', 'ppsgen') : __('The new admin panel is active. Your existing Classic popups still work on the site and remain editable from Classic PopUp whenever you enable it from Settings › Classic PopUp.', 'ppsgen')); ?></p>
      </div>
      <a class="ppsgenOverviewSecondaryBtn" href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=dashboard_options')); ?>">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Dashboard Options', 'ppsgen'); ?>
      </a>
    </section>
  <?php } ?>

  <section class="ppsgenOverviewFeatureGrid">
    <article class="ppsgenOverviewFeature">
      <span class="dashicons dashicons-testimonial"></span>
      <h3><?php esc_html_e('Modern popup builder', 'ppsgen'); ?></h3>
      <p><?php esc_html_e('Create responsive popups with design controls, templates, triggers, statistics, custom CSS/JS, and form blocks inside the same builder.', 'ppsgen'); ?></p>
    </article>
    <article class="ppsgenOverviewFeature">
      <span class="dashicons dashicons-format-aside"></span>
      <h3><?php esc_html_e('Forms connected to CRM', 'ppsgen'); ?></h3>
      <p><?php esc_html_e('Build forms that create or update contacts, collect structured fields, support validation, and feed your campaigns automatically.', 'ppsgen'); ?></p>
    </article>
    <article class="ppsgenOverviewFeature">
      <span class="dashicons dashicons-admin-users"></span>
      <h3><?php esc_html_e('Contact database', 'ppsgen'); ?></h3>
      <p><?php esc_html_e('Manage contacts, fields, tags, verification, activity, imports, exports, and engagement signals in one WordPress-native CRM.', 'ppsgen'); ?></p>
    </article>
    <article class="ppsgenOverviewFeature">
      <span class="dashicons dashicons-admin-links"></span>
      <h3><?php esc_html_e('Email campaigns and flows', 'ppsgen'); ?></h3>
      <p><?php esc_html_e('Use templates, campaign chains, waits, flow logs, open/click events, and automation rules to follow up after each action.', 'ppsgen'); ?></p>
    </article>
  </section>

  <section class="ppsgenOverviewCompare">
    <div>
      <h3><?php esc_html_e('What changed from Classic PopUp?', 'ppsgen'); ?></h3>
      <p><?php esc_html_e('Classic PopUp was focused mainly on popup creation. Customer Engagement System turns the popup into the first step of a full marketing system: forms, CRM, campaigns, templates, mail configurations, and PRO extensions now live together.', 'ppsgen'); ?></p>
    </div>
    <ul>
      <li><?php esc_html_e('One menu and one visual language for every marketing module.', 'ppsgen'); ?></li>
      <li><?php esc_html_e('Shared templates and assets across popups, forms, and email content.', 'ppsgen'); ?></li>
      <li><?php esc_html_e('Statistics and contact activity designed for repeated optimization.', 'ppsgen'); ?></li>
      <li><?php esc_html_e('Classic PopUp keeps running your existing popups while you migrate at your own pace.', 'ppsgen'); ?></li>
    </ul>
  </section>
</div>
