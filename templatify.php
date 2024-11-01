<?php
/*

Plugin Name: Templatify
Plugin URI: http://www.marcocanestrari.it/
Description: This plugin adds Page Templates feature to Posts and Custom Post Types. No settings needed.
Version: 1.0.2
Author: Marco Canestrari
Author URI: http://www.marcocanestrari.it/
License: GPLv2 or later

*/

class Templatify {

    /*
     * Class constructor
     */
    function __construct() {

        add_action('admin_init', array($this, 'add_templatify_meta_box'));	// Adds metabox to edit screens
        add_filter('theme_page_templates', array($this,'filter_templates'));	// Filters templates list for page edit screen
	add_action('save_post', array($this, 'set_template'));			// Sets custom template meta on post save
	add_filter('single_template', array($this, 'load_template'));		// Loads custom template
	add_filter('body_class', array($this, 'body_class'));			// Adds templatify class

    }

    /*
     * Adds metabox to posts types except Pages
     */
    public function add_templatify_meta_box() {

	// Select post types, exclude Page
        $post_types = get_post_types(array( 'public' => true), 'objects');
        $screens;
        foreach ($post_types as $post_type) {
            if($post_type->name != "page") {
                $screens[] = $post_type->name;
            }
        }

	// Add metabox
	add_meta_box( 'template_selector', __( 'Select Template', 'templatify' ), array( $this, 'template_selector'), $screens, 'side', 'high' );

    }

    /*
     * Template selector in edit screen
     */
    public function template_selector( $post ) {

	$templates = get_page_templates();
	$screen = get_post_type();
	$post_type = get_post_type_object( $screen );

	// Get current post template. If none, default is selected
	$current_template = get_post_meta($post->ID,'_templatify',true);
	if(!$current_template) {
	    $current_template = 'default';
	}

	// Iterates templates to build the selector
	if ( $templates ) {

	    $templates_groups = array();

	    // Divides templates into two groups: one for post type specific templates, one for common (page) templates
	    foreach($templates as $tname => $tfile) {

		// Gets template headers. Template Post Type is the templatify specific header
		$headers = get_file_data( TEMPLATEPATH .'/'.$tfile, array( 'Template Name' => 'Template Name','Template Post Type' => 'Template Post Type' ) );

		if($headers['Template Post Type'] == $screen) {
		    $templates_groups[$screen][$tname] =  $tfile;
		} elseif (!$headers['Template Post Type']) {
		    $templates_groups['common'][$tname] =  $tfile;
		}

	    }

	    ?>

		<select name="templatify_post_template" id="templatify_post_template">
			<option value='default' <?php echo selected( $current_template , 'default') ?>><?php _e( 'Default Template'); ?></option>
			<?php

			// Post type templates
			if($templates_groups[$screen]) {
			    ?>
			    <optgroup label="<?php echo $post_type->labels->name . ' ' . __('Templates','templatify'); ?>">
			    <?php
			    foreach($templates_groups[$screen] as $tname => $tfile) {
				?>
				<option value='<?php echo $tfile; ?>' <?php echo selected( $current_template , $tfile) ?> ><?php echo esc_html($tname); ?></option>
				<?php
			    }

			    ?>
			    </optgroup>
			    <?php
			}

			// Common (page) templates
			if($templates_groups['common']) {
			    ?>
			    <optgroup label="<?php echo __('Page Templates','templatify'); ?>">
			    <?php
			    foreach($templates_groups['common'] as $tname => $tfile) {
				?>
				<option value='<?php echo $tfile; ?>' <?php echo selected( $current_template , $tfile) ?> ><?php echo esc_html($tname); ?></option>
				<?php
			    }
			}

			?>

		</select>
	    <?php

	} else {

	    // No templates found
	    echo __( 'No Templates available for this Post Type.', 'templatify' );
	}
    }

    /*
     * Adds template class to html
     */
    public function body_class( $classes ) {
        if (!is_single()) {
                return $classes;
        }
        $current_template = get_post_meta( get_the_ID(), '_templatify', true );
        if( !empty( $post_template) ) {
                $classes[] = 'templatify';
                $classes[] = 'templatify-' . str_replace( '.php', '-php', $current_template );
        }
        return $classes;
    }

    /*
     * Filters templates to exclude custom post type templates in page edit screen
     */
    public function filter_templates( $templates ) {

	$screen = get_post_type();

	// Filter templates in page edit screen only
	if($screen == 'page') {

	    foreach ( $templates as  $tfile => $tname ) {
		$headers = get_file_data( TEMPLATEPATH .'/'.$tfile, array( 'Template Name' => 'Template Name','Template Post Type' => 'Template Post Type' ) );


		if($headers['Template Post Type'] && $headers['Template Post Type'] != 'page') {
		    unset( $templates[$tfile]);
		}
	    }
	}

	return $templates;
    }

    /*
     * Load template in frontend
     */
    public static function load_template( $template ) {

	global $wp_query;
	$template_file = get_post_meta($wp_query->post->ID,'_templatify',true);

	if ( ! $template_file )
		return $template;

	if ( file_exists( TEMPLATEPATH . DIRECTORY_SEPARATOR . $template_file ) )
		return TEMPLATEPATH . DIRECTORY_SEPARATOR . $template_file;

	return $template;
    }

    /*
     * Set post meta custom template on save
     */
    public function set_template( $post_id ) {

	$template_file = $_POST[ 'templatify_post_template' ];

	// Sanitizing
	if($template_file != 'default') {
	    $template_file = sanitize_file_name( $template_file );
	}

	// Validating: must be existing PHP file
	if ( (!file_exists( TEMPLATEPATH . DIRECTORY_SEPARATOR . $template_file ) || false === strpos (  $template_file ,  '.php' )) && $template_file != 'default') {
	    $template_file = "";
	}

	if($template_file) {

	    update_post_meta( $post_id, '_templatify', $template_file );

	}

    }

}

new Templatify();

?>