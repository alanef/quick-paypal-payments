<?php

// Prevent direct access.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
use Quick_Paypal_Payments\Core\Utilities;
global $quick_paypal_payments_fs;
add_action( 'admin_init', 'qpp_settings_init' );
add_action( 'admin_menu', 'qpp_page_init' );
add_action( 'admin_menu', 'qpp_admin_pages' );
add_action(
    'plugin_row_meta',
    'qpp_plugin_row_meta',
    10,
    2
);
add_action( 'wp_head', 'qpp_head_css' );
/**
 * The two level settings navigation.
 *
 * Eleven top level tabs wrapped onto two rows and put unrelated screens side by
 * side. Grouped the way WooCommerce does it: a short row of sections, then the
 * pages within the active one. Validation Messages and the Auto Responder are
 * together because both are simply text shown or sent to the buyer, which was
 * never apparent when they sat five tabs apart.
 *
 * Tab keys are unchanged. Only the presentation moved, so every existing link
 * and every render function still works.
 *
 * @return array Group key => label and pages.
 */
/**
 * The branded page header, shared by the settings and payments screens.
 *
 * Both printed a bare h1. The logo is shipped inside the plugin rather than
 * pulled from ps.w.org: offloaded assets are not allowed, and the previous
 * remote logo never rendered anyway because a global find and replace had
 * mangled its src attribute.
 *
 * @return void
 */
function qpp_admin_header() {
    /*
     * The trailing hr.wp-header-end is WordPress's marker for where admin
     * notices belong. Without it WordPress relocates them to just after the
     * first h1 it finds, and this h1 is nested in the header, so every notice
     * landed inside the blue banner.
     */
    printf(
        '<div class="qpp-admin-header"><img class="qpp-admin-header-logo" src="%1$s" width="44" height="44" alt="" /><div><h1>%2$s</h1><p>%3$s</p></div></div><hr class="wp-header-end" />',
        esc_url( plugins_url( 'ui/admin/images/qpp-logo-128.jpg', QUICK_PAYPAL_PAYMENTS_PLUGIN_DIR . 'quick-paypal-payments.php' ) ),
        esc_html__( 'Quick PayPal Payments', 'quick-paypal-payments' ),
        esc_html__( 'Take PayPal and card payments from a shortcode.', 'quick-paypal-payments' )
    );
}

function qpp_admin_tab_groups() {
    return array(
        'setup'        => array(
            'label' => esc_html__( 'Global', 'quick-paypal-payments' ),
            'pages' => array(
                'setup' => esc_html__( 'Global', 'quick-paypal-payments' ),
            ),
        ),
        'form'         => array(
            'label' => esc_html__( 'Forms', 'quick-paypal-payments' ),
            'pages' => array(
                'manage'   => esc_html__( 'Create / Manage', 'quick-paypal-payments' ),
                'settings' => esc_html__( 'Fields', 'quick-paypal-payments' ),
                'styles'   => esc_html__( 'Styling', 'quick-paypal-payments' ),
                'address'  => esc_html__( 'Personal Details', 'quick-paypal-payments' ),
            ),
        ),
        'messages'     => array(
            'label' => esc_html__( 'Messages', 'quick-paypal-payments' ),
            'pages' => array(
                'error'        => esc_html__( 'Validation', 'quick-paypal-payments' ),
                'autoresponce' => esc_html__( 'Auto Responder', 'quick-paypal-payments' ),
            ),
        ),
        'selling'      => array(
            'label' => esc_html__( 'Selling', 'quick-paypal-payments' ),
            'pages' => array(
                'multipleproducts' => esc_html__( 'Products', 'quick-paypal-payments' ),
                'coupon'           => esc_html__( 'Coupons', 'quick-paypal-payments' ),
            ),
        ),
        'payments'     => array(
            'label' => esc_html__( 'Payments', 'quick-paypal-payments' ),
            'pages' => array(
                'send'   => esc_html__( 'Send Options', 'quick-paypal-payments' ),
                'stripe' => 'Stripe',
                'ipn'    => 'IPN',
            ),
            'links' => array(array(
                'label' => esc_html__( 'Payment Records', 'quick-paypal-payments' ),
                'url'   => 'admin.php?page=quick-paypal-payments-messages',
            )),
        ),
        'integrations' => array(
            'label' => esc_html__( 'Integrations', 'quick-paypal-payments' ),
            'pages' => array(
                'integrations' => esc_html__( 'Integrations', 'quick-paypal-payments' ),
            ),
        ),
    );
}

/**
 * Which group a page belongs to.
 *
 * Pages reachable only by link, such as the shortcode reference and the reset
 * screen, are not in any group and leave no section marked, which is honest:
 * they are not part of the navigation.
 *
 * @param string $page Current tab key.
 *
 * @return string Group key, or '' when the page is not in the navigation.
 */
function qpp_admin_tab_group_for(  $page  ) {
    foreach ( qpp_admin_tab_groups() as $group => $data ) {
        if ( isset( $data['pages'][$page] ) ) {
            return $group;
        }
    }
    return '';
}

function qpp_admin_tabs(  $current = 'settings'  ) {
    $groups = qpp_admin_tab_groups();
    $active = qpp_admin_tab_group_for( $current );
    echo '<h2 class="nav-tab-wrapper">';
    foreach ( $groups as $group => $data ) {
        $class = ( $group === $active ? ' nav-tab-active' : '' );
        // Links to the first page in the group, so a section is always one click.
        $first = key( $data['pages'] );
        echo '<a class="nav-tab' . esc_attr( $class ) . '" href="?page=quick-paypal-payments&tab=' . esc_attr( $first ) . '">' . esc_html( $data['label'] ) . '</a>';
    }
    echo '</h2>';
    $links = ( isset( $groups[$active]['links'] ) ? $groups[$active]['links'] : array() );
    // A single page group with nothing else to point at has nothing to choose
    // between, so no second row.
    if ( '' === $active || count( $groups[$active]['pages'] ) < 2 && empty( $links ) ) {
        return;
    }
    echo '<ul class="qpp-subnav">';
    foreach ( $groups[$active]['pages'] as $page => $label ) {
        $class = ( $page === $current ? 'qpp-subnav-current' : '' );
        echo '<li><a class="' . esc_attr( $class ) . '" href="?page=quick-paypal-payments&tab=' . esc_attr( $page ) . '">' . esc_html( $label ) . '</a></li>';
    }
    foreach ( $links as $link ) {
        echo '<li class="qpp-subnav-away"><a href="' . esc_url( admin_url( $link['url'] ) ) . '">' . esc_html( $link['label'] ) . '</a></li>';
    }
    echo '</ul>';
}

function qpp_tabbed_page() {
    global $quick_paypal_payments_fs;
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    qpp_admin_header();
    /*
     * Form Settings is a long page with a single Save at the very bottom, so it
     * is easy to configure several fields, click a tab and lose all of it with no
     * warning. Marks the page dirty on the first edit and lets the browser ask.
     *
     * Printed separately rather than appended to $content, which goes through
     * wp_kses() and would strip a script tag.
     */
    wp_print_inline_script_tag( '(function () {
            var dirty = false;
            var forms = document.querySelectorAll( ".qpp-settings form" );
            Array.prototype.forEach.call( forms, function ( form ) {
                form.addEventListener( "input", function () { dirty = true; } );
                form.addEventListener( "change", function () { dirty = true; } );
                form.addEventListener( "submit", function () { dirty = false; } );
            } );
            window.addEventListener( "beforeunload", function ( event ) {
                if ( ! dirty ) {
                    return;
                }
                event.preventDefault();
                event.returnValue = "";
            } );
        })();' );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
    if ( isset( $_GET['tab'] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required.
        $tab = sanitize_text_field( $_GET['tab'] );
        qpp_admin_tabs( $tab );
    } else {
        qpp_admin_tabs( 'setup' );
        $tab = 'setup';
    }
    switch ( $tab ) {
        case 'setup':
            qpp_setup( $id );
            break;
        case 'manage':
            qpp_forms_page( $id );
            break;
        case 'settings':
            qpp_form_options( $id );
            break;
        case 'styles':
            qpp_styles( $id );
            break;
        case 'send':
            qpp_send_page( $id );
            break;
        case 'error':
            qpp_error_page( $id );
            break;
        case 'address':
            qpp_address( $id );
            break;
        /*
         * There is no case for 'shortcodes' or 'reset'. Both used to route to
         * functions that no longer exist anywhere in the plugin, so reaching
         * either tab was a white screen rather than a page. Nothing links to
         * them now; an old bookmark falls through to the default below, which is
         * the same thing any unrecognised tab does.
         */
        case 'coupon':
            qpp_coupon_codes( $id );
            break;
        case 'ipn':
            qpp_ipn_page();
            break;
        case 'autoresponce':
            qpp_autoresponce_page( $id );
            break;
        case 'stripe':
            qpp_stripe_page();
            break;
        case 'integrations':
            qpp_integrations_page( $id );
            break;
        case 'multipleproducts':
            qpp_upgrade_page( 'platinum', esc_html__( 'Multiple Products', 'quick-paypal-payments' ) );
            break;
    }
}

/**
 * Upsell markup shown in place of a setting the current plan does not include.
 *
 * Named the plan rather than saying "Pro", which stopped identifying a single
 * tier in 6.0. Callers are free installs and paid installs below the required
 * plan, so the wording has to work for both.
 *
 * @param string $plan    Lowest plan that unlocks the feature, lower case.
 * @param string $feature Human readable feature name.
 *
 * @return string
 */
function qpp_upgrade_prompt(  $plan, $feature  ) {
    // Shared with the sidebar box so the wording and the destination always
    // agree. This used to link to the trial while saying "See plans and prices".
    $cta = qpp_upgrade_cta();
    return '<span class="description">' . sprintf( 
        /* translators: 1: feature name, 2: plan name such as Silver. */
        esc_html__( '%1$s is available in the %2$s plan and above.', 'quick-paypal-payments' ),
        esc_html( $feature ),
        esc_html( ucfirst( $plan ) )
     ) . ' <a href="' . esc_url( $cta['url'] ) . '">' . esc_html( $cta['label'] ) . '</a></span>';
}

/**
 * Says whether the field a settings screen configures is actually switched on.
 *
 * The Products and Coupons screens configure something that only reaches the
 * form when its field is ticked on Forms -> Fields. Neither screen said so, so a
 * merchant could fill one in completely, save it, and see no change to the form
 * with nothing anywhere explaining why.
 *
 * @param bool   $enabled     Whether the field is ticked for the current form.
 * @param string $feature     What this screen configures, sentence case plural.
 * @param string $field_label The row label on the Fields screen, so the
 *                            instruction names what to look for.
 * @param string $blocked     Optional reason the field cannot be ticked at all,
 *                            which outranks being merely switched off.
 *
 * @return string
 */
function qpp_field_status_notice(
    $enabled,
    $feature,
    $field_label,
    $blocked = ''
) {
    $fields = '<a href="' . esc_url( admin_url( 'options-general.php?page=quick-paypal-payments&tab=settings' ) ) . '">' . esc_html__( 'Forms &rarr; Fields', 'quick-paypal-payments' ) . '</a>';
    if ( '' !== $blocked ) {
        return '<p class="qpp-status qpp-status-blocked"><strong>' . sprintf( esc_html__( '%s cannot be used on this form.', 'quick-paypal-payments' ), esc_html( $feature ) ) . '</strong> ' . esc_html( $blocked ) . ' ' . esc_html__( 'Anything set here is kept, and applies again if that changes.', 'quick-paypal-payments' ) . '</p>';
    }
    if ( $enabled ) {
        return '<p class="qpp-status qpp-status-on">' . sprintf(
            /* translators: 1: what the screen configures, e.g. "Coupons", 2: the row label on the Fields screen, 3: a link to the Fields screen. */
            esc_html__( '%1$s are switched on for this form, so what you set here appears on it. Untick "%2$s" on %3$s to switch them off.', 'quick-paypal-payments' ),
            '<strong>' . esc_html( $feature ) . '</strong>',
            esc_html( $field_label ),
            $fields
        ) . '</p>';
    }
    return '<p class="qpp-status qpp-status-off">' . sprintf(
        /* translators: 1: what the screen configures, e.g. "Coupons", 2: the row label on the Fields screen, 3: a link to the Fields screen. */
        esc_html__( '%1$s are switched off for this form, so nothing set here will appear on it. Tick "%2$s" on %3$s to switch them on. Your settings are kept either way.', 'quick-paypal-payments' ),
        '<strong>' . esc_html( $feature ) . '</strong>',
        esc_html( $field_label ),
        $fields
    ) . '</p>';
}

/**
 * Renders a whole settings tab as an upsell.
 *
 * The tab stays visible on every plan so the feature is discoverable, but the
 * page body is replaced. Callers return immediately after this, which also stops
 * the tab's save handler running, so the settings cannot be posted directly by a
 * plan that does not include them.
 *
 * @param string $plan    Lowest plan that unlocks the feature, lower case.
 * @param string $feature Human readable feature name.
 *
 * @return void
 */
function qpp_upgrade_page(  $plan, $feature  ) {
    $content = '<div class="qpp-settings"><div class="qpp-options" style="width:auto;float:none;">' . '<h2>' . esc_html( $feature ) . '</h2>' . '<p>' . qpp_upgrade_prompt( $plan, $feature ) . '</p>' . qpp_upgrade_pitch( $plan ) . '</div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

/**
 * Sells what the paid plans can do, on a tab a plan cannot open.
 *
 * Deliberately not a feature comparison. The upgrade link goes to the Freemius
 * pricing page, which already lays the plans out side by side, so repeating that
 * here is duplicated work that would drift out of step with the dashboard.
 *
 * The job on this page is that a free install has no idea the plugin can do any
 * of this. They clicked a tab expecting a setting and found a wall, so the useful
 * thing is to say what becomes possible, in terms of what it does for them rather
 * than what it is called.
 *
 * @param string $highlight Plan the clicked feature needs.
 *
 * @return string
 */
function qpp_upgrade_pitch(  $highlight = ''  ) {
    $pitches = array(
        'silver'   => array(
            'title' => __( 'Stop checking PayPal by hand', 'quick-paypal-payments' ),
            'lines' => array(
                __( 'Payments confirm themselves. An order marks itself paid the moment PayPal takes the money, instead of sitting there until you go and look.', 'quick-paypal-payments' ),
                __( 'Your buyer gets a thank you email automatically, with their order details in it.', 'quick-paypal-payments' ),
                __( 'Charge postage and handling, as a fixed amount or a percentage of the order.', 'quick-paypal-payments' ),
                __( 'Test everything safely in PayPal sandbox before you take a real payment.', 'quick-paypal-payments' ),
                __( 'Create a WordPress account for the buyer as they pay, so paying and having a login are one step.', 'quick-paypal-payments' ),
                __( 'Email and knowledge base support, instead of the public forum.', 'quick-paypal-payments' )
            ),
        ),
        'gold'     => array(
            'title' => __( 'Sell, rather than just collect money', 'quick-paypal-payments' ),
            'lines' => array(
                __( 'Coupon codes, fixed amount or percentage, with your own expiry and usage limits.', 'quick-paypal-payments' ),
                __( 'Recurring payments, so a subscription or a payment plan bills itself.', 'quick-paypal-payments' ),
                __( 'Take donations, with a slider or a set of suggested amounts instead of an empty box.', 'quick-paypal-payments' ),
                __( 'Offer a choice of prices as buttons or a dropdown, instead of one price or an empty box.', 'quick-paypal-payments' )
            ),
        ),
        'platinum' => array(
            'title' => __( 'Take cards, not just PayPal', 'quick-paypal-payments' ),
            'lines' => array(
                __( 'Stripe Checkout, so customers who will not use PayPal can pay by card. Hosted by Stripe, so no card details touch your site.', 'quick-paypal-payments' ),
                __( 'Sell up to nine products from one form, with their own prices and quantities.', 'quick-paypal-payments' ),
                __( 'Collect a date on the form, for bookings, deliveries or appointments.', 'quick-paypal-payments' ),
                __( 'Add every buyer to your Mailchimp list as they pay.', 'quick-paypal-payments' )
            ),
        ),
    );
    $order = array('silver', 'gold', 'platinum');
    /*
     * The plan that was clicked goes first. Someone who opened the Stripe tab was
     * reading about Stripe, and leading with two blocks of PayPal copy answers a
     * question they did not ask.
     */
    if ( '' !== $highlight && isset( $pitches[$highlight] ) ) {
        $rest = array_values( array_diff( $order, array($highlight) ) );
        $below = array_slice( $order, 0, array_search( $highlight, $order, true ) );
        $above = array_slice( $order, array_search( $highlight, $order, true ) + 1 );
    } else {
        $rest = $order;
        $below = array();
        $above = array();
    }
    $render = function ( $plan, $lead ) use($pitches) {
        $style = ( $lead ? ' style="border-left:4px solid #2271b1;padding-left:12px;"' : '' );
        $out = '<div' . $style . '><h3>' . esc_html( $pitches[$plan]['title'] ) . ' <span class="description">' . esc_html( ucfirst( $plan ) ) . '</span></h3>' . '<ul style="list-style:disc;margin-left:1.4em;">';
        foreach ( $pitches[$plan]['lines'] as $line ) {
            $out .= '<li>' . esc_html( $line ) . '</li>';
        }
        return $out . '</ul></div>';
    };
    $out = '';
    if ( '' !== $highlight && isset( $pitches[$highlight] ) ) {
        $out .= $render( $highlight, true );
        if ( !empty( $below ) ) {
            $names = array_map( 'ucfirst', $below );
            $out .= '<p>' . sprintf( 
                /* translators: 1: plan name, 2: the plans beneath it, e.g. "Gold and Silver". */
                esc_html__( '%1$s includes everything in %2$s as well:', 'quick-paypal-payments' ),
                esc_html( ucfirst( $highlight ) ),
                /* translators: joins two plan names in a list. */
                esc_html( implode( __( ' and ', 'quick-paypal-payments' ), array_reverse( $names ) ) )
             ) . '</p>';
            foreach ( array_reverse( $below ) as $plan ) {
                $out .= $render( $plan, false );
            }
        }
        if ( !empty( $above ) ) {
            $out .= '<p>' . esc_html__( 'And above that:', 'quick-paypal-payments' ) . '</p>';
            foreach ( $above as $plan ) {
                $out .= $render( $plan, false );
            }
        }
    } else {
        foreach ( $order as $plan ) {
            $out .= $render( $plan, false );
        }
    }
    $cta = qpp_upgrade_cta();
    $out .= '<p><a class="button button-primary" href="' . esc_url( $cta['url'] ) . '">' . esc_html( $cta['label'] ) . '</a></p>';
    return $out;
}

/**
 * Where an upgrade link should point.
 *
 * @return string
 */
function qpp_upgrade_cta() {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    /*
     * A free install with a trial still available is asked to try, not to buy.
     * Trying is the smaller decision, and the Freemius trial runs on the top
     * plan so they see everything. Anyone else is sent to the plans page.
     */
    $on_free = !$quick_paypal_payments_fs->is_plan_or_trial__premium_only( 'silver' );
    $trial_available = !$quick_paypal_payments_fs->is_trial() && !$quick_paypal_payments_fs->is_trial_utilized();
    if ( $on_free && $trial_available ) {
        return array(
            'url'   => $quick_paypal_payments_fs->get_trial_url(),
            'label' => __( 'Start your free trial', 'quick-paypal-payments' ),
            'trial' => true,
        );
    }
    return array(
        'url'   => $quick_paypal_payments_fs->get_upgrade_url(),
        'label' => __( 'See plans and prices', 'quick-paypal-payments' ),
        'trial' => false,
    );
}

function qpp_upgrade_url() {
    $cta = qpp_upgrade_cta();
    return $cta['url'];
}

/**
 * The upgrade box for the admin sidebar.
 *
 * Free installs are pitched the trial rather than a plan. Naming a plan here is
 * how "Upgrade to Silver" ended up linking to a page headed "Test all our
 * awesome Platinum premium features". Once the trial is spent, or on a paid plan
 * below the top one, the ask becomes the next plan up.
 *
 * Shared by the settings and payments screens so the two cannot drift.
 *
 * @return string Markup, or nothing when there is nothing left to sell.
 */
function qpp_upgrade_box() {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    $cta = qpp_upgrade_cta();
    if ( $cta['trial'] ) {
        /*
         * No trial length here. It is set in the Freemius dashboard and already
         * stated in their own admin banner and on the plans page, so repeating it
         * only creates something that can go stale or disagree.
         */
        return '<div class="qppupgrade"><a href="' . esc_url( $cta['url'] ) . '">
        <h3>' . esc_html__( 'Try every feature free', 'quick-paypal-payments' ) . '</h3>
        <p>' . esc_html__( 'Automatic payment confirmation, coupons, recurring payments and card payments with Stripe. The lot, for the whole trial.', 'quick-paypal-payments' ) . '</p>
        <p>' . esc_html( $cta['label'] ) . '</p>
        </a></div>';
    }
    $upnext = 'Silver';
    $upgives = __( 'payments confirmed automatically, the Auto Responder, postage and handling, creating a WordPress user when someone pays, and email and knowledge base support', 'quick-paypal-payments' );
    return '<div class="qppupgrade"><a href="' . esc_url( $cta['url'] ) . '">
        <h3>' . sprintf( 
        /* translators: %s: plan name such as Silver. */
        esc_html__( 'Upgrade to %s', 'quick-paypal-payments' ),
        esc_html( $upnext )
     ) . '</h3>
        <p>' . sprintf( 
        /* translators: %s: a list of what the next plan adds. */
        esc_html__( 'Upgrading gives %s.', 'quick-paypal-payments' ),
        esc_html( $upgives )
     ) . '</p>
        <p>' . esc_html( $cta['label'] ) . '</p>
        </a></div>';
}

/**
 * Create and manage the payment forms.
 *
 * Lived on the Setup screen, mixed in with the PayPal account email and the
 * global switches, so creating a form meant scrolling past settings that have
 * nothing to do with forms and submitting all of them together.
 *
 * Saves only what it owns. That screen and this one both write qpp_setup, so
 * each reads the stored option and changes its own keys. Rebuilding the option
 * from one screen's post would delete whatever the other screen manages.
 *
 * @param string $id Current form id.
 *
 * @return void
 */
function qpp_forms_page(  $id  ) {
    $qpp_setup = qpp_get_stored_setup();
    $new_curr = '';
    if ( isset( $_POST['qpp_forms_save'] ) && check_admin_referer( "save_qpp" ) ) {
        $stored = get_option( 'qpp_setup' );
        if ( !is_array( $stored ) ) {
            $stored = array();
        }
        $stored['alternative'] = sanitize_text_field( wp_unslash( $_POST['alternative'] ) );
        if ( !empty( $_POST['new_form'] ) ) {
            $new = sanitize_text_field( wp_unslash( $_POST['new_form'] ) );
            // Form names become part of an option name, so anything but letters
            // is dropped rather than escaped.
            $new = preg_replace( "/[^A-Za-z]/", '', $new );
            $stored['current'] = $new;
            $stored['alternative'] = $new . ',' . $stored['alternative'];
        } else {
            $stored['current'] = ( isset( $_POST['current'] ) ? sanitize_text_field( wp_unslash( $_POST['current'] ) ) : '' );
        }
        $qpp_curr = array();
        $qpp_email = array();
        foreach ( explode( ',', $stored['alternative'] ) as $item ) {
            $qpp_curr[$item] = ( isset( $_POST['qpp_curr' . $item] ) ? sanitize_text_field( wp_unslash( $_POST['qpp_curr' . $item] ) ) : '' );
            $qpp_email[$item] = ( isset( $_POST['qpp_email' . $item] ) ? sanitize_text_field( wp_unslash( $_POST['qpp_email' . $item] ) ) : '' );
        }
        if ( !empty( $_POST['new_form'] ) ) {
            $qpp_curr[$stored['current']] = ( isset( $_POST['new_curr'] ) ? sanitize_text_field( wp_unslash( $_POST['new_curr'] ) ) : '' );
        }
        update_option( 'qpp_curr', $qpp_curr );
        update_option( 'qpp_email', $qpp_email );
        update_option( 'qpp_setup', $stored );
        $qpp_setup = qpp_get_stored_setup();
        qpp_admin_notice( esc_html__( 'The forms have been updated.', 'quick-paypal-payments' ) );
        if ( !empty( $_POST['qpp_clone'] ) && !empty( $_POST['new_form'] ) ) {
            qpp_clone( $qpp_setup['current'], sanitize_text_field( wp_unslash( $_POST['qpp_clone'] ) ) );
        }
    }
    foreach ( explode( ',', $qpp_setup['alternative'] ) as $item ) {
        if ( '' === $item ) {
            continue;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified below before anything is deleted.
        if ( !isset( $_POST['deleteform' . $item], $_POST['delete' . $item] ) ) {
            continue;
        }
        check_admin_referer( "save_qpp" );
        if ( sanitize_text_field( wp_unslash( $_POST['deleteform' . $item] ) ) !== $item ) {
            continue;
        }
        $forms = $qpp_setup['alternative'];
        qpp_delete_things( $item );
        $stored = get_option( 'qpp_setup' );
        $stored['alternative'] = str_replace( $item . ',', '', $forms );
        $stored['current'] = '';
        update_option( 'qpp_setup', $stored );
        $qpp_setup = qpp_get_stored_setup();
        /* translators: %s is the name of the deleted form. */
        qpp_admin_notice( sprintf( esc_html__( 'The form named %s has been deleted.', 'quick-paypal-payments' ), $item ) );
        $id = '';
    }
    $qpp_curr = qpp_get_stored_curr();
    $qpp_email = qpp_get_stored_email();
    if ( !$new_curr ) {
        $new_curr = $qpp_curr[''];
    }
    $arr = explode( ',', $qpp_setup['alternative'] );
    sort( $arr );
    $content = '<div class="qpp-settings"><div class="qpp-options">
    <form method="post" action="">
    <h2>' . esc_html__( 'Your Forms', 'quick-paypal-payments' ) . '</h2>
    <p class="description">' . esc_html__( 'The selected form is the one the other tabs edit.', 'quick-paypal-payments' ) . '</p>
    <table>
    <tr>
    <td><b>' . esc_html__( 'Form name', 'quick-paypal-payments' ) . '&nbsp;&nbsp;</b></td>
    <td><b>' . esc_html__( 'Currency', 'quick-paypal-payments' ) . '</b></td>
    <td><b>' . esc_html__( 'Shortcode', 'quick-paypal-payments' ) . '</b></td>
    <td></td>
    </tr>';
    foreach ( $arr as $item ) {
        $checked = ( $qpp_setup['current'] == $item ? 'checked' : '' );
        $formname = ( '' === $item ? 'default' : $item );
        if ( !isset( $qpp_email[$item] ) || !$qpp_email[$item] ) {
            $qpp_email[$item] = $qpp_setup['email'];
        }
        $content .= '<tr>
        <td><input type="radio" name="current" value="' . esc_attr( $item ) . '" ' . checked( $checked, 'checked', false ) . ' /> ' . esc_html( $formname ) . '</td>
        <td>' . qpp_currency_select( 'qpp_curr' . $item, $qpp_curr[$item] ) . '</td>
        <td><code>[qpp' . (( $item ? ' form="' . esc_attr( $item ) . '"' : '' )) . ']</code></td>
        <td>';
        if ( $item ) {
            $content .= '<input type="hidden" name="deleteform' . esc_attr( $item ) . '" value="' . esc_attr( $item ) . '">
            <input type="submit" name="delete' . esc_attr( $item ) . '" class="button-secondary qpp-danger" value="' . esc_attr__( 'Delete', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( sprintf( 
                /* translators: %s: form name. */
                __( 'Delete the form %s and all of its settings? This cannot be undone.', 'quick-paypal-payments' ),
                $item
             ) ) . '\' );" />';
        }
        $content .= '</td></tr>';
    }
    $content .= '</table>
    <p><input type="submit" name="qpp_forms_save" class="button-primary" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /></p>
    <h2>' . esc_html__( 'Create New Form', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Enter form name (letters only - no numbers, spaces or punctuation marks)', 'quick-paypal-payments' ) . '</p>
    <p><input type="text" name="new_form" value="" /></p>
    <p>' . esc_html__( 'Currency:', 'quick-paypal-payments' ) . ' ' . qpp_currency_select( 'new_curr', ( '' !== $new_curr ? $new_curr : 'GBP' ) ) . '</p>
    <p class="description">' . esc_html__( 'Only the currencies PayPal accepts are listed. A currency PayPal does not take is refused at the checkout, which the customer sees as the payment simply not working.', 'quick-paypal-payments' ) . '</p>
    <p class="description">' . esc_html__( 'If you take payment in a currency your PayPal account does not hold, set your Payment Receiving Preferences in PayPal to accept it. Otherwise every payment sits pending until you approve it by hand, and the order stays unpaid here until you do.', 'quick-paypal-payments' ) . '</p>
    <input type="hidden" name="alternative" value="' . esc_attr( $qpp_setup['alternative'] ) . '" />
    <p>' . esc_html__( 'Copy settings from an existing form.', 'quick-paypal-payments' ) . '</p>
    <select name="qpp_clone"><option>' . esc_html__( 'Do not copy settings', 'quick-paypal-payments' ) . '</option>';
    foreach ( $arr as $item ) {
        $label = ( '' === $item ? 'default' : $item );
        $content .= '<option value="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</option>';
    }
    $content .= '</select>
    <p><input type="submit" name="qpp_forms_save" class="button-primary" value="' . esc_attr__( 'Create Form', 'quick-paypal-payments' ) . '" /></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form></div>
    <div class="qpp-options" style="float:right">
    <h2>' . esc_html__( 'Using your forms', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Each form has its own fields, styling, messages and send options. Select a form above, then use the other tabs to edit it.', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'Put a form on a page with its shortcode. The default form is [qpp], and a named form adds the name.', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'Creating a form can copy the settings of one you already have, which is usually quicker than starting again.', 'quick-paypal-payments' ) . '</p>
    </div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_setup(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    $qpp_setup = qpp_get_stored_setup();
    $new_curr = '';
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        /*
         * Only the site wide keys. Creating and selecting forms is on the Forms
         * tab and writes the same option, so this reads what is stored and
         * changes its own keys rather than rebuilding the option from this post.
         */
        $stored = get_option( 'qpp_setup' );
        if ( !is_array( $stored ) ) {
            $stored = array();
        }
        $stored['email'] = ( isset( $_POST['email'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['email'] ) ) ) : '' );
        $stored['image_url'] = ( isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '' );
        $stored['sandbox'] = ( isset( $_POST['sandbox'] ) ? sanitize_text_field( wp_unslash( $_POST['sandbox'] ) ) : '' );
        $stored['disable_error'] = ( isset( $_POST['disable_error'] ) ? sanitize_text_field( wp_unslash( $_POST['disable_error'] ) ) : '' );
        $stored['nostore'] = ( isset( $_POST['nostore'] ) ? sanitize_text_field( wp_unslash( $_POST['nostore'] ) ) : '' );
        update_option( 'qpp_setup', $stored );
        $qpp_setup = qpp_get_stored_setup();
        qpp_admin_notice( esc_html__( 'The settings have been updated.', 'quick-paypal-payments' ) );
        /*
         * Said at the moment it is typed, rather than found out later by a
         * customer. The value is still saved, because losing what someone typed
         * to correct them is worse than keeping it and telling them.
         */
        $problem = qpp_paypal_account_problem( $stored['email'] );
        if ( '' !== $problem ) {
            qpp_admin_notice( $problem, 'error' );
        }
    }
    /*
     * Asks PayPal directly. Checking the shape of the address is not enough: the
     * typo that prompted this was a perfectly valid email address that simply is
     * not a PayPal account, and the only thing that knows the difference is
     * PayPal. Nothing is charged, and the merchant has to press the button.
     */
    if ( isset( $_POST['qpp_check_account'] ) && check_admin_referer( "save_qpp" ) ) {
        // Whatever is in the box, not whatever was last saved. Someone correcting
        // a typo will type the new address and press Check, and checking the old
        // one would tell them their fix had not worked.
        $to_check = ( isset( $_POST['email'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['email'] ) ) ) : $qpp_setup['email'] );
        $checked = qpp_check_paypal_account( $to_check, !empty( $qpp_setup['sandbox'] ) );
        qpp_admin_notice( $checked['message'], ( $checked['ok'] ? 'updated' : 'error' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        qpp_delete_everything();
        qpp_admin_notice( esc_html__( 'Everything has been reset.', 'quick-paypal-payments' ) );
        $qpp_setup = qpp_get_stored_setup();
    }
    $content = '<div class="qpp-settings"><div class="qpp-options">
    <form method="post" action="">
    <h2>' . esc_html__( 'Account Email', 'quick-paypal-payments' ) . '</h2>
    <p><span style="color:red; font-weight: bold; margin-right: 3px">' . esc_html__( 'Important!', 'quick-paypal-payments' ) . '</span> ' . esc_html__( 'Enter your PAYPAL email address', 'quick-paypal-payments' ) . '</p>
    <input type="text" id="qpp_account_email" class="qpp-account-email" label="Email" name="email" value="' . esc_attr( $qpp_setup['email'] ) . '" /></p>
    <p id="qpp_account_status" class="qpp-account-status"></p>
    <p><input type="submit" name="qpp_check_account" class="button-secondary" value="' . esc_attr__( 'Check this account with PayPal', 'quick-paypal-payments' ) . '" />
    <br /><span class="description">' . esc_html__( 'Checked with PayPal when you change it, and whenever you press the button. Nothing is charged. A typed address can look perfectly valid and still not be a PayPal account, which is the usual reason a customer sees "something went wrong".', 'quick-paypal-payments' ) . '</span></p>
    ';
    $content .= '<h2>' . esc_html__( 'Global Settings', 'quick-paypal-payments' ) . '</h2>';
    $content .= '<p>' . qpp_upgrade_prompt( 'silver', esc_html__( 'Sandbox mode and IPN error logging control', 'quick-paypal-payments' ) ) . '</p>';
    $content .= '<p><input type="checkbox" name="nostore"' . checked( $qpp_setup['nostore'], 'checked', false ) . ' value="checked"> ' . esc_html__( 'Do not store messages in the database (this will disable all notifications).', 'quick-paypal-payments' ) . '</p>';
    /*
     * The checkout logo is a cosmetic touch on pages PayPal hosts, and almost
     * nobody sets it. It used to have its own heading directly under the account
     * email, which gave the most obscure field on the screen the same weight as
     * the one the plugin cannot work without.
     */
    $content .= '<p><label for="qpp_image_url">' . esc_html__( 'Checkout logo', 'quick-paypal-payments' ) . '</label><br />';
    $content .= qpp_upgrade_prompt( 'platinum', esc_html__( 'A logo on the PayPal checkout pages', 'quick-paypal-payments' ) );
    $content .= '</p>';
    $content .= '<p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Update Settings', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-secondary" value="' . esc_attr__( 'Reset Everything', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'This will delete all your forms and settings. Are you sure you want to reset everything?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>';
    $content .= '</div>
    <div class="qpp-options" style="float:right">';
    $content .= qpp_upgrade_box();
    $content .= '<h2>' . esc_html__( 'Adding the payment form to your site', 'quick-paypal-payments' ) . '</h2>
    <p>' . sprintf( 
        /* translators: %s: the shortcode, [qpp], in a code tag. */
        esc_html__( 'To add the basic payment form to your posts or pages use the shortcode: %s. Shortcodes for named forms are given on the left.', 'quick-paypal-payments' ),
        '<code>[qpp]</code>'
     ) . '</p>
    <p>' . esc_html__( 'There is also a widget called "Quick Paypal Payments" you can drag and drop into a sidebar.', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'That\'s it. The payment form is ready to use.', 'quick-paypal-payments' ) . '</p>
    <h2>' . esc_html__( 'Shortcodes and Examples', 'quick-paypal-payments' ) . '</h2>
    <p>' . sprintf( 
        /* translators: %s: a link with the text "on this page". */
        esc_html__( 'All the shortcodes are given %s.', 'quick-paypal-payments' ),
        '<a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">' . esc_html__( 'on this page', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <p>' . sprintf( 
        /* translators: %s: a link with the text "on this page". */
        esc_html__( 'There are examples of payment forms %s.', 'quick-paypal-payments' ),
        '<a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">' . esc_html__( 'on this page', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <h2>' . esc_html__( 'Options and Settings', 'quick-paypal-payments' ) . '</h2>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=settings">' . esc_html__( 'Form Settings.', 'quick-paypal-payments' ) . '</a></span> ' . esc_html__( 'Change the layout of the form, add or remove fields and the order they appear and edit the labels and captions.', 'quick-paypal-payments' ) . '</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=styles">' . esc_html__( 'Styling.', 'quick-paypal-payments' ) . '</a></span> ' . esc_html__( 'Change fonts, colours, borders, images and submit button.', 'quick-paypal-payments' ) . '</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=send">' . esc_html__( 'Send Options.', 'quick-paypal-payments' ) . '</a></span> ' . esc_html__( 'Change how the form is sent.', 'quick-paypal-payments' ) . '</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=error">' . esc_html__( 'Validation Messages.', 'quick-paypal-payments' ) . '</a></span> ' . esc_html__( 'Change the error message.', 'quick-paypal-payments' ) . '</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=autoresponce">' . esc_html__( 'Auto Responder.', 'quick-paypal-payments' ) . '</a></span> ' . esc_html__( 'Set up a thank you message.', 'quick-paypal-payments' ) . '</p>
    <p><span style="font-weight:bold"><a href="?page=quick-paypal-payments&tab=ipn">' . esc_html__( 'Instant Payment Notification.', 'quick-paypal-payments' ) . '</a></span> ' . esc_html__( 'Keep track of completed payments.', 'quick-paypal-payments' ) . '</p>
    <p><span style="font-weight:bold"><a href="' . esc_url( admin_url( '?page=quick-paypal-payments-messages' ) ) . '">' . esc_html__( 'Payment Records.', 'quick-paypal-payments' ) . '</a></span> ' . sprintf( 
        /* translators: %s: the word "Payments" in bold, the dashboard menu item. */
        esc_html__( 'See all the payment records. Or click on the %s link in the dashboard menu.', 'quick-paypal-payments' ),
        '<b>' . esc_html__( 'Payments', 'quick-paypal-payments' ) . '</b>'
     ) . '</p>
    <h2>' . esc_html__( 'Support', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'First please check the knowledge base to see if a resolution to your issue is already documented', 'quick-paypal-payments' ) . '</p>
    <a href="https://fullworks.net/docs/quick-paypal-payments/" class="button" target="_blank" rel="noopener">' . esc_html__( 'Knowledge Base', 'quick-paypal-payments' ) . '</a>';
    $content .= '<p>' . esc_html__( 'If you can not find an answer, for the free version please raise your support questions on the WordPress community forum', 'quick-paypal-payments' ) . '</p>
<a href="https://wordpress.org/support/plugin/quick-paypal-payments/" class="button" target="_blank" rel="noopener">' . esc_html__( 'WordPress Support Forum', 'quick-paypal-payments' ) . '</a>
<p>' . sprintf( 
        /* translators: %s: a link with the text "upgrade to a paid plan". */
        esc_html__( 'If you require urgent or personal support please %s', 'quick-paypal-payments' ),
        '<a href="' . esc_url( $quick_paypal_payments_fs->get_upgrade_url() ) . '" >' . esc_html__( 'upgrade to a paid plan', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>';
    $content .= '</div>
    </div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_clone(  $id, $clone  ) {
    if ( $clone == 'default' ) {
        $clone = '';
    }
    $update = qpp_get_stored_options( $clone );
    update_option( 'qpp_options' . $id, $update );
    $update = qpp_get_stored_send( $clone );
    update_option( 'qpp_send' . $id, $update );
    $update = qpp_get_stored_style( $clone );
    update_option( 'qpp_style' . $id, $update );
    $update = qpp_get_stored_coupon( $clone );
    update_option( 'qpp_coupon' . $id, $update );
    $update = qpp_get_stored_error( $clone );
    update_option( 'qpp_error' . $id, $update );
    $update = qpp_get_stored_address( $clone );
    update_option( 'qpp_address' . $id, $update );
}

function qpp_form_options(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    qpp_change_form_update( $id );
    if ( isset( $_POST['qpp_submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'title',
            'blurb',
            'sort',
            'inputreference',
            'inputamount',
            'allow_amount',
            'combobox',
            'comboboxword',
            'comboboxlabel',
            'shortcodereference',
            'use_quantity',
            'quantitylabel',
            'use_stock',
            'ruse_stock',
            'fixedstock',
            'stocklabel',
            'use_cf',
            'ruse_cf',
            'cflabel',
            'use_consent',
            'ruse_consent',
            'consentlabel',
            'consentpaypal',
            'noconsentpaypal',
            'use_message',
            'ruse_message',
            'messagelabel',
            'use_options',
            'optionlabel',
            'optionvalues',
            'optionselector',
            'inline_options',
            'shortcodeamount',
            'shortcode_labels',
            'submitcaption',
            'cancelurl,',
            'thanksurl',
            'target',
            'paypal-url',
            'paypal-location',
            'postageblurb',
            'postagepercent',
            'postagefixed',
            'usepostage',
            'usecoupon',
            'couponblurb',
            'couponref',
            'couponbutton',
            'captcha',
            'mathscaption',
            'fixedreference',
            'fixedamount',
            'minamount',
            'useterms',
            'useblurb',
            'extrablurb',
            'userecurring',
            'recurringblurb',
            'recurring',
            'recurringhowmany',
            'Dperiod',
            'Wperiod',
            'Mperiod',
            'Yperiod',
            'srt',
            'payments',
            'every',
            'termsblurb',
            'termsurl',
            'termspage',
            'quantitymax',
            'quantitymaxblurb',
            'combine',
            'usetotals',
            'totalsblurb',
            'useaddress',
            'addressblurb',
            'use_datepicker',
            'ruse_datepicker',
            'use_multiples',
            'datepickerlabel',
            'currency_seperator',
            'selector',
            'refselector',
            'use_reset',
            'resetcaption',
            'use_slider',
            'sliderlabel',
            'min',
            'max',
            'initial',
            'step',
            'inline_amount',
            'useemail',
            'ruseemail',
            'emailblurb',
            'variablerecurring'
        );
        $args = array(
            'strong' => array(),
            'em'     => array(),
            'b'      => array(),
            'i'      => array(),
            'a'      => array(
                'href'   => array(),
                'target' => array(),
                'class'  => array(),
                'rel'    => array(),
                'title'  => array(),
            ),
        );
        /*
         * Seeded from what is stored, for two reasons. Settings this plan cannot
         * edit render no input, so they are absent from the post and would be
         * erased rather than coming back on upgrade. And this save rebuilds the
         * option from the list above, so anything the list does not name would be
         * dropped whether the plan could edit it or not.
         */
        $qpp = get_option( 'qpp_options' . $id );
        if ( !is_array( $qpp ) ) {
            $qpp = array();
        }
        $stored = $qpp;
        $preserved = qpp_plan_preserved_values( 'qpp_options', $id, qpp_plan_gated_options() );
        /*
         * A recurring form does not render the coupon checkbox at all, so it is
         * absent from the post for the same reason a gated setting is. Carry the
         * stored value forward rather than reading it as off.
         */
        if ( qpp_get_element( $stored, 'userecurring', false ) ) {
            $preserved['usecoupon'] = qpp_get_element( $stored, 'usecoupon', '' );
        }
        foreach ( $options as $item ) {
            if ( array_key_exists( $item, $preserved ) ) {
                $qpp[$item] = $preserved[$item];
                continue;
            }
            /*
             * Absent means off. Reading only the keys that were posted meant an
             * unticked checkbox kept its old value, so nothing on this screen
             * could be switched off once it had been switched on.
             */
            $qpp[$item] = ( isset( $_POST[$item] ) ? wp_kses_post( stripslashes( $_POST[$item] ) ) : '' );
        }
        if ( qpp_get_element( $qpp, 'userecurring', false ) ) {
            $qpp['use_quantity'] = '';
            $qpp['usepostage'] = '';
            $qpp['useprocess'] = '';
            $qpp['use_stock'] = '';
        }
        update_option( 'qpp_options' . $id, $qpp );
        qpp_admin_notice( esc_html__( 'The form and submission settings have been updated.', 'quick-paypal-payments' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_options' . $id );
        qpp_admin_notice( esc_html__( 'The form and submission settings have been reset.', 'quick-paypal-payments' ) );
    }
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $currency = qpp_get_stored_curr();
    $currency = qpp_ensure_currency( $currency, $id );
    $refnone = $refradio = $refdropdown = $ignore = $radio = $dropdown = $optionsradio = $optionsdropdown = false;
    $optionscheckbox = $processfixed = $postagefixed = $postagepercent = $comma = $M = $D = $W = $Y = $imagebelow = $imageabove = false;
    $qpp = qpp_get_stored_options( $id );
    ${$qpp['paypal-location']} = 'checked';
    ${$qpp['processtype']} = 'checked';
    ${$qpp['postagetype']} = 'checked';
    // ${$qpp['coupontype']} = 'checked';
    ${$qpp['recurring']} = 'checked';
    ${$qpp['currency_seperator']} = 'checked';
    ${$qpp['refselector']} = 'checked';
    ${$qpp['optionselector']} = 'checked';
    ${$qpp['selector']} = 'checked';
    $content = qpp_head_css();
    // Printed directly rather than appended to $content, which is filtered through wp_kses() and would strip a script tag.
    wp_print_inline_script_tag( 'jQuery(function() {var qpp_sort = jQuery( "#qpp_sort" ).sortable({ axis: "y" ,
    update:function(e,ui) {
    var order = qpp_sort.sortable("toArray").join();
    jQuery("#qpp_settings_sort").val(order);}
    });});' );
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    if ( $id ) {
        /* translators: %s: form name. */
        $content .= '<h2>' . sprintf( esc_html__( 'Form settings for %s', 'quick-paypal-payments' ), esc_html( $id ) ) . '</h2>';
    } else {
        $content .= '<h2>' . esc_html__( 'Default form settings', 'quick-paypal-payments' ) . '</h2>';
    }
    $content .= qpp_change_form( $qpp_setup );
    $content .= '<form action="" method="POST">
    <p>' . esc_html__( 'Paypal form heading (optional)', 'quick-paypal-payments' ) . '</p>
    <input type="text" style="width:100%" name="title" value="' . esc_attr( $qpp['title'] ) . '" />
    <p>' . esc_html__( 'This is the text that will appear below the heading and above the form (optional):', 'quick-paypal-payments' ) . '</p>
    <input type="text" style="width:100%" name="blurb" value="' . esc_attr( $qpp['blurb'] ) . '" />
    <h2>' . esc_html__( 'Form Fields', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Drag and drop to change order of the fields', 'quick-paypal-payments' ) . '</p>
    <div style="margin-left:7px;font-weight:bold;"><div style="float:left; width:30%;">' . esc_html__( 'Form Fields', 'quick-paypal-payments' ) . '</div><div style="float:left; width:30%;">' . esc_html__( 'Labels and Options', 'quick-paypal-payments' ) . '</div></div>
    <div style="clear:left"></div>
    <ul id="qpp_sort">';
    foreach ( explode( ',', $qpp['sort'] ) as $name ) {
        $check = $type = $input = $checked = $options = false;
        switch ( $name ) {
            case 'field1':
                $check = '&nbsp;';
                $type = esc_html__( 'Reference', 'quick-paypal-payments' );
                $input = 'inputreference';
                $checked = 'checked';
                $options = qpp_upgrade_prompt( 'gold', esc_html__( 'Pre-set reference options', 'quick-paypal-payments' ) );
                break;
            case 'field2':
                $check = '<input type="checkbox" name="use_stock" ' . checked( $qpp['use_stock'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Use Item Number', 'quick-paypal-payments' );
                $input = 'stocklabel';
                $checked = $qpp['use_stock'];
                $options = '<input type="checkbox" name="fixedstock" ' . checked( $qpp['fixedstock'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Display as a pre-set item number', 'quick-paypal-payments' ) . '<br>
<input type="checkbox" name="ruse_stock" ' . esc_attr( $qpp['ruse_stock'] ) . ' value="checked" /> ' . esc_html__( 'Required Field', 'quick-paypal-payments' );
                break;
            case 'field3':
                $check = ( $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox"   name="use_quantity" ' . esc_attr( $qpp['use_quantity'] ) . ' value="checked" />' );
                $type = esc_html__( 'Quantity', 'quick-paypal-payments' );
                $input = 'quantitylabel';
                $checked = $qpp['use_quantity'];
                $options = '<input type="checkbox" name="quantitymax" ' . checked( $qpp['quantitymax'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Display and validate a maximum quantity', 'quick-paypal-payments' ) . '<br><span class="description">' . esc_html__( 'Message that will display on the form. The limit is the number in it, so the message must contain one:', 'quick-paypal-payments' ) . '</span><br>
            <input type="text" name="quantitymaxblurb" value="' . esc_attr( $qpp['quantitymaxblurb'] ) . '" />';
                break;
            case 'field4':
                $check = '&nbsp;';
                $type = esc_html__( 'Amount', 'quick-paypal-payments' );
                $input = 'inputamount';
                $checked = 'checked';
                /*
                 * Setting one price is free. A form that cannot set a price is a
                 * donation box rather than a payment form, and nobody evaluating
                 * the free version concludes they should upgrade, they conclude
                 * it does not work. What stays paid is the powerful part: a list
                 * of prices, how it is rendered, and amount validation.
                 */
                $options = '<input type="checkbox" class="qpp_fixed_amount" name="fixedamount" ' . checked( $qpp['fixedamount'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Charge one set price', 'quick-paypal-payments' ) . '<br>
            <span class="description">' . sprintf( 
                    /* translators: %s: the heading "Fixed payment and shortcode labels", in bold. */
                    esc_html__( 'Set the price in %s further down this screen.', 'quick-paypal-payments' ),
                    '<strong>' . esc_html__( 'Fixed payment and shortcode labels', 'quick-paypal-payments' ) . '</strong>'
                 ) . '</span><br><br>';
                $options .= qpp_upgrade_prompt( 'gold', esc_html__( 'Offering a choice of prices, and amount validation', 'quick-paypal-payments' ) );
                break;
            case 'field5':
                $check = ( $qpp['userecurring'] ? '&nbsp;' : '<input type="checkbox"   name="use_options" ' . checked( $qpp['use_options'], 'checked', false ) . ' value="checked" />' );
                $type = esc_html__( 'Options', 'quick-paypal-payments' );
                $input = 'optionlabel';
                $checked = $qpp['use_options'];
                $options = '<span class="description">' . esc_html__( 'Options (separate with a comma):', 'quick-paypal-payments' ) . '</span><br><textarea  name="optionvalues" label="Radio" rows="2">' . $qpp['optionvalues'] . '</textarea><br>
            ' . esc_html__( 'Options Selector:', 'quick-paypal-payments' ) . ' <input type="radio" name="optionselector" value="optionsradio" ' . checked( $qpp['optionselector'], 'optionsradio', false ) . ' /> ' . esc_html__( 'Radio', 'quick-paypal-payments' ) . '&nbsp;
            <input type="radio" name="optionselector" value="optionscheckbox" ' . checked( $qpp['optionselector'], 'optionscheckbox', false ) . ' /> ' . esc_html__( 'Checkbox', 'quick-paypal-payments' ) . '&nbsp;
            <input type="radio" name="optionselector" value="optionsdropdown" ' . checked( $qpp['optionselector'], 'optionscheckbox', false ) . ' /> ' . esc_html__( 'Dropdown', 'quick-paypal-payments' ) . '<br>
            <input type="checkbox" name="inline_options" ' . checked( $qpp['inline_options'], 'checked', false ) . ' value="checked" />&nbsp;' . esc_html__( 'Display inline radio and checkbox fields', 'quick-paypal-payments' );
                break;
            case 'field6':
                $type = esc_html__( 'Handling', 'quick-paypal-payments' );
                $check = '&nbsp;';
                $input = '';
                $options = qpp_upgrade_prompt( 'silver', esc_html__( 'Postage and handling', 'quick-paypal-payments' ) );
                break;
            case 'field8':
                $check = '<input  type="checkbox"   name="captcha"' . checked( $qpp['captcha'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Maths Captcha', 'quick-paypal-payments' );
                $input = 'mathscaption';
                $checked = $qpp['captcha'];
                $options = '<span class="description">' . esc_html__( 'Add a maths checker to the form to (hopefully) block most of the spambots.', 'quick-paypal-payments' ) . '</span>';
                break;
            case 'field9':
                $type = esc_html__( 'Coupon Code', 'quick-paypal-payments' );
                $check = '&nbsp;';
                $input = '';
                $options = qpp_upgrade_prompt( 'gold', esc_html__( 'Coupon codes', 'quick-paypal-payments' ) );
                break;
            case 'field10':
                $check = '<input  type="checkbox" name="useterms"' . checked( $qpp['useterms'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Terms and Conditions', 'quick-paypal-payments' );
                $input = 'termsblurb';
                $checked = $qpp['termsblurb'];
                $options = '<span class="description">' . esc_html__( 'URL of Terms and Conditions:', 'quick-paypal-payments' ) . '</span><br>
            <input type="text" name="termsurl" value="' . esc_attr( $qpp['termsurl'] ) . '" /><br>
            <input  type="checkbox" name="termspage"' . checked( $qpp['termspage'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Open link in a new page', 'quick-paypal-payments' );
                break;
            case 'field11':
                $check = '<input  type="checkbox" name="useblurb"' . checked( $qpp['useblurb'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Additional Information', 'quick-paypal-payments' );
                $input = 'extrablurb';
                $checked = $qpp['useblurb'];
                $options = '<span class="description">' . esc_html__( 'Add additional information to your form', 'quick-paypal-payments' ) . '</span>';
                break;
            case 'field12':
                $type = esc_html__( 'Recurring Payments', 'quick-paypal-payments' );
                $check = '&nbsp;';
                $input = '';
                $options = qpp_upgrade_prompt( 'gold', esc_html__( 'Recurring payments', 'quick-paypal-payments' ) );
                break;
                $check = '<input  type="checkbox" name="userecurring"' . checked( $qpp['userecurring'], 'checked', false ) . ' value="checked" />';
                $input = 'recurringblurb';
                $checked = $qpp['userecurring'];
                $options = '<p>' . esc_html__( 'Number of payments:', 'quick-paypal-payments' ) . ' <input type="text" style="width:2em;padding:2px" name="recurringhowmany" value="' . esc_attr( $qpp['recurringhowmany'] ) . '" /> ' . esc_html__( '(max 52 , min 2)', 'quick-paypal-payments' ) . '<br>
            ' . esc_html__( 'Message:', 'quick-paypal-payments' ) . ' <input type="text" style="width:10em;padding:2px" name="every" value="' . esc_attr( $qpp['every'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="D"' . esc_attr( $D ) . ' /> 
            <input type="text" style="width:6em;padding:2px" name="Dperiod" value="' . esc_attr( $qpp['Dperiod'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="W"' . esc_attr( $W ) . ' />
             <input type="text" style="width:6em;padding:2px" name="Wperiod" value="' . esc_attr( $qpp['Wperiod'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="M"' . esc_attr( $M ) . ' /> 
            <input type="text" style="width:6em;padding:2px" name="Mperiod" value="' . esc_attr( $qpp['Mperiod'] ) . '" /></p>
            <p><input type="radio" name="recurring" value="Y"' . esc_attr( $Y ) . ' /> 
            <input type="text" style="width:6em;padding:2px" name="Yperiod" value="' . esc_attr( $qpp['Yperiod'] ) . '" /></p>
            <p><input  type="checkbox" name="variablerecurring"' . checked( $qpp['variablerecurring'], 'checked', false ) . ' value="checked" />&nbsp;' . esc_html__( 'Allow users to change number of payments.', 'quick-paypal-payments' ) . '</p>
            <p><span style="color:red">' . esc_html__( 'WARNING!', 'quick-paypal-payments' ) . '</span> ' . esc_html__( 'Recurring payments only work if you have a Business or Premier account.', 'quick-paypal-payments' ) . '<br>' . esc_html__( 'Using recurring payments will disable some form fields.', 'quick-paypal-payments' ) . '</p>';
                break;
            case 'field13':
                $check = '<input  type="checkbox" name="useaddress"' . checked( $qpp['useaddress'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Personal Details', 'quick-paypal-payments' );
                $input = 'addressblurb';
                $checked = $qpp['useaddress'];
                $options = '<p><a href="?page=quick-paypal-payments&tab=address">' . esc_html__( 'Personal details Settings', 'quick-paypal-payments' ) . '</a></p>';
                break;
            case 'field14':
                $check = '<input  type="checkbox" name="usetotals"' . checked( $qpp['usetotals'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Show totals', 'quick-paypal-payments' );
                $input = 'totalsblurb';
                $checked = $qpp['usetotals'];
                $options = '<span class="description">' . esc_html__( 'Show live totals on your form. Warning: Only works if you have one form of a type on the page', 'quick-paypal-payments' ) . '</span>';
                break;
            case 'field15':
                $type = esc_html__( 'Range slider', 'quick-paypal-payments' );
                $check = '&nbsp;';
                $input = '';
                $options = qpp_upgrade_prompt( 'gold', esc_html__( 'The range slider', 'quick-paypal-payments' ) );
                break;
            case 'field16':
                $check = '<input  type="checkbox" name="useemail"' . checked( $qpp['useemail'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Email Address', 'quick-paypal-payments' );
                $input = 'emailblurb';
                $checked = $qpp['useemail'];
                $options = '<span class="description">' . esc_html__( 'Use this to collect the Payers email address.', 'quick-paypal-payments' ) . '</span><br>
            <input  type="checkbox" name="ruseemail"' . checked( $qpp['ruseemail'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Required Field', 'quick-paypal-payments' );
                break;
            case 'field17':
                $check = '<input  type="checkbox" name="use_message"' . checked( $qpp['use_message'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Add textbox for comments', 'quick-paypal-payments' );
                $input = 'messagelabel';
                $checked = $qpp['use_message'];
                $options = '<input  type="checkbox" name="ruse_message"' . checked( $qpp['ruse_message'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Required Field', 'quick-paypal-payments' );
                break;
            case 'field18':
                $check = '&nbsp;';
                $type = esc_html__( 'Add datepicker', 'quick-paypal-payments' );
                $input = '';
                $options = qpp_upgrade_prompt( 'platinum', esc_html__( 'The datepicker', 'quick-paypal-payments' ) );
                break;
            case 'field19':
                $check = '&nbsp;';
                $type = esc_html__( 'Multiple Products', 'quick-paypal-payments' );
                $input = '';
                $options = qpp_upgrade_prompt( 'platinum', esc_html__( 'Multiple products', 'quick-paypal-payments' ) );
                break;
            case 'field21':
                $check = '<input  type="checkbox" name="use_cf"' . checked( $qpp['use_cf'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Codice Fiscale (Solo Italia)', 'quick-paypal-payments' );
                $input = 'cflabel';
                $checked = $qpp['use_cf'];
                // Reads its own option, as every sibling Required Field checkbox does.
                // This read $qpp['field22'], so the box never showed as ticked once saved.
                $options = '<input  type="checkbox" name="ruse_cf"' . checked( $qpp['ruse_cf'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Required Field', 'quick-paypal-payments' );
                break;
            case 'field22':
                $check = '<input  type="checkbox" name="use_consent"' . checked( $qpp['use_consent'], 'checked', false ) . ' value="checked" />';
                $type = esc_html__( 'Consent', 'quick-paypal-payments' );
                $input = 'consentlabel';
                $checked = $qpp['use_consent'];
                $options = '<span class="description">' . esc_html__( 'Add a checkbox to permit data storage. You may use html links above.', 'quick-paypal-payments' ) . '</span><br>' . '<span class="description">' . esc_html__( 'Add consent info to PayPal item line. Blank out if not required.', 'quick-paypal-payments' ) . '</span><br>' . '<input type="text" name="consentpaypal" value="' . esc_attr( $qpp['consentpaypal'] ) . '" />' . esc_html__( 'if consent given', 'quick-paypal-payments' ) . '<br>' . '<input type="text" name="noconsentpaypal" value="' . esc_attr( $qpp['noconsentpaypal'] ) . '" />' . esc_html__( 'if consent NOT given', 'quick-paypal-payments' ) . '<br>';
                $options .= '<input  type="checkbox" name="ruse_consent"' . checked( $qpp['ruse_consent'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Required Field', 'quick-paypal-payments' );
                break;
        }
        $li_class = ( $checked ? 'button_active' : 'button_inactive' );
        if ( $check ) {
            $content .= '<li class="' . esc_attr( $li_class ) . '" id="' . esc_attr( $name ) . '">
            <div style="float:left; width:5%;">' . $check . '</div>
            <div style="float:left; width:25%;">' . $type . '</div>
            <div style="float:left; width:65%;">';
            if ( $input ) {
                $content .= '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $input ) . '" value="' . esc_attr( esc_attr( $qpp[$input] ) ) . '" />';
            }
            if ( $options ) {
                $content .= $options;
            }
            $content .= '</div>
            <div style="clear:left"></div></li>';
        }
    }
    $content .= '</ul>
    <h2>' . esc_html__( 'Fixed payment and shortcode labels', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'These are the labels that will display if you are using a fixed reference or amount or shortcode attributes.', 'quick-paypal-payments' ) . ' ' . sprintf( 
        /* translators: %s: a link with the text "on this page". */
        esc_html__( 'All the shortcodes are given %s.', 'quick-paypal-payments' ),
        '<a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">' . esc_html__( 'on this page', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <p>' . esc_html__( 'Label for the payment Reference/ID/Number:', 'quick-paypal-payments' ) . '</p>
    <input type="text" name="shortcodereference" value="' . esc_attr( $qpp['shortcodereference'] ) . '" />
    <p>' . esc_html__( 'Label for the amount field:', 'quick-paypal-payments' ) . '</p>
    <input type="text" name="shortcodeamount" value="' . esc_attr( $qpp['shortcodeamount'] ) . '" />
    <h2>' . esc_html__( 'Submit button caption', 'quick-paypal-payments' ) . '</h2>
    <input type="text" name="submitcaption" value="' . esc_attr( $qpp['submitcaption'] ) . '" />
    <h2>' . esc_html__( 'Reset button', 'quick-paypal-payments' ) . '</h2>
    <p><input  type="checkbox" name="use_reset"' . checked( $qpp['use_reset'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Show Reset Button', 'quick-paypal-payments' ) . '</p>
    <input type="text" name="resetcaption" value="' . esc_attr( $qpp['resetcaption'] ) . '" />
    <h2>' . esc_html__( 'PayPal Image', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Upload an image and select where you want it to display (Leave blank if you don\'t want to use an image).', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'Below form title:', 'quick-paypal-payments' ) . ' <input type="radio" label="paypal-location" name="paypal-location" value="imageabove"' . esc_attr( $imageabove ) . ' /> ' . esc_html__( 'Below Submit Button:', 'quick-paypal-payments' ) . '&nbsp;
    <input type="radio" label="paypal-location" name="paypal-location" value="imagebelow"' . esc_attr( $imagebelow ) . ' /></p>
    <p>
    <input id="qpp_upload_image" type="text" name="paypal-url" value="' . esc_attr( $qpp['paypal-url'] ) . '" />
    <input class="button qpp-media-pick" type="button" data-target="#qpp_upload_image" data-title="' . esc_attr__( 'Image above or below the form', 'quick-paypal-payments' ) . '" value="' . esc_attr__( 'Choose image', 'quick-paypal-payments' ) . '" />
    </p>
    <p><input type="submit" name="qpp_submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the form settings?', 'quick-paypal-payments' ) ) . '\' );"/></p>
    <input type="hidden" id="qpp_settings_sort" name="sort" value="' . esc_attr( $qpp['sort'] ) . '" />';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>' . esc_html__( 'Form Preview', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.', 'quick-paypal-payments' ) . '</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args, true );
    $content .= qpp_examples_footer() . '</div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_styles(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    qpp_change_form_update();
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'font',
            'font-family',
            'font-size',
            'font-colour',
            'text-font-family',
            'text-font-size',
            'text-font-colour',
            'form-border',
            'input-border',
            'required-border',
            'error-colour',
            'border',
            'width',
            'widthtype',
            'background',
            'backgroundhex',
            'backgroundimage',
            'corners',
            'custom',
            'use_custom',
            'usetheme',
            'styles',
            'submit-colour',
            'submit-background',
            'submit-hover-background',
            'submit-button',
            'submit-border',
            'submitwidth',
            'submitwidthset',
            'submitposition',
            'coupon-colour',
            'coupon-background',
            'header-type',
            'header-size',
            'header-colour',
            'slider-background',
            'slider-revealed',
            'handle-background',
            'handle-border',
            'output-size',
            'output-colour',
            'slider-thickness',
            'handle-corners',
            'line_margin',
            'labeltype'
        );
        $styles = array();
        foreach ( $options as $item ) {
            if ( isset( $_POST[$item] ) ) {
                $styles[$item] = stripslashes( $_POST[$item] );
                $styles[$item] = sanitize_text_field( $styles[$item] );
            }
        }
        update_option( 'qpp_style' . $id, $styles );
        qpp_admin_notice( esc_html__( 'The form styles have been updated.', 'quick-paypal-payments' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_style' . $id );
        qpp_admin_notice( esc_html__( 'The form styles have been reset.', 'quick-paypal-payments' ) );
    }
    $font = $h2 = $h3 = $h4 = $percent = $pixel = $none = $plain = $shadow = $roundshadow = false;
    $corner = $square = $round = $rounded = $color = $white = $square = $theme = $submitrandom = false;
    $submitmiddle = $submitpixel = $submitleft = $submitmiddle = $submitright = false;
    $submitpercent = $submitrandom = $submitpixel = false;
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $style = qpp_get_stored_style( $id );
    ${$style['widthtype']} = 'checked';
    ${$style['submitwidth']} = 'checked';
    ${$style['submitposition']} = 'checked';
    ${$style['border']} = 'checked';
    ${$style['background']} = 'checked';
    ${$style['corners']} = 'checked';
    ${$style['styles']} = 'checked';
    ${$style['header-type']} = 'checked';
    $tiny = $none = $hiding = $plain = "";
    switch ( $style['labeltype'] ) {
        case 'none':
            $none = " checked='checked' ";
            break;
        case 'tiny':
            $tiny = " checked='checked' ";
            break;
        case 'plain':
            $plain = " checked='checked' ";
            break;
        case 'hiding':
            $hiding = " checked='checked' ";
            break;
    }
    $content = qpp_head_css();
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    if ( $id ) {
        /* translators: %s: form name. */
        $content .= '<h2>' . sprintf( esc_html__( 'Style options for %s', 'quick-paypal-payments' ), esc_html( $id ) ) . '</h2>';
    } else {
        $content .= '<h2>' . esc_html__( 'Default form style options', 'quick-paypal-payments' ) . '</h2>';
    }
    $content .= qpp_change_form( $qpp_setup );
    $qpp = qpp_get_stored_options( $id );
    $content .= '
    <form method="post" action=""> 
    <p class="description"><strong>' . esc_html__( 'Note:', 'quick-paypal-payments' ) . '</strong> ' . esc_html__( 'Leave fields blank if you don\'t want to use them', 'quick-paypal-payments' ) . '</p>
    <table>
    <tr>
    <td colspan="2"><h2>' . esc_html__( 'Form Width', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td width="30%"></td>
    <td><input type="radio" name="widthtype" value="percent"' . esc_attr( $percent ) . ' /> ' . esc_html__( '100% (fill the available space)', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="widthtype" value="pixel"' . esc_attr( $pixel ) . ' /> ' . esc_html__( 'Pixel (fixed):', 'quick-paypal-payments' ) . ' 
    <input type="text" style="width:4em" label="width" name="width" value="' . esc_attr( $style['width'] ) . '" /> ' . esc_html__( 'use px, em or %. Default is px.', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td colspan="2"><h2>' . esc_html__( 'Form Border', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Type:', 'quick-paypal-payments' ) . '</td>
    <td><input type="radio" name="border" value="none"' . esc_attr( $none ) . ' /> ' . esc_html__( 'No border', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="border" value="plain"' . esc_attr( $plain ) . ' /> ' . esc_html__( 'Plain Border', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="border" value="rounded"' . esc_attr( $rounded ) . ' /> ' . esc_html__( 'Round Corners', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="border" value="shadow"' . esc_attr( $shadow ) . ' /> ' . esc_html__( 'Shadowed Border', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="border" value="roundshadow"' . esc_attr( $roundshadow ) . ' /> ' . esc_html__( 'Rounded Shadowed Border', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Style:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" label="form-border" name="form-border" value="' . esc_attr( $style['form-border'] ) . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>' . esc_html__( 'Background', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Colour:', 'quick-paypal-payments' ) . '</td>
    <td><input type="radio" name="background" value="white"' . esc_attr( $white ) . ' /> ' . esc_html__( 'White', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="background" value="theme"' . esc_attr( $theme ) . ' /> ' . esc_html__( 'Use theme colours', 'quick-paypal-payments' ) . '<br />
    <input style="margin-bottom:5px;" type="radio" name="background" value="color"' . esc_attr( $color ) . ' />
    <input type="text" class="qpp-color" label="background" name="backgroundhex" value="' . esc_attr( $style['backgroundhex'] ) . '" /></td>
    </tr>
    <tr><td>' . esc_html__( 'Background', 'quick-paypal-payments' ) . '<br>' . esc_html__( 'Image:', 'quick-paypal-payments' ) . '</td>
    <td>
    <input id="qpp_background_image" type="text" name="backgroundimage" value="' . esc_attr( $style['backgroundimage'] ) . '" />
    <input class="button qpp-media-pick" type="button" data-target="#qpp_background_image" data-title="' . esc_attr__( 'Background image', 'quick-paypal-payments' ) . '" value="' . esc_attr__( 'Choose image', 'quick-paypal-payments' ) . '" /></td>
    </tr>
    <tr><td colspan="2"><h2>' . esc_html__( 'Font Styles', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td></td>
    <td><input  type="radio" name="font" value="theme"' . esc_attr( $theme ) . ' /> ' . esc_html__( 'Use theme font styles', 'quick-paypal-payments' ) . '<br />
    <input  type="radio" name="font" value="plugin"' . esc_attr( $plugin ) . ' /> ' . esc_html__( 'Use Plugin font styles (enter font family and size below)', 'quick-paypal-payments' ) . '
    </td>
    </tr>
    <tr>
    <td colspan="2"><h2>' . esc_html__( 'Form Header', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td style="vertical-align:top;">' . esc_html__( 'Header', 'quick-paypal-payments' ) . '</td>
    <td><input type="radio" name="header-type" value="h2"' . esc_attr( $h2 ) . ' /> H2 
    <input type="radio" name="header-type" value="h3"' . esc_attr( $h3 ) . ' /> H3 
    <input type="radio" name="header-type" value="h4"' . esc_attr( $h4 ) . ' /> H4</td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Header Size:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:6em" label="header-size" name="header-size" value="' . esc_attr( $style['header-size'] ) . '" /></td>
    </tr>
    <tr><td>' . esc_html__( 'Header Colour:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="header-colour" name="header-colour" value="' . esc_attr( $style['header-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>' . esc_html__( 'Field Label Locations', 'quick-paypal-payments' ) . '</h2></td>
    <tr>
    <td colspan="2"><input type="radio" name="labeltype" value="tiny"' . esc_attr( $tiny ) . ' />
     ' . esc_html__( 'Reduce in size on focus', 'quick-paypal-payments' ) . '&nbsp;&nbsp;&nbsp;
     <input type="radio" name="labeltype" value="hiding"' . esc_attr( $hiding ) . ' /> ' . esc_html__( 'Placeholders', 'quick-paypal-payments' ) . '&nbsp;&nbsp;&nbsp;
     <input type="radio" name="labeltype" value="plain"' . esc_attr( $plain ) . ' /> ' . esc_html__( 'Above Input Fields', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td colspan="2"><h2>' . esc_html__( 'Input fields', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Family:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" label="font-family" name="font-family" value="' . esc_attr( $style['font-family'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Size:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" label="font-size" name="font-size" value="' . esc_attr( $style['font-size'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Colour:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" class="qpp-color" label="font-colour" name="font-colour" value="' . esc_attr( $style['font-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Normal Border:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" label="input-border" name="input-border" value="' . esc_attr( $style['input-border'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Required Border:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" name="required-border" value="' . esc_attr( $style['required-border'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Error Colour:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" class="qpp-color" name="error-colour" value="' . esc_attr( $style['error-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Corners:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="radio" name="corners" value="corner"' . esc_attr( $corner ) . ' /> ' . esc_html__( 'Use theme settings', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="corners" value="square"' . esc_attr( $square ) . ' /> ' . esc_html__( 'Square corners', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="corners" value="round"' . esc_attr( $round ) . ' /> ' . esc_html__( '5px rounded corners', 'quick-paypal-payments' ) . '</td></tr>
    <tr>
    <td style="vertical-align:top;">' . esc_html__( 'Margins and Padding', 'quick-paypal-payments' ) . '</td>
    <td><span class="description">' . esc_html__( 'Set the margins and padding of each bit using CSS shortcodes', 'quick-paypal-payments' ) . ':</span><br>
    <input type="text" label="line margin" name="line_margin" value="' . esc_attr( $style['line_margin'] ) . '" /></td>
    </tr>
    <tr>';
    if ( $qpp['usecoupon'] ) {
        $content .= '<td colspan="2"><h2>' . esc_html__( 'Apply Coupon Button', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Colour:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" class="qpp-color" label="coupon-colour" name="coupon-colour" value="' . esc_attr( $style['coupon-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Background:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" class="qpp-color" label="coupon-background" name="coupon-background" value="' . esc_attr( $style['coupon-background'] ) . '" /><br>' . esc_html__( 'Other settings are the same as the Submit Button', 'quick-paypal-payments' ) . '</td>
    </tr>';
    }
    $content .= '<tr>
    <td colspan="2"><h2>' . esc_html__( 'Other text content', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Family:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" label="text-font-family" name="text-font-family" value="' . esc_attr( $style['text-font-family'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Size:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" style="width:6em" label="text-font-size" name="text-font-size" value="' . esc_attr( $style['text-font-size'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Colour:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" class="qpp-color" label="text-font-colour" name="text-font-colour" value="' . esc_attr( $style['text-font-colour'] ) . '" /></td>
    </tr>
    <tr>
    <td colspan="2"><h2>' . esc_html__( 'Submit Button', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Font Colour:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="submit-colour" name="submit-colour" value="' . esc_attr( $style['submit-colour'] ) . '" /></td></tr>
    <tr>
    <td>' . esc_html__( 'Background:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="submit-background" name="submit-background" value="' . esc_attr( $style['submit-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Hover:', 'quick-paypal-payments' ) . ' </td>
    <td><input type="text" class="qcf-color" label="submit-hover-background" name="submit-hover-background" value="' . esc_attr( $style['submit-hover-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Border:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" label="submit-border" name="submit-border" value="' . esc_attr( $style['submit-border'] ) . '" /></td></tr>
    <tr>
    <td>' . esc_html__( 'Size:', 'quick-paypal-payments' ) . '</td>
    <td><input type="radio" name="submitwidth" value="submitpercent"' . esc_attr( $submitpercent ) . ' /> ' . esc_html__( 'Same width as the form', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="submitwidth" value="submitrandom"' . esc_attr( $submitrandom ) . ' /> ' . esc_html__( 'Same width as the button text', 'quick-paypal-payments' ) . '<br />
    <input type="radio" name="submitwidth" value="submitpixel"' . esc_attr( $submitpixel ) . ' /> ' . esc_html__( 'Set your own width:', 'quick-paypal-payments' ) . ' 
    <input type="text" style="width:5em" label="submitwidthset" name="submitwidthset" value="' . esc_attr( $style['submitwidthset'] ) . '" /> ' . esc_html__( '(px, % or em)', 'quick-paypal-payments' ) . '</td></tr>
    <tr>
    <td>' . esc_html__( 'Position:', 'quick-paypal-payments' ) . '</td>
    <td><input type="radio" name="submitposition" value="submitleft"' . esc_attr( $submitleft ) . ' /> ' . esc_html__( 'Left', 'quick-paypal-payments' ) . ' 
    <input type="radio" name="submitposition" value="submitmiddle"' . esc_attr( $submitmiddle ) . ' /> ' . esc_html__( 'Centre', 'quick-paypal-payments' ) . ' 
    <input type="radio" name="submitposition" value="submitright"' . esc_attr( $submitright ) . ' /> ' . esc_html__( 'Right', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td style="vertical-align:top;">' . esc_html__( 'Button Image:', 'quick-paypal-payments' ) . ' </td><td>';
    /*
     * The buttons that ship with the plugin. They were sitting in the images
     * folder with no way to reach them, so the only route to a button image was
     * to find one elsewhere and upload it.
     *
     * The Stripe ones are always shown, because a merchant cannot decide they
     * want card payments if nothing tells them card payments exist. They are
     * only selectable once Stripe is actually switched on: a button reading
     * "Pay by Card" on a form that sends the customer to PayPal is a lie about
     * where their money is going.
     */
    $current = qpp_resolve_button_url( $style['submit-button'] );
    $stripe = qpp_get_stored_stripe();
    // enable is blanked at the read path below Platinum, so this is false on
    // every plan without Stripe as well as when it is simply switched off.
    $stripe_live = 'checked' === $stripe['enable'] && '' !== $stripe['secret_key'];
    $choice = function ( $url, $label, $enabled ) use($current) {
        $classes = 'qpp-button-choice' . (( $current === $url ? ' is-selected' : '' )) . (( $enabled ? '' : ' is-disabled' ));
        return '<button type="button" class="' . esc_attr( $classes ) . '"' . (( $enabled ? ' data-target="#qpp_submit_button" data-value="' . esc_url( $url ) . '"' : ' disabled="disabled"' )) . '><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $label ) . '" /></button>';
    };
    $content .= '<p class="qpp-button-choices">';
    $content .= '<button type="button" class="qpp-button-choice' . (( '' === $style['submit-button'] ? ' is-selected' : '' )) . '" data-target="#qpp_submit_button" data-value="">' . esc_html__( 'No image', 'quick-paypal-payments' ) . '</button>';
    foreach ( qpp_bundled_buttons_for( 'paypal' ) as $file => $button ) {
        $content .= $choice( qpp_bundled_button_url( $file ), $button['label'], true );
    }
    $content .= '</p>';
    $content .= '<p class="qpp-button-choices">';
    foreach ( qpp_bundled_buttons_for( 'stripe' ) as $file => $button ) {
        $content .= $choice( qpp_bundled_button_url( $file ), $button['label'], $stripe_live );
    }
    $content .= '</p>';
    if ( !$stripe_live ) {
        $content .= '<p class="description qpp-button-locked">';
        $content .= qpp_upgrade_prompt( 'platinum', esc_html__( 'Taking card payments through Stripe', 'quick-paypal-payments' ) );
        $content .= '</p>';
    }
    $content .= '<p><input id="qpp_submit_button" class="qpp-media-url" type="text" name="submit-button" value="' . esc_attr( $style['submit-button'] ) . '" />
    <input class="button-secondary qpp-media-pick" type="button" data-target="#qpp_submit_button" data-title="' . esc_attr__( 'Submit button image', 'quick-paypal-payments' ) . '" value="' . esc_attr__( 'Choose image', 'quick-paypal-payments' ) . '" /></p>
    <p class="description">' . esc_html__( 'Leave blank for a text button styled with the settings above.', 'quick-paypal-payments' ) . '</p>
    </td></tr>';
    if ( $qpp['use_slider'] ) {
        $content .= '<tr>
    <td colspan="2"><h2>' . esc_html__( 'Slider', 'quick-paypal-payments' ) . '</h2></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Thickness', 'quick-paypal-payments' ) . '</td>
    <td><input type="number" step = "0.125"  min="0.25" style="width:7em" label="input-border" name="slider-thickness" value="' . esc_attr( $style['slider-thickness'] ) . '" />em</td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Normal Background', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="input-border" name="slider-background" value="' . esc_attr( $style['slider-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Revealed Background', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="input-border" name="slider-revealed" value="' . esc_attr( $style['slider-revealed'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Handle Background', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="input-border" name="handle-background" value="' . esc_attr( $style['handle-background'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Handle Border', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="input-border" name="handle-border" value="' . esc_attr( $style['handle-border'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Corners', 'quick-paypal-payments' ) . '</td>
    <td><input type="number"  style="width:4em" name="handle-corners" value="' . esc_attr( $style['handle-corners'] ) . '" />&nbsp;%</td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Output Size', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:5em" label="input-border" name="output-size" value="' . esc_attr( $style['output-size'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Output Colour', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" class="qpp-color" label="input-border" name="output-colour" value="' . esc_attr( $style['output-colour'] ) . '" /></td>
    </tr>';
    }
    $content .= '</table>

    <h2>' . esc_html__( 'Custom CSS', 'quick-paypal-payments' ) . '</h2>
    <p><input  type="checkbox" style="margin:0; padding: 0; border: none" name="use_custom"' . checked( $style['use_custom'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Use Custom CSS', 'quick-paypal-payments' ) . '</p>
    <p><textarea style="width:100%; height: 200px" name="custom">' . $style['custom'] . '</textarea></p>
    <p>' . sprintf( 
        /* translators: %s: the CSS id .qpp-style, in a code tag. */
        esc_html__( 'The main style wrapper is the %s id.', 'quick-paypal-payments' ),
        '<code>.qpp-style</code>'
     ) . '</p>
    <p>' . esc_html__( 'The form borders are: #none, #plain, #rounded, #shadow, #roundshadow.', 'quick-paypal-payments' ) . '</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the form styles?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;"> <h2>' . esc_html__( 'Test Form', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Not all of your style selections will display here (because of how WordPress works). So check the form on your site.', 'quick-paypal-payments' ) . '</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args, true );
    $content .= qpp_examples_footer() . '</div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

/**
 * The two documentation links under every form preview.
 *
 * @return string
 */
function qpp_examples_footer() {
    return '<p>' . sprintf( 
        /* translators: %s: a link with the text "on this page". */
        esc_html__( 'There are some more examples of payment forms %s.', 'quick-paypal-payments' ),
        '<a href="https://fullworks.net/docs/quick-paypal-payments/demos-quick-paypal-payments/" target="_blank">' . esc_html__( 'on this page', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <p>' . sprintf( 
        /* translators: %s: a link with the text "on this page". */
        esc_html__( 'And there are loads of shortcode options %s.', 'quick-paypal-payments' ),
        '<a href="https://fullworks.net/docs/quick-paypal-payments/usage-quick-paypal-payments/shortcode-reference/" target="_blank">' . esc_html__( 'on this page', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>';
}

function qpp_send_page(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    $AU = $AT = $BE = $BR = $pt_BR = $CA = $CH = $CN = $da_DK = $FR = $DE = $he_IL = $id_ID = $IT = $ja_JP = $NL = $no_NO = false;
    $PL = $PT = $RU = $ru_RU = $zh_CN = $zh_HK = $zh_TW = $ES = $sv_SE = $th_TH = $tr_TR = $GB = $US = false;
    qpp_change_form_update();
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'waiting',
            'use_lc',
            'lc',
            'image_url',
            'customurl',
            'cancelurl',
            'thanksurl',
            'target',
            'email',
            'donate',
            'combine',
            'confirmmessage',
            'google_onclick',
            'confirmemail'
        );
        /*
         * Seeded from what is stored, not empty. This save rebuilds the option
         * from the list above, so any key the list does not name is dropped. That
         * silently deleted createuser when it moved to the Integrations tab, and
         * would do the same to the next setting that moves.
         */
        $send = get_option( 'qpp_send' . $id );
        if ( !is_array( $send ) ) {
            $send = array();
        }
        $preserved = qpp_plan_preserved_values( 'qpp_send', $id, qpp_plan_gated_send() );
        foreach ( $options as $item ) {
            // A gated setting renders no input, so it is absent from the post for the
            // same reason an unticked box is. Carry the stored value forward instead
            // of reading it as off, which would erase it.
            if ( array_key_exists( $item, $preserved ) ) {
                $send[$item] = $preserved[$item];
                continue;
            }
            // An unticked checkbox is absent from the post, which means off.
            $send[$item] = ( isset( $_POST[$item] ) ? stripslashes( $_POST[$item] ) : '' );
            $send[$item] = sanitize_text_field( $send[$item] );
        }
        update_option( 'qpp_send' . $id, $send );
        qpp_admin_notice( esc_html__( 'The submission settings have been updated.', 'quick-paypal-payments' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_send' . $id );
        qpp_admin_notice( esc_html__( 'The submission settings have been reset.', 'quick-paypal-payments' ) );
    }
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $send = qpp_get_stored_send( $id );
    $newpage = $customurl = '';
    $list = qpp_get_stored_mailinglist();
    $current = $newpage = '';
    if ( isset( $send['target'] ) ) {
        ${$send['target']} = 'checked';
    }
    if ( isset( $send['lc'] ) ) {
        ${$send['lc']} = 'selected';
    }
    if ( empty( qpp_get_element( $send, 'confirmemail' ) ) ) {
        $send['confirmemail'] = get_bloginfo( 'admin_email' );
    }
    $content = qpp_head_css();
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    if ( $id ) {
        /* translators: %s: form name. */
        $content .= '<h2>' . sprintf( esc_html__( 'Send settings for %s', 'quick-paypal-payments' ), esc_html( $id ) ) . '</h2>';
    } else {
        $content .= '<h2>' . esc_html__( 'Default form send options', 'quick-paypal-payments' ) . '</h2>';
    }
    $content .= qpp_change_form( $qpp_setup );
    $content .= '
    <form action="" method="POST">
    
    <h2>' . esc_html__( 'Submission Message', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'This is what the visitor sees while the paypal page loads', 'quick-paypal-payments' ) . '</p>
    <input type="text" style="width:100%" name="waiting" value="' . esc_attr( $send['waiting'] ) . '" />
    
    <h2>' . esc_html__( 'Force Locale', 'quick-paypal-payments' ) . '</h2>
    <p class="description">' . esc_html__( 'This may or may not work, Paypal has some very strange rule regarding language', 'quick-paypal-payments' ) . '</p>
    <p><input  type="checkbox" name="use_lc"' . checked( $send['use_lc'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Use Locale', 'quick-paypal-payments' ) . '</p>
    <select name="lc">
    <option value="AU" ' . $AU . '>' . esc_html__( 'Australia', 'quick-paypal-payments' ) . '</option>
    <option value="AT" ' . $AT . '>' . esc_html__( 'Austria', 'quick-paypal-payments' ) . '</option>
    <option value="BE" ' . $BE . '>' . esc_html__( 'Belgium', 'quick-paypal-payments' ) . '</option>
    <option value="BR" ' . $BR . '>' . esc_html__( 'Brazil', 'quick-paypal-payments' ) . '</option>
    <option value="pt_BR" ' . $pt_BR . '>' . esc_html__( 'Brazilian Portuguese (for Portugal and Brazil only)', 'quick-paypal-payments' ) . '</option>
    <option value="CA" ' . $CA . '>' . esc_html__( 'Canada', 'quick-paypal-payments' ) . '</option>
    <option value="CH" ' . $CH . '>' . esc_html__( 'Switzerland', 'quick-paypal-payments' ) . '</option>
    <option value="CN" ' . $CN . '>' . esc_html__( 'China', 'quick-paypal-payments' ) . '</option>
    <option value="da_DK" ' . $da_DK . '>' . esc_html__( 'Danish (for Denmark only)', 'quick-paypal-payments' ) . '</option>
    <option value="FR" ' . $FR . '>' . esc_html__( 'France', 'quick-paypal-payments' ) . '</option>
    <option value="DE" ' . $DE . '>' . esc_html__( 'Germany', 'quick-paypal-payments' ) . '</option>
    <option value="he_IL" ' . $he_IL . '>' . esc_html__( 'Hebrew (all)', 'quick-paypal-payments' ) . '</option>
    <option value="id_ID" ' . $id_ID . '>' . esc_html__( 'Indonesian (for Indonesia only)', 'quick-paypal-payments' ) . '</option>
    <option value="IT" ' . $IT . '>' . esc_html__( 'Italy', 'quick-paypal-payments' ) . '</option>
    <option value="ja_JP" ' . $ja_JP . '>' . esc_html__( 'Japanese (for Japan only)', 'quick-paypal-payments' ) . '</option>
    <option value="NL" ' . $NL . '>' . esc_html__( 'Netherlands', 'quick-paypal-payments' ) . '</option>
    <option value="no_NO" ' . $no_NO . '>' . esc_html__( 'Norwegian (for Norway only)', 'quick-paypal-payments' ) . '</option>
    <option value="PL" ' . $PL . '>' . esc_html__( 'Poland', 'quick-paypal-payments' ) . '</option>
    <option value="PT" ' . $PT . '>' . esc_html__( 'Portugal', 'quick-paypal-payments' ) . '</option>
    <option value="RU" ' . $RU . '>' . esc_html__( 'Russia', 'quick-paypal-payments' ) . '</option>
    <option value="ru_RU" ' . $ru_RU . '>' . esc_html__( 'Russian (for Lithuania, Latvia, and Ukraine only)', 'quick-paypal-payments' ) . '</option>
    <option value="zh_CN" ' . $zh_CN . '>' . esc_html__( 'Simplified Chinese (for China only)', 'quick-paypal-payments' ) . '</option>
    <option value="zh_HK" ' . $zh_HK . '>' . esc_html__( 'Traditional Chinese (for Hong Kong only)', 'quick-paypal-payments' ) . '</option>
    <option value="zh_TW" ' . $zh_TW . '>' . esc_html__( 'Traditional Chinese (for Taiwan only)', 'quick-paypal-payments' ) . '</option>
    <option value="ES" ' . $ES . '>' . esc_html__( 'Spain', 'quick-paypal-payments' ) . '</option>
    <option value="sv_SE" ' . $sv_SE . '>' . esc_html__( 'Swedish (for Sweden only)', 'quick-paypal-payments' ) . '</option>
    <option value="th_TH" ' . $th_TH . '>' . esc_html__( 'Thai (for Thailand only)', 'quick-paypal-payments' ) . '</option>
    <option value="tr_TR" ' . $tr_TR . '>' . esc_html__( 'Turkish (for Turkey only)', 'quick-paypal-payments' ) . '</option>
    <option value="GB" ' . $GB . '>' . esc_html__( 'United Kingdom', 'quick-paypal-payments' ) . '</option>
    <option value="US" ' . $US . '>' . esc_html__( 'United States', 'quick-paypal-payments' ) . '</option>
    </select>
    
    <h2>' . esc_html__( 'Cancel and Thank you pages', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Where the buyer is sent after paying, or after changing their mind. Leave them blank and they come back to the page the form is on. This applies whichever gateway takes the payment.', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'URL of cancellation page', 'quick-paypal-payments' ) . '</p>
    <input type="text" style="width:100%" name="cancelurl" value="' . esc_attr( qpp_get_element( $send, 'cancelurl' ) ) . '" />
    <p>' . esc_html__( 'URL of thank you page', 'quick-paypal-payments' ) . '</p>
    <input type="text" style="width:100%" name="thanksurl" value="' . esc_attr( qpp_get_element( $send, 'thanksurl' ) ) . '" />
    <h2>' . esc_html__( 'Confirmation Message', 'quick-paypal-payments' ) . '</h2>
    <p><input  type="checkbox" name="confirmmessage"' . checked( qpp_get_element( $send, 'confirmmessage' ), 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Send yourself a copy of the payment details.', 'quick-paypal-payments' ) . '</p>
    <p><input type="text" style="width:100%" name="confirmemail" value="' . esc_attr( qpp_get_element( $send, 'confirmemail' ) ) . '" /></p>
    ';
    $content .= '<p>' . qpp_upgrade_prompt( 'silver', esc_html__( 'Sending the payer a confirmation email', 'quick-paypal-payments' ) ) . '</p>';
    $content .= '<h2>' . esc_html__( 'Custom Paypal Settings', 'quick-paypal-payments' ) . '</h2>';
    $content .= '<p>' . qpp_upgrade_prompt( 'gold', esc_html__( 'Donations only mode', 'quick-paypal-payments' ) ) . '</p>';
    $content .= '<p>' . qpp_upgrade_prompt( 'silver', esc_html__( 'Including postage in the amount to pay', 'quick-paypal-payments' ) ) . '</p>';
    $content .= '<p>' . esc_html__( 'If you have a custom PayPal page enter the URL here. Leave blank to use the standard PayPal payment page', 'quick-paypal-payments' ) . '</p>
    <p><input type="text" style="width:100%" name="customurl" value="' . esc_attr( qpp_get_element( $send, 'customurl' ) ) . '" /></p>
    <p>' . esc_html__( 'Alternate PayPal email address:', 'quick-paypal-payments' ) . '</p>
    <p><input type="text" style="width:100%" name="email" value="' . esc_attr( qpp_get_element( $send, 'email' ) ) . '" /></p>
    <p><input type="radio" name="target" value="current"' . esc_attr( $current ) . ' /> ' . esc_html__( 'Open in existing page', 'quick-paypal-payments' ) . '<br>
    <input type="radio" name="target" value="newpage"' . esc_attr( $newpage ) . ' /> ' . esc_html__( 'Open link in new page/tab', 'quick-paypal-payments' ) . ' <span class="description">' . esc_html__( 'This is very browser dependent. Use with caution!', 'quick-paypal-payments' ) . '</span></p>
    
    <h2>' . esc_html__( 'Google onClick Event', 'quick-paypal-payments' ) . '</h2>
    <p><input type="text" style="width:100%" name="google_onclick" value="' . esc_attr( qpp_get_element( $send, 'google_onclick' ) ) . '" /></p>
    
    ';
    $content .= '<p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the form settings?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>';
    $content .= '</div>
    <div class="qpp-options" style="float:right;">
    
    <h2>' . esc_html__( 'Form Preview', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Note: The preview form uses the wordpress admin styles. Your form will use the theme styles so won\'t look exactly like the one below.', 'quick-paypal-payments' ) . '</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args, true );
    $content .= qpp_examples_footer() . '</div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_error_page(  $id  ) {
    qpp_change_form_update();
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array('errortitle', 'errorblurb');
        $error = array();
        foreach ( $options as $item ) {
            // An unticked checkbox is absent from the post, which means off.
            $error[$item] = ( isset( $_POST[$item] ) ? stripslashes( $_POST[$item] ) : '' );
            $error[$item] = sanitize_text_field( $error[$item] );
        }
        update_option( 'qpp_error' . $id, $error );
        qpp_admin_notice( esc_html__( 'The error settings have been updated.', 'quick-paypal-payments' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_error' . $id );
        qpp_admin_notice( esc_html__( 'The error messages have been reset.', 'quick-paypal-payments' ) );
    }
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $error = qpp_get_stored_error( $id );
    $content = qpp_head_css();
    $content .= '<div class="qpp-settings"><div class="qpp-options">';
    if ( $id ) {
        /* translators: %s: form name. */
        $content .= '<h2>' . sprintf( esc_html__( 'Error message settings for %s', 'quick-paypal-payments' ), esc_html( $id ) ) . '</h2>';
    } else {
        $content .= '<h2>' . esc_html__( 'Default form error message', 'quick-paypal-payments' ) . '</h2>';
    }
    $content .= qpp_change_form( $qpp_setup );
    $content .= '<form method="post" action="">
    <p class="description">' . esc_html__( 'Shown above the form when a payment cannot be submitted. Leave a field blank to use the wording the plugin ships with.', 'quick-paypal-payments' ) . '</p>
    <table>
    <tr>
    <td>' . esc_html__( 'Error header', 'quick-paypal-payments' ) . '</td>
    <td><input type="text"  style="width:100%" name="errortitle" value="' . esc_attr( $error['errortitle'] ) . '" /></td>
    </tr>
    <tr>
    <td>' . esc_html__( 'Error message', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="errorblurb" value="' . esc_attr( $error['errorblurb'] ) . '" /></td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the error message?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>' . esc_html__( 'Error Checker', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Try sending a blank form to test your error messages.', 'quick-paypal-payments' ) . '</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args, true );
    $content .= qpp_examples_footer() . '</div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

/**
 * The Integrations tab.
 *
 * Mailchimp used to sit at the bottom of Send Options, below an unrelated Save
 * button, which is a poor home for something sold as part of Platinum. It also
 * shared that page's Reset handler: the Mailchimp form's reset was name="Reset",
 * and the send page deletes qpp_send on that name, so resetting Mailchimp wiped
 * the submission settings while the confirmation said otherwise.
 *
 * Each integration is a card, shown on every plan so the feature is discoverable,
 * with the settings or an upsell inside depending on the plan.
 */
function qpp_integrations_page(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    $entitled = $quick_paypal_payments_fs->is_plan_or_trial__premium_only( 'platinum' );
    if ( $entitled && isset( $_POST['qpp_mailchimp_save'] ) && check_admin_referer( "save_qpp" ) ) {
        $list = array();
        foreach ( array(
            'enable',
            'mailchimpoptin',
            'mailchimpkey',
            'mailchimplistid'
        ) as $item ) {
            // An unticked checkbox is absent from the post, which means off.
            $list[$item] = ( isset( $_POST[$item] ) ? sanitize_text_field( wp_unslash( $_POST[$item] ) ) : '' );
        }
        update_option( 'qpp_mailinglist', $list );
        qpp_admin_notice( esc_html__( 'The Mailchimp settings have been updated.', 'quick-paypal-payments' ) );
    }
    if ( $entitled && isset( $_POST['qpp_mailchimp_reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_mailinglist' );
        qpp_admin_notice( esc_html__( 'The Mailchimp settings have been reset.', 'quick-paypal-payments' ) );
    }
    if ( $quick_paypal_payments_fs->is_plan_or_trial__premium_only( 'silver' ) && isset( $_POST['qpp_createuser_save'] ) && check_admin_referer( "save_qpp" ) ) {
        // Read, change one key, write. This option holds the whole Send Options
        // screen, so rebuilding it from this form's post would erase the rest.
        $send = get_option( 'qpp_send' . $id );
        if ( !is_array( $send ) ) {
            $send = array();
        }
        $send['createuser'] = ( isset( $_POST['createuser'] ) ? 'checked' : '' );
        // Run through the same guard the account creation uses, so a role that
        // is not on the list cannot be posted straight into the option.
        $send['createuserwhen'] = ( 'aftersubmission' === qpp_get_element( $_POST, 'createuserwhen' ) ? 'aftersubmission' : 'afterpayment' );
        $send['createuserrole'] = qpp_creatable_role( ( isset( $_POST['createuserrole'] ) ? sanitize_key( wp_unslash( $_POST['createuserrole'] ) ) : '' ) );
        update_option( 'qpp_send' . $id, $send );
        qpp_admin_notice( esc_html__( 'The account creation setting has been updated.', 'quick-paypal-payments' ) );
    }
    $send = qpp_get_stored_send( $id );
    $list = qpp_get_stored_mailinglist();
    $connected = 'checked' === $list['enable'] && !empty( $list['mailchimpkey'] );
    $content = '<div class="qpp-settings"><div class="qpp-options" style="width:auto;float:none;max-width:820px;">
    <h2>' . esc_html__( 'Integrations', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Send what your forms collect on to the other services you use.', 'quick-paypal-payments' ) . '</p>

    <div style="border:1px solid #dcdcde;border-radius:8px;overflow:hidden;margin-bottom:1.5em;background:#fff;">
      <div style="background:#ffe01b;padding:22px 24px;display:flex;align-items:center;gap:16px;">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:50%;background:#241c15;color:#ffe01b;font-size:26px;font-weight:700;">M</span>
        <span>
          <span style="display:block;font-size:1.5em;font-weight:700;color:#241c15;line-height:1.2;">Mailchimp</span>
          <span style="display:block;color:#241c15;">' . esc_html__( 'Add every buyer to your mailing list as they pay.', 'quick-paypal-payments' ) . '</span>
        </span>
      </div>
      <div style="padding:20px 24px;">';
    if ( !$entitled ) {
        $content .= '<p>' . qpp_upgrade_prompt( 'platinum', 'Mailchimp' ) . '</p>
        <p class="description">' . esc_html__( 'Buyers who tick the opt-in box on your form are added to your audience automatically, with their first name, last name and email address.', 'quick-paypal-payments' ) . '</p>';
    } else {
        $status = ( $connected ? '<span style="color:#008a20;font-weight:600;">' . esc_html__( 'Connected', 'quick-paypal-payments' ) . '</span>' : '<span style="color:#8c8f94;">' . esc_html__( 'Not connected', 'quick-paypal-payments' ) . '</span>' );
        $content .= '<p>' . esc_html__( 'Status', 'quick-paypal-payments' ) . ': ' . $status . '</p>
        <p>' . esc_html__( 'Your form must collect an email address, and show the opt-in checkbox, for anyone to be added. The fields sent are EMAIL, FNAME and LNAME.', 'quick-paypal-payments' ) . '</p>
        <p><a href="https://fullworksplugins.com/docs/quick-paypal-payments/pro-features-quick-paypal-payments/mailchhimp-setup/" target="_blank" rel="noopener">' . esc_html__( 'Help setting up Mailchimp', 'quick-paypal-payments' ) . '</a></p>
        <form method="post" action="">
        <p><input type="checkbox" name="enable"' . checked( $list['enable'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Enable Mailchimp data collection.', 'quick-paypal-payments' ) . '</p>
        <p>' . esc_html__( 'Opt-in message shown on the form', 'quick-paypal-payments' ) . '<br><input type="text" style="width:100%" name="mailchimpoptin" value="' . esc_attr( $list['mailchimpoptin'] ) . '" /></p>
        <p>' . esc_html__( 'API key', 'quick-paypal-payments' ) . '<br><input type="password" autocomplete="new-password" style="width:100%" name="mailchimpkey" value="' . esc_attr( $list['mailchimpkey'] ) . '" /></p>
        <p>' . esc_html__( 'Audience (list) ID', 'quick-paypal-payments' ) . '<br><input type="text" style="width:100%" name="mailchimplistid" value="' . esc_attr( $list['mailchimplistid'] ) . '" /></p>
        <p><input type="submit" name="qpp_mailchimp_save" class="button-primary" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" />
        <input type="submit" name="qpp_mailchimp_reset" class="button-secondary" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the Mailchimp settings?', 'quick-paypal-payments' ) ) . '\' );" /></p>';
        $content .= wp_nonce_field( "save_qpp" );
        $content .= '</form>';
    }
    $content .= '</div>
    </div>';
    $form_label = ( '' === $id ? esc_html__( 'the default form', 'quick-paypal-payments' ) : $id );
    $content .= '<div style="border:1px solid #dcdcde;border-radius:8px;overflow:hidden;margin-bottom:1.5em;background:#fff;">
      <div style="background:#21759b;padding:22px 24px;display:flex;align-items:center;gap:16px;">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:50%;background:#fff;color:#21759b;font-size:26px;" class="dashicons dashicons-wordpress"></span>
        <span>
          <span style="display:block;font-size:1.5em;font-weight:700;color:#fff;line-height:1.2;">' . esc_html__( 'WordPress accounts', 'quick-paypal-payments' ) . '</span>
          <span style="display:block;color:#fff;">' . esc_html__( 'Give the buyer a login on your site when they pay.', 'quick-paypal-payments' ) . '</span>
        </span>
      </div>
      <div style="padding:20px 24px;">';
    $content .= '<p>' . qpp_upgrade_prompt( 'silver', esc_html__( 'Creating a WordPress account when someone pays', 'quick-paypal-payments' ) ) . '</p>
        <p class="description">' . esc_html__( 'You choose the role, and the buyer is emailed a link to set their own password.', 'quick-paypal-payments' ) . '</p>';
    $content .= '</div>
    </div>
    </div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

/**
 * The Stripe settings tab.
 *
 * Platinum. Guards the save as well as the render, so the keys cannot be posted
 * in by a plan that does not include the gateway.
 */
function qpp_stripe_page() {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    qpp_upgrade_page( 'platinum', esc_html__( 'Stripe Checkout', 'quick-paypal-payments' ) );
    return;
    /*
     * The Stripe code is required at plugin load behind can_use_premium_code(),
     * while this page is gated on the platinum plan. Two conditions guarding the
     * same code, so if they ever disagree this page would fatal on a call to an
     * undefined function. Say so instead.
     */
    if ( !function_exists( 'qpp_stripe_webhook_url' ) ) {
        echo wp_kses( '<div class="qpp-settings"><div class="qpp-options"><h2>' . esc_html__( 'Stripe Checkout', 'quick-paypal-payments' ) . '</h2>' . '<p>' . esc_html__( 'The Stripe code is not loaded on this install. Reactivate your licence, then reload this page.', 'quick-paypal-payments' ) . '</p>' . '</div></div>', qpp_allowed_html() );
        return;
    }
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $stripe = qpp_get_stored_stripe();
        foreach ( array('enable', 'secret_key', 'webhook_secret') as $item ) {
            // An unticked checkbox is absent from the post, which means off.
            $stripe[$item] = ( isset( $_POST[$item] ) ? sanitize_text_field( wp_unslash( $_POST[$item] ) ) : '' );
        }
        update_option( 'qpp_stripe', $stripe );
        qpp_admin_notice( esc_html__( 'The Stripe settings have been updated.', 'quick-paypal-payments' ) );
    }
    $stripe = qpp_get_stored_stripe();
    $content = '<div class="qpp-settings"><div class="qpp-options">
    <h2>' . esc_html__( 'Stripe Checkout', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Take card payments through Stripe instead of PayPal. The customer pays on a page hosted by Stripe, so no card details ever reach your site.', 'quick-paypal-payments' ) . '</p>
    <form method="post" action="">
    <p><input type="checkbox" name="enable"' . checked( $stripe['enable'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Use Stripe for this site\'s payment forms.', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'Secret key', 'quick-paypal-payments' ) . '<br><input type="password" autocomplete="new-password" style="width:100%" name="secret_key" value="' . esc_attr( $stripe['secret_key'] ) . '" /></p>
    <p class="description">' . esc_html__( 'From Stripe, Developers, API keys. Starts sk_live_ or sk_test_.', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'Webhook signing secret', 'quick-paypal-payments' ) . '<br><input type="password" autocomplete="new-password" style="width:100%" name="webhook_secret" value="' . esc_attr( $stripe['webhook_secret'] ) . '" /></p>
    <p class="description">' . esc_html__( 'Starts whsec_. Without it payments cannot be confirmed, because there is no way to tell a real notification from a forged one.', 'quick-paypal-payments' ) . '</p>
    <p><input type="submit" name="Submit" class="button-primary" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right">
    <h2>' . esc_html__( 'Setting Stripe up', 'quick-paypal-payments' ) . '</h2>
    <ol>
    <li>' . esc_html__( 'In Stripe go to Developers, then Webhooks, and add an endpoint.', 'quick-paypal-payments' ) . '</li>
    <li>' . esc_html__( 'Paste this URL as the endpoint:', 'quick-paypal-payments' ) . '<br><code>' . esc_url( qpp_stripe_webhook_url() ) . '</code></li>
    <li>' . sprintf( 
        /* translators: %s: the Stripe event name checkout.session.completed, in a code tag. */
        esc_html__( 'Choose the event %s. Nothing else is needed.', 'quick-paypal-payments' ),
        '<code>checkout.session.completed</code>'
     ) . '</li>
    <li>' . esc_html__( 'Copy the signing secret Stripe shows you into the box on the left.', 'quick-paypal-payments' ) . '</li>
    <li>' . esc_html__( 'Copy your secret key from Developers, API keys.', 'quick-paypal-payments' ) . '</li>
    </ol>
    <p>' . esc_html__( 'Use your test keys first. A test payment appears in your payment list exactly as a live one would.', 'quick-paypal-payments' ) . '</p>
    <p><strong>' . esc_html__( 'Recurring forms still go through PayPal.', 'quick-paypal-payments' ) . '</strong> ' . esc_html__( 'Stripe Checkout will not accept a subscription that stops after a set number of payments, so a recurring form falls back to PayPal rather than billing a customer open ended. One off payments on the same site still go through Stripe.', 'quick-paypal-payments' ) . '</p>
    </div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_ipn_page() {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    qpp_upgrade_page( 'silver', esc_html__( 'Instant Payment Notification', 'quick-paypal-payments' ) );
    return;
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'ipn',
            'paid',
            'title',
            'listener',
            'deleterecord'
        );
        $ipn = array();
        foreach ( $options as $item ) {
            // An unticked checkbox is absent from the post, which means off.
            $ipn[$item] = ( isset( $_POST[$item] ) ? stripslashes( $_POST[$item] ) : '' );
            $ipn[$item] = sanitize_text_field( $ipn[$item] );
        }
        update_option( 'qpp_ipn', $ipn );
        qpp_admin_notice( esc_html__( 'The IPN settings have been updated.', 'quick-paypal-payments' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_ipn' );
        qpp_admin_notice( esc_html__( 'The IPN settings have been reset.', 'quick-paypal-payments' ) );
    }
    $ipn = qpp_get_stored_ipn();
    $content = '<div class="qpp-settings"><div class="qpp-options">
	<h2>' . esc_html__( 'Instant Payment Notifications', 'quick-paypal-payments' ) . '</h2>
    <p><b>' . esc_html__( 'Note:', 'quick-paypal-payments' ) . '</b> ' . sprintf( 
        /* translators: %s: the heading "Setting IPN up in PayPal", in bold. */
        esc_html__( 'IPN needs a PayPal Business or Premier account, and IPN must be switched on in that account. See %s alongside.', 'quick-paypal-payments' ),
        '<b>' . esc_html__( 'Setting IPN up in PayPal', 'quick-paypal-payments' ) . '</b>'
     ) . '</p>
	<form method="post" action="">
    <table>
    <tr>
    <td><input  type="checkbox" name="ipn"' . checked( $ipn['ipn'], 'checked', false ) . ' value="checked" /></td>
    <td colspan="2"> ' . esc_html__( 'Enable IPN.', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td></td>
    <td width ="40%">' . esc_html__( 'Payment Report Column header:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text"  style="width:100%" name="title" value="' . esc_attr( $ipn['title'] ) . '" /></td>
    </tr>
    <tr>
    <td></td>
    <td>' . esc_html__( 'Payment Complete Label:', 'quick-paypal-payments' ) . '</td>
    <td><input type="text"  style="width:100%" name="paid" value="' . esc_attr( $ipn['paid'] ) . '" /></td>
    </tr>
    <tr>
    <td></td>
    <td>' . esc_html__( 'Third Party Listener URL (optional: advanced):', 'quick-paypal-payments' ) . '</td>
    <td><input type="text"  style="width:100%" name="listener" value="' . esc_attr( qpp_get_element( $ipn, 'listener' ) ) . '" /></td>
    </tr>
    <tr>
    <td><input  type="checkbox" name="deleterecord"' . checked( qpp_get_element( $ipn, 'deleterecord' ), 'checked', false ) . ' value="checked" /></td>
    <td colspan="2"> ' . esc_html__( 'Delete record after payment.', 'quick-paypal-payments' ) . '</td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the IPN settings?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    <p>' . esc_html__( 'If you set a Listener URL above this plugin will not automatically handle IPN\'s, this is for advanced usage e.g. split IPN handling. If you haven\'t set an IPN listener URL above this is the one you need to get payment confirmation:', 'quick-paypal-payments' ) . '<pre>' . site_url( '/?qpp_ipn' ) . '</pre></p>
    <p>' . sprintf( 
        /* translators: 1: the word "Payments" in bold, the dashboard menu item, 2: a link with the text "click here". */
        esc_html__( 'To check completed payments click on the %1$s link in your dashboard menu or %2$s.', 'quick-paypal-payments' ),
        '<b>' . esc_html__( 'Payments', 'quick-paypal-payments' ) . '</b>',
        '<a href="?page=quick-paypal-payments-messages">' . esc_html__( 'click here', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>' . esc_html__( 'Setting IPN up in PayPal', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'PayPal have moved their IPN documentation more than once, so the steps are repeated here in full.', 'quick-paypal-payments' ) . '</p>
    <ol>
    <li>' . esc_html__( 'Log in to your PayPal account', 'quick-paypal-payments' ) . '</li>
    <li>' . sprintf( 
        /* translators: %s: the word "Business" in bold. */
        esc_html__( 'You need a %s account. If yours is Personal, follow PayPal\'s instructions to upgrade it first', 'quick-paypal-payments' ),
        '<b>' . esc_html__( 'Business', 'quick-paypal-payments' ) . '</b>'
     ) . '</li>
    <li>' . sprintf( 
        /* translators: %s: a link with the text "Business profile -> Notifications -> Instant payment notifications". */
        esc_html__( 'Go to %s', 'quick-paypal-payments' ),
        '<a href="https://www.paypal.com/businessmanage/account/notifications" target="_blank">' . esc_html__( 'Business profile &rarr; Notifications &rarr; Instant payment notifications', 'quick-paypal-payments' ) . '</a>'
     ) . '</li>
    <li>' . sprintf(
        /* translators: 1, 2, 3: the PayPal button names Manage, Update and Choose IPN Settings, in bold. */
        esc_html__( 'Click %1$s (or %2$s, then %3$s)', 'quick-paypal-payments' ),
        '<b>' . esc_html__( 'Manage', 'quick-paypal-payments' ) . '</b>',
        '<b>' . esc_html__( 'Update', 'quick-paypal-payments' ) . '</b>',
        '<b>' . esc_html__( 'Choose IPN Settings', 'quick-paypal-payments' ) . '</b>'
    ) . '</li>
    <li>' . esc_html__( 'Enter this Notification URL:', 'quick-paypal-payments' ) . '<pre>' . esc_url( site_url( '/?qpp_ipn' ) ) . '</pre></li>
    <li>' . sprintf( 
        /* translators: %s: the PayPal option name "Receive IPN messages (Enabled)", in bold. */
        esc_html__( 'Select %s', 'quick-paypal-payments' ),
        '<b>' . esc_html__( 'Receive IPN messages (Enabled)', 'quick-paypal-payments' ) . '</b>'
     ) . '</li>
    <li>' . sprintf( 
        /* translators: %s: the button name "Save", in bold. */
        esc_html__( 'Click %s', 'quick-paypal-payments' ),
        '<b>' . esc_html__( 'Save', 'quick-paypal-payments' ) . '</b>'
     ) . '</li>
    </ol>
    <p>' . sprintf( 
        /* translators: %s: a link with the text "IPN history". */
        esc_html__( 'To see what PayPal actually sent, and to resend it, open your %s. That is the first place to look if a payment has not been marked complete.', 'quick-paypal-payments' ),
        '<a href="https://www.paypal.com/merchantnotification/ipn/history" target="_blank">' . esc_html__( 'IPN history', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <p>' . sprintf( 
        /* translators: %s: a link with the text "IPN setup guide". */
        esc_html__( 'PayPal\'s own reference is the %s.', 'quick-paypal-payments' ),
        '<a href="https://developer.paypal.com/api/nvp-soap/ipn/IPNSetup/" target="_blank">' . esc_html__( 'IPN setup guide', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    </div>
    <div class="qpp-options" style="float:right;clear:right;">
    <h2>' . esc_html__( 'IPN Simulation', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'IPN can be blocked or restricted by your server settings, theme or other plugins. The good news is you can simulate the notifications to check if all is working.', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'To carry out a simulation:', 'quick-paypal-payments' ) . '</p>
    <ol>
    <li>' . sprintf( 
        /* translators: %s: a link with the text "plugin setup page". */
        esc_html__( 'Enable the PayPal Sandbox on the %s', 'quick-paypal-payments' ),
        '<a href="?page=quick-paypal-payments&tab=setup">' . esc_html__( 'plugin setup page', 'quick-paypal-payments' ) . '</a>'
     ) . '</li>
    <li>' . esc_html__( 'Fill in and send your payment form (you do not need to make an actual payment)', 'quick-paypal-payments' ) . '</li>
    <li>' . sprintf( 
        /* translators: %s: a link with the text "Payments Report". */
        esc_html__( 'Go to the %s and copy the long number in the last column from the payment you have just made', 'quick-paypal-payments' ),
        '<a href="?page=quick-paypal-payments-messages">' . esc_html__( 'Payments Report', 'quick-paypal-payments' ) . '</a>'
     ) . '</li>
    <li>' . esc_html__( 'Go to the IPN simulation page:', 'quick-paypal-payments' ) . ' <a href="https://developer.paypal.com/dashboard/ipnSimulator" target="_blank">https://developer.paypal.com/dashboard/ipnSimulator</a></li>
    <li>' . esc_html__( 'Login and enter the IPN listener URL', 'quick-paypal-payments' ) . '</li>
    <li>' . esc_html__( 'Select \'Express Checkout\' from the drop down', 'quick-paypal-payments' ) . '</li>
    <li>' . esc_html__( 'Scroll to the bottom of the page and enter the long number you copied at step 3 into the \'Custom\' field', 'quick-paypal-payments' ) . '</li>
    <li>' . esc_html__( 'Click \'Send IPN\'. Scroll up the page and you should see an \'IPN Verified\' message.', 'quick-paypal-payments' ) . '</li>
    <li>' . esc_html__( 'Go back to your Payments Report and refresh, you should now see the payment completed message', 'quick-paypal-payments' ) . '</li>
    </ol>
    </div>
    </div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_autoresponce_page(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    qpp_upgrade_page( 'silver', esc_html__( 'Auto Responder', 'quick-paypal-payments' ) );
    return;
    qpp_change_form_update();
    $afterpayment = $aftersubmission = false;
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'enable',
            'whenconfirm',
            'fromname',
            'fromemail',
            'subject',
            'message',
            'paymentdetails'
        );
        $auto = array();
        foreach ( $options as $item ) {
            // An unticked checkbox is absent from the post, which means off.
            $auto[$item] = ( isset( $_POST[$item] ) ? stripslashes( $_POST[$item] ) : '' );
        }
        update_option( 'qpp_autoresponder' . $id, $auto );
        if ( $id ) {
            /* translators: %s: form name. */
            qpp_admin_notice( sprintf( esc_html__( 'The autoresponder settings for %s have been updated.', 'quick-paypal-payments' ), $id ) );
        } else {
            qpp_admin_notice( esc_html__( 'The default form autoresponder settings have been updated.', 'quick-paypal-payments' ) );
        }
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_autoresponder' . $id );
        if ( $id ) {
            /* translators: %s: form name. */
            qpp_admin_notice( sprintf( esc_html__( 'The autoresponder settings for the form called %s have been reset.', 'quick-paypal-payments' ), $id ) );
        } else {
            qpp_admin_notice( esc_html__( 'The default form autoresponder settings have been reset.', 'quick-paypal-payments' ) );
        }
    }
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $qpp = qpp_get_stored_options( $id );
    $auto = qpp_get_stored_autoresponder( $id );
    ${$auto['whenconfirm']} = 'checked';
    $message = $auto['message'];
    $content = '<div class="qpp-settings"><div class="qpp-options" style="width:90%;">';
    if ( $id ) {
        /* translators: %s: form name. */
        $content .= '<h2 style="color:#B52C00">' . sprintf( esc_html__( 'Autoresponse settings for %s', 'quick-paypal-payments' ), esc_html( $id ) ) . '</h2>';
    } else {
        $content .= '<h2 style="color:#B52C00">' . esc_html__( 'Default form autoresponse settings', 'quick-paypal-payments' ) . '</h2>';
    }
    $content .= qpp_change_form( $qpp_setup );
    $content .= '<p>' . esc_html__( 'The auto responder sends a confirmation message to the Payer. Use the editor below to send links, images and anything else you normally add to a post or page.', 'quick-paypal-payments' ) . '</p>
    <p class="description">' . sprintf( 
        /* translators: %s: a link with the text "Form Settings". */
        esc_html__( 'Note that the autoresponder only works if you collect an email address on the %s.', 'quick-paypal-payments' ),
        '<a href="?page=quick-paypal-payments&tab=settings">' . esc_html__( 'Form Settings', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <p class="description">' . sprintf( 
        /* translators: %s: a link with the text "Send Options". */
        esc_html__( 'If you want to receive notification messages use the option on the %s tab.', 'quick-paypal-payments' ),
        '<a href="?page=quick-paypal-payments&tab=send">' . esc_html__( 'Send Options', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <form method="post" action="">
    <p><input type="checkbox" name="enable"' . checked( $auto['enable'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Enable Auto Responder', 'quick-paypal-payments' ) . '</p> 
    <p><input type="radio" name="whenconfirm" value="aftersubmission"' . checked( $auto['whenconfirm'], 'aftersubmission', false ) . ' /> ' . esc_html__( 'After submission to PayPal', 'quick-paypal-payments' ) . '<br>
    <input type="radio" name="whenconfirm" value="afterpayment"' . checked( $auto['whenconfirm'], 'afterpayment', false ) . ' /> ' . sprintf( 
        /* translators: %s: a link with the text "IPN". */
        esc_html__( 'After payment (only works if %s is enabled)', 'quick-paypal-payments' ),
        '<a href="?page=quick-paypal-payments&tab=ipn">' . esc_html__( 'IPN', 'quick-paypal-payments' ) . '</a>'
     ) . '</p>
    <p>' . esc_html__( 'From Name', 'quick-paypal-payments' ) . ' (<span class="description">' . sprintf( 
        /* translators: %s: a link with the text "Site Title". */
        esc_html__( 'Defaults to your %s if left blank.', 'quick-paypal-payments' ),
        '<a href="' . get_admin_url() . 'options-general.php">' . esc_html__( 'Site Title', 'quick-paypal-payments' ) . '</a>'
     ) . '</span>):<br>
    <input type="text" style="width:50%" name="fromname" value="' . esc_attr( $auto['fromname'] ) . '" /></p>
    <p>' . esc_html__( 'From Email', 'quick-paypal-payments' ) . ' (<span class="description">' . sprintf( 
        /* translators: %s: a link with the text "PayPal email address". */
        esc_html__( 'Defaults to your %s if left blank.', 'quick-paypal-payments' ),
        '<a href="?page=quick-paypal-payments&tab=setup">' . esc_html__( 'PayPal email address', 'quick-paypal-payments' ) . '</a>'
     ) . '</span>):<br>
    <input type="text" style="width:50%" name="fromemail" value="' . esc_attr( $auto['fromemail'] ) . '" /></p>
    <p>' . esc_html__( 'Subject', 'quick-paypal-payments' ) . '</p>
    <input style="width:100%" type="text" name="subject" value="' . esc_attr( $auto['subject'] ) . '"/><br>
    <p>' . esc_html__( 'Message Content', 'quick-paypal-payments' ) . '</p>';
    echo wp_kses( $content, qpp_allowed_html() );
    wp_editor( $message, 'message', $settings = array(
        'textarea_rows' => '20',
        'wpautop'       => false,
    ) );
    $personal_details = '<a href="?page=quick-paypal-payments&tab=address">' . esc_html__( 'personal details', 'quick-paypal-payments' ) . '</a>';
    $content = '<p>' . esc_html__( 'You can use the following shortcodes in the message body:', 'quick-paypal-payments' ) . '</p>
    <table>
    <tr>
    <th>' . esc_html__( 'Shortcode', 'quick-paypal-payments' ) . '</th>
    <th>' . esc_html__( 'Replacement Text', 'quick-paypal-payments' ) . '</th>
    </tr>
    <tr>
    <td>[firstname]</td>
    <td>' . sprintf( 
        /* translators: %s: a link with the text "personal details". */
        esc_html__( 'The registrants first name if you are using the %s option.', 'quick-paypal-payments' ),
        $personal_details
     ) . '</td>
    </tr>
    <tr>
    <td>[name]</td>
    <td>' . sprintf( 
        /* translators: %s: a link with the text "personal details". */
        esc_html__( 'The registrants first and last name if you are using the %s option.', 'quick-paypal-payments' ),
        $personal_details
     ) . '</td>
    </tr>
    <tr>
    <td>[reference]</td>
    <td>' . esc_html__( 'The name of the item being purchased', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>[amount]</td>
    <td>' . esc_html__( 'The total amount to be paid without the currency symbol', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>[fullamount]</td>
    <td>' . esc_html__( 'The total amount to be paid with currency symbol', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>[quantity]</td>
    <td>' . esc_html__( 'The number of items purchased', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>[option]</td>
    <td>' . esc_html__( 'The option selected', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>[stock]</td>
    <td>' . esc_html__( 'The stock, SKU or item number', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>[details]</td>
    <td>' . esc_html__( 'The payment information (reference, quantity, options, stock number, amount)', 'quick-paypal-payments' ) . '</td>
    </tr>
    <tr>
    <td>[multiple]</td>
    <td>' . esc_html__( 'A table with the names and quantities of each item ordered (Platinum only)', 'quick-paypal-payments' ) . '</td>
    </tr>
    </table>
    <p><input type="checkbox" name="paymentdetails"' . checked( $auto['paymentdetails'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Add payment details to the message', 'quick-paypal-payments' ) . '</p> 
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the auto responder settings?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    </div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_address(  $id  ) {
    qpp_change_form_update();
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $options = array(
            'useaddress',
            'firstname',
            'lastname',
            'email',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b',
            'rfirstname',
            'rlastname',
            'remail',
            'raddress1',
            'raddress2',
            'rcity',
            'rstate',
            'rzip',
            'rcountry',
            'rnight_phone_b',
            'permitted_country',
            'default_country'
        );
        $address = array();
        foreach ( $options as $item ) {
            if ( !isset( $_POST[$item] ) ) {
                $address[$item] = '';
                continue;
            }
            $address[$item] = ( is_array( $_POST[$item] ) ? array_map( 'esc_attr', $_POST[$item] ) : esc_attr( $_POST[$item] ) );
        }
        update_option( 'qpp_address' . $id, $address );
        qpp_admin_notice( esc_html__( 'The form settings have been updated.', 'quick-paypal-payments' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        // This deleted qpp_error, so Reset on this screen wiped the validation
        // messages and left the address fields exactly as they were.
        delete_option( 'qpp_address' . $id );
        qpp_admin_notice( esc_html__( 'The personal details fields have been reset.', 'quick-paypal-payments' ) );
    }
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $address = qpp_get_stored_address( $id );
    $content = '<div class="qpp-settings"><div class="qpp-options">';
    if ( $id ) {
        /* translators: %s: form name. */
        $content .= '<h2>' . sprintf( esc_html__( 'Personal Information Fields for %s', 'quick-paypal-payments' ), esc_html( $id ) ) . '</h2>';
    } else {
        $content .= '<h2>' . esc_html__( 'Personal Information Fields', 'quick-paypal-payments' ) . '</h2>';
    }
    $content .= qpp_change_form( $qpp_setup );
    $ccodes = Utilities::get_instance()->get_paypal_locales();
    $default_countries = '';
    foreach ( $ccodes as $code => $data ) {
        $sel = '';
        if ( $code == $address['default_country'] ) {
            $sel = 'selected';
        }
        $default_countries .= '<option value="' . esc_attr( $code ) . '" ' . $sel . '>' . $data['region'] . '</option>';
    }
    $permitted_countries = '';
    foreach ( $ccodes as $code => $data ) {
        $sel = '';
        if ( isset( $address['permitted_country'] ) && is_array( $address['permitted_country'] ) && in_array( $code, $address['permitted_country'] ) ) {
            $sel = 'selected';
        }
        $permitted_countries .= '<option value="' . esc_attr( $code ) . '" ' . $sel . '>' . $data['region'] . '</option>';
    }
    $content .= '<form method="post" action="">
    <p class="description">' . esc_html__( 'Note: The information will be collected and saved and passed to PayPal but usage is dependent on browser and user settings. Which means they may have to fill in the information again when they get to PayPal', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( '1. Delete labels for fields you do not want to use.', 'quick-paypal-payments' ) . '</p>
    <p>' . sprintf( 
        /* translators: %s: the column heading "R", in bold. */
        esc_html__( '2. Check the %s box for mandatory/required fields.', 'quick-paypal-payments' ),
        '<b>' . esc_html_x( 'R', 'column heading, short for Required', 'quick-paypal-payments' ) . '</b>'
     ) . '</p>
    <table>
    <tr>
    
    <th>' . esc_html__( 'Field', 'quick-paypal-payments' ) . '</th>
    <th>' . esc_html__( 'Label', 'quick-paypal-payments' ) . '</th>
    <th>' . esc_html_x( 'R', 'column heading, short for Required', 'quick-paypal-payments' ) . '</th>
    </tr>
    <tr>
    
    <td width="20%">' . esc_html__( 'First Name', 'quick-paypal-payments' ) . '</td>
    <td><input type="text"  style="width:100%" name="firstname" value="' . esc_attr( $address['firstname'] ) . '" /></td>
    <td width="5%"><input  type="checkbox" name="rfirstname"' . checked( $address['rfirstname'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'Last Name', 'quick-paypal-payments' ) . '</td>
    <td><input type="text"  style="width:100%" name="lastname" value="' . esc_attr( $address['lastname'] ) . '" /></td>
    <td><input  type="checkbox" name="rlastname"' . checked( $address['rlastname'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'Email', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="email" value="' . esc_attr( $address['email'] ) . '" /></td>
    <td><input  type="checkbox" name="remail"' . checked( $address['remail'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'Address Line 1', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="address1" value="' . esc_attr( $address['address1'] ) . '" /></td>
    <td><input  type="checkbox" name="raddress1"' . checked( $address['raddress1'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'Address Line 2', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="address2" value="' . esc_attr( $address['address2'] ) . '" /></td>
    <td><input  type="checkbox" name="raddress2"' . checked( $address['raddress2'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'City', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="city" value="' . esc_attr( $address['city'] ) . '" /></td>
    <td><input  type="checkbox" name="rcity"' . checked( $address['rcity'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'State', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="state" value="' . esc_attr( $address['state'] ) . '" /></td>
    <td><input  type="checkbox" name="rstate"' . checked( $address['rstate'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'Zip', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="zip" value="' . esc_attr( $address['zip'] ) . '" /></td>
    <td><input  type="checkbox" name="rzip"' . checked( $address['rzip'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'Country', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="country" value="' . esc_attr( $address['country'] ) . '" /></td>
    <td><input  type="checkbox" name="rcountry"' . checked( $address['rcountry'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    <tr>
    
    <td>' . esc_html__( 'Default / Permitted Countries', 'quick-paypal-payments' ) . '</td>
    <td><select style="width:40%" name="default_country">
							<option value="" disabled selected>' . esc_html__( 'Default Country', 'quick-paypal-payments' ) . '</option>' . esc_html( $default_countries ) . '
							
    </select><select style="width:50%; min-height: 200px;" name="permitted_country[]" multiple>
							<option value="" disabled selected>' . esc_html__( 'Permitted ( Multi-select)', 'quick-paypal-payments' ) . '</option>' . esc_html( $permitted_countries ) . '
							
    </select></td>
    
    <tr>
    
    <td>' . esc_html__( 'Phone', 'quick-paypal-payments' ) . '</td>
    <td><input type="text" style="width:100%" name="night_phone_b" value="' . esc_attr( $address['night_phone_b'] ) . '" /></td>
    <td><input  type="checkbox" name="rnight_phone_b"' . checked( $address['rnight_phone_b'], 'checked', false ) . ' value="checked" /></td>
    </tr>
    </table>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the personal details fields?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>' . esc_html__( 'Example Form', 'quick-paypal-payments' ) . '</h2>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args, true );
    $content .= qpp_examples_footer() . '</div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_coupon_codes(  $id  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    qpp_upgrade_page( 'gold', esc_html__( 'Coupon Codes', 'quick-paypal-payments' ) );
    return;
    qpp_change_form_update();
    if ( isset( $_POST['Submit'] ) && check_admin_referer( "save_qpp" ) ) {
        $arr = array(
            'couponnumber',
            'couponget',
            'duplicate',
            'couponerror',
            'couponexpired'
        );
        $coupon = array();
        foreach ( $arr as $item ) {
            if ( isset( $_POST[$item] ) ) {
                $coupon[$item] = stripslashes( $_POST[$item] );
                $coupon[$item] = sanitize_text_field( $coupon[$item] );
            }
        }
        $options = array(
            'code',
            'coupontype',
            'couponpercent',
            'couponfixed',
            'qty',
            'expired'
        );
        $coupon['couponnumber'] = (int) qpp_get_element( $coupon, 'couponnumber', 0 );
        if ( $coupon['couponnumber'] < 1 ) {
            $coupon['couponnumber'] = 1;
        }
        for ($i = 1; $i <= $coupon['couponnumber']; $i++) {
            foreach ( $options as $item ) {
                if ( isset( $_POST[$item . $i] ) ) {
                    $coupon[$item . $i] = stripslashes( sanitize_text_field( $_POST[$item . $i] ) );
                } else {
                    $coupon[$item . $i] = '';
                }
            }
            if ( $coupon['qty' . $i] > 0 || $coupon['qty' . $i] === '' ) {
                $coupon['expired' . $i] = false;
            }
            if ( !$coupon['coupontype' . $i] ) {
                $coupon['coupontype' . $i] = 'percent' . $i;
            }
            if ( !$coupon['couponpercent' . $i] ) {
                $coupon['couponpercent' . $i] = '10';
            }
            if ( !$coupon['couponfixed' . $i] ) {
                $coupon['couponfixed' . $i] = '5';
            }
        }
        update_option( 'qpp_coupon' . $id, $coupon );
        if ( isset( $coupon['duplicate'] ) && $coupon['duplicate'] ) {
            $qpp_setup = qpp_get_stored_setup();
            $arr = explode( ",", $qpp_setup['alternative'] );
            foreach ( $arr as $item ) {
                update_option( 'qpp_coupon' . $item, $coupon );
            }
        }
        qpp_admin_notice( esc_html__( 'The coupon settings have been updated.', 'quick-paypal-payments' ) );
    }
    if ( isset( $_POST['Reset'] ) && check_admin_referer( "save_qpp" ) ) {
        delete_option( 'qpp_coupon' . $id );
        qpp_admin_notice( esc_html__( 'The coupon settings have been reset.', 'quick-paypal-payments' ) );
    }
    $qpp_setup = qpp_get_stored_setup();
    $id = $qpp_setup['current'];
    $currency = qpp_get_stored_curr();
    $currency = qpp_ensure_currency( $currency, $id );
    $before = array(
        'USD' => '&#x24;',
        'CDN' => '&#x24;',
        'EUR' => '&euro;',
        'GBP' => '&pound;',
        'JPY' => '&yen;',
        'AUD' => '&#x24;',
        'BRL' => 'R&#x24;',
        'HKD' => '&#x24;',
        'ILS' => '&#x20aa;',
        'MXN' => '&#x24;',
        'NZD' => '&#x24;',
        'PHP' => '&#8369;',
        'SGD' => '&#x24;',
        'TWD' => 'NT&#x24;',
        'TRY' => '&pound;',
    );
    $after = array(
        'CZK' => 'K&#269;',
        'DKK' => 'Kr',
        'HUF' => 'Ft',
        'MYR' => 'RM',
        'NOK' => 'kr',
        'PLN' => 'z&#322',
        'RUB' => '&#1056;&#1091;&#1073;',
        'SEK' => 'kr',
        'CHF' => 'CHF',
        'THB' => '&#3647;',
    );
    $b = '';
    foreach ( $before as $item => $key ) {
        if ( $item == $currency[$id] ) {
            $b = $key;
        }
    }
    $a = '';
    foreach ( $after as $item => $key ) {
        if ( $item == $currency[$id] ) {
            $a = $key;
        }
    }
    $coupon = qpp_get_stored_coupon( $id );
    $content = '<div class="qpp-settings"><div class="qpp-options">';
    if ( $id ) {
        /* translators: %s: form name. */
        $content .= '<h2>' . sprintf( esc_html__( 'Coupon codes for %s', 'quick-paypal-payments' ), esc_html( $id ) ) . '</h2>';
    } else {
        $content .= '<h2>' . esc_html__( 'Default form coupon codes', 'quick-paypal-payments' ) . '</h2>';
    }
    $content .= qpp_change_form( $qpp_setup );
    $qpp = qpp_get_stored_options( $id );
    $content .= qpp_field_status_notice(
        (bool) $qpp['usecoupon'],
        esc_html__( 'Coupon codes', 'quick-paypal-payments' ),
        esc_html__( 'Coupon Code', 'quick-paypal-payments' ),
        // Recurring forms take the field away rather than leaving it unticked,
        // so saying it is switched off would send a merchant looking for a box
        // that is not on the screen.
        ( $qpp['userecurring'] ? esc_html__( 'A form set to take recurring payments cannot also take a coupon.', 'quick-paypal-payments' ) : '' )
    );
    $content .= '<form method="post" action="">
    <p class="description"><strong>' . esc_html__( 'Note:', 'quick-paypal-payments' ) . '</strong> ' . esc_html__( 'Leave fields blank if you don\'t want to use them', 'quick-paypal-payments' ) . '</p>
    <p>' . esc_html__( 'Number of Coupons:', 'quick-paypal-payments' ) . ' <input type="text" name="couponnumber" value="' . esc_attr( $coupon['couponnumber'] ) . '" style="width:4em"></p>
    <table>
    <tr><td>' . esc_html__( 'Code', 'quick-paypal-payments' ) . '</td><td>' . esc_html__( 'Percentage', 'quick-paypal-payments' ) . '</td><td>' . esc_html__( 'Fixed Amount', 'quick-paypal-payments' ) . '</td><td>' . esc_html__( 'Qty', 'quick-paypal-payments' ) . '<br>' . esc_html__( '(remaining / blank unlimited)', 'quick-paypal-payments' ) . '</td></tr>';
    for ($i = 1; $i <= $coupon['couponnumber']; $i++) {
        $percent = ( $coupon['coupontype' . $i] == 'percent' . $i ? 'checked' : '' );
        $fixed = ( $coupon['coupontype' . $i] == 'fixed' . $i ? 'checked' : '' );
        $content .= '<tr><td><input placeholder="' . esc_attr__( 'Enter Coupon Code', 'quick-paypal-payments' ) . '" type="text" name="code' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'code' . $i ) ) . '" /></td>
        <td><input type="radio" name="coupontype' . $i . '" value="percent' . $i . '" ' . esc_attr( $percent ) . ' />&nbsp;
        <input type="text" style="width:4em;padding:2px" label="couponpercent' . $i . '" name="couponpercent' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'couponpercent' . $i ) ) . '" /> %</td>
        <td><input type="radio" name="coupontype' . $i . '" value="fixed' . $i . '" ' . esc_attr( $fixed ) . ' />&nbsp;' . $b . '&nbsp;
        <input type="text" style="width:4em;padding:2px" label="couponfixed' . $i . '" name="couponfixed' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'couponfixed' . $i ) ) . '" /> ' . $a . '</td>
        <td><input type="text" style="width:3em;padding:2px" name="qty' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'qty' . $i ) ) . '" /></td>
                    <td><input type="checkbox" name="expired' . $i . '" value="' . esc_attr( qpp_get_element( $coupon, 'expired' . $i ) ) . '" ' . checked( qpp_get_element( $coupon, 'expired' . $i ), '1', false ) . ' /></td></tr>
    </tr>';
    }
    $content .= '</table>
    <h2>' . esc_html__( 'Invalid Coupon Code Message', 'quick-paypal-payments' ) . '</h2>
    <input id="couponerror" type="text" name="couponerror" value="' . esc_attr( $coupon['couponerror'] ) . '" /></p>
    <h2>' . esc_html__( 'Expired Coupon Message', 'quick-paypal-payments' ) . '</h2>
    <input id="couponexpired" type="text" name="couponexpired" value="' . esc_attr( $coupon['couponexpired'] ) . '" /></p>
    <h2>' . esc_html__( 'Coupon Code Autofill', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'You can add coupon codes to URLs which will autofill the field. The URL format is: mysite.com/mypaymentpage/?coupon=code. The code you set will appear on the form with the following caption:', 'quick-paypal-payments' ) . '<br>
    <input id="couponget" type="text" name="couponget" value="' . esc_attr( $coupon['couponget'] ) . '" /></p>
    <h2>' . esc_html__( 'Clone Coupon Settings', 'quick-paypal-payments' ) . '</h2>
    <p><input  type="checkbox" name="duplicate"' . checked( $coupon['duplicate'], 'checked', false ) . ' value="checked" /> ' . esc_html__( 'Duplicate coupon codes across all forms', 'quick-paypal-payments' ) . '</p>
    <p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Save Changes', 'quick-paypal-payments' ) . '" /> <input type="submit" name="Reset" class="button-primary" style="color: #FFF;" value="' . esc_attr__( 'Reset', 'quick-paypal-payments' ) . '" onclick="return window.confirm( \'' . esc_js( __( 'Are you sure you want to reset the coupon codes?', 'quick-paypal-payments' ) ) . '\' );"/></p>';
    $content .= wp_nonce_field( "save_qpp" );
    $content .= '</form>
    </div>
    <div class="qpp-options" style="float:right;">
    <h2>' . esc_html__( 'Coupon Check', 'quick-paypal-payments' ) . '</h2>
    <p>' . esc_html__( 'Test your coupon codes.', 'quick-paypal-payments' ) . '</p>';
    if ( $id ) {
        $form = ' form="' . $id . '"';
    }
    $args = array(
        'form'   => $id,
        'id'     => '',
        'amount' => '',
    );
    $content .= qpp_loop( $args, true );
    $content .= qpp_examples_footer() . '</div></div>';
    echo wp_kses( $content, qpp_allowed_html() );
}

function qpp_delete_everything() {
    $qpp_setup = qpp_get_stored_setup();
    $arr = explode( ",", $qpp_setup['alternative'] );
    foreach ( $arr as $item ) {
        qpp_delete_things( $item );
    }
    qpp_delete_things( '' );
    delete_option( 'qpp_setup' );
    delete_option( 'qpp_curr' );
    delete_option( 'qpp_message' );
}

function qpp_delete_things(  $id  ) {
    delete_option( 'qpp_options' . $id );
    delete_option( 'qpp_send' . $id );
    delete_option( 'qpp_error' . $id );
    delete_option( 'qpp_style' . $id );
}

function qpp_change_form(  $qpp_setup  ) {
    $content = '';
    if ( $qpp_setup['alternative'] ) {
        $content .= '<form style="margin-top: 8px" method="post" action="" >';
        $arr = explode( ",", $qpp_setup['alternative'] );
        sort( $arr );
        foreach ( $arr as $item ) {
            if ( $qpp_setup['current'] == $item ) {
                $checked = 'checked';
            } else {
                $checked = '';
            }
            if ( $item == '' ) {
                $formname = 'default';
                $item = '';
            } else {
                $formname = $item;
            }
            $content .= '<input  type="radio" name="current" value="' . esc_attr( $item ) . '" ' . checked( $item, $qpp_setup['current'], false ) . '>' . esc_attr( $formname ) . ' &nbsp;';
        }
        $content .= wp_nonce_field(
            'qpp_save',
            '_wpnonce',
            true,
            false
        );
        $content .= '<input type="hidden" name="alternative" value = "' . esc_attr( $qpp_setup['alternative'] ) . '" />
        <input type="hidden" name="email" value = "' . esc_attr( $qpp_setup['email'] ) . '" />&nbsp;&nbsp;
        <input type="submit" name="Select" class="button-secondary" value="Select Form" />
        </form>';
    }
    return $content;
}

function qpp_change_form_update() {
    if ( isset( $_POST['Select'] ) ) {
        check_admin_referer( 'qpp_save' );
        $qpp_setup = qpp_get_stored_setup();
        /*
         * current is a radio, and none is checked when the selected form has
         * since been deleted, so it is not always posted. The other two are
         * hidden fields carried through the same form.
         */
        $qpp_setup['current'] = sanitize_text_field( wp_unslash( $_POST['current'] ?? '' ) );
        $qpp_setup['alternative'] = sanitize_text_field( wp_unslash( $_POST['alternative'] ?? $qpp_setup['alternative'] ) );
        $qpp_setup['email'] = sanitize_text_field( wp_unslash( $_POST['email'] ?? $qpp_setup['email'] ) );
        update_option( 'qpp_setup', $qpp_setup );
    }
}

function qpp_generate_csv() {
    $qpp_setup = qpp_get_stored_setup();
    $ipn = qpp_get_stored_ipn();
    if ( isset( $_POST['download_qpp_csv'] ) ) {
        check_admin_referer( 'qpp_download_form', 'qpp_download_form_nonce' );
        // The export contains every payer's name, email, address and phone. A
        // nonce proves the request came from our form, not that the sender is
        // allowed the data, so the capability is checked as well.
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export payment records.', 'quick-paypal-payments' ), '', array(
                'response' => 403,
            ) );
        }
        $id = $_POST['formname'];
        $filename = urlencode( $id . '.csv' );
        if ( $id == '' ) {
            $filename = urlencode( 'default.csv' );
        }
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Type: text/csv' );
        $outstream = fopen( "php://output", 'w' );
        $message = get_option( 'qpp_messages' . $id );
        $messageoptions = qpp_get_stored_msg();
        if ( !is_array( $message ) ) {
            $message = array();
        }
        $qpp = qpp_get_stored_options( $id );
        $address = qpp_get_stored_address( $id );
        $headerrow = array();
        array_push( $headerrow, esc_html__( 'Date Sent', 'quick-paypal-payments' ) );
        array_push( $headerrow, $qpp['inputreference'] );
        array_push( $headerrow, $qpp['quantitylabel'] );
        array_push( $headerrow, esc_html__( 'Amount', 'quick-paypal-payments' ) );
        if ( $qpp['use_stock'] ) {
            array_push( $headerrow, $qpp['stocklabel'] );
        }
        if ( $qpp['use_options'] ) {
            array_push( $headerrow, $qpp['optionlabel'] );
        }
        if ( $qpp['usecoupon'] ) {
            array_push( $headerrow, $qpp['couponblurb'] );
        }
        if ( $qpp['use_message'] ) {
            array_push( $headerrow, $qpp['messagelabel'] );
        }
        if ( $messageoptions['showaddress'] ) {
            if ( $address['email'] ) {
                array_push( $headerrow, $address['email'] );
            }
            if ( $address['firstname'] ) {
                array_push( $headerrow, $address['firstname'] );
            }
            if ( $address['lastname'] ) {
                array_push( $headerrow, $address['lastname'] );
            }
            if ( $address['address1'] ) {
                array_push( $headerrow, $address['address1'] );
            }
            if ( $address['address2'] ) {
                array_push( $headerrow, $address['address2'] );
            }
            if ( $address['city'] ) {
                array_push( $headerrow, $address['city'] );
            }
            if ( $address['state'] ) {
                array_push( $headerrow, $address['state'] );
            }
            if ( $address['zip'] ) {
                array_push( $headerrow, $address['zip'] );
            }
            if ( $address['country'] ) {
                array_push( $headerrow, $address['country'] );
            }
            if ( $address['night_phone_b'] ) {
                array_push( $headerrow, $address['night_phone_b'] );
            }
        }
        if ( $ipn['ipn'] ) {
            array_push( $headerrow, 'Paid' );
        }
        fputcsv(
            $outstream,
            $headerrow,
            ',',
            '"'
        );
        foreach ( array_reverse( $message ) as $value ) {
            /*
             * Rows stored before a field was switched on do not carry it, so
             * every read below was a warning on an older export. field0 is the
             * timestamp and is not part of the value map.
             */
            $value = array_merge( array(
                'field0' => '',
            ), array_fill_keys( array_map( function ( $n ) {
                return 'field' . $n;
            }, range( 1, 22 ) ), '' ), ( is_array( $value ) ? $value : array() ) );
            $cells = array();
            array_push( $cells, qpp_wp_date( 'Y-m-d H:i:s', $value['field0'] ) );
            array_push( $cells, $value['field1'] );
            array_push( $cells, $value['field2'] );
            array_push( $cells, $value['field3'] );
            if ( $qpp['use_stock'] ) {
                $value['field4'] = ( $value['field4'] != qpp_get_element( $qpp, 'stocklabel' ) ? $value['field4'] : '' );
                array_push( $cells, $value['field4'] );
            }
            if ( $qpp['use_options'] ) {
                $value['field5'] = ( $value['field5'] != qpp_get_element( $qpp, 'optionlabel' ) ? $value['field5'] : '' );
                array_push( $cells, $value['field5'] );
            }
            if ( $qpp['usecoupon'] ) {
                $value['field6'] = ( $value['field6'] != qpp_get_element( $qpp, 'couponblurb' ) ? $value['field6'] : '' );
                array_push( $cells, $value['field6'] );
            }
            if ( $qpp['use_message'] ) {
                $value['field19'] = ( $value['field19'] != qpp_get_element( $qpp, 'messagelabel' ) ? $value['field19'] : '' );
                array_push( $cells, $value['field19'] );
            }
            if ( $messageoptions['showaddress'] ) {
                if ( $address['email'] ) {
                    $value['field8'] = ( $value['field8'] != $address['email'] ? $value['field8'] : '' );
                    array_push( $cells, $value['field8'] );
                }
                if ( $address['firstname'] ) {
                    $value['field9'] = ( $value['field9'] != $address['firstname'] ? $value['field9'] : '' );
                    array_push( $cells, $value['field9'] );
                }
                if ( $address['lastname'] ) {
                    $value['field10'] = ( $value['field10'] != $address['lastname'] ? $value['field10'] : '' );
                    array_push( $cells, $value['field10'] );
                }
                if ( $address['address1'] ) {
                    $value['field11'] = ( $value['field11'] != $address['address1'] ? $value['field11'] : '' );
                    array_push( $cells, $value['field11'] );
                }
                if ( $address['address2'] ) {
                    $value['field12'] = ( $value['field12'] != $address['address2'] ? $value['field12'] : '' );
                    array_push( $cells, $value['field12'] );
                }
                if ( $address['city'] ) {
                    $value['field13'] = ( $value['field13'] != $address['city'] ? $value['field13'] : '' );
                    array_push( $cells, $value['field13'] );
                }
                if ( $address['state'] ) {
                    $value['field14'] = ( $value['field14'] != $address['state'] ? $value['field14'] : '' );
                    array_push( $cells, $value['field14'] );
                }
                if ( $address['zip'] ) {
                    $value['field15'] = ( $value['field15'] != $address['zip'] ? $value['field15'] : '' );
                    array_push( $cells, $value['field15'] );
                }
                if ( $address['country'] ) {
                    $value['field16'] = ( $value['field16'] != $address['country'] ? $value['field16'] : '' );
                    array_push( $cells, $value['field16'] );
                }
                if ( $address['night_phone_b'] ) {
                    $value['field17'] = ( $value['field17'] != $address['night_phone_b'] ? $value['field17'] : '' );
                    array_push( $cells, $value['field17'] );
                }
            }
            if ( $ipn['ipn'] ) {
                $paid = ( $value['field18'] == 'Paid' ? 'Paid' : '' );
                array_push( $cells, $paid );
            }
            fputcsv(
                $outstream,
                $cells,
                ',',
                '"'
            );
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the PHP output stream for a CSV download, not a filesystem write.
        fclose( $outstream );
        exit;
    }
}

function qpp_settings_init() {
    qpp_generate_csv();
    return;
}

function qpp_scripts_init(  $hook  ) {
    if ( $hook != 'settings_page_quick-paypal-payments' && $hook != 'toplevel_page_quick-paypal-payments-messages' ) {
        return;
    }
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    // Bundled copy: assets must not be loaded from a remote host such as the Google CDN.
    wp_enqueue_style(
        'jquery-style',
        plugins_url( 'jquery-ui.css', __FILE__ ),
        array(),
        '1.8.9'
    );
    wp_enqueue_style(
        'qpp_settings',
        plugins_url( 'settings.css', __FILE__ ),
        array(),
        qpp_asset_version( __DIR__ . '/settings.css' )
    );
    /*
     * Go through the front end registration rather than naming the files again
     * here. Registering payments.js a second time by URL gave the admin copy
     * neither its dependencies nor the localised qpp_data, so the Error Checker
     * on the validation screen logged "qpp_data.ajax_url is not available" and
     * its nonce refresh and ajax submit did nothing. qpp_loop() then found the
     * handle already registered and skipped the registration that would have
     * fixed it.
     */
    if ( !wp_script_is( 'qpp_script', 'registered' ) ) {
        qpp_register_scripts();
    }
    wp_enqueue_style( 'qpp_style' );
    wp_enqueue_media();
    wp_enqueue_script(
        'qpp-media',
        plugins_url( 'media.js', __FILE__ ),
        array('jquery', 'wp-color-picker'),
        qpp_asset_version( __DIR__ . '/media.js' ),
        true
    );
    wp_localize_script( 'qpp-media', 'qpp_account_check', array(
        'url'     => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'qpp_check_account' ),
        'working' => __( 'Checking with PayPal...', 'quick-paypal-payments' ),
    ) );
    wp_enqueue_script( 'qpp_script' );
}

add_action( 'admin_enqueue_scripts', 'qpp_scripts_init' );
function qpp_page_init() {
    add_options_page(
        'Paypal Payments',
        'Paypal Payments',
        'manage_options',
        'quick-paypal-payments',
        'qpp_tabbed_page'
    );
}

function qpp_admin_notice(  $message = '', $type = 'updated'  ) {
    if ( !empty( $message ) ) {
        // 'error' is red and not dismissible, which is what a wrong PayPal
        // account deserves. Anything else keeps the old green box.
        $class = ( 'error' === $type ? 'notice notice-error' : 'updated' );
        echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
    }
}

/**
 * Warns on every plugin screen while the PayPal account is unusable.
 *
 * A mistyped account is the most common support question this plugin gets. It
 * shows up as a customer reaching PayPal and being turned away by a page that
 * says nothing useful, and the merchant only hears about it if that customer
 * bothers to tell them. Every form on the site is dead until it is fixed, so
 * this is worth saying loudly and on every visit, not once after a save.
 *
 * Named forms are checked too, because Send Options carries a per form address
 * that overrides the site wide one.
 */
function qpp_account_admin_notice() {
    $screen = ( function_exists( 'get_current_screen' ) ? get_current_screen() : null );
    if ( !$screen || !preg_match( '#quick-paypal-payments#i', $screen->base ) ) {
        return;
    }
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }
    $setup = qpp_get_stored_setup();
    $forms = array_unique( explode( ',', (string) $setup['alternative'] ) );
    if ( !in_array( '', $forms, true ) ) {
        $forms[] = '';
    }
    $broken = array();
    foreach ( $forms as $form ) {
        $problem = qpp_paypal_account_problem( qpp_paypal_account_for( $setup, qpp_get_stored_send( $form ) ) );
        if ( '' === $problem ) {
            continue;
        }
        $name = ( '' === $form ? __( 'the default form', 'quick-paypal-payments' ) : $form );
        $broken[$problem][] = $name;
    }
    foreach ( $broken as $problem => $names ) {
        printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s<br />%s</p></div>',
            esc_html__( 'Quick PayPal Payments cannot take a payment.', 'quick-paypal-payments' ),
            esc_html( $problem ),
            sprintf( 
                /* translators: %s is a comma separated list of form names. */
                esc_html__( 'Affects: %s.', 'quick-paypal-payments' ),
                esc_html( implode( ', ', $names ) )
             )
        );
    }
}

add_action( 'admin_notices', 'qpp_account_admin_notice' );
/**
 * Checks a PayPal account as it is typed.
 *
 * The button posts the page and is the fallback with no javascript. This is the
 * same check, run when the field changes, so a typo is caught while the person
 * who made it is still looking at it.
 */
function qpp_ajax_check_paypal_account() {
    check_ajax_referer( 'qpp_check_account' );
    if ( !current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array(
            'message' => __( 'You cannot do that.', 'quick-paypal-payments' ),
        ) );
    }
    $email = ( isset( $_POST['email'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['email'] ) ) ) : '' );
    $setup = qpp_get_stored_setup();
    wp_send_json_success( qpp_check_paypal_account( $email, !empty( $setup['sandbox'] ) ) );
}

add_action( 'wp_ajax_qpp_check_paypal_account', 'qpp_ajax_check_paypal_account' );
function qpp_admin_pages() {
    $records = function () {
        require_once 'messages.php';
    };
    add_menu_page(
        'Payments',
        'Payments',
        'manage_options',
        'quick-paypal-payments-messages',
        $records,
        'dashicons-cart'
    );
    // Names the automatically created first child for what it actually is,
    // rather than repeating the menu title.
    add_submenu_page(
        'quick-paypal-payments-messages',
        esc_html__( 'Payment Records', 'quick-paypal-payments' ),
        esc_html__( 'Payment Records', 'quick-paypal-payments' ),
        'manage_options',
        'quick-paypal-payments-messages',
        $records
    );
    /*
     * The settings screen stays where WordPress puts a settings screen, under
     * Settings. This is a second route to the same page from the plugin's own
     * menu, because that is where someone looks for the settings of the thing
     * they are already looking at.
     */
    add_submenu_page(
        'quick-paypal-payments-messages',
        esc_html__( 'Settings', 'quick-paypal-payments' ),
        esc_html__( 'Settings', 'quick-paypal-payments' ),
        'manage_options',
        'options-general.php?page=quick-paypal-payments'
    );
}

function qpp_plugin_row_meta(  $links, $file = ''  ) {
    /** @var \Freemius $quick_paypal_payments_fs Freemius global object. */
    global $quick_paypal_payments_fs;
    if ( false !== strpos( $file, '/quick-paypal-payments.php' ) ) {
        $new_links[] = '<a href="https://fullworks.net/docs/quick-paypal-payments/"><strong>Help and Support</strong></a>';
        if ( false === $quick_paypal_payments_fs->is_plan_or_trial( 'platinum' ) ) {
            global $quick_paypal_payments_fs;
            // One link either way. The trial text is empty when Freemius has no
            // trial configured, so it cannot be the only label or the row would
            // carry a link with nothing in it.
            $new_links[] = '<a href="' . esc_url( qpp_upgrade_url() ) . '"><strong>Upgrade</strong></a>';
        }
        $links = array_merge( $links, $new_links );
    }
    return $links;
}
