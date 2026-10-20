<?php
if (!defined('ABSPATH')) {
  exit();
}
$play = is_array($this->play ?? null) ? $this->play : [];
$answers = is_array($this->answers ?? null) ? $this->answers : [];
$definition = is_array($this->definition ?? null) ? $this->definition : [];
$emailStatus = is_array($this->emailStatus ?? null) ? $this->emailStatus : [];
$existingTags = is_array($this->existingTags ?? null) ? $this->existingTags : [];
$isPro = !empty($this->isPro);
$hasAutomation = !empty($this->hasAutomation);
$isBuilt = !empty($play) && ($play['status'] ?? 'draft') !== 'draft';
$adminBase = admin_url('admin.php?page=ppsgen');
$plays = $this->getFrame()->getModule('dashboard')->getPlays();
$fill = function ($template) use ($plays, $answers) {
  // Previews show what the recipient sees: placeholders become a sample name
  $text = $plays->fill((string) $template, $answers);
  $text = preg_replace('/\{\{\s*contact\.first_name\|([^}]*)\}\}/', '$1', $text);
  return str_replace('[first_name]', 'Alex', $text);
};
$triggers = [
  'delay' => __('After 8 seconds', 'ppsgen'),
  'scroll' => __('Halfway down the page', 'ppsgen'),
  'exit' => __('On exit', 'ppsgen'),
  'none' => __('No popup', 'ppsgen'),
];
$fieldLabels = ['name' => __('Name', 'ppsgen'), 'phone' => __('Phone', 'ppsgen'), 'message' => __('"How can we help?"', 'ppsgen')];
$preview = $definition['preview'] ?? [];
$questionCount = count($definition['questions'] ?? []);
$automationNote = !$isPro && (!empty($definition['needs_automation']) || in_array('days', array_column($definition['questions'] ?? [], 'type'), true));
?>
<div class="ppsgenHome ppsgenPlaySetup">
  <a class="ppsgenBackLink" href="<?php echo esc_url(add_query_arg('tab', 'plays', $adminBase)); ?>">&larr; <?php esc_html_e('All plays', 'ppsgen'); ?></a>
  <div class="ppsgenPageHead">
    <div>
      <h1 class="ppsgenPageTitle"><?php echo esc_html($definition['title'] ?? ''); ?></h1>
      <p class="ppsgenPageSub"><?php echo esc_html(sprintf(_n('%d quick question. You can change everything later.', '%d quick questions. You can change everything later.', $questionCount, 'ppsgen'), $questionCount)); ?></p>
    </div>
    <ol class="ppsgenPlaySteps">
      <li class="ppsgenPlayStepOn"><span>1</span><?php esc_html_e('Answer', 'ppsgen'); ?></li>
      <li><span>2</span><?php esc_html_e('Review', 'ppsgen'); ?></li>
      <li><span>3</span><?php esc_html_e('Live', 'ppsgen'); ?></li>
    </ol>
  </div>

  <?php if ($isBuilt) { ?>
    <section class="ppsgenCard">
      <p><?php esc_html_e('This play is already built. Its parts are normal CES items now, so change them in their editors.', 'ppsgen'); ?></p>
      <a class="ppsgenBtnPrimary" href="<?php echo esc_url(add_query_arg(['tab' => ($play['status'] === 'built' ? 'play_review' : 'play_summary'), 'id' => $play['id']], $adminBase)); ?>"><?php esc_html_e('Open the play', 'ppsgen'); ?></a>
    </section>
  <?php } elseif (empty($this->available)) { ?>
    <section class="ppsgenCard">
      <p><?php esc_html_e('This play needs more features switched on.', 'ppsgen'); ?></p>
      <a class="ppsgenBtnPrimary" href="<?php echo esc_url(add_query_arg('tab', 'dashboard_modules', $adminBase)); ?>"><?php esc_html_e('Settings › Features', 'ppsgen'); ?></a>
    </section>
  <?php } else { ?>
  <form class="ppsgenPlayForm" data-type="<?php echo esc_attr($this->type); ?>" data-play-id="<?php echo esc_attr($play['id'] ?? ''); ?>">
    <?php if (!empty($existingTags)) { ?>
      <datalist id="ppsgenPlayTags"><?php foreach ($existingTags as $tag) { ?><option value="<?php echo esc_attr($tag); ?>"></option><?php } ?></datalist>
    <?php } ?>
    <div class="ppsgenPlayLayout">
      <section class="ppsgenCard ppsgenPlayQuestions">
        <?php if ($automationNote) { ?>
          <p class="ppsgenPlayNote"><span class="ppsgenChip"><?php esc_html_e('Uses your 1 Free automation', 'ppsgen'); ?></span></p>
        <?php } ?>
        <?php foreach ($definition['questions'] as $index => $q) {
          $key = $q['key'];
          $inputId = 'ppsgenPlayQ' . $key; ?>
          <div class="ppsgenPlayQuestion">
            <span class="ppsgenPlayQn"><?php echo esc_html($index + 1); ?></span>
            <div>
              <?php if (in_array($q['type'], ['text', 'email', 'url', 'tag'], true)) { ?>
                <label class="ppsgenPlayQt" for="<?php echo esc_attr($inputId); ?>"><?php echo esc_html($q['title']); ?></label>
                <?php if (!empty($q['help'])) { ?><div class="ppsgenMuted"><?php echo esc_html($q['help']); ?></div><?php } ?>
                <input
                  id="<?php echo esc_attr($inputId); ?>"
                  type="<?php echo esc_attr($q['type'] === 'tag' ? 'text' : $q['type']); ?>"
                  name="answers[<?php echo esc_attr($key); ?>]"
                  value="<?php echo esc_attr($answers[$key] ?? ''); ?>"
                  <?php echo !empty($q['required']) ? 'required' : ''; ?>
                  <?php echo $q['type'] === 'tag' && !empty($existingTags) ? 'list="ppsgenPlayTags"' : ''; ?>
                  <?php echo $q['type'] === 'url' ? 'placeholder="https://"' : ''; ?>
                >
              <?php } elseif ($q['type'] === 'fields') { ?>
                <div class="ppsgenPlayQt"><?php echo esc_html($q['title']); ?></div>
                <div class="ppsgenMuted"><?php echo esc_html($q['help']); ?></div>
                <div class="ppsgenPlayChecks">
                  <label><input type="checkbox" checked disabled> <?php esc_html_e('Email', 'ppsgen'); ?></label>
                  <?php foreach ($q['only'] as $field) { ?>
                    <label><input type="checkbox" name="answers[ask_<?php echo esc_attr($field); ?>]" value="1"<?php checked(!empty($answers['ask_' . $field])); ?>> <?php echo esc_html($fieldLabels[$field]); ?></label>
                  <?php } ?>
                </div>
              <?php } elseif ($q['type'] === 'trigger') { ?>
                <div class="ppsgenPlayQt"><?php echo esc_html($q['title']); ?></div>
                <div class="ppsgenMuted"><?php echo esc_html($q['help']); ?></div>
                <div class="ppsgenSegmented" role="radiogroup">
                  <?php foreach ($triggers as $value => $title) { ?>
                    <label><input type="radio" name="answers[trigger]" value="<?php echo esc_attr($value); ?>"<?php checked($answers['trigger'] ?? 'delay', $value); ?>><span><?php echo esc_html($title); ?></span></label>
                  <?php } ?>
                </div>
                <div class="ppsgenPlayInline">
                  <span class="ppsgenMuted"><?php esc_html_e('At most once per visitor per', 'ppsgen'); ?></span>
                  <select name="answers[frequency]">
                    <?php foreach (['day' => __('day', 'ppsgen'), 'week' => __('week', 'ppsgen'), 'month' => __('month', 'ppsgen')] as $value => $title) { ?>
                      <option value="<?php echo esc_attr($value); ?>"<?php selected($answers['frequency'] ?? 'week', $value); ?>><?php echo esc_html($title); ?></option>
                    <?php } ?>
                  </select>
                </div>
              <?php } elseif ($q['type'] === 'message') {
                $isAutomationEmail = !empty($q['days']) || strpos((string) ($q['default']['body'] ?? ''), '{{contact.') !== false; ?>
                <div class="ppsgenPlayQt"><?php echo esc_html($q['title']); ?></div>
                <?php if (!empty($q['toggle'])) { ?>
                  <label class="ppsgenPlayInline"><input type="checkbox" name="answers[<?php echo esc_attr($key); ?>_on]" value="1"<?php checked(!empty($answers[$key . '_on'])); ?>> <?php echo esc_html($q['toggle']); ?></label>
                <?php } ?>
                <?php if (!empty($q['days'])) { ?>
                  <div class="ppsgenPlayInline">
                    <span><?php esc_html_e('Send', 'ppsgen'); ?></span>
                    <input type="number" min="0" max="60" name="answers[<?php echo esc_attr($key); ?>_days]" value="<?php echo esc_attr((int) ($answers[$key . '_days'] ?? 1)); ?>" aria-label="<?php esc_attr_e('Days', 'ppsgen'); ?>">
                    <span><?php echo esc_html($index > 1 && !empty($definition['questions'][$index - 1]['days']) ? __('days after the previous email', 'ppsgen') : __('days after it starts', 'ppsgen')); ?></span>
                  </div>
                <?php } ?>
                <input type="text" name="answers[<?php echo esc_attr($key); ?>_subject]" value="<?php echo esc_attr($answers[$key . '_subject'] ?? ''); ?>" aria-label="<?php esc_attr_e('Subject', 'ppsgen'); ?>">
                <textarea name="answers[<?php echo esc_attr($key); ?>_body]" rows="4" aria-label="<?php esc_attr_e('Message', 'ppsgen'); ?>"><?php echo esc_textarea($answers[$key . '_body'] ?? ''); ?></textarea>
                <div class="ppsgenMuted"><?php echo esc_html($isAutomationEmail ? __('{{contact.first_name|there}} is replaced with their first name, or "there".', 'ppsgen') : __('[first_name] is replaced with their name.', 'ppsgen')); ?></div>
              <?php } elseif ($q['type'] === 'days') { ?>
                <div class="ppsgenPlayQt"><?php echo esc_html($q['title']); ?></div>
                <div class="ppsgenPlayInline">
                  <label><input type="checkbox" name="answers[<?php echo esc_attr($key); ?>_on]" value="1"<?php checked(!empty($answers[$key . '_on'])); ?><?php disabled(!empty($q['automation']) && !$hasAutomation); ?>> <?php echo esc_html($q['toggle']); ?></label>
                  <input type="number" min="1" max="60" name="answers[<?php echo esc_attr($key); ?>_days]" value="<?php echo esc_attr((int) ($answers[$key . '_days'] ?? 2)); ?>" aria-label="<?php esc_attr_e('Days', 'ppsgen'); ?>">
                  <span><?php esc_html_e('days', 'ppsgen'); ?></span>
                  <?php if (!$isPro && !empty($q['automation'])) { ?><span class="ppsgenChip"><?php esc_html_e('Uses your 1 Free automation', 'ppsgen'); ?></span><?php } ?>
                </div>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
      </section>

      <div class="ppsgenPlaySide">
        <section class="ppsgenCard">
          <h2><?php esc_html_e('Live preview', 'ppsgen'); ?></h2>
          <?php if (($preview['type'] ?? '') === 'popup') { ?>
            <div class="ppsgenPlayPreview">
              <div class="ppsgenPlayPreviewSite">
                <span></span><span></span><span></span><span></span>
                <div class="ppsgenPlayPreviewPopup">
                  <div class="ppsgenPlayPreviewTitle" data-template="<?php echo esc_attr($preview['title']); ?>"><?php echo esc_html($fill($preview['title'])); ?></div>
                  <div class="ppsgenMuted" data-template="<?php echo esc_attr($preview['text']); ?>"><?php echo esc_html($fill($preview['text'])); ?></div>
                  <div class="ppsgenPlayPreviewField" data-field="ask_name"><?php esc_html_e('Your name', 'ppsgen'); ?></div>
                  <div class="ppsgenPlayPreviewField"><?php esc_html_e('Email', 'ppsgen'); ?></div>
                  <div class="ppsgenPlayPreviewField" data-field="ask_phone"><?php esc_html_e('Phone', 'ppsgen'); ?></div>
                  <div class="ppsgenPlayPreviewField" data-field="ask_message"><?php esc_html_e('How can we help?', 'ppsgen'); ?></div>
                  <div class="ppsgenPlayPreviewButton" data-template="<?php echo esc_attr($preview['button']); ?>"><?php echo esc_html($fill($preview['button'])); ?></div>
                </div>
              </div>
            </div>
            <p class="ppsgenMuted ppsgenPlayPreviewNote"></p>
          <?php } elseif (($preview['type'] ?? '') === 'email') { ?>
            <div class="ppsgenPlayMail">
              <div class="ppsgenPlayMailHead">
                <span class="ppsgenMuted"><?php echo esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)); ?></span>
                <strong data-template="<?php echo esc_attr($preview['subject']); ?>"><?php echo esc_html($fill($preview['subject'])); ?></strong>
              </div>
              <div class="ppsgenPlayMailBody" data-template="<?php echo esc_attr($preview['body']); ?>"><?php echo esc_html($fill($preview['body'])); ?></div>
            </div>
          <?php } ?>
        </section>
        <section class="ppsgenCard">
          <h2><?php esc_html_e('What will happen', 'ppsgen'); ?></h2>
          <ol class="ppsgenPlayJourney">
            <?php foreach ($definition['journey'] ?? [] as $step) { ?>
              <li data-template="<?php echo esc_attr($step['text']); ?>"<?php if (!empty($step['when'])) { ?> data-when="<?php echo esc_attr($step['when']); ?>"<?php } ?>><?php echo esc_html($fill($step['text'])); ?></li>
            <?php } ?>
          </ol>
        </section>
        <?php if (($emailStatus['state'] ?? '') !== 'ok') { ?>
          <section class="ppsgenCard ppsgenPlayWarn">
            <strong><?php esc_html_e('Emails may land in spam', 'ppsgen'); ?></strong>
            <p class="ppsgenMuted"><?php esc_html_e('Your site sends with its own mail. Everything is still saved in Contacts, but set up SMTP so your emails arrive.', 'ppsgen'); ?></p>
            <a href="<?php echo esc_url($emailStatus['url'] ?? ''); ?>"><?php esc_html_e('Set up email sending →', 'ppsgen'); ?></a>
          </section>
        <?php } ?>
      </div>
    </div>

    <div class="ppsgenPlayFooter">
      <button type="button" class="ppsgenBtnSecondary ppsgenPlaySave" data-build="0"><?php esc_html_e('Save as draft', 'ppsgen'); ?></button>
      <button type="button" class="ppsgenBtnPrimary ppsgenPlaySave" data-build="1" data-busy="<?php esc_attr_e('Building…', 'ppsgen'); ?>"><?php esc_html_e('Review what we\'ll build →', 'ppsgen'); ?></button>
    </div>
  </form>
  <?php } ?>
</div>
