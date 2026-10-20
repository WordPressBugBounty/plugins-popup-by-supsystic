<?php
if (!defined('ABSPATH')) {
  exit();
}
$play = is_array($this->play ?? null) ? $this->play : [];
$parts = is_array($this->parts ?? null) ? $this->parts : [];
$answers = $play['answers'] ?? [];
$emailStatus = is_array($this->emailStatus ?? null) ? $this->emailStatus : [];
$automationConflict = (array) ($this->automationConflict ?? []);
$notes = (array) ($play['notes'] ?? []);
$adminBase = admin_url('admin.php?page=ppsgen');
$isLive = in_array($play['status'] ?? '', ['live', 'paused'], true);
$playDefinition = $this->getFrame()->getModule('dashboard')->getPlays()->getDefinition($play['type'] ?? '');
$asksOwner = in_array('owner_email', array_column($playDefinition['questions'] ?? [], 'key'), true);
$checks = [
  [
    'ok' => ($emailStatus['state'] ?? '') === 'ok',
    'title' => ($emailStatus['state'] ?? '') === 'ok' ? __('Email sending is set up', 'ppsgen') : __('Emails go out with your website\'s own mail', 'ppsgen'),
    'text' => ($emailStatus['state'] ?? '') === 'ok' ? (string) ($emailStatus['label'] ?? '') : __('They often land in spam. Everything is still saved in Contacts.', 'ppsgen'),
    'url' => ($emailStatus['state'] ?? '') === 'ok' ? '' : (string) ($emailStatus['url'] ?? ''),
    'link' => __('Set up email sending →', 'ppsgen'),
  ],
  [
    'ok' => true,
    'title' => __('No sample text left', 'ppsgen'),
    'text' => __('Every heading and email uses your answers.', 'ppsgen'),
  ],
];
if ($asksOwner) {
  $checks[] = [
    'ok' => !empty($answers['owner_email']),
    'title' => !empty($answers['owner_email']) ? __('You\'ll be told about every enquiry', 'ppsgen') : __('Nobody gets an enquiry alert', 'ppsgen'),
    'text' => !empty($answers['owner_email']) ? (string) $answers['owner_email'] : __('Enquiries are only saved in Contacts. Add an alert email in the form options.', 'ppsgen'),
  ];
}
?>
<div class="ppsgenHome ppsgenPlayReview">
  <a class="ppsgenBackLink" href="<?php echo esc_url(add_query_arg('tab', 'plays', $adminBase)); ?>">&larr; <?php esc_html_e('All plays', 'ppsgen'); ?></a>
  <div class="ppsgenPageHead">
    <div>
      <h1 class="ppsgenPageTitle"><?php esc_html_e('Review what we\'ve built', 'ppsgen'); ?></h1>
      <p class="ppsgenPageSub"><?php esc_html_e('Nothing is live yet. These are normal CES items — you\'ll find them under Popups, Forms and Campaigns. Edit opens the normal editor.', 'ppsgen'); ?></p>
    </div>
    <ol class="ppsgenPlaySteps">
      <li class="ppsgenPlayStepDone"><span>&#10003;</span><?php esc_html_e('Answer', 'ppsgen'); ?></li>
      <li class="ppsgenPlayStepOn"><span>2</span><?php esc_html_e('Review', 'ppsgen'); ?></li>
      <li><span>3</span><?php esc_html_e('Live', 'ppsgen'); ?></li>
    </ol>
  </div>

  <div class="ppsgenHomeGrid">
    <section class="ppsgenCard">
      <h2><?php echo esc_html($play['name'] ?? ''); ?></h2>
      <?php foreach ($parts as $part) { ?>
        <div class="ppsgenPlayPart">
          <span class="ppsgenPlayPartType"><?php echo esc_html($part['type']); ?></span>
          <div class="ppsgenPlayPartBody">
            <div class="ppsgenPlayPartName"><?php echo esc_html($part['name']); ?></div>
            <div class="ppsgenMuted"><?php echo esc_html($part['text']); ?></div>
          </div>
          <?php if (!empty($part['url'])) { ?><a class="ppsgenBtnSecondary" href="<?php echo esc_url($part['url']); ?>"><?php esc_html_e('Edit', 'ppsgen'); ?></a><?php } ?>
        </div>
      <?php } ?>
      <?php if (!empty($automationConflict)) { ?>
        <div class="ppsgenPlayPart ppsgenPlayPartGateway">
          <span class="ppsgenPlayPartType"><?php esc_html_e('Automation', 'ppsgen'); ?></span>
          <div class="ppsgenPlayPartBody">
            <div class="ppsgenPlayPartName"><?php echo esc_html(sprintf(__('Going live switches off “%s”', 'ppsgen'), $automationConflict['name'])); ?></div>
            <div class="ppsgenMuted"><?php echo esc_html(ppsgen_automation_limit_text('review_note')); ?></div>
          </div>
          <a class="ppsgenBtnSecondary" href="<?php echo esc_url(ppsgen_automation_limit_url()); ?>"><?php echo esc_html(ppsgen_automation_limit_text('cta')); ?></a>
        </div>
      <?php } ?>
    </section>

    <div class="ppsgenHomeSide">
      <section class="ppsgenCard">
        <h2><?php esc_html_e('Before you go live', 'ppsgen'); ?></h2>
        <?php foreach ($checks as $check) { ?>
          <div class="ppsgenAttention">
            <span class="ppsgenAttentionDot<?php echo $check['ok'] ? ' ppsgenAttentionDotOk' : ''; ?>"></span>
            <div>
              <div class="ppsgenAttentionTitle"><?php echo esc_html($check['title']); ?></div>
              <div class="ppsgenMuted"><?php echo esc_html($check['text']); ?></div>
              <?php if (!empty($check['url'])) { ?><a href="<?php echo esc_url($check['url']); ?>"><?php echo esc_html($check['link']); ?></a><?php } ?>
            </div>
          </div>
        <?php } ?>
        <?php if (!empty($play['parts']['form_id'])) { ?>
          <button type="button" class="ppsgenBtnSecondary ppsgenPlayTestEnquiry" data-play-id="<?php echo esc_attr($play['id']); ?>" data-busy="<?php esc_attr_e('Sending…', 'ppsgen'); ?>"><?php esc_html_e('Send me a test enquiry', 'ppsgen'); ?></button>
          <p class="ppsgenMuted"><?php esc_html_e('You get the same emails a real enquiry sends, marked [Test]. Nothing is saved.', 'ppsgen'); ?></p>
        <?php } ?>
      </section>
      <?php if (empty($this->isPro)) { ?>
        <section class="ppsgenCard">
          <div class="ppsgenStatLabel"><?php esc_html_e('Your Free plan', 'ppsgen'); ?></div>
          <div class="ppsgenMeterRow"><span><?php esc_html_e('Automations', 'ppsgen'); ?></span><span class="ppsgenMuted"><?php echo !empty($play['parts']['chain_id']) ? esc_html__('this play uses your 1 Free automation', 'ppsgen') : esc_html__('none used by this play', 'ppsgen'); ?></span></div>
          <div class="ppsgenMeter"><div style="width: <?php echo !empty($play['parts']['chain_id']) ? '100' : '0'; ?>%;"></div></div>
          <?php $reviewLimit = function_exists('ppsgen_get_contact_limit_status') ? ppsgen_get_contact_limit_status() : []; ?>
          <?php if (!empty($reviewLimit['applies'])) { ?>
            <div class="ppsgenMeterRow"><span><?php esc_html_e('Reachable contacts', 'ppsgen'); ?></span><span class="ppsgenMuted"><?php echo esc_html(sprintf(__('%1$s of %2$s', 'ppsgen'), number_format_i18n($reviewLimit['reachable']), number_format_i18n($reviewLimit['limit']))); ?></span></div>
            <div class="ppsgenMeter"><div style="width: <?php echo (int) $reviewLimit['percent']; ?>%;"></div></div>
          <?php } ?>
        </section>
      <?php } ?>
    </div>
  </div>

  <div class="ppsgenPlayFooter" data-play-id="<?php echo esc_attr($play['id']); ?>">
    <a class="ppsgenBtnSecondary" href="<?php echo esc_url(add_query_arg('tab', 'plays', $adminBase)); ?>"><?php esc_html_e('Go live later', 'ppsgen'); ?></a>
    <?php if ($isLive) { ?>
      <a class="ppsgenBtnPrimary" href="<?php echo esc_url(add_query_arg(['tab' => 'play_summary', 'id' => $play['id']], $adminBase)); ?>"><?php esc_html_e('Open results', 'ppsgen'); ?></a>
    <?php } else { ?>
      <button type="button" class="ppsgenBtnPrimary ppsgenPlaySetLive" data-live="1" data-busy="<?php esc_attr_e('Going live…', 'ppsgen'); ?>"><?php esc_html_e('Go live', 'ppsgen'); ?></button>
    <?php } ?>
  </div>
</div>
