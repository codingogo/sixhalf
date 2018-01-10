<?php



if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MNP_Customer_Inquiry_List_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array (
			'singular' => 'customer_inquiry',     //singular name of the listed records
			'plural'   => 'customer_inquiry',    //plural name of the listed records
			'ajax'     => false        //does this table support ajax?
		) );
	}

	function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			ob_start();
			?>
				<span>조회일시 : </span><input type="text" class="w6 datepicker" name="search_date" value="<?php echo ! empty( $_REQUEST['search_date'] ) ? $_REQUEST['search_date'] : date( 'Y-m-d' ); ?>">
				<span>답변여부 : </span><input type="checkbox" class="w6" name="is_answered" <?php echo ! empty( $_REQUEST['is_answered'] ) ? 'checked' : ''; ?>>
				<input class="button button-primary" type="submit" value="검색">
			<?php
			echo ob_get_clean();
		}
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'InquiryID':
			case 'OrderID':
				return $item->$column_name;
			case 'IsAnswered':
				return empty( $item->$column_name ) ? '미답변' : '답변';
			case 'InquiryDateTime':
				return ( new DateTime( $item->InquiryDateTime ) )->add( new DateInterval( 'PT9H' ) )->format( 'Y-m-d H:i:s' );
			case 'ProductName':
				$data = sprintf( '<a target="_blank" href="%s">%s</a>', get_edit_post_link( $item->ProductID ), $item->$column_name );
				if ( ! empty( $item->ProductOrderOption ) ) {
					$data .= '(' . $item->ProductOrderOption . ')';
				}

				return $data;
			case 'Customer':
				return $item->CustomerName . '(' . $item->CustomerID . ')';

		}
	}

	function column_title( $item ) {
		$title = '<a class="title">[' . $item->Category . ']' . $item->Title . '</a>';
		$title .= '<div class="InquiryContent hide">[문의내용]<pre>' . $item->InquiryContent . '</pre>';
		$title .= '[답변]<br><textarea name="write_answer" rows=5 class="answer">' . $item->AnswerContent . '</textarea><br>';
		$title .= '<input type="button" class="button button-primary button-answer" data-inquiry-id="' . $item->InquiryID . '" data-answer-content-id="' . $item->AnswerContentID . '" value="답변작성">';
		$title .= '</div>';

		return $title;
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			$item['ID']                //The value of the checkbox should be the record's id
		);
	}

	function get_columns() {
		$columns = array (
			'OrderID'         => '주문번호',
			'ProductName'     => '제품정보',
			'Customer'        => '고객정보',
			'Title'           => '제목',
			'InquiryDateTime' => '문의일시',
			'IsAnswered'      => '답변여부',
		);

		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array (
			'title'    => array ( 'title', false ),     //true means it's already sorted
			'rating'   => array ( 'rating', false ),
			'director' => array ( 'director', false )
		);

		return $sortable_columns;
	}

	function prepare_items() {
		$search_date     = ! empty( $_REQUEST['search_date'] ) ? $_REQUEST['search_date'] : date( 'Y-m-d' );
		$InquiryTimeFrom = $search_date . 'T00:00:00+09:00';
//		$InquiryTimeFrom = '2016-04-01' . 'T00:00:00+09:00';
		$InquiryTimeTo   = $search_date . 'T23:59:59+09:00';
		$IsAnswered      = ! empty( $_REQUEST['is_answered'] ) ? true : false;
		$result          = MNP_API::get_customer_inquiry_list( $InquiryTimeFrom, $InquiryTimeTo, $IsAnswered );

		$post_per_page = - 1;

		$columns  = $this->get_columns();
		$hidden   = array ();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array ( $columns, $hidden, $sortable );

		if ( $result->response->ReturnedDataCount > 0 ) {
			$data        = $result->response->CustomerInquiryList->CustomerInquiry;
			$this->items = is_array( $data ) ? $data : array ( $data );
		} else {
			$this->items = null;
		}

		$this->set_pagination_args( array (
			'total_items' => $result->response->ReturnedDataCount,
			'per_page'    => $post_per_page,
			'total_pages' => 1
		) );
	}
}
