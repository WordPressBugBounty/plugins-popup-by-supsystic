<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->getFrame()->core('component');
$options = $this->options ?? [];
$settings = !empty($options['settings']) ? $options['settings'] : [];
$component->setNamePrefix('data[form]');
?>
<div class="ppsgenComponentFieldCodeMirror">
  <p><?php esc_html_e('Use {{ID}} placeholder for the current object identifier.', 'ppsgen'); ?></p>
  <p><code>.ppsgenFormWrapper-{{ID}}</code> <code>.ppsgenForm-{{ID}}</code> <code>.ppsgenForm-{{ID}} .ppsgen-form-field</code></p>
  <?php $component->textarea('[settings][custom_css]', ['value' => $settings['custom_css'] ?? '', 'attrs' => 'rows="18" spellcheck="false" class="large-text code ppsgen-custom-code-editor" style="width:100%;min-height:500px;"']); ?>
</div>
