<?php
if (!defined('ABSPATH')) {
  exit();
}
?>
<div class="ppsgen-cfi-banner">
  <span class="ppsgen-cfi-banner-icon dashicons dashicons-migrate" aria-hidden="true"></span>
  <span class="ppsgen-cfi-banner-text">
    <b><?php echo esc_html(sprintf(_n('%d form from Contact Form by Supsystic can move here', '%d forms from Contact Form by Supsystic can move here', $this->count, 'ppsgen'), $this->count)); ?></b>
    <span><?php esc_html_e('Fields, emails and submissions, in one click. Contact Form stays untouched.', 'ppsgen'); ?></span>
  </span>
  <a class="ppsgen-cfi-banner-btn" href="<?php echo esc_url($this->url); ?>"><?php esc_html_e('Move my forms', 'ppsgen'); ?></a>
</div>
