<?php
/**
 * Configure the 0.7.0 basic Request a Quote flow in a WordPress runtime.
 *
 * Run with:
 * wp eval-file tools/setup-quote-flow-070.php --path=/path/to/wp-root --allow-root
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$form_title = 'SKVN Request a Quote';

$form_markup = <<<'CF7'
<div class="skvn-form skvn-quote-form">
  <div class="skvn-quote-form__grid">
    <label>Full name
      [text* full_name class:skvn-form__control autocomplete:name]
    </label>

    <label>Company name
      [text* company_name class:skvn-form__control]
    </label>

    <label>Email
      [email* email class:skvn-form__control autocomplete:email]
    </label>

    <label>Country
      [text* country class:skvn-form__control autocomplete:country-name]
    </label>

    <label>Product interest
      [text* product_interest class:skvn-form__control default:get]
    </label>

    <label>Quantity / estimated volume
      [text* quantity class:skvn-form__control]
    </label>

    <label>Phone / WhatsApp
      [tel phone class:skvn-form__control autocomplete:tel]
    </label>

    <label>Destination port
      [text destination_port class:skvn-form__control]
    </label>

    <label>Packaging requirement
      [text packaging_requirement class:skvn-form__control]
    </label>

    <label>Message
      [textarea* message class:skvn-form__control]
    </label>
  </div>

  [hidden product_id default:get]
  [hidden product_sku default:get]
  [hidden product_name default:get]
  [hidden product_url default:get]
  [hidden source_url default:get]
  [hidden utm_source default:get]
  [hidden utm_medium default:get]
  [hidden utm_campaign default:get]
  [hidden utm_content default:get]
  [hidden utm_term default:get]

  <div class="skvn-quote-form__actions">
    [submit class:skvn-button class:skvn-button--primary "Submit quote request"]
    <p class="skvn-quote-form__note">We will review your request and reply by email.</p>
  </div>
</div>
CF7;

$mail_body = <<<'MAIL'
New quote request from [_site_title]

Full name: [full_name]
Company: [company_name]
Email: [email]
Country: [country]
Phone / WhatsApp: [phone]

Product interest: [product_interest]
Quantity / estimated volume: [quantity]
Destination port: [destination_port]
Packaging requirement: [packaging_requirement]

Message:
[message]

Context:
Product ID: [product_id]
Product SKU: [product_sku]
Product name: [product_name]
Product URL: [product_url]
Source URL: [source_url]
UTM source: [utm_source]
UTM medium: [utm_medium]
UTM campaign: [utm_campaign]
UTM content: [utm_content]
UTM term: [utm_term]

--
This request was submitted on [_site_title] ([_site_url]).
MAIL;

$mail_2_body = <<<'MAIL'
Thank you [full_name],

We received your quote request and will review it shortly.

Product interest: [product_interest]
Quantity / estimated volume: [quantity]

--
[_site_title]
MAIL;

$existing = get_page_by_title( $form_title, OBJECT, 'wpcf7_contact_form' );

$form_id = $existing ? (int) $existing->ID : 0;

$post_data = array(
	'post_title'   => $form_title,
	'post_name'    => 'skvn-request-a-quote',
	'post_status'  => 'publish',
	'post_type'    => 'wpcf7_contact_form',
	'post_content' => '',
);

if ( $form_id ) {
	$post_data['ID'] = $form_id;
	wp_update_post( wp_slash( $post_data ) );
} else {
	$form_id = wp_insert_post( wp_slash( $post_data ), true );
}

if ( is_wp_error( $form_id ) || ! $form_id ) {
	WP_CLI::error( 'Could not create or update the CF7 form.' );
}

update_post_meta( $form_id, '_form', $form_markup );
update_post_meta(
	$form_id,
	'_mail',
	array(
		'active'             => true,
		'subject'            => '[_site_title] Quote request from [company_name]',
		'sender'             => '[_site_title] <[_site_admin_email]>',
		'recipient'          => '[_site_admin_email]',
		'body'               => $mail_body,
		'additional_headers' => 'Reply-To: [email]',
		'attachments'        => '',
		'use_html'           => false,
		'exclude_blank'      => false,
	)
);
update_post_meta(
	$form_id,
	'_mail_2',
	array(
		'active'             => true,
		'subject'            => '[_site_title] We received your quote request',
		'sender'             => '[_site_title] <[_site_admin_email]>',
		'recipient'          => '[email]',
		'body'               => $mail_2_body,
		'additional_headers' => 'Reply-To: [_site_admin_email]',
		'attachments'        => '',
		'use_html'           => false,
		'exclude_blank'      => false,
	)
);
update_post_meta(
	$form_id,
	'_messages',
	array(
		'mail_sent_ok'        => 'Thank you. Your quote request has been sent.',
		'mail_sent_ng'        => 'There was an error trying to send your quote request. Please try again later.',
		'validation_error'    => 'One or more fields have an error. Please check and try again.',
		'spam'                => 'There was an error trying to send your quote request. Please try again later.',
		'accept_terms'        => 'You must accept the terms and conditions before sending your message.',
		'invalid_required'    => 'Please fill out this field.',
		'invalid_too_long'    => 'This field has too long input.',
		'invalid_too_short'   => 'This field has too short input.',
		'upload_failed'       => 'There was an unknown error uploading the file.',
		'upload_file_type_invalid' => 'You are not allowed to upload files of this type.',
		'upload_file_too_large' => 'The uploaded file is too large.',
		'upload_failed_php_error' => 'There was an error uploading the file.',
	)
);
update_post_meta( $form_id, '_additional_settings', '' );
update_post_meta( $form_id, '_locale', get_locale() );

$quote_page_content = sprintf(
	"<!-- wp:group {\"align\":\"full\",\"className\":\"skvn-section skvn-section--soft skvn-quote-page\",\"layout\":{\"type\":\"constrained\"}} -->\n" .
	"<div class=\"wp-block-group alignfull skvn-section skvn-section--soft skvn-quote-page\"><!-- wp:heading {\"className\":\"skvn-section__heading\"} -->\n" .
	"<h2 class=\"wp-block-heading skvn-section__heading\">Request a Quote</h2>\n" .
	"<!-- /wp:heading -->\n\n" .
	"<!-- wp:paragraph {\"className\":\"skvn-section__lead\"} -->\n" .
	"<p class=\"skvn-section__lead\">Share the product, volume, destination, and packing requirements. Our team will review and reply by email.</p>\n" .
	"<!-- /wp:paragraph -->\n\n" .
	"<!-- wp:shortcode -->\n[contact-form-7 id=\"%d\" title=\"%s\"]\n<!-- /wp:shortcode -->\n\n" .
	"<!-- wp:html -->\n<script>\ndocument.addEventListener('wpcf7mailsent', function (event) {\n  if (event.target && event.target.querySelector('.skvn-quote-form')) {\n    window.location.href = '/quote-thank-you/';\n  }\n});\n</script>\n<!-- /wp:html --></div>\n" .
	"<!-- /wp:group -->",
	$form_id,
	esc_html( $form_title )
);

$thank_you_page_content = <<<'HTML'
<!-- wp:group {"align":"full","className":"skvn-section skvn-section--soft skvn-quote-thank-you","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull skvn-section skvn-section--soft skvn-quote-thank-you"><!-- wp:heading {"className":"skvn-section__heading"} -->
<h2 class="wp-block-heading skvn-section__heading">Thank you for your quote request</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"skvn-section__lead"} -->
<p class="skvn-section__lead">We received your request. Our team will review the details and reply by email.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"skvn-button skvn-button--primary"} -->
<div class="wp-block-button skvn-button skvn-button--primary"><a class="wp-block-button__link wp-element-button" href="/shop/">Back to products</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML;

/**
 * Create or update a page by slug.
 *
 * @param string $title Page title.
 * @param string $slug Page slug.
 * @param string $content Page content.
 * @return int
 */
function skvn_marine_quote_flow_upsert_page( $title, $slug, $content ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	$data = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => $content,
	);

	if ( $page ) {
		$data['ID'] = (int) $page->ID;
		$result     = wp_update_post( wp_slash( $data ), true );
	} else {
		$result = wp_insert_post( wp_slash( $data ), true );
	}

	if ( is_wp_error( $result ) || ! $result ) {
		WP_CLI::error( 'Could not create or update page: ' . $slug );
	}

	return (int) $result;
}

$quote_page_id     = skvn_marine_quote_flow_upsert_page( 'Request a Quote', 'request-a-quote', $quote_page_content );
$thank_you_page_id = skvn_marine_quote_flow_upsert_page( 'Quote Thank You', 'quote-thank-you', $thank_you_page_content );

update_post_meta( $quote_page_id, '_skvn_hide_title', '1' );
update_post_meta( $quote_page_id, '_skvn_full_width_canvas', '1' );
update_post_meta( $thank_you_page_id, '_skvn_hide_title', '1' );
update_post_meta( $thank_you_page_id, '_skvn_full_width_canvas', '1' );

WP_CLI::success(
	sprintf(
		'Configured quote flow: form #%d, request page #%d, thank-you page #%d.',
		$form_id,
		$quote_page_id,
		$thank_you_page_id
	)
);
