<?php
function inicis_pg_get_setting( $id ) {
	$class = 'INICIS_PG_Settings_' . ucfirst( $id );

	if ( class_exists( $class, true ) ) {
		return new $class;
	}

	return null;
}