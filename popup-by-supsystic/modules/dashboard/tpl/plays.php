<?php
if (!defined('ABSPATH')) {
  exit();
}
$definitions = is_array($this->definitions ?? null) ? $this->definitions : [];
$availability = is_array($this->availability ?? null) ? $this->availability : [];
$plays = is_array($this->plays ?? null) ? $this->plays : [];
$adminBase = admin_url('admin.php?page=ppsgen');
$statusLabels = [
  'draft' => __('Draft', 'ppsgen'),
  'built' => __('Not live yet', 'ppsgen'),
  'live' => __('Live', 'ppsgen'),
  'paused' => __('Paused', 'ppsgen'),
];
?>
<div class="ppsgenHome ppsgenPlays">
  <div class="ppsgenPageHead">
    <div>
      <h1 class="ppsgenPageTitle"><?php esc_html_e('What do you want to achieve?', 'ppsgen'); ?></h1>
      <p class="ppsgenPageSub"><?php esc_html_e('Pick an outcome. Answer a few questions and we\'ll build the popup, form, contacts and emails for you. Everything stays editable afterwards.', 'ppsgen'); ?></p>
    </div>
  </div>

  <div class="ppsgenPlayGrid">
    <?php foreach ($definitions as $type => $definition) {
      $available = !empty($availability[$type]); ?>
      <<?php echo $available ? 'a' : 'div'; ?> class="ppsgenCard ppsgenPlayCard<?php echo $available ? '' : ' ppsgenPlayCardOff'; ?>"<?php if ($available) { ?> href="<?php echo esc_url(add_query_arg(['tab' => 'play_setup', 'type' => $type], $adminBase)); ?>"<?php } ?>>
        <span class="ppsgenPlayEyebrow"><?php echo esc_html($definition['eyebrow']); ?></span>
        <span class="ppsgenPlayTitle"><?php echo esc_html($definition['title']); ?></span>
        <span class="ppsgenMuted"><?php echo esc_html($definition['description']); ?></span>
        <span class="ppsgenPlayChips">
          <?php foreach ($definition['chips'] as $chip) { ?><span class="ppsgenChip"><?php echo esc_html($chip); ?></span><?php } ?>
        </span>
        <span class="ppsgenPlayGo">
          <?php echo $available
            ? esc_html(sprintf(__('Start · about %d min →', 'ppsgen'), (int) $definition['minutes']))
            : esc_html__('Needs Popups, Forms and Contacts switched on in Settings › Features', 'ppsgen'); ?>
        </span>
      </<?php echo $available ? 'a' : 'div'; ?>>
    <?php } ?>
  </div>

  <?php if (!empty($plays)) { ?>
    <section class="ppsgenCard">
      <h2><?php esc_html_e('Your plays', 'ppsgen'); ?></h2>
      <table class="ppsgenHomeTable">
        <thead>
          <tr>
            <th><?php esc_html_e('Play', 'ppsgen'); ?></th>
            <th><?php esc_html_e('Outcome', 'ppsgen'); ?></th>
            <th><?php esc_html_e('Status', 'ppsgen'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plays as $play) {
            $status = (string) ($play['status'] ?? 'draft');
            if ($status === 'draft') {
              $url = add_query_arg(['tab' => 'play_setup', 'id' => $play['id']], $adminBase);
              $action = __('Finish setting up →', 'ppsgen');
            } elseif ($status === 'built') {
              $url = add_query_arg(['tab' => 'play_review', 'id' => $play['id']], $adminBase);
              $action = __('Review and go live →', 'ppsgen');
            } else {
              $url = add_query_arg(['tab' => 'play_summary', 'id' => $play['id']], $adminBase);
              $action = __('Results →', 'ppsgen');
            } ?>
            <tr data-play-id="<?php echo esc_attr($play['id']); ?>">
              <td><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($play['name'] ?? ''); ?></a></td>
              <td class="ppsgenMuted"><?php echo esc_html($definitions[$play['type']]['title'] ?? ''); ?></td>
              <td><span class="ppsgenStatus ppsgenStatus-<?php echo esc_attr($status === 'live' ? 'on' : ($status === 'paused' ? 'off' : 'scheduled')); ?>"><?php echo esc_html($statusLabels[$status] ?? $status); ?></span></td>
              <td class="ppsgenReportLink">
                <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($action); ?></a>
                · <a href="#" class="ppsgenPlayDelete" data-confirm="<?php esc_attr_e('Remove this play from the list? Its popup, form and emails stay in Popups, Forms and Campaigns.', 'ppsgen'); ?>"><?php esc_html_e('Remove', 'ppsgen'); ?></a>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </section>
  <?php } ?>

  <section class="ppsgenCard ppsgenPlayManual">
    <div>
      <h2><?php esc_html_e('Know exactly what you want?', 'ppsgen'); ?></h2>
      <p class="ppsgenMuted"><?php esc_html_e('Skip the questions and build it yourself.', 'ppsgen'); ?></p>
    </div>
    <div class="ppsgenPageActions">
      <?php if ($this->getFrame()->getModule('popup')) { ?><a class="ppsgenBtnSecondary" href="<?php echo esc_url(add_query_arg(['tab' => 'template_add', 'template_type' => 'popup'], $adminBase)); ?>"><?php esc_html_e('New popup', 'ppsgen'); ?></a><?php } ?>
      <?php if ($this->getFrame()->getModule('form')) { ?><a class="ppsgenBtnSecondary" href="<?php echo esc_url(add_query_arg(['tab' => 'template_add', 'template_type' => 'form'], $adminBase)); ?>"><?php esc_html_e('New form', 'ppsgen'); ?></a><?php } ?>
      <?php if ($this->getFrame()->getModule('eca')) { ?>
        <a class="ppsgenBtnSecondary" href="<?php echo esc_url(add_query_arg('tab', 'eca_newsletters', $adminBase)); ?>"><?php esc_html_e('New newsletter', 'ppsgen'); ?></a>
        <a class="ppsgenBtnSecondary" href="<?php echo esc_url(add_query_arg('tab', 'eca_chains', $adminBase)); ?>"><?php esc_html_e('New automation', 'ppsgen'); ?></a>
      <?php } ?>
    </div>
  </section>
</div>
