<?php

if (!defined('ABSPATH')) {
  exit();
}

class HelperPpsgen
{
  public $allowed_html = null;
  protected FramePpsgen $_frame;
  protected array $ipGeoCache = [];
  protected string $lastMailProviderError = '';

  public function __construct(FramePpsgen $frame, string $token)
  {
    if ($token !== $frame->getInternalToken()) {
      throw new \Exception('Direct instantiation not allowed');
    }
    $this->_frame = $frame;
  }

  public function getFrame(): FramePpsgen
  {
    return $this->_frame;
  }

  public function setLastMailProviderError(string $message): void
  {
    $this->lastMailProviderError = trim($message);
  }

  public function getLastMailProviderError(): string
  {
    return $this->lastMailProviderError;
  }

  public function getAllowedHtml()
  {
    if ($this->allowed_html === null) {
      global $allowedposttags, $allowedtags, $allowedentitynames;
      $this->allowed_html = array_merge_recursive($allowedposttags, $allowedtags);
      // prettier-ignore
      $html_tags = [
              'a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'bdi', 'bdo', 'blockquote',
              'body', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'data', 'datalist',
              'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'fieldset', 'figcaption',
              'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'i',
              'iframe', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark',
              'menu', 'meter', 'nav', 'noscript', 'ol', 'optgroup', 'option', 'output', 'p',
              'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section',
              'select', 'small', 'source', 'span', 'strong', 'style', 'sub', 'summary', 'sup', 'svg', 'table',
              'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track',
              'u', 'ul', 'var', 'video', 'wbr', 'g', 'path', 'circle', 'rect', 'line', 'polygon', 'polyline', 'ellipse'
      ];
      // prettier-ignore
      $html_attributes = [
              'accept', 'accept-charset', 'accesskey', 'action', 'align', 'alt', 'async', 'autocapitalize',
              'autocomplete', 'autofocus', 'autoplay', 'charset', 'checked', 'cite', 'class', 'cols', 'colspan',
              'content', 'contenteditable', 'controls', 'coords', 'crossorigin', 'data-*', 'datetime', 'decoding',
              'default', 'defer', 'dir', 'dirname', 'disabled', 'download', 'draggable', 'enctype', 'enterkeyhint',
              'for', 'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate', 'formtarget', 'headers',
              'height', 'hidden', 'high', 'href', 'hreflang', 'http-equiv', 'id', 'inputmode', 'integrity', 'ismap',
              'kind', 'label', 'lang', 'list', 'loading', 'loop', 'low', 'max', 'maxlength', 'media', 'method',
              'min', 'minlength', 'multiple', 'muted', 'name', 'novalidate', 'open', 'optimum', 'pattern', 'placeholder',
              'playsinline', 'poster', 'preload', 'readonly', 'referrerpolicy', 'rel', 'required', 'reversed',
              'rows', 'rowspan', 'sandbox', 'scope', 'selected', 'shape', 'size', 'sizes', 'slot', 'span',
              'spellcheck', 'src', 'srclang', 'srcset', 'start', 'step', 'style', 'tabindex', 'target',
              'title', 'translate', 'type', 'usemap', 'value', 'width', 'wrap', 'resize',
              'aria-controls', 'aria-describedby', 'aria-expanded', 'aria-label', 'aria-live', 'aria-sort',
              'data-alpha', 'data-alpha-enabled', 'data-confirmation', 'data-currency_postfix',
              'data-dt-idx', 'data-from', 'data-grid_num', 'data-html-id', 'data-anim',
              'data-maxval', 'data-minval', 'data-next-state', 'data-option-array-index', 'data-settings-tab',
              'data-show-mobile', 'data-show-pc', 'data-skin', 'data-skin_grid', 'data-skin_labels_fromto',
              'data-skin_labels_minmax', 'data-slug', 'data-tab', 'data-to', 'data-toggle-name',
              'data-toggle-val', 'data-type', 'data-uniqid', 'role', 'data-notice-label', 'data-notice-label-desc', 'allowfullscreen', 'allow',
              'xmlns','width','height','viewbox','fill','stroke','stroke-width','stroke-linecap','stroke-linejoin','style','class','id','role','aria-hidden','aria-label','focusable','data-toggle-val','data-type','d','cx','cy','r','x','y','rx','ry','x1','y1','x2','y2','points','transform'
      ];
      foreach ($html_tags as $tag) {
        if (!isset($this->allowed_html[$tag])) {
          $this->allowed_html[$tag] = [];
        }
        $this->allowed_html[$tag] = array_merge($this->allowed_html[$tag], array_fill_keys($html_attributes, true));
        $this->allowed_html[$tag]['style'] = true;
      }
    }
    return apply_filters('wp_kses_allowed_html', $this->allowed_html, '');
  }

  public function backslashDecode($value)
  {
    $replacements = [
      '@@BL@@' => '\\',
      '@@SQ@@' => "'",
      '@@DQ@@' => '"',
    ];
    if (is_array($value)) {
      foreach ($value as $k => $v) {
        $value[$k] = $this->backslashDecode($v);
      }
      return $value;
    }
    if (is_string($value)) {
      return strtr($value, $replacements);
    }
    return $value;
  }

  public function wpKses($html)
  {
    return wp_kses($html, $this->getAllowedHtml());
  }

  public function safeStyleWpKses()
  {
    add_filter('safe_style_css', function ($styles) {
      $styles[] = 'display';
      $styles[] = 'resize';
      $styles[] = 'box-sizing';
      $styles[] = 'text-decoration';
      $styles[] = 'float';
      $styles[] = 'margin';
      $styles[] = 'padding';
      return $styles;
    });
  }

  public function minifyCss(string $css): string
  {
    $css = preg_replace('!/\*.*?\*/!s', '', $css);
    $css = preg_replace('/\n\s*\n/', "\n", $css);
    $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
    $css = preg_replace('/\s*([{}|:;,])\s*/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);
    $css = trim($css);
    return $css;
  }

  public function moveCssImportsToTop(string $css): string
  {
    if ($css === '' || stripos($css, '@import') === false) {
      return $css;
    }

    preg_match_all('/@import\s+[^;]+;/i', $css, $matches);
    if (empty($matches[0])) {
      return $css;
    }

    $imports = array_values(array_unique($matches[0]));
    $css = preg_replace('/@import\s+[^;]+;/i', '', $css);
    return implode('', $imports) . trim((string) $css);
  }

  public function mergeCssRulesBySelector(string $css): string
  {
    $css = trim($css);
    if ($css === '') {
      return $css;
    }

    preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    if (empty($matches)) {
      return $css;
    }

    $cursor = 0;
    $order = [];
    $rules = [];

    foreach ($matches as $match) {
      $fullMatch = $match[0][0] ?? '';
      $fullOffset = (int) ($match[0][1] ?? 0);
      $selector = trim($match[1][0] ?? '');
      $body = trim($match[2][0] ?? '');

      $between = substr($css, $cursor, max(0, $fullOffset - $cursor));
      if (trim($between) !== '') {
        return $css;
      }

      if ($selector === '' || str_starts_with($selector, '@')) {
        return $css;
      }

      if (!isset($rules[$selector])) {
        $rules[$selector] = [];
        $order[] = $selector;
      }

      if ($body !== '') {
        $rules[$selector][] = rtrim($body, ';');
      }

      $cursor = $fullOffset + strlen($fullMatch);
    }

    if (trim(substr($css, $cursor)) !== '') {
      return $css;
    }

    $merged = [];
    foreach ($order as $selector) {
      $body = trim(implode(';', array_filter($rules[$selector], static function ($chunk) {
        return $chunk !== '';
      })));
      if ($body === '') {
        continue;
      }
      $merged[] = $selector . '{' . $body . ';}';
    }

    return implode("\n", $merged);
  }

  public function getGeoLiteLocationByIp(?string $ip): array
  {
    $ip = is_string($ip) ? trim($ip) : '';
    if ($ip === '') {
      return ['country' => ''];
    }
    if (isset($this->ipGeoCache[$ip])) {
      return $this->ipGeoCache[$ip];
    }

    $location = ['country' => ''];
    $geolite = $this->getFrame()->getModule('geolite');
    if (empty($geolite)) {
      $this->ipGeoCache[$ip] = $location;
      return $location;
    }

    try {
      if (method_exists($geolite, 'getCountryName')) {
        $location['country'] = (string) ($geolite->getCountryName($ip) ?? '');
      }
    } catch (\Throwable $e) {
      $location = ['country' => ''];
    }

    $this->ipGeoCache[$ip] = $location;
    return $location;
  }

  public function getGeoLiteLocationLabelByIp(?string $ip): string
  {
    $location = $this->getGeoLiteLocationByIp($ip);
    return (string) ($location['country'] ?? '');
  }

  public function sanitizeCustomCssCode($css): string
  {
    $css = is_string($css) ? $css : '';
    $css = wp_check_invalid_utf8($css);
    $css = str_replace(["\r\n", "\r"], "\n", $css);
    $css = preg_replace('/^\s*<style\b[^>]*>/i', '', $css);
    $css = preg_replace('/<\/style>\s*$/i', '', $css);
    $css = preg_replace('/<\?(php|=)?/i', '', $css);
    $css = str_ireplace('</style', '<\/style', $css);
    return trim($css);
  }

  public function sanitizeCustomJsCode($js): string
  {
    $js = is_string($js) ? $js : '';
    $js = wp_check_invalid_utf8($js);
    $js = str_replace(["\r\n", "\r"], "\n", $js);
    $js = $this->stripScriptWrapperFromCustomJs($js);
    $js = preg_replace('/<\?(php|=)?/i', '', $js);
    $js = str_ireplace('</script', '<\/script', $js);
    return trim($js);
  }

  protected function stripScriptWrapperFromCustomJs(string $js): string
  {
    $js = trim($js);
    if ($js === '') {
      return '';
    }

    if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $js, $matches) && !empty($matches[1])) {
      $parts = array_map(static function ($part) {
        return trim((string) $part);
      }, $matches[1]);
      $parts = array_values(array_filter($parts, static function ($part) {
        return $part !== '';
      }));
      if (!empty($parts)) {
        return implode("\n\n", $parts);
      }
    }

    $js = preg_replace('/^\s*<script\b[^>]*>/i', '', $js);
    $js = preg_replace('/<\/script>\s*$/i', '', $js);
    return trim((string) $js);
  }

  public function replaceCustomCodeIdTokens($code, $id): string
  {
    $code = is_string($code) ? $code : '';
    return str_replace('{{ID}}', (string) ((int) $id), $code);
  }

  public function prepareFrontendCustomCss($css, $id): string
  {
    $css = $this->sanitizeCustomCssCode($css);
    if ($css === '') {
      return '';
    }
    return $this->replaceCustomCodeIdTokens($css, $id);
  }

  public function prepareFrontendCustomJs($js, $id): string
  {
    $js = $this->sanitizeCustomJsCode($js);
    if ($js === '') {
      return '';
    }
    $js = $this->replaceCustomCodeIdTokens($js, $id);
    return "jQuery(function($){\n" . $js . "\n});";
  }

  /**
   * The markup of an SVG icon element, read from disk. $svgIcon is either a library key ("undraw_android":
   * public/assets/libs/ppsgensvg/undraw/icons/android.svg) or a URL of an .svg inside the free or PRO plugin.
   * Reading it over HTTP (file_get_contents of the URL) made the site request itself: that fails on WordPress
   * Playground, with allow_url_fopen off or with loopback requests blocked, and the icons came out empty.
   * Only .svg files inside the two plugin folders are read; anything else returns ''.
   */
  public function getSvgIconMarkup(string $svgIcon): string
  {
    $svgIcon = trim($svgIcon);
    if ($svgIcon === '') {
      return '';
    }
    $roots = [PPSGEN_PLUGIN_DIR];
    if (defined('PPSGEN_PRO_PLUGIN_DIR')) {
      $roots[] = PPSGEN_PRO_PLUGIN_DIR;
    }
    $file = '';
    if (strtolower(substr($svgIcon, -4)) === '.svg') {
      // A stored URL — this site's, another site's (a template made elsewhere), root-relative or with a
      // sub-path such as Playground's /scope:…/ — points at a file under ".../plugins/<plugin folder>/"
      $path = (string) wp_parse_url($svgIcon, PHP_URL_PATH);
      foreach ($roots as $dir) {
        $needle = '/plugins/' . basename(rtrim($dir, '/\\')) . '/';
        $pos = strpos($path, $needle);
        if ($pos !== false) {
          $file = $dir . substr($path, $pos + strlen($needle));
          break;
        }
      }
    } else {
      $parts = explode('_', $svgIcon, 2);
      if (count($parts) === 2 && preg_match('/^[a-z0-9-]+$/i', $parts[0]) && preg_match('/^[a-z0-9_.-]+$/i', $parts[1])) {
        $file = PPSGEN_PLUGIN_DIR . 'public/assets/libs/ppsgensvg/' . $parts[0] . '/icons/' . $parts[1] . '.svg';
      }
    }
    if ($file === '') {
      return '';
    }
    $real = realpath($file);
    if (!$real || strtolower(substr($real, -4)) !== '.svg' || !is_file($real)) {
      return '';
    }
    foreach ($roots as $dir) {
      $base = realpath($dir);
      if ($base && strpos($real, rtrim($base, '/\\') . DIRECTORY_SEPARATOR) === 0) {
        return (string) file_get_contents($real);
      }
    }
    return '';
  }

  /**
   * Editor previews have no submit endpoint, so a sent form did nothing there and anything waiting for the
   * submit (a game that unlocks, a code in the thank-you message) could not be tried. This stand-in shows the
   * form's thank-you message and fires the same ppsgenFormSuccess event a real submit fires — nothing is sent.
   * The live preview stays inert: it cancels submits first, and a cancelled submit is left alone here.
   */
  public function getPreviewFormSubmitScript(): string
  {
    return "<script>(function () {\n"
      // No "already added" flag: a redraw (document.open) keeps the window and document objects but drops
      // their listeners. With several forms the first copy cancels the submit and the others skip it.
      . "  document.addEventListener('submit', function (e) {\n"
      . "    var form = e.target;\n"
      . "    if (e.defaultPrevented || !form.classList || !form.classList.contains('ppsgenForm')) { return; }\n"
      . "    e.preventDefault();\n"
      . "    e.stopPropagation();\n"
      . "    form.querySelectorAll('.ppsgen-form-field-container').forEach(function (el) { el.style.display = 'none'; });\n"
      . "    var wrap = form.querySelector('.ppsgenFormSubmitWrapper');\n"
      . "    if (wrap) {\n"
      . "      wrap.style.display = 'block';\n"
      . "      if (!wrap.querySelector('.ppsgenPreviewSubmitNote')) {\n"
      . "        var note = document.createElement('p');\n"
      . "        note.className = 'ppsgenPreviewSubmitNote';\n"
      . "        note.style.cssText = 'margin:8px 0 0;opacity:.6;font:12px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;text-align:center;';\n"
      . "        note.textContent = " . wp_json_encode(__('Preview — nothing was sent.', 'ppsgen')) . ";\n"
      . "        wrap.appendChild(note);\n"
      . "      }\n"
      . "    }\n"
      . "    var popup = form.closest('.ppsgenPopup');\n"
      // a native event: jQuery .on() handlers get it whichever jQuery copy bound them
      . "    if (popup) { popup.dispatchEvent(new CustomEvent('ppsgenFormSuccess', { bubbles: true, detail: form.getAttribute('data-form-id') })); }\n"
      . "  }, true);\n"
      . "})();</script>";
  }

  public function preparePreviewCustomJs($js, $id): string
  {
    $js = $this->prepareFrontendCustomJs($js, $id);
    if ($js === '') {
      return '';
    }
    // Preview documents load jQuery and the plugin scripts with defer, so an inline script runs before them;
    // wait for DOMContentLoaded (deferred scripts have run by then) or custom code finds no jQuery
    return "<script>\n(function (run) {\n  if (document.readyState === 'loading') {\n    document.addEventListener('DOMContentLoaded', run);\n  } else {\n    run();\n  }\n})(function () {\n" . $js . "\n});\n</script>";
  }

  public function preparePayloadData($payload, $key = 'data')
  {
    $payload = $this->joinPayloadDataParts($payload);
    $data = [];
    if ($payload) {
      $json = base64_decode($payload, true);
      if ($json !== false) {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
          $data = $decoded;
        }
      }
    }
    if (!empty($data)) {
      $dataArr = $this->expandPayloadData($data);
      if (!empty($key)) {
        $dataArr = $dataArr[$key] ?? [];
      }
      return $dataArr;
    }
    return $data;
  }

  public function prepareRequestPayloadData($key = 'data', $requestKey = 'payload_data')
  {
    // Keep the base64 payload intact. Sanitizing it as text can corrupt large
    // packed editor values before JSON decoding; fields are sanitized later.
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $payload = isset($_POST[$requestKey]) ? wp_unslash($_POST[$requestKey]) : '';
    return $this->preparePayloadData($payload, $key);
  }

  public function joinPayloadDataParts($payload)
  {
    if (is_array($payload)) {
      return implode('', array_map('strval', $payload));
    }
    return is_string($payload) ? $payload : '';
  }

  public function expandPayloadData(array $data)
  {
    $result = [];
    foreach ($data as $path => $value) {
      if (!is_string($path) || $path === '') {
        continue;
      }
      $this->assignPayloadPathValue($result, $path, $value);
    }
    return $result;
  }

  protected function assignPayloadPathValue(array &$target, string $path, $value)
  {
    $segments = $this->parsePayloadPath($path);
    if (empty($segments)) {
      return;
    }
    $ref = &$target;
    $lastIndex = count($segments) - 1;
    foreach ($segments as $index => $segment) {
      $isLast = $index === $lastIndex;
      $nextSegment = !$isLast ? $segments[$index + 1] : null;
      if ($segment === '') {
        if (!is_array($ref)) {
          $ref = [];
        }
        if ($isLast) {
          $ref[] = $value;
          return;
        }
        $appendIndex = count($ref);
        if (
          $appendIndex > 0 &&
          is_array($ref[$appendIndex - 1]) &&
          $nextSegment !== '' &&
          !array_key_exists($nextSegment, $ref[$appendIndex - 1])
        ) {
          $appendIndex--;
        } else {
          $ref[$appendIndex] = [];
        }
        $ref = &$ref[$appendIndex];
        continue;
      }
      if ($isLast) {
        if (is_array($value) && isset($ref[$segment]) && is_array($ref[$segment])) {
          $ref[$segment] = array_merge($ref[$segment], $value);
        } else {
          $ref[$segment] = $value;
        }
        return;
      }
      if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
        $ref[$segment] = [];
      }
      $ref = &$ref[$segment];
    }
  }

  protected function parsePayloadPath(string $path)
  {
    preg_match_all('/([^[\\]]+)|\\[([^\\]]*)\\]/', $path, $matches);
    $segments = [];
    foreach ($matches[0] as $index => $match) {
      $segment = $matches[1][$index] !== '' ? $matches[1][$index] : $matches[2][$index];
      $segments[] = $segment;
    }
    return $segments;
  }

  public function bootProMailProviders(): void
  {
    if (!defined('PPSGEN_PRO_PLUGIN_NAME') && !class_exists('FrameProPpsgen')) {
      return;
    }
    if (class_exists('FrameProPpsgen')) {
      $this->getFrame()->core('FramePro');
    }
    if (class_exists('MailProviderManagerPpsgen')) {
      $this->getFrame()->core('MailProviderManager');
    }
  }

  public function sendEmail($to, $fromEmail, $fromName, $subject, $message, $headers = [], $attachments = [], $mailConfigId = 0)
  {
    $this->setLastMailProviderError('');
    $dashboardModel = $this->getFrame()->getModule('dashboard')->getModel();
    $mailConfig = $dashboardModel->getResolvedMailConfigData((int) $mailConfigId);
    $mailSettings = is_array($mailConfig['settings'] ?? null) ? $mailConfig['settings'] : [];
    $providerType = (string) ($mailSettings['provider_type'] ?? 'custom_smtp_imap');
    $mailMethod = !empty($mailSettings['mail_method']) ? $mailSettings['mail_method'] : 'mail';
    $smtpHost = (string) ($mailSettings['smtp_server_name'] ?? '');
    $smtpUser = (string) ($mailSettings['smtp_login'] ?? '');
    $smtpPass = (string) ($mailSettings['smtp_password'] ?? '');
    $smtpPort = (int) ($mailSettings['smtp_port'] ?? 0);
    $smtpTimeout = (int) ($mailSettings['smtp_timeout'] ?? 0);
    $smtpSecure = !empty($mailSettings['smtp_secure']) ? (string) $mailSettings['smtp_secure'] : 'ssl'; // ssl|tls
    $imapHost = (string) ($mailSettings['imap_server_name'] ?? '');
    $imapPort = (int) ($mailSettings['imap_port'] ?? 0);
    $fromEmail = $fromEmail ?: (string) ($mailSettings['mail_from_email'] ?? '');
    $fromName = $fromName ?: (string) ($mailSettings['mail_from_name'] ?? '');
    $fromEmail = $fromEmail ?: get_bloginfo('admin_email');
    $fromName = $fromName ?: get_bloginfo('name');
    if (empty($headers)) {
      $headers = ['Content-Type: text/html; charset=UTF-8'];
    }
    // Free email branding on every HTML email (thank-you, owner alert, tests...), unless a template already added it
    if (function_exists('ppsgen_get_email_branding_html') && stripos(implode("
", (array) $headers), 'text/html') !== false && strpos((string) $message, 'utm_medium=email_footer') === false) {
      $message .= ppsgen_get_email_branding_html();
    }
    $this->bootProMailProviders();
    $providerResult = $this->getFrame()->core('eventDispatcher')->applyFiltersOnce('mail.config.send_email', null, [
      'provider_type' => $providerType,
      'mail_config_id' => (int) $mailConfigId,
      'mail_config' => $mailConfig,
      'to' => (string) $to,
      'from_email' => (string) $fromEmail,
      'from_name' => (string) $fromName,
      'subject' => (string) $subject,
      'message' => (string) $message,
      'headers' => is_array($headers) ? $headers : [$headers],
      'attachments' => is_array($attachments) ? $attachments : [$attachments],
    ]);
    if ($providerResult !== null) {
      if (is_array($providerResult)) {
        if (empty($providerResult['success']) && !empty($providerResult['message'])) {
          $this->setLastMailProviderError((string) $providerResult['message']);
        }
        return !empty($providerResult['success']);
      }
      return (bool) $providerResult;
    }
    /** ============================
     * SMTP INIT
     * ============================ */
    $phpmailerInit = null;
    if ($mailMethod === 'smtp') {
      $phpmailerInit = function ($phpmailer) use ($smtpHost, $smtpUser, $smtpPass, $smtpPort, $smtpTimeout, $smtpSecure, $fromEmail, $fromName) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $smtpHost;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $smtpUser;
        $phpmailer->Password = $smtpPass;
        $phpmailer->Port = $smtpPort ?: ($smtpSecure === 'tls' ? 587 : 465);
        $phpmailer->SMTPSecure = $smtpSecure;
        $phpmailer->Timeout = $smtpTimeout ?: 10;
        $phpmailer->CharSet = 'UTF-8';
        $phpmailer->setFrom($fromEmail, $fromName);
        /** === SMTP DEBUG === */
        $phpmailer->SMTPDebug = 0;
        // $phpmailer->Debugoutput = function ($str, $level) {
        //   ppsgenlog('[SMTP DEBUG][' . $level . '] ' . $str);
        // };
        // ppsgenlog(sprintf(
        //   '[SMTP CONFIG] host=%s port=%d secure=%s user=%s',
        //   $phpmailer->Host,
        //   $phpmailer->Port,
        //   $phpmailer->SMTPSecure,
        //   $phpmailer->Username
        // ));
      };
      add_action('phpmailer_init', $phpmailerInit, 5);
    }
    /** ============================
     * FROM FILTERS
     * ============================ */
    $filterFrom = function () use ($fromEmail) {
      return $fromEmail;
    };
    $filterName = function () use ($fromName) {
      return $fromName;
    };
    add_filter('wp_mail_from', $filterFrom);
    add_filter('wp_mail_from_name', $filterName);
    /** ============================
     * ERROR HOOK
     * ============================ */
    $mailErrorHandler = function ($wpError) {
      if (!($wpError instanceof WP_Error)) {
        return;
      }
      $messages = $wpError->get_error_messages();
      if (!empty($messages)) {
        $this->setLastMailProviderError(implode(' ', array_map('trim', $messages)));
      }
    };
    add_action('wp_mail_failed', $mailErrorHandler);
    /** ============================
     * SEND
     * ============================ */
    try {
      $result = wp_mail($to, $subject, $message, $headers, $attachments);
      if ($result) {
        $this->appendSentEmailToMailbox([
          'mail_method' => $mailMethod,
          'imap_host' => $imapHost,
          'user' => $smtpUser,
          'pass' => $smtpPass,
          'imap_port' => $imapPort,
          'imap_secure' => $smtpSecure,
          'from_email' => $fromEmail,
          'from_name' => $fromName,
        ], (string) $to, (string) $subject, (string) $message);
      }
      // if ($result === false) {
      //   ppsgenlog('[WP_MAIL] wp_mail returned false');
      // }
    } finally {
      remove_filter('wp_mail_from', $filterFrom);
      remove_filter('wp_mail_from_name', $filterName);
      remove_action('wp_mail_failed', $mailErrorHandler);
      if ($phpmailerInit) {
        remove_action('phpmailer_init', $phpmailerInit, 5);
      }
    }
    return $result;
  }

  protected function appendSentEmailToMailbox(array $config, string $toEmail, string $subject, string $htmlMessage): bool
  {
    if (($config['mail_method'] ?? 'mail') !== 'smtp' || !function_exists('imap_open') || !function_exists('imap_append')) {
      return false;
    }
    $host = (string) ($config['imap_host'] ?? '');
    $user = (string) ($config['user'] ?? '');
    $pass = (string) ($config['pass'] ?? '');
    $port = (int) ($config['imap_port'] ?? 0);
    $secure = !empty($config['imap_secure']) ? (string) $config['imap_secure'] : 'ssl';
    if ($host === '' || $user === '' || $pass === '' || $port <= 0) {
      return false;
    }
    $root = sprintf('{%s:%d/imap/%s}', $host, $port, $secure);
    $imap = $this->openSentMailbox($root . 'INBOX', $user, $pass);
    if (!$imap) {
      return false;
    }
    $mailboxes = $this->discoverSentMailboxes($imap, $root);
    $sentMailbox = '';
    foreach ($mailboxes as $mailbox => $type) {
      if ($type === 'sent') {
        $sentMailbox = $mailbox;
        break;
      }
    }
    if ($sentMailbox === '') {
      imap_close($imap);
      return false;
    }
    $fromEmail = !empty($config['from_email']) ? sanitize_email((string) $config['from_email']) : sanitize_email($user);
    $fromName = !empty($config['from_name']) ? (string) $config['from_name'] : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $rfc822 = $this->buildSentEmailRfc822($fromEmail, $fromName, $toEmail, $subject, $htmlMessage);
    $result = @imap_append($imap, $sentMailbox, $rfc822, '\\Seen');
    imap_close($imap);
    return (bool) $result;
  }

  protected function openSentMailbox(string $mailbox, string $user, string $pass)
  {
    imap_timeout(IMAP_OPENTIMEOUT, 20);
    imap_timeout(IMAP_READTIMEOUT, 20);
    return @imap_open($mailbox, $user, $pass);
  }

  protected function discoverSentMailboxes($imap, string $root): array
  {
    $mailboxes = [
      $root . 'INBOX' => 'inbox',
    ];
    $allMailboxes = @imap_list($imap, $root, '*');
    if (empty($allMailboxes) || !is_array($allMailboxes)) {
      return $mailboxes;
    }
    foreach ($allMailboxes as $box) {
      if (!is_string($box) || isset($mailboxes[$box])) {
        continue;
      }
      $boxName = strtolower(str_replace($root, '', $box));
      if (preg_match('/trash|deleted|junk|spam|draft/i', $boxName)) {
        continue;
      }
      if (preg_match('/sent|sent items|sent messages|sent mail|РѕС‚РїСЂР°РІ|gesendet|enviado|enviad/i', $boxName)) {
        $mailboxes[$box] = 'sent';
      }
    }
    return $mailboxes;
  }

  protected function buildSentEmailRfc822(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlMessage): string
  {
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = $fromName !== '' ? '=?UTF-8?B?' . base64_encode($fromName) . '?=' : '';
    $fromHeader = $encodedFromName !== '' ? $encodedFromName . ' <' . $fromEmail . '>' : $fromEmail;
    $messageIdDomain = wp_parse_url(home_url(), PHP_URL_HOST);
    $messageIdDomain = !empty($messageIdDomain) ? $messageIdDomain : 'localhost';
    $headers = [
      'Date: ' . date('r'),
      'From: ' . $fromHeader,
      'To: ' . $toEmail,
      'Subject: ' . $encodedSubject,
      'Message-ID: <' . wp_generate_uuid4() . '@' . $messageIdDomain . '>',
      'MIME-Version: 1.0',
      'Content-Type: text/html; charset=UTF-8',
      'Content-Transfer-Encoding: base64',
      'X-Mailer: WP Supsystic',
    ];
    $body = chunk_split(base64_encode($htmlMessage));
    return implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n";
  }
}
