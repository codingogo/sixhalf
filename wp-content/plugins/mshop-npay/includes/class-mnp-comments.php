<?php



if ( ! class_exists( 'MNP_Comments' ) ) {
	class MNP_Comments {

		public static function sync_review() {

			if ( MNP_Manager::sync_review() ) {
				$from = apply_filters( 'mnp_sync_review_from', gmdate( 'Y-m-d\TH:i:s', strtotime( '-2 hours' ) ) . date( 'P' ) );
				$to   = apply_filters( 'mnp_sync_review_to', gmdate( 'Y-m-d\TH:i:s', strtotime( '+30 minutes' ) ) . date( 'P' ) );


				if ( MNP_Manager::sync_normal_review() ) {

					$result = MNP_API::get_purchase_review_list( $from, $to, 'GENERAL' );

					$response = $result->response;

					if ( "SUCCESS" == $response->ResponseType && $response->ReturnedDataCount > 0 ) {
						if ( is_array( $response->PurchaseReviewList ) ) {
							foreach ( $response->PurchaseReviewList as $PurchaseReview ) {
								self::insert_comment( $PurchaseReview );
							}
						} else {
							self::insert_comment( $response->PurchaseReviewList );
						}
					}

				}

				if ( MNP_Manager::sync_premium_review() ) {
					$result = MNP_API::get_purchase_review_list( $from, $to, 'PREMIUM' );

					$response = $result->response;

					if ( "SUCCESS" == $response->ResponseType && $response->ReturnedDataCount > 0 ) {
						if ( is_array( $response->PurchaseReviewList ) ) {
							foreach ( $response->PurchaseReviewList as $PurchaseReview ) {
								self::insert_comment( $PurchaseReview );
							}
						} else {
							self::insert_comment( $response->PurchaseReviewList );
						}
					}

				}
			}
		}

		public static function insert_comment( $PurchaseReview ) {
			$product = wc_get_product( $PurchaseReview->ProductID );

			if ( $product ) {
				$content = array(
						$PurchaseReview->Title,
						$PurchaseReview->Content
				);

				$content = array_filter( $content );
				$content = implode( '<br><br>', $content );

				$commentdata = array(
						'comment_post_ID'                     => $product->id,
						'comment_author'                      => apply_filters('mnp_review_write_id', $PurchaseReview->WriterId, $PurchaseReview ),
						'comment_content'                     => apply_filters('mnp_review_content', $content, $PurchaseReview ),
						'comment_type'                        => '',
						'comment_parent'                      => 0,
						'comment_approved'                    => apply_filters('mnp_review_approved', 1, $PurchaseReview ),
						'comment_agent'                       => 'NaverPay',
						'comment_naverpay_purchase_review_id' => $PurchaseReview->PurchaseReviewId,
				);

				if ( self::allow_comment( $commentdata ) ) {
					$comment_id = wp_insert_comment( $commentdata );

					add_comment_meta( $comment_id, 'rating', $PurchaseReview->PurchaseReviewScore * 2 + 1, true );
					add_comment_meta( $comment_id, 'naverpay_purchase_review_id', $PurchaseReview->PurchaseReviewId, true );
				}
			}
		}

		static function allow_comment( $commentdata ) {
			global $wpdb;

			$dupe = $wpdb->prepare(
					"SELECT $wpdb->comments.comment_ID FROM $wpdb->comments, $wpdb->commentmeta WHERE $wpdb->comments.comment_approved != 'trash' AND $wpdb->comments.comment_post_ID = %d AND $wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id AND $wpdb->commentmeta.meta_key = 'naverpay_purchase_review_id' AND $wpdb->commentmeta.meta_value = %d",
					wp_unslash( $commentdata['comment_post_ID'] ),
					wp_unslash( $commentdata['comment_naverpay_purchase_review_id'] )
			);

			if ( $wpdb->get_var( $dupe ) ) {
				return false;
			}

			return true;
		}

	}
}

