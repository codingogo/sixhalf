<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MNP_Admin_Dashboard' ) ) :
	class MNP_Admin_Dashboard {
		public function __construct() {
			if ( current_user_can( 'manage_options' ) ) {
				add_action( 'wp_dashboard_setup', array ( $this, 'init' ), 1 );
			}
		}
		public function init() {
			wp_add_dashboard_widget( 'mnp_dashboard_notices', __( '엠샵 NPay 공지사항', 'mshop-npay' ), array (
				$this,
				'notices'
			) );
		}
		public function notices() {
			$url = 'http://pgall.co.kr/msb-get-posts/?bid=plugin-notices&mbcat=mshop-common,' . MNP()->slug();

			$response = wp_remote_get(
				$url,
				array (
					'timeout' => 5
				)

			);

			if ( ! is_wp_error( $response ) ) {
				$result = json_decode( $response['body'] );

				if ( $result && $result->success ) {
					$posts = json_decode( $result->data );

					?>
					<style>
						table.mnp-notices {
							table-layout: fixed;
							width: 100%;
						}

						table.mnp-notices td.title {
							overflow: hidden;
							white-space: nowrap;
						}

						table.mnp-notices td.date {
							width: 90px;
						}
					</style>
					<?php
					// TODO : 스타일 변경
					echo '<table class="mnp-notices">';
					foreach ( $posts as $post ) {

						echo '<tr>';
						echo sprintf( '<td class="title"><a target="_blank" href="%s">%s</a></td>', $post->permalink, $post->post_title );
						echo sprintf( '<td class="date">| %s</td>', date( 'Y-m-d', strtotime( $post->date ) ) );
						echo '</tr>';
					}
					echo '</table>';
				} else {
					echo '<p>' . __( '등록된 공지사항이 없습니다.', 'mshop-npay' ) . '</p>';
				}
			}
		}

	}

endif;

return new MNP_Admin_Dashboard();
