<?php

if (!defined('ABSPATH')) {
  exit();
}

/**
 * The eight plays from the CES UX brief. Each play lists its questions (with defaults),
 * the "What will happen" journey ({key} placeholders are filled from the answers),
 * the preview it shows while answering and the builder that turns answers into CES items.
 */
function ppsgen_get_play_definitions(): array
{
  $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
  $owner = (string) get_bloginfo('admin_email');
  $fieldsQuestion = function (array $defaults, array $only = ['name', 'phone', 'message']) {
    return [
      'key' => 'fields',
      'type' => 'fields',
      'title' => __('What should we ask for?', 'ppsgen'),
      'help' => __('Email is always included. Fewer fields usually means more sign-ups.', 'ppsgen'),
      'only' => $only,
      'default' => $defaults,
    ];
  };
  $triggerQuestion = function (string $trigger = 'delay') {
    return [
      'key' => 'trigger',
      'type' => 'trigger',
      'title' => __('When should visitors see it?', 'ppsgen'),
      'help' => __('You\'ll also get a shortcode to place the form on any page.', 'ppsgen'),
      'default' => ['trigger' => $trigger, 'frequency' => 'week'],
    ];
  };

  return [
    'get_more_enquiries' => [
      'eyebrow' => __('Capture', 'ppsgen'),
      'title' => __('Get more enquiries', 'ppsgen'),
      'description' => __('A popup and form that collect enquiries, thank the person and tell you straight away.', 'ppsgen'),
      'chips' => [__('Popup', 'ppsgen'), __('Form', 'ppsgen'), __('Emails', 'ppsgen'), __('Follow-up', 'ppsgen')],
      'minutes' => 3,
      'modules' => ['popup', 'form', 'contact'],
      'builder' => 'buildEnquiries',
      'name' => __('{label} enquiries', 'ppsgen'),
      'people' => __('Enquiries', 'ppsgen'),
      'stop' => ['slug' => 'replied', 'action' => __('Mark replied', 'ppsgen'), 'done' => __('Replied', 'ppsgen')],
      'preview' => ['type' => 'popup', 'title' => __('Get your {label}', 'ppsgen'), 'text' => __('Tell us a little and we\'ll get back to you within one business day.', 'ppsgen'), 'button' => __('Send enquiry', 'ppsgen')],
      'questions' => [
        ['key' => 'label', 'type' => 'text', 'title' => __('What do people enquire about?', 'ppsgen'), 'help' => __('Used for the popup heading, the form name and the contact tag.', 'ppsgen'), 'default' => __('free quote', 'ppsgen'), 'required' => true],
        $fieldsQuestion(['ask_name' => 1, 'ask_phone' => 1, 'ask_message' => 1]),
        $triggerQuestion('delay'),
        ['key' => 'owner_email', 'type' => 'email', 'title' => __('Who should be told about new enquiries?', 'ppsgen'), 'help' => __('An email arrives the moment someone submits. Leave empty to skip it.', 'ppsgen'), 'default' => $owner],
        ['key' => 'thanks', 'type' => 'message', 'title' => __('What should the enquirer get straight away?', 'ppsgen'), 'toggle' => __('Send a thank-you email', 'ppsgen'), 'default' => ['on' => 1, 'subject' => __('Thanks for your enquiry — we\'ll be in touch', 'ppsgen'), 'body' => sprintf(__("Hi [first_name],\n\nThanks for getting in touch with %s. We'll get back to you within one business day.", 'ppsgen'), $site)]],
        ['key' => 'followup', 'type' => 'days', 'title' => __('Follow up if you haven\'t heard back?', 'ppsgen'), 'toggle' => __('Send one follow-up email after', 'ppsgen'), 'automation' => true, 'default' => ['on' => 1, 'days' => 2]],
      ],
      'journey' => [
        ['text' => __('A visitor sends an enquiry from the popup or your Contact page', 'ppsgen')],
        ['text' => __('You get an email straight away', 'ppsgen'), 'when' => 'owner_email'],
        ['text' => __('They get your thank-you email', 'ppsgen'), 'when' => 'thanks_on'],
        ['text' => __('They\'re saved in Contacts with the tag enquiry-{label}', 'ppsgen')],
        ['text' => __('{followup_days} days later, a follow-up — unless you\'ve marked them as replied', 'ppsgen'), 'when' => 'followup_on'],
      ],
    ],

    'build_email_list' => [
      'eyebrow' => __('Capture', 'ppsgen'),
      'title' => __('Build my email list', 'ppsgen'),
      'description' => __('A signup popup and a welcome email. Then send newsletters from Campaigns.', 'ppsgen'),
      'chips' => [__('Popup', 'ppsgen'), __('Form', 'ppsgen'), __('Welcome email', 'ppsgen'), __('List', 'ppsgen')],
      'minutes' => 2,
      'modules' => ['popup', 'form', 'contact'],
      'builder' => 'buildEmailList',
      'name' => __('{label} sign-ups', 'ppsgen'),
      'people' => __('Sign-ups', 'ppsgen'),
      'preview' => ['type' => 'popup', 'title' => '{headline}', 'text' => '{pitch}', 'button' => __('Subscribe', 'ppsgen')],
      'questions' => [
        ['key' => 'label', 'type' => 'text', 'title' => __('What are people signing up for?', 'ppsgen'), 'help' => __('Used for the form name, the contact tag and the list.', 'ppsgen'), 'default' => __('newsletter', 'ppsgen'), 'required' => true],
        ['key' => 'headline', 'type' => 'text', 'title' => __('Popup heading', 'ppsgen'), 'default' => __('Get our news and offers first', 'ppsgen'), 'required' => true],
        ['key' => 'pitch', 'type' => 'text', 'title' => __('One line about what they\'ll get', 'ppsgen'), 'default' => __('Tips, news and member-only offers. No spam, unsubscribe any time.', 'ppsgen')],
        $fieldsQuestion(['ask_name' => 1], ['name']),
        $triggerQuestion('scroll'),
        ['key' => 'welcome', 'type' => 'message', 'title' => __('What should new subscribers get straight away?', 'ppsgen'), 'toggle' => __('Send a welcome email', 'ppsgen'), 'default' => ['on' => 1, 'subject' => sprintf(__('Welcome to %s', 'ppsgen'), $site), 'body' => sprintf(__("Hi [first_name],\n\nThanks for joining us at %s. We'll send you news and offers now and then, and you can unsubscribe at any time.", 'ppsgen'), $site)]],
      ],
      'journey' => [
        ['text' => __('A visitor signs up from the popup or any page with the form', 'ppsgen')],
        ['text' => __('They get your welcome email', 'ppsgen'), 'when' => 'welcome_on'],
        ['text' => __('They\'re saved in Contacts with the tag {label}', 'ppsgen')],
        ['text' => __('Everyone with that tag is in the list "{label} subscribers" — send them a newsletter from Campaigns', 'ppsgen')],
      ],
    ],

    'follow_up_quote' => [
      'eyebrow' => __('Nurture', 'ppsgen'),
      'title' => __('Follow up a quote', 'ppsgen'),
      'description' => __('Acknowledge, wait, follow up — and stop once they reply.', 'ppsgen'),
      'chips' => [__('Automation', 'ppsgen'), __('2 emails', 'ppsgen')],
      'minutes' => 2,
      'modules' => ['contact', 'eca'],
      'needs_automation' => true,
      'builder' => 'buildFollowUpQuote',
      'name' => __('Quote follow-up', 'ppsgen'),
      'people' => __('People in it', 'ppsgen'),
      'stop' => ['slug' => 'replied', 'action' => __('Mark replied', 'ppsgen'), 'done' => __('Replied', 'ppsgen')],
      'preview' => ['type' => 'email', 'subject' => '{first_subject}', 'body' => '{first_body}'],
      'questions' => [
        ['key' => 'start_tag', 'type' => 'tag', 'title' => __('Which tag means "quote sent"?', 'ppsgen'), 'help' => __('Add this tag to a contact when you send them a quote (Contacts › Add Tag(s), or in their profile). The follow-up starts then.', 'ppsgen'), 'default' => 'quote-sent', 'required' => true],
        ['key' => 'first', 'type' => 'message', 'title' => __('First follow-up', 'ppsgen'), 'days' => true, 'default' => ['days' => 2, 'subject' => __('Any questions about your quote?', 'ppsgen'), 'body' => sprintf(__("Hi {{contact.first_name|there}},\n\nWe sent you a quote a couple of days ago. Do you have any questions? Just reply to this email and we'll help.\n\nThe %s team", 'ppsgen'), $site)]],
        ['key' => 'second', 'type' => 'message', 'title' => __('Second follow-up', 'ppsgen'), 'days' => true, 'default' => ['days' => 5, 'subject' => __('Shall we go ahead?', 'ppsgen'), 'body' => sprintf(__("Hi {{contact.first_name|there}},\n\nJust checking in on the quote we sent. If the timing isn't right, no problem — reply and let us know.\n\nThe %s team", 'ppsgen'), $site)]],
      ],
      'journey' => [
        ['text' => __('You add the tag {start_tag} to a contact when you send a quote', 'ppsgen')],
        ['text' => __('{first_days} days later, the first follow-up', 'ppsgen')],
        ['text' => __('{second_days} days after that, a second follow-up', 'ppsgen')],
        ['text' => __('It stops for anyone you mark as replied', 'ppsgen')],
      ],
    ],

    'welcome_new_lead' => [
      'eyebrow' => __('Nurture', 'ppsgen'),
      'title' => __('Welcome a new lead', 'ppsgen'),
      'description' => __('A short welcome series for every new contact.', 'ppsgen'),
      'chips' => [__('Automation', 'ppsgen'), __('3 emails', 'ppsgen')],
      'minutes' => 3,
      'modules' => ['contact', 'eca'],
      'needs_automation' => true,
      'builder' => 'buildWelcomeSeries',
      'name' => __('Welcome series', 'ppsgen'),
      'people' => __('People in it', 'ppsgen'),
      'preview' => ['type' => 'email', 'subject' => '{email1_subject}', 'body' => '{email1_body}'],
      'questions' => [
        ['key' => 'start_tag', 'type' => 'tag', 'title' => __('Who should get the welcome series?', 'ppsgen'), 'help' => __('Contacts with this tag. Your forms and plays add tags, for example "newsletter" or "enquiry-free-quote".', 'ppsgen'), 'default' => 'newsletter', 'required' => true],
        ['key' => 'email1', 'type' => 'message', 'title' => __('Email 1', 'ppsgen'), 'days' => true, 'default' => ['days' => 0, 'subject' => sprintf(__('Welcome to %s', 'ppsgen'), $site), 'body' => sprintf(__("Hi {{contact.first_name|there}},\n\nWelcome! Here's what you can expect from %s: useful tips, news and the occasional offer.", 'ppsgen'), $site)]],
        ['key' => 'email2', 'type' => 'message', 'title' => __('Email 2', 'ppsgen'), 'days' => true, 'default' => ['days' => 2, 'subject' => __('A little more about us', 'ppsgen'), 'body' => __("Hi {{contact.first_name|there}},\n\nWe wanted to tell you a bit more about who we are and how we help. Reply any time if you have a question.", 'ppsgen')]],
        ['key' => 'email3', 'type' => 'message', 'title' => __('Email 3', 'ppsgen'), 'days' => true, 'default' => ['days' => 5, 'subject' => __('How can we help you?', 'ppsgen'), 'body' => __("Hi {{contact.first_name|there}},\n\nIs there anything we can help you with right now? Just reply to this email.", 'ppsgen')]],
      ],
      'journey' => [
        ['text' => __('A contact gets the tag {start_tag}', 'ppsgen')],
        ['text' => __('{email1_days} days later: "{email1_subject}"', 'ppsgen')],
        ['text' => __('{email2_days} days after that: "{email2_subject}"', 'ppsgen')],
        ['text' => __('{email3_days} days after that: "{email3_subject}"', 'ppsgen')],
      ],
    ],

    'promote_offer' => [
      'eyebrow' => __('Target', 'ppsgen'),
      'title' => __('Promote an offer', 'ppsgen'),
      'description' => __('Show an offer to your visitors and send the details to everyone who asks for it.', 'ppsgen'),
      'chips' => [__('Popup', 'ppsgen'), __('Tag', 'ppsgen'), __('Email', 'ppsgen')],
      'minutes' => 3,
      'modules' => ['popup', 'form', 'contact'],
      'builder' => 'buildOffer',
      'name' => __('{label} offer', 'ppsgen'),
      'people' => __('Claimed', 'ppsgen'),
      'preview' => ['type' => 'popup', 'title' => '{headline}', 'text' => '{details}', 'button' => '{button}'],
      'questions' => [
        ['key' => 'label', 'type' => 'text', 'title' => __('Short name for the offer', 'ppsgen'), 'help' => __('Used for the form name and the contact tag.', 'ppsgen'), 'default' => __('summer sale', 'ppsgen'), 'required' => true],
        ['key' => 'headline', 'type' => 'text', 'title' => __('Offer headline', 'ppsgen'), 'default' => __('15% off this month', 'ppsgen'), 'required' => true],
        ['key' => 'details', 'type' => 'text', 'title' => __('Offer details', 'ppsgen'), 'default' => __('Leave your email and we\'ll send you the code straight away.', 'ppsgen')],
        ['key' => 'button', 'type' => 'text', 'title' => __('Button text', 'ppsgen'), 'default' => __('Send me the code', 'ppsgen')],
        ['key' => 'code', 'type' => 'text', 'title' => __('Discount code (optional)', 'ppsgen'), 'default' => 'SAVE15'],
        ['key' => 'link', 'type' => 'url', 'title' => __('Where should they use it? (optional link)', 'ppsgen'), 'default' => home_url('/')],
        $triggerQuestion('delay'),
        ['key' => 'offer', 'type' => 'message', 'title' => __('The email with the offer', 'ppsgen'), 'default' => ['on' => 1, 'subject' => __('Here\'s your offer', 'ppsgen'), 'body' => __("Hi [first_name],\n\nThanks for your interest! Your code is {code}. Use it here: {link}", 'ppsgen')]],
      ],
      'journey' => [
        ['text' => __('Visitors see "{headline}"', 'ppsgen')],
        ['text' => __('They leave their email to get the offer', 'ppsgen')],
        ['text' => __('They get your offer email with the code {code}', 'ppsgen')],
        ['text' => __('They\'re saved in Contacts with the tag offer-{label}, so you can follow up later', 'ppsgen')],
      ],
    ],

    'request_review' => [
      'eyebrow' => __('Automate', 'ppsgen'),
      'title' => __('Request a review', 'ppsgen'),
      'description' => __('After a job or purchase, ask for a review, with one reminder.', 'ppsgen'),
      'chips' => [__('Automation', 'ppsgen'), __('2 emails', 'ppsgen')],
      'minutes' => 2,
      'modules' => ['contact', 'eca'],
      'needs_automation' => true,
      'builder' => 'buildReviewRequest',
      'name' => __('Review requests', 'ppsgen'),
      'people' => __('People in it', 'ppsgen'),
      'stop' => ['slug' => 'reviewed', 'action' => __('Mark reviewed', 'ppsgen'), 'done' => __('Reviewed', 'ppsgen')],
      'preview' => ['type' => 'email', 'subject' => '{ask_subject}', 'body' => '{ask_body}'],
      'questions' => [
        ['key' => 'start_tag', 'type' => 'tag', 'title' => __('Which tag means "job done"?', 'ppsgen'), 'help' => __('Add this tag to a contact after a job or purchase. WooCommerce customers can be tagged automatically from Contacts › Import.', 'ppsgen'), 'default' => 'job-done', 'required' => true],
        ['key' => 'review_link', 'type' => 'url', 'title' => __('Where should they leave the review?', 'ppsgen'), 'help' => __('For example your Google Business review link.', 'ppsgen'), 'default' => '', 'required' => true],
        ['key' => 'ask', 'type' => 'message', 'title' => __('The review request', 'ppsgen'), 'days' => true, 'default' => ['days' => 1, 'subject' => __('How did we do?', 'ppsgen'), 'body' => sprintf(__("Hi {{contact.first_name|there}},\n\nThank you for choosing %s. Would you take a minute to tell others how we did?", 'ppsgen'), $site)]],
        ['key' => 'reminder', 'type' => 'message', 'title' => __('One reminder', 'ppsgen'), 'days' => true, 'default' => ['days' => 4, 'subject' => __('A quick favour?', 'ppsgen'), 'body' => __("Hi {{contact.first_name|there}},\n\nIf you have a moment, a short review would really help us. Thank you!", 'ppsgen')]],
      ],
      'journey' => [
        ['text' => __('You add the tag {start_tag} after a job or purchase', 'ppsgen')],
        ['text' => __('{ask_days} days later: "{ask_subject}" with a button to your review page', 'ppsgen')],
        ['text' => __('{reminder_days} days after that, one reminder', 'ppsgen')],
        ['text' => __('It stops for anyone you mark as reviewed', 'ppsgen')],
      ],
    ],

    'recover_enquiry' => [
      'eyebrow' => __('Automate', 'ppsgen'),
      'title' => __('Recover an abandoned enquiry', 'ppsgen'),
      'description' => __('When someone is about to leave without asking, offer help. Those who leave an email get help straight away.', 'ppsgen'),
      'chips' => [__('Popup', 'ppsgen'), __('Email', 'ppsgen')],
      'minutes' => 2,
      'modules' => ['popup', 'form', 'contact'],
      'builder' => 'buildRecovery',
      'name' => __('Exit help', 'ppsgen'),
      'people' => __('Help requests', 'ppsgen'),
      'stop' => ['slug' => 'replied', 'action' => __('Mark replied', 'ppsgen'), 'done' => __('Replied', 'ppsgen')],
      'preview' => ['type' => 'popup', 'title' => '{headline}', 'text' => '{pitch}', 'button' => __('Get help', 'ppsgen')],
      'questions' => [
        ['key' => 'headline', 'type' => 'text', 'title' => __('What should the popup ask?', 'ppsgen'), 'default' => __('Before you go — can we help?', 'ppsgen'), 'required' => true],
        ['key' => 'pitch', 'type' => 'text', 'title' => __('One line under it', 'ppsgen'), 'default' => __('Leave your email and question, and we\'ll get back to you today.', 'ppsgen')],
        $fieldsQuestion(['ask_name' => 0, 'ask_message' => 1], ['name', 'message']),
        ['key' => 'owner_email', 'type' => 'email', 'title' => __('Who should be told?', 'ppsgen'), 'default' => $owner],
        ['key' => 'help', 'type' => 'message', 'title' => __('What should they get straight away?', 'ppsgen'), 'toggle' => __('Send a helpful email', 'ppsgen'), 'default' => ['on' => 1, 'subject' => __('We\'ve got your question', 'ppsgen'), 'body' => sprintf(__("Hi [first_name],\n\nThanks for reaching out to %s. We'll answer your question today. In the meantime, just reply to this email if there's anything else.", 'ppsgen'), $site)]],
      ],
      'journey' => [
        ['text' => __('A visitor moves to leave the page', 'ppsgen')],
        ['text' => __('They see "{headline}"', 'ppsgen')],
        ['text' => __('If they leave an email, you\'re told straight away', 'ppsgen'), 'when' => 'owner_email'],
        ['text' => __('They get your helpful email', 'ppsgen'), 'when' => 'help_on'],
        ['text' => __('They\'re saved in Contacts with the tag exit-help', 'ppsgen')],
      ],
    ],

    'reengage_customers' => [
      'eyebrow' => __('Segment', 'ppsgen'),
      'title' => __('Re-engage old customers', 'ppsgen'),
      'description' => __('Make a list of past customers and send them an offer.', 'ppsgen'),
      'chips' => [__('List', 'ppsgen'), __('Newsletter', 'ppsgen')],
      'minutes' => 2,
      'modules' => ['contact', 'eca'],
      'builder' => 'buildReengage',
      'name' => __('Win-back offer ({start_tag})', 'ppsgen'),
      'people' => __('Recipients', 'ppsgen'),
      'preview' => ['type' => 'email', 'subject' => '{offer_subject}', 'body' => '{offer_body}'],
      'questions' => [
        ['key' => 'start_tag', 'type' => 'tag', 'title' => __('Which tag do your past customers have?', 'ppsgen'), 'help' => __('For example "customer". WooCommerce customers get a tag when you import them from Contacts › Import.', 'ppsgen'), 'default' => 'customer', 'required' => true],
        ['key' => 'link', 'type' => 'url', 'title' => __('Where should the button go?', 'ppsgen'), 'default' => home_url('/')],
        ['key' => 'offer', 'type' => 'message', 'title' => __('The offer email', 'ppsgen'), 'default' => ['on' => 1, 'subject' => __('We miss you — here\'s something to welcome you back', 'ppsgen'), 'body' => sprintf(__("Hi {{contact.first_name|there}},\n\nIt's been a while! As one of our customers, here's a little thank-you from %s.", 'ppsgen'), $site)]],
      ],
      'journey' => [
        ['text' => __('We make a list of everyone tagged {start_tag}', 'ppsgen')],
        ['text' => __('We write a newsletter with your offer to that list', 'ppsgen')],
        ['text' => __('You check it, send a test, and send it now or later', 'ppsgen')],
        ['text' => __('Opens and clicks show on the newsletter, so you can follow up', 'ppsgen')],
      ],
    ],
  ];
}
