<?php
/*
Plugin Name: Vendi - Template Router
Description: Vendi's common template routing code.
Version: 1.2.0
Author: Vendi Advertising (Chris Haas)
License: GPL2
*/

/*
History

1.1.0 - Made MU
      - Switched from static variables to instance.

1.2.0 - Added header and footer overrides

1.3.0 - Added template subfolders

1.3.1 - Added static get_current_items()
*/
namespace Vendi\Shared;

use \Vendi\Shared\utils;

class template_router
{
    public $magic_folder;

    public $magic_page;

    public $template_path;

    public $context;

    private static $current_items = [];

    /**
     * The folder relative to $template_path that holds the templates.
     *
     * @since 1.3.0
     * 
     * @var string
     */
    public $template_subfolder;

    private static $_instances = array();

    /**
     * Returns all currently requested routes.
     *
     * NOTE: It is possible for routes to be called after you query
     * this so make sure of your dependency order.
     *
     * @since 1.3.1
     * 
     * @return array An array of arrays with keys context, subfolder and page.
     */
    public static function get_current_items()
    {
        return self::$current_items;
    }

    public static function get_config_for_debug()
    {
        return self::$_instances;
    }

    public static function register_context( $context, $magic_folder, $template_path, $magic_page = 'page', $template_subfolder = 'templates' )
    {
        self::$_instances[ $context ] = new self( $context, $magic_folder, $template_path, $magic_page, $template_subfolder );
    }

    public static function get_instance( $context )
    {
        if( array_key_exists( $context, self::$_instances ) )
        {
            return self::$_instances[ $context ];
        }

        return null;
    }

    private function __construct( $context, $magic_folder, $template_path, $magic_page, $template_subfolder )
    {
        $this->context              = $context;
        $this->magic_folder         = $magic_folder;
        $this->template_path        = untrailingslashit( $template_path );
        $this->magic_page           = $magic_page;

        //Strip any leading and trailing slashes which will be appended later.
        $this->template_subfolder   = trim( $template_subfolder, '/' );

        add_action( 'init',             array( $this, 'add_query_var' ) );

        add_action( 'pre_get_posts',    array( $this, 'look_for_magic_folder' ), 999 );

        add_action( 'init',             array( $this, '_do_wire_rewrite_rules' ) );
    }

    public function create_url( $page, array $args = array(), $fully_qualified = false )
    {
        $prefix = '';

        if( $fully_qualified )
        {
            $host  = strtolower( utils::get_server_value( 'HTTP_HOST', '' ) );
            $https = strtolower( utils::get_server_value( 'HTTPS',     '' ) );

            $https = 'off' !== $https && '' !== $https;

            if( $host )
            {
                $prefix = sprintf( 'http%1$s://%2$s', $https ? 's' : '', $host );
            }
        }

        return add_query_arg( $args, $prefix . '/' . $this->magic_folder . '/' . $page . '/' );
    }

    public function wire_rewrite_rules()
    {
        add_action( 'init', array( $this, '_do_wire_rewrite_rules' ) );
    }

    public function add_query_var()
    {
        global $wp;
        $wp->add_query_var( $this->magic_folder );
        $wp->add_query_var( $this->magic_page );
    }

    public function _do_wire_rewrite_rules()
    {
        add_rewrite_rule(
                            //Look for our magic page
                            '^' . $this->magic_folder . '/([a-zA-Z\-0-9]+)' . '$',

                            //Redirect to index passing the magic page as a query string
                            'index.php?' . $this->magic_folder . '=1&' . $this->magic_page . '=$matches[1]', 'top'
                        );
    }

    public function get_footer()
    {
        if( is_file(     $this->template_path . '/' . $this->template_subfolder . '/wp_footer.php' ) )
        {
            require_once $this->template_path . '/' . $this->template_subfolder . '/wp_footer.php';
        }
        else
        {
            get_footer();
        }
    }

    public function get_header()
    {
        if( is_file(     $this->template_path . '/' . $this->template_subfolder . '/wp_header.php' ) )
        {
            require_once $this->template_path . '/' . $this->template_subfolder . '/wp_header.php';
        }
        else
        {
            get_header();
        }
    }

    public function look_for_magic_folder( $query )
    {
        if ( ! $query->is_main_query() )
        {
            return;
        }

        $is_magic_folder = get_query_var( $this->magic_folder );

        if ( empty( $is_magic_folder ) || '1' != $is_magic_folder )
        {
            return;
        }

        $page = basename( get_query_var( $this->magic_page ) );

        //This doesn't work, not sure why
        if( in_array( $page, array( 'wp_header', 'wp_footer' ) ) )
        {
            die( 'This template does not support direct access' );
        }

        if( is_file( $this->template_path . '/' . $this->template_subfolder . '/' . $page . '.php' ) )
        {

            self::$current_items[] = [
                                        'context'   => $this->context,
                                        'subfolder' => $this->template_subfolder,
                                        'page'      => $page,
                                    ];

            //Call any function that need to be run before page render
            do_action( "vendi/shared/template_router/pre_include_template/$this->context", $this->context, $this->magic_folder, $this->magic_page, $page );

            do_action( 'vendi/shared/template_router/pre_include_template',                $this->context, $this->magic_folder, $this->magic_page, $page );

            add_filter(
                        'template_include',
                        function() use ( $page )
                        {
                            return $this->template_path . '/' . $this->template_subfolder . '/' . $page . '.php';
                        }
                    );

            add_filter(
                        'body_class',
                        function( $classes )
                        {
                            $new = array();
                            foreach( $classes as $idx => $v )
                            {
                                if( ! in_array( $v, array( 'home', 'blog' ) ) )
                                {
                                    $new[] = $v;
                                }
                            }

                            $new[] = 'page';

                            return $new;
                        }
                    );

            do_action( "vendi/shared/template_router/post_include_template/$this->context", $this->context, $this->magic_folder, $this->magic_page, $page );

            do_action( 'vendi/shared/template_router/post_include_template',                $this->context, $this->magic_folder, $this->magic_page, $page );

            return;
        }

        //TODO:
        die( 'Unknown page: ' . esc_html( $page ) );
    }

}
