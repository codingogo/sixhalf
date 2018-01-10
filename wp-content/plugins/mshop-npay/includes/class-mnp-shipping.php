<?php



if ( ! class_exists( 'MNP_Shipping' ) ) {

	class MNP_Shipping {

		static $_free_shipping = null;
		static $_flat_rate = null;

		static function get_shipping_methods( $type ) {
			$shipping_methods = array();

			$shipping_zones = array_merge( array( WC_Shipping_Zones::get_zone( 0 ) ), WC_Shipping_Zones::get_zones() );
			foreach ( $shipping_zones as $shipping_zone ) {
				if ( ! $shipping_zone instanceof WC_Shipping_Zone ) {
					$shipping_zone = WC_Shipping_Zones::get_zone( $shipping_zone['zone_id'] );
				}

				foreach ( $shipping_zone->get_shipping_methods( true ) as $key => $method ) {
					if ( $method->id == $type ) {
						$shipping_methods[ $key ] = $method;
					}
				}
			}

			return $shipping_methods;
		}

		static function get_shipping_options( $type, $placeholder = null ) {
			$zones = array();

			if ( version_compare( WOOCOMMERCE_VERSION, '2.6.0', '>=' ) ) {
				if ( ! is_null( $placeholder ) ) {
					$zones[''] = $placeholder;
				}

				$shipping_zones = array_merge( array( WC_Shipping_Zones::get_zone( 0 ) ), WC_Shipping_Zones::get_zones() );
				foreach ( $shipping_zones as $shipping_zone ) {
					if ( ! $shipping_zone instanceof WC_Shipping_Zone ) {
						$shipping_zone = WC_Shipping_Zones::get_zone( $shipping_zone['zone_id'] );
					}
					foreach ( $shipping_zone->get_shipping_methods( true ) as $key => $method ) {
						if ( $method->id == $type ) {
							$zones[ $key ] = $shipping_zone->get_zone_name() . ' - ' . $method->get_title();
						}
					}
				}
			}

			return $zones;
		}

		static function get_shipping_method( $type ) {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.6.0', '>=' ) ) {
				$method_id = get_option( 'mshop-naverpay-' . str_replace( '_', '-', $type ) );

				$method = WC_Shipping_Zones::get_shipping_method( $method_id );

				if ( empty( $method ) ) {
					$shipping_methods = self::get_shipping_methods( $type );
					$method           = ! empty( $shipping_methods ) ? current( $shipping_methods ) : null;
				}
			} else {
				$shipping_methods = WC()->shipping()->load_shipping_methods();

				$shipping_methods = array_filter( $shipping_methods, function ( $method ) use ( $type ) {
					return $type == $method->id;
				} );

				$method = ! empty( $shipping_methods ) ? current( $shipping_methods ) : null;
			}

			return $method;
		}
		static function get_free_shipping_method() {
			if ( is_null( self::$_free_shipping ) ) {
				self::$_free_shipping = self::get_shipping_method( 'free_shipping' );
			}

			return self::$_free_shipping;
		}
		static function get_flat_rate_method() {
			if ( is_null( self::$_flat_rate ) ) {
				self::$_flat_rate = self::get_shipping_method( 'flat_rate' );
			}

			return self::$_flat_rate;
		}
		static function get_iv_shipping_fee( $product_ids, $postalcode, $address1 ) {
			$cart = new WC_Cart();

			foreach ( $product_ids as $product_id ) {
				$cart->add_to_cart( $product_id, 1 );
			}

			WC()->customer->shipping_postcode = $postalcode;

			add_filter( 'msiv_shipping_method', __CLASS__ . '::msiv_shipping_method', 10, 3 );
			add_filter( 'msiv_get_postcode', __CLASS__ . '::msiv_get_postcode', 10 );

			$fee = apply_filters( 'msiv_get_iv_shipping_fee', 0, $cart );

			remove_filter( 'msiv_shipping_method', __CLASS__ . '::msiv_shipping_method' );
			remove_filter( 'msiv_get_postcode', __CLASS__ . '::msiv_get_postcode' );

			return $fee;
		}
		static function msiv_get_postcode( $postcode ) {
			return WC()->customer->shipping_postcode;
		}
		static function msiv_shipping_method( $shipping_method, $chosen_method, $package ) {
			$free_shipping_requires = array(
					'min_amount',
					'either'
			);

			$free_shipping = self::get_free_shipping_method();
			$flat_rate     = self::get_flat_rate_method();

			if ( $free_shipping && 'yes' == $free_shipping->enabeld && ( empty( $free_shipping->requires ) || in_array( $free_shipping->requires, $free_shipping_requires ) ) ) {
				if ( $package['contents_cost'] >= $free_shipping->min_amount ) {
					return $free_shipping;
				}
			}

			if ( $flat_rate && 'yes' == $flat_rate->enabled ) {
				return $flat_rate;
			}

			return $shipping_method;
		}
		static function get_shipping_cost( $cart ) {
			add_filter( 'woocommerce_package_rates', __CLASS__ . '::woocommerce_package_rates', 10, 2 );
			add_filter( 'transient_shipping-transient-version', __CLASS__ . '::shipping_transient_version', 10, 2 );
			$cart->calculate_shipping();
			remove_filter( 'woocommerce_package_rates', __CLASS__ . '::woocommerce_package_rates' );
			remove_filter( 'transient_shipping-transient-version', __CLASS__ . '::shipping_transient_version' );

			return $cart->shipping_total;
		}
		static function shipping_transient_version( $value, $transient ) {
			$value += rand( 0, 9999999999 );

			return $value;
		}
		static function woocommerce_package_rates( $rates, $package ) {
			$shipping_method = null;

			$free_shipping = self::get_free_shipping_method();
			$flat_rate     = self::get_flat_rate_method();

			if ( $flat_rate && 'yes' == $flat_rate->enabled ) {
				$shipping_method = $flat_rate;
			} else if ( $free_shipping && 'yes' == $free_shipping->enabled ) {
				$shipping_method = $free_shipping;
			}

			if ( ! is_null( $shipping_method ) ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '2.6.0', '>=' ) ) {
					return $shipping_method->get_rates_for_package( $package );
				} else {
					$shipping_method->rates = array();
					if ( $shipping_method->is_available( $package ) && ( empty( $package['ship_via'] ) || in_array( $shipping_method->id, $package['ship_via'] ) ) ) {
						$shipping_method->calculate_shipping( $package );
					}

					return array( $shipping_method->id => current( $shipping_method->rates ) );
				}

			} else {
				return array();
			}
		}
		public static function get_shipping_policy_surcharge_by_area() {
			include_once( 'naverpay/ShippingPolicySurchargeByArea.php' );

			if ( 'yes' == get_option( 'mshop-naverpay-use-additional-fee', 'no' ) ) {
				$fee_mode = get_option( 'mshop-naverpay-additional-fee-mode', MNP_Manager::ADDITIONAL_FEE_REGION );

//				if ( MNP_Manager::ADDITIONAL_FEE_REGION == $fee_mode ) {
					$splitUnit  = get_option( 'mshop-naverpay-additional-fee-region' );
					$area2Price = get_option( 'mshop-naverpay-additional-fee-region-2' );
					$area3Price = get_option( 'mshop-naverpay-additional-fee-region-3' );

					return new ShippingPolicySurchargeByArea( false, $splitUnit, $area2Price, $area3Price );
//				} else if ( MNP_Manager::ADDITIONAL_FEE_API == $fee_mode ) {
//					return new ShippingPolicySurchargeByArea( true, null, null, null );
//				}
			}

			return null;
		}
		static function get_fee_type() {
			$free_shipping_requires = array(
					'min_amount',
					'either'
			);

			$free_shipping = self::get_free_shipping_method();
			$flat_rate     = self::get_flat_rate_method();

			if ( $free_shipping && 'yes' == $free_shipping->enabled ) {
				if ( $flat_rate && 'yes' == $flat_rate->enabled ) {
					if ( empty( $free_shipping->requires ) || in_array( $free_shipping->requires, $free_shipping_requires ) ) {
						return ShippingPolicy::FEE_TYPE_CONDITIONAL_FREE;
					} else {
						return ShippingPolicy::FEE_TYPE_CHARGE;
					}
				} else {
					return ShippingPolicy::FEE_TYPE_FREE;
				}
			} else if ( $flat_rate && 'yes' == $flat_rate->enabled ) {
				return ShippingPolicy::FEE_TYPE_CHARGE;
			}

			return null;
		}
		static function get_shipping_policy( $cart ) {
			if ( 'disabled' == get_option( 'woocommerce_ship_to_countries' ) ) {
				$shipping_cost = 0;
			} else {
				$fee_type = self::get_fee_type();

				if ( is_null( $fee_type ) ) {
					return null;
				}

				if ( ShippingPolicy::FEE_TYPE_FREE == $fee_type ) {
					$fee_pay_type  = ShippingPolicy::FEE_PAY_TYPE_FREE;
					$free_shipping = self::get_free_shipping_method();
					$min_amount    = ! empty( $free_shipping ) ? $free_shipping->min_amount : 0;
				} else if ( ShippingPolicy::FEE_TYPE_CONDITIONAL_FREE == $fee_type ) {
					$fee_pay_type  = ShippingPolicy::FEE_PAY_TYPE_PREPAYED;
					$free_shipping = self::get_free_shipping_method();
					$min_amount    = ! empty( $free_shipping ) ? $free_shipping->min_amount : 0;
				} else {
					$fee_pay_type = ShippingPolicy::FEE_PAY_TYPE_PREPAYED;
					$min_amount   = null;
				}

				$shipping_cost = self::get_shipping_cost( $cart );
			}
			if ( $shipping_cost == 0 ) {
				$fee_type     = ShippingPolicy::FEE_TYPE_FREE;
				$fee_pay_type = ShippingPolicy::FEE_PAY_TYPE_FREE;
			}

			return new ShippingPolicy( $fee_type, $fee_pay_type, $shipping_cost, self::get_shipping_policy_surcharge_by_area(), $min_amount );
		}
	}
}

