<?php
if (!defined('ABSPATH')) {
  exit();
} ?>
<div class="overviewContentStyle">
  <h3><span class="dashicons dashicons-admin-users"></span> Contacts (CRM)</h3>
  <div class="ppsgenTourOverviewActions">
    <button type="button" class="ppsgenTourInlineStartBtn ppsgenTourStartBtn" data-tour-id="contact_module">
      <span class="dashicons dashicons-controls-play"></span>
      <?php echo esc_html__('Start Contacts tutorial', 'ppsgen'); ?>
    </button>
  </div>
  <h4><?php echo esc_html__('Create and manage customer profiles with support for tags and custom contact fields.', 'ppsgen'); ?></h4>
  <ol>
    <li><?php echo esc_html__('This core module lets you create detailed customer profiles in one centralized place.', 'ppsgen'); ?></li>
    <li><?php echo esc_html__('Assign contacts to tags, add custom fields — and instantly use this data across other modules like Form, Email Campaigns & Automations, and Popup.', 'ppsgen'); ?></li>
    <li><?php echo esc_html__('Import and export your contact database with a single file for easy backup or migration.', 'ppsgen'); ?></li>
  </ol>
  <hr>

  <h4><?php echo esc_html__('Additional', 'ppsgen'); ?></h4>

  <p style="font-size:16px;"><b><?php echo esc_html__('You can use internal API methods on your website. This can be very useful for automation processes.', 'ppsgen'); ?></b></p>

  <hr>
  <h5><?php echo esc_html__('Get Contact Tags By Email', 'ppsgen'); ?></h5>
  <code>if (function_exists('ppsgen_get_contact_tags_by_email')) { $tags = ppsgen_get_contact_tags_by_email($email); }</code><br>
  <b>input:</b><br>
  $email (string) 'example@gmail.com'<br>
  <b>output:</b><br>
  $tags (array) ['1' => 'tag_1', '21' => 'tag_5', ...]<br>

  <hr>
  <h5><?php echo esc_html__('Delete Contact Tags By Email', 'ppsgen'); ?></h5>
  <code>if (function_exists('ppsgen_delete_contact_tags_by_email')) { ppsgen_delete_contact_tags_by_email($email, $tags); }</code><br>
  <b>input:</b><br>
  $email (string) 'example@gmail.com'<br>
  $tags (array) ['tag_1', 'tag_5']<br>
  <b>output:</b><br>
  $res (bool) - true, false<br>

  <hr>
  <h5><?php echo esc_html__('Add Tags to Contact By Email', 'ppsgen'); ?></h5>
  <code>if (function_exists('ppsgen_add_contact_tags_by_email')) { ppsgen_add_contact_tags_by_email($email, $tags); }</code><br>
  <b>input:</b><br>
  $email (string) 'example@gmail.com'<br>
  $tags (array) ['tag_1', 'tag_5']<br>
  <b>output:</b><br>
  $res (bool) - true, false<br>
  <br>
  <p><?php echo esc_html__('* If the contact or tag(s) does not exist, it will be created automatically.', 'ppsgen'); ?></p>

  <hr>

  <br>
</div>
