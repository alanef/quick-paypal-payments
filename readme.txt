=== Quick Paypal Payments ===
Contributors: Fullworks
Tags: paypal payment form, paypal, payments
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 5.7.51
License: 	GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zero to PayPal with just one shortcode. Jam packed with features and options with easy to use custom settings.

== Description ==

Taking PayPal payments just got easier, one shortcode to collect any amount from anywhere on your site. With Instant Payment Notifications and GDPR compliancy options.

= Features =

*   Accepts all PayPal approved currencies
*   Fixed or variable payment amounts
*   Easy to use range of shortcode options
*   Fully editable
*   Loads of styling options
*   Multi-language
*   Add custom forms anywhere on your site
*   Downloadable payment records
*   Fully editable autoresponder
*   Instant Payment Notifications
*   GDPR compliant

= Go Pro =

*   Multiple products - sell up to 9 items at once.
*   Custom Logo for Paypal page
*   Mailchimp Integration
*   Personalised Support

= PHP 8.0 =

Tested with PHP 8.0

= Developers plugin page =

[quick paypal payments plugin](https://fullworks.net/products/quick-paypal-payments/).

== External Services ==

This plugin relies on PayPal to take payments. It cannot function without it.

**PayPal checkout**
When a visitor submits one of your payment forms, the plugin sends them to PayPal
to complete the payment. The data submitted to PayPal is the payment amount,
currency, item name and payment reference, together with any name, email address,
postal address and telephone number the visitor entered on your form. This happens
only at the point a visitor submits a payment form.

On pages that contain a payment form, the plugin also loads PayPal's checkout
script from https://www.paypalobjects.com/api/checkout.js.

**PayPal Instant Payment Notification**
If you enable IPN, PayPal sends your site a notification when a payment is made.
Your site posts that notification back to PayPal at https://ipnpb.paypal.com (or
https://ipnpb.sandbox.paypal.com in sandbox mode) so PayPal can confirm it is
genuine. Only the notification PayPal sent is posted back.

PayPal terms of service: https://www.paypal.com/uk/legalhub/useragreement-full
PayPal privacy policy: https://www.paypal.com/uk/legalhub/privacy-full

**Freemius**
Licensing, updates and optional usage tracking are handled by Freemius. Usage
tracking is opt in and you are asked when the plugin is activated. If you opt in,
your site URL, WordPress and PHP versions and the administrator email address are
sent to Freemius. If you decline, no data is sent.

Freemius terms of service: https://freemius.com/terms/
Freemius privacy policy: https://freemius.com/privacy/

== Screenshots ==
1.  This is the main admin screen.
2.  An example form.
3.  The payment record

More [example forms](https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/).

== Installation ==

1.  Login to your wordpress dashboard.
2.  Go to 'Plugins', 'Add New' then search for 'Quick Paypal Payments'.
3.  Follow the on screen instructions.
4.  Activate the plugin.
5.  Go to the plugin 'Settings' page to add your paypal email address and currency
6.  Edit any of the form settings if you wish.
7.  Use the shortcode `[qpp]` in your posts or page or even in your sidebar.
8.  To use the form in your theme files use the code `<?php echo do_shortcode('[qpp]'); ?>`.

== Frequently Asked Questions ==

= How do I change the labels and captions? =
Go to your plugin list and scroll down until you see 'Quick Paypal Payments' and click on 'Settings'.

= What's the shortcode? =
[qpp]

= How do I change the styles and colours? =
Use the plugin settings style page.

= Can I have more than one payment form on a page? =
Yes. But they have to have different names. Create the forms on the setup page.

= Where can I see all the payments? =
At the bottom of the dashboard is a link called 'Payments'.

= It's all gone wrong! =
If it all goes wrong, just reinstall the plugin and start again. If you need help then [you can use the support forum](https://wordpress.org/support/plugin/quick-paypal-payments/).

= How can I report security bugs? =

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/quick-paypal-payments)

== Changelog ==

[Change Log](https://fullworksplugins.com/docs/quick-paypal-payments/installation-quick-paypal-payments/change-log-qpp/)