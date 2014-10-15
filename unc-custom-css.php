<?php
/*
Plugin Name: UNC Custom CSS
Plugin URI: http://github.com/michaelryanmcneill/UNC-custom-css
Description: Allows for Custom CSS to be easily added to WordPress.
Version: 1.0
Author: Michael McNeill (webdotunc)
Author URI: http://michaelryanmcneill.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Forked from CTLT Dev's Improved Simpler CSS (https://wordpress.org/plugins/imporved-simpler-css/)
*/

/**
 * Custom_CSS_to_CPT class.
 */
class Custom_CSS_to_CPT {
	public $post_type;
	public $single;
	public $prefix;
	public $singular_data;

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $post_type
	 * @param mixed $single
	 * @param mixed $prefix
	 * @return void
	 */
	function __construct( $post_type, $single, $prefix ) {
		$this->single 			= $single;
		$this->post_type 		= $post_type;
		$this->prefix 			= $prefix;
		$this->singular_data    = null;
	}
	
	/**
	 * register function.
	 * 
	 * @access public
	 * @return void
	 */
	function register() {		
		$args = array(
		    'public' => false,
		    'publicly_queryable' => false,
		    'show_ui' => false, 
		    'show_in_menu' => false, 
		    'query_var' => false,
		    'rewrite' => array( 'slug' => $this->post_type ),
		    'capability_type' => 'post',
		    'has_archive' => false, 
		    'hierarchical' => false,
		    'menu_position' => null,
		    'supports' => array( 'revisions' )
		  ); 
		register_post_type( (string)$this->post_type, $args );
	}
	
	/**
	 * get function.
	 * 
	 * @access public
	 * @return void
	 */
	function get( $from_cache = true ) {
		if( $this->singular_data && $from_cache )
			return $this->singular_data;
		$args = array(
			'numberposts'     => 1,
			'orderby'         => 'post_date',
			'order'           => 'DESC',
			'post_type'       => $this->post_type,
			'post_status'     => 'publish',
			'suppress_filters' => true );
		$this->singular_data = get_posts( $args );
		$this->singular_data = array_shift( $this->singular_data );
		return $this->singular_data;
	}
	
	/**
	 * update function.
	 * 
	 * @access public
	 * @param mixed $content
	 * @return void
	 */
	function update( $content ) {
		$data = $this->get();
		if( is_object( $data ) ):
			$data = get_object_vars( $data );
		endif;
		if( !isset( $data['post_id'] ) ) {
			$data['post_content'] = $content;
			$data['post_title']   = 'Custom ';
			$data['post_status']  = 'publish';
			$data['post_type']    = $this->post_type;
			$post_id = wp_insert_post( $data );
		} else {		
			$data['post_content'] = $content;
			$post_id = wp_update_post( $data );
			
		}
		return $post_id;
	}
}

/**
 * UNC_Custom_CSS class.
 */
class UNC_Custom_CSS {
	static $object;
	
	/**
	 * init function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function init() {
		add_action('init', array( __CLASS__, 'start' ) );
				
		add_action( 'admin_init' , array(__CLASS__, 'admin' ) );
		add_action( 'admin_menu' , array(__CLASS__, 'admin_menu' ) );
		
		add_action( 'wp_ajax_submit_css', array(__CLASS__, 'ajax' ) );
		
		add_action( 'wp_enqueue_scripts', 	array( __CLASS__, 'load_scripts' ) );
		add_action( 'admin_bar_menu', 		array(__CLASS__, 'adminbar_link' ),100 );
		
		add_action( 'wp_head', array(__CLASS__, 'include_css' ) );				
	}
	
	/**
	 * start function.
	 * 
	 * @access public
	 * @return void
	 */

	public static function start(){
		self::$object = new Custom_CSS_to_CPT('s-custom-css', 'true', 'css' );
		self::$object->register();
	}
	
	/**
	 * load_scripts function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function load_scripts() {
		if ( !(current_user_can( 'manage_options' )) || !is_admin_bar_showing() || is_admin() )
			return true;
		wp_enqueue_script( 'css-edit-window', plugins_url('js/edit-window.js', __FILE__), array('jquery'), 1, true );		
		$custom_css_options = array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'loader_image' => admin_url('images/wpspin_light.gif'),
			'editor' => plugins_url('js/editor.js', __FILE__),
			'load_ace' => plugins_url('js/ace/ace.js', __FILE__)
		);
		wp_localize_script( 'css-edit-window', 'custom_css_options', $custom_css_options );	
	}

	/**
	 * include_css function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function include_css() {
		echo '<!-- Begin Custom CSS -->'. "\n";
		echo '<style type="text/css">' . "\n";
		echo self::get_css();
		echo '</style><!-- End Custom CSS -->' . "\n";
	}

	/**
	 * adminbar_link function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function adminbar_link() {
		global $wp_admin_bar;
		if ( !(current_user_can( 'manage_options' )) || !is_admin_bar_showing() || is_admin() )
			return;
		$wp_admin_bar->add_menu( array(
	        'parent' => 'appearance',
	        'id' => 'custom-css',
	        'title' => __('Custom CSS'),
	        'href' => admin_url( 'themes.php?page=custom-css')
	    ) );
	}
	
	/**
	 * admin function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function admin() {
		global $pagenow;
		if( 'post.php' == $pagenow && isset($_GET['post']) && isset($_GET['revision']) && isset($_GET['message']) && '5' == $_GET['message'] ){
			$data = get_post($_GET['post'] );
			if( $data->post_type == 's-custom-css' ) {
				wp_redirect( admin_url('themes.php?page=custom-css&revision='.$_GET['revision']) );
			}
		}
		add_action( 'unc_custom_css_enqueue_scripts', 'unc_custom_css_admin_enqueue_scripts');
		add_action( 'unc_custom_css_print_styles', 'unc_custom_css_admin_print_styles');
	}
	
	/**
	 * admin_menu function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function admin_menu(){					
		$page_hook_suffix = add_theme_page( 'Custom CSS', 'Custom CSS',  'manage_options', 'custom-css', array( __CLASS__, 'admin_page' ) );	
		add_action( 'admin_print_scripts-' . $page_hook_suffix, array(__CLASS__, 'admin_scripts' ) );
	}
	
	/**
	 * admin_scripts function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function admin_scripts() {
		wp_enqueue_style( 'custom-js-admin-styles', plugins_url( '/css/admin.css', __FILE__ ) );
		wp_register_script( 'acejs', plugins_url( 'js/ace/ace.js', __FILE__ ), '', '1.0', 'true' );
		wp_enqueue_script( 'acejs' );
		wp_register_script( 'aceinit', plugins_url( '/js/admin.js', __FILE__ ), array('acejs', 'jquery-ui-resizable'), '1.1', 'true' );
		wp_enqueue_script( 'aceinit' );
	}
	
	/**
	 * add_metabox function.
	 * 
	 * @access public
	 * @param mixed $css
	 * @return void
	 */
	public static function add_metabox( $css ) {
	}
	
	/**
	 * admin_page function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function admin_page() {
			add_meta_box( 'revisionsdiv', __( 'CSS Revisions', 'safecss' ), array('UNC_Custom_CSS', 'revisions_meta_box'), 's-custom-css', 'side' );
			$message = '';
			if( $_POST ) :
				$nonce = $_POST['update_custom_css_field'];
				if( wp_verify_nonce( $nonce, 'update_custom_css' ) ):
					UNC_Custom_CSS::update_css( $_POST['editor'] );
					$message = 'Custom CSS Successfully Updated!';
				endif;
			endif;
			$data = UNC_Custom_CSS::get( false );
			if( isset($_GET['revision']) )
				$message = "Selected Custom CSS revision restored."
			?>
			<div class="wrap">
				<div id="icon-themes" class="icon32"></div>
				<h2>Custom CSS</h2>
				<?php if( !empty( $message ) ): ?>
					<div class="updated below-h2" id="message"><p><?php echo $message; ?></p></div>
				<?php endif; ?>
				<p style='margin:0;'>New to CSS? Start with a <a href="http://www.htmldog.com/guides/cssbeginner/">beginner tutorial</a>. Questions?
				Ask in the <a href="http://forum.web.unc.edu/forum/support/">Support forum</a>.</p>
				<form action="themes.php?page=custom-css" method="post" >
					<?php wp_nonce_field( 'update_custom_css','update_custom_css_field' ); ?>
					<div class="metabox-holder has-right-sidebar">
						<div class="inner-sidebar">
							<div class="postbox">
								<h3><span>Update</span></h3>
								<div class="inside">
									<input class="button-primary" type="submit" name="publish" value="<?php _e( 'Save CSS' ); ?>" /> 
								</div>
							</div>
							<?php
						do_meta_boxes( 's-custom-css', 'side', $data );
						?>
						</div> <!-- .inner-sidebar -->
						<div id="post-body">
							<div id="post-body-content">
								<div id="global-editor-shell">
								<textarea  style="width:100%; height: 400px; resize: none;" id="editor" class="wp-editor-area" name="editor"><?php echo $data->post_content; ?></textarea>
								</div>
							</div> <!-- #post-body-content -->
						</div> <!-- #post-body -->
					</div> <!-- .metabox-holder -->
				</form>
			</div> <!-- .wrap -->
		<?php 
	}
	
	/**
	 * ajax function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function ajax() {	
		if( !current_user_can( 'manage_options' ) ):
			echo "Permission error. Try logging in again.";
			die();
		endif;
		if( isset( $_POST['css'] ) ):
			if( self::update_css(   $_POST['css'] ) ):
				echo "success";
			else:
				echo "Error saving data.";
			endif;
		endif;	
		die();
	} 
	
	/**
	 * update_css function.
	 * 
	 * @access public
	 * @param mixed $css (default: null)
	 * @return void
	 */
	public static function update_css( $css = null ) {
		return self::$object->update( strip_tags( $css ) );
	}
	
	
	/**
	 * get_css function.
	 * 
	 * @access public
	 * @return void
	 */
	public static function get( $form_cache = true ) {
		return self::$object->get( $form_cache );
	}
	
	/**
	 * get_css function.
	 * 
	 * @access public
	 * @param bool $form_cache (default: true)
	 * @return void
	 */
	public static function get_css( $form_cache = true ) {
		$css = self::$object->get( $form_cache );
		return $css->post_content;
	}
	
	/**
	 * post_revisions_meta_box function.
	 * 
	 * @access public
	 * @param mixed $safecss_post
	 * @return void
	 */
	public static function revisions_meta_box( $post ) {		
		wp_list_post_revisions( $post->ID );
	}
}

UNC_Custom_CSS::init();
