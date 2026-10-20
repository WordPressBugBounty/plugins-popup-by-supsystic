<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$prefix = 'data[contact_migration]';
$wpAutoImportConfig = !empty($this->wpAutoImportConfig) && is_array($this->wpAutoImportConfig) ? $this->wpAutoImportConfig : [];
$isWpAutoImportEnabled = !empty($this->isWpAutoImportEnabled);
$wpAutoImportStatusEnabledText = __('Contact autoimport is enabled for new WordPress users.', 'ppsgen');
$wpAutoImportStatusDisabledText = __('Contact autoimport is disabled for new WordPress users.', 'ppsgen');
$wpAutoImportButtonEnableText = __('Enable Contact Autoimport', 'ppsgen');
$wpAutoImportButtonDisableText = __('Disable Contact Autoimport', 'ppsgen');
$wpUserEmailField = array_key_exists('user_email', $wpAutoImportConfig) ? (string) $wpAutoImportConfig['user_email'] : 'email';
$wpUserNickNameField = array_key_exists('user_nick_name', $wpAutoImportConfig) ? (string) $wpAutoImportConfig['user_nick_name'] : 'first_name';
$wpUserDisplayNameField = array_key_exists('user_display_name', $wpAutoImportConfig) ? (string) $wpAutoImportConfig['user_display_name'] : 'last_name';
$wpUserRoleField = array_key_exists('user_role', $wpAutoImportConfig) ? (string) $wpAutoImportConfig['user_role'] : '';
$wpAutoImportTags = !empty($wpAutoImportConfig['tags']) && is_array($wpAutoImportConfig['tags']) ? array_map('intval', $wpAutoImportConfig['tags']) : [];
$wpAutoImportRoles = !empty($wpAutoImportConfig['roles']) && is_array($wpAutoImportConfig['roles']) ? array_map('sanitize_key', $wpAutoImportConfig['roles']) : ['all_roles'];
$component->setNamePrefix($prefix);
?>

<form method="POST" id="ppsgenGenerateContactSubmitForm">

  <input type="hidden" name="plugin" value="<?php ppsgen_esc_attr(PPSGEN_PLUGIN_SHORTNAME); ?>">
  <input type="hidden" id="action" name="action" value="importGenerateContacts">

  <h3><?php esc_html_e('Generate Contact List from Wordpress Users', 'ppsgen'); ?></h3>
  <p><?php esc_html_e('Please map the default WordPress user fields to your Contact Fields and set up the import process.', 'ppsgen'); ?></p>
  <p class="ppsgenContactAutoImportStatusText" data-enabled-text="<?php echo esc_attr($wpAutoImportStatusEnabledText); ?>" data-disabled-text="<?php echo esc_attr($wpAutoImportStatusDisabledText); ?>">
    <?php echo esc_html($isWpAutoImportEnabled ? $wpAutoImportStatusEnabledText : $wpAutoImportStatusDisabledText); ?>
  </p>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('User Email (required) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[user_email]', ['options' => $this->allContactFields, 'selected' => $wpUserEmailField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('User Nick Name (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[user_nick_name]', ['options' => $this->allContactFields, 'selected' => $wpUserNickNameField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('User Display Name (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[user_display_name]', ['options' => $this->allContactFields, 'selected' => $wpUserDisplayNameField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('User Role (optional) go to', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[user_role]', ['options' => $this->allContactFields, 'selected' => $wpUserRoleField]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Import to Tags (optional)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[tags][]', ['options' => $this->allContactTags, 'class' => 'ppsgenselect', 'selected' => $wpAutoImportTags]); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Source User Roles (optional)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('If left empty import from all user roles.', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php
      $userRoles = array_reverse(wp_roles()->get_names(), true);
      $userRoles = ['all_roles' => esc_html__('From all roles', 'ppsgen')] + $userRoles;
      $component->selectmultiple('[roles][]', [
        'options' => $userRoles,
        'class' => 'ppsgenselect',
        'selected' => $wpAutoImportRoles,
      ]);
      ?>
    </div>
  </div>

  <button
    type="button"
    class="ppsgenSaveBtn ppsgenContactAutoImportToggleBtn"
    data-enabled="<?php echo esc_attr($isWpAutoImportEnabled ? '1' : '0'); ?>"
    data-enable-label="<?php echo esc_attr($wpAutoImportButtonEnableText); ?>"
    data-disable-label="<?php echo esc_attr($wpAutoImportButtonDisableText); ?>"
  >
    <span class="dashicons <?php echo esc_attr($isWpAutoImportEnabled ? 'dashicons-no-alt' : 'dashicons-yes-alt'); ?>"></span>
    <span class="ppsgenAutoImportToggleLabel"><?php echo esc_html($isWpAutoImportEnabled ? $wpAutoImportButtonDisableText : $wpAutoImportButtonEnableText); ?></span>
  </button>
  <button class="ppsgenSaveBtn ppsgenGenerateContactSubmitBtn"><span class="dashicons dashicons-yes-alt"></span><?php esc_html_e('Generate Contacts', 'ppsgen'); ?></button>

</form>

<?php $component->setNamePrefix('');
?>
