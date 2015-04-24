jQuery(document).ready(function($) {

	if ( typeof inlineEditPost !== 'undefined' ) {
		// Create a copy of the WordPress inline edit post function
		var wp_inline_edit = inlineEditPost.edit;

		// Oerwrite the function with our own code
		inlineEditPost.edit = function( id ) {

			// "call" the original WP edit function
			// we don't want to leave WordPress hanging
			wp_inline_edit.apply( this, arguments );

			// Get the post ID
			var post_id = 0;
			if ( typeof( id ) == 'object' ) {
				post_id = parseInt( this.getId( id ) );
			}

			if ( post_id > 0 ) {
				// Get the Edit and Post Row Elements
				var edit_row = $( '#edit-' + post_id );
				var post_row = $( '#post-' + post_id );

				// Get our hidden field values
				var name = $( 'input[name="custom_field_name_' + post_id + '"]', $(post_row) ).val();
				var email = $( 'input[name="custom_field_email_' + post_id + '"]', $(post_row) ).val();

				// Populate Quick Edit Fields with data from the above hidden fields
				// These are output in page_columns_output() in our plugin
				$( 'input[name="_custom_field_name"]', $(edit_row) ).val( name );
				$( 'input[name="_custom_field_email"]', $(edit_row) ).val( email );
			}
		};	

		// Remove all hidden inputs when a search is performed
		// This stops them from being included in the GET URL, otherwise we'd have a really long search URL
		// which breaks some nginx configurations
		$('form#posts-filter').on('submit', function(e) {
			$( "input[name*='custom_field_name'], input[name*='custom_field_email']" ).remove();
		});
	}
});