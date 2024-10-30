<?php
/*
Plugin Name: Lotos Likes
Plugin URI: https://excellentdynamics.biz/wordpress-plugins/
Description: Add "like" functionality to your posts and pages. Display your most liked posts via widget.
Version: 1.8
Author: ExcellentDynamics
Author URI: https://excellentdynamics.biz/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  lotoslikes
Domain Path:  /languages
*/

class LotosLikes {

    function __construct() 
    {	
    	add_action('init', array(&$this, 'lotos_likes_textdomain'));
    	add_action('admin_init', array(&$this, 'admin_init'));
        add_action('admin_menu', array(&$this, 'admin_menu'), 99);
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
        add_filter('the_content', array(&$this, 'the_content'));
        add_filter('the_excerpt', array(&$this, 'the_content'));
        add_filter('body_class', array(&$this, 'body_class'));
        add_action('publish_post', array(&$this, 'setup_likes'));
        add_action('wp_ajax_lotos-likes', array(&$this, 'ajax_callback'));
		add_action('wp_ajax_nopriv_lotos-likes', array(&$this, 'ajax_callback'));
        add_shortcode('lotos_likes', array(&$this, 'shortcode'));
        add_action('widgets_init', function(){register_widget("LotosLikes_Widget");});
	}

	function lotos_likes_textdomain() {
		// Set filter for plugin's languages directory
		$lotos_likes_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lotos_likes_lang_dir = apply_filters( 'lotos_likes_languages_directory', $lotos_likes_lang_dir );

		// Load the translations
		load_plugin_textdomain( 'lotoslikes', false, $lotos_likes_lang_dir );
	}
	
	function admin_init()
	{
		register_setting( 'lotos-likes', 'lotos_likes_settings', array(&$this, 'settings_validate') );
		add_settings_section( 'lotos-likes', '', array(&$this, 'section_intro'), 'lotos-likes' );

		add_settings_field( 'show_on', __( 'Automatically show likes on', 'lotoslikes' ), array(&$this, 'setting_show_on'), 'lotos-likes', 'lotos-likes' );
		add_settings_field( 'exclude_from', __( 'Exclude from Post/Page ID', 'lotoslikes' ), array(&$this, 'setting_exclude_from'), 'lotos-likes', 'lotos-likes' );
		add_settings_field( 'disable_css', __( 'Disable CSS', 'lotoslikes' ), array(&$this, 'setting_disable_css'), 'lotos-likes', 'lotos-likes' );
		add_settings_field( 'ajax_likes', __('AJAX Like Counts', 'lotoslikes'), array(&$this, 'setting_ajax_likes'), 'lotos-likes', 'lotos-likes');
		add_settings_field( 'zero_postfix', __( '0 Count Postfix', 'lotoslikes' ), array(&$this, 'setting_zero_postfix'), 'lotos-likes', 'lotos-likes' );
		add_settings_field( 'one_postfix', __( '1 Count Postfix', 'lotoslikes' ), array(&$this, 'setting_one_postfix'), 'lotos-likes', 'lotos-likes' );
		add_settings_field( 'more_postfix', __( 'More than 1 Count Postfix', 'lotoslikes' ), array(&$this, 'setting_more_postfix'), 'lotos-likes', 'lotos-likes' );
		add_settings_field( 'instructions', __( 'Shortcode and Template Tag', 'lotoslikes' ), array(&$this, 'setting_instructions'), 'lotos-likes', 'lotos-likes' );
	}
	
	function admin_menu() 
	{
		$icon_url = plugins_url( '/images/favicon.png', __FILE__ );
		$page_hook = add_menu_page( __( 'Lotos Likes Settings', 'lotoslikes'), 'Lotos Likes', 'update_core', 'lotos-likes', array(&$this, 'settings_page'), $icon_url );
		add_submenu_page( 'lotos-likes', __( 'Settings', 'lotoslikes' ), __( 'Lotos Likes Settings', 'lotoslikes' ), 'update_core', 'lotos-likes', array(&$this, 'settings_page') );
		// LotosFramework link
		add_submenu_page( 'lotosframework', 'Lotos Likes', 'Lotos Likes', 'update_core', 'lotos-likes', array(&$this, 'settings_page') );
	}
	
	function settings_page()
	{
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"></div>
			<h2><?php _e('Lotos Likes Settings', 'lotoslikes'); ?></h2>
			<?php if( isset($_GET['settings-updated']) && $_GET['settings-updated'] ){ ?>
			<div id="setting-error-settings_updated" class="updated settings-error"> 
				<p><strong><?php _e( 'Settings saved.', 'lotoslikes' ); ?></strong></p>
			</div>
			<?php } ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'lotos-likes' ); ?>
				<?php do_settings_sections( 'lotos-likes' ); ?>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'lotoslikes' ); ?>" /></p>
			</form>
		</div>
		<?php
	}
	
	function section_intro()
	{
	    ?>
		<p><?php _e('Lotos Likes allows you to display like icons throughout your site. Customize the output of Lotos Likes with this settings page.', 'lotoslikes'); ?></p>
		<p><?php _e('Check out our other free <a href="https://excellentdynamics.biz/wordpress-plugins/" target="_blank">plugins</a> and <a href="https://excellentdynamics.biz/wordpress-themes/" target="_blank">themes</a>.', 'lotoslikes'); ?></p>
		<?php
		
	}

	function setting_show_on()
	{
		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['add_to_posts']) ) $options['add_to_posts'] = '0';
		if( !isset($options['add_to_pages']) ) $options['add_to_pages'] = '0';
		if( !isset($options['add_to_other']) ) $options['add_to_other'] = '0';
		
		echo '<input type="hidden" name="lotos_likes_settings[add_to_posts]" value="0" />
		<label><input type="checkbox" name="lotos_likes_settings[add_to_posts]" value="1"'. (($options['add_to_posts']) ? ' checked="checked"' : '') .' />
		'. __('Posts', 'lotoslikes') .'</label><br />
		<input type="hidden" name="lotos_likes_settings[add_to_pages]" value="0" />
		<label><input type="checkbox" name="lotos_likes_settings[add_to_pages]" value="1"'. (($options['add_to_pages']) ? ' checked="checked"' : '') .' />
		'. __('Pages', 'lotoslikes') .'</label><br />
		<input type="hidden" name="lotos_likes_settings[add_to_other]" value="0" />
		<label><input type="checkbox" name="lotos_likes_settings[add_to_other]" value="1"'. (($options['add_to_other']) ? ' checked="checked"' : '') .' />
		'. __('Blog Index Page, Archive Pages, and Search Results', 'lotoslikes') .'</label><br />';
	}
	
	function setting_exclude_from()
	{
		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['exclude_from']) ) $options['exclude_from'] = '';
		
		echo '<input type="text" name="lotos_likes_settings[exclude_from]" class="regular-text" value="'. $options['exclude_from'] .'" />
		<p class="description">'. __('Comma separated list of post/page ID\'s (e.g. 4,7,87)', 'lotoslikes') . '</p>';
	}
	
	function setting_disable_css()
	{
		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['disable_css']) ) $options['disable_css'] = '0';
		
		echo '<input type="hidden" name="lotos_likes_settings[disable_css]" value="0" />
		<label><input type="checkbox" name="lotos_likes_settings[disable_css]" value="1"'. (($options['disable_css']) ? ' checked="checked"' : '') .' />' . __('I want to use my own CSS styles', 'lotoslikes') . '</label>';
		
		// Shutterbug conflict warning
		$theme_name = '';
		if(function_exists('wp_get_theme')) $theme_name = wp_get_theme();
		else $theme_name = get_current_theme();
		if(strtolower($theme_name) == 'shutterbug'){
    		echo '<br /><span class="description" style="color:red">'. __('We recommend you check this option when using the Shutterbug theme to avoid conflicts', 'lotoslikes') .'</span>';
		}
	}
	
	function setting_ajax_likes()
	{
	    $options = get_option( 'lotos_likes_settings' );
	    if( !isset($options['ajax_likes']) ) $options['ajax_likes'] = '0';
	    
	    echo '<input type="hidden" name="lotos_likes_settings[ajax_likes]" value="0" />
		<label><input type="checkbox" name="lotos_likes_settings[ajax_likes]" value="1"'. (($options['ajax_likes']) ? ' checked="checked"' : '') .' />
		' . __('AJAX Like Counts on page load', 'lotoslikes') . '</label><br />
		<span class="description">'. __('If you are using a cacheing plugin, you may want to dynamically load the like counts via AJAX.', 'lotoslikes') .'</span>';
	}
	
	function setting_zero_postfix()
	{
		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['zero_postfix']) ) $options['zero_postfix'] = '';

		$zero_postfix_clear = esc_html( $options['zero_postfix'] );
		
		echo '<input type="text" name="lotos_likes_settings[zero_postfix]" class="regular-text" value="'. $zero_postfix_clear .'" /><br />
		<span class="description">'. __('The text after the count when no one has liked a post/page. Leave blank for no text after the count.', 'lotoslikes') .'</span>';
	}
	
	function setting_one_postfix()
	{
		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['one_postfix']) ) $options['one_postfix'] = '';

		$one_postfix_clear = esc_html( $options['one_postfix'] );
		
		echo '<input type="text" name="lotos_likes_settings[one_postfix]" class="regular-text" value="'. $one_postfix_clear .'" /><br />
		<span class="description">'. __('The text after the count when one person has liked a post/page. Leave blank for no text after the count.', 'lotoslikes') .'</span>';
	}
	
	function setting_more_postfix()
	{
		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['more_postfix']) ) $options['more_postfix'] = '';

		$more_postfix_clear = esc_html( $options['more_postfix'] );
		
		echo '<input type="text" name="lotos_likes_settings[more_postfix]" class="regular-text" value="'. $more_postfix_clear .'" /><br />
		<span class="description">'. __('The text after the count when more than one person has liked a post/page. Leave blank for no text after the count.', 'lotoslikes') .'</span>';
	}
	
	function setting_instructions()
	{
		echo '<p>'. __('To use Lotos Likes in your posts and pages you can use the shortcode:', 'lotoslikes') .'</p>
		<p><code>[lotos_likes]</code></p>
		<p>'. __('To use Lotos Likes manually in your theme template use the following PHP code:', 'lotoslikes') .'</p>
		<p><code>&lt;?php if( function_exists(\'lotos_likes\') ) lotos_likes(); ?&gt;</code></p>';
	}
	
	function settings_validate($input)
	{
	    $input['exclude_from'] = str_replace(' ', '', trim(strip_tags($input['exclude_from'])));
		
		return $input;
	}
	
	function enqueue_scripts()
	{
	    $options = get_option( 'lotos_likes_settings' );
		if( !isset($options['disable_css']) ) $options['disable_css'] = '0';
		
		if(!$options['disable_css']) wp_enqueue_style( 'lotos-likes', plugins_url( '/styles/lotos-likes.css', __FILE__ ) );
		
		wp_enqueue_script( 'lotos-likes', plugins_url( '/scripts/lotos-likes.js', __FILE__ ), array('jquery') );
		wp_enqueue_script( 'jquery' ); 
		
		wp_localize_script( 'lotos-likes', 'lotos_likes', array('ajaxurl' => admin_url('admin-ajax.php')) );
	}
	
	function the_content( $content )
	{		
	    // Don't show on custom page templates
	    //if(is_page_template()) return $content;
	    
	    // Don't show on Stacked slides
	    if(get_post_type() == 'slide') return $content;
	    
		global $wp_current_filter;
		if ( in_array( 'get_the_excerpt', (array) $wp_current_filter ) ) {
			return $content;
		}
		
		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['add_to_posts']) ) $options['add_to_posts'] = '0';
		if( !isset($options['add_to_pages']) ) $options['add_to_pages'] = '0';
		if( !isset($options['add_to_other']) ) $options['add_to_other'] = '0';
		if( !isset($options['exclude_from']) ) $options['exclude_from'] = '';
		
		$ids = explode(',', $options['exclude_from']);
		if(in_array(get_the_ID(), $ids)) return $content;
		
		if(is_singular('post') && $options['add_to_posts']) $content .= $this->do_likes();
		if(is_page() && !is_front_page() && $options['add_to_pages']) $content .= $this->do_likes();
		if((is_front_page() || is_home() || is_category() || is_tag() || is_author() || is_date() || is_search()) && $options['add_to_other'] ) $content .= $this->do_likes();
		
		return $content;
	}
	/*init likes*/
	function setup_likes( $post_id ) 
	{
		if(!is_numeric($post_id)) return;
	
		add_post_meta($post_id, '_lotos_likes', '0', true);
	}
	
	function ajax_callback($post_id) 
	{

		$options = get_option( 'lotos_likes_settings' );
		if( !isset($options['add_to_posts']) ) $options['add_to_posts'] = '0';
		if( !isset($options['add_to_pages']) ) $options['add_to_pages'] = '0';
		if( !isset($options['add_to_other']) ) $options['add_to_other'] = '0';
		if( !isset($options['zero_postfix']) ) $options['zero_postfix'] = '';
		if( !isset($options['one_postfix']) ) $options['one_postfix'] = '';
		if( !isset($options['more_postfix']) ) $options['more_postfix'] = '';

		if( isset( $_POST['likes_id'] )) {
			$likes_id_clear = sanitize_text_field( $_POST['likes_id'] );
		    // Click event. Get and Update Count
			$post_id = str_replace('lotos-likes-', '', $likes_id_clear);
			echo $this->like_this($post_id, $options['zero_postfix'], $options['one_postfix'], $options['more_postfix'], 'update');
		} else {
			$post_id_clear = sanitize_text_field( $_POST['post_id'] );
		    // AJAXing data in. Get Count
			$post_id = str_replace('lotos-likes-', '', $post_id_clear);
			echo $this->like_this($post_id, $options['zero_postfix'], $options['one_postfix'], $options['more_postfix'], 'get');
		}
		
		exit;
	}
	
	function like_this($post_id, $zero_postfix = false, $one_postfix = false, $more_postfix = false, $action = 'get') 
	{
		if(!is_numeric($post_id)) return;
		$zero_postfix = strip_tags($zero_postfix);
		$one_postfix = strip_tags($one_postfix);
		$more_postfix = strip_tags($more_postfix);		
		
		switch($action) {
		
			case 'get':
				$likes = get_post_meta($post_id, '_lotos_likes', true);
				if( !$likes ){
					$likes = 0;
					add_post_meta($post_id, '_lotos_likes', $likes, true);
				}
				
				if( $likes == 0 ) { $postfix = $zero_postfix; }
				elseif( $likes == 1 ) { $postfix = $one_postfix; }
				else { $postfix = $more_postfix; }
				
				return '<span class="lotos-likes-count">'. $likes .'</span> <span class="lotos-likes-postfix">'. $postfix .'</span>';
				break;
				
			case 'update':
				$likes = get_post_meta($post_id, '_lotos_likes', true);
				if( isset($_COOKIE['lotos_likes_'. $post_id]) ) return $likes;
				
				$likes++;
				update_post_meta($post_id, '_lotos_likes', $likes);
				setcookie('lotos_likes_'. $post_id, $post_id, time()*20, '/');
				
				if( $likes == 0 ) { $postfix = $zero_postfix; }
				elseif( $likes == 1 ) { $postfix = $one_postfix; }
				else { $postfix = $more_postfix; }
				
				return '<span class="lotos-likes-count">'. $likes .'</span> <span class="lotos-likes-postfix">'. $postfix .'</span>';
				break;
		
		}
	}
	
	function shortcode( $atts )
	{
		extract( shortcode_atts( array(
		), $atts ) );
		
		return $this->do_likes();
	}
	
	function do_likes()
	{
		global $post;

        $options = get_option( 'lotos_likes_settings' );
		if( !isset($options['zero_postfix']) ) $options['zero_postfix'] = '';
		if( !isset($options['one_postfix']) ) $options['one_postfix'] = '';
		if( !isset($options['more_postfix']) ) $options['more_postfix'] = '';
		
		$output = $this->like_this($post->ID, $options['zero_postfix'], $options['one_postfix'], $options['more_postfix']);
  
  		$class = 'lotos-likes';
  		$title = __('Like this', 'lotoslikes');
		if( isset($_COOKIE['lotos_likes_'. $post->ID]) ){
			$class = 'lotos-likes active';
			$title = __('You already like this', 'lotoslikes');
		}
		
		return '<a href="#" class="'. $class .'" id="lotos-likes-'. $post->ID .'" title="'. $title .'">'. $output .'</a>';
	}
	
    function body_class($classes) {
        $options = get_option( 'lotos_likes_settings' );
        
        if( !isset($options['ajax_likes']) ) $options['ajax_likes'] = false;
        
        if( $options['ajax_likes'] ) {
        	$classes[] = 'ajax-lotos-likes';
    	}
    	return $classes;
    }
	
}
global $lotos_likes;
$lotos_likes = new LotosLikes();

/**
 * Template Tag
 */
function lotos_likes()
{
	global $lotos_likes;
    echo $lotos_likes->do_likes(); 
}

/**
 * Widget to display posts by likes popularity
 */

class LotosLikes_Widget extends WP_Widget {

	function __construct() {
		parent::__construct( 'lotos_likes_widget', 'Lotos Likes', array( 'description' => __('Displays your most popular posts sorted by most liked', 'lotoslikes') ) );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );
		$desc = $instance['description'];
		$posts = empty( $instance['posts'] ) ? 1 : $instance['posts'];
		$display_count = $instance['display_count'];

		// Output our widget
		echo $before_widget;
		if( !empty( $title ) ) echo $before_title . $title . $after_title;

		if( $desc ) echo '<p>' . $desc . '</p>';

		$likes_posts_args = array(
			'numberposts' => $posts,
			'orderby' => 'meta_value_num',
			'order' => 'DESC',
			'meta_key' => '_lotos_likes',
			'post_type' => 'post',
			'post_status' => 'publish'
		);
		$likes_posts = get_posts($likes_posts_args);

		echo '<ul class="lotos-likes-popular-posts">';
		foreach( $likes_posts as $likes_post ) {
			$count_output = '';
			if( $display_count ) {
				$count = get_post_meta( $likes_post->ID, '_lotos_likes', true);
				$count_output = " <span class='lotos-likes-count'>($count)</span>";
			}
			echo '<li><a href="' . get_permalink($likes_post->ID) . '">' . get_the_title($likes_post->ID) . '</a>' . $count_output . '</li>';
		}
		echo '</ul>';

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['description'] = strip_tags($new_instance['description'], '<a><b><strong><i><em><span>');
		$instance['posts'] = strip_tags($new_instance['posts']);
		$instance['display_count'] = strip_tags($new_instance['display_count']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance
		);

		$defaults = array(
			'title' => __('Popular Posts', 'lotoslikes'),
			'description' => '',
			'posts' => 5,
			'display_count' => 1
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = $instance['title'];
		$description = $instance['description'];
		$posts = $instance['posts'];
		$display_count = $instance['display_count'];
		?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('description'); ?>"><?php _e('Description:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>" type="text" value="<?php echo $description; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('posts'); ?>"><?php _e('Posts:'); ?></label> 
			<input id="<?php echo $this->get_field_id('posts'); ?>" name="<?php echo $this->get_field_name('posts'); ?>" type="text" value="<?php echo $posts; ?>" size="3" />
		</p>
		<p>
			<input id="<?php echo $this->get_field_id('display_count'); ?>" name="<?php echo $this->get_field_name('display_count'); ?>" type="checkbox" value="1" <?php checked( $display_count ); ?>>
			<label for="<?php echo $this->get_field_id('display_count'); ?>"><?php _e('Display like counts'); ?></label>
		</p>

		<?php
	}
}