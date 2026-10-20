<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$options = $this->options;
$settings = !empty($options['settings']) ? $options['settings'] : '';
$prefix = 'data[form]';
$sprefix = 'data[form][settings]';
$component->setNamePrefix($prefix);
?>

<div class="ppsgenComponentsList">
  <h3><?php esc_html_e('General Options', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form is Enabled', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->checkboxToggle('[active]', ['checked' => $component->value($options['active'] ?? null, '1'), 'class' => 'data-option-toggle-trigger']); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form Name', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[name]', ['value' => $component->value($options['name'] ?? null), 'attrs' => ' ']); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form Type', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[form_type]', [
        'options' => [
          'submit' => esc_html__('Simple submission Form', 'ppsgen'),
          'create_contact' => esc_html__('Create / Identify Contact Form', 'ppsgen'),
          'login' => esc_html__('Login Form for Worpdress', 'ppsgen'),
          'logout' => esc_html__('Logout Form for Worpdress', 'ppsgen'),
          'signup' => esc_html__('Signup Form for Wordpress', 'ppsgen'),
          'reset_password' => esc_html__('Reset Password Form for Wordpress', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($options['form_type'] ?? null, 'submit'),
      ]); ?>
    </div>
  </div>

  <?php $component->setNamePrefix($sprefix); ?>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show Form restrictions', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'Select who can see the form. User Role filter will be available for logged-in users only.',
  'ppsgen',
); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->select('[form_restrictions]', [
        'options' => [
          'show_all' => esc_html__('Show to everyone (no restrictions)', 'ppsgen'),
          'show_logged_in' => esc_html__('Show only to logged-in users', 'ppsgen'),
          'show_guests' => esc_html__('Show only to guests (not logged-in)', 'ppsgen'),
          'show_not_submitted' => esc_html__('Show only to users who have not submitted the form (by user ID, IP, or cookie)', 'ppsgen'),
        ],
        'class' => 'ppsgenselect data-option-toggle-trigger',
        'selected' => $component->value($settings['form_restrictions'] ?? null, 'show_all'),
      ]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_restrictions]" data-toggle-val="show_logged_in, show_not_submitted">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Show FORM for Specific User Roles', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php
      $userRoles = array_reverse(wp_roles()->get_names(), true);
      $userRoles = ['show_all' => esc_html__('Show to every roles', 'ppsgen')] + $userRoles;
      $component->selectmultiple('[form_restrictions_user_roles][]', [
        'options' => $userRoles,
        'class' => 'ppsgenselect',
        'selected' => !empty($settings['form_restrictions_user_roles']) ? $settings['form_restrictions_user_roles'] : ['show_all'],
      ]);
      ?>
    </div>
  </div>

  <?php include PPSGEN_PLUGIN_DIR . 'modules/contact/tpl/audience_rows.php'; ?>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form CLASS (optional)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[form_class]', ['value' => $component->value($settings['form_class'] ?? null)]); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form ID (optional)', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[form_id]', ['value' => $component->value($settings['form_id'] ?? null)]); ?>
    </div>
  </div>

  <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="login">
    <h3><?php esc_html_e('Login Form Fields & Options', 'ppsgen'); ?></h3>
    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email (required)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[logform_field_email]', [
          'options' => $this->allContactFields,
          'class' => '',
          'selected' => $component->value($settings['logform_field_email'] ?? null, 'email'),
        ]); ?>
      </div>
    </div>
    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Password (required)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[logform_field_password]', [
          'options' => $this->allContactFields,
          'class' => '',
          'selected' => $component->value($settings['logform_field_password'] ?? null, 'password'),
        ]); ?>
      </div>
    </div>
  </div>

  <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="create_contact">

    <h3><?php esc_html_e('Create / Identify Contact Form Fields & Options', 'ppsgen'); ?></h3>

    <div class="ppsgen-help-text-title"><span class="dashicons dashicons-welcome-learn-more"></span> Contact Create / Identify Form – Logic Overview
      <div class="ppsgen-help-text-btn" data-show="create_contact_logic_overview">Read More</div>
    </div>

    <div class="ppsgen-help-text" data-show="create_contact_logic_overview">
      <p><?php esc_html_e('This form creates a new contact or links a submission to an existing contact based on the email provided and user login status.', 'ppsgen'); ?></p>

      <h4><?php esc_html_e('If the user is logged in:', 'ppsgen'); ?></h4>
      <ul>
        <li><?php esc_html_e('If the email matches the user’s email, the contact is verified automatically.', 'ppsgen'); ?></li>
        <li><?php esc_html_e('If a contact exists:', 'ppsgen'); ?>
          <ul>
            <li><?php esc_html_e('You can update contact fields or only save the submission.', 'ppsgen'); ?></li>
          </ul>
        </li>
        <li><?php esc_html_e('If no contact exists, a new verified contact is created.', 'ppsgen'); ?></li>
        <li><?php esc_html_e('The submission is always saved and linked to the contact.', 'ppsgen'); ?></li>
      </ul>

      <h4><?php esc_html_e('If the user is not logged in:', 'ppsgen'); ?></h4>
      <ul>
        <li><?php esc_html_e('The system checks for a contact by email (default).', 'ppsgen'); ?></li>
        <li><?php esc_html_e('If a contact exists, the submission is linked to it with "unverified_submit" field flag.', 'ppsgen'); ?></li>
        <li><?php esc_html_e('If no contact exists, a new contact is created with unverified contact flag.', 'ppsgen'); ?></li>
      </ul>

      <p><b><?php esc_html_e('You can verify submits and contact by handle confirmation in Form/Contact (Submits Tab).', 'ppsgen'); ?></b></p>

    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Field in Form (required)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Form field that contains the contact email.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[form_logic_email_field]', [
          'options' => $this->allContactFields,
          'class' => '',
          'selected' => $component->value($settings['form_logic_email_field'] ?? null),
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Form Logic', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Choose what the form should do when Contact is Identified.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[form_logic]', [
          'options' => ['change_contact_field' => __('Change Contact Field (if user logged in)', 'ppsgen'), 'add_submit' => __('Add submit to Contact (dont change fields)', 'ppsgen')],
          'selected' => $component->value($settings['form_logic'] ?? 'add_submit'),
          'class' => 'data-option-toggle-trigger',
        ]); ?>
        <span><?php esc_html_e('* If no contact is identified by email, a new contact is always created.', 'ppsgen'); ?></span>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[form_logic]" data-toggle-val="change_contact_field">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Fields to Update Contact', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Select fields to update in contact. Leave empty to update all fields.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
      <?php $component->selectmultiple('[form_logic_update_fields][]', [
        'options' => $this->allContactFields,
        'selected' => !empty($settings['form_logic_update_fields']) ? $settings['form_logic_update_fields'] : [],
        'class' => 'ppsgenselect',
      ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Ask people to confirm their email (double opt-in)', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('The contact is saved straight away, but the form\'s tags (and the automations and lists they feed) are only added after the person clicks the link in the confirmation email.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[contact_email_confirmation]', ['checked' => $component->value($settings['contact_email_confirmation'] ?? null, '0'), 'class' => 'data-option-toggle-trigger']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[contact_email_confirmation]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Confirmation email subject', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->text('[contact_email_confirmation_subject]', ['value' => $component->value($settings['contact_email_confirmation_subject'] ?? null, __('Please confirm your email', 'ppsgen'))]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[contact_email_confirmation]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Confirmation email text', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Supports [website_name], [user_email], your field slugs such as [first_name], and [activation_link], which must stay in the text.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php wp_editor(
          isset($settings['html_contact_email_confirmation_text'])
            ? $this->getFrame()->core('helper')->wpKses($settings['html_contact_email_confirmation_text'])
            : __('Hi,<br><br>Thanks for signing up with [website_name]. Please confirm your email address by clicking the link below:<br><br><a href="[activation_link]">Confirm my email</a>', 'ppsgen'),
          'wp_editor_contact_email_confirmation',
          [
            'textarea_name' => $sprefix . '[html_contact_email_confirmation_text]',
            'wpautop' => false,
            'textarea_rows' => 6,
            'media_buttons' => false,
            'teeny' => true,
            'tinymce' => true,
            'quicktags' => true,
          ],
        ); ?>
      </div>
    </div>

  </div>

  <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="signup">
    <h3><?php esc_html_e('Signup Form Fields & Options', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('User Login (required)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
      <?php $component->select('[regform_user_login]', [
        'options' => $this->allContactFields,
        'class' => '',
        'selected' => $component->value($settings['regform_user_login'] ?? null, 'user_login'),
      ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email (required)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
      <?php $component->select('[regform_field_email]', [
        'options' => $this->allContactFields,
        'class' => '',
        'selected' => $component->value($settings['regform_field_email'] ?? null, 'email'),
      ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Password (required)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
      <?php $component->select('[regform_field_password]', [
        'options' => $this->allContactFields,
        'class' => '',
        'selected' => $component->value($settings['regform_field_password'] ?? null, 'password'),
      ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('First Name (optional)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
      <?php $component->select('[regform_first_name]', [
        'options' => $this->allContactFields,
        'class' => '',
        'selected' => $component->value($settings['regform_first_name'] ?? null, 'first_name'),
      ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Last Name (optional)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
      <?php $component->select('[regform_last_name]', [
        'options' => $this->allContactFields,
        'class' => '',
        'selected' => $component->value($settings['regform_last_name'] ?? null, 'last_name'),
      ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Nick Name (optional)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
      <?php $component->select('[regform_nick_name]', [
        'options' => $this->allContactFields,
        'class' => '',
        'selected' => $component->value($settings['regform_nick_name'] ?? null, ''),
      ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Display Name (optional)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[regform_display_name]', [
          'options' => $this->allContactFields,
          'class' => '',
          'selected' => $component->value($settings['regform_display_name'] ?? null, ''),
        ]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Select role for new WP user', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[regform_role]', [
          'options' => array_reverse(wp_roles()->get_names(), true),

          'selected' => $component->value($settings['regform_role'] ?? null, 'subscriber'),
        ]); ?>
      </div>
    </div>

  </div>

  <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="reset_password">

    <h3><?php esc_html_e('Reset Password Form Fields & Options', 'ppsgen'); ?></h3>
    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email (required)', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[resform_field_email]', [
          'options' => $this->allContactFields,
          'class' => '',
          'selected' => $component->value($settings['resform_field_email'] ?? null, 'email'),
        ]); ?>
      </div>
    </div>
  </div>

  <h3><?php esc_html_e('Submit Options', 'ppsgen'); ?></h3>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Redirect Link after Success Submit', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[redirect_link]', ['value' => $component->value($settings['redirect_link'] ?? null, '/'), 'attrs' => '']); ?>
    </div>
  </div>

  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Success message text after form submission', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php wp_editor(isset($settings['html_success_msg']) ? $this->getFrame()->core('helper')->wpKses($settings['html_success_msg']) : esc_html__('Thank you! Form has been successfully submitted.', 'ppsgen'), 'wp_editor_success_msg', [
        'textarea_name' => $sprefix . '[html_success_msg]',
        'wpautop' => false,
        'textarea_rows' => 6,
        'media_buttons' => true,
        'teeny' => false,
        'tinymce' => true,
        'quicktags' => true,
      ]); ?>
    </div>
  </div>

  <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="signup">

    <h4><?php esc_html_e('Contact Processing on submit', 'ppsgen'); ?></h4>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Create New Contact with Fields from Submitted Form Data', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[contact_handle_signup]', ['checked' => $component->value($settings['contact_handle_signup'] ?? null), 'class' => 'data-option-toggle-trigger']); ?>
      </div>
    </div>

  </div>

  <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="create_contact, signup">

    <h4><?php esc_html_e('Other options on submit', 'ppsgen'); ?></h4>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Add specific tag(s) to contact', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->selectmultiple('[specific_tags][]', [
          'options' => $this->allContactTags,
          'class' => 'ppsgenselect',
          'selected' => !empty($settings['specific_tags']) ? $settings['specific_tags'] : [],
        ]); ?>
      </div>
    </div>

  </div>

  <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="submit, create_contact, signup, reset_password">

    <h3><?php esc_html_e('SMTP / IMAP Config', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('SMTP / IMAP Config', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Which mail configuration should be used for emails sent by this form?', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->select('[mail_config_id]', ['options' => $this->getFrame()->getModule('dashboard')->getModel()->getMailConfigOptions(true), 'class' => '', 'selected' => $component->value($settings['mail_config_id'] ?? null, '')]); ?>
      </div>
    </div>

    <h3><?php esc_html_e('Template for Email', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Template', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Predefined Email Template from Email Campaigns & Automations Module', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php if (!empty($this->getFrame()->getModule('eca'))) { ?>
          <?php
          $templateList = $this->getFrame()->getModule('eca')->getModel()->getTemplatesList(true);
          $templateList = [0 => ''] + $templateList;
          ?>
          <?php $component->select('[template_id]', ['options' => $templateList, 'class' => '', 'selected' => $component->value($settings['template_id'] ?? null, '')]); ?>
        <?php } else { ?>
          <?php esc_html_e('Activate the Email Campaigns and Automations module to use predefined templates.', 'ppsgen'); ?>
        <?php } ?>
      </div>
    </div>

    <h3><?php esc_html_e('Send Form Data to Admin Email Options', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Send submitted form data to email', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'Enable this option to send submitted form data to specified email addresses. You can use field placeholders like [slug_field_1], [slug_field_2] in the message body.',
  'ppsgen',
); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[send_admin]', ['checked' => $component->value($settings['send_admin'] ?? null), 'class' => 'data-option-toggle-trigger']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[send_admin]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Recipients', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('List of email addresses to receive the form data. Use , symbol as separator.', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->email('[send_admin_email]', ['value' => $component->value($settings['send_admin_email'] ?? null), 'attrs' => 'multiple ']); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[send_admin]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Subject', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Subject of the email. Supports placeholders like [slug_field_1], [slug_field_2]', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->text('[send_admin_subject]', ['value' => $component->value($settings['send_admin_subject'] ?? null)]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[send_admin]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Body', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Message content. Use placeholders to include field values', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php wp_editor(isset($settings['html_send_admin_text']) ? $this->getFrame()->core('helper')->wpKses($settings['html_send_admin_text']) : '', 'wp_editor_send_admin_text', [
          'textarea_name' => $sprefix . '[html_send_admin_text]',
          'wpautop' => false,
          'textarea_rows' => 6,
          'media_buttons' => true,
          'teeny' => false,
          'tinymce' => true,
          'quicktags' => true,
        ]); ?>
      </div>
    </div>

    <h3><?php esc_html_e('Send to Contact Email Options', 'ppsgen'); ?></h3>

    <div class="ppsgenComponentRow">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Send success message to Contact email', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e(
  'Enable this option to send submitted form data to specified email addresses. You can use field placeholders like [slug_field_1], [slug_field_2] in the message body.',
  'ppsgen',
); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->checkboxToggle('[send_contact]', ['checked' => $component->value($settings['send_contact'] ?? null), 'class' => 'data-option-toggle-trigger']); ?>
      </div>
    </div>

    <div data-toggle-name="<?php ppsgen_esc_attr($prefix); ?>[form_type]" data-toggle-val="submit, create_contact">
      <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[send_contact]" data-toggle-val="1">
        <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Contact Email Field', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Form field that contains the contact email.', 'ppsgen'); ?>"></div>
        <div class="ppsgenComponentOption">
          <?php $component->select('[send_contact_email_field]', [
            'options' => $this->allContactFields,
            'class' => '',
            'selected' => $component->value($settings['send_contact_email_field'] ?? null),
          ]); ?>
        </div>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[send_contact]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Subject', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Subject of the email. Supports placeholders like [slug_field_1], [slug_field_2]', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php $component->text('[send_contact_subject]', ['value' => $component->value($settings['send_contact_subject'] ?? null)]); ?>
      </div>
    </div>

    <div class="ppsgenComponentRow" data-toggle-name="<?php ppsgen_esc_attr($sprefix); ?>[send_contact]" data-toggle-val="1">
      <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Email Body', 'ppsgen'); ?>" data-notice-label-desc="<?php esc_attr_e('Message content. Use placeholders to include field values', 'ppsgen'); ?>"></div>
      <div class="ppsgenComponentOption">
        <?php wp_editor(isset($settings['html_send_contact_text']) ? $this->getFrame()->core('helper')->wpKses($settings['html_send_contact_text']) : '', 'wp_editor_send_contact_text', [
          'textarea_name' => $sprefix . '[html_send_contact_text]',
          'wpautop' => false,
          'textarea_rows' => 6,
          'media_buttons' => true,
          'teeny' => false,
          'tinymce' => true,
          'quicktags' => true,
        ]); ?>
      </div>
    </div>

  </div>

  <?php if (!empty($options['obj_id'])) { ?>
  <h3><?php esc_html_e('Date Info', 'ppsgen'); ?></h3>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Created', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[created_at]', ['value' => $component->value($options['created_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <div class="ppsgenComponentRow">
    <div class="ppsgenComponentLabel" data-notice-label="<?php esc_attr_e('Date Time Updated', 'ppsgen'); ?>"></div>
    <div class="ppsgenComponentOption">
      <?php $component->text('[updated_at]', ['value' => $component->value($options['updated_at'] ?? null), 'attrs' => 'readonly ']); ?>
    </div>
  </div>
  <?php } ?>

</div>
