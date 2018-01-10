<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MNP_Admin_Notices' ) ) :
	class MNP_Admin_Notices {

		public $option_name;
		public function __construct() {
			if ( current_user_can( 'manage_options' ) ) {

				$this->option_name = MNP()->slug() . '_admin_notices';

				add_action( 'admin_notices', array ( $this, 'admin_notices' ) );
			}
		}

		function get_admin_notices( $seqno = 0 ) {
			$url = 'http://pgall.co.kr/msb-get-posts/?bid=admin-notices&seqno=' . $seqno . '&mbcat=' . MNP()->slug();

			$response = wp_remote_get(
				$url,
				array (
					'timeout' => 5
				)
			);

			if ( ! is_wp_error( $response ) ) {
				$result = json_decode( $response['body'] );

				if ( $result && $result->success ) {
					$notices = array ();

					foreach ( json_decode( $result->data, true ) as $notice ) {
						$notices[ $notice['seqno'] ] = $notice;
					}

					return $notices;
				}

				return array ();
			} else {
				return $response;
			}

		}

		function admin_notices() {
			$notices = get_option( $this->option_name, array ( 'data' => array (), 'seqno' => 0, 'date' => null ) );
			if ( ! empty( $_REQUEST['dismiss-notice'] ) ) {
				$dismiss_id = $_REQUEST['notice-id'];

				$notices['data'] = array_filter( $notices['data'], function ( $notice ) use ( $dismiss_id ) {
					return $notice['seqno'] != $dismiss_id;
				} );

				update_option( $this->option_name, $notices, 'no' );
			}

			$recent_notices = null;

			if ( is_null( $notices['date'] ) ) {
				$recent_notices = $this->get_admin_notices();
				$notices['date']  = date( 'Y-m-d h:i:s' );
				update_option( $this->option_name, $notices, 'no' );
			} else {
				$last_date    = date_create( $notices['date'] );
				$current_date = date_create();
				$diff         = date_diff( $last_date, $current_date );

				if ( $diff->h >= 1 ) {
					$recent_notices = $this->get_admin_notices( $notices['seqno'] );
					$notices['date']  = date( 'Y-m-d h:i:s' );
					update_option( $this->option_name, $notices, 'no' );
				}
			}

			if ( ! is_wp_error( $recent_notices ) && $recent_notices ) {
				$notices['data']  = array_merge( $notices['data'], $recent_notices );
				$notices['date']  = date( 'Y-m-d h:i:s' );
				$notices['seqno'] = reset( $recent_notices )['seqno'];

				update_option( $this->option_name, $notices, 'no' );
			}

			if ( ! empty( $notices['data'] ) ) {
				?>
				<style>
					div.notice{
						margin: 10px 20px 10px 0px;
					}
					input[name=dismiss-notice] {
						font-size: 0.9em !important;
						padding: 2px 8px !important;
						line-height: 20px !important;
						height: 24px !important;
						margin-bottom: 10px !important;
					}
				</style>
				<?php
				foreach ( $notices['data'] as $key => $notice ) {
					if ( ! empty( $notice['version'] ) && version_compare( MNP_VERSION, $notice['version'] ) >= 0 ) {
						unset( $notices[ $key ] );
						update_option( $this->option_name, $notices, 'no' );
					} else if ( empty( $notice['version'] ) || version_compare( MNP_VERSION, $notice['version'] ) < 0 ) {
						if ( empty( $notice['dismissed'] ) ) {
							include( 'templates/admin-notice.php' );
						}
					}
				}
			}
		}
	}

endif;

return new MNP_Admin_Notices();
