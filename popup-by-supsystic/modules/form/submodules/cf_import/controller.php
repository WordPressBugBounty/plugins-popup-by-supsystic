<?php

if (!defined('ABSPATH')) {
  exit();
}

class CfImportSubmoduleControllerPpsgen extends BaseControllerPpsgen
{
  protected function guard(): void
  {
    check_ajax_referer('ppsgenAjaxNonce', 'nonce_code');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('You are not allowed to do this.', 'ppsgen')], 403);
    }
  }

  protected function postIds(string $name): array
  {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard()
    $raw = isset($_POST[$name]) ? wp_unslash($_POST[$name]) : [];
    return array_values(array_filter(array_map('intval', (array) $raw)));
  }

  protected function postKeys(string $name): array
  {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard()
    $raw = isset($_POST[$name]) ? (array) wp_unslash($_POST[$name]) : [];
    return array_values(array_filter(array_map(static function ($key) {
      return preg_replace('/[^a-z0-9_:\-]/i', '', (string) $key);
    }, $raw)));
  }

  protected function postFlag(string $name, bool $default): bool
  {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard()
    if (!isset($_POST[$name])) {
      return $default;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard()
    return in_array((string) wp_unslash($_POST[$name]), ['1', 'true', 'on', 'yes'], true);
  }

  public function cfImportScan()
  {
    $this->guard();
    wp_send_json_success($this->getModel()->scan());
  }

  public function cfImportRun()
  {
    $this->guard();
    $model = $this->getModel();
    if (!$model->sourceExists()) {
      wp_send_json_error(['message' => __('Contact Form by Supsystic data was not found on this site.', 'ppsgen')]);
    }
    $result = $model->import($this->postIds('ids'), [
      'submissions' => $this->postFlag('submissions', true),
      'contacts' => $this->postFlag('contacts', true),
    ]);
    $result['scan'] = $model->scan();
    wp_send_json_success($result);
  }

  public function cfImportReplaceShortcodes()
  {
    $this->guard();
    $model = $this->getModel();
    $result = $model->replaceShortcodes($this->postKeys('keys'));
    $result['scan'] = $model->scan();
    wp_send_json_success($result);
  }

  public function cfImportUndoShortcodes()
  {
    $this->guard();
    $model = $this->getModel();
    $result = $model->undoShortcodes($this->postKeys('keys'));
    $result['scan'] = $model->scan();
    wp_send_json_success($result);
  }
}
