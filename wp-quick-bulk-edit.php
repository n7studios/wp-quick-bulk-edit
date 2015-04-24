 <?php
/**
 * Plugin Name: WP Quick Bulk Edit
 * Plugin URI: http://wpbeginner.com
 * Version: 1.0
 * Author: Tim Carr
 * Author URI: http://www.n7studios.co.uk
 * Description: Adds Custom Fields to WordPress Pages, Quick Edit and Bulk Edit screens
 * License: GPL2
 */

class WP_Quick_Bulk_Edit {
	
	/**
	 * Constructor. Called when the plugin is initialised.
	 */
	function __construct() {
	
		// Actions
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( &$this, 'save_custom_fields' ) );
		add_filter( 'manage_pages_columns', array( $this, 'page_columns' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'page_columns_output' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_output_custom_fields' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this, 'quick_edit_output_custom_fields' ), 10, 2 );
		add_action( 'post_updated', array( $this, 'bulk_edit_save' ) );

	}

	/**
	* Register and enqueue any JS for the WordPress Administration
	*/
	function admin_scripts() {

		// JS
		wp_enqueue_script( 'wp-quick-bulk-edit', plugin_dir_url( __FILE__ ) . 'wp-quick-bulk-edit.js', array('jquery'), '1.0', true );

	}

	/**
	 * Adds a meta box to WordPress Pages
	 */
	function add_meta_boxes() {

	    // Add metabox to Envira CPT
	    add_meta_box( 'wp-quick-bulk-edit', __( 'Custom Fields', 'envira-gallery' ), array( $this, 'output_custom_fields' ), 'page', 'normal', 'high' );

	}

	/**
	 * Outputs Custom Fields on Pages
	 *
	 * @param WP_Post $post The current post object.
	 */
	function output_custom_fields( $post ) {

	    // Get custom field values
		$name = get_post_meta( $post->ID, '_custom_field_name', true );
		$email = get_post_meta( $post->ID, '_custom_field_email', true );
			
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'custom_fields', 'custom_fields_nonce' );
		
		// Output name label and field
		echo ( '<label for="custom_field_name">' . __( 'Name', 'wp-quick-bulk-edit' ) . '</label><br />' );
		echo ( '<input type="text" name="_custom_field_name" id="custom_field_name" value="' . esc_attr( $name ) . '" /><br />' );

		// Output email label and field
		echo ( '<label for="custom_field_email">' . __( 'Email Address', 'wp-quick-bulk-edit' ) . '</label><br />' );
		echo ( '<input type="text" name="_custom_field_email" id="custom_field_email" value="' . esc_attr( $email ) . '" />' );

	}

	/**
	* Saves the meta box field data
	*
	* @param int $post_id Post ID
	*/
	function save_custom_fields( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['custom_fields_nonce'] ) ) {
			return $post_id;	
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['custom_fields_nonce'], 'custom_fields' ) ) {
			return $post_id;
		}

		// Check this the Post we're saving is a Page
		if ( 'page' != $_POST['post_type'] ) {
			return $post_id;
		}

		// Check the logged in user has permission to edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// OK to save meta data
		$name = sanitize_text_field( $_POST['_custom_field_name'] );
		$email = sanitize_text_field( $_POST['_custom_field_email'] );

		update_post_meta( $post_id, '_custom_field_name', $name );
		update_post_meta( $post_id, '_custom_field_email', $email );

	}

	/**
	 * Adds a Custom Fields column to the Pages WP_List_Table
	 *
	 * @param array $columns WP_List_Table Columns
	 * @return array WP_List_Table Columns
	 */
	function page_columns( $columns ) {
		
		$columns['custom_fields'] = __( 'Custom Fields', 'wp-quick-bulk-edit' );
		return $columns;

	}

	/**
	* Output Custom Field values and hidden fields
	*
	* @param string $column WP_List_Table column name
	* @param int $post_id Post ID
	*/
	function page_columns_output( $column, $post_id ) {
		
		// Check the column we're on is the Custom Fields column
		if ( 'custom_fields' != $column ) {
			return;
		}

		// Get custom field values
		$name = get_post_meta( $post_id, '_custom_field_name', true );
		$email = get_post_meta( $post_id, '_custom_field_email', true );

		// Output custom field values
		echo __( 'Name: ', 'wp-quick-bulk-edit' ) . $name . '<br />';
		echo __( 'Email: ', 'wp-quick-bulk-edit' ) . $email . '<br />';

		// Output these values as hidden fields. We'll use this when Quick Editing a Page later
		echo '<input type="hidden" name="custom_field_name_' . $post_id . '" value="' . $name . '" />';
		echo '<input type="hidden" name="custom_field_email_' . $post_id . '" value="' . $email . '" />';

	}

	/**
	 * Outputs Custom Fields on the Page Quick Edit screen
	 *
	 * @param string $column_name Column Name
	 * @param string $post_type Post Type
	 * @return Custom Fields
	 */
	function quick_edit_output_custom_fields( $column_name, $post_type ) {

		// Check the post type we're Quick Editing is a Page
		if ( 'page' !== $post_type ) {
			return;
		}

	    // Check the column we're on is the Custom Fields column
	    if ( 'custom_fields' !== $column_name ) {
	        return;
	    }

	    // Add a nonce field so we can check for it later.
		wp_nonce_field( 'custom_fields', 'custom_fields_nonce' );

	    // Output a fieldset comprising of our Custom Fields
	    ?>
	    <fieldset class="inline-edit-col-left">
	    	<h4><?php _e( 'Custom Fields', 'wp-quick-bulk-edit' ); ?></h4>
	        <div class="inline-edit-col inline-edit-custom-fields">
	            <label class="inline-edit-group">
	                <span class="title"><?php _e( 'Name', 'wp-quick-bulk-edit' ); ?></span>
	                <input type="text" name="_custom_field_name" value="" />
	            </label>

	           	<label class="inline-edit-group">
	                <span class="title"><?php _e( 'Email', 'wp-quick-bulk-edit' ); ?></span>
	                <input type="text" name="_custom_field_email" value="" />
	            </label>
	        </div>
	    </fieldset>
	    <?php	
		
	}

	/**
	* Called every time a WordPress Post is updated
	*
	* Checks to see if the request came from submitting the Bulk Editor form,
	* and if so applies the updates.  This is because there is no direct action
	* or filter fired for bulk saving
	*
	* @param int $post_ID Post ID
	*/
    public function bulk_edit_save( $post_ID ) {

	    // Check we are performing a Bulk Edit
	    if ( ! isset( $_REQUEST['bulk_edit'] ) ) {
		    return;
	    }

	    // Check Post IDs have been submitted
        $post_ids = ( ! empty( $_REQUEST[ 'post' ] ) ) ? $_REQUEST[ 'post' ] : array();
		if ( empty( $post_ids ) || !is_array( $post_ids ) ) {
			return;
		}

		// Iterate through post IDs, updating settings
		foreach ( $post_ids as $post_id ) {
			// Check if our fields exist for this Post ID
			// If so, update those fields
			if ( ! empty( $_REQUEST['_custom_field_name'] ) ) {
				$name = sanitize_text_field( $_REQUEST['_custom_field_name'] );
				update_post_meta( $post_id, '_custom_field_name', $name );
			}
		
			if ( ! empty( $_REQUEST['_custom_field_email'] ) ) {
				$email = sanitize_text_field( $_REQUEST['_custom_field_email'] );
				update_post_meta( $post_id, '_custom_field_email', $email );
			}
		}

	}

}

$wp_quick_bulk_edit = new WP_Quick_Bulk_Edit;