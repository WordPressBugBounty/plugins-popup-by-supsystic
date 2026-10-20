<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$prefix = 'data[woo_contact_migration]';
$wooAutoImportConfig = !empty($this->wooAutoImportConfig) && is_array($this->wooAutoImportConfig) ? $this->wooAutoImportConfig : [];
$isWooAutoImportEnabled = !empty($this->isWooAutoImportEnabled);
$wooAutoImportStatusEnabledText = __('Contact autoimport is enabled for new WooCommerce customers and orders.', 'ppsgen');
$wooAutoImportStatusDisabledText = __('Contact autoimport is disabled for new WooCommerce customers and orders.', 'ppsgen');
$wooAutoImportButtonEnableText = __('Enable Contact Autoimport', 'ppsgen');
$wooAutoImportButtonDisableText = __('Disable Contact Autoimport', 'ppsgen');
$wooBillingEmailField = array_key_exists('billing_email', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_email'] : 'email';
$wooBillingFirstNameField = array_key_exists('billing_first_name', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_first_name'] : 'first_name';
$wooBillingLastNameField = array_key_exists('billing_last_name', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_last_name'] : 'last_name';
$wooBillingPhoneField = array_key_exists('billing_phone', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_phone'] : 'phone';
$wooAutoImportTags = !empty($wooAutoImportConfig['tags']) && is_array($wooAutoImportConfig['tags']) ? array_map('intval', $wooAutoImportConfig['tags']) : [];
$wooAutoImportSources = !empty($wooAutoImportConfig['sources']) && is_array($wooAutoImportConfig['sources']) ? array_map('sanitize_key', $wooAutoImportConfig['sources']) : ['orders', 'customers'];
$wooAutoImportOrderStatuses = !empty($wooAutoImportConfig['order_statuses']) && is_array($wooAutoImportConfig['order_statuses']) ? array_map('sanitize_key', $wooAutoImportConfig['order_statuses']) : ['wc-processing', 'wc-completed', 'wc-on-hold'];
$component->setNamePrefix($prefix);
?>

<form method="POST" id="ppsgenGenerateWooContactSubmitForm">

  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" id="action" name="action" value="importGenerateWooContacts">

  <h3><?php esc_html_e('Generate Contact List from WooCommerce Customers', 'ppsgen'); ?></h3>
  <p><?php esc_html_e('Map WooCommerce billing/customer fields to your Contact Fields and generate contacts from WooCommerce orders/customers, including guest checkout customers.', 'ppsgen'); ?></p>
  <p class="ppsgenWooContactAutoImportStatusText" data-enabled-text="<?php echo esc_attr($wooAutoImportStatusEnabledText); ?>" data-disabled-text="<?php echo esc_attr($wooAutoImportStatusDisabledText); ?>">
    <?php echo esc_html($isWooAutoImportEnabled ? $wooAutoImportStatusEnabledText : $wooAutoImportStatusDisabledText); ?>
  </p>

  <?php if (empty($this->isWooCommerceActive)) { ?>
    <p><?php esc_html_e('WooCommerce is not active. Enable WooCommerce before starting the import.', 'ppsgen'); ?></p>
  <?php } ?>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Import Sources', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Choose whether to import from WooCommerce orders, registered customers, or both sources together.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php
      $component->selectmultiple('[sources][]', [
        'options' => [
          'orders' => __('Orders', 'ppsgen'),
          'customers' => __('Registered Customers', 'ppsgen'),
        ],
        'class' => 'ppsgenselect',
        'selected' => $wooAutoImportSources,
      ]);
      ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing Email (required) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_email]', ['options' => $this->allContactFields, 'selected' => $wooBillingEmailField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing First Name (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_first_name]', ['options' => $this->allContactFields, 'selected' => $wooBillingFirstNameField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing Last Name (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_last_name]', ['options' => $this->allContactFields, 'selected' => $wooBillingLastNameField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing Phone (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_phone]', ['options' => $this->allContactFields, 'selected' => $wooBillingPhoneField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing Company (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_company]', ['options' => $this->allContactFields, 'selected' => array_key_exists('billing_company', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_company'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing City (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_city]', ['options' => $this->allContactFields, 'selected' => array_key_exists('billing_city', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_city'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing Country (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_country]', ['options' => $this->allContactFields, 'selected' => array_key_exists('billing_country', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_country'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Billing Postcode (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[billing_postcode]', ['options' => $this->allContactFields, 'selected' => array_key_exists('billing_postcode', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['billing_postcode'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Customer ID (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[customer_id]', ['options' => $this->allContactFields, 'selected' => array_key_exists('customer_id', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['customer_id'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Order Count (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[order_count]', ['options' => $this->allContactFields, 'selected' => array_key_exists('order_count', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['order_count'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Total Spent (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[total_spent]', ['options' => $this->allContactFields, 'selected' => array_key_exists('total_spent', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['total_spent'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Import to Tags (optional)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[tags][]', ['options' => $this->allContactTags, 'class' => 'ppsgenselect', 'selected' => $wooAutoImportTags]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Order Statuses (optional)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('If left empty the import uses processing, completed, on-hold and pending orders.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php
      $component->selectmultiple('[order_statuses][]', [
        'options' => $this->wooOrderStatuses,
        'class' => 'ppsgenselect',
        'selected' => $wooAutoImportOrderStatuses,
      ]);
      ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date From (optional)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->date('[date_from]', ['value' => array_key_exists('date_from', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['date_from'] : '']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date To (optional)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->date('[date_to]', ['value' => array_key_exists('date_to', $wooAutoImportConfig) ? (string) $wooAutoImportConfig['date_to'] : '']); ?>
    </div>
  </div>

  <button
    type="button"
    class="ppsgenSaveBtn ppsgenWooContactAutoImportToggleBtn"
    data-enabled="<?php echo esc_attr($isWooAutoImportEnabled ? '1' : '0'); ?>"
    data-enable-label="<?php echo esc_attr($wooAutoImportButtonEnableText); ?>"
    data-disable-label="<?php echo esc_attr($wooAutoImportButtonDisableText); ?>"
  >
    <span class="dashicons <?php echo esc_attr($isWooAutoImportEnabled ? 'dashicons-no-alt' : 'dashicons-yes-alt'); ?>"></span>
    <span class="ppsgenAutoImportToggleLabel"><?php echo esc_html($isWooAutoImportEnabled ? $wooAutoImportButtonDisableText : $wooAutoImportButtonEnableText); ?></span>
  </button>
  <button class="ppsgenSaveBtn ppsgenGenerateWooContactSubmitBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Generate WooCommerce Contacts', 'ppsgen'); ?></button>

</form>

<?php $component->setNamePrefix(''); ?>
