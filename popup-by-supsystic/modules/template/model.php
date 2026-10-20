<?php

if (!defined('ABSPATH')) {
  exit();
}

require_once __DIR__ . '/package.php';

class TemplateModelPpsgen extends BaseModelPpsgen
{
  private $redirectUrl = null;
  private bool $showProPopup = false;

  public function installTables()
  {
  }

  public function setRedirectUrl($url)
  {
    $this->redirectUrl = esc_url_raw($url);
  }

  public function getRedirectUrl()
  {
    return $this->redirectUrl;
  }

  public function setShowProPopup(bool $show): void
  {
    $this->showProPopup = $show;
  }

  public function getShowProPopup(): bool
  {
    return $this->showProPopup;
  }

  public function templateAddSubmit($template_code, $template_type, $template_name)
  {
    $this->setShowProPopup(false);
    if (empty($template_type)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Something went wrong. Template type is empty. Please repeat creating from start.', 'ppsgen'));
    }
    if (empty($template_code)) {
      $redirect = '/';
      switch ($template_type) {
        case 'form':
          $redirect = admin_url('admin.php?page=ppsgen&tab=form_edit');
          break;
        case 'popup':
          $redirect = admin_url('admin.php?page=ppsgen&tab=popup_edit');
          break;
        case 'email_template':
          $redirect = admin_url('admin.php?page=ppsgen&tab=eca_template_edit');
          break;
      }
      $this->setStatus('success');
      $this->setMessage(esc_html__('Success. Waiting for redirect.', 'ppsgen'));
      $this->setRedirectUrl($redirect);
      return true;
    }
    // PRO templates need full PRO (an expired/frozen license only keeps existing PRO items running)
    $templateCodes = array_column($this->getTemplateList(), 'pro', 'code');
    if (!empty($templateCodes[$template_code]) && !(function_exists('ppsgen_is_pro_license_active') && ppsgen_is_pro_license_active())) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('This template is available in PRO only.', 'ppsgen'));
      $this->setShowProPopup(true);
      return true;
    }
    if (!array_key_exists($template_code, $templateCodes)) {
      $this->setStatus('error');
      $this->setMessage(esc_html__('Unknown template.', 'ppsgen'));
      return true;
    }
    if (!empty($templateCodes[$template_code])) {
      // PRO templates come from the template server through the PRO add-on (signed packages)
      $data = apply_filters('ppsgen_template_install_pro', null, $template_code, $template_name, $template_type);
      if ($data === null) {
        $this->setStatus('error');
        $this->setMessage(esc_html__('This template is available in PRO only.', 'ppsgen'));
        $this->setShowProPopup(true);
        return true;
      }
    } else {
      // FREE templates are part of the plugin: installed from its own checked package, nothing is downloaded
      $package = TemplatePackagePpsgen::readLocal($template_code);
      $data = is_wp_error($package) ? $package : $this->installTemplate(TemplatePackagePpsgen::placeholders($package), $template_name);
    }
    if (is_wp_error($data)) {
      $this->setStatus('error');
      $this->setMessage(esc_html($data->get_error_message()));
      return true;
    }
    if (!empty($data)) {
      switch ($template_type) {
        case 'form':
          $msg = esc_html__('Form Successfully Created. Waiting for redirect.', 'ppsgen');
          if (!empty($data['form_id']) && !empty($data['popup_id'])) {
            $msg = esc_html__('Form and Popup Successfully Created. Waiting for redirect.', 'ppsgen');
          }
          $redirect = admin_url('admin.php?page=ppsgen&tab=form_edit') . '&id=' . (int) $data['form_id'];
          break;
        case 'popup':
          $msg = esc_html__('Popup Successfully Created. Waiting for redirect.', 'ppsgen');
          if (!empty($data['form_id']) && !empty($data['popup_id'])) {
            $msg = esc_html__('Popup and Form Successfully Created. Waiting for redirect.', 'ppsgen');
          }
          $redirect = admin_url('admin.php?page=ppsgen&tab=popup_edit') . '&id=' . (int) $data['popup_id'];
          break;
        case 'email_template':
          $msg = esc_html__('Email Template Successfully Created. Waiting for redirect.', 'ppsgen');
          $redirect = admin_url('admin.php?page=ppsgen&tab=eca_template_edit') . '&id=' . (int) $data['email_template_id'];
          break;
      }
      $this->setStatus('success');
      $this->setMessage($msg);
      $this->setRedirectUrl($redirect);
      return true;
    }
    $this->setStatus('error');
    $this->setMessage(esc_html__('The template could not be installed. Please try again or contact support.', 'ppsgen'));
    return true;
  }

  /**
   * Filter buttons in this order: access (FREE, PRO), then broad topics, then industries, then technical kinds.
   * Tags not listed here are shown after these.
   */
  public function getTemplateTagOrder()
  {
    return [
      'FREE',
      'PRO',
      'With Forms',
      'First Steps',
      'Holidays & Seasons',
      'Sales & Promotions',
      'Subscribe & Leads',
      'Contact & Booking',
      'Site Notices',
      'Luxury',
      'Online Store',
      'Real Estate',
      'Cars',
      'Travel & Hotels',
      'Restaurants',
      'Beauty & Wellness',
      'Education',
      'Business & SaaS',
      'WordPress Forms',
      'Video & Embeds',
    ];
  }

  /**
   * Order of the cards: FREE first (in list order), then PRO themes in this order — holidays by the calendar,
   * then sales, leads, contact, then industries — so similar designs sit next to each other. A key is the template
   * code without the popup_/form_ prefix and the _form suffix (a theme's popup and form share it).
   */
  public function getTemplateThemeOrder()
  {
    return [
      'new_year_midnight_gala',
      'season_lunar_new_year',
      'valentine_love_letter',
      'season_womens_day',
      'season_eid',
      'easter_egg_hunt',
      'season_earth_day',
      'spring_bloom',
      'mothers_day_peonies',
      'fathers_day_tweed',
      'season_july_fourth',
      'summer_sale_sunset_wave',
      'back_to_school_notebook',
      'halloween_spooky_sale',
      'season_diwali',
      'season_singles_day',
      'season_winter_drop',
      'thanksgiving_harvest',
      'black_friday_early_access',
      'season_small_business',
      'cyber_monday_decrypt',
      'game_advent_calendar',
      'early_xmas_sale',
      'shop_holiday_delivery',
      'christmas_party',
      'season_boxing_day',
      'ultimate_holiday',
      'flash_sale_lightning',
      'spin_win_fortune',
      'free_shipping_parcel',
      'exit_door',
      'cart_recovery_boutique',
      'step_yes_no_ticket',
      'step_mystery_box',
      'game_scratch_card',
      'game_pick_card',
      'game_daily_deal',
      'step_quiz_roast',
      'step_exit_survey',
      'bar_sale_countdown',
      'bar_free_shipping',
      'shop_gift_with_order',
      'shop_bundle_duo',
      'shop_low_stock',
      'shop_back_in_stock',
      'shop_price_drop',
      'shop_vip_club',
      'shop_refer_friend',
      'shop_thank_you_next',
      'shop_app_download',
      'shop_sms_signup',
      'shop_birthday_club',
      'shop_review_stars',
      'welcome_mat_linen',
      'newsletter_morning_letter',
      'ebook_playbook',
      'lead_content_upgrade',
      'lead_gated_article',
      'lead_podcast',
      'lead_event_ticket',
      'lead_donation',
      'lead_job_alerts',
      'webinar_live_masterclass',
      'launch_orbit',
      'saas_trial_glass',
      'video_demo_glow',
      'slidein_read_next',
      'coming_soon_aurora',
      'lead_book_demo',
      'lead_consultation',
      'callback_concierge',
      'fb_nps_score',
      'fb_heard_about_us',
      'fb_bug_idea',
      'fb_idle_survey',
      'slidein_chat_call',
      'step_booking_visit',
      'feedback_letterpress',
      'step_home_valuation',
      'real_estate_golden_hour',
      'lux_villa_riviera',
      'lux_penthouse_noir',
      'lux_chateau',
      'lux_grand_tourer',
      'lux_heritage_classic',
      'lux_chauffeur',
      'travel_postcard',
      'tour_hot_deals',
      'tour_finder_quiz',
      'tour_guided_walk',
      'tour_early_booking',
      'tour_travel_club',
      'hotel_book_direct',
      'hotel_stay_longer',
      'hotel_exit_rate',
      'cruise_itinerary',
      'cruise_cabin_picker',
      'cruise_last_cabins',
      'lux_grand_hotel',
      'lux_private_jet',
      'restaurant_maison',
      'rest_table_booking',
      'rest_delivery',
      'rest_craving_picker',
      'rest_free_delivery_timer',
      'beauty_salon_velvet',
      'lux_parfum_maison',
      'lux_spa_retreat',
      'lux_barber',
      'fitness_pulse',
      'online_course_atelier',
      'lux_masterclass',
      'editorial_journal',
      'bold_promo',
      'saas_update',
      'adblock_red_alert',
      'adblock_midnight_orange',
      'adblock_frost_portal',
      'age_gate_crimson',
      'age_gate_midnight_orange',
      'age_gate_ivory',
    ];
  }

  /** Template list sorted for the catalogue (see getTemplateThemeOrder) */
  public function getSortedTemplateList()
  {
    $list = $this->getTemplateList();
    $rank = array_flip($this->getTemplateThemeOrder());
    $keyed = [];
    foreach (array_values($list) as $i => $tpl) {
      $key = preg_replace(['/^(popup|form|email)_/', '/_form$/'], '', (string) $tpl['code']);
      $keyed[] = [empty($tpl['pro']) ? 0 : 1, $rank[$key] ?? PHP_INT_MAX, $i, $tpl];
    }
    usort($keyed, static function ($a, $b) {
      return [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]];
    });
    return array_column($keyed, 3);
  }

  public function getTemplateList()
  {
    return [
      [
        'name' => 'Login Form Alpha',
        'code' => 'form_login_alpha',
        'module' => ['form'],
        'tags' => ['FREE', 'First Steps', 'WordPress Forms'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-login-alpha.webp',
      ],
      [
        'name' => 'Sign Up Form Alpha',
        'code' => 'form_signup_alpha',
        'module' => ['form'],
        'tags' => ['FREE', 'First Steps', 'WordPress Forms'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-signup-alpha.webp',
      ],
      [
        'name' => 'Reset Password Form Alpha',
        'code' => 'form_resetpass_alpha',
        'module' => ['form'],
        'tags' => ['FREE', 'First Steps', 'WordPress Forms'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-resetpass-alpha.webp',
      ],
      [
        'name' => 'Logout Form Alpha',
        'code' => 'form_logout_alpha',
        'module' => ['form'],
        'tags' => ['FREE', 'First Steps', 'WordPress Forms'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-logout-alpha.webp',
      ],
      [
        'name' => 'Subscribe Form Alpha',
        'code' => 'form_subscribe_alpha',
        'module' => ['form'],
        'tags' => ['FREE', 'First Steps', 'Subscribe & Leads'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-subscribe-alpha.webp',
      ],
      [
        'name' => 'Update Contact Form Alpha',
        'code' => 'form_update_contact_alpha',
        'module' => ['form'],
        'tags' => ['FREE', 'First Steps', 'Contact & Booking'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-update-contact-alpha.webp',
      ],
      [
        'name' => 'Data Collection Form Alpha',
        'code' => 'form_data_collection_alpha',
        'module' => ['form'],
        'tags' => ['FREE', 'First Steps', 'Contact & Booking'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-data-collection-alpha.webp',
      ],
      [
        'name' => 'YouTube Video Alpha',
        'code' => 'popup_youtube_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'First Steps', 'Video & Embeds'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-youtube-alpha.webp',
      ],
      [
        'name' => 'Iframe Window Alpha',
        'code' => 'popup_iframe_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'First Steps', 'Video & Embeds'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-iframe-alpha.webp',
      ],
      [
        'name' => 'Facebook Page Iframe Alpha',
        'code' => 'popup_iframe_fbpage_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'First Steps', 'Video & Embeds'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-iframe-fbpage-alpha.webp',
      ],
      [
        'name' => 'Google Maps Iframe Alpha',
        'code' => 'popup_iframe_gmaps_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'First Steps', 'Video & Embeds'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-iframe-gmaps-alpha.webp',
      ],
      [
        'name' => 'Popup & Form Alpha',
        'code' => 'popup_form_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'With Forms', 'First Steps', 'Subscribe & Leads'],
        'pro' => false,
        'features' => ['form'],
        'preview' => 'popup-form-alpha.webp',
      ],
      [
        'name' => 'Example of Element Click Events',
        'code' => 'popup_example_click_events',
        'module' => ['popup'],
        'tags' => ['FREE', 'First Steps'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-example-click-events.webp',
      ],
      [
        'name' => 'Bottom Popup & Form Subscribe + Social Alpha',
        'code' => 'popup_bottom_subscribe_social_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'With Forms', 'First Steps', 'Subscribe & Leads'],
        'pro' => false,
        'features' => ['form'],
        'preview' => 'popup-bottom-subscribe-social-alpha.webp',
      ],
      [
        'name' => 'Google Maps Appointment Popup & Form Alpha',
        'code' => 'popup_gmaps_appointment_form_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'With Forms', 'First Steps', 'Contact & Booking', 'Video & Embeds'],
        'pro' => false,
        'features' => ['form'],
        'preview' => 'popup-gmaps-appointment-form-alpha.webp',
      ],
      [
        'name' => 'YouTube & Courses Form Alpha',
        'code' => 'popup_youtube_courses_form_alpha',
        'module' => ['popup'],
        'tags' => ['FREE', 'With Forms', 'First Steps', 'Subscribe & Leads', 'Education', 'Video & Embeds'],
        'pro' => false,
        'features' => ['form'],
        'preview' => 'popup-youtube-courses-form-alpha.webp',
      ],
      [
        'name' => 'Christmas Party Popup',
        'code' => 'popup_christmas_party_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-christmas-party.webp',
      ],
      [
        'name' => 'Ultimate Holiday Discount',
        'code' => 'popup_ultimate_holiday_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-ultimate-holiday.webp',
      ],
      [
        'name' => 'Early Xmas Sale',
        'code' => 'popup_early_xmas_sale_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-early-xmas-sale.webp',
      ],
      [
        'name' => 'Back to School Notebook',
        'code' => 'popup_back_to_school_notebook_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-back-to-school-notebook.webp',
      ],
      [
        'name' => 'Back to School Notebook Form',
        'code' => 'form_back_to_school_notebook',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-back-to-school-notebook.webp',
      ],
      [
        'name' => 'Black Friday Early Access',
        'code' => 'popup_black_friday_early_access_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-black-friday-early-access.webp',
      ],
      [
        'name' => 'Black Friday Early Access Form',
        'code' => 'form_black_friday_early_access',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-black-friday-early-access.webp',
      ],
      [
        'name' => 'Callback Concierge',
        'code' => 'popup_callback_concierge_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-callback-concierge.webp',
      ],
      [
        'name' => 'Callback Concierge Form',
        'code' => 'form_callback_concierge',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-callback-concierge.webp',
      ],
      [
        'name' => 'Cart Recovery Boutique Bag',
        'code' => 'popup_cart_recovery_boutique_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-cart-recovery-boutique.webp',
      ],
      [
        'name' => 'Cart Recovery Boutique Bag Form',
        'code' => 'form_cart_recovery_boutique',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-cart-recovery-boutique.webp',
      ],
      [
        'name' => 'Cyber Monday Decrypt',
        'code' => 'popup_cyber_monday_decrypt_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-cyber-monday-decrypt.webp',
      ],
      [
        'name' => 'Cyber Monday Decrypt Form',
        'code' => 'form_cyber_monday_decrypt',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-cyber-monday-decrypt.webp',
      ],
      [
        'name' => 'Easter Egg Hunt',
        'code' => 'popup_easter_egg_hunt_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-easter-egg-hunt.webp',
      ],
      [
        'name' => 'Easter Egg Hunt Form',
        'code' => 'form_easter_egg_hunt',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-easter-egg-hunt.webp',
      ],
      [
        'name' => 'Fathers Day Tweed and Leather',
        'code' => 'popup_fathers_day_tweed_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-fathers-day-tweed.webp',
      ],
      [
        'name' => 'Fathers Day Tweed and Leather Form',
        'code' => 'form_fathers_day_tweed',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-fathers-day-tweed.webp',
      ],
      [
        'name' => 'Flash Sale Lightning',
        'code' => 'popup_flash_sale_lightning_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-flash-sale-lightning.webp',
      ],
      [
        'name' => 'Flash Sale Lightning Form',
        'code' => 'form_flash_sale_lightning',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-flash-sale-lightning.webp',
      ],
      [
        'name' => 'Free Shipping Parcel',
        'code' => 'popup_free_shipping_parcel_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-free-shipping-parcel.webp',
      ],
      [
        'name' => 'Free Shipping Parcel Form',
        'code' => 'form_free_shipping_parcel',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-free-shipping-parcel.webp',
      ],
      [
        'name' => 'Halloween Spooky Sale',
        'code' => 'popup_halloween_spooky_sale_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-halloween-spooky-sale.webp',
      ],
      [
        'name' => 'Halloween Spooky Sale Form',
        'code' => 'form_halloween_spooky_sale',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-halloween-spooky-sale.webp',
      ],
      [
        'name' => 'Product Launch Orbit Waitlist',
        'code' => 'popup_launch_orbit_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-launch-orbit.webp',
      ],
      [
        'name' => 'Product Launch Orbit Waitlist Form',
        'code' => 'form_launch_orbit',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-launch-orbit.webp',
      ],
      [
        'name' => 'Luxury Chauffeur Service',
        'code' => 'popup_lux_chauffeur_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Luxury', 'Cars'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-chauffeur.webp',
      ],
      [
        'name' => 'Luxury Chauffeur Service Form',
        'code' => 'form_lux_chauffeur',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Luxury', 'Cars'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-chauffeur.webp',
      ],
      [
        'name' => 'Luxury Executive Masterclass',
        'code' => 'popup_lux_masterclass_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Luxury', 'Education'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-masterclass.webp',
      ],
      [
        'name' => 'Luxury Executive Masterclass Form',
        'code' => 'form_lux_masterclass',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Luxury', 'Education'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-masterclass.webp',
      ],
      [
        'name' => 'Mothers Day Watercolor Peonies',
        'code' => 'popup_mothers_day_peonies_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-mothers-day-peonies.webp',
      ],
      [
        'name' => 'Mothers Day Watercolor Peonies Form',
        'code' => 'form_mothers_day_peonies',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-mothers-day-peonies.webp',
      ],
      [
        'name' => 'New Year Midnight Gala',
        'code' => 'popup_new_year_midnight_gala_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-new-year-midnight-gala.webp',
      ],
      [
        'name' => 'New Year Midnight Gala Form',
        'code' => 'form_new_year_midnight_gala',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-new-year-midnight-gala.webp',
      ],
      [
        'name' => 'Newsletter Morning Letter',
        'code' => 'popup_newsletter_morning_letter_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-newsletter-morning-letter.webp',
      ],
      [
        'name' => 'Newsletter Morning Letter Form',
        'code' => 'form_newsletter_morning_letter',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-newsletter-morning-letter.webp',
      ],
      [
        'name' => 'Restaurant Maison Reservation',
        'code' => 'popup_restaurant_maison_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Restaurants'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-restaurant-maison.webp',
      ],
      [
        'name' => 'Restaurant Maison Reservation Form',
        'code' => 'form_restaurant_maison',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Restaurants'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-restaurant-maison.webp',
      ],
      [
        'name' => 'Spring Bloom Collection',
        'code' => 'popup_spring_bloom_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-spring-bloom.webp',
      ],
      [
        'name' => 'Spring Bloom Collection Form',
        'code' => 'form_spring_bloom',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-spring-bloom.webp',
      ],
      [
        'name' => 'Summer Sale Sunset Wave',
        'code' => 'popup_summer_sale_sunset_wave_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-summer-sale-sunset-wave.webp',
      ],
      [
        'name' => 'Summer Sale Sunset Wave Form',
        'code' => 'form_summer_sale_sunset_wave',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-summer-sale-sunset-wave.webp',
      ],
      [
        'name' => 'Thanksgiving Harvest Wreath',
        'code' => 'popup_thanksgiving_harvest_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-thanksgiving-harvest.webp',
      ],
      [
        'name' => 'Thanksgiving Harvest Wreath Form',
        'code' => 'form_thanksgiving_harvest',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-thanksgiving-harvest.webp',
      ],
      [
        'name' => 'Travel Postcard Deals',
        'code' => 'popup_travel_postcard_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-travel-postcard.webp',
      ],
      [
        'name' => 'Travel Postcard Deals Form',
        'code' => 'form_travel_postcard',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-travel-postcard.webp',
      ],
      [
        'name' => 'Luxury Grand Tourer Test Drive',
        'code' => 'popup_lux_grand_tourer_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Luxury', 'Cars'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-grand-tourer.webp',
      ],
      [
        'name' => 'Luxury Grand Tourer Test Drive Form',
        'code' => 'form_lux_grand_tourer',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Luxury', 'Cars'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-grand-tourer.webp',
      ],
      [
        'name' => 'Luxury Chateau Estate',
        'code' => 'popup_lux_chateau_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Luxury', 'Real Estate'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-chateau.webp',
      ],
      [
        'name' => 'Luxury Chateau Estate Form',
        'code' => 'form_lux_chateau',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Luxury', 'Real Estate'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-chateau.webp',
      ],
      [
        'name' => 'Luxury Private Jet Charter',
        'code' => 'popup_lux_private_jet_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Luxury', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-private-jet.webp',
      ],
      [
        'name' => 'Luxury Private Jet Charter Form',
        'code' => 'form_lux_private_jet',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Luxury', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-private-jet.webp',
      ],
      [
        'name' => 'Luxury Penthouse Noir',
        'code' => 'popup_lux_penthouse_noir_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Luxury', 'Real Estate'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-penthouse-noir.webp',
      ],
      [
        'name' => 'Luxury Penthouse Noir Form',
        'code' => 'form_lux_penthouse_noir',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Luxury', 'Real Estate'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-penthouse-noir.webp',
      ],
      [
        'name' => 'Luxury Villa Riviera',
        'code' => 'popup_lux_villa_riviera_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Luxury', 'Real Estate'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-villa-riviera.webp',
      ],
      [
        'name' => 'Luxury Villa Riviera Form',
        'code' => 'form_lux_villa_riviera',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Luxury', 'Real Estate'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-villa-riviera.webp',
      ],
      [
        'name' => 'Exit Intent Door Ajar',
        'code' => 'popup_exit_door_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-exit-door.webp',
      ],
      [
        'name' => 'Exit Intent Door Ajar Form',
        'code' => 'form_exit_door',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-exit-door.webp',
      ],
      [
        'name' => 'Luxury Heritage Classic Cars',
        'code' => 'popup_lux_heritage_classic_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Luxury', 'Cars'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-heritage-classic.webp',
      ],
      [
        'name' => 'Luxury Heritage Classic Cars Form',
        'code' => 'form_lux_heritage_classic',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Luxury', 'Cars'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-heritage-classic.webp',
      ],
      [
        'name' => 'AdBlock Detected Red Alert',
        'code' => 'popup_adblock_red_alert',
        'module' => ['popup'],
        'tags' => ['PRO', 'Site Notices'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-adblock-red-alert.webp',
      ],
      [
        'name' => 'AdBlock Detected Midnight Orange',
        'code' => 'popup_adblock_midnight_orange',
        'module' => ['popup'],
        'tags' => ['PRO', 'Site Notices'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-adblock-midnight-orange.webp',
      ],
      [
        'name' => 'AdBlock Detected Frost Portal',
        'code' => 'popup_adblock_frost_portal',
        'module' => ['popup'],
        'tags' => ['PRO', 'Site Notices'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-adblock-frost-portal.webp',
      ],
      [
        'name' => 'Age Verification Crimson 18+',
        'code' => 'popup_age_gate_crimson',
        'module' => ['popup'],
        'tags' => ['PRO', 'Site Notices'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-age-gate-crimson.webp',
      ],
      [
        'name' => 'Age Verification Midnight Orange 18+',
        'code' => 'popup_age_gate_midnight_orange',
        'module' => ['popup'],
        'tags' => ['PRO', 'Site Notices'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-age-gate-midnight-orange.webp',
      ],
      [
        'name' => 'Age Verification Ivory 18+',
        'code' => 'popup_age_gate_ivory',
        'module' => ['popup'],
        'tags' => ['PRO', 'Site Notices'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-age-gate-ivory.webp',
      ],
      [
        'name' => 'Announcement Top Bar',
        'code' => 'popup_bar_announcement',
        'module' => ['popup'],
        'tags' => ['FREE', 'Sales & Promotions', 'Site Notices'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-bar-announcement.webp',
      ],
      [
        'name' => 'Sale Ends Countdown Bar',
        'code' => 'popup_bar_sale_countdown',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-bar-sale-countdown.webp',
      ],
      [
        'name' => 'Free Shipping Bottom Bar',
        'code' => 'popup_bar_free_shipping',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-bar-free-shipping.webp',
      ],
      [
        'name' => 'Full Screen Welcome Mat',
        'code' => 'popup_welcome_mat_linen',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-welcome-mat-linen.webp',
      ],
      [
        'name' => 'Full Screen Welcome Mat Form',
        'code' => 'form_welcome_mat_linen',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-welcome-mat-linen.webp',
      ],
      [
        'name' => 'Coming Soon Countdown Screen',
        'code' => 'popup_coming_soon_aurora',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Site Notices'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-coming-soon-aurora.webp',
      ],
      [
        'name' => 'Coming Soon Countdown Screen Form',
        'code' => 'form_coming_soon_aurora',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Site Notices'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-coming-soon-aurora.webp',
      ],
      [
        'name' => 'Product Video Demo',
        'code' => 'popup_video_demo_glow',
        'module' => ['popup'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Business & SaaS', 'Video & Embeds'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-video-demo-glow.webp',
      ],
      [
        'name' => 'Bundle Deal 1+1',
        'code' => 'popup_shop_bundle_duo',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-shop-bundle-duo.webp',
      ],
      [
        'name' => 'SMS Text Club Signup',
        'code' => 'popup_shop_sms_signup',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-shop-sms-signup.webp',
      ],
      [
        'name' => 'SMS Text Club Signup Form',
        'code' => 'form_shop_sms_signup',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-shop-sms-signup.webp',
      ],
      [
        'name' => 'Refer a Friend Give and Get',
        'code' => 'popup_shop_refer_friend',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-shop-refer-friend.webp',
      ],
      [
        'name' => 'Low Stock Reserved Timer',
        'code' => 'popup_shop_low_stock',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-shop-low-stock.webp',
      ],
      [
        'name' => 'Podcast Subscribe On Air',
        'code' => 'popup_lead_podcast',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lead-podcast.webp',
      ],
      [
        'name' => 'Podcast Subscribe On Air Form',
        'code' => 'form_lead_podcast',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lead-podcast.webp',
      ],
      [
        'name' => 'Follow Us on Social',
        'code' => 'popup_lead_social_follow',
        'module' => ['popup'],
        'tags' => ['FREE', 'Subscribe & Leads'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-lead-social-follow.webp',
      ],
      [
        'name' => 'NPS Score Survey',
        'code' => 'popup_fb_nps_score',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-fb-nps-score.webp',
      ],
      [
        'name' => 'NPS Score Survey Form',
        'code' => 'form_fb_nps_score',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-fb-nps-score.webp',
      ],
      [
        'name' => '4th of July Fireworks Sale',
        'code' => 'popup_season_july_fourth',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-july-fourth.webp',
      ],
      [
        'name' => '4th of July Fireworks Sale Form',
        'code' => 'form_season_july_fourth',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-july-fourth.webp',
      ],
      [
        'name' => '11.11 Neon Shopping Day',
        'code' => 'popup_season_singles_day',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-singles-day.webp',
      ],
      [
        'name' => '11.11 Neon Shopping Day Form',
        'code' => 'form_season_singles_day',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-singles-day.webp',
      ],
      [
        'name' => 'Diwali Festival of Lights',
        'code' => 'popup_season_diwali',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-diwali.webp',
      ],
      [
        'name' => 'Diwali Festival of Lights Form',
        'code' => 'form_season_diwali',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-diwali.webp',
      ],
      [
        'name' => 'Ramadan and Eid Crescent',
        'code' => 'popup_season_eid',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-eid.webp',
      ],
      [
        'name' => 'Ramadan and Eid Crescent Form',
        'code' => 'form_season_eid',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-eid.webp',
      ],
      [
        'name' => 'Winter Collection Drop',
        'code' => 'popup_season_winter_drop',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-winter-drop.webp',
      ],
      [
        'name' => 'Winter Collection Drop Form',
        'code' => 'form_season_winter_drop',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-winter-drop.webp',
      ],
      [
        'name' => 'Tour Finder Quiz',
        'code' => 'popup_tour_finder_quiz',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-tour-finder-quiz.webp',
      ],
      [
        'name' => 'Tour Finder Quiz Form',
        'code' => 'form_tour_finder_quiz',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-tour-finder-quiz.webp',
      ],
      [
        'name' => 'Hotel Stay 3 Pay 2',
        'code' => 'popup_hotel_stay_longer',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-hotel-stay-longer.webp',
      ],
      [
        'name' => 'Cruise Final Call Sunset',
        'code' => 'popup_cruise_last_cabins',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-cruise-last-cabins.webp',
      ],
      [
        'name' => 'Basic Light',
        'code' => 'email_basic_light',
        'module' => ['email_template'],
        'tags' => ['FREE', 'First Steps'],
        'pro' => false,
        'features' => [],
        'preview' => 'email-basic-light.webp',
      ],
      [
        'name' => 'Basic Dark',
        'code' => 'email_basic_dark',
        'module' => ['email_template'],
        'tags' => ['FREE', 'First Steps'],
        'pro' => false,
        'features' => [],
        'preview' => 'email-basic-dark.webp',
      ],
      [
        'name' => 'Editorial Journal',
        'code' => 'email_editorial_journal',
        'module' => ['email_template'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'email-editorial-journal.webp',
      ],
      [
        'name' => 'Bold Promo',
        'code' => 'email_bold_promo',
        'module' => ['email_template'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'email-bold-promo.webp',
      ],
      [
        'name' => 'SaaS Product Update',
        'code' => 'email_saas_update',
        'module' => ['email_template'],
        'tags' => ['PRO', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'email-saas-update.webp',
      ],
      [
        'name' => 'Cruise Itinerary Map',
        'code' => 'popup_cruise_itinerary',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-cruise-itinerary.webp',
      ],
      [
        'name' => 'Cruise Itinerary Map Form',
        'code' => 'form_cruise_itinerary',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-cruise-itinerary.webp',
      ],
      [
        'name' => 'Hotel Book Direct',
        'code' => 'popup_hotel_book_direct',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-hotel-book-direct.webp',
      ],
      [
        'name' => 'Hotel Book Direct Form',
        'code' => 'form_hotel_book_direct',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-hotel-book-direct.webp',
      ],
      [
        'name' => 'Food Delivery First Order',
        'code' => 'popup_rest_delivery',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Restaurants'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-rest-delivery.webp',
      ],
      [
        'name' => 'Early Booking Santorini',
        'code' => 'popup_tour_early_booking',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-tour-early-booking.webp',
      ],
      [
        'name' => 'Hotel Best Rate Exit',
        'code' => 'popup_hotel_exit_rate',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-hotel-exit-rate.webp',
      ],
      [
        'name' => 'Hot Tour Deals',
        'code' => 'popup_tour_hot_deals',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-tour-hot-deals.webp',
      ],
      [
        'name' => 'Hot Tour Deals Form',
        'code' => 'form_tour_hot_deals',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-tour-hot-deals.webp',
      ],
      [
        'name' => 'Food Delivery Craving Picker',
        'code' => 'popup_rest_craving_picker',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Restaurants'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-rest-craving-picker.webp',
      ],
      [
        'name' => 'Food Delivery Exit Timer',
        'code' => 'popup_rest_free_delivery_timer',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Restaurants'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-rest-free-delivery-timer.webp',
      ],
      [
        'name' => 'Feedback Letterpress',
        'code' => 'popup_feedback_letterpress_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-feedback-letterpress.webp',
      ],
      [
        'name' => 'Feedback Letterpress Form',
        'code' => 'form_feedback_letterpress',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-feedback-letterpress.webp',
      ],
      [
        'name' => 'Exit Survey Before You Go',
        'code' => 'popup_step_exit_survey',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-step-exit-survey.webp',
      ],
      [
        'name' => 'Advent Calendar',
        'code' => 'popup_game_advent_calendar',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-game-advent-calendar.webp',
      ],
      [
        'name' => 'Advent Calendar Form',
        'code' => 'form_game_advent_calendar',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-game-advent-calendar.webp',
      ],
      [
        'name' => 'Webinar Live Masterclass',
        'code' => 'popup_webinar_live_masterclass_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Education', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-webinar-live-masterclass.webp',
      ],
      [
        'name' => 'Webinar Live Masterclass Form',
        'code' => 'form_webinar_live_masterclass',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Education', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-webinar-live-masterclass.webp',
      ],
      [
        'name' => 'Valentine Love Letter',
        'code' => 'popup_valentine_love_letter_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-valentine-love-letter.webp',
      ],
      [
        'name' => 'Valentine Love Letter Form',
        'code' => 'form_valentine_love_letter',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-valentine-love-letter.webp',
      ],
      [
        'name' => 'Travel Club Polaroids',
        'code' => 'popup_tour_travel_club',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-tour-travel-club.webp',
      ],
      [
        'name' => 'Travel Club Polaroids Form',
        'code' => 'form_tour_travel_club',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-tour-travel-club.webp',
      ],
      [
        'name' => 'Real Estate Golden Hour',
        'code' => 'popup_real_estate_golden_hour_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Real Estate'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-real-estate-golden-hour.webp',
      ],
      [
        'name' => 'Real Estate Golden Hour Form',
        'code' => 'form_real_estate_golden_hour',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Real Estate'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-real-estate-golden-hour.webp',
      ],
      [
        'name' => 'Online Course Atelier',
        'code' => 'popup_online_course_atelier_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Education'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-online-course-atelier.webp',
      ],
      [
        'name' => 'Online Course Atelier Form',
        'code' => 'form_online_course_atelier',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Education'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-online-course-atelier.webp',
      ],
      [
        'name' => 'Content Upgrade Checklist',
        'code' => 'popup_lead_content_upgrade',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lead-content-upgrade.webp',
      ],
      [
        'name' => 'Content Upgrade Checklist Form',
        'code' => 'form_lead_content_upgrade',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lead-content-upgrade.webp',
      ],
      [
        'name' => 'Fitness Pulse Free Class',
        'code' => 'popup_fitness_pulse_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Beauty & Wellness'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-fitness-pulse.webp',
      ],
      [
        'name' => 'Fitness Pulse Free Class Form',
        'code' => 'form_fitness_pulse',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Beauty & Wellness'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-fitness-pulse.webp',
      ],
      [
        'name' => 'Cookie Notice Card',
        'code' => 'popup_cookie_notice',
        'module' => ['popup'],
        'tags' => ['FREE', 'Site Notices'],
        'pro' => false,
        'features' => [],
        'preview' => 'popup-cookie-notice.webp',
      ],
      [
        'name' => 'How Did You Hear About Us',
        'code' => 'popup_fb_heard_about_us',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-fb-heard-about-us.webp',
      ],
      [
        'name' => 'How Did You Hear About Us Form',
        'code' => 'form_fb_heard_about_us',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-fb-heard-about-us.webp',
      ],
      [
        'name' => 'Idle Visitor Mini Survey',
        'code' => 'popup_fb_idle_survey',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-fb-idle-survey.webp',
      ],
      [
        'name' => 'Idle Visitor Mini Survey Form',
        'code' => 'form_fb_idle_survey',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-fb-idle-survey.webp',
      ],
      [
        'name' => 'Free Consultation Request',
        'code' => 'popup_lead_consultation',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lead-consultation.webp',
      ],
      [
        'name' => 'Free Consultation Request Form',
        'code' => 'form_lead_consultation',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lead-consultation.webp',
      ],
      [
        'name' => 'Job Alerts Radar',
        'code' => 'popup_lead_job_alerts',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lead-job-alerts.webp',
      ],
      [
        'name' => 'Job Alerts Radar Form',
        'code' => 'form_lead_job_alerts',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lead-job-alerts.webp',
      ],
      [
        'name' => 'Luxury Grand Hotel Key',
        'code' => 'popup_lux_grand_hotel_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Luxury', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-grand-hotel.webp',
      ],
      [
        'name' => 'Luxury Grand Hotel Key Form',
        'code' => 'form_lux_grand_hotel',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Luxury', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-grand-hotel.webp',
      ],
      [
        'name' => 'Luxury Parfum Maison',
        'code' => 'popup_lux_parfum_maison_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Luxury', 'Beauty & Wellness'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-parfum-maison.webp',
      ],
      [
        'name' => 'Luxury Parfum Maison Form',
        'code' => 'form_lux_parfum_maison',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Luxury', 'Beauty & Wellness'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-parfum-maison.webp',
      ],
      [
        'name' => 'Luxury Spa Retreat',
        'code' => 'popup_lux_spa_retreat_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Luxury', 'Beauty & Wellness'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-spa-retreat.webp',
      ],
      [
        'name' => 'Luxury Spa Retreat Form',
        'code' => 'form_lux_spa_retreat',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Luxury', 'Beauty & Wellness'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-spa-retreat.webp',
      ],
      [
        'name' => 'Boxing Day Tumbling Boxes',
        'code' => 'popup_season_boxing_day',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-boxing-day.webp',
      ],
      [
        'name' => 'Boxing Day Tumbling Boxes Form',
        'code' => 'form_season_boxing_day',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-boxing-day.webp',
      ],
      [
        'name' => 'Earth Day Globe',
        'code' => 'popup_season_earth_day',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-earth-day.webp',
      ],
      [
        'name' => 'Earth Day Globe Form',
        'code' => 'form_season_earth_day',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-earth-day.webp',
      ],
      [
        'name' => 'Lunar New Year Lanterns',
        'code' => 'popup_season_lunar_new_year',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-lunar-new-year.webp',
      ],
      [
        'name' => 'Lunar New Year Lanterns Form',
        'code' => 'form_season_lunar_new_year',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-lunar-new-year.webp',
      ],
      [
        'name' => 'Small Business Saturday Shopfront',
        'code' => 'popup_season_small_business',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-small-business.webp',
      ],
      [
        'name' => 'Small Business Saturday Shopfront Form',
        'code' => 'form_season_small_business',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-small-business.webp',
      ],
      [
        'name' => 'Womens Day Tulips',
        'code' => 'popup_season_womens_day',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-season-womens-day.webp',
      ],
      [
        'name' => 'Womens Day Tulips Form',
        'code' => 'form_season_womens_day',
        'module' => ['form'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-season-womens-day.webp',
      ],
      [
        'name' => 'Birthday Club Treat',
        'code' => 'popup_shop_birthday_club',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-shop-birthday-club.webp',
      ],
      [
        'name' => 'Birthday Club Treat Form',
        'code' => 'form_shop_birthday_club',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-shop-birthday-club.webp',
      ],
      [
        'name' => 'Free Gift With Order',
        'code' => 'popup_shop_gift_with_order',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-shop-gift-with-order.webp',
      ],
      [
        'name' => 'Holiday Delivery Deadline',
        'code' => 'popup_shop_holiday_delivery',
        'module' => ['popup'],
        'tags' => ['PRO', 'Holidays & Seasons', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-shop-holiday-delivery.webp',
      ],
      [
        'name' => 'Review Request Stars',
        'code' => 'popup_shop_review_stars',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-shop-review-stars.webp',
      ],
      [
        'name' => 'Review Request Stars Form',
        'code' => 'form_shop_review_stars',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-shop-review-stars.webp',
      ],
      [
        'name' => 'Blog Subscribe Slide-in',
        'code' => 'popup_slidein_blog_subscribe',
        'module' => ['popup'],
        'tags' => ['FREE', 'With Forms', 'Subscribe & Leads'],
        'pro' => false,
        'features' => ['form'],
        'preview' => 'popup-slidein-blog-subscribe.webp',
      ],
      [
        'name' => 'Blog Subscribe Slide-in Form',
        'code' => 'form_slidein_blog_subscribe',
        'module' => ['form'],
        'tags' => ['FREE', 'Subscribe & Leads'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-slidein-blog-subscribe.webp',
      ],
      [
        'name' => 'Chat or Call Slide-in',
        'code' => 'popup_slidein_chat_call',
        'module' => ['popup'],
        'tags' => ['PRO', 'Contact & Booking'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-slidein-chat-call.webp',
      ],
      [
        'name' => 'Appointment Booking Wizard',
        'code' => 'popup_step_booking_visit',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-step-booking-visit.webp',
      ],
      [
        'name' => 'Home Valuation Wizard',
        'code' => 'popup_step_home_valuation',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Real Estate'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-step-home-valuation.webp',
      ],
      [
        'name' => 'Two-Step Yes or No Golden Ticket',
        'code' => 'popup_step_yes_no_ticket',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-step-yes-no-ticket.webp',
      ],
      [
        'name' => 'Two-Step Yes or No Golden Ticket Form',
        'code' => 'form_step_yes_no_ticket',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-step-yes-no-ticket.webp',
      ],
      [
        'name' => 'Luxury Gentlemans Barber',
        'code' => 'popup_lux_barber_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Luxury', 'Beauty & Wellness'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lux-barber.webp',
      ],
      [
        'name' => 'Luxury Gentlemans Barber Form',
        'code' => 'form_lux_barber',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Luxury', 'Beauty & Wellness'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lux-barber.webp',
      ],
      [
        'name' => 'Thank You Next Order Coupon',
        'code' => 'popup_shop_thank_you_next',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-shop-thank-you-next.webp',
      ],
      [
        'name' => 'Product Finder Quiz Coffee Roast',
        'code' => 'popup_step_quiz_roast',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-step-quiz-roast.webp',
      ],
      [
        'name' => 'Pick a Mystery Box',
        'code' => 'popup_step_mystery_box',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-step-mystery-box.webp',
      ],
      [
        'name' => 'SaaS Free Trial Glass',
        'code' => 'popup_saas_trial_glass_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-saas-trial-glass.webp',
      ],
      [
        'name' => 'SaaS Free Trial Glass Form',
        'code' => 'form_saas_trial_glass',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-saas-trial-glass.webp',
      ],
      [
        'name' => 'Report a Bug or Idea',
        'code' => 'popup_fb_bug_idea',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-fb-bug-idea.webp',
      ],
      [
        'name' => 'Report a Bug or Idea Form',
        'code' => 'form_fb_bug_idea',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-fb-bug-idea.webp',
      ],
      [
        'name' => 'Gated Article Unlock',
        'code' => 'popup_lead_gated_article',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lead-gated-article.webp',
      ],
      [
        'name' => 'Gated Article Unlock Form',
        'code' => 'form_lead_gated_article',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lead-gated-article.webp',
      ],
      [
        'name' => 'Back in Stock Alert',
        'code' => 'popup_shop_back_in_stock',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-shop-back-in-stock.webp',
      ],
      [
        'name' => 'Back in Stock Alert Form',
        'code' => 'form_shop_back_in_stock',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-shop-back-in-stock.webp',
      ],
      [
        'name' => 'VIP Club Membership Card',
        'code' => 'popup_shop_vip_club',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-shop-vip-club.webp',
      ],
      [
        'name' => 'VIP Club Membership Card Form',
        'code' => 'form_shop_vip_club',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-shop-vip-club.webp',
      ],
      [
        'name' => 'Event Registration Ticket',
        'code' => 'popup_lead_event_ticket',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Contact & Booking'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lead-event-ticket.webp',
      ],
      [
        'name' => 'Event Registration Ticket Form',
        'code' => 'form_lead_event_ticket',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Contact & Booking'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lead-event-ticket.webp',
      ],
      [
        'name' => 'Beauty Salon Velvet Booking',
        'code' => 'popup_beauty_salon_velvet_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Beauty & Wellness'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-beauty-salon-velvet.webp',
      ],
      [
        'name' => 'Beauty Salon Velvet Booking Form',
        'code' => 'form_beauty_salon_velvet',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Beauty & Wellness'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-beauty-salon-velvet.webp',
      ],
      [
        'name' => 'Ebook Playbook Lead Magnet',
        'code' => 'popup_ebook_playbook_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-ebook-playbook.webp',
      ],
      [
        'name' => 'Ebook Playbook Lead Magnet Form',
        'code' => 'form_ebook_playbook',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-ebook-playbook.webp',
      ],
      [
        'name' => 'Cruise Cabin Picker',
        'code' => 'popup_cruise_cabin_picker',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-cruise-cabin-picker.webp',
      ],
      [
        'name' => 'Cruise Cabin Picker Form',
        'code' => 'form_cruise_cabin_picker',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-cruise-cabin-picker.webp',
      ],
      [
        'name' => 'Read Next Article Slide-in',
        'code' => 'popup_slidein_read_next',
        'module' => ['popup'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-slidein-read-next.webp',
      ],
      [
        'name' => 'Donation Ask Seedling',
        'code' => 'popup_lead_donation',
        'module' => ['popup'],
        'tags' => ['PRO', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-lead-donation.webp',
      ],
      [
        'name' => 'Price Drop Alert',
        'code' => 'popup_shop_price_drop',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-shop-price-drop.webp',
      ],
      [
        'name' => 'Price Drop Alert Form',
        'code' => 'form_shop_price_drop',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-shop-price-drop.webp',
      ],
      [
        'name' => 'Get the App Offer',
        'code' => 'popup_shop_app_download',
        'module' => ['popup'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'popup-shop-app-download.webp',
      ],
      [
        'name' => 'Restaurant Table Booking',
        'code' => 'popup_rest_table_booking',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Restaurants'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-rest-table-booking.webp',
      ],
      [
        'name' => 'Restaurant Table Booking Form',
        'code' => 'form_rest_table_booking',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Restaurants'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-rest-table-booking.webp',
      ],
      [
        'name' => 'Guided Tour Booking',
        'code' => 'popup_tour_guided_walk',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Contact & Booking', 'Travel & Hotels'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-tour-guided-walk.webp',
      ],
      [
        'name' => 'Guided Tour Booking Form',
        'code' => 'form_tour_guided_walk',
        'module' => ['form'],
        'tags' => ['PRO', 'Contact & Booking', 'Travel & Hotels'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-tour-guided-walk.webp',
      ],
      [
        'name' => 'Welcome Discount 10%',
        'code' => 'popup_shop_welcome_discount',
        'module' => ['popup'],
        'tags' => ['FREE', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => false,
        'features' => ['form'],
        'preview' => 'popup-shop-welcome-discount.webp',
      ],
      [
        'name' => 'Welcome Discount 10% Form',
        'code' => 'form_shop_welcome_discount',
        'module' => ['form'],
        'tags' => ['FREE', 'Sales & Promotions', 'Subscribe & Leads', 'Online Store'],
        'pro' => false,
        'features' => [],
        'preview' => 'form-shop-welcome-discount.webp',
      ],
      [
        'name' => 'Scratch and Win Card',
        'code' => 'popup_game_scratch_card',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-game-scratch-card.webp',
      ],
      [
        'name' => 'Scratch and Win Card Form',
        'code' => 'form_game_scratch_card',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-game-scratch-card.webp',
      ],
      [
        'name' => 'Book a Demo B2B',
        'code' => 'popup_lead_book_demo',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Subscribe & Leads', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-lead-book-demo.webp',
      ],
      [
        'name' => 'Book a Demo B2B Form',
        'code' => 'form_lead_book_demo',
        'module' => ['form'],
        'tags' => ['PRO', 'Subscribe & Leads', 'Contact & Booking', 'Business & SaaS'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-lead-book-demo.webp',
      ],
      [
        'name' => 'Pick a Card',
        'code' => 'popup_game_pick_card',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-game-pick-card.webp',
      ],
      [
        'name' => 'Pick a Card Form',
        'code' => 'form_game_pick_card',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Subscribe & Leads'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-game-pick-card.webp',
      ],
      [
        'name' => 'Daily Deal Box',
        'code' => 'popup_game_daily_deal',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-game-daily-deal.webp',
      ],
      [
        'name' => 'Daily Deal Box Form',
        'code' => 'form_game_daily_deal',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions', 'Online Store'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-game-daily-deal.webp',
      ],
      [
        'name' => 'Spin and Win Fortune Wheel',
        'code' => 'popup_spin_win_fortune_form',
        'module' => ['popup'],
        'tags' => ['PRO', 'With Forms', 'Sales & Promotions'],
        'pro' => true,
        'features' => ['form'],
        'preview' => 'popup-spin-win-fortune.webp',
      ],
      [
        'name' => 'Spin and Win Fortune Wheel Form',
        'code' => 'form_spin_win_fortune',
        'module' => ['form'],
        'tags' => ['PRO', 'Sales & Promotions'],
        'pro' => true,
        'features' => [],
        'preview' => 'form-spin-win-fortune.webp',
      ],
    ];
  }

  public function templateTypeList()
  {
    return [
      'form' => __('Form', 'ppsgen'),
      'popup' => __('Popup', 'ppsgen'),
      'email_template' => __('Email Template', 'ppsgen'),
    ];
  }

  protected function normalizeSiteUrl(string $url): string
  {
    $host = parse_url($url, PHP_URL_HOST);
    return preg_replace('/^www\./i', '', strtolower($host));
  }

  /**
   * A template's form keeps the email design id from the site it was made on, which means nothing here. A form
   * that wraps its emails in a design gets this site's default email template (id 1) when it exists; 0 (emails
   * written as complete HTML, e.g. a booking alert) stays 0.
   */
  protected function localFormEmailTemplate(string $settings): string
  {
    $data = json_decode($settings, true);
    if (!is_array($data) || empty($data['template_id'])) {
      return $settings;
    }
    global $wpdb;
    $default = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ppsgen_eca_templates WHERE id = 1");
    $data['template_id'] = $default ? '1' : '0';
    return (string) wp_json_encode($data);
  }

  /**
   * Makes sure the contact fields a template needs exist here: an existing field with the same slug is used
   * as it is (never changed), a missing one is created from the template's definition. Returns slug => id.
   */
  protected function ensureTemplateContactFields(array $fields, string $now): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'ppsgen_contact_fields';
    $ids = [];
    foreach ($fields as $field) {
      $slug = (string) ($field['slug'] ?? '');
      if ($slug === '') {
        continue;
      }
      $id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $slug));
      if (!$id) {
        $wpdb->insert($table, [
          'slug' => $slug,
          'label' => (string) ($field['label'] ?? $slug),
          'field_type' => (string) ($field['field_type'] ?? 'text'),
          'is_enabled' => 1,
          'is_default' => 0,
          'only_form' => !empty($field['only_form']) ? 1 : 0,
          'options' => (string) ($field['options'] ?? ''),
          'settings' => (string) ($field['settings'] ?? '{}'),
          'order_index' => 0,
          'created_at' => $now,
          'updated_at' => $now,
        ]);
        $id = (int) $wpdb->insert_id;
      }
      if ($id) {
        $ids[$slug] = $id;
      }
    }
    return $ids;
  }

  public function installTemplate($remoteData, $template_name = 'Installed Template ')
  {
    global $wpdb;
    $template = $remoteData ?? [];
    if (empty($template)) {
      return false;
    }
    $form_id_new = null;
    $popup_id_new = null;
    $email_tpl_id_new = null;
    $now = current_time('mysql');
    // Contact fields the template's form uses (e.g. form_guests): created when this site doesn't have them yet
    $contactFieldIds = $this->ensureTemplateContactFields($template['contact_fields'] ?? [], $now);

    if (!empty($template['form'])) {
      $form = $template['form'];
      $form_data = [
        'name' => !empty($template_name) ? $template_name : 'Installed at ' . $now,
        'form_type' => $form['form_type'] ?? 'submit',
        'settings' => $this->localFormEmailTemplate((string) ($form['settings'] ?? '{}')),
        'design' => $form['design'] ?? '{}',
        'active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
      ];
      $wpdb->insert("{$wpdb->prefix}ppsgen_forms", $form_data);
      $form_id_new = $wpdb->insert_id;
      if (!$form_id_new) {
        // $this->setStatus('error');
        // $this->setMessage('Failed to create form: ' . $wpdb->last_error);
        return false;
      }

      if (!empty($template['form_fields']) && is_array($template['form_fields'])) {
        foreach ($template['form_fields'] as $field) {
          $settings = json_decode($field['settings'] ?? '{}', true);
          $slug = $settings['field_slug'] ?? '';
          $local_cf_id = 0;
          if (!empty($slug)) {
            $local_cf_id = $contactFieldIds[$slug] ?? (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ppsgen_contact_fields WHERE slug = %s AND is_enabled = 1 LIMIT 1", $slug));
          }
          $field_data = [
            'form_id' => $form_id_new,
            'cf_id' => $local_cf_id,
            'fkey' => $field['fkey'] ?? '',
            'settings' => $field['settings'] ?? '{}',
            'options' => $field['options'] ?? '[]',
            'order_index' => $field['order_index'] ?? 0,
            'created_date' => $now,
            'updated_date' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_form_fields", $field_data);
          if ($wpdb->last_error) {
          }
        }
      }
    }

    if (!empty($template['popup'])) {
      $popup = $template['popup'];
      $popup_data = [
        'name' => !empty($template_name) ? $template_name : 'Installed at ' . $now,
        'settings' => $popup['settings'] ?? '{}',
        'design' => $popup['design'] ?? '{}',
        'active' => 0,
        'created_at' => $now,
        'updated_at' => $now,
      ];
      $wpdb->insert("{$wpdb->prefix}ppsgen_popups", $popup_data);
      $popup_id_new = $wpdb->insert_id;
      if (!$popup_id_new) {
        // $this->setStatus('error');
        // $this->setMessage('Failed to create popup: ' . $wpdb->last_error);
        return false;
      }
      if (!empty($template['popup_fields']) && is_array($template['popup_fields'])) {
        foreach ($template['popup_fields'] as $pfield) {
          $type = $pfield['type'] ?? '';
          $settings_raw = $pfield['settings'] ?? '{}';
          $settings = json_decode($settings_raw, true);
          if ($type === 'form' && !empty($settings['form_id']) && $form_id_new) {
            $settings['form_id'] = (string) $form_id_new;
            $settings_raw = wp_json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
          }
          $field_data = [
            'popup_id' => $popup_id_new,
            'parent_key' => $pfield['parent_key'] ?? '',
            'fkey' => $pfield['fkey'] ?? '',
            'type' => $type,
            'settings' => $settings_raw,
            'order_index' => $pfield['order_index'] ?? 0,
            'created_date' => $now,
            'updated_date' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_popup_fields", $field_data);
        }
      }
    }

    if (!empty($template['email_template'])) {
      $etpl = $template['email_template'];
      $etpl_data = [
        'name' => !empty($template_name) ? $template_name : 'Installed at ' . $now,
        'settings' => $etpl['settings'] ?? '[]',
        'design' => $etpl['design'] ?? '{}',
        'active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
      ];
      $wpdb->insert("{$wpdb->prefix}ppsgen_eca_templates", $etpl_data);
      $email_tpl_id_new = $wpdb->insert_id;
      if (!$email_tpl_id_new) {
        // $this->setStatus('error');
        // $this->setMessage('Failed to create email template: ' . $wpdb->last_error);
        return false;
      }
      if (!empty($template['email_template_fields']) && is_array($template['email_template_fields'])) {
        foreach ($template['email_template_fields'] as $efield) {
          $field_data = [
            'owner_id' => $email_tpl_id_new,
            'owner_type' => 'template',
            'parent_key' => $efield['parent_key'] ?? '',
            'fkey' => $efield['fkey'] ?? '',
            'type' => $efield['type'] ?? '',
            'settings' => $efield['settings'] ?? '{}',
            'order_index' => $efield['order_index'] ?? 0,
            'created_date' => $now,
            'updated_date' => $now,
          ];
          $wpdb->insert("{$wpdb->prefix}ppsgen_eca_fields", $field_data);
        }
      }
    }

    return [
      'form_id' => $form_id_new ? (int) $form_id_new : 0,
      'popup_id' => $popup_id_new ? (int) $popup_id_new : 0,
      'email_template_id' => $email_tpl_id_new ? (int) $email_tpl_id_new : 0,
    ];
  }
}
