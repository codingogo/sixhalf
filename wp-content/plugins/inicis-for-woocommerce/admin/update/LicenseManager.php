<?php

if ( !class_exists('INICIS_PG_LicenseManager') ) {

    Class INICIS_PG_LicenseManager
    {
        private $rev = '20151113';

        private $slug;

        private $debug = false;

        private $license_server_url;
        private $update_server_url;

        public function __construct($slug, $dir, $file, $debug = false)
        {
            $this->slug = $slug;
            $this->debug = $debug;

            $this->license_server_url = 'http://lic.codemshop.com/manager_' . $this->rev;
            $this->update_server_url  = 'http://lic.codemshop.com/update';

            require 'plugin-updates/plugin-update-checker.php';

            $license_info = get_option('msl_license_' . $this->slug, null);
            if ($license_info) {
                $license_info = json_decode($license_info);
            }

            $this->update_checker = PucFactory::buildUpdateChecker(
                $this->update_server_url . '?action=get_metadata&slug=' . $this->slug . '&license_key=' . ($license_info ? $license_info->license_key : '') . '&activation_key=' . ($license_info ? $license_info->activation_key : '') . '&domain=' . ($license_info ? $license_info->site_url : ''),
                $file,
                $this->slug
            );

            add_action("in_plugin_update_message-" . basename($dir) . '/' . basename($file), array($this , "in_plugin_update_message"), 10, 2);
            add_action('wp_ajax_msl_activation_' . $this->slug, array(&$this, 'msl_activation'));
            add_action('wp_ajax_msl_verify_' . $this->slug, array(&$this, 'msl_verify'));
            add_action('wp_ajax_msl_reset_' . $this->slug, array(&$this, 'msl_reset'));

            add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
        }

        public function admin_enqueue_scripts(){
            wp_enqueue_style( 'mshop-license-checker', plugin_dir_url( __FILE__ ) . 'assets/css/license-manager.css' );

        }

        public function in_plugin_update_message($plugin_data, $r)
        {
            if( !empty($plugin_data['upgrade_notice']) ) {
                echo '<br>' . $plugin_data['upgrade_notice'] . '</br>';
            }
        }

        public function msl_activation()
        {
            $license_key = $_REQUEST['msl_license_key'];
            $site_url = site_url();
            $site_url = preg_replace('#^https?://#', '', $site_url);

            $data = apply_filters( 'mshop_license_info_' . $this->slug, null );

            $response = wp_remote_post( $this->license_server_url, array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => array( 'action' => 'activation', 'slug' => $this->slug, 'license_key' => $license_key, 'domain' => $site_url, 'data' => json_encode($data) ),
                    'cookies' => array()
                )
            );

            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                $result = $error_message;
            } else {
                $result = json_decode($response['body']);
            }

            if (!empty( $result) && $result->result >= 0) {
                $license_info = array(
                    'site_url' => $site_url,
                    'slug' => $this->slug,
                    'license_key' => $license_key,
                    'activation_key' => $result->activation_key,
                    'expire_date' => $result->expire_date,
                    'status' => $result->result,
                    'data' => !empty( $_REQUEST['msl_data'] ) ? json_encode( $_REQUEST['msl_data'] ) : ''
                );

                update_option('msl_license_' . $this->slug, json_encode($license_info));
                wp_send_json_success(array('message' => $result->notice, 'licenseInfo' => $license_info ) );
            } else {
                wp_send_json_error(array('message' => !empty( $result->notice ) ? $result->notice : '오류가 발생했습니다. 잠시 후 다시 시도해주세요.' ));
            }
        }

        public function msl_reset()
        {
            delete_option('msl_license_' . $this->slug);

            wp_send_json_success();
        }

        public function msl_verify()
        {
            ob_start();

            $license_info = get_option('msl_license_' . $this->slug, null);
            $site_url = site_url();
            $site_url = preg_replace('#^https?://#', '', $site_url);
            $data = apply_filters( 'mshop_license_info_' . $this->slug, null );

            if ($license_info) {
                $license_info = json_decode($license_info);

                $response = wp_remote_post( $this->license_server_url, array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(),
                        'body' => array( 'action' => 'verify', 'slug' => $license_info->slug, 'license_key' => $license_info->license_key , 'domain' => $site_url, 'activation_key' =>  $license_info->activation_key, 'data' => json_encode( $data ) ),
                        'cookies' => array()
                    )
                );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    wp_send_json_error( '[9000] 라이센스정보를 확인할 수 없습니다. 잠시 후, 다시 시도해주세요.(' . $error_message . ')' );
                } else {
                    $result = json_decode($response['body']);

                    if ($result->result != 1) {
                        $license_info->status = $result->result;
                        update_option('msl_license_' . $this->slug, json_encode($license_info));
                    }

                    wp_send_json_success( $license_info );
                }
            }

            wp_send_json_error( '[90001] 라이센스정보가 없습니다.' );
        }

        public function load_activation_form()
        {
            wp_enqueue_script( 'jquery-ui-accordion' );
            wp_enqueue_script( 'jquery-blockui', plugin_dir_url(__FILE__) . 'assets/js/blockui/jquery.blockUI.js' );
            wp_enqueue_style( 'jquery-ui', '//code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css' );

            include( 'templates/html-license-activation-form.php' );
        }

        public function check_valid(){
            $license_info = get_option('msl_license_' . $this->slug, null);
            if( !empty( $license_info ) ){
                $license_info = json_decode( $license_info );
                if( $license_info->status >= 0){
                    return true;
                }
            }

            return false;
        }
    }
}