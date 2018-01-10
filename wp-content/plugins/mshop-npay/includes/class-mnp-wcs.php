<?php



if ( ! class_exists( 'MNP_Wcs' ) ) {

	class MNP_Wcs {
		static function init() {
			if ( MNP_Manager::is_production() || ( MNP_Manager::is_sandbox() && MNP_Manager::is_test_user() ) ) {
				add_action( 'wp_head', __CLASS__ . '::wp_head' );
				add_action( 'wp_footer', __CLASS__ . '::wp_footer' );
			}
		}
		static function wp_head() {

			$home_url = parse_url( home_url() );
			$server = $home_url['host'];

			if ( 0 === strpos( $server, 'www.' ) ) {
				$server = substr( $server, 4, strlen( $server ) - 4 );
			}

			?>
			<script type="text/javascript" src="<?php echo( is_ssl() ? 'https' : 'http' ); ?>://wcs.naver.net/wcslog.js"></script>
			<script type="text/javascript">
				if (!wcs_add)
					var wcs_add = {};
				wcs_add["wa"] = "<?php echo MNP_Manager::common_auth_key(); ?>";
				wcs.inflow("<?php echo $server; ?>");
			</script>
			<?php
		}
		static function wp_footer() {
			?>
			<script type="text/javascript">
				jQuery(function ($) {
					$(document).ready(function () {
						wcs_do();
					})
				});
			</script>
			<?php
		}
	}

	MNP_Wcs::init();
}

