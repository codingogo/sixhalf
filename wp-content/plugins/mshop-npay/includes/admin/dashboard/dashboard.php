<?php
wp_enqueue_style( 'semantic-ui-daterangepicker', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/semantic-ui-daterangepicker/daterangepicker.css' );
wp_enqueue_style( 'bootstrap', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/bootstrap/bootstrap.css' );
wp_enqueue_style( 'mnp-dashboard', MNP()->plugin_url() . '/includes/admin/dashboard/assets/css/dashboard.css', array(), MNP()->version );

wp_enqueue_script( 'moment', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/moment/moment.min.js' );
wp_enqueue_script( 'semantic-ui-daterangepicker', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/semantic-ui-daterangepicker/daterangepicker.js', array (
	'jquery',
	'jquery-ui-core',
	'moment',
	'underscore'
) );
wp_enqueue_script( 'amchart', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/amcharts/amcharts.js' );
wp_enqueue_script( 'amchart-serial', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/amcharts/serial.js' );
wp_enqueue_script( 'amchart-pie', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/amcharts/pie.js' );
wp_enqueue_script( 'amchart-light', MNP()->plugin_url() . '/includes/admin/dashboard/assets/vendor/amcharts/themes/light.js' );

wp_enqueue_script( 'jquery-block-ui', MNP()->plugin_url() . '/assets/js/jquery.blockUI.js' );

wp_enqueue_script( 'mnp-dashboard', MNP()->plugin_url() . '/includes/admin/dashboard/assets/js/dashboard.js', array(), MNP()->version );
wp_localize_script( 'mnp-dashboard', 'mnp_dashboard', array (
	'ajax_url'         => admin_url( 'admin-ajax.php', 'relative' ),
	'dashboard_action' => MNP()->slug() . '-dashboard_action',
	'start_date'       => date( 'Y-m-d', strtotime( "-30 days" ) ),
	'end_date'         => date( "Y-m-d" ),
	'currency'         => get_woocommerce_currency_symbol()
) );

add_action( 'admin_footer', 'mnp_dashboard_footer' );

function mnp_dashboard_footer() {
	?>
	<div id="balloon" style="display: none;"></div>
	<?php
}

?>
<h3>NPAY Dashboard</h3>

<div id="mnp-dashboard-wrapper">
	<div class="mnp-dashboard stat invert">
		<div class="mnp-dashboard-stat-wrapper">
			<div class="mnp-dashboard-stat">
				<div class="display today">
					<div class="number">
						<h3 class="font-green-sharp">
							<span class="amount">0</span>
							<small class="font-green-sharp">원</small>
						</h3>
						<small>TODAY</small>
						<h3 class="font-green-sharp small" style="float: right">
							<span class="count">0</span>
							<span>건</span>
						</h3>
					</div>
					<div class="icon">
						<i class="icon-pie-chart"></i>
					</div>
				</div>
			</div>
		</div>
		<div class="mnp-dashboard-stat-wrapper">
			<div class="mnp-dashboard-stat">
				<div class="display week">
					<div class="number">
						<h3 class="font-red-haze">
							<span class="amount">0</span>
							<small class="font-red-haze">원</small>
						</h3>
						<small>THIS WEEK</small>
						<h3 class="font-red-haze small" style="float: right">
							<span class="count">0</span>
							<span>건</span>
						</h3></div>
					<div class="icon">
						<i class="icon-pie-chart"></i>
					</div>
				</div>
			</div>
		</div>
		<div class="mnp-dashboard-stat-wrapper">
			<div class="mnp-dashboard-stat">
				<div class="display month">
					<div class="number">
						<h3 class="font-blue-sharp">
							<span class="amount">0</span>
							<small class="font-blue-sharp">원</small>
						</h3>
						<small>THIS MONTH</small>
						<h3 class="font-blue-sharp small" style="float: right">
							<span class="count">0</span>
							<span>건</span>
						</h3>
					</div>
					<div class="icon">
						<i class="icon-pie-chart"></i>
					</div>
				</div>
			</div>
		</div>
		<div class="mnp-dashboard-stat-wrapper">
			<div class="mnp-dashboard-stat">
				<div class="display year">
					<div class="number">
						<h3 class="font-purple-soft">
							<span class="amount">0</span>
							<small class="font-purple-soft">원</small>
						</h3>
						<small>THIS YEAR</small>
						<h3 class="font-purple-soft small" style="float: right">
							<span class="count">0</span>
							<span>건</span>
						</h3>
					</div>
					<div class="icon">
						<i class="icon-pie-chart"></i>
					</div>
				</div>
			</div>
		</div>

	</div>

	<div class="mnp-dashboard-search">
		<div id="reportrange" class="clear" style="">
			<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
			<span><?php echo date( 'Y-m-d', strtotime( "-30 days" ) ); ?> - <?php echo date( "Y-m-d" ); ?></span> <b
				class="caret"></b>
		</div>
	</div>

	<div class="mnp-dashboard stat">
		<div class="mnp-dashboard-stat-wrapper-box">
			<div class="mnp-dashboard-stat-wrapper-progress-box">
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display PAYMENT_WAITING">
							<div class="order-status">
								<small>입금대기</small>
								<div class="font-grey small" style="float: right">
									<span class="count">0</span>
									<span>건</span>
								</div>
								<div class="amount-wrapper">
									<span class="amount">0</span>
									<small class="font-greyt">원</small>
								</div>
							</div>
							<div class="icon">
								<i class="icon-pie-chart"></i>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display PAYED">
							<div class="order-status">
								<small>결제완료</small>
								<div class="font-grey small" style="float: right">
									<span class="count">0</span>
									<span>건</span>
								</div>
								<div class="amount-wrapper">
									<span class="amount">0</span>
									<small class="font-greyt">원</small>
								</div>
							</div>
							<div class="icon">
								<i class="icon-pie-chart"></i>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display DELIVERING">
							<div class="order-status">
								<small>배송중</small>
								<div class="font-grey small" style="float: right">
									<span class="count">0</span>
									<span>건</span>
								</div>
								<div class="amount-wrapper">
									<span class="amount">0</span>
									<small class="font-greyt">원</small>
								</div>
							</div>
							<div class="icon">
								<i class="icon-pie-chart"></i>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display DELIVERED">
							<div class="order-status">
								<small>배송완료</small>
								<div class="font-grey small" style="float: right">
									<span class="count">0</span>
									<span>건</span>
								</div>
								<div class="amount-wrapper">
									<span class="amount">0</span>
									<small class="font-greyt">원</small>
								</div>
							</div>
							<div class="icon">
								<i class="icon-pie-chart"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="mnp-dashboard-stat-wrapper-box">
			<div class="mnp-dashboard-stat">
				<div class="display TOTAL_SALES">
					<div class="order-status">
						<h3>구매확정</h3>
						<div class="count-wrapper">
							<span class="count">0</span>
							<span>건</span>
						</div>
						<div class="amount-wrapper">
							<span class="amount">0</span>
							<small class="font-greyt">원</small>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="mnp-dashboard-stat-wrapper-box">
			<div class="mnp-dashboard-stat-wrapper-claim-box">
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display CANCEL_REQUEST">
							<div class="order-status">
								<small>취소요청</small>
								<div class="amount-wrapper">
									<span class="count">0</span>
									<span>건</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display EXCHANGE_REQUEST">
							<div class="order-status">
								<small>교환요청</small>
								<div class="amount-wrapper">
									<span class="count">0</span>
									<span>건</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display RETURN_REQUEST">
							<div class="order-status">
								<small>반품요청</small>
								<div class="amount-wrapper">
									<span class="count">0</span>
									<span>건</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display CANCELED">
							<div class="order-status">
								<small>취소완료</small>
								<div class="amount-wrapper">
									<span class="count">0</span>
									<span>건</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display RETURNED">
							<div class="order-status">
								<small>반품완료</small>
								<div class="amount-wrapper">
									<span class="count">0</span>
									<span>건</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mnp-dashboard-progress-item">
					<div class="mnp-dashboard-stat">
						<div class="display CANCELED_BY_NOPAYMENT">
							<div class="order-status">
								<small>미입금취소</small>
								<div class="amount-wrapper">
									<span class="count">0</span>
									<span>건</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="mnp-dashboard timeline">
		<div class="mnp_w12 mnp_dashboard_panel_wrapper">
			<div class="mnp_dashboard_panel">
				<p class="mnp_panel_title">
					<span>매출현황 (구매확정 기준)</span>
					<span class="search-interval" data-interval="1M" data-amount_label="월별매출" data-count_label="월별구매건수">월</span>
					<span class="search-interval" data-interval="1w" data-amount_label="주별매출" data-count_label="주별구매건수">주</span>
					<span class="search-interval selected" data-interval="1d" data-amount_label="일별매출" data-count_label="일별구매건수">일</span>
				</p>
				<div class="mnp_serialchart_panel">
					<div id="top_sales_by_date_chart"></div>
				</div>
			</div>
		</div>

		<div class="mnp_w12 mnp_dashboard_panel_wrapper">
			<div class="mnp_dashboard_panel">
				<p class="mnp_panel_title">
					<span>결제완료/구매확정 현황</span>
					<span class="search-interval" data-interval="1M" data-amount_label="월별매출" data-count_label="월별구매건수">월</span>
					<span class="search-interval" data-interval="1w" data-amount_label="주별매출" data-count_label="주별구매건수">주</span>
					<span class="search-interval selected" data-interval="1d" data-amount_label="일별매출" data-count_label="일별구매건수">일</span>
				</p>
				<div class="mnp_serialchart_panel">
					<div id="payed_by_date_chart"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="mnp-dashboard timeline" style="margin-top:20px;">
		<div class="mnp_w12 mnp_dashboard_panel_wrapper">
			<div class="mnp_dashboard_panel">
				<p class="mnp_panel_title">Top Sales (금액)</p>
				<table class="sales_by_product amount">
					<thead>
					<tr>
						<th class="title">상품명</th>
						<th class="value">판매금액</th>
						<th class="spartline"></th>
					</tr>
					</thead>
					<tbody>

					</tbody>
				</table>
			</div>
		</div>

		<div class="mnp_w12 mnp_dashboard_panel_wrapper">
			<div class="mnp_dashboard_panel">
				<p class="mnp_panel_title">Top Sales (수량)</p>
				<table class="sales_by_product qty">
					<thead>
					<tr>
						<th class="title">상품명</th>
						<th class="value">판매수량</th>
						<th class="spartline"></th>
					</tr>
					</thead>
					<tbody>

					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
