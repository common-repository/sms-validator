<?php

if ( !class_exists( 'CSNetworks_Settings_API' ) ) {
    include_once dirname( __FILE__ ) . '/lib/class.settings-api.php';
}

/**
 * Admin options handler class
 *
 * @author CSNetworks
 */
class CSNetworks_SMS_Admin {

    private $settings_api;

    function __construct() {
        $this->settings_api = new CSNetworks_Settings_API();

        //plugin options
        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_options_page( __( 'SMS Verification', 'csnetworks' ), __( 'SMS Verification', 'csnetworks' ), 'install_plugins', 'csnetworks_sms', array($this, 'plugin_page') );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'csnetworks_sms_labels',
                'title' => __( 'Messages', 'csnetworks' )
            ),
            array(
                'id' => 'csnetworks_sms_options',
                'title' => __( 'Other Options', 'csnetworks' )
            ),
            array(
                'id' => 'csnetworks_sms_gateways',
                'title' => __( 'CS Networks Credentials', 'csnetworks' )
            ),
        );

        return apply_filters( 'csnetworks_sms_sections', $sections );
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public static function get_settings_fields() {
        $settings_fields = array();
        $gateways = array();
        $gateway_obj = CSNetworks_SMS_Gateways::instance();
        $registered_gateways = $gateway_obj->get_gateways();



        foreach ($registered_gateways as $gateway => $option) {
            $gateways[$gateway] = $option['label'];
        }

        $settings_fields['csnetworks_sms_labels'] = array(
            'sender_name' => array(
                'name' => 'sender_name',
                'label' => __( 'Sender Name', 'csnetworks' ),
                'default' => 'CS Networks'
            ),
            'mob_instruction' => array(
                'name' => 'mob_instruction',
                'label' => __( 'SMS Instruction', 'csnetworks' ),
                'default' => __( 'Enter your mobile number we will send verification code to', 'csnetworks' )
            ),
            'unlock_instruction' => array(
                'name' => 'unlock_instruction',
                'label' => __( 'Unlock Instruction', 'csnetworks' ),
                'default' => __( 'Please enter your verification code.', 'csnetworks' )
            ),
            'sms_text' => array(
                'name' => 'sms_text',
                'label' => __( 'SMS Text', 'csnetworks' ),
                'type' => 'textarea',
                'default' => __( 'Your verification code is: %CODE%', 'csnetworks' ),
                'desc' => __( 'will be displayed in SMS. <strong>%CODE%</strong> will be replaced by verification code', 'csnetworks' )
            ),
            'process_msg' => array(
                'name' => 'process_msg',
                'label' => __( 'Processing Message', 'csnetworks' ),
                'default' => __( 'Processing Verification code. Please wait...', 'csnetworks' )
            ),
            'sending_msg' => array(
                'name' => 'sending_msg',
                'label' => __( 'SMS Sending Message', 'csnetworks' ),
                'default' => __( 'Sending SMS Please wait...', 'csnetworks' )
            ),
            'error_msg' => array(
                'name' => 'error_msg',
                'label' => __( 'Error Message', 'csnetworks' ),
                'default' => __( 'Error: Something went wrong', 'csnetworks' )
            ),
            'success_msg' => array(
                'name' => 'success_msg',
                'label' => __( 'Success Message', 'csnetworks' ),
                'default' => __( 'Account Verified. Please wait', 'csnetworks' )
            ),
            'invalid_number' => array(
                'name' => 'invalid_number',
                'label' => __( 'Invalid Number Error', 'csnetworks' ),
                'default' => __( 'Invalid Number', 'csnetworks' )
            ),
            'sms_sent_msg' => array(
                'name' => 'sms_sent_msg',
                'label' => __( 'SMS Sent Success', 'csnetworks' ),
                'default' => __( 'SMS sent. Please enter your verification code', 'csnetworks' )
            ),
            'sms_sent_error' => array(
                'name' => 'sms_sent_error',
                'label' => __( 'SMS Sent Error', 'csnetworks' ),
                'default' => __( 'Unable to send sms. Contact admin', 'csnetworks' )
            ),
        );

        $settings_fields['csnetworks_sms_options'] = array(
            array(
                'name' => 'override_comment',
                'label' => __( 'Enable on comment form', 'csnetworks' ),
                'desc' => __( 'Enable user verification when posting comments', 'csnetworks' ),
                'type' => 'checkbox'
            ),
            array(
                'name' => 'register_form',
                'label' => __( 'Enable on Registration', 'csnetworks' ),
                'desc' => __( 'Enable verification during registration process. Customer will be verified on each registration', 'csnetworks' ),
                'type' => 'checkbox'
            ),
        );

        $settings_fields['csnetworks_sms_gateways'] = array(
            array(
                'name' => 'active_gateway',
                'label' => __( 'CS Networks Settings', 'csnetworks' ),
                'type' => 'select',
                'options' => $gateways
            ),
            array(
                'name' => 'csnetworks_header',
                'label' => '',
                'type' => 'html',
                'desc' => __( '<span style="font-size: 14px;font-weight: bold;"><a href="http://www.cs-networks.net" class="sms-gateway-api" data-rows="3">CS Networks Credentials</a></span>', 'csnetworks' )
            ),
            array(
                'name' => 'csnetworks_username',
                'label' => __( 'Username', 'csnetworks' )
            ),
            array(
                'name' => 'csnetworks_pass',
                'label' => __( 'Password', 'csnetworks' )
            ),
        );

        $gateway_toggle_js = '<script>(function($){$("#csnetworks_sms_gateways").on("click","a.sms-gateway-api",function(e){e.preventDefault();var self=$(this),rows=self.data("rows"),parent=self.parents("tr");var next=parent.nextAll();if(next.length){var elems=next.slice(0,rows);$(elems).each(function(ind,el){$(el).slideToggle()})}})})(jQuery);</script>';


        return apply_filters( 'csnetworks_sms_fields', $settings_fields );
    }

    function plugin_page() {
        echo '<div class="wrap">';
        settings_errors();

        echo '<div id="icon-themes" class="icon32"></div>';
        echo __( '<h2>SMS Verification</h2>', 'csnetworks' );
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

}

$sms_admin = new CSNetworks_SMS_Admin();
