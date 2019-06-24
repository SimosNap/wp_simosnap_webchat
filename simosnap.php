<?php
/*
Plugin Name: SimosNap Webchat
Plugin URI: http://wordpress.simosnap.org/
Description: SimosNap Network Internet Relay Chat plugin for wordpress, webchat and stats
Version: 1.0
Author: SimosNap IRC Network
Author URI: https://www.simosnap.org/
License: GPLv2
*/

function add_webchat_frame() {
    $webchat_id = get_option('webchat_id');
    if (!$webchat_id_) {
    // Create post object
    $my_post = array(
      'post_title'    => wp_strip_all_tags( 'Webchat' ),
      'post_content'  => '',
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => 'page',
      'page_template'  => 'chat-template.php'
    );

    // Insert the post into the database
    $page_id = wp_insert_post( $my_post );
    update_post_meta($page_id, '_wp_page_template', 'chat-template.php');
    update_option('webchat_id', $page_id);
    }
}

function remove_webchat_frame() {

    $page_id = get_option('webchat_id');
    wp_delete_post($page_id);

}

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'simosnap_install');
register_activation_hook(__FILE__, 'add_webchat_frame');

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'simosnap_remove' );
register_deactivation_hook( __FILE__, 'remove_webchat_frame' );


function simosnap_install() {
/* Creates new database field */
    $default = array(
        'channel'     => 'IRCHelp',
        'kiwiirc_id'   => '',
        'kiwiirc_title'   => 'KiwiIRC WebChat',
        'kiwiirc_myradio' => '0',
        'kiwiirc_mystreaming' => '',
        'kiwiirc_myradioname' => '',
        'kiwiirc_myradiourl' => '',
        'kiwiirc_theme' => 'default',
        'kiwiirc_layout' => 'compact',
        'kiwiirc_stateKey' => 'IRCHelp-'.md5(time())

    );
add_option("simosnap_general", $default , '', 'yes');
}

function simosnap_remove() {
/* Deletes the database field */
delete_option('simosnap_general');
}

$options = get_option('simosnap_general');
//add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null );

function simosnap_customize_register( $wp_customize ) {
    // Sidebar background
    $wp_customize->add_setting( 'tab_background', array(
        'default'   => '#60a1c1',
        'transport' => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'tab_background', array(
        'section' => 'colors',
        'label'   => esc_html__( 'Chat login Background', 'theme' ),
    ) ) );
 
    // Sidebar background
    $wp_customize->add_setting( 'tab_current_background', array(
        'default'   => '#0082c3',
        'transport' => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'tab_current_background', array(
        'section' => 'colors',
        'label'   => esc_html__( 'Chat login Background (selezionato)', 'theme' ),
    ) ) );
  }

  add_action( 'customize_register', 'simosnap_customize_register' );
  

function simosnap_get_customizer_css() {
    ob_start();
    
    $tab_background = get_theme_mod( 'tab_background', '' );
    if ( ! empty( $tab_background ) ) {
        ?>
        .jquery-tab-pager-tabbar li {
            border: 1px solid <?php echo $tab_background; ?>;
            background: <?php echo $tab_background; ?>;
        }
        <?php
    }

    $tab_current_background = get_theme_mod( 'tab_current_background', '' );
    if ( ! empty( $tab_current_background ) ) {
        ?>
        .jquery-tab-pager-tabbar li.current {
            border: 1px solid <?php echo $tab_current_background; ?>;
            background: <?php echo $tab_current_background; ?>;
        }
        <?php
    }
    
    $css = ob_get_clean();
    return $css;
}

function simosnap_enqueue_script() {
	wp_enqueue_style( 'simosnap', '/wp-content/plugins/simosnap/css/style.css') ;
	wp_enqueue_style( 'awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
	wp_enqueue_style( 'tab-pager', plugins_url( 'css/jquery.tabpager.css', __FILE__ )) ;

	wp_enqueue_script('tab-pager', plugins_url( 'js/jquery.tabpager.js', __FILE__ ), array('jquery'));
	wp_enqueue_script('simosnap', plugins_url( 'js/simosnap.js', __FILE__ ), array('jquery'));
    wp_enqueue_style( 'tab-pager', get_stylesheet_uri() ); 
    $custom_css = simosnap_get_customizer_css();
    wp_add_inline_style( 'tab-pager', $custom_css );
}

add_action('wp_enqueue_scripts', 'simosnap_enqueue_script');


add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'salcode_add_plugin_page_settings_link');
function salcode_add_plugin_page_settings_link( $links ) {
	$links[] = '<a href="' .
		admin_url( 'options-general.php?page=simosnap-setting-admin' ) .
		'">' . __('Settings') . '</a>';
	return $links;
}

class PageTemplater {

	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;

	/**
	 * The array of templates that this plugin tracks.
	 */
	protected $templates;

	/**
	 * Returns an instance of this class. 
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new PageTemplater();
		} 

		return self::$instance;

	} 

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {

		$this->templates = array();


		// Add a filter to the attributes metabox to inject template into the cache.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {

			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				array( $this, 'register_project_templates' )
			);

		} else {

			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates', array( $this, 'add_new_template' )
			);

		}

		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data', 
			array( $this, 'register_project_templates' ) 
		);


		// Add a filter to the template include to determine if the page has our 
		// template assigned and return it's path
		add_filter(
			'template_include', 
			array( $this, 'view_project_template') 
		);


		// Add your templates to this array.
		$this->templates = array(
			'chat-template.php' => 'Chat Container',
		);
			
	} 

	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}


	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public function register_project_templates( $atts ) {

		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list. 
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		} 

		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $atts;

	} 

	/**
	 * Checks if the template is assigned to the page
	 */
	public function view_project_template( $template ) {
		
		// Get global post
		global $post;

		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}

		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[get_post_meta( 
			$post->ID, '_wp_page_template', true 
		)] ) ) {
			return $template;
		} 

		$file = plugin_dir_path( __FILE__ ). get_post_meta( 
			$post->ID, '_wp_page_template', true
		);

		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}

		// Return template
		return $template;

	}

} 
add_action( 'plugins_loaded', array( 'PageTemplater', 'get_instance' ) );

/*
 * Initializes the plugin by setting filters and administration functions.
 */

class SimosNapSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_simosnap_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_simosnap_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'SimosNap Webchat',
            'SimosNap Webchat',
            'manage_options',
            'simosnap-setting-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'simosnap_general' );
        ?>
        <div class="wrap">
            <h1>SimosNap Webchat Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'simosnap_general_group' );
                do_settings_sections( 'simosnap-setting-admin' );
                do_settings_sections( 'simosnap-setting-kiwiirc' );
                do_settings_sections( 'simosnap-setting-webradio' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'simosnap_general_group', // Option group
            'simosnap_general', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Impostazioni Canale', // Title
            array( $this, 'print_plugin_info' ), // Callback
            'simosnap-setting-admin' // Page
        );

        add_settings_section(
            'setting_section_kiwiirc', // ID
            'kiwiirc Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'simosnap-setting-kiwiirc' // Page
        );

        add_settings_section(
            'setting_section_webradio', // ID
            'Web Radio Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'simosnap-setting-webradio' // Page
        );

        add_settings_field(
            'channel',
            'Canale',
            array( $this, 'channel_callback' ),
            'simosnap-setting-admin',
            'setting_section_id'
        );

        add_settings_field(
            'kiwi_stateKey',
            '',
            array( $this, 'statekey_callback' ),
            'simosnap-setting-admin',
            'setting_section_id'
        );
        
        add_settings_field(
            'kiwi_id', // ID
            'ID Client kiwiIRC', // Title
            array( $this, 'id_number_callback' ), // Callback
            'simosnap-setting-admin', // Page
            'setting_section_id' // Section
        );

         add_settings_field(
            'kiwiirc_title',
            'Titolo della chat:',
            array( $this, 'kiwiirc_title_callback' ),
            'simosnap-setting-kiwiirc',
            'setting_section_kiwiirc'
        );

           add_settings_field(
            'kiwiirc_theme',
            'Tema Chat:',
            array( $this, 'kiwiirc_theme_callback' ),
            'simosnap-setting-kiwiirc',
            'setting_section_kiwiirc'
        );

           add_settings_field(
            'kiwiirc_layout',
            'Schema Chat:',
            array( $this, 'kiwiirc_layout_callback' ),
            'simosnap-setting-kiwiirc',
            'setting_section_kiwiirc'
        );

           add_settings_field(
            'kiwiirc_target',
            'Chat Target:',
            array( $this, 'kiwiirc_target_callback' ),
            'simosnap-setting-kiwiirc',
            'setting_section_kiwiirc'
        );

           add_settings_field(
            'kiwiirc_myradio',
            'Abilita Webradio privata:',
            array( $this, 'kiwiirc_myradio_callback' ),
            'simosnap-setting-webradio',
            'setting_section_webradio'
        );
        
           add_settings_field(
            'kiwiirc_myradioname',
            'Nome Web Radio:',
            array( $this, 'kiwiirc_myradioname_callback' ),
            'simosnap-setting-webradio',
            'setting_section_webradio'
        );

           add_settings_field(
            'kiwiirc_myradiourl',
            'Sito Web Radio:',
            array( $this, 'kiwiirc_myradiourl_callback' ),
            'simosnap-setting-webradio',
            'setting_section_webradio'
        );
           add_settings_field(
            'kiwiirc_mystreaming',
            'Indirizzo streaming :',
            array( $this, 'kiwiirc_mystreaming_callback' ),
            'simosnap-setting-webradio',
            'setting_section_webradio'
        );

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['kiwi_id'] ) )
            $new_input['kiwi_id'] = absint( $input['kiwi_id'] );

        if( isset( $input['channel'] ) )
            $new_input['channel'] = sanitize_text_field( $input['channel'] );

        if( isset( $input['kiwiirc_stateKey'] ) )
            $new_input['kiwiirc_stateKey'] = sanitize_text_field( $input['kiwiirc_stateKey'] );

        if( isset( $input['kiwiirc_title'] ) )
            $new_input['kiwiirc_title'] = sanitize_text_field( $input['kiwiirc_title'] );
            
        if( isset( $input['kiwiirc_myradio'] ) )
            $new_input['kiwiirc_myradio'] = absint( $input['kiwiirc_myradio'] );

        if( isset( $input['kiwiirc_myradioname'] ) )
            $new_input['kiwiirc_myradioname'] = sanitize_text_field( $input['kiwiirc_myradioname'] );

        if( isset( $input['kiwiirc_myradiourl'] ) )
            $new_input['kiwiirc_myradiourl'] = sanitize_text_field( $input['kiwiirc_myradiourl'] );

        if( isset( $input['kiwiirc_mystreaming'] ) )
            $new_input['kiwiirc_mystreaming'] = sanitize_text_field( $input['kiwiirc_mystreaming'] );

        if( isset( $input['kiwiirc_theme'] ) )
            $new_input['kiwiirc_theme'] = sanitize_text_field( $input['kiwiirc_theme'] );
            
        if( isset( $input['kiwiirc_layout'] ) )
            $new_input['kiwiirc_layout'] = sanitize_text_field( $input['kiwiirc_layout'] );

        if( isset( $input['kiwiirc_target'] ) )
            $new_input['kiwiirc_target'] = sanitize_text_field( $input['kiwiirc_target'] );
            
        return $new_input;
    }

    /**
     * Print the Section text
     */

    public function print_plugin_info()
    {
        print '<p>Una volta completata la configurazione potrai includere nelle tue pagine il form di login della chat utilizzando lo shortcode [chatlogin]</p>';
        print '<p>Inserisci le impostazioni di seguito:</p>';
    }

    public function print_section_info()
    {
        print '<p>Inserisci le impostazioni di seguito:</p>';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function id_number_callback()
    {
        printf(
            '<input type="text" id="kiwi_id" name="simosnap_general[kiwi_id]" value="%s" /><p class="description">Inserisci l\' ID di un client KiwiIRC. <a href="https://www.simosnap.org/resources#kiwiirc" target="_blank">Genera client KiwiIRC</a> su SimosNap IRC Network.</p><p class="description">Se compilato, sarà utilizzato per il link al client KiwiIRC nella widget IRC.</p>',
            isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function channel_callback()
    {
        printf(
            '<input type="text" id="channel" name="simosnap_general[channel]" value="%s" /><p class="description">Inserisci il nome del canale IRC senza il simbolo #</p>',
            isset( $this->options['channel'] ) ? esc_attr( $this->options['channel']) : 'IRCHelp'
        );
    }

    public function statekey_callback()
    {
        printf(
            '<input type="hidden" id="stateKey" name="simosnap_general[kiwiirc_stateKey]" value="%s" />',
            isset( $this->options['channel'] ) ? esc_attr( $this->options['channel']).'-'.md5(time()) : 'IRCHelp-'.md5(time())
        );
    }

    public function kiwiirc_title_callback()
    {
        printf(
            '<input type="text" id="kiwiirc_title" name="simosnap_general[kiwiirc_title]" value="%s" /><p class="description">Inserisci il titolo della Chat</p>',
            isset( $this->options['kiwiirc_title'] ) ? esc_attr( $this->options['kiwiirc_title']) : 'SimosNap Webchat'
        );
    }

    public function kiwiirc_myradio_callback()
    {

	    $options = array(0,1);
	    echo '<select id="kiwiirc_myradio" name="simosnap_general[kiwiirc_myradio]">';

		printf('<option value="0" %s />Non attivo</option>',
	            (isset( $this->options['kiwiirc_myradio'] )  &&  ($this->options['kiwiirc_myradio'] == 0 )) ? 'selected="selected"' : ''
	        );
		printf('<option value="0" %s />Attivo</option>',
	            (isset( $this->options['kiwiirc_myradio'] )  &&  ($this->options['kiwiirc_myradio'] == 1 )) ? 'selected="selected"' : ''
	        );

		echo '</select>';
    }

    public function kiwiirc_myradioname_callback()
    {
        printf(
            '<input type="text" id="kiwiirc_myradioname" name="simosnap_general[kiwiirc_myradioname]" value="%s" /><p class="description">Se hai una webradio e vuoi inserirla nella chat scrivi qui il nome della stazione</p>',
            isset( $this->options['kiwiirc_myradioname'] ) ? esc_attr( $this->options['kiwiirc_myradioname']) : ''
        );
    }

    public function kiwiirc_myradiourl_callback()
    {
        printf(
            '<input type="text" id="kiwiirc_myradiourl" name="simosnap_general[kiwiirc_myradiourl]" value="%s" /><p class="description">Indirizzo del sito web della Web Radio</p>',
            isset( $this->options['kiwiirc_myradiourl'] ) ? esc_attr( $this->options['kiwiirc_myradiourl']) : ''
        );
    }

    public function kiwiirc_mystreaming_callback()
    {
        printf(
            '<input type="text" id="kiwiirc_mystreaming" name="simosnap_general[kiwiirc_mystreaming]" value="%s" /><p class="description">Indirizzo di streaming della webradio</p>',
            isset( $this->options['kiwiirc_mystreaming'] ) ? esc_attr( $this->options['kiwiirc_mystreaming']) : ''
        );
    }

    public function kiwiirc_theme_callback()
    {

	    $options = array('default','elite','Coffee','GrayFox','Nightswatch','Osprey','Sky','Radioactive','Dark');
	    echo '<select id="kiwiirc_theme" name="simosnap_general[kiwiirc_theme]">';
	    foreach($options as $value) {

			printf(
	            '<option value="'.$value.'" %s />'.$value.'</option>',
	            (isset( $this->options['kiwiirc_theme'] )  &&  ($this->options['kiwiirc_theme'] == $value )) ? 'selected="selected"' : ''
	        );

	    }
		echo '</select>';
    }

    public function kiwiirc_layout_callback()
    {

	    $options = array('compact','modern','inline');
	    echo '<select id="kiwiirc_layout" name="simosnap_general[kiwiirc_layout]">';
	    foreach($options as $value) {

			printf(
	            '<option value="'.$value.'" %s />'.$value.'</option>',
	            (isset( $this->options['kiwiirc_layout'] )  &&  ($this->options['kiwiirc_layout'] == $value )) ? 'selected="selected"' : ''
	        );

	    }
		echo '</select>';
    }

    public function kiwiirc_target_callback()
    {

	    $options = array('popup' => 'Finestra indipendente','full' => 'Finestra indipendente Massimizzata','blank' => 'Tab del browser indipendente');
	    echo '<select id="kiwiirc_target" name="simosnap_general[kiwiirc_target]">';
	    foreach($options as $key => $value) {

			printf(
	            '<option value="'.$key.'" %s />'.$value.'</option>',
	            (isset( $this->options['kiwiirc_target'] )  &&  ($this->options['kiwiirc_target'] == $key )) ? 'selected="selected"' : ''
	        );

	    }
		echo '</select>';
    }

}

if( is_admin() )
    $SimosNapSettingsPage = new SimosNapSettingsPage();


// add_filter( 'the_title', 'tfr_the_content' );

function tfr_the_content( $title ) {
  //return $title . '<p>Thanks for Reading!</p>';
}

class simosnap_irc_widget extends WP_Widget {

	// constructor
	function simosnap_irc_widget() {
		parent::__construct(false, $name = __('SimosNap IRC Widget', 'simosnap_irc_widget') );
	}

	// widget form creation
	function form($instance) {
		// Check values
		if( $instance) {
			 $title = esc_attr($instance['title']);
		} else {
			 $title = '';
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'simosnap_irc_widget'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php
	}

	// widget update
	function update($new_instance, $old_instance) {
      $instance = $old_instance;
      // Fields
      $instance['title'] = strip_tags($new_instance['title']);
     return $instance;
	}

	// widget display
	function widget($args, $instance) {
		$options = get_option('simosnap_general');
	   extract( $args );
	   // these are the widget options
	   $title = apply_filters('widget_title', $instance['title']);
	   echo $before_widget;
	   // Display the widget
	   echo '<div class="widget-text wp_widget_plugin_box">';

	   // Check if title is set
	   if ( $title ) {
		  echo $before_title . $title . $after_title;
	   } else {
		   echo $before_title . "". $after_title;
	   }
?>
<script>

jQuery(document).ready(function() {

       jQuery.getJSON('https://www.simosnap.org/rest/service.php/fullchannels/%23<?php echo $options['channel']; ?>', function(result) {
            jQuery("#chan_users_wg").text(result.users);
            //jQuery("#chan_modes_wg").text(result.modes ? "+"+result.modes : mLang.None);
        });


});
</script>
<ul>
	<li><i class="fa fa-plug" aria-hidden="true"></i> <a href="irc://irc.simosnap.com:6667/#<?php echo $options['channel']; ?>" target="_blank">Connessione IRC Standard</a></li>
	<li><i class="fa fa-shield" aria-hidden="true"></i> <a href="ircs://irc.simosnap.com:6697/#<?php echo $options['channel']; ?>" target="_blank">Connessione IRC SSL</a></li>
	<li><i class="fa fa-group" aria-hidden="true"></i> <a><span id="chan_users_wg" class="val"></span> <span>utenti connessi alla chat </span></a></li>
</ul>
<?php
       echo '</div>';
	   echo $after_widget;
	}
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("simosnap_irc_widget");'));


//CHAT LOGIN
add_action( 'init', 'register_shortcodes');

function chatLogin(){
	$options = get_option('simosnap_general');
	$channel = $options['channel'];
	$kiwiirc = $options['kiwiircid'];
	$theme = $options['kiwiirc_theme'];
	$layout = $options['kiwiirc_layout'];
	$target = $options['kiwiirc_target'];
	$myradio = $options['kiwiirc_myradio'];
	$myradioname = $options['kiwiirc_myradioname'];
	$myradiourl = $options['kiwiirc_myradiourl'];
	$mystreaming = $options['kiwiirc_mystreaming'];
	$stateKey = $options['kiwiirc_stateKey'];
	
	
?>

<div style="display:block;min-height:196px;height:auto;max-height:auto;">
<ul id="tabs">
  <li><i class="fa fa-camera"></i> Chat</li>
  <li><i class="fa fa-info"></i> Info</li>
</ul>

<div id="wrappers">

    <div class="contents" >

    	<form  method="POST" action="https://kiwiirc.simosnap.com/login.php" onsubmit="return validateForm();" target="chat" id="kiwiircform" style="margin:0px;padding:0px;">

            <div class="widget chatform" style="word-break: keep-all;">

    			<div class="chat-login">

                    <div class="flexdiv">

                        <div class="datadiv">
                			<div>
                                <label>
                                    <div class="nickerror" style=""><i class="fa fa-exclamation-triangle"></i> Scegli un nickname!</div>
                        			<input id="nickinput" placeholder="Inserisci il tuo nickname ..." type="text" name="nick">
            
                                    <div class="nsnotify" id="nsnotify" style="">Il Nick scelto risulta registrato.</div>
                                    <div id="nspwdlabel" style="display:none">
                            			<input id="nspwd" class="textbox" placeholder="Inserisci la password" type="password" name="password" value="" style="display:none;width:100%;">
                        			</div>
                                </label>
                			</div>
                			<div class="chatasl">
                                <label><input placeholder="Età" type="text" name="age"></label>
                        		<label>                        		
                        		<select class ="select-css" name="sex"><option  selected="selected" value="U">Sono ...</option><option value="M">Uomo</option><option value="F">Donna</option><option value="O">Altro</option></select></label>
                        		<label><input placeholder="Località" type="text" name="location" id="location"></label>
                			</div>
                			<div class="chatsettings">
                    			<label><span><i class="fa fa-exchange"></i> Nascondi Entrate e Uscite:</span>
                                    <input type="checkbox" value="true" name="show_joinparts" checked="checked">
                    			</label>
                			</div>

                            <input type="hidden" name="channel" value="#<?php echo $channel; ?>">
                            <input type="hidden" name="theme" value="<?php echo $theme; ?>">
                            <input type="hidden" name="layout" value="<?php echo $layout; ?>">
                            <?php if ($myradio == 1) {?>
                            <input type="hidden" name="radio" value="on">
                            <input type="hidden" name="radioname" value="<?php echo $myradioname; ?>">
                            <input type="hidden" name="radioweb" value="<?php echo $myradiourl; ?>">
                            <input type="hidden" name="streaming" value="<?php echo $mystreaming; ?>">                      
                            <?php } ?>
                            <input type="hidden" name="stateKey" value="<?php echo $stateKey; ?>">
                            <input type="hidden" name="target" value="<?php echo $target; ?>">
                        </div>

                        <div class="submitdiv">
							<div>
								<label class="sumbitlabel">
								<button class="login-button chat-button" type="submit"><span class="submitspanicon">Accedi <i class="fa fa-sign-in"></i></span></button>
								</label>
							</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>


    <div class="contents">

        <div class="widget">

                    <div clas="widget-text">
                        <h1><?php echo $chatdesc; ?></h1>
                        <ul>
                            <li>Connessione IRC Standard <a href="irc://irc.simosnap.com:6667/#<?php echo $channel; ?>">irc://irc.simosnap.com:6667/#<?php echo $channel; ?></a></li>
                            <li>Connessione IRC SSL <a href="ircs://irc.simosnap.com:6697/#<?php echo $channel; ?>">ircs://irc.simosnap.com:6697/#<?php echo $channel; ?></a></li>
                            <li>Scarica <a href="https://www.adiirc.com" target="_blank">AdiIRC</a> il client IRC per Windows consigliato da <a href="https://www.simosnap.org/" target="_blank">SimosNap IRC Network</a></li>
                            <li>Scarica <a href="https://www.codeux.com/textual/" target="_blank">Textual</a> il client IRC per OsX consigliato da <a href="https://www.simosnap.org/" target="_blank">SimosNap IRC Network</a></li>
                        </ul>
                    </div>

        </div>
    </div>

</div>
</div>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDxH99NguUJw8QAA6m0Gq5NYg4j8FAfQx0" type="text/javascript"></script>
<?php
}
function register_shortcodes() {
add_shortcode('chatlogin', 'chatLogin');
}
?>
