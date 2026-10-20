<?php
if (!defined('ABSPATH')) {
  exit();
}
$field = is_array($this->contactLookupField ?? null) ? $this->contactLookupField : [];
$fieldName = (string) ($field['field_name'] ?? 'contact_id');
$selectedId = (int) ($field['selected_id'] ?? 0);
$selectedLabel = (string) ($field['selected_label'] ?? '');
$placeholder = (string) ($field['placeholder'] ?? __('Search by contact ID or email', 'ppsgen'));
$inputClass = trim((string) ($field['input_class'] ?? ''));
$wrapperClass = trim((string) ($field['wrapper_class'] ?? ''));
$minLength = max(1, (int) ($field['min_length'] ?? 2));
$limit = max(1, min(10, (int) ($field['limit'] ?? 10)));
$instanceId = (string) ($field['instance_id'] ?? ('ppsgenContactLookup' . wp_rand(1000, 999999)));
?>
<div
  id="<?php echo esc_attr($instanceId); ?>"
  class="ppsgenContactLookup <?php echo esc_attr($wrapperClass); ?>"
  data-min-length="<?php echo esc_attr($minLength); ?>"
  data-limit="<?php echo esc_attr($limit); ?>"
  data-search-action="searchContactLookup"
>
  <input
    type="hidden"
    name="<?php echo esc_attr($fieldName); ?>"
    value="<?php echo esc_attr($selectedId); ?>"
    class="ppsgenContactLookupValue"
  />
  <input
    type="text"
    value="<?php echo esc_attr($selectedLabel); ?>"
    class="ppsgenContactLookupSearch <?php echo esc_attr($inputClass); ?>"
    placeholder="<?php echo esc_attr($placeholder); ?>"
    autocomplete="off"
    spellcheck="false"
    data-selected-label="<?php echo esc_attr($selectedLabel); ?>"
  />
  <div class="ppsgenContactLookupDropdown" hidden></div>
</div>
