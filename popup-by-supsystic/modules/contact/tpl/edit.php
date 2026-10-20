<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$objId = $options['obj_id'];
$contactHeaderMeta = is_array($this->contactHeaderMeta ?? null) ? $this->contactHeaderMeta : [];
$contactHeaderStats = is_array($contactHeaderMeta['stats'] ?? null) ? $contactHeaderMeta['stats'] : [];
$incomingAttentionTotal = (int) ($contactHeaderStats['incoming_email_unhandled_total'] ?? 0);
$incomingAttentionTitle = $incomingAttentionTotal > 0
  ? sprintf(
    _n('%d new incoming email needs attention', '%d new incoming emails need attention', $incomingAttentionTotal, 'ppsgen'),
    $incomingAttentionTotal
  )
  : __('No new incoming emails need attention', 'ppsgen');
$leadClass = 'ppsgenLeadInactive';
if (($contactHeaderStats['label'] ?? '') === __('Hot', 'ppsgen')) {
  $leadClass = 'ppsgenLeadHot';
} elseif (in_array(($contactHeaderStats['label'] ?? ''), [__('Medium', 'ppsgen'), __('Warm', 'ppsgen')], true)) {
  $leadClass = 'ppsgenLeadWarm';
} elseif (($contactHeaderStats['label'] ?? '') === __('Cold', 'ppsgen')) {
  $leadClass = 'ppsgenLeadCold';
} elseif (($contactHeaderStats['label'] ?? '') === __('Unsubscribed', 'ppsgen')) {
  $leadClass = 'ppsgenLeadUnsubscribed';
}
$prefix = 'data[contact]';
$component->setNamePrefix($prefix);
?>
<div class="ppsgenModulePageTitleWrapper">
  <div class="ppsgenContactEditHeaderMain">
    <div class="ppsgenContactEditHeaderTop">
      <h2 class="ppsgenModulePageTitle">
        <?php if ($objId) { ?>
        <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Edit Contact', 'ppsgen'); ?>
        <?php } else { ?>
        <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Add New Contact', 'ppsgen'); ?>
        <?php } ?>
      </h2>
      <button class="ppsgenSaveBtn ppsgenObjEditSaveBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Save', 'ppsgen'); ?></button>
    </div>
    <?php if ($objId) { ?>
      <div class="ppsgenContactEditHeaderMeta">
        <?php if (!empty($contactHeaderMeta['email'])) { ?>
          <div class="ppsgenContactEditHeaderEmailWrap">
            <div class="ppsgenContactEditHeaderEmail"><?php echo esc_html($contactHeaderMeta['email']); ?></div>
            <span class="ppsgenContactEditHeaderVerified<?php echo !empty($contactHeaderMeta['verified']) ? ' is-verified' : ' is-unverified'; ?>" title="<?php echo !empty($contactHeaderMeta['verified']) ? esc_attr__('Contact is verified', 'ppsgen') : esc_attr__('Contact is not verified', 'ppsgen'); ?>">
              <span class="dashicons <?php echo !empty($contactHeaderMeta['verified']) ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
            </span>
          </div>
        <?php } else { ?>
          <span class="ppsgenContactEditHeaderVerified<?php echo !empty($contactHeaderMeta['verified']) ? ' is-verified' : ' is-unverified'; ?>" title="<?php echo !empty($contactHeaderMeta['verified']) ? esc_attr__('Contact is verified', 'ppsgen') : esc_attr__('Contact is not verified', 'ppsgen'); ?>">
            <span class="dashicons <?php echo !empty($contactHeaderMeta['verified']) ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
          </span>
        <?php } ?>
        <?php if (!empty($contactHeaderMeta['tags']) && is_array($contactHeaderMeta['tags'])) { ?>
          <div class="ppsgenContactEditHeaderTags">
            <?php foreach ($contactHeaderMeta['tags'] as $tagTitle) { ?>
              <span class="ppsgenContactEditHeaderTag"><?php echo esc_html($tagTitle); ?></span>
            <?php } ?>
          </div>
        <?php } ?>
        <div class="ppsgenContactEditHeaderStats">
          <span class="ppsgenContactEngagementPill ppsgenContactEngagementScore <?php echo esc_attr($leadClass); ?>"><?php echo esc_html((int) ($contactHeaderStats['percent'] ?? 0)); ?>% <?php echo esc_html($contactHeaderStats['label'] ?? __('Inactive', 'ppsgen')); ?></span>
          <?php if (!empty($contactHeaderStats['conversion_paid_total'])) { ?>
            <span class="ppsgenContactEngagementPill ppsgenContactConversionPill">
              <span class="dashicons dashicons-chart-line" aria-hidden="true"></span>
              <?php esc_html_e('Paid', 'ppsgen'); ?>: <?php echo esc_html((int) ($contactHeaderStats['conversion_paid_total'] ?? 0)); ?>
              <?php if (!empty($contactHeaderStats['conversion_paid_value_label']) && $contactHeaderStats['conversion_paid_value_label'] !== '0') { ?>
                / <?php echo esc_html((string) $contactHeaderStats['conversion_paid_value_label']); ?>
              <?php } ?>
            </span>
          <?php } ?>
          <span class="ppsgenContactEditHeaderActivity">
            <span class="ppsgenContactIncomingIndicator<?php echo !empty($contactHeaderStats['has_unhandled_incoming_email']) ? ' is-active' : ''; ?>" title="<?php echo esc_attr__('New incoming email detected', 'ppsgen'); ?>"></span>
            <span class="ppsgenContactEditHeaderActivityText"><?php echo esc_html($contactHeaderStats['last_activity_text'] ?? __('No activity yet', 'ppsgen')); ?></span>
          </span>
        </div>
      </div>
    <?php } ?>
  </div>
</div>
<form method="POST" id="ppsgenEditObjForm">
  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" id="action" name="action" value="editContact">
  <?php if ($objId) { ?>
  <input type="hidden" name="obj_id" value="<?php ppsgen_esc_attr($objId); ?>">
  <?php } ?>
  <div class="ppsgenObjEditWrapper">
    <div class="ppsgenObjEditTabsButtons">
      <?php foreach ($this->tabs as $tabKey => $tabData) {
        if ($tabKey === 'form_submits' && !$this->getFrame()->useModule('form')) {
          continue;
        } ?>
      <div class="ppsgenObjEditTabButton<?php echo $tabKey === 'contact_field' ? ' ppsgenObjEditTabButtonActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
        <span class="ppsgenContactEditTabButtonInner">
          <span class="ppsgenContactEditTabButtonLabel"><?php echo esc_html($tabData['title']); ?></span>
          <?php if ($tabKey === 'email_chat') { ?>
            <span
              class="ppsgenContactIncomingIndicatorBadge ppsgenContactEditTabIncomingBadge<?php echo $incomingAttentionTotal > 0 ? ' is-active' : ''; ?>"
              title="<?php echo esc_attr($incomingAttentionTitle); ?>"
              data-singular-title="<?php echo esc_attr__('new incoming email needs attention', 'ppsgen'); ?>"
              data-plural-title="<?php echo esc_attr__('new incoming emails need attention', 'ppsgen'); ?>"
              data-empty-title="<?php echo esc_attr__('No new incoming emails need attention', 'ppsgen'); ?>"
            >
              <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
            </span>
          <?php } ?>
        </span>
      </div>
      <?php
      } ?>
    </div>
    <div class="ppsgenObjEditTabs">
      <?php foreach ($this->tabs as $tabKey => $tabData) {
        if ($tabKey === 'form_submits' && !$this->getFrame()->useModule('form')) {
          continue;
        } ?>
      <div class="ppsgenObjEditTab<?php echo $tabKey === 'contact_field' ? ' ppsgenObjEditTabActive' : ''; ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo $this->getFrame()->core('helper')->wpKses($tabData['content']); ?>
      </div>
      <?php
      } ?>
    </div>
  </div>
</form>
<?php $component->setNamePrefix('');
?>
