<?php
/*
Plugin Name: WP Crawler
Plugin URI: http://wpcrawler.xyz/
Description: Crawl Any Website Content Into WordPress Posts
Author: WP Crawler Team
Version: 1.1.3
Author URI: http://wpcrawler.xyz/
*/

/*-----------------------------------------------------------------------------------*/
/*	Includes
/*-----------------------------------------------------------------------------------*/
if( ! defined( 'WP_CRAWLER_DIR' ) ) {
	define( 'WP_CRAWLER_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'WP_CRAWLER_URL' ) ) {
	define( 'WP_CRAWLER_URL', plugin_dir_url( __FILE__ ) );
}

require 'vendor/autoload.php';
require_once WP_CRAWLER_DIR . 'libs/cmb2/init.php';
require_once WP_CRAWLER_DIR . 'libs/simplehtmldom/simple_html_dom.php';
require_once WP_CRAWLER_DIR . 'libs/Lipsum.php';

/*-----------------------------------------------------------------------------------*/
/*	Sources Post Type
/*-----------------------------------------------------------------------------------*/
add_action( 'init', 'wp_crawler_post_types_init' );
function wp_crawler_post_types_init() {
	$source_labels = array(
		'name'               => _x( 'Sources', 'post type general name', 'wp-crawler' ),
		'singular_name'      => _x( 'Source', 'post type singular name', 'wp-crawler' ),
		'menu_name'          => _x( 'WP Crawler', 'admin menu', 'wp-crawler' ),
		'name_admin_bar'     => _x( 'Source', 'add new on admin bar', 'wp-crawler' ),
		'add_new'            => _x( 'Add New', 'source', 'wp-crawler' ),
		'add_new_item'       => __( 'Add New Source', 'wp-crawler' ),
		'new_item'           => __( 'New Source', 'wp-crawler' ),
		'edit_item'          => __( 'Edit Source', 'wp-crawler' ),
		'view_item'          => __( 'View Source', 'wp-crawler' ),
		'all_items'          => __( 'All Sources', 'wp-crawler' ),
		'search_items'       => __( 'Search Sources', 'wp-crawler' ),
		'parent_item_colon'  => __( 'Parent Sources:', 'wp-crawler' ),
		'not_found'          => __( 'No sources found.', 'wp-crawler' ),
		'not_found_in_trash' => __( 'No sources found in Trash.', 'wp-crawler' )
	);

	$source_args = array(
		'labels'             => $source_labels,
    'description'        => __( 'Description.', 'wp-crawler' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => false,
		'rewrite'            => array( 'slug' => 'source' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title' )
	);

	$schedule_labels = array(
		'name'               => _x( 'Schedules', 'post type general name', 'wp-crawler' ),
		'singular_name'      => _x( 'Schedule', 'post type singular name', 'wp-crawler' ),
		'menu_name'          => _x( 'Schedules', 'admin menu', 'wp-crawler' ),
		'name_admin_bar'     => _x( 'Schedule', 'add new on admin bar', 'wp-crawler' ),
		'add_new'            => _x( 'Add New', 'schedule', 'wp-crawler' ),
		'add_new_item'       => __( 'Add New Schedule', 'wp-crawler' ),
		'new_item'           => __( 'New Schedule', 'wp-crawler' ),
		'edit_item'          => __( 'Edit Schedule', 'wp-crawler' ),
		'view_item'          => __( 'View Schedule', 'wp-crawler' ),
		'all_items'          => __( 'Schedules', 'wp-crawler' ),
		'search_items'       => __( 'Search Schedules', 'wp-crawler' ),
		'parent_item_colon'  => __( 'Parent Schedules:', 'wp-crawler' ),
		'not_found'          => __( 'No schedules found.', 'wp-crawler' ),
		'not_found_in_trash' => __( 'No schedules found in Trash.', 'wp-crawler' )
	);

	$schedule_args = array(
		'labels'             => $schedule_labels,
    'description'        => __( 'Description.', 'wp-crawler' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => 'edit.php?post_type=wp-crawler-source',
		'query_var'          => false,
		'rewrite'            => array( 'slug' => 'schedule' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title' )
	);

	register_post_type( 'wp-crawler-source', $source_args );
	register_post_type( 'wp-crawler-schedule', $schedule_args );
}

/*-----------------------------------------------------------------------------------*/
/*	Sources Metabox
/*-----------------------------------------------------------------------------------*/
add_action( 'cmb2_admin_init', 'wp_crawler_metabox_init' );
function wp_crawler_metabox_init() {
	$prefix = '_wp_crawler_source_';

	$cmb = new_cmb2_box( array(
		'id'            => 'schedule_metabox',
		'title'         => __( 'Schedule Setting', 'wp-crawler' ),
		'object_types'  => array( 'wp-crawler-schedule' ),
		'context'       => 'normal',
		'priority'      => 'high',
		'cmb_styles'    => true,
		'show_names'    => true,
	) );

	$sources_query = new WP_Query( array( 'post_type' => 'wp-crawler-source', 'posts_per_page' => -1 ) );

	if ( $sources_query->have_posts() ) {

		$sources_list = array();
		while ( $sources_query->have_posts() ) {
			$sources_query->the_post();
			$sources_list[ get_the_ID() ] = get_the_title();
		}

		$cmb->add_field( array(
			'name' => __( 'Recurrence', 'wp-crawler' ),
			'id' => '_wp_crawler_schedule_recurrence',
			'type' => 'select',
			'show_option_none' => false,
			'default' => 'hourly',
			'options' => array(
				'hourly' => __( 'Hourly', 'wp-crawler' ),
				'twicedaily' => __( 'Twice Daily', 'wp-crawler' ),
				'daily' => __( 'Daily', 'wp-crawler' ),
			),
		) );

		$cmb->add_field( array(
			'name'         => __( 'Source', 'wp-crawler' ),
			'id'           => '_wp_crawler_schedule_source',
			'type'         => 'select',
			'default'      => 'custom',
			'options'      => $sources_list,
		) );

		$cmb->add_field( array(
			'name' => __( 'Multiple Items URL', 'wp-crawler' ),
			'id'   => '_wp_crawler_schedule_url',
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name'  => __( 'Post Category', 'wp-crawler' ),
			'id' => '_wp_crawler_schedule_category',
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'Post Status', 'wp-crawler' ),
			'id' => '_wp_crawler_schedule_post_status',
			'type' => 'select',
			'show_option_none' => false,
			'default' => 'draft',
			'options' => array(
				'draft' => __( 'Draft', 'wp-crawler' ),
				'publish' => __( 'Publish', 'wp-crawler' ),
				'pending' => __( 'Pending', 'wp-crawler' ),
				'private' => __( 'Private', 'wp-crawler' ),
			),
		) );

		$cmb->add_field( array(
			'name' => __( 'Post Author', 'wp-crawler' ),
			'id'   => '_wp_crawler_schedule_post_author',
			'type' => 'text',
			'attributes'  => array(
				'placeholder' => '1',
			),
		) );

		$cmb->add_field( array(
			'name' => __( 'Post Type', 'wp-crawler' ),
			'id'   => '_wp_crawler_schedule_post_type',
			'type' => 'text',
			'attributes'  => array(
				'placeholder' => 'post',
			),
		) );

	} else {
		$cmb->add_field( array(
			'name' => __( 'Please Add Source Before Schedule' ),
			'type' => 'title',
			'id' => 'item',
		) );
	}

	$cmb = new_cmb2_box( array(
		'id'            => 'source_listing_metabox',
		'title'         => __( 'Listing Page', 'wp-crawler' ),
		'object_types'  => array( 'wp-crawler-source' ),
		'context'       => 'normal',
		'priority'      => 'high',
		'cmb_styles'    => true,
		'show_names'    => true,
	) );

	$cmb->add_field( array(
		'name' => 'Item',
		'type' => 'title',
		'id' => 'item',
	) );

	$cmb->add_field( array(
		'name' => __( 'Selector', 'wp-crawler' ),
		'id'   => $prefix . 'item',
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => __( 'Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'item_attr',
		'type' => 'text',
		'default' => 'href'
	) );

	$cmb = new_cmb2_box( array(
		'id'            => 'source_metabox',
		'title'         => __( 'Single Item', 'wp-crawler' ),
		'object_types'  => array( 'wp-crawler-source' ),
		'context'       => 'normal',
		'priority'      => 'high',
		'cmb_styles'    => true,
		'show_names'    => true,
	) );

	$cmb->add_field( array(
		'name' => __( 'Post Title', 'wp-crawler' ),
		'type' => 'title',
		'id' => 'title',
	) );

	$cmb->add_field( array(
		'name' => __( 'Selector', 'wp-crawler' ),
		'id'   => $prefix . 'title',
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => __( 'Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'title_attr',
		'type' => 'text',
		'default' => 'innertext'
	) );

	$cmb->add_field( array(
		'name' => 'Post Content',
		'type' => 'title',
		'id' => 'content',
	) );

	$cmb->add_field( array(
		'name' => __( 'Selector', 'wp-crawler' ),
		'id'   => $prefix . 'content',
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => __( 'Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'content_attr',
		'type' => 'text',
		'default' => 'innertext'
	) );

	$cmb->add_field( array(
		'name' => 'Post Excerpt',
		'type' => 'title',
		'id' => 'excerpt',
	) );

	$cmb->add_field( array(
		'name' => __( 'Selector', 'wp-crawler' ),
		'id'   => $prefix . 'excerpt',
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => __( 'Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'excerpt_attr',
		'type' => 'text',
		'default' => 'innertext'
	) );

	$cmb->add_field( array(
		'name' => 'Post Date',
		'type' => 'title',
		'id' => 'date',
	) );

	$cmb->add_field( array(
		'name' => __( 'Selector', 'wp-crawler' ),
		'id'   => $prefix . 'date',
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => __( 'Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'date_attr',
		'type' => 'text',
		'default' => 'innertext'
	) );

	$cmb->add_field( array(
		'name' => 'Post Thumbnail (Image URL)',
		'type' => 'title',
		'id' => 'thumbnail',
	) );

	$cmb->add_field( array(
		'name' => __( 'Selector', 'wp-crawler' ),
		'id'   => $prefix . 'thumbnail',
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => __( 'Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'thumbnail_attr',
		'type' => 'text',
		'default' => 'src'
	) );

	$taxonomies = $cmb->add_field( array(
		'id'   => $prefix . 'taxonomies',
		'type' => 'group',
		'options'     => array(
			'group_title'   => __( 'Taxonomy {#}', 'wp-crawler' ),
			'add_button'    => __( 'Add Another Taxonomy', 'wp-crawler' ),
			'remove_button' => __( 'Remove Taxonomy', 'wp-crawler' ),
			'sortable' => true,
			'closed' => true,
		),
	) );

	$cmb->add_group_field( $taxonomies, array(
		'name' => __( 'Taxonomy Slug', 'wp-crawler' ),
		'id'   => $prefix . 'taxonomy_slug',
		'type' => 'text'
	) );

	$cmb->add_group_field( $taxonomies, array(
		'name' => __( 'Taxonomy Selector', 'wp-crawler' ),
		'id'   => $prefix . 'taxonomy_selector',
		'type' => 'text'
	) );

	$cmb->add_group_field( $taxonomies, array(
		'name' => __( 'Taxonomy Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'taxonomy_attr',
		'type' => 'text'
	) );

	$cmb->add_group_field( $taxonomies, array(
		'name' => __( 'Multiple Value?', 'wp-crawler' ),
		'id' => $prefix . 'taxonomy_multiple',
		'type' => 'select',
		'show_option_none' => false,
		'default' => 'no',
		'options' => array(
			'no' => __( 'No', 'wp-crawler' ),
			'yes' => __( 'Yes', 'wp-crawler' ),
		),
	) );

	$custom_fields = $cmb->add_field( array(
		'id'   => $prefix . 'custom_fields',
		'type' => 'group',
		'options'     => array(
			'group_title'   => __( 'Custom Field {#}', 'wp-crawler' ),
			'add_button'    => __( 'Add Another Custom Field', 'wp-crawler' ),
			'remove_button' => __( 'Remove Custom Field', 'wp-crawler' ),
			'sortable' => true,
			'closed' => true,
		),
	) );

	$cmb->add_group_field( $custom_fields, array(
		'name' => __( 'Custom Field Name', 'wp-crawler' ),
		'id'   => $prefix . 'custom_field_slug',
		'type' => 'text'
	) );

	$cmb->add_group_field( $custom_fields, array(
		'name' => __( 'Custom Field Selector', 'wp-crawler' ),
		'id'   => $prefix . 'custom_field_selector',
		'type' => 'text'
	) );

	$cmb->add_group_field( $custom_fields, array(
		'name' => __( 'Custom Field Attribute', 'wp-crawler' ),
		'id'   => $prefix . 'custom_field_attr',
		'type' => 'text',
	) );
}

/*-----------------------------------------------------------------------------------*/
/*	Schedule event
/*-----------------------------------------------------------------------------------*/
function wp_crawler_schedules_run( $query_attribute = 'post_type=wp-crawler-schedule&post_status=publish&meta_key=_wp_crawler_schedule_recurrence&meta_value=hourly' ) {
	$schedules = new WP_Query( $query_attribute );

	//print_r($schedules);

	while ( $schedules->have_posts() ) {
		$schedules->the_post();

		$crawler_url = get_post_meta( get_the_ID(), '_wp_crawler_schedule_url', true );
		$crawler_source = get_post_meta( get_the_ID(), '_wp_crawler_schedule_source', true );
		$crawler_args = array(
			'post_status' => get_post_meta( get_the_ID(), '_wp_crawler_schedule_post_status', true ),
			'post_author' => get_post_meta( get_the_ID(), '_wp_crawler_schedule_post_author', true ),
			'post_type' => get_post_meta( get_the_ID(), '_wp_crawler_schedule_post_type', true ),
			'post_cat' => get_post_meta( get_the_ID(), '_wp_crawler_schedule_category', true )
		);

		$source_item_selector = get_post_meta( $crawler_source, '_wp_crawler_source_item', true );
		$source_item_attr = get_post_meta( $crawler_source, '_wp_crawler_source_item_attr', true );

		if ( empty( $source_item_attr ) ) {
			$source_item_attr = 'href';
		}

		if ( ! empty( $source_item_selector ) ) {
			$context_options = array(
				'http' => array(
					'method' => "GET",
					'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36"
				)
			);
			$context = stream_context_create($context_options);
			$html = file_get_html( $crawler_url, false, $context );
			if ( $html ) {
				$crawler_urls = $html->find( $source_item_selector );
				if ( empty ( $crawler_urls ) ) {
					echo '<div class="updated notice notice-error below-h2"><p>Can\'t Get Single Item\'s URLs from Selector: <code>' . $source_item_selector . '</code>. Please <a href="' . get_home_url() . '/wp-admin/post.php?post=' . $crawler_source . '&action=edit">Update Source</a>.</p></div>';
				} else {
					foreach( $crawler_urls as $crawler_single_url ) {
						$single_url = parse_url( $crawler_single_url->$source_item_attr );
						if ( empty( $single_url['host'] ) ) {
							$parse_crawler_url = parse_url( $crawler_url );
							$item_single_url = $parse_crawler_url['scheme'] . '://' . $parse_crawler_url['host'] . $crawler_single_url->$source_item_attr;
						} else if ( empty( $single_url['scheme'] ) ) {
							$item_single_url = 'http://' . ltrim( $crawler_single_url->$source_item_attr, '/' );
						} else {
							$item_single_url = $crawler_single_url->$source_item_attr;
						}
						wp_crawler_insert_post( $item_single_url, $crawler_source, $crawler_args );
					}
				}
			}
		}
	}
	wp_reset_postdata();
}

register_activation_hook(__FILE__, 'wp_crawler_activation');

function wp_crawler_activation() {
	$timestamp = wp_next_scheduled( 'wp_crawler_hourly_event' );
	if( $timestamp == false ){
		wp_schedule_event( time(), 'hourly', 'wp_crawler_hourly_event' );
	}

	$timestamp_2 = wp_next_scheduled( 'wp_crawler_twicedaily_event' );
	if( $timestamp_2 == false ){
		wp_schedule_event( time(), 'twicedaily', 'wp_crawler_twicedaily_event' );
	}

	$timestamp_3 = wp_next_scheduled( 'wp_crawler_daily_event' );
	if( $timestamp_3 == false ){
		wp_schedule_event( time(), 'daily', 'wp_crawler_daily_event' );
	}
}

add_action( 'wp_crawler_hourly_event', 'wp_crawler_do_this_hourly' );
function wp_crawler_do_this_hourly() {
	wp_crawler_schedules_run( 'post_type=wp-crawler-schedule&post_status=publish&meta_key=_wp_crawler_schedule_recurrence&meta_value=hourly' );
}

add_action( 'wp_crawler_twicedaily_event', 'wp_crawler_do_this_twicedaily' );
function wp_crawler_do_this_twicedaily() {
	wp_crawler_schedules_run( 'post_type=wp-crawler-schedule&post_status=publish&meta_key=_wp_crawler_schedule_recurrence&meta_value=twicedaily' );
}

add_action( 'wp_crawler_daily_event', 'wp_crawler_do_this_daily' );
function wp_crawler_do_this_daily() {
	wp_crawler_schedules_run( 'post_type=wp-crawler-schedule&post_status=publish&meta_key=_wp_crawler_schedule_recurrence&meta_value=daily' );
}

register_deactivation_hook(__FILE__, 'wp_crawler_deactivation');
function wp_crawler_deactivation() {
	wp_clear_scheduled_hook( 'wp_crawler_hourly_event');
	wp_clear_scheduled_hook( 'wp_crawler_twicedaily_event');
	wp_clear_scheduled_hook( 'wp_crawler_daily_event');
}

/*-----------------------------------------------------------------------------------*/
/*	Custom Admin Script & Style
/*-----------------------------------------------------------------------------------*/
add_action( 'admin_enqueue_scripts', 'wp_crawler_admin_scripts' );
function wp_crawler_admin_scripts() {
	wp_enqueue_style( 'wp-crawler-style', WP_CRAWLER_URL . 'assets/css/admin-style.css' );
}

/*-----------------------------------------------------------------------------------*/
/*	Add Admin Menu
/*-----------------------------------------------------------------------------------*/
function wp_crawler_admin_menu() {
	add_submenu_page( 'edit.php?post_type=wp-crawler-source', __( 'Run', 'wp-crawler'), __('Run', 'wp-crawler'), 'manage_options', 'wp-crawler', 'wp_crawler_run' );
}
add_action( 'admin_menu', 'wp_crawler_admin_menu' );

/*-----------------------------------------------------------------------------------*/
/*	Settings Page
/*-----------------------------------------------------------------------------------*/
function wp_crawler_run() { ?>
	<div class="wrap">
		<h1>Run Crawler</h1>
		<?php
		if ( ! empty( $_POST['crawler_url'] ) && ! empty( $_POST['crawler_source'] )  ) {
			$crawler_url = $_POST['crawler_url'];
			$crawler_source = $_POST['crawler_source'];
			$crawler_args = array(
				'post_status' => $_POST['crawler_post_status'],
				'post_author' => $_POST['crawler_post_author'],
				'post_type' => $_POST['crawler_post_type'],
				'post_cat' => $_POST['cat'],
				'translate' => $_POST['crawler_translate'],
				'translate_from' => $_POST['crawler_translate_from'],
				'translate_to' => $_POST['crawler_translate_to'],
				'translate_title' => $_POST['crawler_translate_title'],
				'translate_content' => $_POST['crawler_translate_content'],
			);

			if ( 'listing' === $_POST['crawler_type'] ) {
				$source_item_selector = get_post_meta( $crawler_source, '_wp_crawler_source_item', true );
				$source_item_attr = get_post_meta( $crawler_source, '_wp_crawler_source_item_attr', true );
				if ( empty( $source_item_attr ) ) {
					$source_item_attr = 'href';
				}
				if ( ! empty( $source_item_selector ) ) {
					$context_options = array(
						'http' => array(
							'method' => "GET",
							'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36"
						)
					);
					$context = stream_context_create($context_options);
					$html = file_get_html( $crawler_url, false, $context );
					if ( $html ) {
						$crawler_urls = $html->find( $source_item_selector );
						if ( empty ( $crawler_urls ) ) {
							echo '<div class="updated notice notice-error below-h2"><p>Can\'t Get Single Item\'s URLs from Selector: <code>' . $source_item_selector . '</code>. Please <a href="' . get_home_url() . '/wp-admin/post.php?post=' . $crawler_source . '&action=edit">Update Source</a>.</p></div>';
						} else {
							foreach( $crawler_urls as $crawler_single_url ) {
								$single_url = parse_url( $crawler_single_url->$source_item_attr );
								if ( empty( $single_url['host'] ) ) {
									$parse_crawler_url = parse_url( $crawler_url );
									$item_single_url = $parse_crawler_url['scheme'] . '://' . $parse_crawler_url['host'] . $crawler_single_url->$source_item_attr;
								} else if ( empty( $single_url['scheme'] ) ) {
									$item_single_url = 'http://' . ltrim( $crawler_single_url->$source_item_attr, '/' );
								} else {
									$item_single_url = $crawler_single_url->$source_item_attr;
								}
								wp_crawler_insert_post( $item_single_url, $crawler_source, $crawler_args );
							}
						}
					} else {
						echo '<div class="updated notice notice-error below-h2"><p>Can\'t Read This Site</p></div>';
					}
				} else { ?>
				<div class="updated notice notice-error below-h2">
					<p><?php _e( 'Missing Selector for Item in Listing Page.', 'wp-crawler' ); ?></p>
				</div>
				<?php }
			} else {
				wp_crawler_insert_post( $crawler_url, $crawler_source, $crawler_args );
			}
		} else { ?>
		<?php if ( ! empty( $_POST['crawler_source'] ) ) : ?>
			<div class="updated notice notice-error below-h2">
				<p><?php _e( 'Please Input Source URL.', 'wp-crawler' ); ?></p>
			</div>
		<?php endif; ?>
		<?php
		$the_query = new WP_Query( array( 'post_type' => 'wp-crawler-source', 'posts_per_page' => -1 ) );
		if ( $the_query->have_posts() ) :
		?>
		<form action="<?php __FILE__ ?>" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="crawler_source">Source</label>
					</th>
					<td>
						<select name="crawler_source" id="crawler_source" class="postform">
							<?php  $i = 0; while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
							<option value="<?php echo get_the_ID(); ?>"<?php echo ( 0 === $i ) ? ' selected="selected"' : ''; ?>><?php the_title(); ?></option>
							<?php $i++; endwhile; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="crawler_type">Type</label>
					</th>
					<td>
						<select name="crawler_type" id="crawler_type" class="postform">
							<option value="single" selected="selected">Single Item</option>
							<option value="listing">Multiple Items</option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="crawler_url">URL</label></th>
					<td><input name="crawler_url" id="crawler_url" type="text" class="regular-text code"></td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cat">Post Category</label>
					</th>
					<td>
						<?php wp_dropdown_categories( 'hide_empty=0' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="crawler_post_status">Post Status</label>
					</th>
					<td>
						<select name="crawler_post_status" id="crawler_post_status" class="postform">
							<option value="draft" selected="selected">Draft</option>
							<option value="publish">Publish</option>
							<option value="pending">Pending</option>
							<option value="future">Future</option>
							<option value="private">Private</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="crawler_post_author">Post Author</label>
					</th>
					<td><input name="crawler_post_author" id="crawler_post_author" type="text" class="regular-text code" placeholder="1"></td>
				</tr>
				<tr>
					<th scope="row">
						<label for="crawler_post_type">Post Type</label>
					</th>
					<td><input name="crawler_post_type" id="crawler_post_type" type="text" class="regular-text code" placeholder="post"></td>
				</tr>
				<tr>
					<th scope="row">
						<label for="crawler_translate">Auto Translate?</label>
					</th>
					<td>
						<input name="crawler_translate" id="crawler_translate" type="checkbox"> <label for="crawler_translate">Check this box to enable translate your content to other language</label>
						<div>
							<p>
								From
								<select name="crawler_translate_from">
									<option value="auto">Auto</option>
									<option value="af">Afrikaans</option>
									<option value="sq">Albanian</option>
									<option value="ar">Arabic</option>
									<option value="hy">Armenian</option>
									<option value="az">Azerbaijani</option>
									<option value="eu">Basque</option>
									<option value="be">Belarusian</option>
									<option value="bn">Bengali</option>
									<option value="bs">Bosnian</option>
									<option value="bg">Bulgarian</option>
									<option value="ca">Catalan</option>
									<option value="ceb">Cebuano</option>
									<option value="ny">Chichewa</option>
									<option value="zh-CN">Chinese Simplified</option>
									<option value="zh-TW">Chinese Traditional</option>
									<option value="hr">Croatian</option>
									<option value="cs">Czech</option>
									<option value="da">Danish</option>
									<option value="nl">Dutch</option>
									<option value="en">English</option>
									<option value="eo">Esperanto</option>
									<option value="et">Estonian</option>
									<option value="tl">Filipino</option>
									<option value="fi">Finnish</option>
									<option value="fr">French</option>
									<option value="gl">Galician</option>
									<option value="ka">Georgian</option>
									<option value="de">German</option>
									<option value="el">Greek</option>
									<option value="gu">Gujarati</option>
									<option value="ht">Haitian Creole</option>
									<option value="ha">Hausa</option>
									<option value="iw">Hebrew</option>
									<option value="hi">Hindi</option>
									<option value="hmn">Hmong</option>
									<option value="hu">Hungarian</option>
									<option value="is">Icelandic</option>
									<option value="ig">Igbo</option>
									<option value="id">Indonesian</option>
									<option value="ga">Irish</option>
									<option value="it">Italian</option>
									<option value="ja">Japanese</option>
									<option value="jw">Javanese</option>
									<option value="kn">Kannada</option>
									<option value="kk">Kazakh</option>
									<option value="km">Khmer</option>
									<option value="ko">Korean</option>
									<option value="lo">Lao</option>
									<option value="la">Latin</option>
									<option value="lv">Latvian</option>
									<option value="lt">Lithuanian</option>
									<option value="mk">Macedonian</option>
									<option value="mg">Malagasy</option>
									<option value="ms">Malay</option>
									<option value="ml">Malayalam</option>
									<option value="mt">Maltese</option>
									<option value="mi">Maori</option>
									<option value="mr">Marathi</option>
									<option value="mn">Mongolian</option>
									<option value="my">Myanmar (Burmese)</option>
									<option value="ne">Nepali</option>
									<option value="no">Norwegian</option>
									<option value="fa">Persian</option>
									<option value="pl">Polish</option>
									<option value="pt">Portuguese</option>
									<option value="ma">Punjabi</option>
									<option value="ro">Romanian</option>
									<option value="ru">Russian</option>
									<option value="sr">Serbian</option>
									<option value="st">Sesotho</option>
									<option value="si">Sinhala</option>
									<option value="sk">Slovak</option>
									<option value="sl">Slovenian</option>
									<option value="so">Somali</option>
									<option value="es">Spanish</option>
									<option value="su">Sudanese</option>
									<option value="sw">Swahili</option>
									<option value="sv">Swedish</option>
									<option value="tg">Tajik</option>
									<option value="ta">Tamil</option>
									<option value="te">Telugu</option>
									<option value="th">Thai</option>
									<option value="tr">Turkish</option>
									<option value="uk">Ukrainian</option>
									<option value="ur">Urdu</option>
									<option value="uz">Uzbek</option>
									<option value="vi">Vietnamese</option>
									<option value="cy">Welsh</option>
									<option value="yi">Yiddish</option>
									<option value="yo">Yoruba</option>
									<option value="z">Zulu</option>
								</select>
								To
								<select name="crawler_translate_to">
									<option value="af">Afrikaans</option>
									<option value="sq">Albanian</option>
									<option value="ar">Arabic</option>
									<option value="hy">Armenian</option>
									<option value="az">Azerbaijani</option>
									<option value="eu">Basque</option>
									<option value="be">Belarusian</option>
									<option value="bn">Bengali</option>
									<option value="bs">Bosnian</option>
									<option value="bg">Bulgarian</option>
									<option value="ca">Catalan</option>
									<option value="ceb">Cebuano</option>
									<option value="ny">Chichewa</option>
									<option value="zh-CN">Chinese Simplified</option>
									<option value="zh-TW">Chinese Traditional</option>
									<option value="hr">Croatian</option>
									<option value="cs">Czech</option>
									<option value="da">Danish</option>
									<option value="nl">Dutch</option>
									<option value="en">English</option>
									<option value="eo">Esperanto</option>
									<option value="et">Estonian</option>
									<option value="tl">Filipino</option>
									<option value="fi">Finnish</option>
									<option value="fr">French</option>
									<option value="gl">Galician</option>
									<option value="ka">Georgian</option>
									<option value="de">German</option>
									<option value="el">Greek</option>
									<option value="gu">Gujarati</option>
									<option value="ht">Haitian Creole</option>
									<option value="ha">Hausa</option>
									<option value="iw">Hebrew</option>
									<option value="hi">Hindi</option>
									<option value="hmn">Hmong</option>
									<option value="hu">Hungarian</option>
									<option value="is">Icelandic</option>
									<option value="ig">Igbo</option>
									<option value="id">Indonesian</option>
									<option value="ga">Irish</option>
									<option value="it">Italian</option>
									<option value="ja">Japanese</option>
									<option value="jw">Javanese</option>
									<option value="kn">Kannada</option>
									<option value="kk">Kazakh</option>
									<option value="km">Khmer</option>
									<option value="ko">Korean</option>
									<option value="lo">Lao</option>
									<option value="la">Latin</option>
									<option value="lv">Latvian</option>
									<option value="lt">Lithuanian</option>
									<option value="mk">Macedonian</option>
									<option value="mg">Malagasy</option>
									<option value="ms">Malay</option>
									<option value="ml">Malayalam</option>
									<option value="mt">Maltese</option>
									<option value="mi">Maori</option>
									<option value="mr">Marathi</option>
									<option value="mn">Mongolian</option>
									<option value="my">Myanmar (Burmese)</option>
									<option value="ne">Nepali</option>
									<option value="no">Norwegian</option>
									<option value="fa">Persian</option>
									<option value="pl">Polish</option>
									<option value="pt">Portuguese</option>
									<option value="ma">Punjabi</option>
									<option value="ro">Romanian</option>
									<option value="ru">Russian</option>
									<option value="sr">Serbian</option>
									<option value="st">Sesotho</option>
									<option value="si">Sinhala</option>
									<option value="sk">Slovak</option>
									<option value="sl">Slovenian</option>
									<option value="so">Somali</option>
									<option value="es">Spanish</option>
									<option value="su">Sudanese</option>
									<option value="sw">Swahili</option>
									<option value="sv">Swedish</option>
									<option value="tg">Tajik</option>
									<option value="ta">Tamil</option>
									<option value="te">Telugu</option>
									<option value="th">Thai</option>
									<option value="tr">Turkish</option>
									<option value="uk">Ukrainian</option>
									<option value="ur">Urdu</option>
									<option value="uz">Uzbek</option>
									<option value="vi">Vietnamese</option>
									<option value="cy">Welsh</option>
									<option value="yi">Yiddish</option>
									<option value="yo">Yoruba</option>
									<option value="z">Zulu</option>
								</select>
							</p>
							<p><label><input name="crawler_translate_title" id="crawler_translate_title" type="checkbox"> Title?</label></p>
							<p><label><input name="crawler_translate_content" id="crawler_translate_content" type="checkbox"> Content?</label></p>
						</div>
						<style type="text/css">#crawler_translate ~ div { display: none } #crawler_translate:checked ~ div { display: block; margin-top: 10px; }</style>
					</td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" name="submit" value="Run Crawler" class="button-primary" /></td>
				</tr>
			</tbody>
		</table>
		</form>
		<?php else : ?>
			<div class="updated notice notice-error below-h2">
				<p>Please <a href="post-new.php?post_type=wp-crawler-source">Add Source</a> Before Run.</p>
			</div>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
		<?php } ?>
	</div>
<?php }

/*-----------------------------------------------------------------------------------*/
/*	Insert Featured Image
/*-----------------------------------------------------------------------------------*/
function wp_crawler_set_featured_image( $post_id, $image_url, $item_url ) {
	$parsed_item_url = parse_url( $item_url );
	$parsed_image_url = parse_url( $image_url );
	if ( empty( $parsed_image_url['host'] ) ) {
		$image_url = $parsed_item_url['scheme'] . '://' . $parsed_item_url['host'] . $image_url;
	} else if ( strpos( $parsed_image_url['host'], 'vk.me' ) ) {
		$image_url = substr( $image_url, 0, strpos( $image_url, '.jpg' ) + 4 );
	}

	$upload_folder = ABSPATH . "wp-content/uploads";
	$filename = $upload_folder . '/thumbnail-for-' . $post_id . '.jpg';
	if ( false === file_exists( $upload_folder ) ) {
		chmod( ABSPATH . 'wp-content/uploads/', 0755 );
		mkdir( $upload_folder );
		chmod( $upload_folder, 0755 );
	}
	copy( $image_url, $filename );
	$wp_filetype = wp_check_filetype( basename( $filename ), null );
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => $post_id,
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );

	require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id,  $attach_data );
	update_post_meta( $post_id,'_thumbnail_id', $attach_id );
}

/*-----------------------------------------------------------------------------------*/
/*	Insert Post
/*-----------------------------------------------------------------------------------*/
use Stichoza\GoogleTranslate\TranslateClient;

function wp_crawler_insert_post( $url, $source, $args ) {
	if ( ! empty( $url ) && ! empty( $source ) ) {

		// Init Translate
		if ( isset( $args['translate'] ) ) {
			$translate = new TranslateClient();
			$translate_from = ( 'auto' === $args['translate_from'] ) ? null : $args['translate_from'];
			$translate_to = $args['translate_to'];
			$translate->setSource( $translate_from );
			$translate->setTarget( $translate_to );
		}

		$context_options = array(
			'http' => array(
				'method' => "GET",
				'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36"
			)
		);
		$context = stream_context_create($context_options);		
		$html = file_get_html( $url, false, $context );
		//$html = file_get_html( $url );
		$title_selector = get_post_meta( $source, '_wp_crawler_source_title', true );
		$title_attr = get_post_meta( $source, '_wp_crawler_source_title_attr', true );
		if ( empty( $title_attr ) ) $title_attr = 'innertext';
		if ( $title_selector ) {
			$title = trim( $html->find( $title_selector, 0 )->$title_attr );

			//Translate Title
			if ( isset( $args['translate'] ) && isset( $args['translate_title'] ) ) {
				$title = $translate->translate( $title );
			}
		} else {
			$title = '';
		}

		$content_selector = get_post_meta( $source, '_wp_crawler_source_content', true );
		$content_attr = get_post_meta( $source, '_wp_crawler_source_content_attr', true );
		if ( empty( $content_attr ) ) $content_attr = 'innertext';
		if ( $content_selector ) {
			$content = trim( $html->find( $content_selector, 0 )->$content_attr );

			//Translate Content
			if ( isset( $args['translate'] ) && isset( $args['translate_content'] ) ) {
				$content = $translate->translate( $content );
			}
		} else {
			$content = '';
		}

		$excerpt_selector = get_post_meta( $source, '_wp_crawler_source_excerpt', true );
		$excerpt_attr = get_post_meta( $source, '_wp_crawler_source_excerpt_attr', true );
		if ( empty( $excerpt_attr ) ) $excerpt_attr = 'innertext';
		if ( $excerpt_selector ) {
			$excerpt = trim( $html->find( $excerpt_selector, 0 )->$excerpt_attr );
		} else {
			$excerpt = '';
		}

		$date_selector = get_post_meta( $source, '_wp_crawler_source_date', true );
		$date_attr = get_post_meta( $source, '_wp_crawler_source_date_attr', true );
		if ( empty( $date_attr ) ) $date_attr = 'innertext';
		if ( $date_selector ) {
			$mystring_date = $html->find( $date_selector, 0 )->$date_attr;
  			$replace_string_1 = str_replace('/','-',$mystring_date);
			echo 'replace_string_1 = '.$replace_string_1.'<br>'; 
			$replace_string = str_replace('&nbsp;', '/ /', $replace_string_1);
            echo 'replace_string new = '.$replace_string.'<br>'; 
			$split_string = preg_split('/ /', $replace_string);
            echo 'split_string = '.$split_string.'<br>';
			$mydate = NULL;
			$mytime = NULL;
			$strdate = '';
			foreach ($split_string as $value)
			{
				echo 'value = '.$value.'<br>';
				if (strpos($value, '-') !== false && is_null($mydate) &&  strlen($value) > 10)
				{
					$mydate = trim($value);
					$strdate = $mydate;					
				}
				if (strpos($value, ':') !== false && is_null($mytime) && strlen($value) > 5) 
				{
					$mytime = trim($value);			
				}
				if(!is_null($mydate) && !is_null($mytime))	
				{
					break;
				}
			}
			$strdate = $mydate.' '.$mytime;	
			$strdate = $mydate.' '.$mytime;	
			$mydatetime = date_create($strdate);
			$date = date_format($mydatetime, 'Y-m-d H:i:s');
            echo 'date = '.$date.'<br>';
		} else {
			$date = '';
		}
		if ( wp_crawler_check_exists( $url ) ) {
			$insert_post = false;
			$post_id = wp_crawler_check_exists( $url );
		} else {
			$insert_post = true;
			$post_id = '';
		}

		if ( $args['post_status'] && in_array( $args['post_status'], array( 'draft', 'publish', 'pending', 'future', 'private' ) ) ) {
			$status = $args['post_status'];
		} else {
			$status = 'draft';
		}

		if ( $args['post_author'] && ( false !== get_user_by( 'id', $args['post_author'] ) ) ) {
			$author_id = $args['post_author'];
		} else {
			$author_id = 1;
		}

		if ( $args['post_type'] && post_type_exists(  $args['post_type'] ) ) {
			$post_type = $args['post_type'];
		} else {
			$post_type = 'post';
		}

		if ( $args['post_cat'] && $args['post_cat'] != 1 ) {
			$post_category = array( $args['post_cat'] );
		} else {
			$post_category = '';
		}

		// Insert Post
		$post_data = array(
			'ID'        => $post_id,
			'post_title'     => $title,
			'post_content'   => $content,
			'post_excerpt'   => $excerpt,
			'post_date'      => $date,
			'post_status'    => $status,
			'post_author'    => $author_id,
			'post_type'      => $post_type,
			'comment_status' => 'open',
			'post_category'  => $post_category,
		);

		if($insert_post === true)
		{
			$post_id = wp_insert_post( $post_data );

			if ( is_wp_error( $post_id ) ) {
				echo '<div class="updated notice notice-error below-h2"><p>' . $return->get_error_message() . '</p></div>';
			} else {

				// Insert Thumbnail
				$thumbnail_selector = get_post_meta( $source, '_wp_crawler_source_thumbnail', true );
				$thumbnail_attr = get_post_meta( $source, '_wp_crawler_source_thumbnail_attr', true );
				if ( empty( $thumbnail_attr ) ) $thumbnail_attr = 'src';
				if ( $thumbnail_selector ) {
					$thumbnail_url = $html->find( $thumbnail_selector, 0 )->$thumbnail_attr;
					if(empty($thumbnail_url))
					{
						//$thumbnail_url = 'http://104.155.188.212/wp-content/uploads/2016/10/Default_Thumbnail.png';
						echo 'Emtpy thumbnail - set default <br>';
						set_post_thumbnail( $post_id, 192 );
					}
					else
					{
						echo 'Thumbnail'.$thumbnail_url.'<br>';
						wp_crawler_set_featured_image( $post_id, $thumbnail_url, $url );
					}
				}

				// Insert Taxonomies
				$taxonomies = get_post_meta( $source, '_wp_crawler_source_taxonomies', true );
				if ( $taxonomies ) {
					foreach ( $taxonomies as $taxonomy ) {
						if ( empty( $taxonomy['_wp_crawler_source_taxonomy_attr'] ) ) {
							$taxonomy_attr = 'innertext';
						} else {
							$taxonomy_attr = $taxonomy['_wp_crawler_source_taxonomy_attr'];
						}
						if ( 'yes' === $taxonomy['_wp_crawler_source_taxonomy_multiple'] ) {
							$taxonomy_array = '';
							foreach( $html->find( $taxonomy['_wp_crawler_source_taxonomy_selector'] ) as $taxonomy_name ) {
								$taxonomy_array[] = $taxonomy_name->$taxonomy_attr;
							}
							if ( $taxonomy_array && ! empty( $taxonomy['_wp_crawler_source_taxonomy_slug'] ) ) {
								wp_set_object_terms( $post_id, $taxonomy_array, $taxonomy['_wp_crawler_source_taxonomy_slug'] );
							}
						} else {
							if ( ! empty ( $taxonomy['_wp_crawler_source_taxonomy_slug'] ) ) {
								wp_set_object_terms( $post_id, array( trim( $html->find( $custom_field['_wp_crawler_source_taxonomy_selector'], 0 )->$taxonomy_attr ) ), $taxonomy['_wp_crawler_source_taxonomy_slug'] );
							}
						}
					}
				}

				// Insert Custom Fields
				$custom_fields = get_post_meta( $source, '_wp_crawler_source_custom_fields', true );
				if ( $custom_fields ) {
					foreach ( $custom_fields as $custom_field ) {
						if ( empty( $custom_field['_wp_crawler_source_custom_field_attr'] ) ) {
							$custom_field_attr = 'innertext';
						} else {
							$custom_field_attr = $custom_field['_wp_crawler_source_custom_field_attr'];
						}
						update_post_meta( $post_id, $custom_field['_wp_crawler_source_custom_field_slug'], maybe_unserialize( trim( $html->find( $custom_field['_wp_crawler_source_custom_field_selector'], 0 )->$custom_field_attr ) ) );
					}
				}
				update_post_meta( $post_id, '_original_url', $url );
				echo '<div class="updated notice notice-success below-h2"><p>Well Done!</p></div>';
			}
		}
	} else {
		echo '<div class="updated notice notice-success below-h2"><p>Missing Source &amp; URL</p></div>';
	}
}

/*-----------------------------------------------------------------------------------*/
/*	Check Post Exists
/*-----------------------------------------------------------------------------------*/
function wp_crawler_check_exists( $url ) {
	global $wpdb;
	$url_in_db = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE `meta_value` = '$url' ");
	if ( $url_in_db ) {
		return $url_in_db->post_id;
	} else {
		return false;
	}
}
