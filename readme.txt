=== Quick Paypal Payments ===
Contributors: Fullworks
Tags: paypal payment form, paypal, payments
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 6.0.1
License: 	GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take PayPal payments from a shortcode. Unlimited forms, any currency, no business account needed.

== Description ==

Take a PayPal payment today.

One shortcode puts a payment form anywhere on your WordPress site. All it needs is your PayPal email address, so you can be taking money in a few minutes, with no business account, no API keys and no developer. Unlimited forms, any currency PayPal accepts, and every label and colour is yours to change.

= What the free version does =

*   Unlimited payment forms, placed with a shortcode, a block or a widget
*   Any currency PayPal accepts
*   Charge one set price, or let the buyer type the amount
*   Quantity, item number, and a list of options to pick from
*   Name, email, postal address and phone number, if you want to collect them
*   Your own message field, terms and conditions, a consent tick box and a maths captcha
*   Change every label, caption and colour, or leave the defaults alone
*   A record of every payment, with a CSV download
*   Mark a payment as paid by hand once you have checked it in PayPal
*   Multi-language and GDPR ready

A personal PayPal account is enough. There is nothing to configure beyond your
email address.

= What the paid version adds =

The paid version is about getting your time back and selling more than one thing.

**Payments confirm themselves.** PayPal tells your site the moment a payment
clears, so orders mark themselves paid and you stop opening PayPal to check
whether one went through. Your buyer gets an automatic thank you email, and you
can create their WordPress account at the same time, in whichever role you
choose.

**Take card payments as well as PayPal.** Send buyers to Stripe instead and take
cards directly, on a page hosted by Stripe so no card details touch your site.
Some people will not pay through PayPal, and that is the reason they abandon a
payment.

**Sell properly rather than just collect money.** Offer a choice of prices on one
form, a slider, or a set of pre-set references. Run coupon codes with
percentage or fixed discounts, expiry dates and limited quantities. Add postage
and handling as a fixed amount or a percentage. Set a minimum amount, or switch
the form to donations.

**Charge again next month.** Recurring payments through PayPal on a schedule you
choose, stopping automatically after the number of payments you set. Recurring
forms use PayPal even on a site that otherwise takes cards.

**Sell up to nine things at once,** each with its own price and quantity, on one
form.

**And the rest:** a datepicker field, Mailchimp signup, your own logo on the
PayPal checkout page, sandbox mode for testing, and support by email and
knowledge base.

[See what is in each plan](https://fullworks.net/products/quick-paypal-payments/).

= PHP =

Tested up to PHP 8.5

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

**Stripe (paid version only)**
The free version does not contain the Stripe integration and never contacts
Stripe. In the paid version, if you enter your Stripe keys and switch Stripe on,
submitting a payment form creates a checkout session at https://api.stripe.com
and sends the visitor to a payment page hosted by Stripe. The data sent is the
payment amount, currency, item name and, if your form collects it, the visitor's
email address. No card details are entered on or handled by your site. Stripe
then notifies your site when the payment completes.

Stripe terms of service: https://stripe.com/legal/ssa
Stripe privacy policy: https://stripe.com/privacy

**Freemius**
Licensing, updates and optional usage tracking are handled by Freemius. Usage
tracking is opt in and you are asked when the plugin is activated. If you opt in,
your site URL, WordPress and PHP versions and the administrator email address are
sent to Freemius. If you decline, no data is sent.

Freemius terms of service: https://freemius.com/terms/
Freemius privacy policy: https://freemius.com/privacy/

== Screenshots ==
1.  A payment form on the front of a site.
2.  The form settings, where you choose which fields appear.
3.  Styling, with a live preview and the button images that ship with the plugin.
4.  Every payment, with a CSV download and a button to mark one paid by hand.

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

== Upgrade Notice ==

= 6.0.1 =
Version numbering only, no code change since 6.0. Upgrading from an earlier version, read the 6.0.0 notice below.

= 6.0.0 =
Some free features now need a paid plan, and payments are no longer confirmed automatically in free, though you can mark them paid by hand. If you had this plugin before 6.0 you can still claim a free lifetime Gold licence that restores everything, from the notice on the settings screen.
