<?php
if (!defined('ABSPATH')) {
  exit();
}
$scan = $this->scan;
$forms = $scan['forms'];
$total = count($forms);
$movedCount = count(array_filter($forms, static function ($f) {
  return !empty($f['cesId']);
}));
$submissions = array_sum(array_map(static function ($f) {
  return (int) $f['submissions'];
}, $forms));
$limit = $scan['limit'];
?>
<div class="ppsgen-cfi" data-ppsgen-cfi>

  <section class="ppsgen-cfi-hero">
    <div class="ppsgen-cfi-hero-text">
      <p class="ppsgen-cfi-eyebrow">
        <span><?php esc_html_e('Contact Form by Supsystic', 'ppsgen'); ?></span>
        <span class="ppsgen-cfi-eyebrow-arrow" aria-hidden="true">→</span>
        <span><?php esc_html_e('Customer Engagement System', 'ppsgen'); ?></span>
      </p>
      <h1><?php echo $this->fromCf ? esc_html__('Welcome! Your forms are one click away', 'ppsgen') : esc_html__('Move your forms from Contact Form', 'ppsgen'); ?></h1>
      <p class="ppsgen-cfi-lead"><?php esc_html_e('Fields, settings, emails, conditional logic and submissions move into CES forms, and you pick their look from the CES templates. Senders become contacts you can tag and follow up. Contact Form and its data stay exactly as they are.', 'ppsgen'); ?></p>
      <ul class="ppsgen-cfi-chips">
        <li><b data-ppsgen-cfi-count="forms"><?php echo esc_html((string) $total); ?></b> <?php echo esc_html(_n('form found', 'forms found', $total, 'ppsgen')); ?></li>
        <li><b><?php echo esc_html((string) $submissions); ?></b> <?php echo esc_html(_n('submission', 'submissions', $submissions, 'ppsgen')); ?></li>
        <li><b data-ppsgen-cfi-count="moved"><?php echo esc_html((string) $movedCount); ?></b> <?php esc_html_e('already in CES', 'ppsgen'); ?></li>
        <li class="ppsgen-cfi-chip-state"><?php echo $scan['sourceActive'] ? esc_html__('Contact Form is active', 'ppsgen') : esc_html__('Contact Form is off: the move works anyway', 'ppsgen'); ?></li>
      </ul>
    </div>
    <div class="ppsgen-cfi-hero-art" aria-hidden="true">
      <div class="ppsgen-cfi-art-card ppsgen-cfi-art-old">
        <i></i><i></i><i class="short"></i><b></b>
      </div>
      <div class="ppsgen-cfi-art-arrow"><span></span></div>
      <div class="ppsgen-cfi-art-card ppsgen-cfi-art-new">
        <i></i><i></i><i class="short"></i><b></b>
        <em class="ppsgen-cfi-art-tag"><?php esc_html_e('contact', 'ppsgen'); ?></em>
      </div>
    </div>
  </section>

  <div class="ppsgen-cfi-grid">
    <div class="ppsgen-cfi-main">

      <section class="ppsgen-cfi-card" data-ppsgen-cfi-forms-card>
        <header class="ppsgen-cfi-card-head">
          <div>
            <h2><?php esc_html_e('Your Contact Form forms', 'ppsgen'); ?></h2>
            <p><?php esc_html_e('Pick the forms to move, or move them all. A form that is already in CES is never copied twice.', 'ppsgen'); ?></p>
          </div>
          <label class="ppsgen-cfi-check ppsgen-cfi-selectall">
            <input type="checkbox" data-ppsgen-cfi-selectall checked>
            <span><?php esc_html_e('Select all', 'ppsgen'); ?></span>
          </label>
        </header>
        <div class="ppsgen-cfi-list" data-ppsgen-cfi-list role="list">
          <p class="ppsgen-cfi-loading"><?php esc_html_e('Loading your forms…', 'ppsgen'); ?></p>
        </div>
        <div class="ppsgen-cfi-notes" data-ppsgen-cfi-notes></div>
        <footer class="ppsgen-cfi-actions">
          <button type="button" class="ppsgen-cfi-btn ppsgen-cfi-btn-primary" data-ppsgen-cfi-move="all">
            <span class="ppsgen-cfi-spinner" aria-hidden="true"></span>
            <span data-ppsgen-cfi-label><?php esc_html_e('Move all forms', 'ppsgen'); ?></span>
          </button>
          <button type="button" class="ppsgen-cfi-btn" data-ppsgen-cfi-move="selected">
            <span data-ppsgen-cfi-label><?php esc_html_e('Move selected', 'ppsgen'); ?></span>
          </button>
          <span class="ppsgen-cfi-status" data-ppsgen-cfi-status role="status" aria-live="polite"></span>
        </footer>
      </section>

      <section class="ppsgen-cfi-card ppsgen-cfi-results" data-ppsgen-cfi-results hidden>
        <header class="ppsgen-cfi-card-head">
          <div>
            <h2 data-ppsgen-cfi-results-title></h2>
            <p data-ppsgen-cfi-results-lead></p>
          </div>
        </header>
        <div data-ppsgen-cfi-results-list></div>
      </section>

      <section class="ppsgen-cfi-card" data-ppsgen-cfi-shortcodes>
        <header class="ppsgen-cfi-card-head">
          <div>
            <h2><?php esc_html_e('Shortcodes on your pages', 'ppsgen'); ?></h2>
            <p>
              <?php esc_html_e('Pages show a Contact Form form with', 'ppsgen'); ?> <code>[supsystic-form id=…]</code>.
              <?php esc_html_e('After the move, swap it for', 'ppsgen'); ?> <code>[supsystic-ces-form id=…]</code>
              <?php esc_html_e('here. Check the list first: you can undo it at any time.', 'ppsgen'); ?>
            </p>
          </div>
        </header>
        <div data-ppsgen-cfi-sc-list></div>
        <footer class="ppsgen-cfi-actions">
          <button type="button" class="ppsgen-cfi-btn ppsgen-cfi-btn-accent" data-ppsgen-cfi-sc="replace" hidden>
            <span class="ppsgen-cfi-spinner" aria-hidden="true"></span>
            <span data-ppsgen-cfi-label></span>
          </button>
          <button type="button" class="ppsgen-cfi-btn" data-ppsgen-cfi-sc="undo" hidden>
            <span data-ppsgen-cfi-label><?php esc_html_e('Undo the replacement', 'ppsgen'); ?></span>
          </button>
          <span class="ppsgen-cfi-status" data-ppsgen-cfi-sc-status role="status" aria-live="polite"></span>
        </footer>
      </section>

    </div>

    <aside class="ppsgen-cfi-side">
      <section class="ppsgen-cfi-card ppsgen-cfi-options">
        <h2><?php esc_html_e('What to move', 'ppsgen'); ?></h2>
        <label class="ppsgen-cfi-switch">
          <input type="checkbox" data-ppsgen-cfi-opt="submissions" checked>
          <span class="ppsgen-cfi-switch-ui" aria-hidden="true"></span>
          <span class="ppsgen-cfi-switch-text">
            <b><?php esc_html_e('Move submissions', 'ppsgen'); ?></b>
            <small><?php esc_html_e('With their dates and IP addresses. Run the move again later to bring new ones.', 'ppsgen'); ?></small>
          </span>
        </label>
        <label class="ppsgen-cfi-switch" data-ppsgen-cfi-contacts-row>
          <input type="checkbox" data-ppsgen-cfi-opt="contacts" checked>
          <span class="ppsgen-cfi-switch-ui" aria-hidden="true"></span>
          <span class="ppsgen-cfi-switch-text">
            <b><?php esc_html_e('Add senders to Contacts', 'ppsgen'); ?></b>
            <small><?php esc_html_e('Tagged "contact-form". Added quietly: no email is sent and no automation starts.', 'ppsgen'); ?></small>
          </span>
        </label>
        <?php if (!empty($limit['applies'])) { ?>
          <p class="ppsgen-cfi-hint"><?php echo esc_html(sprintf(__('CES Free adds up to %1$d contacts in bulk moves (you have %2$d now). Submissions always move.', 'ppsgen'), (int) $limit['limit'], (int) $limit['total'])); ?></p>
        <?php } ?>
      </section>

      <section class="ppsgen-cfi-card ppsgen-cfi-why">
        <h2><?php esc_html_e('What you get in CES', 'ppsgen'); ?></h2>
        <ul>
          <li><span class="dashicons dashicons-groups" aria-hidden="true"></span><?php esc_html_e('Every sender in one contact list with tags', 'ppsgen'); ?></li>
          <li><span class="dashicons dashicons-email-alt" aria-hidden="true"></span><?php esc_html_e('Follow-up emails and automations', 'ppsgen'); ?></li>
          <li><span class="dashicons dashicons-format-gallery" aria-hidden="true"></span><?php esc_html_e('Show the same form in a popup', 'ppsgen'); ?></li>
          <li><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span><?php esc_html_e('Views, submissions and conversion per form', 'ppsgen'); ?></li>
        </ul>
      </section>

      <section class="ppsgen-cfi-card ppsgen-cfi-safe">
        <h2><?php esc_html_e('Safe to try', 'ppsgen'); ?></h2>
        <p><?php esc_html_e('Your Contact Form forms and submissions are only read, never changed or deleted. Its shortcodes keep working until you replace them, and the replacement can be undone.', 'ppsgen'); ?></p>
        <p><a href="<?php echo esc_url($this->formsUrl); ?>"><?php esc_html_e('Go to CES forms', 'ppsgen'); ?></a> · <a href="<?php echo esc_url($this->templatesUrl); ?>"><?php esc_html_e('Start a new form from a template', 'ppsgen'); ?></a></p>
      </section>
    </aside>
  </div>
</div>
