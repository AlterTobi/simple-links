<?php
                 /**
                  * Methods for the Simple Links Plugin
                  * 
                  * @author Mat Lipe <mat@matlipe.com>
                  * 
                   * 
                  * @uses These methods are used in both the admin output of the site
                  * 
                  * @see simple_links_admin() for the only admin methods
                  * @see SL_post_type_tax() for the post type and tax registrations
                  */

if( !class_exists( 'simple_links' ) ){
class simple_links extends SL_post_type_tax{
	
	
	public $additional_fields = array();

    /**
     * Constructor
	 * 
	 * 
     */
	function __construct(){
	   
											
	    //Add the translate ability
	    add_action('plugins_loaded', array( $this,'translate') );

		parent::__construct();
		
		
		//Setup the form output for the new button
		add_filter('query_vars', array( $this, 'outside_page_query_var') );
		add_action('template_redirect', array( $this, 'loadShortcodeForm') );
		
		//Bring in the shortcode
		add_shortcode('simple-links', array( $this, 'shortcode' ) );
        
        //Add the widgets
        add_action( 'widgets_init', array( $this, 'addWidgets') ); 
	
	}


    /**
     * Retrieve the additional fields names
     * 
     * @since 2.0
     */
    function getAdditionalFields(){
        static $fields = false;
        
        if( $fields ) return $fields;
        
        $fields = get_option('link_additional_fields');
        
        if( !is_string($fields) ) return $fields; 

        //pre version 2.0
        return $fields = json_decode($fields, true);
        
    }


    /**
     * Register the widgets
     * 
     * @since 3.2.14
     * 
     * @uses added to the widgets_init hook by self::__construct();
     */
    function addWidgets(){
        //Register the main widget
        register_widget( 'SL_links_main' );

    }


    /**
     * Generates an html link from a links ID
     * 
     * @since 2.10.14
     * 
     * @param int  $linksId - the links post->ID
     */
    public function linkFactory($linkId){
       $link = get_post( $linkId );
       $meta = get_post_meta( $linkId );
       
       $link_output = sprintf('<a href="%s" target="%s" title="%s" %s>%s</a>',
                    $meta['web_address'][0],
                    $meta['target'][0],
                    htmlentities(strip_tags($meta['description'][0]), ENT_QUOTES, 'UTF-8'),
                    empty( $meta['link_target_nofollow'][0] ) ? '': 'rel="nofollow"',
                    $link->post_title
        );
            
        return apply_filters('simple_links_factory_output', $link_output, $linkId );
            
    }
    
    


	
	
	/**
	 * Add the translate ability for I18n standards
	 * @since 10.11.12
	 * @uses called on __construct()
	 */
	function translate(){
	    load_plugin_textdomain('simple-links', false, 'simple-links/languages');
	}
	
	
	/**
	 * Creates the shortcode output
	 * @return the created list based on attributes
	 * @uses [simple-links $atts]
	 * @param string $atts the attributes specified in shortcode
	 * @since 1.7.14
	 * @param $atts = 'title'              => false,
                      'category'           => false,
                       'orderby'           => 'menu_order',
                       'count'             => '-1',
                       'show_image'        => false,
                       'show_image_only'   => false,
                       'image_size'        => 'thumbnail',
                       'order'             => 'ASC',
                       'fields'            => false,
                       'description'       => false,
                       'separator'         =>  '-',
                       'id'                =>  false,
                       'remove_line_break' =>  false

     * 
     * @filters  
     *       the shortcode atts
     *      * add_filter( 'simple_links_shortcode_atts', $atts );
     *       the shortcode output
     *      * add_filter( 'simple_links_shortcode_output', $output, $links, $atts )
     *       the links object directly
     *      *  apply_filters('simple_links_shortcode_links_object', $links, $atts);
     *       the links meta data per link
     *      * apply_filters('simple_links_shortcode_link_meta', $meta, $link, $atts );
     * 
     * 
	 * @uses the function filtering this output can accept 3 args.   <br>
	 * 				$output = The Output Generated by the Function
	 * 				$links  = The complete links to direct munipulation
	 * 				$atts   = The shortcode Attributes sent to this
	 * @uses All filters may be used by id by calling them with the id appened like so  'simple_links_shortcode_output_%id%' there must be an 'id' specified in the shortcode for this to work 
	 * @uses Using the filters without the id will filter all the shortcodes
	 * 
	 */
	function shortcode( $atts ){
	            
        //shortcode atts filter - 
        $atts = apply_filters('simple_links_shortcode_atts', $atts);
        if( isset($atts['id']) ){
           $atts = apply_filters('simple_links_shortcode_atts_' . $atts['id'], $atts);
        }

        $links = new SimpleLinksFactory($atts, 'shortcode');
        
               
        $output =  apply_filters( 'simple_links_shortcode_output', $links->output(), $links->links, $links->args, $links->query_args );
        if( isset( $atts['id'] ) ){
            $output = apply_filters( 'simple_links_shortcode_output_' . $atts['id'], $output, $links->links, $links->args, $links->query_args  );
        }
        
        return $output;
	
	}
	
	
	
	
	
	/**
	 * @deprecated use Simple_Links_Categories::get_category_names()
	 * 
	 * @todo find all uses of this and convert to new object
	 * 
	 */
	function get_categories(){
		return Simple_Links_Categories::get_category_names();
		
	}
	
	
	/**
	 * Retrieves all available image sizes
	 * @since 8/19/12
	 * @return array
	 */
	function image_sizes(){
		return get_intermediate_image_sizes();
	}
	
	
	
	/**
	 * Brings in the PHP page for the mce buttons shortcode popup
	 * @since 9.22.13
     * 
     * @uses added to the template_redirect hook by self::__construct();
     * 
	 * @uses called by the mce icon
	 */
	function loadShortcodeForm(){
		//Escape Hatch
		if( !is_user_logged_in() ){ return; }
		//Check the query var
		switch(get_query_var('simple_links_shortcode')) {
			case 'form':
				include(SIMPLE_LINKS_JS_PATH . 'shortcode-form.php' );
				die();
            break;
		}
	}
	
	
	/**
	 * Setsup the query var to bring in the outside page to the popup form
	 * @since 8/19/12
	 * @uses called by mce_button()
	 */
	function outside_page_query_var($queries){
		array_push( $queries, 'simple_links_shortcode' );
		return $queries;
	}
	
    /**
     * Get the additional Field Values for a post
     * 
     * @since 2.0
     * @param int $postId
     */
    function getAdditionalFieldsValues($postId){

        $values = get_post_meta($postId, 'link_additional_value', true);

        //pre version 2.0
        if( !is_array( $values ) ){
            $values = json_decode( $values, true);
        }
        
        return $values;
   
    }
	
	
	

	
	/**
	 * Retrieves all the link categories a link is assinged to
	 * @param int $postID the link ID
	 * @param boolean $full_array to return all values default to an array of just names
	 * @return boolean|array
	 * @since 8/21/12
	 * @uses call whereve you would like
	 */
	function get_link_categories( $postID, $full_array = false ){
		$cats = get_the_terms( $postID, 'simple_link_category' );
	
		//escape hatch
		if( !is_array($cats) ){
			return false;
		}
	
		//return full array
		if( $full_array ){
			return $cats;
		}
	
	
		foreach( $cats as $cat ){
			$cat_names[] = $cat->name;
		}
	
		return $cat_names;
	}
	
	


	}
  //-- End of Class
} //-- End of if class exists