<?php
if (!defined('ABSPATH')) {
  exit();
}
$play = is_array($this->play ?? null) ? $this->play : [];
$automationConflict = (array) ($this->automationConflict ?? []);
$parts = is_array($this->parts ?? null) ? $this->parts : [];
$stats = is_array($this->stats ?? null) ? $this->stats : [];
$people = is_array($this->people ?? null) ? $this->people : [];
$definition = is_array($this->definition ?? null) ? $this->definition : [];
$adminBase = admin_url('admin.php?page=ppsgen');
$isLive = ($play['status'] ?? '') === 'live';
$formId = (int) ($play['parts']['form_id'] ?? 0);
$newsletterId = (int) ($play['parts']['newsletter_id'] ?? 0);
$hasSwitch = !empty($play['parts']['popup_id']) || !empty($play['parts']['chain_id']);
$stop = $definition['stop'] ?? [];
$hasFormPeople = $formId > 0;
?>
<div class="ppsgenHome ppsgenPlaySummary" data-play-id="<?php echo esc_attr($play['id']); ?>">
  <?php if ($isLive && $hasSwitch && (current_time('timestamp') - (int) ($play['live_at'] ?? 0)) < DAY_IN_SECONDS) { ?>
    <div class="ppsgenPlayLiveBanner">
      <span class="ppsgenAttentionDot ppsgenAttentionDotOk"></span>
      <div>
        <strong><?php esc_html_e('It\'s live. New people appear below as they come in.', 'ppsgen'); ?></strong>
        <?php if ($formId > 0) { ?>
          <div class="ppsgenMuted"><?php esc_html_e('Tip: also add the form to any page with', 'ppsgen'); ?> <code>[supsystic-ces-form id="<?php echo esc_html($formId); ?>"]</code></div>
        <?php } ?>
      </div>
      <?php if (!empty($play['parts']['popup_id'])) { ?><a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View on your site ↗', 'ppsgen'); ?></a><?php } ?>
    </div>
  <?php } ?>
  <?php if ($newsletterId > 0) { ?>
    <div class="ppsgenPlayLiveBanner">
      <span class="ppsgenAttentionDot"></span>
      <div>
        <strong><?php esc_html_e('Your newsletter is ready to send.', 'ppsgen'); ?></strong>
        <div class="ppsgenMuted"><?php esc_html_e('Open it to check the email, send yourself a test, then send it now or schedule it.', 'ppsgen'); ?></div>
      </div>
      <a class="ppsgenBtnPrimary" href="<?php echo esc_url(add_query_arg(['tab' => 'eca_chain_edit', 'id' => $newsletterId], $adminBase)); ?>"><?php esc_html_e('Open newsletter', 'ppsgen'); ?></a>
    </div>
  <?php } ?>

  <div class="ppsgenPageHead">
    <div>
      <div class="ppsgenMuted"><a href="<?php echo esc_url(add_query_arg('tab', 'plays', $adminBase)); ?>"><?php esc_html_e('Plays', 'ppsgen'); ?></a> / <?php echo esc_html($definition['title'] ?? ''); ?></div>
      <h1 class="ppsgenPageTitle"><?php echo esc_html($play['name'] ?? ''); ?></h1>
    </div>
    <?php if ($hasSwitch) { ?>
      <div class="ppsgenPageActions">
        <label class="ppsgenEditorLive<?php echo $isLive ? ' ppsgenEditorLiveOn' : ''; ?> ppsgenPlayLiveSwitch" title="<?php echo esc_attr(empty($automationConflict) ? __('Switches the popup and the automation on or off. Nothing is deleted.', 'ppsgen') : sprintf(ppsgen_automation_limit_text('switch_title'), $automationConflict['name'])); ?>">
          <input type="checkbox" class="ppsgenEditorLiveInput"<?php echo $isLive ? ' checked' : ''; ?>>
          <span class="ppsgenEditorLiveTrack"></span>
          <span class="ppsgenEditorLiveText"><?php echo $isLive ? esc_html__('Live', 'ppsgen') : esc_html__('Paused', 'ppsgen'); ?></span>
        </label>
      </div>
    <?php } ?>
  </div>

  <?php if ($isLive) {
    include __DIR__ . '/usage_ask.php';
  } ?>

  <?php if (!empty($stats['cards'])) { ?>
    <div class="ppsgenStatCards">
      <?php foreach ($stats['cards'] as $card) { ?>
        <div class="ppsgenCard ppsgenStatCard">
          <div class="ppsgenStatLabel"><?php echo esc_html($card['label']); ?></div>
          <div class="ppsgenStatValue"><?php echo esc_html($card['value']); ?></div>
          <div class="ppsgenStatSub"><?php echo esc_html($card['sub']); ?></div>
        </div>
      <?php } ?>
    </div>
  <?php } ?>

  <div class="ppsgenHomeGrid">
    <section class="ppsgenCard">
      <div class="ppsgenCardHead">
        <h2><?php echo esc_html($definition['people'] ?? __('People', 'ppsgen')); ?></h2>
        <a href="<?php echo esc_url(add_query_arg('tab', 'contact_list', $adminBase)); ?>"><?php esc_html_e('Open in Contacts', 'ppsgen'); ?></a>
      </div>
      <?php if ($newsletterId > 0 && empty($people)) { ?>
        <p class="ppsgenEmpty"><?php esc_html_e('Recipients, opens and clicks show on the newsletter once it is sent.', 'ppsgen'); ?></p>
      <?php } elseif (empty($people)) { ?>
        <p class="ppsgenEmpty">
          <?php echo $hasFormPeople
            ? esc_html__('Nobody yet. People appear here as soon as someone sends the form.', 'ppsgen')
            : esc_html__('Nobody yet. People appear here when a contact gets the tag that starts it.', 'ppsgen'); ?>
        </p>
      <?php } else { ?>
        <table class="ppsgenHomeTable">
          <thead>
            <tr>
              <th><?php esc_html_e('Name', 'ppsgen'); ?></th>
              <?php if ($hasFormPeople) { ?>
                <th><?php esc_html_e('Phone', 'ppsgen'); ?></th>
                <th><?php esc_html_e('Message', 'ppsgen'); ?></th>
              <?php } ?>
              <th><?php esc_html_e('When', 'ppsgen'); ?></th>
              <th><?php echo !empty($play['parts']['chain_id']) ? esc_html__('Emails', 'ppsgen') : ''; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($people as $row) { ?>
              <tr>
                <td>
                  <?php if ($row['contact_id'] > 0) { ?><a href="<?php echo esc_url(add_query_arg(['tab' => 'contact_edit', 'id' => $row['contact_id']], $adminBase)); ?>"><?php } ?>
                  <?php echo esc_html($row['name'] !== '' ? $row['name'] : $row['email']); ?>
                  <?php if ($row['contact_id'] > 0) { ?></a><?php } ?>
                </td>
                <?php if ($hasFormPeople) { ?>
                  <td class="ppsgenMuted"><?php echo esc_html($row['phone']); ?></td>
                  <td class="ppsgenMuted"><?php echo esc_html($row['message']); ?></td>
                <?php } ?>
                <td class="ppsgenMuted"><?php echo esc_html($row['when']); ?></td>
                <td>
                  <?php if ($row['status'] === 'stopped') { ?>
                    <span class="ppsgenChip"><?php echo esc_html($stop['done'] ?? ''); ?></span>
                  <?php } elseif ($row['status'] === 'done') { ?>
                    <span class="ppsgenChip"><?php esc_html_e('Finished', 'ppsgen'); ?></span>
                  <?php } else { ?>
                    <?php if ($row['status'] === 'due' && $row['due'] !== '') { ?><span class="ppsgenChip"><?php echo esc_html(sprintf(__('Next %s', 'ppsgen'), $row['due'])); ?></span><?php } ?>
                    <?php if (!empty($stop) && $row['contact_id'] > 0) { ?>
                      <a href="#" class="ppsgenPlayMarkStopped" data-contact-id="<?php echo esc_attr($row['contact_id']); ?>"><?php echo esc_html($stop['action']); ?></a>
                    <?php } ?>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
    </section>

    <div class="ppsgenHomeSide">
      <section class="ppsgenCard">
        <h2><?php esc_html_e('What this play is made of', 'ppsgen'); ?></h2>
        <p class="ppsgenMuted"><?php esc_html_e('Normal CES items — edit any of them directly.', 'ppsgen'); ?></p>
        <?php foreach ($parts as $part) { ?>
          <?php if (!empty($part['url'])) { ?>
            <a class="ppsgenPlayPartLink" href="<?php echo esc_url($part['url']); ?>"><span class="ppsgenPlayPartType"><?php echo esc_html($part['type']); ?></span><span class="ppsgenPlayPartName"><?php echo esc_html($part['name']); ?></span><span><?php esc_html_e('Edit', 'ppsgen'); ?></span></a>
          <?php } else { ?>
            <div class="ppsgenPlayPartLink"><span class="ppsgenPlayPartType"><?php echo esc_html($part['type']); ?></span><span class="ppsgenPlayPartName"><?php echo esc_html($part['name']); ?></span></div>
          <?php } ?>
        <?php } ?>
      </section>
      <?php if (empty($this->isPro) && !empty($play['parts']['chain_id'])) { ?>
        <section class="ppsgenCard">
          <div class="ppsgenCardHead"><h2><?php esc_html_e('Make it work harder', 'ppsgen'); ?></h2><span class="ppsgenProChip">PRO</span></div>
          <p class="ppsgenMuted"><?php esc_html_e('Add more steps, and run more than one automation at a time.', 'ppsgen'); ?></p>
          <a href="<?php echo esc_url(add_query_arg('tab', 'settings_pro', $adminBase)); ?>"><?php esc_html_e('See what PRO adds →', 'ppsgen'); ?></a>
        </section>
      <?php } ?>
    </div>
  </div>
</div>
