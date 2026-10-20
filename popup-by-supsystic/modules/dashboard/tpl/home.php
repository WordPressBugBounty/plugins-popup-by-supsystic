<?php
if (!defined('ABSPATH')) {
  exit();
}
$home = is_array($this->home ?? null) ? $this->home : [];
$modules = $home['modules'] ?? [];
$adminBase = admin_url('admin.php?page=ppsgen');
$link = function ($tab, $extra = []) use ($adminBase) {
  return add_query_arg(array_merge(['tab' => $tab], $extra), $adminBase);
};
$startItems = [];
if (!empty($modules['popup'])) {
  $startItems[] = ['title' => __('New popup', 'ppsgen'), 'text' => __('Start from a template, then choose when visitors see it', 'ppsgen'), 'url' => $link('template_add', ['template_type' => 'popup'])];
}
if (!empty($modules['form'])) {
  $startItems[] = ['title' => __('New form', 'ppsgen'), 'text' => __('Collect enquiries and sign-ups straight into Contacts', 'ppsgen'), 'url' => $link('template_add', ['template_type' => 'form'])];
}
if (!empty($modules['eca'])) {
  $startItems[] = ['title' => __('Send a newsletter', 'ppsgen'), 'text' => __('One email to a list of contacts, now or at a set time', 'ppsgen'), 'url' => $link('eca_newsletters')];
  $startItems[] = ['title' => __('New automation', 'ppsgen'), 'text' => __('Welcome or follow up new contacts automatically', 'ppsgen'), 'url' => $link('eca_chains')];
}
if (!empty($modules['contact'])) {
  $startItems[] = ['title' => __('Import contacts', 'ppsgen'), 'text' => __('Bring in a CSV, WordPress users or WooCommerce customers', 'ppsgen'), 'url' => $link('contact_import')];
}
$automations = $home['automations'] ?? ['total' => 0, 'running' => 0];
?>
<div class="ppsgenHome">
  <div class="ppsgenPageHead">
    <div>
      <h1 class="ppsgenPageTitle"><?php esc_html_e('Home', 'ppsgen'); ?></h1>
      <p class="ppsgenPageSub"><?php esc_html_e('How your website engaged visitors in the last 7 days.', 'ppsgen'); ?></p>
    </div>
    <div class="ppsgenPageActions">
      <?php if (!empty($modules['eca'])) { ?>
        <a class="ppsgenBtnSecondary" href="<?php echo esc_url($link('eca_newsletters')); ?>"><?php esc_html_e('Send a newsletter', 'ppsgen'); ?></a>
      <?php } ?>
      <a class="ppsgenBtnPrimary" href="<?php echo esc_url($link('plays')); ?>">+ <?php esc_html_e('Start a play', 'ppsgen'); ?></a>
    </div>
  </div>

  <?php if (!empty($home['cards'])) { ?>
    <div class="ppsgenStatCards">
      <?php foreach ($home['cards'] as $card) { ?>
        <?php $cardTag = !empty($card['url']) ? 'a' : 'div'; ?>
        <<?php echo esc_attr($cardTag); ?> class="ppsgenCard ppsgenStatCard"<?php if (!empty($card['url'])) { ?> href="<?php echo esc_url($card['url']); ?>"<?php } ?>>
          <div class="ppsgenStatLabel"><?php echo esc_html($card['label']); ?></div>
          <div class="ppsgenStatValue"><?php echo esc_html($card['value']); ?></div>
          <div class="ppsgenStatSub"><?php echo esc_html($card['sub']); ?></div>
        </<?php echo esc_attr($cardTag); ?>>
      <?php } ?>
    </div>
  <?php } ?>

  <div class="ppsgenHomeGrid">
    <div class="ppsgenHomeMain">
      <?php $homePlays = is_array($this->homePlays ?? null) ? $this->homePlays : []; ?>
      <section class="ppsgenCard">
        <div class="ppsgenCardHead">
          <h2><?php esc_html_e('Your plays', 'ppsgen'); ?></h2>
          <a href="<?php echo esc_url($link('plays')); ?>"><?php esc_html_e('All plays', 'ppsgen'); ?></a>
        </div>
        <?php if (empty($homePlays)) { ?>
          <p class="ppsgenEmpty"><?php esc_html_e('No plays yet. A play sets up a popup, form and emails for one goal, like getting more enquiries.', 'ppsgen'); ?> <a href="<?php echo esc_url($link('plays')); ?>"><?php esc_html_e('Start one →', 'ppsgen'); ?></a></p>
        <?php } else { ?>
          <?php foreach ($homePlays as $homePlay) {
            $status = (string) ($homePlay['status'] ?? 'draft');
            $target = $status === 'draft' ? 'play_setup' : ($status === 'built' ? 'play_review' : 'play_summary'); ?>
            <a class="ppsgenHomePlay" href="<?php echo esc_url($link($target, ['id' => $homePlay['id']])); ?>">
              <span class="ppsgenHomePlayBody">
                <span class="ppsgenStartItemTitle"><?php echo esc_html($homePlay['name'] ?? ''); ?></span>
                <span class="ppsgenMuted"><?php echo esc_html($this->playDefinitions[$homePlay['type']]['title'] ?? ''); ?><?php echo $status === 'draft' ? ' · ' . esc_html__('questions answered, not built yet', 'ppsgen') : ''; ?></span>
                <?php if (!empty($homePlay['chips'])) { ?>
                  <span class="ppsgenPlayChips"><?php foreach (array_unique($homePlay['chips']) as $chip) { ?><span class="ppsgenChip"><?php echo esc_html($chip); ?></span><?php } ?></span>
                <?php } ?>
              </span>
              <?php if (!empty($homePlay['stats'])) { ?>
                <span class="ppsgenHomePlayNum"><strong><?php echo esc_html(number_format_i18n((int) $homePlay['stats']['primary'])); ?></strong> <span class="ppsgenMuted"><?php echo esc_html(mb_strtolower($this->playDefinitions[$homePlay['type']]['people'] ?? __('People', 'ppsgen'))); ?></span></span>
              <?php } else { ?>
                <span class="ppsgenHomePlayNum"><?php echo esc_html($status === 'draft' ? __('Finish setting up →', 'ppsgen') : __('Review and go live →', 'ppsgen')); ?></span>
              <?php } ?>
              <span class="ppsgenStatus ppsgenStatus-<?php echo esc_attr($status === 'live' ? 'on' : ($status === 'paused' ? 'off' : 'scheduled')); ?>"><?php echo esc_html(['draft' => __('Draft', 'ppsgen'), 'built' => __('Not live yet', 'ppsgen'), 'live' => __('Live', 'ppsgen'), 'paused' => __('Paused', 'ppsgen')][$status] ?? $status); ?></span>
            </a>
          <?php } ?>
        <?php } ?>
      </section>
      <?php if (!empty($modules['form'])) { ?>
        <section class="ppsgenCard">
          <div class="ppsgenCardHead">
            <h2><?php esc_html_e('Recent enquiries', 'ppsgen'); ?></h2>
            <?php if (!empty($modules['contact'])) { ?>
              <a href="<?php echo esc_url($link('contact_list')); ?>"><?php esc_html_e('All contacts', 'ppsgen'); ?></a>
            <?php } ?>
          </div>
          <?php if (!empty($home['recent'])) { ?>
            <table class="ppsgenHomeTable">
              <thead>
                <tr>
                  <th><?php esc_html_e('Name', 'ppsgen'); ?></th>
                  <th><?php esc_html_e('Email', 'ppsgen'); ?></th>
                  <th><?php esc_html_e('Came from', 'ppsgen'); ?></th>
                  <th><?php esc_html_e('When', 'ppsgen'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($home['recent'] as $row) { ?>
                  <tr>
                    <td><a href="<?php echo esc_url($row['url']); ?>"><?php echo esc_html($row['name'] !== '' ? $row['name'] : __('(no name)', 'ppsgen')); ?></a></td>
                    <td class="ppsgenMuted"><?php echo esc_html($row['email']); ?></td>
                    <td><?php if ($row['source'] !== '') { ?><span class="ppsgenChip"><?php echo esc_html($row['source']); ?></span><?php } ?></td>
                    <td class="ppsgenMuted"><?php echo esc_html($row['when']); ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          <?php } else { ?>
            <p class="ppsgenEmpty"><?php esc_html_e('No enquiries yet. They appear here as soon as someone sends a form.', 'ppsgen'); ?></p>
          <?php } ?>
        </section>
      <?php } ?>
      <?php $monthly = is_array($this->monthly ?? null) ? $this->monthly : []; ?>
      <?php if (!empty($monthly['rows'])) { ?>
        <section class="ppsgenCard ppsgenThisMonth">
          <div class="ppsgenCardHead">
            <h2><?php esc_html_e('This month', 'ppsgen'); ?></h2>
            <span class="ppsgenMuted"><?php esc_html_e('so far, compared with the same days last month', 'ppsgen'); ?></span>
          </div>
          <table class="ppsgenHomeTable">
            <thead>
              <tr>
                <th></th>
                <th><?php echo esc_html($monthly['this_label']); ?></th>
                <th><?php echo esc_html($monthly['last_label']); ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($monthly['rows'] as $row) { ?>
                <tr>
                  <td><?php echo esc_html($row['label']); ?></td>
                  <td><strong><?php echo esc_html(number_format_i18n($row['this'])); ?></strong></td>
                  <td class="ppsgenMuted"><?php echo esc_html(number_format_i18n($row['last'])); ?></td>
                  <td class="ppsgenThisMonthChange<?php echo $row['change'] !== null ? ($row['change'] >= 0 ? ' ppsgenThisMonthUp' : ' ppsgenThisMonthDown') : ''; ?>"><?php echo $row['change'] === null ? '—' : esc_html(($row['change'] > 0 ? '+' : '') . $row['change'] . '%'); ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
          <?php $monthlyReport = is_array($this->monthlyReport ?? null) ? $this->monthlyReport : []; ?>
          <div class="ppsgenThisMonthEmail">
            <label><input type="checkbox" class="ppsgenMonthlyReportToggle"<?php checked(!empty($monthlyReport['enabled'])); ?>> <?php echo esc_html(sprintf(__('Email me last month’s summary on the 1st of each month (to %s)', 'ppsgen'), $monthlyReport['to'] ?? '')); ?></label>
            <a href="#" class="ppsgenMonthlyReportSample"><?php esc_html_e('Send me a sample now', 'ppsgen'); ?></a>
          </div>
        </section>
      <?php } ?>
    </div>

    <div class="ppsgenHomeSide">
      <section class="ppsgenCard">
        <h2><?php esc_html_e('Needs your attention', 'ppsgen'); ?></h2>
        <?php if (!empty($home['attention'])) { ?>
          <?php foreach ($home['attention'] as $item) { ?>
            <div class="ppsgenAttention">
              <span class="ppsgenAttentionDot"></span>
              <div>
                <div class="ppsgenAttentionTitle"><?php echo esc_html($item['title']); ?></div>
                <div class="ppsgenMuted"><?php echo esc_html($item['text']); ?></div>
                <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['link']); ?></a>
              </div>
            </div>
          <?php } ?>
        <?php } else { ?>
          <p class="ppsgenEmpty"><?php esc_html_e('All good. Nothing needs fixing right now.', 'ppsgen'); ?></p>
        <?php } ?>
      </section>

      <?php if (!empty($startItems)) { ?>
        <section class="ppsgenCard">
          <h2><?php esc_html_e('Start something new', 'ppsgen'); ?></h2>
          <?php foreach ($startItems as $item) { ?>
            <a class="ppsgenStartItem" href="<?php echo esc_url($item['url']); ?>">
              <span class="ppsgenStartItemTitle"><?php echo esc_html($item['title']); ?></span>
              <span class="ppsgenMuted"><?php echo esc_html($item['text']); ?></span>
            </a>
          <?php } ?>
        </section>
      <?php } ?>

      <?php if (empty($home['is_pro']) && !empty($modules['eca'])) { ?>
        <section class="ppsgenCard">
          <div class="ppsgenStatLabel"><?php esc_html_e('Your Free plan', 'ppsgen'); ?></div>
          <div class="ppsgenMeterRow">
            <span><?php esc_html_e('Automations', 'ppsgen'); ?></span>
            <span class="ppsgenMuted"><?php echo esc_html(sprintf(__('%1$d of %2$d running', 'ppsgen'), min((int) $automations['running'], 1), 1)); ?></span>
          </div>
          <div class="ppsgenMeter"><div style="width: <?php echo esc_attr((int) $automations['running'] > 0 ? '100' : '0'); ?>%;"></div></div>
          <?php if ((int) $automations['running'] > 0) { ?>
            <p class="ppsgenMuted"><?php esc_html_e('Build as many automations as you like and switch between them. To run a second one at the same time, like a welcome series next to your follow-up, PRO runs as many as you need.', 'ppsgen'); ?></p>
            <a href="<?php echo esc_url($link('settings_pro')); ?>"><?php esc_html_e('See what PRO adds →', 'ppsgen'); ?></a>
          <?php } ?>
        </section>
      <?php } ?>
    </div>
  </div>
</div>
