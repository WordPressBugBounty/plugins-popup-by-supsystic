<?php
if (!defined('ABSPATH')) {
  exit();
} ?>
<div class="ppsgenDashContentWrapper">
  <?php $proLicenseNotice = is_array($this->proLicenseNotice ?? null) ? $this->proLicenseNotice : []; ?>
  <?php if (!empty($proLicenseNotice['state'])) { ?>
    <div class="ppsgenProLicenseStateNotice ppsgenProLicenseStateNotice-<?php echo esc_attr($proLicenseNotice['state']); ?><?php echo !empty($proLicenseNotice['item_locked']) ? ' ppsgenProLicenseStateNotice-locked' : ''; ?>">
      <span class="ppsgenProLicenseStateNoticeIcon dashicons <?php echo !empty($proLicenseNotice['item_locked']) ? 'dashicons-lock' : 'dashicons-warning'; ?>"></span>
      <div class="ppsgenProLicenseStateNoticeText">
        <?php if (!empty($proLicenseNotice['item_locked'])) { ?>
          <strong><?php esc_html_e('View-only: renew your PRO license to edit this item.', 'ppsgen'); ?></strong>
          <span><?php esc_html_e('This item was built with PRO. It keeps working on your site exactly as before and its statistics stay available. While your license is expired you can only enable or disable it (Save stores the Enabled status only) or delete it.', 'ppsgen'); ?></span>
        <?php } elseif ($proLicenseNotice['state'] === 'expiring') { ?>
          <strong>
            <?php
            /* translators: 1: license end date, 2: days left */
            echo esc_html(sprintf(_n('Your PRO license ends on %1$s, in %2$d day.', 'Your PRO license ends on %1$s, in %2$d days.', $proLicenseNotice['days_left'], 'ppsgen'), $proLicenseNotice['date_end'], $proLicenseNotice['days_left']));
            ?>
          </strong>
          <span>
            <?php
            /* translators: %s: e.g. "4 popups, 2 forms and 3 automations" */
            echo esc_html(sprintf(__('Renew at the same price you joined at. If it lapses, everything you built keeps working, but %s become view-only, new items get Free features only, your emails carry the Supsystic footer again, and updates and support stop.', 'ppsgen'), $proLicenseNotice['items']));
            ?>
          </span>
        <?php } elseif ($proLicenseNotice['state'] === 'grace') { ?>
          <strong>
            <?php
            /* translators: 1: license expiration date, 2: days left in the grace period */
            echo esc_html(sprintf(_n('Your PRO license expired on %1$s. PRO stays fully available for %2$d more day.', 'Your PRO license expired on %1$s. PRO stays fully available for %2$d more days.', $proLicenseNotice['grace_days_left'], 'ppsgen'), $proLicenseNotice['date_end'], $proLicenseNotice['grace_days_left']));
            ?>
          </strong>
          <span>
            <?php
            /* translators: %s: date when PRO editing will be frozen */
            echo esc_html(sprintf(__('Renew before %s. After that everything you built keeps working, but PRO popups, forms and email campaigns become view-only, new items get Free features only, and updates and support stop.', 'ppsgen'), $proLicenseNotice['grace_end']));
            ?>
          </span>
        <?php } else { ?>
          <strong>
            <?php
            /* translators: %s: license expiration date */
            echo esc_html(sprintf(__('Your PRO license expired on %s.', 'ppsgen'), $proLicenseNotice['date_end']));
            ?>
          </strong>
          <span><?php esc_html_e('Everything you built keeps working, including tracking, statistics and email sending. PRO popups, forms and email campaigns are view-only, new items get Free features only, and updates and support are paused until you renew.', 'ppsgen'); ?></span>
        <?php } ?>
      </div>
      <div class="ppsgenProLicenseStateNoticeActions">
        <a class="ppsgenProLicenseStateNoticeBtn ppsgenProLicenseStateNoticeBtnPrimary" href="<?php echo esc_url($proLicenseNotice['renew_url']); ?>" target="_blank" rel="noopener noreferrer">
          <span class="dashicons dashicons-update"></span>
          <?php echo !empty($proLicenseNotice['item_locked']) ? esc_html__('Renew to edit', 'ppsgen') : esc_html__('Renew license', 'ppsgen'); ?>
        </a>
        <a class="ppsgenProLicenseStateNoticeBtn" href="<?php echo esc_url($proLicenseNotice['license_url']); ?>">
          <span class="dashicons dashicons-admin-network"></span>
          <?php esc_html_e('License', 'ppsgen'); ?>
        </a>
        <?php if ($proLicenseNotice['state'] === 'expiring') { ?>
          <a class="ppsgenProLicenseStateNoticeBtn ppsgenProLicenseExpiryDismiss" href="#" data-milestone="<?php echo esc_attr($proLicenseNotice['milestone']); ?>"><?php esc_html_e('Remind me later', 'ppsgen'); ?></a>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
  <?php $feedbackNotice = is_array($this->feedbackNotice ?? null) ? $this->feedbackNotice : []; ?>
  <?php if (!empty($feedbackNotice['show'])) { ?>
    <div class="ppsgenFeedbackNotice" data-review-url="<?php echo esc_url($feedbackNotice['review_url'] ?? ''); ?>">
      <a class="ppsgenFeedbackNoticeClose" href="#" aria-label="<?php esc_attr_e('Close notice', 'ppsgen'); ?>">
        <span class="dashicons dashicons-no-alt"></span>
      </a>
      <div class="ppsgenFeedbackNoticeContent">
        <div class="ppsgenFeedbackNoticeEyebrow"><?php echo esc_html($feedbackNotice['first_result'] ?? ''); ?> <span class="dashicons dashicons-heart"></span></div>
        <h3 class="ppsgenFeedbackNoticeTitle"><?php esc_html_e('Nice work! Would you rate CES?', 'ppsgen'); ?></h3>
        <p class="ppsgenFeedbackNoticeText">
          <?php esc_html_e('A short review on WordPress.org helps other site owners find CES, and tells us what to improve next.', 'ppsgen'); ?>
          <span class="dashicons dashicons-thumbs-up"></span>
          <span class="dashicons dashicons-smiley"></span>
        </p>
        <div class="ppsgenFeedbackNoticeActions">
          <a class="ppsgenFeedbackNoticeBtn ppsgenFeedbackNoticeBtnPrimary ppsgenFeedbackNoticeActionBtn" href="#" data-action="submit">
            <span class="dashicons dashicons-star-filled"></span>
            <?php esc_html_e('Leave a Review', 'ppsgen'); ?>
          </a>
          <a class="ppsgenFeedbackNoticeBtn ppsgenFeedbackNoticeActionBtn" href="#" data-action="already">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e('Already Reviewed', 'ppsgen'); ?>
          </a>
          <a class="ppsgenFeedbackNoticeBtn ppsgenFeedbackNoticeActionBtn" href="#" data-action="remind">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e('Remind Me in a Week', 'ppsgen'); ?>
          </a>
        </div>
      </div>
    </div>
  <?php } ?>
  <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  echo $this->frame->core('helper')->wpKses($this->moduleContent); ?>
</div>
