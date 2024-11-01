<?php

/**
 * SMS Gateway handler class
 *
 * @author CSNetworks
 */
class CSNetworks_SMS_Gateways {

    private static $_instance;

    /**
     * Gateway slug
     *
     * @param string $provider name of the gateway
     */
    function __construct() {
        add_filter( 'csnetworks_sms_via_csnetworks', array($this, 'csnetworksAPI') );
    }

    public static function instance() {
        if ( !self::$_instance ) {
            self::$_instance = new CSNetworks_SMS_Gateways();
        }

        return self::$_instance;
    }


    function get_gateways() {
        $gateways = array(
            'csnetworks' => array('label' => 'CS Networks'),
        );

        return apply_filters( 'csnetworks_sms_gateways', $gateways );
    }

 
    function send( $to ) {

        $active_gateway = csnetworks_sms_get_option( 'active_gateway' );

        if ( empty( $active_gateway ) ) {
            $response = array(
                'success' => false,
                'message' => 'No active gateway found'
            );

            return $response;
        }

        $code = rand( 1000, 9999 );
        $sms_text = csnetworks_sms_get_option( 'sms_text' );
        $sms_text = str_replace( '%CODE%', $code, $sms_text );
        $sms_data = array('text' => $sms_text, 'to' => $to, 'code' => $code);
  
        $status = apply_filters( 'csnetworks_sms_via_' . $active_gateway, $sms_data );

        return $status;
    }


    /**
     * Sends SMS via CS Networks api
     *
     * @uses `csnetworks_sms_via_csnetworks` filter to fire
     *
     * @param type $sms_data
     * @return boolean
     */
    function csnetworksAPI( $sms_data ) {
        $response = array(
            'success' => false,
            'message' => csnetworks_sms_get_option( 'sms_sent_error' )
        );

        $username = csnetworks_sms_get_option( 'csnetworks_username' );
        $password = csnetworks_sms_get_option( 'csnetworks_pass' );
	$msg = urlencode($sms_data['text']);

        //bail out if nothing provided
        if ( empty( $username ) || empty( $password ) ) {
            return $response;
        }

        // auth call
        $baseurl = "http://api.cs-networks.net";

        $url = sprintf( '%s/bin/send?USERNAME=%s&PASSWORD=%s&DESTADDR=%s&MESSAGE=%s', $baseurl, $username, $password, $sms_data['to'], $msg );

            // do sendmsg call
            $ret = file( $url );
            $send = explode( "\n", $ret[0] );

            if ( $send[0] ) {
                $response = array(
                    'success' => true,
                    'code' => $sms_data['code'],
                    'message' => csnetworks_sms_get_option( 'sms_sent_msg' )
                );
            #}
                return $response;
	}


    }


}
