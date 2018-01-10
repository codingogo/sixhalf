<?php

require_once(ABSPATH . 'wp-admin/includes/user.php');

if ( ! class_exists( 'MSSHelper' ) ) {

    Class MSSHelper {
        private static $msh_editable_roles = array();
        public static function get_settings( $setting, $postid = null ) {
            $values = array();

            if ( ! empty( $setting['id'] ) ) {
                $value = $postid ? get_post_meta( $postid, $setting['id'], true ) : get_option( $setting['id'], isset( $setting['default'] ) ? $setting['default'] : '' );
                if ( empty( $value ) && isset( $setting['default'] ) ) {
                    $value = $setting['default'];
                }

                $values[ $setting['id'] ] = apply_filters( 'msshelper_get_' . $setting['id'], $value );
            }

            if ( ! empty( $setting['elements'] ) && empty( $setting['repeater'] ) ) {
                foreach ( $setting['elements'] as $element ) {
                    $values = array_merge( $values, self::get_settings( $element, $postid ) );
                }
            }

            return $values;
        }

        public static function update_settings( $setting, $postid = null ) {
            if ( ! empty( $setting['id'] ) ) {
                if ( has_action( 'update_' . $setting['id'] ) ) {
                    do_action( 'update_' . $setting['id'] );
                } else {
                    if ( ! empty( $_REQUEST[ $setting['id'] ] ) ) {
                        $postid ? update_post_meta( $postid, $setting['id'], $_REQUEST[ $setting['id'] ] ) : update_option( $setting['id'], $_REQUEST[ $setting['id'] ] );
                    } else {
                        $postid ? delete_post_meta( $postid, $setting['id'] ) : delete_option( $setting['id'] );
                    }
                }
            }

            if ( ! empty( $setting['elements'] ) && empty( $setting['repeater'] ) ) {
                foreach ( $setting['elements'] as $element ) {
                    self::update_settings( $element, $postid );
                }
            }
        }
        public static function get_editable_roles( $filter_name = null ) {
            if ( empty( self::$msh_editable_roles['default'] ) ) {
                self::$msh_editable_roles['default']          = get_editable_roles();
                self::$msh_editable_roles['default']['guest'] = array(
                    'name' => __( '비회원', 'mshop-npay' )
                );
            }

            if ( empty( $filter_name ) ) {
                return self::$msh_editable_roles['default'];
            } else if ( ! empty( self::$msh_editable_roles[ $filter_name ] ) ) {
                return self::$msh_editable_roles[ $filter_name ];
            } else {
                $filters = array_filter( get_option( $filter_name, [ ] ), function ( $item ) {
                    return 'yes' === $item['enabled'];
                } );
                $keys = array_flip( array_map( function ( $role ) {
                    return $role['role'];
                }, $filters ) );
                self::$msh_editable_roles[ $filter_name ] = array_intersect_key( self::$msh_editable_roles['default'], $keys );

                return self::$msh_editable_roles[ $filter_name ];
            }
        }
        public static function get_role_based_rules( $option_name, $template, $options = array(), $filter_name = null, $postid = null ) {
            $editable_roles     = self::get_editable_roles( $filter_name );
            $editable_roles_key = array_keys( $editable_roles );
            $rules = ! empty( $option_name ) ? ( $postid ? get_post_meta( $postid, $option_name, true ) : get_option( $option_name, '[]' ) ) : $options;
            if ( ! is_array( $rules ) ) {
                $rules = array();
            }
            $rules = array_filter( $rules, function ( $rule ) use ( $editable_roles_key ) {
                return in_array( $rule['role'], $editable_roles_key );
            } );
            $rules_key = array_map( function ( $rule ) {
                return $rule['role'];
            }, $rules );
            $new_roles = array_diff( $editable_roles_key, $rules_key );

            foreach ( $editable_roles as $key => $value ) {
                if ( in_array( $key, $new_roles ) ) {
                    $rules[] = array_merge(
                        array(
                            'role' => $key,
                            'name' => $value['name']
                        ),
                        ! empty( $template ) ? $template : array()
                    );
                }
            }

            return $rules;
        }
    }

}
