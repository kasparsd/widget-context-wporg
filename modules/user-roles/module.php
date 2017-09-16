<?php

// Check for Widget Context plugin
if ( ! class_exists( 'widget_context' ) )
    die;


// Go!
WidgetContextUserRole::instance();


class WidgetContextUserRole {

    private static $instance;
    private $wc;


    static function instance() {

        if ( ! self::$instance )
            self::$instance = new self();

        return self::$instance;

    }


    private function __construct() {

        $this->wc = widget_context::instance();

        add_filter( 'widget_contexts', array( $this, 'add_context' ) );

        add_filter( 'widget_context_control-user_roles', array( $this, 'context_controls' ), 10, 2 );

        add_filter( 'widget_context_check-user_roles', array( $this, 'context_check' ), 10, 2 );

    }


    function add_context( $contexts ) {

        $contexts[ 'user_roles' ] = array(
            'label' => __( 'User Roles', 'widget-context' ),
            'description' => __( 'Context based user roles', 'widget-context' ),
            'weight' => 10
        );

        return $contexts;

    }


    function context_check( $check, $settings ) {

        // Assume:
        //   "return $check" = Pass-through case
        //   "return true"   = Affirmative case

        if ( empty( $settings ) ) {

            return $check;
        }

        $current_user = wp_get_current_user();
        $is_logged_in = ( $current_user->ID !== 0 && $current_user->ID );

        //
        //  1. Logged-in/logged-out check:
        //

        // Check for logged-out user
        if( $settings['logged-out'] === '1' && $is_logged_in !== true ) {

            // Check for logged-out and user not auth'd, affirmative case
            return true;
        }

        // Check for logged-in user
        if( $settings['logged-in'] === '1' && $is_logged_in ) {

            // Check for logged-in and user is auth'd, affirmative case
            return true;
        }

        // Drop the auth settings before additional iteration
        unset( $settings['logged-in'], $settings['logged-out'] );

        //
        //  2. User role intersection check:
        //

        // Build whitelist of user roles that are allowed to see widget
        $role_check_array = array();

        foreach( $settings as $role_key => $setting_val ) {

            if( $setting_val === '1' ) {

                // Push role onto whitelist without "role-" prepend
                array_push( $role_check_array, substr( $role_key, 5 ) );
            }
        }

        if(
            // User is auth'd
            $is_logged_in &&

            // There are roles to check for
            ! empty( $role_check_array ) &&

            // The current auth'd user has roles to check
            is_array( $current_user->roles ) &&
            ! empty( $current_user->roles )
        ) {

            // Calculate an intersecting set of current user roles and roles to check against
            $roles_intersection = array_intersect( $current_user->roles, $role_check_array );

            if( ! empty( $roles_intersection ) ) {

                // Roles intersect, affirmative case
                return true;
            }
        }

        // No roles intersect, pass-through case
        return $check;
    }


    function context_controls( $control_args ) {

        $options = array();
        $out = array();

        // Grab editable roles and work down to a key => value assoc. array
        $pre_roles = $this->get_roles();
        $roles = array();

        foreach( $pre_roles as $key => $role ) {

            $roles[ $key ] = $role['name'];
        }

        // Alphabetically sort roles for best UIX
        asort( $roles );

        // First two options are the logged-in and logged-out options
        $options[ 'logged-in' ] = __( 'When user is logged in (all types, global)', 'widget-context' );
        $options[ 'logged-out' ] = __( 'When user is logged out (global)', 'widget-context' );

        // Add the roles that the user can currently edit as whitelist user role options
        foreach( $roles as $role_key => $role_name ) {

            $options[ 'role-' . $role_key ] = sprintf( __( 'When user has role "%s" (%s)', 'widget-context' ), $role_name, $role_key );
        }

        // Build the output array of checkbox input HTML
        foreach( $options as $option => $label ) {

            $out[] = $this->wc->make_simple_checkbox( $control_args, $option, $label );
        }

        return implode( '', $out );
    }


    /**
     * Get editable roles for user
     * - "editable_roles" filter solution from http://wordpress.stackexchange.com/a/1666
     * @return mixed|void
     */
    function get_roles() {

        global $wp_roles;
        return apply_filters( 'editable_roles', $wp_roles->roles );
    }
}
