<?php

if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/converter.php';

/**
 * Moves forms from Contact Form by Supsystic into CES. Everything is read straight from the Contact Form tables,
 * so the move works with Contact Form switched off or removed. The Contact Form tables are only read, never written.
 *
 * What was moved is kept in the option ppsgen_cf_import: forms => [Contact Form id => [ces_id, at, submits => [old id => new id]]],
 * so a second run never makes a copy. Shortcode replacements are kept in ppsgen_cf_import_shortcodes to be undone.
 */
class CfImportSubmoduleModelPpsgen extends BaseModelPpsgen
{
  const OPTION = 'ppsgen_cf_import';
  const SHORTCODES_OPTION = 'ppsgen_cf_import_shortcodes';
  const TAG = 'contact-form';
  const CF_SHORTCODE = 'supsystic-form';
  const CES_SHORTCODE = 'supsystic-ces-form';
  const ASSETS_DIR = 'ppsgen-cf-import';

  protected $converter;
  protected $createdContactFields = [];
  protected $tagIdCache = null;

  public function installTables() {}

  /* ---------------------------------------------------------------- state */

  public function getState(): array
  {
    $state = get_option(self::OPTION, []);
    $state = is_array($state) ? $state : [];
    $state['forms'] = isset($state['forms']) && is_array($state['forms']) ? $state['forms'] : [];
    return $state;
  }

  protected function saveState(array $state): void
  {
    update_option(self::OPTION, $state, false);
  }

  /** @return array Contact Form id => CES id of the forms that were moved and still exist in CES */
  public function getImportedMap(): array
  {
    global $wpdb;
    $map = [];
    foreach ($this->getState()['forms'] as $cfId => $info) {
      $cesId = (int) ($info['ces_id'] ?? 0);
      if ($cesId > 0 && $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_forms WHERE id = %d", $cesId))) {
        $map[(int) $cfId] = $cesId;
      }
    }
    return $map;
  }

  /* ---------------------------------------------------------------- source */

  public function sourceExists(): bool
  {
    global $wpdb;
    $table = $wpdb->prefix . 'cfs_forms';
    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table;
  }

  public function isSourceActive(): bool
  {
    if (!function_exists('is_plugin_active')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    return is_plugin_active('contact-form-by-supsystic/cfs.php');
  }

  protected function contactsTableExists(): bool
  {
    global $wpdb;
    $table = $wpdb->prefix . 'cfs_contacts';
    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table;
  }

  /** Links Contact Form keeps in its data as tags (forms model _getLinksReplacement) */
  public function getCfUrls(): array
  {
    $plugins = plugins_url();
    return [
      '[CFS_MOD_URL]' => $plugins . '/contact-form-by-supsystic/modules/forms/',
      '[CFS_SITE_URL]' => get_bloginfo('wpurl') . '/',
      '[CFS_ASSETS_URL]' => $plugins . '/contact-form-by-supsystic/assets/forms/',
    ];
  }

  /** The forms people made (templates have original_id 0, A/B test variants an ab_id) */
  public function getSourceRows(array $ids = []): array
  {
    global $wpdb;
    if (!$this->sourceExists()) {
      return [];
    }
    $where = 'original_id != 0 AND ab_id = 0';
    if (!empty($ids)) {
      $ids = array_values(array_filter(array_map('intval', $ids)));
      if (empty($ids)) {
        return [];
      }
      $where .= ' AND id IN (' . implode(',', $ids) . ')';
    }
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- ids are integers
    $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cfs_forms WHERE {$where} ORDER BY id ASC", ARRAY_A);
    $urls = $this->getCfUrls();
    $forms = [];
    foreach ((array) $rows as $row) {
      $forms[(int) $row['id']] = CfImportConverterPpsgen::readRow($row, $urls);
    }
    return $forms;
  }

  protected function countSubmissions(): array
  {
    global $wpdb;
    if (!$this->contactsTableExists()) {
      return [];
    }
    $counts = [];
    foreach ((array) $wpdb->get_results("SELECT form_id, COUNT(*) AS c FROM {$wpdb->prefix}cfs_contacts GROUP BY form_id", ARRAY_A) as $row) {
      $counts[(int) $row['form_id']] = (int) $row['c'];
    }
    return $counts;
  }

  protected function countAbVariants(): int
  {
    global $wpdb;
    return $this->sourceExists() ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cfs_forms WHERE original_id != 0 AND ab_id != 0") : 0;
  }

  public function getConverter(): CfImportConverterPpsgen
  {
    if ($this->converter === null) {
      $this->converter = new CfImportConverterPpsgen($this->getConverterEnv(), [$this, 'resolveContactField']);
    }
    return $this->converter;
  }

  protected function isCesPro(): bool
  {
    return function_exists('ppsgen_is_pro_license_active') && ppsgen_is_pro_license_active() && class_exists('FrameProPpsgen');
  }

  protected function getConverterEnv(): array
  {
    global $wpdb;
    $countries = [];
    $table = $wpdb->prefix . 'cfs_countries';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) {
      foreach ((array) $wpdb->get_results("SELECT id, name FROM {$table} ORDER BY name ASC", ARRAY_A) as $c) {
        $countries[(int) $c['id']] = (string) $c['name'];
      }
    }
    $categories = [];
    foreach ((array) get_categories(['hide_empty' => false]) as $term) {
      $categories[(int) $term->term_id] = (string) $term->name;
    }
    return [
      'isPro' => $this->isCesPro(),
      'countries' => $countries,
      'categories' => $categories,
      'cfUrls' => $this->getCfUrls(),
      'copyAsset' => [$this, 'copyAsset'],
      'senders' => $this->getSenderEmails(),
    ];
  }

  /** The addresses CES sends from: the site's admin email and the From of each mail configuration */
  protected function getSenderEmails(): array
  {
    global $wpdb;
    $emails = [(string) get_bloginfo('admin_email')];
    $table = $wpdb->prefix . 'ppsgen_mail_configs';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) {
      foreach ((array) $wpdb->get_col("SELECT settings FROM {$table}") as $raw) {
        $settings = json_decode((string) $raw, true);
        if (!empty($settings['mail_from_email'])) {
          $emails[] = (string) $settings['mail_from_email'];
        }
      }
    }
    return array_values(array_unique(array_filter($emails)));
  }

  /* ---------------------------------------------------------------- the list on the page */

  public function scan(): array
  {
    $converter = $this->getConverter();
    $imported = $this->getImportedMap();
    $state = $this->getState();
    $submissions = $this->countSubmissions();
    $usage = $this->findShortcodeUsage();
    $forms = [];
    foreach ($this->getSourceRows() as $id => $row) {
      $info = $converter->describe($row);
      $cesId = $imported[$id] ?? 0;
      $movedSubmits = $cesId ? count((array) ($state['forms'][$id]['submits'] ?? [])) : 0;
      $forms[] = [
        'id' => $id,
        'name' => trim((string) $row['label']) !== '' ? (string) $row['label'] : '#' . $id,
        'active' => !empty($row['active']),
        'template' => $info['template'],
        'fields' => $info['fields'],
        'pro' => $info['pro'],
        'submissions' => (int) ($submissions[$id] ?? 0),
        'newSubmissions' => $cesId ? max(0, (int) ($submissions[$id] ?? 0) - $movedSubmits) : 0,
        'pages' => array_values($usage[$id] ?? []),
        'cesId' => $cesId,
        'editUrl' => $cesId ? admin_url('admin.php?page=ppsgen&tab=form_edit&id=' . $cesId) : '',
        'cfShortcode' => '[' . self::CF_SHORTCODE . ' id=' . $id . ']',
        'cesShortcode' => $cesId ? '[' . self::CES_SHORTCODE . ' id=' . $cesId . ']' : '',
        'report' => $cesId ? ($state['forms'][$id]['report'] ?? null) : null,
      ];
    }
    return [
      'sourceExists' => $this->sourceExists(),
      'sourceActive' => $this->isSourceActive(),
      'forms' => $forms,
      'abVariants' => $this->countAbVariants(),
      'popups' => $this->countClassicPopupsWithForms(),
      'widgets' => $this->countWidgets(),
      'shortcodes' => $this->getShortcodePlan(),
      'limit' => function_exists('ppsgen_get_contact_limit_status') ? ppsgen_get_contact_limit_status() : ['applies' => false],
      'isPro' => $this->isCesPro(),
    ];
  }

  /** Classic PopUp popups that show a Contact Form form inside (they keep using Contact Form) */
  protected function countClassicPopupsWithForms(): int
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pps_popup';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) {
      return 0;
    }
    $count = 0;
    foreach ((array) $wpdb->get_col("SELECT params FROM {$table} WHERE original_id != 0") as $raw) {
      $params = @unserialize((string) base64_decode((string) $raw, true), ['allowed_classes' => false]);
      if (is_array($params) && !empty($params['tpl']['enb_contact_form']) && !empty($params['tpl']['contact_form'])) {
        $count++;
      }
    }
    return $count;
  }

  protected function countWidgets(): int
  {
    $widgets = get_option('widget_formswidgetwpcfs', []);
    $count = 0;
    foreach ((array) $widgets as $key => $widget) {
      if (is_numeric($key) && is_array($widget)) {
        $count++;
      }
    }
    return $count;
  }

  /* ---------------------------------------------------------------- contact fields */

  /**
   * The contact field a moved field writes to. A built-in field (email, names, phone) is used as it is; another
   * field is created with the name of the Contact Form field, or reused when it is the same kind of field
   * (for example "message" of a second moved form).
   * @return array slug, id
   */
  public function resolveContactField(array $want): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'ppsgen_contact_fields';
    $slug = (string) $want['slug'];
    if (!empty($want['builtin'])) {
      $row = $wpdb->get_row($wpdb->prepare("SELECT id, slug FROM {$table} WHERE slug = %s", $slug), ARRAY_A);
      if ($row) {
        return ['slug' => $row['slug'], 'id' => (int) $row['id']];
      }
    }
    $taken = array_flip((array) ($want['taken'] ?? []));
    $base = $slug;
    for ($n = 1; $n < 50; $n++) {
      $try = $n === 1 ? $base : $base . '_' . $n;
      if (isset($taken[$try])) {
        continue;
      }
      $row = $wpdb->get_row($wpdb->prepare("SELECT id, slug, field_type, options FROM {$table} WHERE slug = %s", $try), ARRAY_A);
      if (!$row) {
        $now = current_time('mysql');
        $settings = ['required' => '0', 'placeholder' => '', 'show_tooltip' => '0', 'title' => ''];
        if ($want['field_type'] === 'textarea') {
          $settings['max_length_textarea'] = '';
        }
        $ok = $wpdb->insert($table, [
          'slug' => $try,
          'label' => mb_substr((string) $want['label'], 0, 250),
          'field_type' => $want['field_type'],
          'is_enabled' => 1,
          'is_default' => 0,
          'only_form' => 0,
          'options' => !empty($want['options']) ? wp_json_encode($want['options'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
          'settings' => wp_json_encode($settings),
          'order_index' => 100,
          'created_at' => $now,
          'updated_at' => $now,
        ]);
        if (!$ok) {
          return ['slug' => '', 'id' => 0];
        }
        $id = (int) $wpdb->insert_id;
        $this->createdContactFields[] = $id;
        return ['slug' => $try, 'id' => $id];
      }
      // The same kind of field with the same choices can be shared
      if ((string) $row['field_type'] === (string) $want['field_type'] && $this->sameOptions((string) $row['options'], (array) $want['options'])) {
        return ['slug' => $row['slug'], 'id' => (int) $row['id']];
      }
    }
    return ['slug' => '', 'id' => 0];
  }

  protected function sameOptions(string $stored, array $wanted): bool
  {
    $have = json_decode($stored, true);
    $values = function ($opts) {
      $out = [];
      foreach ((array) $opts as $opt) {
        if (is_array($opt) && array_key_exists('value', $opt)) {
          $out[] = (string) $opt['value'];
        }
      }
      sort($out);
      return $out;
    };
    return $values($have) === $values($wanted);
  }

  protected function getTagId(): int
  {
    if ($this->tagIdCache !== null) {
      return $this->tagIdCache;
    }
    global $wpdb;
    $id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_tags WHERE slug = %s", self::TAG));
    if (!$id) {
      $now = current_time('mysql');
      $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags", ['slug' => self::TAG, 'order_index' => 0, 'created_at' => $now, 'updated_at' => $now]);
      $id = (int) $wpdb->insert_id;
    }
    return $this->tagIdCache = $id;
  }

  /* ---------------------------------------------------------------- assets */

  /** A file of the Contact Form plugin (template image, font) is copied to uploads/ppsgen-cf-import */
  public function copyAsset(string $url): string
  {
    $pluginsUrl = plugins_url();
    $path = '';
    foreach (['contact-form-by-supsystic', 'contact-form-by-supsystic-pro'] as $folder) {
      $prefix = $pluginsUrl . '/' . $folder . '/';
      $plain = preg_replace('#^https?:#i', '', $url);
      $plainPrefix = preg_replace('#^https?:#i', '', $prefix);
      if (strpos($plain, $plainPrefix) === 0) {
        $path = $folder . '/' . substr($plain, strlen($plainPrefix));
        break;
      }
    }
    if ($path === '') {
      return $url;
    }
    $path = preg_replace('/[?#].*$/', '', $path);
    if (strpos($path, '..') !== false || !preg_match('/\.(png|jpe?g|gif|webp|svg|woff2?|ttf|eot|otf)$/i', $path)) {
      return $url;
    }
    $uploads = wp_upload_dir();
    if (!empty($uploads['error'])) {
      return $url;
    }
    $source = WP_PLUGIN_DIR . '/' . $path;
    $target = trailingslashit($uploads['basedir']) . self::ASSETS_DIR . '/' . $path;
    $targetUrl = trailingslashit($uploads['baseurl']) . self::ASSETS_DIR . '/' . $path;
    if (file_exists($target)) {
      return $targetUrl;
    }
    if (!is_file($source) || !wp_mkdir_p(dirname($target)) || !@copy($source, $target)) {
      return $url;
    }
    return $targetUrl;
  }

  /* ---------------------------------------------------------------- import */

  /**
   * @param int[] $ids     Contact Form form ids (empty: all)
   * @param array $options submissions, contacts
   * @return array results per form, totals
   */
  public function import(array $ids, array $options): array
  {
    $options = wp_parse_args($options, ['submissions' => true, 'contacts' => true]);
    $rows = $this->getSourceRows($ids);
    $imported = $this->getImportedMap();
    $results = [];
    $totals = ['forms' => 0, 'skipped' => 0, 'submissions' => 0, 'contacts' => 0, 'limited' => 0];
    foreach ($rows as $cfId => $row) {
      try {
        if (!empty($imported[$cfId])) {
          $res = ['id' => $cfId, 'name' => (string) $row['label'], 'status' => 'exists', 'cesId' => $imported[$cfId]];
          $totals['skipped']++;
        } else {
          $res = $this->importForm($row, $options);
          $totals['forms']++;
        }
        if (!empty($options['submissions'])) {
          $sub = $this->importSubmissions($cfId, (int) $res['cesId'], !empty($options['contacts']));
          $res['submissions'] = $sub;
          $totals['submissions'] += $sub['moved'];
          $totals['contacts'] += $sub['contacts'];
          $totals['limited'] += $sub['limited'];
        }
        $res['editUrl'] = admin_url('admin.php?page=ppsgen&tab=form_edit&id=' . (int) $res['cesId']);
        // The look is picked in the editor: the Design tab opens with the colour styles at its top
        $res['designUrl'] = admin_url('admin.php?page=ppsgen&tab=form_edit&id=' . (int) $res['cesId'] . '&obj_edit_active_tab=design');
        $res['shortcode'] = '[' . self::CES_SHORTCODE . ' id=' . (int) $res['cesId'] . ']';
        if ($res['status'] === 'exists') {
          $state = $this->getState();
          $res['report'] = $state['forms'][$cfId]['report'] ?? null;
        }
        $results[] = $res;
      } catch (\Throwable $e) {
        $results[] = ['id' => $cfId, 'name' => (string) $row['label'], 'status' => 'error', 'message' => $e->getMessage()];
      }
    }
    $message = '';
    if ($totals['limited'] > 0 && function_exists('ppsgen_get_contact_import_limit_message')) {
      $message = ppsgen_get_contact_import_limit_message($totals['limited']);
    }
    $needsPro = false;
    foreach ($results as $res) {
      if (!empty($res['report']['needsPro'])) {
        $needsPro = true;
      }
    }
    $coupon = $this->getCoupon();
    if ($coupon) {
      $coupon['needsPro'] = $needsPro;
    }
    $coupon = apply_filters('ppsgen_cf_import_coupon', $coupon, $needsPro);
    return ['results' => $results, 'totals' => $totals, 'limitMessage' => $message, 'coupon' => $coupon];
  }

  /** CES PRO in the shop of supsystic.com (the product the CES Spooky Sale banner sells too) */
  const CES_PRO_PID = 30155;

  /**
   * Contact Form PRO with a working licence, read from the database (the code of Contact Form may be off):
   * the plugin is in active_plugins and its PRO module add_fields is on in {prefix}cfs_modules. Contact Form PRO
   * switches its PRO modules on with an active licence and off without one, after the grace period and when it is
   * deactivated (license model _updateLicenseData / _setExpired); add_fields is the module its own isPro() checks.
   */
  public function isCfProLicensed(): bool
  {
    global $wpdb;
    if (!function_exists('is_plugin_active')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (!is_plugin_active('contact-form-by-supsystic-pro/contact-form-by-supsystic-pro.php')) {
      return false;
    }
    $table = $wpdb->prefix . 'cfs_modules';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) {
      return false;
    }
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the table name is built from the prefix
    return (int) $wpdb->get_var("SELECT active FROM {$table} WHERE code = 'add_fields' AND ex_plug_dir IS NOT NULL AND ex_plug_dir != '' LIMIT 1") === 1;
  }

  /**
   * The offer for people moving from Contact Form, in the result of the move: CFPRO30 (−30%) for Contact Form PRO
   * with a working licence, CFFREE15 (−15%) otherwise, nothing with an active CES PRO licence. The button puts
   * CES PRO in the cart with the coupon.
   */
  public function getCoupon(): ?array
  {
    if (function_exists('ppsgen_is_pro_license_active') && ppsgen_is_pro_license_active()) {
      return null;
    }
    $cfPro = $this->isCfProLicensed();
    $code = $cfPro ? 'CFPRO30' : 'CFFREE15';
    return [
      'code' => $code,
      'discount' => $cfPro ? 30 : 15,
      'url' => add_query_arg(
        ['mod' => 'user', 'addQty' => 1, 'goto' => 'checkout', 'lang' => 'en', 'action' => 'addToCart', 'pid' => self::CES_PRO_PID, 'coupon' => $code],
        'https://supsystic.com/'
      ),
    ];
  }

  protected function importForm(array $row, array $options): array
  {
    global $wpdb;
    $cfId = (int) $row['id'];
    $converter = $this->getConverter();
    $this->createdContactFields = [];
    $data = $converter->convert($row, ['tagIds' => [$this->getTagId()]]);

    // Spam protection: CES has one setting for all forms. It is set only when nothing was chosen there yet
    $captchaNote = '';
    if (!empty($data['hasCaptcha'])) {
      $captchaNote = $this->ensureBotProtection($data['captchaKeys']);
      if ($captchaNote !== '') {
        $data['moved'][] = $captchaNote;
      }
    }

    $now = current_time('mysql');
    $ok = $wpdb->insert("{$wpdb->prefix}ppsgen_forms", [
      'name' => mb_substr($data['form']['name'], 0, 250),
      'form_type' => $data['form']['form_type'],
      'settings' => wp_json_encode($data['form']['settings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      'design' => wp_json_encode($data['form']['design'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      'active' => $data['form']['active'],
      'created_at' => $now,
      'updated_at' => $now,
    ]);
    $cesId = (int) $wpdb->insert_id;
    if (!$ok || !$cesId) {
      throw new \RuntimeException(__('The form could not be saved in CES.', 'ppsgen') . ' ' . $wpdb->last_error);
    }
    $order = 0;
    foreach ($data['fields'] as $field) {
      $order++;
      $field['settings']['order_index'] = (string) $order;
      $wpdb->insert("{$wpdb->prefix}ppsgen_form_fields", [
        'form_id' => $cesId,
        'cf_id' => (int) $field['cf_id'],
        'fkey' => $field['fkey'],
        'settings' => wp_json_encode($field['settings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'options' => wp_json_encode((object) $field['options'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'order_index' => $order,
        'created_date' => $now,
        'updated_date' => $now,
      ]);
    }
    if (!empty($data['logic'])) {
      $wpdb->insert("{$wpdb->prefix}ppsgen_form_conditional_logic", [
        'form_id' => $cesId,
        'logic_data' => $data['logic']['logic_data'],
        'hidden_fields' => wp_json_encode($data['logic']['hidden_fields']),
        'created_at' => $now,
        'updated_at' => $now,
      ]);
    }
    // Views and submissions counted by Contact Form start the statistics of the CES form
    $views = (int) ($row['views'] ?? 0);
    if ($views > 0) {
      $this->updateOption('form_view', 'stats', $views, $cesId, 'form', 'json');
    }

    $report = ['moved' => $data['moved'], 'skipped' => $data['skipped'], 'needsPro' => !empty($data['needsPro']), 'at' => $now];
    $state = $this->getState();
    $state['forms'][$cfId] = [
      'ces_id' => $cesId,
      'at' => $now,
      'field_map' => $data['fieldMap'],
      'submits' => [],
      'contacts' => [],
      'contact_fields' => $this->createdContactFields,
      'report' => $report,
    ];
    $this->saveState($state);
    return ['id' => $cfId, 'name' => $data['form']['name'], 'status' => 'moved', 'cesId' => $cesId, 'report' => $report];
  }

  protected function ensureBotProtection(array $keys): string
  {
    $formModel = $this->getFrame()->getModule('form')->getModel();
    $current = (string) $formModel->getOption('bot_protection', 'options', 0, 'form');
    if ($current !== '' && $current !== 'none') {
      return '';
    }
    if (!empty($keys['public']) && !empty($keys['secret'])) {
      $formModel->updateOption('bot_protection', 'options', 'google_recaptcha_v2', 0, 'form', 'json');
      $formModel->updateOption('google_recaptchav2_public', 'options', $keys['public'], 0, 'form', 'json');
      $formModel->updateOption('google_recaptchav2_secret', 'options', $keys['secret'], 0, 'form', 'json');
      return __('Your reCAPTCHA keys, in Settings › Forms (CES had no spam protection set)', 'ppsgen');
    }
    $formModel->updateOption('bot_protection', 'options', 'honeypot', 0, 'form', 'json');
    return __('Spam protection in Settings › Forms is now the invisible honeypot (CES had none set)', 'ppsgen');
  }

  /* ---------------------------------------------------------------- submissions */

  /**
   * Submissions of Contact Form become CES submissions with their date and IP. With $withContacts every
   * submission with an email is linked to a contact (an existing one with that email, or a new one with the
   * tag "contact-form"). Contacts are added quietly: no email is sent and no automation starts.
   */
  public function importSubmissions(int $cfId, int $cesId, bool $withContacts): array
  {
    global $wpdb;
    $out = ['moved' => 0, 'contacts' => 0, 'linked' => 0, 'limited' => 0];
    if (!$this->contactsTableExists() || $cesId <= 0) {
      return $out;
    }
    $state = $this->getState();
    $info = $state['forms'][$cfId] ?? null;
    if (!$info) {
      return $out;
    }
    $fieldMap = (array) ($info['field_map'] ?? []);
    $done = (array) ($info['submits'] ?? []);
    $emailSlug = in_array('email', $fieldMap, true) ? 'email' : '';
    $countries = $this->getConverterEnv()['countries'];
    $countryFields = $this->getCountryFieldNames($cfId);
    $tagId = $withContacts ? $this->getTagId() : 0;
    $lastId = 0;
    do {
      $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cfs_contacts WHERE form_id = %d AND id > %d ORDER BY id ASC LIMIT 200", $cfId, $lastId), ARRAY_A);
      foreach ((array) $rows as $row) {
        $lastId = (int) $row['id'];
        if (isset($done[$lastId])) {
          continue;
        }
        $fields = $this->decodeSubmission((string) $row['fields']);
        $values = [];
        foreach ($fields as $name => $value) {
          if (!isset($fieldMap[$name])) {
            continue;
          }
          if (isset($countryFields[$name])) {
            $value = is_array($value) ? array_map(function ($v) use ($countries) {
              return $countries[(int) $v] ?? $v;
            }, $value) : ($countries[(int) $value] ?? $value);
          }
          $values[$fieldMap[$name]] = $value;
        }
        $contactId = 0;
        if ($withContacts && $emailSlug !== '' && !empty($values['email']) && is_email((string) $values['email'])) {
          $contactId = $this->findContactByEmail((string) $values['email']);
          if ($contactId) {
            $out['linked']++;
            $this->addTagQuietly($contactId, $tagId);
          } elseif (function_exists('ppsgen_contact_import_allows_new') && !ppsgen_contact_import_allows_new()) {
            $out['limited']++;
          } else {
            $contactId = $this->createContactQuietly($values, $tagId, (string) $row['date_created']);
            if ($contactId) {
              $out['contacts']++;
              $info['contacts'][] = $contactId;
            }
          }
        }
        $ok = $wpdb->insert("{$wpdb->prefix}ppsgen_form_submits", [
          'form_id' => $cesId,
          'wp_user_id' => null,
          'contact_id' => $contactId ?: null,
          'ip' => (string) $row['ip'],
          'fields_values' => wp_json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          'created_date' => (string) $row['date_created'],
        ]);
        if ($ok) {
          $done[$lastId] = (int) $wpdb->insert_id;
          $out['moved']++;
        }
      }
    } while (!empty($rows) && count($rows) === 200);
    if ($out['moved'] > 0) {
      $formModel = $this->getFrame()->getModule('form')->getModel();
      $total = (int) $formModel->getOption('submit_total', 'stats', $cesId, 'form');
      $success = (int) $formModel->getOption('submit_success', 'stats', $cesId, 'form');
      $formModel->updateOption('submit_total', 'stats', $total + $out['moved'], $cesId, 'form', 'json');
      $formModel->updateOption('submit_success', 'stats', $success + $out['moved'], $cesId, 'form', 'json');
    }
    $state = $this->getState();
    $info['submits'] = $done;
    $info['contacts'] = array_values(array_unique(array_map('intval', (array) ($info['contacts'] ?? []))));
    $state['forms'][$cfId] = $info;
    $this->saveState($state);
    return $out;
  }

  protected function getCountryFieldNames(int $cfId): array
  {
    $rows = $this->getSourceRows([$cfId]);
    $names = [];
    foreach ((array) ($rows[$cfId]['params']['fields'] ?? []) as $f) {
      if (in_array((string) ($f['html'] ?? ''), ['countryList', 'countryListMultiple'], true) && !empty($f['name'])) {
        $names[(string) $f['name']] = true;
      }
    }
    return $names;
  }

  protected function decodeSubmission(string $raw): array
  {
    if ($raw === '') {
      return [];
    }
    $decoded = strpos($raw, ':ORD_ENC') !== false ? '' : base64_decode($raw, true);
    if (strpos($raw, ':ORD_ENC') !== false) {
      foreach (explode('|', str_replace(':ORD_ENC', '', $raw)) as $ord) {
        $decoded .= chr((int) $ord);
      }
    }
    $data = is_string($decoded) ? @unserialize($decoded, ['allowed_classes' => false]) : false;
    return is_array($data) ? $data : [];
  }

  protected function findContactByEmail(string $email): int
  {
    global $wpdb;
    $fieldId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = %s", 'email'));
    if (!$fieldId) {
      return 0;
    }
    return (int) $wpdb->get_var($wpdb->prepare(
      "SELECT c.id FROM {$wpdb->prefix}ppsgen_contacts c INNER JOIN {$wpdb->prefix}ppsgen_contact_field_values v ON v.contact_id = c.id AND v.field_id = %d WHERE LOWER(TRIM(v.value)) = %s AND c.deleted_at IS NULL ORDER BY c.id ASC LIMIT 1",
      $fieldId,
      strtolower(trim($email))
    ));
  }

  protected function createContactQuietly(array $values, int $tagId, string $date): int
  {
    global $wpdb;
    $date = $date !== '' ? $date : current_time('mysql');
    $ok = $wpdb->insert("{$wpdb->prefix}ppsgen_contacts", ['wp_user_id' => 0, 'verified' => 0, 'created_at' => $date, 'updated_at' => $date]);
    if (!$ok) {
      return 0;
    }
    $contactId = (int) $wpdb->insert_id;
    foreach ($values as $slug => $value) {
      $fieldId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = %s", $slug));
      if (!$fieldId) {
        continue;
      }
      if (is_array($value)) {
        $value = wp_json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
      }
      $wpdb->replace("{$wpdb->prefix}ppsgen_contact_field_values", ['contact_id' => $contactId, 'field_id' => $fieldId, 'value' => (string) $value]);
      $wpdb->insert("{$wpdb->prefix}ppsgen_contact_fields_logs", ['contact_id' => $contactId, 'field_id' => $fieldId, 'field_before_value' => '', 'field_value' => (string) $value, 'initiator' => 'cf_import', 'updated_at' => $date]);
    }
    $this->addTagQuietly($contactId, $tagId, $date);
    return $contactId;
  }

  protected function addTagQuietly(int $contactId, int $tagId, string $date = ''): void
  {
    global $wpdb;
    if ($contactId <= 0 || $tagId <= 0) {
      return;
    }
    if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_to_tags WHERE contact_id = %d AND tag_id = %d", $contactId, $tagId))) {
      return;
    }
    $date = $date !== '' ? $date : current_time('mysql');
    $wpdb->insert("{$wpdb->prefix}ppsgen_contact_to_tags", ['contact_id' => $contactId, 'tag_id' => $tagId, 'created_at' => $date]);
    $wpdb->insert("{$wpdb->prefix}ppsgen_contact_tags_logs", ['contact_id' => $contactId, 'tag_action' => 'added', 'tag_ids' => (string) $tagId, 'initiator' => 'cf_import', 'created_at' => $date]);
  }

  /* ---------------------------------------------------------------- shortcodes */

  protected function shortcodeRegex(): string
  {
    // [supsystic-form id=5], [supsystic-form id="5"], [supsystic-form id=\"5\"] (page builder JSON), id%3D5
    return '/\[' . preg_quote(self::CF_SHORTCODE, '/') . '(?=[\s\]])[^\]]*?\bid(?:=|%3D)\\\\?["\']?(\d+)\\\\?["\']?[^\]]*\]/';
  }

  /** Where the old shortcode is: posts and pages (any type), text widgets, Elementor data */
  protected function findShortcodeLocations(): array
  {
    global $wpdb;
    $like = '%' . $wpdb->esc_like('[' . self::CF_SHORTCODE) . '%';
    $locations = [];
    $posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_type, post_status, post_content FROM {$wpdb->posts} WHERE post_type NOT IN ('revision', 'nav_menu_item') AND post_status NOT IN ('trash', 'auto-draft', 'inherit') AND post_content LIKE %s LIMIT 1000", $like), ARRAY_A);
    foreach ((array) $posts as $p) {
      $locations['post:' . $p['ID']] = ['kind' => 'post', 'id' => (int) $p['ID'], 'key' => '', 'title' => (string) $p['post_title'], 'type' => (string) $p['post_type'], 'content' => (string) $p['post_content']];
    }
    $metas = $wpdb->get_results($wpdb->prepare("SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE %s LIMIT 500", $like), ARRAY_A);
    foreach ((array) $metas as $m) {
      $post = get_post((int) $m['post_id']);
      if (!$post || $post->post_status === 'trash' || $post->post_type === 'revision') {
        continue;
      }
      $locations['meta:' . $m['meta_id']] = ['kind' => 'meta', 'id' => (int) $m['meta_id'], 'post' => (int) $m['post_id'], 'key' => (string) $m['meta_key'], 'title' => (string) $post->post_title . ' (Elementor)', 'type' => (string) $post->post_type, 'content' => (string) $m['meta_value']];
    }
    foreach (['widget_text', 'widget_block', 'widget_custom_html'] as $option) {
      $value = get_option($option);
      if (!is_array($value)) {
        continue;
      }
      foreach ($value as $num => $widget) {
        if (!is_array($widget)) {
          continue;
        }
        foreach (['text', 'content'] as $prop) {
          if (isset($widget[$prop]) && is_string($widget[$prop]) && strpos($widget[$prop], '[' . self::CF_SHORTCODE) !== false) {
            $locations['option:' . $option . ':' . $num . ':' . $prop] = ['kind' => 'option', 'id' => (int) $num, 'key' => $option . ':' . $prop, 'title' => sprintf(__('Widget "%s"', 'ppsgen'), !empty($widget['title']) ? $widget['title'] : $option . ' #' . $num), 'type' => 'widget', 'content' => $widget[$prop]];
          }
        }
      }
    }
    return $locations;
  }

  /** Contact Form id => pages that show it */
  public function findShortcodeUsage(): array
  {
    $usage = [];
    foreach ($this->findShortcodeLocations() as $key => $loc) {
      if (preg_match_all($this->shortcodeRegex(), $loc['content'], $m)) {
        foreach (array_unique($m[1]) as $cfId) {
          $usage[(int) $cfId][$key] = ['title' => $loc['title'] !== '' ? $loc['title'] : '#' . $loc['id'], 'type' => $this->typeLabel((string) $loc['type']), 'url' => $this->locationEditUrl($loc)];
        }
      }
    }
    return $usage;
  }

  protected function locationEditUrl(array $loc): string
  {
    if ($loc['kind'] === 'post') {
      return (string) get_edit_post_link($loc['id'], 'raw');
    }
    if ($loc['kind'] === 'meta') {
      return (string) get_edit_post_link((int) $loc['post'], 'raw');
    }
    return admin_url('widgets.php');
  }

  /** What a replacement would change: each place with its old and new shortcodes, and what was replaced already */
  public function getShortcodePlan(): array
  {
    $map = $this->getImportedMap();
    $plan = [];
    foreach ($this->findShortcodeLocations() as $key => $loc) {
      if (!preg_match_all($this->shortcodeRegex(), $loc['content'], $m, PREG_SET_ORDER)) {
        continue;
      }
      $changes = [];
      $missing = [];
      foreach ($m as $match) {
        $cfId = (int) $match[1];
        if (!empty($map[$cfId])) {
          $changes[$match[0]] = '[' . self::CES_SHORTCODE . ' id=' . $map[$cfId] . ']';
        } else {
          $missing[$cfId] = true;
        }
      }
      $plan[] = [
        'key' => $key,
        'title' => $loc['title'] !== '' ? $loc['title'] : '#' . $loc['id'],
        'type' => $this->typeLabel((string) $loc['type']),
        'url' => $this->locationEditUrl($loc),
        'changes' => array_map(function ($from, $to) {
          return ['from' => stripslashes($from), 'to' => $to];
        }, array_keys($changes), array_values($changes)),
        'notMoved' => array_keys($missing),
      ];
    }
    $done = get_option(self::SHORTCODES_OPTION, []);
    $replaced = [];
    foreach ((array) $done as $key => $entry) {
      $parts = explode(':', (string) $key);
      $postId = $parts[0] === 'post' ? (int) $parts[1] : ($parts[0] === 'meta' ? (int) $this->_wpdb->get_var($this->_wpdb->prepare("SELECT post_id FROM {$this->_wpdb->postmeta} WHERE meta_id = %d", (int) $parts[1])) : 0);
      $replaced[] = [
        'key' => $key,
        'title' => (string) ($entry['title'] ?? $key),
        'count' => count((array) ($entry['pairs'] ?? [])),
        'at' => (string) ($entry['at'] ?? ''),
        'type' => $postId ? $this->typeLabel((string) get_post_type($postId)) : __('Widget', 'ppsgen'),
        'url' => $postId ? (string) get_edit_post_link($postId, 'raw') : admin_url('widgets.php'),
      ];
    }
    return ['pending' => $plan, 'replaced' => $replaced];
  }

  /** "Page", "Post", "Widget"... as people read it */
  protected function typeLabel(string $type): string
  {
    if ($type === 'widget') {
      return __('Widget', 'ppsgen');
    }
    $object = get_post_type_object($type);
    return $object && !empty($object->labels->singular_name) ? (string) $object->labels->singular_name : $type;
  }

  /** Replaces the old shortcodes of the moved forms. The original text of each place is kept for undo */
  public function replaceShortcodes(array $keys = []): array
  {
    global $wpdb;
    $map = $this->getImportedMap();
    $backups = get_option(self::SHORTCODES_OPTION, []);
    $backups = is_array($backups) ? $backups : [];
    $changed = 0;
    $places = 0;
    foreach ($this->findShortcodeLocations() as $key => $loc) {
      if (!empty($keys) && !in_array($key, $keys, true)) {
        continue;
      }
      $pairs = [];
      $new = preg_replace_callback($this->shortcodeRegex(), function ($m) use ($map, &$pairs) {
        $cfId = (int) $m[1];
        if (empty($map[$cfId])) {
          return $m[0];
        }
        $to = '[' . self::CES_SHORTCODE . ' id=' . $map[$cfId] . ']';
        $pairs[] = [$m[0], $to];
        return $to;
      }, $loc['content']);
      if (empty($pairs) || $new === $loc['content']) {
        continue;
      }
      $entry = isset($backups[$key]) ? $backups[$key] : ['original' => $loc['content'], 'pairs' => []];
      $entry['title'] = $loc['title'];
      $entry['pairs'] = array_merge((array) $entry['pairs'], $pairs);
      $entry['replaced'] = $new;
      $entry['at'] = current_time('mysql');
      if (!$this->writeLocation($loc, $new)) {
        continue;
      }
      $backups[$key] = $entry;
      $changed += count($pairs);
      $places++;
    }
    update_option(self::SHORTCODES_OPTION, $backups, false);
    return ['shortcodes' => $changed, 'places' => $places];
  }

  /** Puts the old shortcodes back. A place edited after the replacement gets only the shortcodes swapped back */
  public function undoShortcodes(array $keys = []): array
  {
    $backups = get_option(self::SHORTCODES_OPTION, []);
    $backups = is_array($backups) ? $backups : [];
    $restored = 0;
    foreach ($backups as $key => $entry) {
      if (!empty($keys) && !in_array($key, $keys, true)) {
        continue;
      }
      $loc = $this->readLocation($key);
      if ($loc === null) {
        unset($backups[$key]);
        continue;
      }
      if ($loc['content'] === (string) ($entry['replaced'] ?? '')) {
        $content = (string) $entry['original'];
      } else {
        $content = $loc['content'];
        foreach (array_reverse((array) $entry['pairs']) as $pair) {
          $pos = strpos($content, $pair[1]);
          if ($pos !== false) {
            $content = substr_replace($content, $pair[0], $pos, strlen($pair[1]));
          }
        }
      }
      if ($this->writeLocation($loc, $content)) {
        unset($backups[$key]);
        $restored++;
      }
    }
    update_option(self::SHORTCODES_OPTION, $backups, false);
    return ['places' => $restored];
  }

  protected function readLocation(string $key): ?array
  {
    global $wpdb;
    $parts = explode(':', $key);
    if ($parts[0] === 'post') {
      $content = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", (int) $parts[1]));
      return $content === null ? null : ['kind' => 'post', 'id' => (int) $parts[1], 'content' => (string) $content];
    }
    if ($parts[0] === 'meta') {
      $row = $wpdb->get_row($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_id = %d", (int) $parts[1]), ARRAY_A);
      return $row ? ['kind' => 'meta', 'id' => (int) $parts[1], 'post' => (int) $row['post_id'], 'content' => (string) $row['meta_value']] : null;
    }
    if ($parts[0] === 'option' && count($parts) === 4) {
      $value = get_option($parts[1]);
      if (!is_array($value) || !isset($value[$parts[2]][$parts[3]])) {
        return null;
      }
      return ['kind' => 'option', 'id' => (int) $parts[2], 'key' => $parts[1] . ':' . $parts[3], 'content' => (string) $value[$parts[2]][$parts[3]]];
    }
    return null;
  }

  /** Written straight to the database (no revision, no content filters), then the caches are cleared */
  protected function writeLocation(array $loc, string $content): bool
  {
    global $wpdb;
    if ($loc['kind'] === 'post') {
      $ok = $wpdb->update($wpdb->posts, ['post_content' => $content], ['ID' => $loc['id']]);
      clean_post_cache($loc['id']);
      return $ok !== false;
    }
    if ($loc['kind'] === 'meta') {
      $ok = $wpdb->update($wpdb->postmeta, ['meta_value' => $content], ['meta_id' => $loc['id']]);
      wp_cache_delete((int) $loc['post'], 'post_meta');
      // Elementor keeps the rendered page in its own cache
      delete_post_meta((int) $loc['post'], '_elementor_element_cache');
      return $ok !== false;
    }
    if ($loc['kind'] === 'option') {
      [$option, $prop] = explode(':', $loc['key']);
      $value = get_option($option);
      if (!is_array($value) || !isset($value[$loc['id']])) {
        return false;
      }
      $value[$loc['id']][$prop] = $content;
      return update_option($option, $value);
    }
    return false;
  }

  /* ---------------------------------------------------------------- entry points */

  /** Forms of Contact Form that were not moved yet (for the banner on the CES Forms list) */
  public function countNotMoved(): int
  {
    if (!$this->sourceExists()) {
      return 0;
    }
    global $wpdb;
    $ids = array_map('intval', (array) $wpdb->get_col("SELECT id FROM {$wpdb->prefix}cfs_forms WHERE original_id != 0 AND ab_id = 0"));
    return count(array_diff($ids, array_keys($this->getImportedMap())));
  }
}
