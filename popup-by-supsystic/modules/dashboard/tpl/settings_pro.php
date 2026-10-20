<?php
if (!defined('ABSPATH')) {
  exit();
}
$offerUrl = (string) ($this->proOfferUrl ?? '');
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle"><?php esc_html_e('Licence & PRO', 'ppsgen'); ?></h2>
</div>
<div class="ppsgenProOfferNotice ppsgenProOfferNoticeTab">
  <div class="ppsgenProOfferNoticeHero">
    <div class="ppsgenProOfferNoticeEyebrow"><?php esc_html_e('PRO FEATURES', 'ppsgen'); ?></div>
    <div class="ppsgenProOfferNoticeBadges">
      <span class="ppsgenProOfferNoticeBadge">CRM</span>
      <span class="ppsgenProOfferNoticeBadge">EMAILS</span>
      <span class="ppsgenProOfferNoticeBadge">FORMS</span>
      <span class="ppsgenProOfferNoticeBadge">POPUPS</span>
    </div>
    <h3 class="ppsgenProOfferNoticeTitle"><?php esc_html_e('Get PRO for $99 a year', 'ppsgen'); ?></h3>
    <p class="ppsgenProOfferNoticeText"><?php esc_html_e('Get the full PRO version with advanced CRM tools, premium builders, provider integrations, and deeper automation flows for $99 a year, renewing at the same price.', 'ppsgen'); ?></p>
    <div class="ppsgenProOfferNoticeCouponLabel"><?php esc_html_e('Yearly licence · 1 site', 'ppsgen'); ?></div>
    <div class="ppsgenProOfferNoticeCoupon"><span><strong>$99</strong> / year</span></div>
    <div class="ppsgenProOfferNoticeSubtext"><?php esc_html_e('$99 stays $99 when you renew · No contact-based pricing · 30-day money-back guarantee', 'ppsgen'); ?></div>
    <div class="ppsgenProOfferNoticeActions">
      <a class="ppsgenProOfferNoticeBtn ppsgenProOfferNoticeBtnPrimary" href="<?php echo esc_url($offerUrl); ?>" target="_blank" rel="noopener noreferrer">
        <span class="dashicons dashicons-superhero-alt"></span>
        <?php esc_html_e('Unlock all PRO features', 'ppsgen'); ?>
      </a>
    </div>
    <p class="ppsgenSettingsProAlready">
      <strong><?php esc_html_e('Already purchased?', 'ppsgen'); ?></strong>
      <?php esc_html_e('Install and activate the PRO plugin from your Supsystic account. Your licence key is entered on this page once PRO is active.', 'ppsgen'); ?>
    </p>
  </div>
  <div class="ppsgenProOfferNoticeFeatures">
    <div class="ppsgenProOfferNoticeFeaturesTitle"><?php esc_html_e('Everything in PRO', 'ppsgen'); ?></div>
    <ul class="ppsgenProOfferNoticeFeatureList">
      <li><?php esc_html_e('Email your whole audience: every contact stays reachable, and imports of any size (Free emails the first 500)', 'ppsgen'); ?></li>
      <li><?php esc_html_e('Run as many automations at once as you need, such as a welcome series next to your enquiry follow-up', 'ppsgen'); ?></li>
      <li><?php esc_html_e('Your own branding: no "Powered by Supsystic" in the emails your clients receive', 'ppsgen'); ?></li>
      <li><?php esc_html_e('The full template library for forms, popups and emails', 'ppsgen'); ?></li>
      <li><?php esc_html_e('Send through Gmail, Microsoft 365, SendGrid and other delivery services, set up for you', 'ppsgen'); ?></li>
      <li><?php esc_html_e('WooCommerce product grids and dynamic fields in emails, file uploads, country targeting', 'ppsgen'); ?></li>
      <li><?php esc_html_e('Countdown timers, multi-step popups and custom JavaScript', 'ppsgen'); ?></li>
    </ul>
  </div>
</div>
