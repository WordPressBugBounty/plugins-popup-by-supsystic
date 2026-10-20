<?php
if (!defined('ABSPATH')) {
  exit();
}
$hash = $this->hash;
?>

  <div class="ppsgenEcaUnsubscribeOverlay">
    <div class="ppsgenEcaUnsubscribeWrapper">
      <h2><?php esc_html_e('You are about to unsubscribe', 'ppsgen'); ?></h2>
      <p><?php esc_html_e('Please confirm that you want to unsubscribe from this mailing list.', 'ppsgen'); ?></p>
      <form method="POST">
        <input type="hidden" name="plugin" value="ppsgen">
        <input type="hidden" name="mod" value="eca">
        <input type="hidden" name="action" value="unsubscribeContactAction">
        <?php wp_nonce_field('ppsgenAjaxNonceUnsubscribe', 'nonce_code'); ?>
        <input type="hidden" name="data[unsubscribe]" value="1">
        <input type="hidden" name="data[hash]" value="<?php echo esc_attr($hash); ?>">
        <button class="ppsgenEcaSubmitUnsubscribe" type="submit"><?php esc_html_e('Unsubscribe', 'ppsgen'); ?></button>
      </form>
    </div>
  </div>