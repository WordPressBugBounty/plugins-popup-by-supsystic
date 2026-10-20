<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$templateType = $this->templateType;
$templateTypeList = $this->getModel()->templateTypeList();
if (!array_key_exists($templateType, $templateTypeList)) {
  return '';
}
// FREE first, then PRO themes grouped by topic (holidays by the calendar, then sales, leads, industries)
$templateList = $this->getModel()->getSortedTemplateList();
$templateList = array_values(array_filter($templateList, function ($tpl) use ($templateType) {
  return in_array($templateType, $tpl['module']);
}));
// Same rule as installing: PRO templates need an active PRO licence (a lapsed one keeps existing items only)
$ls = function_exists('ppsgen_is_pro_license_active') && ppsgen_is_pro_license_active();
?>
<div class="ppsgenModulePageTitleWrapper">
  <h2 class="ppsgenModulePageTitle" style="font-size:24px;">
    <?php esc_html_e('Choose a Template for ', 'ppsgen'); ?>
    <?php echo esc_html($templateTypeList[$templateType]); ?>
  </h2>
</div>

<form method="POST" id="ppsgenTemplateAdd" class="ppsgenTemplateEnterNameWrapper">
  <input type="text" required name="template_name" id="template_name" placeholder="<?php esc_attr_e('Enter template name', 'ppsgen'); ?>" value="">
  <input type="hidden" name="template_type" id="template_type" value="<?php echo $templateType; ?>">
  <input type="hidden" name="template_code" id="template_code" value="">
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="submit" name="template_submit" id="template_submit" value="Submit & Create">
</form>

<?php // PRO offer for a PRO template (popups, forms and email templates share the premium look) ?>
<div class="ppsgenTemplateProOfferPopup ppsgenTemplateProOfferPopupLux" id="ppsgenTemplateProOfferPopup" style="display:none;" data-offer-url="https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&amp;utm_medium=feature&amp;utm_campaign=templates_pro">
  <div class="ppsgenTemplateProOfferPopupBackdrop"></div>
  <div class="ppsgenTemplateProOfferPopupDialog" role="dialog" aria-modal="true" aria-labelledby="ppsgenTemplateProOfferPopupTitle">
    <a class="ppsgenTemplateProOfferPopupClose" href="#" aria-label="<?php esc_attr_e('Close popup', 'ppsgen'); ?>">
      <span class="dashicons dashicons-no-alt"></span>
    </a>
    <div class="ppsgenTemplateProOfferPopupMain">
      <div class="ppsgenTemplateProOfferPopupEyebrow"><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><?php esc_html_e('This one comes with PRO', 'ppsgen'); ?></div>
      <h3 class="ppsgenTemplateProOfferPopupTitle" id="ppsgenTemplateProOfferPopupTitle"><?php esc_html_e('Skip the design work with the full template library', 'ppsgen'); ?></h3>
      <p class="ppsgenTemplateProOfferPopupText"><?php esc_html_e('The free templates cover every play. PRO adds more styles and specialist designs ready to use, so you don’t have to build them yourself, plus everything else PRO does for growing sites.', 'ppsgen'); ?></p>
      <ul class="ppsgenTemplateProOfferPopupList">
        <li><?php esc_html_e('Premium high-CVR templates for forms, popups, and emails', 'ppsgen'); ?></li>
        <li><?php esc_html_e('Advanced CRM fields: files, maps, taxonomies, WooCommerce products, attributes, categories, and tags', 'ppsgen'); ?></li>
        <li><?php esc_html_e('Run as many automations at once as you need, with deeper campaign controls', 'ppsgen'); ?></li>
        <li><?php esc_html_e('GeoLite IP recognition, internal API for custom integrations, and extra premium modules', 'ppsgen'); ?></li>
      </ul>
    </div>
    <div class="ppsgenTemplateProOfferPopupAside">
      <?php // The template that was clicked (filled in by backend.list.js): seeing what you get is the best argument ?>
      <figure class="ppsgenTemplateProOfferPopupPreview" hidden>
        <img src="" alt="" />
        <figcaption></figcaption>
      </figure>
      <div class="ppsgenTemplateProOfferPopupPrice">
        <span class="ppsgenTemplateProOfferPopupPriceLabel"><?php esc_html_e('Yearly licence · 1 site', 'ppsgen'); ?></span>
        <span class="ppsgenTemplateProOfferPopupPriceValue"><strong>$99</strong> / year</span>
        <span class="ppsgenTemplateProOfferPopupPriceMeta"><?php esc_html_e('renews at the same price · 30-day money-back guarantee', 'ppsgen'); ?></span>
      </div>
      <div class="ppsgenTemplateProOfferPopupActions">
        <a class="ppsgenTemplateProOfferPopupBtn ppsgenTemplateProOfferPopupBtnPrimary" href="#" data-action="activate">
          <span class="dashicons dashicons-superhero-alt"></span>
          <?php esc_html_e('Get PRO for $99 a year', 'ppsgen'); ?>
        </a>
        <a class="ppsgenTemplateProOfferPopupBtn ppsgenTemplateProOfferPopupBtnSecondary" href="#" data-action="dismiss">
          <span class="dashicons dashicons-dismiss"></span>
          <?php esc_html_e('Maybe Later', 'ppsgen'); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="ppsgenTemplateFilter">
  <span class="ppsgenFilterBtn active" data-tag="all"><?php esc_html_e('All', 'ppsgen'); ?></span>
  <?php
  $allTags = [];
  foreach ($templateList as $tpl) {
    $allTags = array_merge($allTags, $tpl['tags']);
  }
  // The feature filter is always offered for popups, even before any template has it
  if ($templateType === 'popup') {
    $allTags[] = 'With Forms';
  }
  $allTags = array_values(array_unique($allTags));
  // Broad filters first (FREE, PRO, First Steps, Holidays…), narrow ones (industries, technical) last
  $tagRank = array_flip($this->getModel()->getTemplateTagOrder());
  usort($allTags, function ($a, $b) use ($tagRank) {
    return ($tagRank[$a] ?? PHP_INT_MAX) <=> ($tagRank[$b] ?? PHP_INT_MAX) ?: strcmp($a, $b);
  });
  foreach ($allTags as $tag): ?>
  <span class="ppsgenFilterBtn" data-tag="<?php echo esc_attr($tag); ?>"><?php echo esc_html($tag); ?></span>
  <?php endforeach;
  ?>
</div>

<div class="ppsgenTemplateEmpty" style="display:none;"><?php esc_html_e('No templates here yet — they are on the way.', 'ppsgen'); ?></div>

<div class="ppsgenTemplateGrid">
  <?php foreach ($templateList as $template):

    $isPro = $template['pro'];
    $opacityClass = !$ls && $isPro ? 'ppsgenProInactive' : '';
    $previewUrl = PPSGEN_MODULES_URL . '/template/assets/img/' . $template['preview'];
    ?>
  <div class="ppsgenTemplateCard <?php echo esc_attr($opacityClass); ?>" data-tags="<?php echo esc_attr(implode(', ', $template['tags'])); ?>" data-code="<?php echo esc_attr($template['code']); ?>" data-is-pro="<?php echo !empty($isPro) ? '1' : '0'; ?>">
    <div class="ppsgenTemplateTitle"><?php echo esc_html($template['name']); ?></div>
    <div class="ppsgenTemplatePreview">
      <img src="<?php echo esc_url($previewUrl); ?>" alt="<?php echo esc_attr($template['name']); ?>" />
      <?php if ($isPro): ?>
      <div class="ppsgenProBadge"><?php esc_html_e('PRO', 'ppsgen'); ?></div>
      <?php endif; ?>
      <?php $features = isset($template['features']) && is_array($template['features']) ? $template['features'] : []; ?>
      <?php if ($features): ?>
      <div class="ppsgenTplFeatures">
        <?php if (in_array('form', $features, true)): ?>
        <span class="ppsgenTplFeature ppsgenTplFeatureForm" title="<?php esc_attr_e('With form', 'ppsgen'); ?>" aria-label="<?php esc_attr_e('With form', 'ppsgen'); ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2.5"/><path d="M8 8h8M8 12h8"/><rect x="8" y="15.5" width="5" height="2.5" rx="1" class="ppsgenTplFeatureFill"/></svg>
        </span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php
  endforeach; ?>
</div>
