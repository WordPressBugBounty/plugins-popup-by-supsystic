<?php
if (!defined('ABSPATH')) {
  exit();
} ?>
<section class="overviewContentStyle">
  <h3>
    <span class="dashicons dashicons-email-alt"></span>
    <?php echo esc_html__('Email campaigns & automations', 'ppsgen'); ?>
  </h3>
  <div class="ppsgenTourOverviewActions">
    <button type="button" class="ppsgenTourInlineStartBtn ppsgenTourStartBtn" data-tour-id="eca_module">
      <span class="dashicons dashicons-controls-play"></span>
      <?php echo esc_html__('Start Email Campaigns tutorial', 'ppsgen'); ?>
    </button>
  </div>

  <h4>
    <?php echo esc_html__('A streamlined system for targeted email campaigns and automated delivery campaigns', 'ppsgen'); ?>
  </h4>
  <p>
    <?php echo esc_html__(
      'The ECA (Email Campaigns & Automations) module provides a clean and efficient way to send one-time email broadcasts and build step-based automated sequences. It allows you to segment contacts, apply conditional logic, and deliver personalized messages using reusable email templates.',
      'ppsgen',
    ); ?>
  </p>

  <p>
    <?php echo esc_html__(
      'Automations are built as campaigns of steps (emails) triggered by contact data and segmentation rules. Each campaign supports delays, conditional execution, exit rules, and queued processing to ensure accurate timing and predictable delivery. This keeps the system lightweight while remaining reliable.',
      'ppsgen',
    ); ?>
  </p>

  <hr>

  <h4>
    <?php echo esc_html__('Getting Started', 'ppsgen'); ?>
  </h4>

  <h5>
    <?php echo esc_html__('1. Email Server – General Settings', 'ppsgen'); ?>
  </h5>

  <ol>
    <li>
      <?php echo esc_html__('Go to the plugin global settings and configure your email server connection and related options:', 'ppsgen'); ?>
      <br>

      <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=dashboard_options')); ?>">
        <?php echo esc_html__('Open Global Settings', 'ppsgen'); ?>
      </a>
    </li>
  </ol>

  <h5>
    <?php echo esc_html__('2. Email Templates', 'ppsgen'); ?>
  </h5>

  <ol>
    <li>
      <?php echo esc_html__('Navigate to Campaigns → Email templates:', 'ppsgen'); ?>
      <br>

      <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_templates')); ?>">
        <?php echo esc_html__('Open Templates', 'ppsgen'); ?>
      </a>
    </li>

    <li>
      <?php echo esc_html__('On the template editing page, use the visual page builder to configure the global design of your email messages. Review the predefined “Default Emails Campaign Template” as a reference.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('Add the mandatory element “Email Step / Campaign Content”. The content of each Step (Email) from the linked Campaign will be injected into this element before sending.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('Add the mandatory element “Unsubscribe” at the bottom of the email. This element automatically inserts a generated unsubscribe link for the contact.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('Use the “Load Preview” button in the top-right corner to preview how the email will appear in the recipient’s email client.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('Click “Save”. If the Template is already linked to one or more Campaigns, updated templates will be automatically regenerated for all related Steps.', 'ppsgen'); ?>
    </li>
  </ol>

  <h5>
    <?php echo esc_html__('3. Creating Email Campaigns & Automation Campaigns', 'ppsgen'); ?>
  </h5>

  <ol>
    <li>
      <?php echo esc_html__('Navigate to Campaigns → Automations:', 'ppsgen'); ?>
      <br>

      <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_chains')); ?>">
        <?php echo esc_html__('Open Campaigns', 'ppsgen'); ?>
      </a>
    </li>

    <li>
      <?php echo esc_html__('In the Campaign editor, the top horizontal timeline represents all Steps (Emails) in the Campaign. You can add, remove, and reorder Steps using drag & drop.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('Clicking a Step loads its content and options. Use the page builder to configure the email content and carefully define Step Conditions. The email will be sent only if the contact meets these conditions at execution time.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('In the main Campaign Options tab, configure Conditions & Segmentation and click “Save Campaign Options”. Any contact that matches these conditions will automatically enter the Campaign.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('To apply the Campaign to existing contacts, click “Apply Campaign to Contacts”. The process runs via the queue and may take several minutes.', 'ppsgen'); ?>
    </li>
  </ol>

  <h5>
    <?php echo esc_html__('4. Delivery Process & Tracking', 'ppsgen'); ?>
  </h5>

  <ol>
    <li>
      <?php echo esc_html__('Active executions are available in Campaigns → Automations → People in automations:', 'ppsgen'); ?>
      <br>

      <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_flows')); ?>">
        <?php echo esc_html__('Open Flows', 'ppsgen'); ?>
      </a>
    </li>

    <li>
      <?php echo esc_html__('Email interaction events (opens and link clicks) are available in Reports → Email activity:', 'ppsgen'); ?>
      <br>

      <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_step_events')); ?>">
        <?php echo esc_html__('Open Email Events', 'ppsgen'); ?>
      </a>
    </li>
  </ol>

  <h5>
    <?php echo esc_html__('5. Conversion Tracking API', 'ppsgen'); ?>
  </h5>

  <p style="font-size:16px;"><b><?php echo esc_html__('You can use internal API methods to register successful purchases, subscriptions, paid orders, or any other business conversion and connect it back to your campaigns.', 'ppsgen'); ?></b></p>

  <p>
    <?php echo esc_html__(
      'The conversion API is universal. It does not depend on WooCommerce, Easy Digital Downloads, Google Analytics, or Supsystic checkout. Call it from your own order/payment hook when the conversion is confirmed.',
      'ppsgen',
    ); ?>
  </p>

  <ol>
    <li>
      <?php echo esc_html__('Tracked conversions are available in Reports → Conversions:', 'ppsgen'); ?>
      <br>

      <a href="<?php echo esc_url(admin_url('admin.php?page=ppsgen&tab=eca_conversions')); ?>">
        <?php echo esc_html__('Open Conversions', 'ppsgen'); ?>
      </a>
    </li>
  </ol>

  <hr>
  <h5><?php echo esc_html__('Register Conversion', 'ppsgen'); ?></h5>
  <code>if (function_exists('ppsgen_set_eca_conversion')) { $conversion_id = ppsgen_set_eca_conversion($data); }</code><br>
  <b>input:</b><br>
  $data (array)<br>
  <pre><code>array(
  'email' => 'customer@example.com',
  'conversion_key' => 'purchase',
  'external_id' => 12345,
  'status' => 'paid',
  'value' => 49.00,
  'currency' => 'USD',
  'source' => 'my_checkout',
  'product_id' => 'gallery-pro',
  'product_name' => 'Gallery PRO',
)</code></pre>
  <b>output:</b><br>
  $conversion_id (int|false) - created/updated conversion ID or false<br>
  <p><?php echo esc_html__('* If conversion_key + external_id + source already exists, the same conversion row is updated instead of creating a duplicate.', 'ppsgen'); ?></p>
  <p><?php echo esc_html__('* Attribution is resolved automatically: browser token first, then the latest email click for this contact email, then the latest sent campaign email within the attribution window.', 'ppsgen'); ?></p>

  <hr>
  <h5><?php echo esc_html__('Successful Order Example', 'ppsgen'); ?></h5>
  <pre><code>if ($status == 'paid' &amp;&amp; function_exists('ppsgen_set_eca_conversion')) {
    ppsgen_set_eca_conversion(array(
        'email' => $customerEmail,
        'conversion_key' => 'purchase',
        'external_id' => $orderId,
        'status' => 'paid',
        'value' => $orderTotal,
        'currency' => $orderCurrency,
        'source' => 'my_checkout',
        'product_id' => $productId,
        'product_name' => $productName,
    ));
}</code></pre>

  <hr>
  <h5><?php echo esc_html__('Browser Attribution Token', 'ppsgen'); ?></h5>
  <code>$attribution = function_exists('ppsgen_get_eca_browser_attribution') ? ppsgen_get_eca_browser_attribution() : false;</code><br>
  <b>output:</b><br>
  $attribution (array|false) - contact_id, chain_id, step_id, flow_id, click_event_id<br>
  <p><?php echo esc_html__('After a tracked email click, the plugin stores a signed browser attribution token for 180 days in a cookie and localStorage. This token contains campaign IDs, not the contact email.', 'ppsgen'); ?></p>
  <p><?php echo esc_html__('If your frontend checkout needs to pass the token to the backend, call window.ppsgenAttachEcaAttributionToForm(formElement) or send window.ppsgenGetEcaAttributionToken() with your checkout request.', 'ppsgen'); ?></p>

  <hr>
  <h5><?php echo esc_html__('Get Contact Attribution', 'ppsgen'); ?></h5>
  <code>if (function_exists('ppsgen_get_contact_eca_attribution')) { $attribution = ppsgen_get_contact_eca_attribution($email, array('window_days' => 180)); }</code><br>
  <b>input:</b><br>
  $email (string) 'customer@example.com'<br>
  <b>output:</b><br>
  $attribution (array) - latest known ECA attribution for this contact<br>

  <hr>

  <h5>
    <?php echo esc_html__('6. System Notes', 'ppsgen'); ?>
  </h5>

  <ol style="color:red;">
    <li>
      <?php echo esc_html__('The system uses WP_CRON to process email batches and conditions every minute.', 'ppsgen'); ?>
    </li>

    <li>
      <?php echo esc_html__('WP_CRON is not a real system cron. For production environments, it is strongly recommended to configure a real server cron job that triggers the site every 1 minute.', 'ppsgen'); ?>
    </li>
  </ol>

  <p>
    <?php echo esc_html__('We hope you enjoy working with the Supsystic Email Campaigns & Automations module.', 'ppsgen'); ?>
  </p>

</section>
