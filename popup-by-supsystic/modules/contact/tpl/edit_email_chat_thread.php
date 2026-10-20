<?php
if (!defined('ABSPATH')) {
  exit();
}
$data = is_array($this->contactEmailThreadData ?? null) ? $this->contactEmailThreadData : [];
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$error = (string) ($data['error'] ?? '');
?>
<?php if ($error !== '') { ?>
  <div class="ppsgenContactCampaignsEmpty ppsgenFormSubmitsWrapperWarning"><?php echo esc_html($error); ?></div>
<?php } elseif (empty($items)) { ?>
  <div class="ppsgenContactCampaignsEmpty ppsgenFormSubmitsWrapperWarning">
    <?php esc_html_e('No emails were found for this contact in the connected mailbox yet.', 'ppsgen'); ?>
  </div>
<?php } else { ?>
  <div class="ppsgenContactEmailThreadTimeline">
    <?php foreach ($items as $item) { ?>
      <?php
      $direction = ($item['direction'] ?? '') === 'incoming' ? 'incoming' : 'outgoing';
      $seen = !empty($item['seen']);
      $mailboxKey = (string) ($item['mailbox_key'] ?? '');
      $isImapMailboxKey = $mailboxKey !== '' && ctype_xdigit($mailboxKey) && strlen($mailboxKey) % 2 === 0;
      $messageIdentifier = !empty($item['uid']) && $isImapMailboxKey
        ? (string) $item['uid']
        : (string) ($item['message_id'] ?? ($item['uid'] ?? ''));
      ?>
      <div class="ppsgenContactEmailThreadRow ppsgenContactEmailThreadRow<?php echo $direction === 'incoming' ? 'Incoming' : 'Outgoing'; ?>">
        <button
          type="button"
          class="ppsgenContactEmailThreadCard<?php echo $seen ? ' is-seen' : ' is-unseen'; ?> ppsgenContactEmailOpenMessageBtn"
          data-mailbox-key="<?php echo esc_attr($mailboxKey); ?>"
          data-message-id="<?php echo esc_attr($messageIdentifier); ?>">
          <div class="ppsgenContactEmailThreadCardTop">
            <span class="ppsgenContactEmailThreadDirection"><?php echo $direction === 'incoming' ? esc_html__('Client Reply', 'ppsgen') : esc_html__('Your Message', 'ppsgen'); ?></span>
            <span class="ppsgenContactEmailThreadState"><?php echo $seen ? esc_html__('Read', 'ppsgen') : esc_html__('Unread', 'ppsgen'); ?></span>
          </div>
          <div class="ppsgenContactEmailThreadSubject"><?php echo esc_html((string) ($item['subject'] ?? '')); ?></div>
          <div class="ppsgenContactEmailThreadMeta">
            <?php if (!empty($item['date'])) { ?>
              <span><?php echo esc_html((string) $item['date']); ?></span>
            <?php } ?>
            <?php if (!empty($item['from'])) { ?>
              <span><?php echo esc_html((string) $item['from']); ?></span>
            <?php } ?>
          </div>
        </button>
      </div>
    <?php } ?>
  </div>
<?php } ?>
