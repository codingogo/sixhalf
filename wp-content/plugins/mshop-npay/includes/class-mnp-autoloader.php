<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class MNP_Autoloader {
	private $include_path = '';
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( MNP_PLUGIN_FILE ) ) . '/includes/';
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
		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		if ( strpos( $class, 'mnp_') === FALSE ){
			return;
		}

		if ( strpos( $class, 'mnp_admin' ) === 0 ) {
			$this->load_file( $this->include_path . 'admin/' . $file );
			return;
		}else if ( strpos( $class, 'msshelper' ) === 0 ) {
			$this->load_file( $this->include_path . 'admin/setting-manager/mshop-setting-helper.php' );
			return;
		}elseif ( strpos( $class, 'mnp_settings' ) === 0 ) {
			$path = $this->include_path . 'admin/settings/';
		}elseif ( strpos( $class, 'mnp_message' ) === 0 ) {
			$path = $this->include_path . 'message/';
		}elseif ( strpos( $class, 'mnp_meta_box' ) === 0 ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		}

		if ( empty( $path ) || ( ! $this->load_file( $path . $file ) && strpos( $class, 'mnp_' ) === 0 ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new MNP_Autoloader();
