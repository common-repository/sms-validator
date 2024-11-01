<?php

/**
 * Ajax request handler class
 *
 * @author CSNetworks
 */
class CSNetworks_SMS_Ajax {

    function __construct() {
        add_action( 'wp_ajax_send_sms', array($this, 'send_sms') );
        add_action( 'wp_ajax_nopriv_send_sms', array($this, 'send_sms') );

        add_action( 'wp_ajax_sms_verify_code', array($this, 'verify_code') );
        add_action( 'wp_ajax_nopriv_sms_verify_code', array($this, 'verify_code') );
    }

    function send_sms() {
        global $wpdb;

        $error = false;
        $status = "";
        $send_to = $_POST['mobile'];

        $gateway_obj = CSNetworks_SMS_Gateways::instance();
        $status = $gateway_obj->send( $send_to );

        if ( $status['success'] == true ) {
            $current_user = wp_get_current_user();

            if ( 0 != $current_user->ID ) {
                //Save Verification code and mobile number in user meta
                update_user_meta( $current_user->ID, 'csnetworkssms_mobile', $send_to );
                update_user_meta( $current_user->ID, 'csnetworkssms_referenceno', $status['code'] );
                update_user_meta( $current_user->ID, 'sms_verified', 0 );
            } else {
                /* Unregistered user */
                $code = md5( $status['code'] );
                setcookie( 'csnetworkssms_verify', $code, time() + 86400, '/' );
            }

            unset( $status['code'] );
        }

        echo json_encode( $status );
        exit;
    }

    function verify_code() {
        $error = true;
        $posted_code = $_POST['code'];
        $current_user = wp_get_current_user();

        //Get Reference from user meta
        if ( is_user_logged_in() ) {
            $user_ref_code = get_user_meta( $current_user->ID, 'csnetworkssms_referenceno', true );

            if ( $posted_code == $user_ref_code ) {
                $error = false;
                update_user_meta( $current_user->ID, 'csnetworkssms_referenceno', "" );
                update_user_meta( $current_user->ID, 'sms_verified', 1 );
            }
        } else {
            /* Unregistered user */

            if ( isset( $_COOKIE['csnetworkssms_verify'] ) ) {
                $hash = md5( $posted_code );
                if ( $hash == $_COOKIE['csnetworkssms_verify'] ) {
                    $error = false;
                    setcookie( 'csnetworkssms_verify', '1', time() + 86400, '/' );
                }
            }
        }

        if ( $error ) {
            $response = array(
                'success' => false,
                'message' => __( 'Unable to verify', 'csnetworks' )
            );
        } else {
            $response = array(
                'success' => true,
                'message' => __( 'You are verified successfully', 'csnetworks' )
            );
        }

        echo json_encode( $response );
        exit;
    }

}

$csnetworks_sms_ajax = new CSNetworks_SMS_Ajax();