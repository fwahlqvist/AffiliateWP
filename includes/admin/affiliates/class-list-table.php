<?php
/**
 * Affiiates Admin List Table
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Affiliates
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 */

use AffWP\Admin\List_Table;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AffWP_Affiliates_Table Class
 *
 * Renders the Affiliates table on the Affiliates page
 *
 * @since 1.0
 *
 * @see \AffWP\Admin\List_Table
 */
class AffWP_Affiliates_Table extends List_Table {

	/**
	 * Default number of items to show per page
	 *
	 * @var string
	 * @since 1.0
	 */
	public $per_page = 30;

	/**
	 * Total number of affiliates found
	 *
	 * @var int
	 * @since 1.0
	 */
	public $total_count;

	/**
	 * Number of active affiliates found
	 *
	 * @var string
	 * @since 1.0
	 */
	public $active_count;

	/**
	 *  Number of inactive affiliates found
	 *
	 * @var string
	 * @since 1.0
	 */
	public $inactive_count;

	/**
	 * Number of pending affiliates found
	 *
	 * @var string
	 * @since 1.0
	 */
	public $pending_count;

	/**
	 * Number of rejected affiliates found
	 *
	 * @var string
	 * @since 1.0
	 */
	public $rejected_count;

	/**
	 * Get things started
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @see WP_List_Table::__construct()
	 *
	 * @param array $args Optional. Arbitrary display and query arguments to pass through
	 *                    the list table. Default empty array.
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'affiliate',
			'plural'   => 'affiliates',
		) );

		parent::__construct( $args );

		$this->get_affiliate_counts();
	}

	/**
	 * Show the search field
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @param string $text Label for the search box
	 * @param string $input_id ID of the search box
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, 'button', false, false, array( 'ID' => 'search-submit' ) ); ?>
		</p>
	<?php
	}

	/**
	 * Retrieve the view types
	 *
	 * @access public
	 * @since 1.0
	 * @return array $views All the views available
	 */
	public function get_views() {
		$base           = admin_url( 'admin.php?page=affiliate-wp-affiliates' );

		$current        = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count    = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$active_count   = '&nbsp;<span class="count">(' . $this->active_count . ')</span>';
		$inactive_count = '&nbsp;<span class="count">(' . $this->inactive_count  . ')</span>';
		$pending_count  = '&nbsp;<span class="count">(' . $this->pending_count  . ')</span>';
		$rejected_count = '&nbsp;<span class="count">(' . $this->rejected_count  . ')</span>';

		$views = array(
			'all'		=> sprintf( '<a href="%s"%s>%s</a>', esc_url( remove_query_arg( 'status', $base ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All', 'affiliate-wp') . $total_count ),
			'active'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'active', $base ) ), $current === 'active' ? ' class="current"' : '', __('Active', 'affiliate-wp') . $active_count ),
			'inactive'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'inactive', $base ) ), $current === 'inactive' ? ' class="current"' : '', __('Inactive', 'affiliate-wp') . $inactive_count ),
			'pending'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'pending', $base ) ), $current === 'pending' ? ' class="current"' : '', __('Pending', 'affiliate-wp') . $pending_count ),
			'rejected'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'rejected', $base ) ), $current === 'rejected' ? ' class="current"' : '', __('Rejected', 'affiliate-wp') . $rejected_count ),
		);

		return $views;
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 1.0
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'name'         => __( 'Name', 'affiliate-wp' ),
			'affiliate_id' => __( 'Affiliate ID', 'affiliate-wp' ),
			'username'     => __( 'Username', 'affiliate-wp' ),
			'earnings'     => __( 'Earnings', 'affiliate-wp' ),
			'rate'     	   => __( 'Rate', 'affiliate-wp' ),
			'unpaid'       => __( 'Unpaid Referrals', 'affiliate-wp' ),
			'referrals'    => __( 'Paid Referrals', 'affiliate-wp' ),
			'visits'       => __( 'Visits', 'affiliate-wp' ),
			'status'       => __( 'Status', 'affiliate-wp' ),
		);

		return apply_filters( 'affwp_affiliate_table_columns', $this->prepare_columns( $columns ) );
	}

	/**
	 * Retrieve the table's sortable columns
	 *
	 * @access public
	 * @since 1.0
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		return array(
			'name'         => array( 'name', false ),
			'affiliate_id' => array( 'affiliate_id', false ),
			'username'     => array( 'username', false ),
			'earnings'     => array( 'earnings', false ),
			'rate'         => array( 'rate', false ),
			'unpaid'       => array( 'unpaid', false ),
			'referrals'    => array( 'referrals', false ),
			'visits'       => array( 'visits', false ),
			'status'       => array( 'status', false ),
		);
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @param array $affiliate Contains all the data of the affiliate
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	function column_default( $affiliate, $column_name ) {
		switch( $column_name ){

			default:
				$value = isset( $affiliate->$column_name ) ? $affiliate->$column_name : '';
				break;
		}

		return apply_filters( 'affwp_affiliate_table_' . $column_name, $value );
	}

	/**
	 * Render the Name Column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $affiliate Contains all the data of the affiliate
	 * @return string Data shown in the Name column
	 */
	function column_name( $affiliate ) {
		$base         = admin_url( 'admin.php?page=affiliate-wp&affiliate_id=' . $affiliate->affiliate_id );
		$row_actions  = array();
		$name         = affiliate_wp()->affiliates->get_affiliate_name( $affiliate->affiliate_id );

		$base_query_args = array(
			'page'         => 'affiliate-wp-affiliates',
			'affiliate_id' => $affiliate->ID
		);

		// Main 'Name' link.
		if ( ! $name ) {
			$affiliate_name = __( '(user deleted)', 'affiliate-wp' );
		} else {
			$affiliate_name = $name;
		}

		$value = sprintf( '<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg(
				array_merge( $base_query_args, array(
					'affwp_notice' => false,
					'action'       => 'edit_affiliate',
				) )
			) ),
			$affiliate_name
		);

		// Reports.
		$row_actions['reports'] = $this->get_row_action_link(
			__( 'Reports', 'affiliate-wp' ),
			array_merge( $base_query_args, array(
				'affwp_notice' => false,
				'action'       => 'view_affiliate',
			) )
		);

		if ( $name ) {
			// Edit User.
			$row_actions['edit_user'] = $this->get_row_action_link(
				__( 'Edit User', 'affiliate-wp' ),
				array(),
				array( 'base_uri' => get_edit_user_link( $affiliate->user_id ) )
			);
		}

		if ( strtolower( $affiliate->status ) == 'active' ) {

			// Deactivate.
			$row_actions['deactivate'] = $this->get_row_action_link(
				__( 'Deactivate', 'affiliate-wp' ),
				array_merge( $base_query_args, array(
					'affwp_notice' => 'affiliate_deactivated',
					'action'       => 'deactivate'
				) ),
				array( 'nonce' => 'affiliate-nonce' )
			);

		} elseif( strtolower( $affiliate->status ) == 'pending' ) {

			// Review.
			$row_actions['review'] = $this->get_row_action_link(
				__( 'Review', 'affiliate-wp' ),
				array_merge( $base_query_args, array(
					'affwp_notice' => false,
					'action'       => 'review_affiliate'
				) ),
				array( 'nonce' => 'affiliate-nonce' )
			);

			// Accept.
			$row_actions['accept'] = $this->get_row_action_link(
				__( 'Accept', 'affiliate-wp' ),
				array_merge( $base_query_args, array(
					'affwp_notice' => 'affiliate_accepted',
					'action'       => 'accept'
				) ),
				array( 'nonce' => 'affiliate-nonce' )
			);

			// Reject.
			$row_actions['reject'] = $this->get_row_action_link(
				__( 'Reject', 'affiliate-wp' ),
				array_merge( $base_query_args, array(
					'affwp_notice' => 'affiliate_rejected',
					'action'       => 'reject'
				) ),
				array( 'nonce' => 'affiliate-nonce' )
			);

		} else {

			// Activate.
			$row_actions['activate'] = $this->get_row_action_link(
				__( 'Activate', 'affiliate-wp' ),
				array_merge( $base_query_args, array(
					'affwp_notice' => 'affiliate_activated',
					'action'       => 'activate'
				) ),
				array( 'nonce' => 'affiliate-nonce' )
			);

		}

		// Delete.
		$row_actions['delete'] = $this->get_row_action_link(
			__( 'Delete', 'affiliate-wp' ),
			array_merge( $base_query_args, array(
				'affwp_notice' => false,
				'action'       => 'delete'
			) ),
			array( 'nonce' => 'affiliate-nonce' )
		);

		/**
		 * Filters the row actions array for the Affiliates list table.
		 *
		 * @since 1.0
		 *
		 * @param array            $row_actions Row actions array.
		 * @param \AffWP\Affiliate $affiliate   Current affiliate.
		 */
		$row_actions = apply_filters( 'affwp_affiliate_row_actions', $row_actions, $affiliate );

		$value .= '<div class="row-actions">' . $this->row_actions( $row_actions, true ) . '</div>';

		return apply_filters( 'affwp_affiliate_table_name', $value, $affiliate );
	}

	/**
	 * Render the Username Column
	 *
	 * @access public
	 * @since 1.8
	 * @param array $affiliate Contains all the data of the affiliate
	 * @return string Data shown in the Username column
	 */
	function column_username( $affiliate ) {

		$row_actions = array();
		$user_info = get_userdata( $affiliate->user_id );

		$username = ( $user_info ) ? $user_info->user_login : false;

		if ( $username ) {
			$value = $username;
		} else {
			$value = __( '(user deleted)', 'affiliate-wp' );
		}

		return apply_filters( 'affwp_affiliate_table_username', $value, $affiliate );

	}

	/**
	 * Render the checkbox column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $affiliate Contains all the data for the checkbox column
	 * @return string Displays a checkbox
	 */
	function column_cb( $affiliate ) {
		return '<input type="checkbox" name="affiliate_id[]" value="' . absint( $affiliate->affiliate_id ) . '" />';
	}

	/**
	 * Render the earnings column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $affiliate Contains all the data for the earnings column
	 * @return string earnings link
	 */
	function column_earnings( $affiliate ) {
		$value = affwp_get_affiliate_earnings( $affiliate->affiliate_id, true );
		return apply_filters( 'affwp_affiliate_table_earnings', $value, $affiliate );
	}

	/**
	 * Render the earnings column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $affiliate Contains all the data for the earnings column
	 * @return string earnings link
	 */
	function column_rate( $affiliate ) {
		$value = affwp_get_affiliate_rate( $affiliate->affiliate_id, true );
		return apply_filters( 'affwp_affiliate_table_rate', $value, $affiliate );
	}


	/**
	 * Render the unpaid referrals column
	 *
	 * @access public
	 * @since 1.7.5
	 * @param array $affiliate Contains all the data for the unpaid referrals column
	 * @return string unpaid referrals link
	 */
	function column_unpaid( $affiliate ) {
		$unpaid_count = affiliate_wp()->referrals->unpaid_count( '', $affiliate->affiliate_id );

		$value = '<a href="' . admin_url( 'admin.php?page=affiliate-wp-referrals&affiliate_id=' . $affiliate->affiliate_id . '&status=unpaid' ) . '">' . $unpaid_count . '</a>';
		return apply_filters( 'affwp_affiliate_table_unpaid', $value, $affiliate );
	}


	/**
	 * Render the referrals column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $affiliate Contains all the data for the referrals column
	 * @return string referrals link
	 */
	function column_referrals( $affiliate ) {
		$value = '<a href="' . admin_url( 'admin.php?page=affiliate-wp-referrals&affiliate_id=' . $affiliate->affiliate_id . '&status=paid' ) . '">' . $affiliate->referrals . '</a>';
		return apply_filters( 'affwp_affiliate_table_referrals', $value, $affiliate );
	}

	/**
	 * Render the visits column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $affiliate Contains all the data for the visits column
	 * @return string visits link
	 */
	function column_visits( $affiliate ) {
		$value = '<a href="' . admin_url( 'admin.php?page=affiliate-wp-visits&affiliate=' . $affiliate->affiliate_id ) . '">' . affwp_get_affiliate_visit_count( $affiliate->affiliate_id ) . '</a>';
		return apply_filters( 'affwp_affiliate_table_visits', $value, $affiliate );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.7.2
	 * @access public
	 */
	function no_items() {
		_e( 'No affiliates found.', 'affiliate-wp' );
	}

	/**
	 * Retrieve the bulk actions
	 *
	 * @access public
	 * @since 1.0
	 * @return array $actions Array of the bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			'accept'     => __( 'Accept', 'affiliate-wp' ),
			'reject'     => __( 'Reject', 'affiliate-wp' ),
			'activate'   => __( 'Activate', 'affiliate-wp' ),
			'deactivate' => __( 'Deactivate', 'affiliate-wp' ),
			'delete'     => __( 'Delete', 'affiliate-wp' )
		);

		return apply_filters( 'affwp_affiliates_bulk_actions', $actions );
	}

	/**
	 * Process the bulk actions
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function process_bulk_action() {

		if( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-affiliates' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'affiliate-nonce' ) ) {
			return;
		}

		$ids = isset( $_GET['affiliate_id'] ) ? $_GET['affiliate_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) ) {
			return;
		}

		foreach ( $ids as $id ) {

			if ( 'accept' === $this->current_action() ) {
				affwp_set_affiliate_status( $id, 'active' );
			}

			if ( 'reject' === $this->current_action() ) {
				affwp_set_affiliate_status( $id, 'rejected' );
			}

			if ( 'activate' === $this->current_action() ) {
				affwp_set_affiliate_status( $id, 'active' );
			}

			if ( 'deactivate' === $this->current_action() ) {
				affwp_set_affiliate_status( $id, 'inactive' );
			}

		}

	}

	/**
	 * Retrieve the discount code counts
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function get_affiliate_counts() {

		$search = isset( $_GET['s'] ) ? $_GET['s'] : '';

		$this->active_count = affiliate_wp()->affiliates->count(
			array_merge( $this->query_args, array(
				'status' => 'active',
				'search' => $search
			) )
		);

		$this->inactive_count = affiliate_wp()->affiliates->count(
			array_merge( $this->query_args, array(
				'status' => 'inactive',
				'search' => $search
			) )
		);

		$this->pending_count = affiliate_wp()->affiliates->count(
			array_merge( $this->query_args, array(
				'status' => 'pending',
				'search' => $search
			) )
		);

		$this->rejected_count = affiliate_wp()->affiliates->count(
			array_merge( $this->query_args, array(
				'status' => 'rejected',
				'search' => $search
			) )
		);

		$this->total_count = $this->active_count + $this->inactive_count + $this->pending_count + $this->rejected_count;
	}

	/**
	 * Retrieve all the data for all the Affiliates
	 *
	 * @access public
	 * @since 1.0
	 * @return array $affiliate_data Array of all the data for the Affiliates
	 */
	public function affiliate_data() {

		$page    = isset( $_GET['paged'] )    ? absint( $_GET['paged'] ) : 1;
		$status  = isset( $_GET['status'] )   ? $_GET['status']          : '';
		$search  = isset( $_GET['s'] )        ? $_GET['s']               : '';
		$order   = isset( $_GET['order'] )    ? $_GET['order']           : 'DESC';
		$orderby = isset( $_GET['orderby'] )  ? $_GET['orderby']         : 'affiliate_id';

		$per_page = $this->get_items_per_page( 'affwp_edit_affiliates_per_page', $this->per_page );

		$args = wp_parse_args( $this->query_args, array(
			'number'  => $per_page,
			'offset'  => $per_page * ( $page - 1 ),
			'status'  => $status,
			'search'  => $search,
			'orderby' => sanitize_text_field( $orderby ),
			'order'   => sanitize_text_field( $order )
		) );

		$affiliates   = affiliate_wp()->affiliates->get_affiliates( $args );

		return $affiliates;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 1.0
	 * @uses AffWP_Affiliates_Table::get_columns()
	 * @uses AffWP_Affiliates_Table::get_sortable_columns()
	 * @uses AffWP_Affiliates_Table::process_bulk_action()
	 * @uses AffWP_Affiliates_Table::affiliate_data()
	 * @uses WP_List_Table::get_pagenum()
	 * @uses WP_List_Table::set_pagination_args()
	 * @return void
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'affwp_edit_affiliates_per_page', $this->per_page );

		$columns = $this->get_columns();

		$hidden = array();

		$sortable = $this->get_sortable_columns();

		$this->get_column_info();

		$this->process_bulk_action();

		$data = $this->affiliate_data();

		$current_page = $this->get_pagenum();

		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'any';

		switch( $status ) {
			case 'active':
				$total_items = $this->active_count;
				break;
			case 'inactive':
				$total_items = $this->inactive_count;
				break;
			case 'pending':
				$total_items = $this->pending_count;
				break;
			case 'rejected':
				$total_items = $this->rejected_count;
				break;
			case 'any':
				$total_items = $this->total_count;
				break;
		}

		$this->items = $data;

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			)
		);
	}
}
