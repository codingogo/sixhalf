<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class INICIS_PG_Autoloader {
	private $include_path = '';
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( INICIS_PG_PLUGIN_FILE ) ) . '/includes/';
	}
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			return true;
		}
		return false;
	}
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( strpos( $class, 'inicis_pg_') === FALSE &&  strpos( $class, 'msshelper') === FALSE ){
			return;
		}

		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		if ( strpos( $class, 'inicis_pg_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		}else if ( strpos( $class, 'inicis_pg_meta_box' ) === 0 ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		}else if ( strpos( $class, 'msshelper' ) === 0 ) {
			$this->load_file( $this->include_path . '/admin/setting-manager/mshop-setting-helper.php' );
			return;
		}elseif ( strpos( $class, 'inicis_pg_gateway' ) === 0 ) {
			$path = $this->include_path . 'gateways/mshop-inicis/';
		}elseif ( strpos( $class, 'inicis_pg_settings' ) === 0 ) {
			$path = $this->include_path . 'gateways/mshop-inicis/settings/';
		}

		if ( empty( $path ) || ( ! $this->load_file( $path . $file ) && strpos( $class, 'inicis_pg_' ) === 0 ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new INICIS_PG_Autoloader();