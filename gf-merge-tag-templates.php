<?php
/**
 * Plugin Name:       Gravity Forms: Merge Tag Templates
 * Plugin URI:
 * Description:       Create Merge Tag Templates.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * Contributor:       shazahm1@hotmail.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gf_publish_draft_post
 * Domain Path:       /languages
 *
 * @link              https://connections-pro.com
 * @since             1.0
 * @package           gf_merge_tag_templates
 *
 * @wordpress-plugin
 */

add_action(
	'gform_loaded',
	function() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		GFForms::include_addon_framework();
	},
	5
);

// Register Custom Post Type
function register_gf_merge_tag_template_post_type() {

	$labels = array(
		'name'                  => _x( 'Merge Tag Templates', 'Post Type General Name', 'gf_merge_tag_templates' ),
		'singular_name'         => _x( 'Merge Tag Template', 'Post Type Singular Name', 'gf_merge_tag_templates' ),
		'menu_name'             => __( 'GF Merge Tag Templates', 'gf_merge_tag_templates' ),
		'name_admin_bar'        => __( 'GF Merge Tag Templates', 'gf_merge_tag_templates' ),
		'archives'              => __( 'Item Archives', 'gf_merge_tag_templates' ),
		'attributes'            => __( 'Item Attributes', 'gf_merge_tag_templates' ),
		'parent_item_colon'     => __( 'Parent Item:', 'gf_merge_tag_templates' ),
		'all_items'             => __( 'All Templates', 'gf_merge_tag_templates' ),
		'add_new_item'          => __( 'Add New Item', 'gf_merge_tag_templates' ),
		'add_new'               => __( 'Add New Template', 'gf_merge_tag_templates' ),
		'new_item'              => __( 'New Template', 'gf_merge_tag_templates' ),
		'edit_item'             => __( 'Edit Template', 'gf_merge_tag_templates' ),
		'update_item'           => __( 'Update Template', 'gf_merge_tag_templates' ),
		'view_item'             => __( 'View Template', 'gf_merge_tag_templates' ),
		'view_items'            => __( 'View Templates', 'gf_merge_tag_templates' ),
		'search_items'          => __( 'Search Templates', 'gf_merge_tag_templates' ),
		'not_found'             => __( 'Not found', 'gf_merge_tag_templates' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'gf_merge_tag_templates' ),
		'featured_image'        => __( 'Featured Image', 'gf_merge_tag_templates' ),
		'set_featured_image'    => __( 'Set featured image', 'gf_merge_tag_templates' ),
		'remove_featured_image' => __( 'Remove featured image', 'gf_merge_tag_templates' ),
		'use_featured_image'    => __( 'Use as featured image', 'gf_merge_tag_templates' ),
		'insert_into_item'      => __( 'Insert into template', 'gf_merge_tag_templates' ),
		'uploaded_to_this_item' => __( 'Uploaded to this template', 'gf_merge_tag_templates' ),
		'items_list'            => __( 'Template list', 'gf_merge_tag_templates' ),
		'items_list_navigation' => __( 'Templates list navigation', 'gf_merge_tag_templates' ),
		'filter_items_list'     => __( 'Filter templates list', 'gf_merge_tag_templates' ),
	);
	$args = array(
		'label'                 => __( 'Merge Tag Template', 'gf_merge_tag_templates' ),
		'description'           => __( 'Gravity Forms Merge Tag Templates.', 'gf_merge_tag_templates' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-welcome-widgets-menus',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'rewrite'               => false,
		'capability_type'       => 'page',
		'show_in_rest'          => true,
	);
	register_post_type( 'gf_mergetag_template', $args );

}
add_action( 'init', 'register_gf_merge_tag_template_post_type', 0 );

add_filter(
	'gform_custom_merge_tags',
	function( $tags, $form_id, $fields, $element_id ) {

		$posts = get_posts(
			array(
				'post_type' => 'gf_mergetag_template',
			)
		);

		//var_dump( $posts );

		foreach ( $posts as $post ) {

			array_push(
				$tags,
				array(
					'label' => "Template: {$post->post_title}",
					'tag'   => "{template:{$post->ID}}",
				)
			);
		}

		return $tags;
	},
	10,
	4
);

function gfmtt_mergetag_template( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {

	if ( ! preg_match( '/{template:(\d+)}/', $text, $matches ) ) {

		return $text;
	}

	$post = get_post( $matches[1] );

	if ( ! $post instanceof WP_Post ) {

		return $text;
	}

	$form_id = $form['id'];
	$form = gf_apply_filters( array( 'gform_pre_process', $form_id ), GFFormsModel::get_form_meta( $form_id ) );

	$content = gfmtt_replace_field_label_merge_tags( $post->post_content, $form );
	$content = GFCommon::replace_variables( $content, $form, $entry, false, false, false );

	return $content;
}
add_filter( 'gform_replace_merge_tags', 'gfmtt_mergetag_template', 10, 7 );

function gfmtt_replace_field_label_merge_tags( $text, $form ) {

	// Reg exp has been expanded to allow modifiers (i.e. {Field Label:value}).
	preg_match_all( '/{([^:]*?)(:[^0-9]+?)*}/', $text, $matches, PREG_SET_ORDER );
	if( empty( $matches ) )
		return $text;

	foreach( $matches as $match ) {

		list( $search, $field_label, $modifiers ) = array_pad( $match, 3, false );

		foreach( $form['fields'] as $field ) {

			$full_input_id = $input_id = false;
			$matches_admin_label = strcasecmp( rgar( $field, 'adminLabel' ), $field_label ) === 0;
			$matches_field_label = false;

			if( is_array( $field->get_entry_inputs() ) ) {
				foreach( $field['inputs'] as $input ) {
					if( strcasecmp( GFFormsModel::get_label( $field, $input['id'] ), $field_label ) === 0 ) {
						$matches_field_label = true;
						$input_id = $input['id'];
						break;
					}
				}
			} else {
				$matches_field_label = strcasecmp( GFFormsModel::get_label( $field ), $field_label ) === 0;
				$input_id = $field['id'];
			}

			if( ! $matches_admin_label && ! $matches_field_label )
				continue;

			if( ! $modifiers ) {
				$modifiers = '';
			}

			$replace = sprintf( '{%s:%s%s}', $field_label, (string) $input_id, $modifiers);
			$text = str_replace( $search, $replace, $text );

			break;
		}

	}

	return $text;
}
