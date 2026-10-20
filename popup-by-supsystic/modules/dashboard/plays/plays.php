<?php

if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/definitions.php';

/**
 * Plays: outcome wizards that build normal CES items (popup, form, tags, emails, automation, list, newsletter).
 * A play never hides what it builds: every part stays a normal item in Popups, Forms, Contacts and Campaigns.
 * Plays are stored as one option row; the built items are referenced by id in the play record.
 */
class PlaysPpsgen
{
  protected FramePpsgen $frame;
  protected ?array $definitions = null;

  public function __construct(FramePpsgen $frame)
  {
    $this->frame = $frame;
  }

  protected function getStore()
  {
    return $this->frame->getModule('dashboard')->getModel();
  }

  //=====================================
  //       DEFINITIONS AND STORAGE
  //=====================================

  public function getDefinitions(): array
  {
    if ($this->definitions === null) {
      $this->definitions = ppsgen_get_play_definitions();
    }
    return $this->definitions;
  }

  public function getDefinition(string $type): array
  {
    return $this->getDefinitions()[$type] ?? [];
  }

  public function isAvailable(string $type): bool
  {
    $definition = $this->getDefinition($type);
    if (empty($definition)) {
      return false;
    }
    foreach ($definition['modules'] as $module) {
      if (!$this->frame->getModule($module)) {
        return false;
      }
    }
    return true;
  }

  public function getPlays(): array
  {
    $plays = $this->getStore()->getOption('plays', 'plays', 0, 'dashboard');
    return is_array($plays) ? array_values($plays) : [];
  }

  public function getPlay(string $id): array
  {
    foreach ($this->getPlays() as $play) {
      if (($play['id'] ?? '') === $id) {
        return $play;
      }
    }
    return [];
  }

  protected function savePlay(array $play): void
  {
    $plays = $this->getPlays();
    $found = false;
    foreach ($plays as $index => $item) {
      if (($item['id'] ?? '') === $play['id']) {
        $plays[$index] = $play;
        $found = true;
      }
    }
    if (!$found) {
      array_unshift($plays, $play);
    }
    $this->getStore()->updateOption('plays', 'plays', $plays, 0, 'dashboard', 'json');
  }

  // Removes the play record only. The items it built stay as normal items.
  public function deletePlay(string $id): void
  {
    $plays = array_values(array_filter($this->getPlays(), function ($play) use ($id) {
      return ($play['id'] ?? '') !== $id;
    }));
    $this->getStore()->updateOption('plays', 'plays', $plays, 0, 'dashboard', 'json');
  }

  // "Part of: [play]" chip on popup, form and campaign editors
  public function getPlayByPart(string $partType, int $partId): array
  {
    if ($partId <= 0) {
      return [];
    }
    foreach ($this->getPlays() as $play) {
      if ((int) ($play['parts'][$partType . '_id'] ?? 0) === $partId) {
        return $play;
      }
    }
    return [];
  }

  //=====================================
  //       ANSWERS
  //=====================================

  // Flat answers: message "thanks" → thanks_on / thanks_subject / thanks_body / thanks_days
  public function getDefaultAnswers(string $type): array
  {
    $answers = [];
    foreach ($this->getDefinition($type)['questions'] ?? [] as $question) {
      $key = $question['key'];
      $default = $question['default'] ?? '';
      switch ($question['type']) {
        case 'fields':
        case 'trigger':
          $answers = array_merge($answers, $default);
          break;
        case 'message':
          $answers[$key . '_on'] = (int) ($default['on'] ?? 1);
          $answers[$key . '_subject'] = (string) ($default['subject'] ?? '');
          $answers[$key . '_body'] = (string) ($default['body'] ?? '');
          if (!empty($question['days'])) {
            $answers[$key . '_days'] = (int) ($default['days'] ?? 1);
          }
          break;
        case 'days':
          $answers[$key . '_on'] = (int) ($default['on'] ?? 1);
          $answers[$key . '_days'] = (int) ($default['days'] ?? 2);
          break;
        default:
          $answers[$key] = (string) $default;
      }
    }
    return $answers;
  }

  public function sanitizeAnswers(string $type, $input): array
  {
    $input = is_array($input) ? $input : [];
    $defaults = $this->getDefaultAnswers($type);
    $answers = [];
    foreach ($this->getDefinition($type)['questions'] ?? [] as $question) {
      $key = $question['key'];
      switch ($question['type']) {
        case 'fields':
          foreach (['name', 'phone', 'message'] as $field) {
            if (in_array($field, $question['only'], true)) {
              $answers['ask_' . $field] = !empty($input['ask_' . $field]) ? 1 : 0;
            }
          }
          break;
        case 'trigger':
          $trigger = sanitize_key((string) ($input['trigger'] ?? ''));
          $answers['trigger'] = in_array($trigger, ['delay', 'scroll', 'exit', 'none'], true) ? $trigger : $defaults['trigger'];
          $frequency = sanitize_key((string) ($input['frequency'] ?? ''));
          $answers['frequency'] = in_array($frequency, ['day', 'week', 'month'], true) ? $frequency : 'week';
          break;
        case 'message':
          $answers[$key . '_on'] = isset($question['toggle']) ? (!empty($input[$key . '_on']) ? 1 : 0) : 1;
          $answers[$key . '_subject'] = trim(sanitize_text_field((string) ($input[$key . '_subject'] ?? ''))) ?: $defaults[$key . '_subject'];
          $answers[$key . '_body'] = trim(sanitize_textarea_field((string) ($input[$key . '_body'] ?? ''))) ?: $defaults[$key . '_body'];
          if (!empty($question['days'])) {
            $answers[$key . '_days'] = min(60, max(0, (int) ($input[$key . '_days'] ?? $defaults[$key . '_days'])));
          }
          break;
        case 'days':
          $answers[$key . '_on'] = !empty($input[$key . '_on']) ? 1 : 0;
          $answers[$key . '_days'] = min(60, max(1, (int) ($input[$key . '_days'] ?? $defaults[$key . '_days'])));
          break;
        case 'email':
          $email = sanitize_email((string) ($input[$key] ?? ''));
          $answers[$key] = is_email($email) ? $email : '';
          break;
        case 'url':
          $answers[$key] = esc_url_raw(trim((string) ($input[$key] ?? '')));
          break;
        case 'tag':
          $answers[$key] = sanitize_title((string) ($input[$key] ?? '')) ?: $defaults[$key];
          break;
        default:
          $answers[$key] = trim(sanitize_text_field((string) ($input[$key] ?? '')));
          if ($answers[$key] === '' && !empty($question['required'])) {
            $answers[$key] = $defaults[$key];
          }
      }
    }
    return $answers;
  }

  public function validateAnswers(string $type, array $answers): string
  {
    foreach ($this->getDefinition($type)['questions'] ?? [] as $question) {
      if (!empty($question['required']) && in_array($question['type'], ['url'], true) && empty($answers[$question['key']])) {
        return sprintf(__('Please answer: %s', 'ppsgen'), $question['title']);
      }
    }
    return '';
  }

  // "{label} enquiries" → "Free quote enquiries"
  public function fill(string $template, array $answers): string
  {
    return preg_replace_callback('/\{([a-z0-9_]+)\}/', function ($m) use ($answers) {
      return isset($answers[$m[1]]) && !is_array($answers[$m[1]]) ? (string) $answers[$m[1]] : $m[0];
    }, $template);
  }

  public function getPlayName(string $type, array $answers): string
  {
    return ucfirst($this->fill((string) ($this->getDefinition($type)['name'] ?? ''), $answers));
  }

  public function saveAnswers(string $type, array $input, string $id = ''): array
  {
    if (!$this->isAvailable($type)) {
      return ['ok' => false, 'message' => __('This play needs more features switched on in Settings › Features.', 'ppsgen')];
    }
    $play = $id !== '' ? $this->getPlay($id) : [];
    if (!empty($play) && ($play['status'] ?? 'draft') !== 'draft') {
      return ['ok' => false, 'message' => __('This play is already built. Edit its parts directly.', 'ppsgen')];
    }
    $answers = $this->sanitizeAnswers($type, $input);
    if (empty($play)) {
      $play = [
        'id' => substr(md5($type . microtime() . wp_rand()), 0, 10),
        'type' => $type,
        'status' => 'draft',
        'created_at' => current_time('timestamp'),
        'parts' => [],
      ];
    }
    $play['answers'] = $answers;
    $play['name'] = $this->getPlayName($type, $answers);
    $this->savePlay($play);
    return ['ok' => true, 'id' => $play['id']];
  }

  //=====================================
  //       BUILD
  //=====================================

  public function build(string $id): array
  {
    $play = $this->getPlay($id);
    if (empty($play)) {
      return ['ok' => false, 'message' => __('Play not found.', 'ppsgen')];
    }
    if (($play['status'] ?? '') !== 'draft') {
      return ['ok' => true, 'id' => $id];
    }
    $definition = $this->getDefinition($play['type']);
    $error = $this->validateAnswers($play['type'], $play['answers']);
    if ($error !== '') {
      return ['ok' => false, 'message' => $error];
    }
    $method = $definition['builder'] ?? '';
    if (!method_exists($this, $method)) {
      return ['ok' => false, 'message' => __('Play not found.', 'ppsgen')];
    }
    $play['notes'] = [];
    $result = $this->{$method}($play);
    if (empty($result['ok'])) {
      return $result;
    }
    $play['parts'] = $result['parts'];
    $play['notes'] = $result['notes'] ?? [];
    $play['status'] = 'built';
    $play['built_at'] = current_time('timestamp');
    $this->savePlay($play);
    return ['ok' => true, 'id' => $id];
  }

  protected function buildEnquiries(array $play): array
  {
    $a = $play['answers'];
    $parts = [
      'tag_id' => $this->ensureTag('enquiry-' . $a['label']),
      'stop_tag_id' => $this->ensureTag('replied'),
    ];
    $built = $this->buildPopupWithForm([
      'label' => $a['label'],
      'title' => sprintf(__('Get your %s', 'ppsgen'), $a['label']),
      'text' => __('Tell us a little and we\'ll get back to you within one business day.', 'ppsgen'),
      'submit' => __('Send enquiry', 'ppsgen'),
      'success' => __('Thank you! We\'ve got your enquiry and will be in touch soon.', 'ppsgen'),
      'fields' => $this->fieldPlan($a, ['name' => __('Your name', 'ppsgen'), 'phone' => __('Phone', 'ppsgen'), 'message' => __('How can we help?', 'ppsgen')]),
      'tag_ids' => [$parts['tag_id']],
      'owner_email' => $a['owner_email'],
      'owner_subject' => sprintf(__('New enquiry: %s', 'ppsgen'), $a['label']),
      'contact_on' => $a['thanks_on'],
      'contact_subject' => $a['thanks_subject'],
      'contact_body' => $a['thanks_body'],
      'trigger' => $a['trigger'],
      'frequency' => $a['frequency'],
    ]);
    if (empty($built['ok'])) {
      return $built;
    }
    $parts = array_merge($parts, $built['parts']);
    $notes = [];
    if (!empty($a['followup_on'])) {
      $chain = $this->buildAutomation(sprintf(__('%s follow-up', 'ppsgen'), ucfirst($a['label'])), [$parts['tag_id']], [[
        'subject' => sprintf(__('Still keen on your %s?', 'ppsgen'), $a['label']),
        'body' => sprintf(
          __("Hi {{contact.first_name|there}},\n\nA couple of days ago you asked us about a %1\$s. We just wanted to check you got our reply and see if you have any questions.\n\nJust reply to this email and we'll help.\n\nThe %2\$s team", 'ppsgen'),
          $a['label'],
          wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)
        ),
        'days' => $a['followup_days'],
      ]], $parts['stop_tag_id']);
      $parts['chain_id'] = $chain['chain_id'];
      $notes = $chain['notes'];
    }
    return ['ok' => true, 'parts' => $parts, 'notes' => $notes];
  }

  protected function buildEmailList(array $play): array
  {
    $a = $play['answers'];
    $parts = ['tag_id' => $this->ensureTag($a['label'])];
    $built = $this->buildPopupWithForm([
      'label' => $a['label'],
      'title' => $a['headline'],
      'text' => $a['pitch'],
      'submit' => __('Subscribe', 'ppsgen'),
      'success' => __('You\'re in! Check your inbox for a welcome email.', 'ppsgen'),
      'fields' => $this->fieldPlan($a, ['name' => __('Your name', 'ppsgen')]),
      'tag_ids' => [$parts['tag_id']],
      'owner_email' => '',
      'contact_on' => $a['welcome_on'],
      'contact_subject' => $a['welcome_subject'],
      'contact_body' => $a['welcome_body'],
      'trigger' => $a['trigger'],
      'frequency' => $a['frequency'],
      // People already on the list never see the signup popup again
      'audience' => 'without_tags',
      'audience_tags' => [$parts['tag_id']],
      'form_name' => sprintf(__('%s signup form', 'ppsgen'), ucfirst($a['label'])),
      'popup_name' => sprintf(__('%s signup popup', 'ppsgen'), ucfirst($a['label'])),
    ]);
    if (empty($built['ok'])) {
      return $built;
    }
    $parts = array_merge($parts, $built['parts']);
    $parts['list_id'] = $this->createTagList(sprintf(__('%s subscribers', 'ppsgen'), ucfirst($a['label'])), $parts['tag_id']);
    return ['ok' => true, 'parts' => $parts, 'notes' => []];
  }

  protected function buildFollowUpQuote(array $play): array
  {
    $a = $play['answers'];
    $parts = ['tag_id' => $this->ensureTag($a['start_tag']), 'stop_tag_id' => $this->ensureTag('replied')];
    $chain = $this->buildAutomation(__('Quote follow-up', 'ppsgen'), [$parts['tag_id']], [
      ['subject' => $a['first_subject'], 'body' => $a['first_body'], 'days' => $a['first_days']],
      ['subject' => $a['second_subject'], 'body' => $a['second_body'], 'days' => $a['second_days']],
    ], $parts['stop_tag_id']);
    if (empty($chain['chain_id'])) {
      return ['ok' => false, 'message' => $this->automationLimitMessage()];
    }
    $parts['chain_id'] = $chain['chain_id'];
    return ['ok' => true, 'parts' => $parts, 'notes' => $chain['notes']];
  }

  protected function buildWelcomeSeries(array $play): array
  {
    $a = $play['answers'];
    $parts = ['tag_id' => $this->ensureTag($a['start_tag'])];
    $steps = [];
    foreach (['email1', 'email2', 'email3'] as $key) {
      $steps[] = ['subject' => $a[$key . '_subject'], 'body' => $a[$key . '_body'], 'days' => $a[$key . '_days']];
    }
    $chain = $this->buildAutomation(__('Welcome series', 'ppsgen'), [$parts['tag_id']], $steps, 0);
    if (empty($chain['chain_id'])) {
      return ['ok' => false, 'message' => $this->automationLimitMessage()];
    }
    $parts['chain_id'] = $chain['chain_id'];
    return ['ok' => true, 'parts' => $parts, 'notes' => $chain['notes']];
  }

  protected function buildOffer(array $play): array
  {
    $a = $play['answers'];
    $parts = ['tag_id' => $this->ensureTag('offer-' . $a['label'])];
    $body = $this->fill($a['offer_body'], ['code' => $a['code'], 'link' => $a['link']]);
    $built = $this->buildPopupWithForm([
      'label' => $a['label'],
      'title' => $a['headline'],
      'text' => $a['details'],
      'submit' => $a['button'],
      'success' => __('Done! Check your inbox for the offer.', 'ppsgen'),
      'fields' => $this->fieldPlan(['ask_name' => 0], []),
      'tag_ids' => [$parts['tag_id']],
      'owner_email' => '',
      'contact_on' => 1,
      'contact_subject' => $a['offer_subject'],
      'contact_body' => $body,
      'trigger' => $a['trigger'],
      'frequency' => $a['frequency'],
      'form_name' => sprintf(__('%s offer form', 'ppsgen'), ucfirst($a['label'])),
      'popup_name' => sprintf(__('%s offer popup', 'ppsgen'), ucfirst($a['label'])),
    ]);
    if (empty($built['ok'])) {
      return $built;
    }
    return ['ok' => true, 'parts' => array_merge($parts, $built['parts']), 'notes' => []];
  }

  protected function buildReviewRequest(array $play): array
  {
    $a = $play['answers'];
    $parts = ['tag_id' => $this->ensureTag($a['start_tag']), 'stop_tag_id' => $this->ensureTag('reviewed')];
    $button = ['text' => __('Leave a review', 'ppsgen'), 'url' => $a['review_link']];
    $chain = $this->buildAutomation(__('Review requests', 'ppsgen'), [$parts['tag_id']], [
      ['subject' => $a['ask_subject'], 'body' => $a['ask_body'], 'days' => $a['ask_days'], 'button' => $button],
      ['subject' => $a['reminder_subject'], 'body' => $a['reminder_body'], 'days' => $a['reminder_days'], 'button' => $button],
    ], $parts['stop_tag_id']);
    if (empty($chain['chain_id'])) {
      return ['ok' => false, 'message' => $this->automationLimitMessage()];
    }
    $parts['chain_id'] = $chain['chain_id'];
    return ['ok' => true, 'parts' => $parts, 'notes' => $chain['notes']];
  }

  protected function buildRecovery(array $play): array
  {
    $a = $play['answers'];
    $parts = ['tag_id' => $this->ensureTag('exit-help'), 'stop_tag_id' => $this->ensureTag('replied')];
    $built = $this->buildPopupWithForm([
      'label' => 'exit help',
      'title' => $a['headline'],
      'text' => $a['pitch'],
      'submit' => __('Get help', 'ppsgen'),
      'success' => __('Thanks! We\'ll get back to you today.', 'ppsgen'),
      'fields' => $this->fieldPlan($a, ['name' => __('Your name', 'ppsgen'), 'message' => __('Your question', 'ppsgen')]),
      'tag_ids' => [$parts['tag_id']],
      'owner_email' => $a['owner_email'],
      'owner_subject' => __('Someone asked for help before leaving', 'ppsgen'),
      'contact_on' => $a['help_on'],
      'contact_subject' => $a['help_subject'],
      'contact_body' => $a['help_body'],
      'trigger' => 'exit',
      'frequency' => 'week',
      'form_name' => __('Exit help form', 'ppsgen'),
      'popup_name' => __('Exit help popup', 'ppsgen'),
    ]);
    if (empty($built['ok'])) {
      return $built;
    }
    return ['ok' => true, 'parts' => array_merge($parts, $built['parts']), 'notes' => []];
  }

  protected function buildReengage(array $play): array
  {
    $a = $play['answers'];
    $ecaModel = $this->getEcaModel();
    if (!$ecaModel || !method_exists($ecaModel, 'createNewsletter')) {
      return ['ok' => false, 'message' => __('Campaigns are switched off in Settings › Features.', 'ppsgen')];
    }
    $parts = ['tag_id' => $this->ensureTag($a['start_tag'])];
    $parts['list_id'] = $this->createTagList(sprintf(__('Past customers (%s)', 'ppsgen'), $a['start_tag']), $parts['tag_id']);
    $newsletterId = (int) $ecaModel->createNewsletter(sprintf(__('Win-back offer (%s)', 'ppsgen'), $a['start_tag']));
    if ($newsletterId <= 0) {
      return ['ok' => false, 'message' => __('The newsletter could not be created.', 'ppsgen')];
    }
    $steps = $ecaModel->getChainStep($newsletterId);
    $stepId = (int) ($steps[0]['id'] ?? 0);
    if ($stepId > 0) {
      $this->writeStep($ecaModel, $stepId, [
        'name' => __('Newsletter email', 'ppsgen'),
        'subject' => $a['offer_subject'],
        'body' => $a['offer_body'],
        'days' => 0,
        'button' => $a['link'] !== '' ? ['text' => __('See the offer', 'ppsgen'), 'url' => $a['link']] : null,
      ], 0);
    }
    $this->applyChainTemplate($newsletterId);
    if ($parts['list_id'] !== '' && method_exists($ecaModel, 'setNewsletterList')) {
      $ecaModel->setNewsletterList($newsletterId, $parts['list_id']);
    }
    $parts['newsletter_id'] = $newsletterId;
    return ['ok' => true, 'parts' => $parts, 'notes' => []];
  }

  //=====================================
  //       BUILDING BLOCKS
  //=====================================

  protected function newKey(): string
  {
    return strtolower(wp_generate_password(11, false, false));
  }

  protected function getBlueprint(): array
  {
    $file = __DIR__ . '/blueprint-enquiries.json';
    $data = file_exists($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($data) ? $data : [];
  }

  // The PRO model when the add-on is active (its limits differ), otherwise the Free one
  protected function getEcaModel()
  {
    $module = $this->frame->getModule('ecaExt') ?: $this->frame->getModule('eca');
    return $module ? $module->getModel() : null;
  }

  protected function automationLimitMessage(): string
  {
    return __('The automation could not be created. Please try again.', 'ppsgen');
  }

  protected function ensureTag(string $slug): int
  {
    global $wpdb;
    $slug = sanitize_title($slug);
    $id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug = %s", $slug));
    if ($id > 0) {
      return $id;
    }
    $now = current_time('mysql');
    $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags", ['slug' => $slug, 'order_index' => 0, 'created_at' => $now, 'updated_at' => $now]);
    return (int) $wpdb->insert_id;
  }

  protected function createTagList(string $name, int $tagId): string
  {
    $contact = $this->frame->getModule('contact');
    if (!$contact || !method_exists($contact->getModel(), 'saveSavedContactList')) {
      return '';
    }
    $result = $contact->getModel()->saveSavedContactList($name, ['filter_dynamic_tag_titles[]' => [(string) $tagId]]);
    return !empty($result['ok']) ? (string) $result['id'] : '';
  }

  // [['slug' => 'first_name', 'placeholder' => ..., 'required' => 0], ...] with email always included
  protected function fieldPlan(array $answers, array $optional): array
  {
    $plan = [];
    if (!empty($answers['ask_name']) && isset($optional['name'])) {
      $plan[] = ['slug' => 'first_name', 'placeholder' => $optional['name'], 'required' => 0];
    }
    $plan[] = ['slug' => 'email', 'placeholder' => __('Email', 'ppsgen'), 'required' => 1];
    if (!empty($answers['ask_phone']) && isset($optional['phone'])) {
      $plan[] = ['slug' => 'phone', 'placeholder' => $optional['phone'], 'required' => 0];
    }
    if (!empty($answers['ask_message']) && isset($optional['message'])) {
      $plan[] = ['slug' => 'additional_info', 'placeholder' => $optional['message'], 'required' => 0];
    }
    return $plan;
  }

  protected function getContactFieldIds(): array
  {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT slug, id, field_type FROM {$wpdb->prefix}ppsgen_contact_fields WHERE is_enabled = 1", ARRAY_A);
    $map = [];
    foreach ($rows ?: [] as $row) {
      $map[$row['slug']] = ['id' => (int) $row['id'], 'type' => (string) $row['field_type']];
    }
    return $map;
  }

  // Plays send with plain, neutral templates: "Simple letter" (with unsubscribe) for automations
  // and newsletters, "Simple notice" (no unsubscribe link) for form emails. Created once, then editable.
  protected function getDefaultEmailTemplateId(bool $withUnsubscribe = true): int
  {
    global $wpdb;
    $ecaModule = $this->frame->getModule('eca');
    if (!$ecaModule) {
      return 0;
    }
    $optionName = $withUnsubscribe ? 'plays_template_letter' : 'plays_template_notice';
    $store = $this->getStore();
    $id = (int) $store->getOption($optionName, 'plays', 0, 'dashboard');
    if ($id > 0 && $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_eca_templates WHERE id = %d", $id))) {
      return $id;
    }
    $design = (string) $wpdb->get_var("SELECT design FROM {$wpdb->prefix}ppsgen_eca_templates ORDER BY id ASC LIMIT 1");
    $now = current_time('mysql');
    $wpdb->insert("{$wpdb->prefix}ppsgen_eca_templates", [
      'name' => $withUnsubscribe ? __('Simple letter', 'ppsgen') : __('Simple notice', 'ppsgen'),
      'settings' => '[]',
      'design' => $design !== '' ? $design : '{}',
      'active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);
    $id = (int) $wpdb->insert_id;
    if ($id <= 0) {
      return (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_eca_templates WHERE active = 1 ORDER BY id ASC LIMIT 1");
    }
    $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $fields = [];
    $rows = [
      ['type' => 'title', 'settings' => ['title_text' => esc_html($site), 'title_tag' => 'h3', 'text_align' => 'center', 'font_size' => '20', 'font_color' => '#222222', 'padding_top' => '20', 'padding_bottom' => '10']],
      ['type' => 'email_content', 'settings' => ['padding_top' => '10', 'padding_bottom' => '10', 'padding_left' => '20', 'padding_right' => '20']],
    ];
    if ($withUnsubscribe) {
      $rows[] = ['type' => 'unsubscribe', 'settings' => ['unsubscribe_text' => __('Unsubscribe', 'ppsgen'), 'text_align' => 'center', 'font_size' => '12', 'font_color' => '#888888', 'padding_top' => '16', 'padding_bottom' => '16']];
    }
    foreach ($rows as $index => $row) {
      $rowKey = $this->newKey();
      $columnKey = $this->newKey();
      $elementKey = $this->newKey();
      $fields[$rowKey] = ['field_key' => $rowKey, 'field_type' => 'row', 'parent_key' => '', 'order_index' => (string) ($index + 1), 'width_select' => 'manual', 'width' => '100', 'width_unit' => '%'];
      $fields[$columnKey] = ['field_key' => $columnKey, 'field_type' => 'column', 'parent_key' => $rowKey, 'order_index' => '1', 'width_select' => 'manual', 'width' => '100', 'width_unit' => '%'];
      $fields[$elementKey] = array_merge(['field_key' => $elementKey, 'field_type' => $row['type'], 'parent_key' => $columnKey, 'order_index' => '1'], $row['settings']);
    }
    $ecaModule->getModel()->saveTemplateFields($id, $fields);
    $store->updateOption($optionName, 'plays', $id, 0, 'dashboard', 'json');
    return $id;
  }

  protected function textToHtml(string $text): string
  {
    $paragraphs = preg_split("/\n{2,}/", trim($text));
    $html = '';
    foreach ($paragraphs as $paragraph) {
      $html .= '<p>' . nl2br(esc_html(trim($paragraph))) . '</p>';
    }
    return $html;
  }

  // A popup (optional) with a heading, a line of text and a form; the form tags contacts and sends the emails
  protected function buildPopupWithForm(array $o): array
  {
    global $wpdb;
    $blueprint = $this->getBlueprint();
    if (empty($blueprint)) {
      return ['ok' => false, 'message' => __('The play blueprint is missing. Please reinstall the plugin.', 'ppsgen')];
    }
    $templateModule = $this->frame->getModule('template');
    if (!$templateModule) {
      return ['ok' => false, 'message' => __('Templates are switched off in Settings › Features.', 'ppsgen')];
    }
    $cfMap = $this->getContactFieldIds();
    $formFields = [];
    $order = 1;
    foreach ($o['fields'] as $plan) {
      if (!isset($cfMap[$plan['slug']])) {
        continue;
      }
      $key = $this->newKey();
      $cf = $cfMap[$plan['slug']];
      $field = array_merge($blueprint['form']['field_prototype'], [
        'field_key' => $key,
        'field_type' => $cf['type'],
        'field_id' => (string) $cf['id'],
        'field_slug' => $plan['slug'],
        'order_index' => (string) $order,
        'required' => (string) $plan['required'],
        'placeholder' => $plan['placeholder'],
        'hide_label' => '1',
        'icon_class' => $plan['slug'] === 'email' ? ($blueprint['form']['field_prototype']['icon_class'] ?? '') : '',
        'icon_position' => $plan['slug'] === 'email' ? ($blueprint['form']['field_prototype']['icon_position'] ?? '') : '',
      ]);
      $formFields[] = ['fkey' => $key, 'cf_id' => $cf['id'], 'settings' => wp_json_encode($field), 'options' => '[]', 'order_index' => $order];
      $order++;
    }
    $submitKey = $this->newKey();
    $submit = array_merge($blueprint['form']['submit_prototype'], ['field_key' => $submitKey, 'order_index' => (string) $order, 'submit_text' => $o['submit']]);
    $formFields[] = ['fkey' => $submitKey, 'cf_id' => 0, 'settings' => wp_json_encode($submit), 'options' => '[]', 'order_index' => $order];

    $ownerEmail = (string) ($o['owner_email'] ?? '');
    $formSettings = array_merge($blueprint['form']['settings'], [
      'form_logic' => 'add_submit',
      'form_logic_email_field' => 'email',
      'form_logic_update_fields' => ['email'],
      'contact_email_confirmation' => '0',
      'contact_handle_signup' => '0',
      'specific_tags' => array_map('strval', $o['tag_ids']),
      'send_admin' => $ownerEmail !== '' ? '1' : '0',
      'send_admin_email' => $ownerEmail,
      'send_admin_subject' => (string) ($o['owner_subject'] ?? ''),
      'html_send_admin_text' => '<p>' . esc_html__('New submission from your website.', 'ppsgen') . '</p>'
        . '<p>' . esc_html__('Name', 'ppsgen') . ': [first_name]<br>' . esc_html__('Email', 'ppsgen') . ': [email]<br>' . esc_html__('Phone', 'ppsgen') . ': [phone]</p>'
        . '<p>[additional_info]</p>',
      'send_contact' => !empty($o['contact_on']) ? '1' : '0',
      'send_contact_email_field' => 'email',
      'send_contact_subject' => (string) $o['contact_subject'],
      'html_send_contact_text' => $this->textToHtml((string) $o['contact_body']),
      'html_success_msg' => '<p style="text-align: center;">' . esc_html($o['success']) . '</p>',
      'template_id' => (string) $this->getDefaultEmailTemplateId(false),
      'redirect_link' => '',
    ]);
    $template = [
      'form' => ['form_type' => 'create_contact', 'settings' => wp_json_encode($formSettings), 'design' => wp_json_encode($blueprint['form']['design'])],
      'form_fields' => $formFields,
    ];

    if (($o['trigger'] ?? 'none') !== 'none') {
      $trigger = $o['trigger'];
      $popupSettings = array_merge($blueprint['popup']['settings'], [
        'show_on' => $trigger === 'scroll' ? 'scroll_window' : ($trigger === 'exit' ? 'on_exit' : 'page_load'),
        'show_on_page_load_delay' => $trigger === 'delay' ? '8' : '',
        'show_on_scroll_window_perc_scroll' => $trigger === 'scroll' ? '50' : '0',
        'show_pages' => 'all',
        'show_to' => 'everyone',
        'count_times_num' => '1',
        'count_times_mes' => $o['frequency'] ?? 'week',
        'close_on' => 'user_close',
        // Who sees it (see visitor.php)
        'audience' => $o['audience'] ?? 'everyone',
        'audience_tags' => array_map('strval', (array) ($o['audience_tags'] ?? [])),
      ]);
      $rootKey = $this->newKey();
      $popupFields = [];
      foreach ($blueprint['popup']['elements'] as $element) {
        $key = $element['field_key'] === 'root' ? $rootKey : $this->newKey();
        $element['field_key'] = $key;
        $element['parent_key'] = $element['parent_key'] === 'root' ? $rootKey : '';
        if ($element['field_type'] === 'title') {
          $element['title_text'] = $o['title'];
        } elseif ($element['field_type'] === 'textarea') {
          $element['html_textarea_content'] = $o['text'] !== '' ? '<p style="text-align: center;">' . esc_html($o['text']) . '</p>' : '';
        } elseif ($element['field_type'] === 'form') {
          // installTemplate swaps a non-empty form_id for the new form's id
          $element['form_id'] = '1';
        }
        $popupFields[] = ['fkey' => $key, 'parent_key' => $element['parent_key'], 'type' => $element['field_type'], 'settings' => wp_json_encode($element), 'order_index' => (int) $element['order_index']];
      }
      $template['popup'] = ['settings' => wp_json_encode($popupSettings), 'design' => wp_json_encode($blueprint['popup']['design'])];
      $template['popup_fields'] = $popupFields;
    }

    $formName = $o['form_name'] ?? sprintf(__('%s form', 'ppsgen'), ucfirst($o['label']));
    $installed = $templateModule->getModel()->installTemplate($template, $formName);
    if (empty($installed['form_id'])) {
      return ['ok' => false, 'message' => __('The form could not be created.', 'ppsgen')];
    }
    $parts = ['form_id' => (int) $installed['form_id']];
    // installTemplate links only the standard name/email/phone fields; link the message field too
    if (isset($cfMap['additional_info'])) {
      $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}ppsgen_form_fields SET cf_id = %d WHERE form_id = %d AND settings LIKE %s",
        $cfMap['additional_info']['id'],
        $parts['form_id'],
        '%"field_slug":"additional_info"%'
      ));
    }
    if (!empty($installed['popup_id'])) {
      $parts['popup_id'] = (int) $installed['popup_id'];
      $popupName = $o['popup_name'] ?? sprintf(__('%s popup', 'ppsgen'), ucfirst($o['label']));
      $wpdb->update("{$wpdb->prefix}ppsgen_popups", ['name' => $popupName, 'active' => 0], ['id' => $parts['popup_id']]);
    }
    return ['ok' => true, 'parts' => $parts];
  }

  // An automation (switched off) that starts when a contact gets one of the tags; stops for the stop tag
  protected function buildAutomation(string $name, array $tagIds, array $steps, int $stopTagId): array
  {
    global $wpdb;
    $ecaModel = $this->getEcaModel();
    if (!$ecaModel) {
      return ['chain_id' => 0, 'notes' => []];
    }
    // The Free automation limit applies: a second play with an automation meets the gateway
    if (!$ecaModel->canCreateChain()) {
      return ['chain_id' => 0, 'notes' => ['automation_limit']];
    }
    $chainId = (int) $ecaModel->createChain($name);
    if ($chainId <= 0) {
      return ['chain_id' => 0, 'notes' => []];
    }
    $templateId = $this->getDefaultEmailTemplateId();
    $chainSettings = [
      'name' => $name,
      'active' => 0,
      'template_id' => $templateId,
      'entry_tag_yes' => array_map('strval', $tagIds),
      'entry_tag_yes_condition' => 'or',
    ];
    $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", [
      'settings' => $ecaModel->_prepareValueToStore($chainSettings, 'json'),
      'template_id' => $templateId > 0 ? $templateId : 1,
      'active' => 0,
    ], ['id' => $chainId]);
    $notes = [];
    foreach ($steps as $index => $step) {
      // Free allows a few email steps in total; extra steps meet the same gateway
      if ($index > 0 && !$ecaModel->canCreateChainStep()) {
        $notes[] = 'step_limit';
        break;
      }
      $stepId = (int) $ecaModel->addNewChainStep($chainId, sprintf(__('Email %d', 'ppsgen'), $index + 1));
      if ($stepId > 0) {
        $this->writeStep($ecaModel, $stepId, array_merge(['name' => sprintf(__('Email %d', 'ppsgen'), $index + 1)], $step), $stopTagId);
      }
    }
    return ['chain_id' => $chainId, 'notes' => $notes];
  }

  // One email step: subject, delay, optional stop tag, and the content as row › column › text (› button)
  protected function writeStep($ecaModel, int $stepId, array $step, int $stopTagId): void
  {
    $options = [
      'name' => $step['name'],
      'active' => 1,
      'email_subject' => $step['subject'],
      'delay_unit' => 'days',
      'delay_time' => (int) $step['days'],
    ];
    if ($stopTagId > 0) {
      $options['tag_not'] = [(string) $stopTagId];
      $options['tag_not_condition'] = 'or';
      $options['on_false_condition'] = 'exit';
    }
    $ecaModel->saveChainStepOptions($stepId, ['chain' => ['step_options' => $options]]);
    $rowKey = $this->newKey();
    $columnKey = $this->newKey();
    $textKey = $this->newKey();
    $fields = [
      $rowKey => ['field_key' => $rowKey, 'field_type' => 'row', 'parent_key' => '', 'order_index' => '1', 'width_select' => 'manual', 'width' => '100', 'width_unit' => '%'],
      $columnKey => ['field_key' => $columnKey, 'field_type' => 'column', 'parent_key' => $rowKey, 'order_index' => '1', 'width_select' => 'manual', 'width' => '100', 'width_unit' => '%'],
      $textKey => ['field_key' => $textKey, 'field_type' => 'textarea', 'parent_key' => $columnKey, 'order_index' => '1', 'html_textarea_content' => $this->textToHtml($step['body'])],
    ];
    if (!empty($step['button']['url'])) {
      $buttonKey = $this->newKey();
      $fields[$buttonKey] = [
        'field_key' => $buttonKey,
        'field_type' => 'button',
        'parent_key' => $columnKey,
        'order_index' => '2',
        'button_text' => $step['button']['text'],
        'button_url' => $step['button']['url'],
        'button_target' => '_blank',
        'text_align' => 'center',
        'background' => '#3f51b5',
        'font_color' => '#ffffff',
        'font_weight' => '600',
        'padding_top' => '12',
        'padding_bottom' => '12',
        'padding_left' => '20',
        'padding_right' => '20',
        'margin_top' => '16',
        'border_radius' => '6',
        'width_select' => 'auto',
      ];
    }
    $ecaModel->saveChainStepFields($stepId, ['chain' => ['step_fields' => $fields]]);
  }

  protected function applyChainTemplate(int $chainId): void
  {
    global $wpdb;
    $templateId = $this->getDefaultEmailTemplateId();
    if ($templateId > 0) {
      $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", ['template_id' => $templateId], ['id' => $chainId]);
      $ecaModel = $this->getEcaModel();
      foreach ((array) $ecaModel->getChainStep($chainId) as $step) {
        $ecaModel->generateStepHtmlOnSaving((int) $step['id'], true);
      }
    }
  }

  //=====================================
  //       LIVE / PAUSE
  //=====================================

  // The running automation this play's follow-up would replace on Free, if any
  public function getAutomationConflict(array $play): array
  {
    $chainId = (int) ($play['parts']['chain_id'] ?? 0);
    return $chainId > 0 ? $this->getEcaModel()->getAutomationConflict($chainId) : [];
  }

  public function setLive(string $id, bool $live, bool $replace = false): array
  {
    global $wpdb;
    $play = $this->getPlay($id);
    if (empty($play) || ($play['status'] ?? 'draft') === 'draft') {
      return ['ok' => false, 'message' => __('Build the play first.', 'ppsgen')];
    }
    $parts = $play['parts'] ?? [];
    $active = $live ? 1 : 0;
    // Free runs 1 automation at a time: offer to switch the running one off
    if ($live && !empty($parts['chain_id'])) {
      $ecaModel = $this->getEcaModel();
      $conflict = $ecaModel->getAutomationConflict((int) $parts['chain_id']);
      if (!empty($conflict) && !$replace) {
        if (function_exists('ppsgen_track_gateway')) {
          ppsgen_track_gateway('second_automation');
        }
        return [
          'ok' => false,
          'conflict' => $conflict,
          'message' => sprintf(ppsgen_automation_limit_text('conflict_play'), $conflict['name']),
        ];
      }
      if (!empty($conflict)) {
        foreach ($ecaModel->getRunningAutomations() as $row) {
          if ((int) $row['id'] !== (int) $parts['chain_id']) {
            $ecaModel->setAutomationActive((int) $row['id'], false);
            $other = $this->getPlayByPart('chain', (int) $row['id']);
            if (!empty($other) && ($other['status'] ?? '') === 'live') {
              $other['status'] = 'paused';
              $this->savePlay($other);
            }
          }
        }
      }
    }
    if (!empty($parts['popup_id'])) {
      $wpdb->update("{$wpdb->prefix}ppsgen_popups", ['active' => $active], ['id' => (int) $parts['popup_id']]);
    }
    if (!empty($parts['form_id'])) {
      // The form stays usable on pages with its shortcode
      $wpdb->update("{$wpdb->prefix}ppsgen_forms", ['active' => 1], ['id' => (int) $parts['form_id']]);
    }
    if (!empty($parts['chain_id'])) {
      $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", ['active' => $active], ['id' => (int) $parts['chain_id']]);
      $wpdb->update("{$wpdb->prefix}ppsgen_eca_flow", ['active' => $active], ['chain_id' => (int) $parts['chain_id']]);
      // Keep the chain's own "enabled" setting in step with the switch
      $settings = json_decode((string) $wpdb->get_var($wpdb->prepare("SELECT settings FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", (int) $parts['chain_id'])), true);
      if (is_array($settings)) {
        $settings['active'] = $active;
        $wpdb->update("{$wpdb->prefix}ppsgen_eca_chains", ['settings' => wp_json_encode($settings)], ['id' => (int) $parts['chain_id']]);
      }
    }
    // A newsletter is never sent by the switch: it is sent from its own editor
    $play['status'] = $live ? 'live' : 'paused';
    if ($live && empty($play['live_at'])) {
      $play['live_at'] = current_time('timestamp');
    }
    $this->savePlay($play);
    return ['ok' => true, 'message' => $live ? __('It\'s live.', 'ppsgen') : __('Paused. Nothing was deleted.', 'ppsgen')];
  }

  //=====================================
  //       RESULTS
  //=====================================

  protected function getTagSlug(int $tagId): string
  {
    global $wpdb;
    return (string) $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$wpdb->prefix}ppsgen_contact_tags WHERE id = %d", $tagId));
  }

  public function getParts(array $play): array
  {
    global $wpdb;
    $parts = $play['parts'] ?? [];
    $a = $play['answers'] ?? [];
    $definition = $this->getDefinition($play['type'] ?? '');
    $list = [];
    if (!empty($parts['popup_id'])) {
      $popup = $wpdb->get_row($wpdb->prepare("SELECT id, name, settings FROM {$wpdb->prefix}ppsgen_popups WHERE id = %d", (int) $parts['popup_id']), ARRAY_A);
      if ($popup) {
        $triggerText = [
          'delay' => __('After 8 seconds', 'ppsgen'),
          'scroll' => __('Halfway down the page', 'ppsgen'),
          'exit' => __('When they\'re about to leave', 'ppsgen'),
        ][$a['trigger'] ?? 'exit'] ?? '';
        $popupSettings = json_decode((string) $popup['settings'], true);
        $audience = is_array($popupSettings) ? (string) ($popupSettings['audience'] ?? 'everyone') : 'everyone';
        $audienceText = $audience !== 'everyone' && $audience !== '' && function_exists('ppsgen_get_audience_rules') ? ' · ' . (ppsgen_get_audience_rules()[$audience] ?? '') : '';
        $list[] = ['type' => __('Popup', 'ppsgen'), 'name' => $popup['name'], 'text' => sprintf(__('%1$s · once a %2$s per visitor · all pages', 'ppsgen'), $triggerText, $a['frequency'] ?? 'week') . $audienceText, 'url' => admin_url('admin.php?page=ppsgen&tab=popup_edit&id=' . (int) $popup['id'])];
      }
    }
    if (!empty($parts['form_id'])) {
      $form = $wpdb->get_row($wpdb->prepare("SELECT id, name, settings FROM {$wpdb->prefix}ppsgen_forms WHERE id = %d", (int) $parts['form_id']), ARRAY_A);
      if ($form) {
        $settings = json_decode((string) $form['settings'], true) ?: [];
        $list[] = ['type' => __('Form', 'ppsgen'), 'name' => $form['name'], 'text' => sprintf(__('Also on any page with %s', 'ppsgen'), '[supsystic-ces-form id="' . (int) $form['id'] . '"]'), 'url' => admin_url('admin.php?page=ppsgen&tab=form_edit&id=' . (int) $form['id'])];
        $optionsUrl = admin_url('admin.php?page=ppsgen&tab=form_edit&id=' . (int) $form['id'] . '&obj_edit_active_tab=options');
        if (!empty($settings['send_admin']) && !empty($settings['send_admin_email'])) {
          $list[] = ['type' => __('Email', 'ppsgen'), 'name' => sprintf(__('Alert → %s', 'ppsgen'), $settings['send_admin_email']), 'text' => __('Sent to you the moment someone submits', 'ppsgen'), 'url' => $optionsUrl];
        }
        if (!empty($settings['send_contact'])) {
          $list[] = ['type' => __('Email', 'ppsgen'), 'name' => (string) ($settings['send_contact_subject'] ?? ''), 'text' => __('Sent to them straight away', 'ppsgen'), 'url' => $optionsUrl];
        }
      }
    }
    if (!empty($parts['chain_id'])) {
      $chain = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", (int) $parts['chain_id']), ARRAY_A);
      if ($chain) {
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_chain_step WHERE chain_id = %d", (int) $chain['id']));
        $text = sprintf(_n('%d email', '%d emails', $count, 'ppsgen'), $count);
        if (!empty($parts['tag_id'])) {
          $text .= ' · ' . sprintf(__('starts when a contact gets the tag %s', 'ppsgen'), $this->getTagSlug((int) $parts['tag_id']));
        }
        if (!empty($definition['stop'])) {
          $text .= ' · ' . sprintf(__('stops for %s', 'ppsgen'), $definition['stop']['done']);
        }
        $list[] = ['type' => __('Automation', 'ppsgen'), 'name' => $chain['name'], 'text' => $text, 'url' => admin_url('admin.php?page=ppsgen&tab=eca_chain_edit&id=' . (int) $chain['id'])];
      }
    }
    if (!empty($parts['newsletter_id'])) {
      $newsletter = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}ppsgen_eca_chains WHERE id = %d", (int) $parts['newsletter_id']), ARRAY_A);
      if ($newsletter) {
        $list[] = ['type' => __('Newsletter', 'ppsgen'), 'name' => $newsletter['name'], 'text' => __('Not sent yet. Send a test, then send it now or later.', 'ppsgen'), 'url' => admin_url('admin.php?page=ppsgen&tab=eca_chain_edit&id=' . (int) $newsletter['id'])];
      }
    }
    if (!empty($parts['list_id'])) {
      $contact = $this->frame->getModule('contact');
      $savedList = $contact && method_exists($contact->getModel(), 'getSavedContactList') ? $contact->getModel()->getSavedContactList((string) $parts['list_id']) : [];
      if (!empty($savedList)) {
        $list[] = ['type' => __('List', 'ppsgen'), 'name' => $savedList['name'], 'text' => __('Updates itself from the tag', 'ppsgen'), 'url' => admin_url('admin.php?page=ppsgen&tab=contact_list')];
      }
    }
    if (!empty($parts['tag_id'])) {
      $list[] = ['type' => __('Tag', 'ppsgen'), 'name' => $this->getTagSlug((int) $parts['tag_id']), 'text' => !empty($parts['form_id']) ? __('Added to everyone who submits', 'ppsgen') : __('You add it to contacts', 'ppsgen'), 'url' => ''];
    }
    return $list;
  }

  // Up to four numbers for the play summary, from the parts the play has
  public function getStats(array $play): array
  {
    global $wpdb;
    $parts = $play['parts'] ?? [];
    $definition = $this->getDefinition($play['type'] ?? '');
    $since = date('Y-m-d H:i:s', current_time('timestamp') - 7 * DAY_IN_SECONDS);
    $cards = [];
    $primary = 0;
    if (!empty($parts['popup_id']) && $this->frame->getModule('popup')) {
      $summary = $this->frame->getModule('popup')->getModel()->getPopupStatsSummaryByIds([(int) $parts['popup_id']]);
      $popupStats = $summary[(int) $parts['popup_id']] ?? [];
      $unique = (int) ($popupStats['unique_views'] ?? 0);
      $fromPopup = (int) ($popupStats['form_submits'] ?? 0);
      $cards[] = ['label' => __('Popup seen', 'ppsgen'), 'value' => number_format_i18n((int) ($popupStats['total_views'] ?? 0)), 'sub' => __('all time', 'ppsgen')];
      $cards[] = ['label' => __('Conversion', 'ppsgen'), 'value' => $unique > 0 ? number_format_i18n($fromPopup / $unique * 100, 1) . '%' : '—', 'sub' => __('popup submissions ÷ visitors who saw it', 'ppsgen')];
    }
    if (!empty($parts['form_id'])) {
      $primary = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_form_submits WHERE form_id = %d AND created_date >= %s", (int) $parts['form_id'], $since));
      $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_form_submits WHERE form_id = %d", (int) $parts['form_id']));
      array_splice($cards, min(1, count($cards)), 0, [['label' => $definition['people'] ?? __('Submissions', 'ppsgen'), 'value' => number_format_i18n($primary), 'sub' => sprintf(__('last 7 days · %s in total', 'ppsgen'), number_format_i18n($total))]]);
    }
    if (!empty($parts['chain_id']) && $this->getEcaModel()) {
      $chainStats = $this->getEcaModel()->getChainStats((int) $parts['chain_id']);
      $inIt = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d", (int) $parts['chain_id']));
      if (empty($parts['form_id'])) {
        $primary = $inIt;
        $cards[] = ['label' => __('People in it', 'ppsgen'), 'value' => number_format_i18n($inIt), 'sub' => sprintf(__('%s finished', 'ppsgen'), number_format_i18n((int) ($chainStats['finished'] ?? 0)))];
      }
      $cards[] = ['label' => __('Emails sent', 'ppsgen'), 'value' => number_format_i18n((int) ($chainStats['total_letters'] ?? 0)), 'sub' => sprintf(__('%s opened', 'ppsgen'), number_format_i18n(max(0, (int) ($chainStats['unique_event_open'] ?? 0) - (int) ($chainStats['suspicious_unique_event_open'] ?? 0))))];
    }
    if (!empty($parts['newsletter_id']) && $this->getEcaModel() && method_exists($this->getEcaModel(), 'getNewsletterState')) {
      $state = $this->getEcaModel()->getNewsletterState((int) $parts['newsletter_id']);
      $primary = (int) $state['recipients'];
      $cards[] = ['label' => __('Recipients', 'ppsgen'), 'value' => number_format_i18n((int) $state['recipients']), 'sub' => $state['status'] === 'draft' ? sprintf(__('about %s on the list', 'ppsgen'), number_format_i18n($this->getEcaModel()->countNewsletterAudience((int) $parts['newsletter_id']))) : ucfirst($state['status'])];
      $cards[] = ['label' => __('Opened', 'ppsgen'), 'value' => number_format_i18n((float) $state['opened_rate'], 1) . '%', 'sub' => __('of recipients', 'ppsgen')];
      $cards[] = ['label' => __('Clicked', 'ppsgen'), 'value' => number_format_i18n((float) $state['clicked_rate'], 1) . '%', 'sub' => __('of recipients', 'ppsgen')];
    }
    if (!empty($definition['stop']) && !empty($parts['stop_tag_id']) && !empty($parts['tag_id'])) {
      $stopped = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT a.contact_id) FROM {$wpdb->prefix}ppsgen_contact_to_tags a
        INNER JOIN {$wpdb->prefix}ppsgen_contact_to_tags b ON b.contact_id = a.contact_id AND b.tag_id = %d
        WHERE a.tag_id = %d",
        (int) $parts['stop_tag_id'],
        (int) $parts['tag_id']
      ));
      $cards[] = ['label' => $definition['stop']['done'], 'value' => number_format_i18n($stopped), 'sub' => __('marked by you', 'ppsgen')];
    }
    return ['cards' => array_slice($cards, 0, 4), 'primary' => $primary];
  }

  protected function contactName(int $contactId): array
  {
    $contact = $this->frame->getModule('contact');
    if (!$contact || $contactId <= 0) {
      return ['name' => '', 'email' => ''];
    }
    $model = $contact->getModel();
    return [
      'name' => trim($model->getContactFieldValueBySlug($contactId, 'first_name') . ' ' . $model->getContactFieldValueBySlug($contactId, 'last_name')),
      'email' => $model->getContactFieldValueBySlug($contactId, 'email'),
    ];
  }

  // The people a play is working with: form submissions, or the people in its automation
  public function getPeople(array $play, int $limit = 20): array
  {
    global $wpdb;
    $parts = $play['parts'] ?? [];
    $now = current_time('timestamp');
    $stopTag = (int) ($parts['stop_tag_id'] ?? 0);
    $rows = [];
    if (!empty($parts['form_id'])) {
      foreach ((array) $wpdb->get_results($wpdb->prepare(
        "SELECT contact_id, fields_values, created_date FROM {$wpdb->prefix}ppsgen_form_submits WHERE form_id = %d ORDER BY id DESC LIMIT %d",
        (int) $parts['form_id'],
        $limit
      ), ARRAY_A) as $row) {
        $values = json_decode((string) $row['fields_values'], true);
        $values = is_array($values) ? $values : [];
        $rows[] = [
          'contact_id' => (int) $row['contact_id'],
          'name' => trim((string) ($values['first_name'] ?? '')),
          'email' => (string) ($values['email'] ?? ''),
          'phone' => (string) ($values['phone'] ?? ''),
          'message' => wp_trim_words((string) ($values['additional_info'] ?? ''), 10),
          'time' => strtotime($row['created_date']),
        ];
      }
    } elseif (!empty($parts['chain_id'])) {
      foreach ((array) $wpdb->get_results($wpdb->prepare(
        "SELECT contact_id, created_at FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d ORDER BY id DESC LIMIT %d",
        (int) $parts['chain_id'],
        $limit
      ), ARRAY_A) as $row) {
        $who = $this->contactName((int) $row['contact_id']);
        $rows[] = ['contact_id' => (int) $row['contact_id'], 'name' => $who['name'], 'email' => $who['email'], 'phone' => '', 'message' => '', 'time' => strtotime($row['created_at'])];
      }
    }
    foreach ($rows as $index => $row) {
      $status = '';
      $due = '';
      $contactId = $row['contact_id'];
      if ($stopTag > 0 && $contactId > 0 && $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id = %d", $contactId, $stopTag))) {
        $status = 'stopped';
      } elseif (!empty($parts['chain_id']) && $contactId > 0) {
        $flow = $wpdb->get_row($wpdb->prepare(
          "SELECT status, next_run_at FROM {$wpdb->prefix}ppsgen_eca_flow WHERE chain_id = %d AND contact_id = %d ORDER BY id DESC LIMIT 1",
          (int) $parts['chain_id'],
          $contactId
        ), ARRAY_A);
        if (!empty($flow)) {
          $status = $flow['status'] === 'active' ? 'due' : 'done';
          $dueAt = !empty($flow['next_run_at']) ? strtotime((string) $flow['next_run_at']) : 0;
          $due = $dueAt > $now ? sprintf(__('in %s', 'ppsgen'), human_time_diff($now, $dueAt)) : '';
        }
      }
      $rows[$index]['status'] = $status;
      $rows[$index]['due'] = $due;
      $rows[$index]['when'] = sprintf(__('%s ago', 'ppsgen'), human_time_diff((int) $row['time'], $now));
    }
    return $rows;
  }

  // "Mark replied" / "Mark reviewed": the automation checks this tag and stops for this person
  public function markStopped(string $id, int $contactId): array
  {
    $play = $this->getPlay($id);
    $tagId = (int) ($play['parts']['stop_tag_id'] ?? 0);
    if ($tagId <= 0 || $contactId <= 0 || !$this->frame->getModule('contact')) {
      return ['ok' => false, 'message' => __('Contact not found.', 'ppsgen')];
    }
    $this->frame->getModule('contact')->getModel()->addContactTags([$tagId], $contactId, 'admin');
    $done = $this->getDefinition($play['type'])['stop']['done'] ?? __('Done', 'ppsgen');
    return ['ok' => true, 'message' => sprintf(__('Marked: %s. No more emails from this play will go to this person.', 'ppsgen'), $done), 'label' => $done];
  }

  // Existing tag slugs for the "which tag" questions
  public function getExistingTags(): array
  {
    global $wpdb;
    return array_map('strval', (array) $wpdb->get_col("SELECT slug FROM {$wpdb->prefix}ppsgen_contact_tags ORDER BY slug ASC LIMIT 200"));
  }
}
