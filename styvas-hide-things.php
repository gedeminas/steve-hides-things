<?php
/*
Plugin Name: Styvas Hide Things
Description: Hide things you do not need in admin bar, dashboard and more.
Version: 1.0
Author: Gediminas Čekanauskas
Author URI: http://styvas.lt
Text Domain: styvas-hide-things
Domain Path: /languages
*/
add_action( 'plugins_loaded', 'sht_load_textdomain' );
function sht_load_textdomain() {
  load_plugin_textdomain( 'styvas-hide-things', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

class Styvas_Hide_Things {

  function __construct(){
    // generate options
    add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    add_action( 'admin_init', array( $this, 'admin_init' ) );
    
    // get what things to hide
    global $settings;
    $settings = (array)get_option('styvas_hide_things_options');
    
    /*
    * Dashboard
    */
    function tidy_dashboard_widgets() {
      global $settings;
      if (isset($settings['dashboard-hide_activity']) && $settings['dashboard-hide_activity']) {
        remove_meta_box('dashboard_activity', 'dashboard', 'normal'); // from 3.8
      }
      if (isset($settings['dashboard-hide_other']) && $settings['dashboard-hide_other']) {
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
        remove_meta_box('dashboard_primary', 'dashboard', 'normal');
        remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'normal');
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'normal');
        //remove_meta_box('icl_dashboard_widget', 'dashboard', 'normal'); // wpml - todo: check if exists and hide
      }
    }
    add_action('admin_init', 'tidy_dashboard_widgets');
    // hide welcome panel for new users
    if (isset($settings['dashboard-hide_other']) && $settings['dashboard-hide_other']) {
      function hide_welcome_panel() {
        $user_id = get_current_user_id();
        if ( 1 == get_user_meta( $user_id, 'show_welcome_panel', true ) )
          update_user_meta( $user_id, 'show_welcome_panel', 0 );
      }
      add_action( 'load-index.php', 'hide_welcome_panel' );
    }
    if (isset($settings['dashboard-create_support']) && $settings['dashboard-create_support']) {
      // add my welcome panel
      function my_custom_dashboard_widgets() {
        global $wp_meta_boxes;
        wp_add_dashboard_widget('custom_help_widget', __( 'Support', 'styvas-hide-things' ), 'custom_dashboard_help');
      }
      function custom_dashboard_help() {
        echo "<p>";
        printf(__('If you have technical questions, please call <a href="mailto:%1$s">this</a> number.', 'styvas-hide-things'), "gcekanauskas@gmail.com");
        echo "</p>";
      }
      add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');
    }
    
    /*
    * Admin Bar
    */
    // disable admin bar when browsing site
    if (isset($settings['admin_bar-hide_front']) && $settings['admin_bar-hide_front']) {
      add_filter( 'show_admin_bar', '__return_false' );
    }
    // remove annoying admin bar links
    if (isset($settings['admin_bar-minimal_back']) && $settings['admin_bar-minimal_back']) {
      function remove_admin_bar_links() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('wp-logo');
        //$wp_admin_bar->remove_menu('view-site');
        $wp_admin_bar->remove_menu('new-content');
        $wp_admin_bar->remove_menu('comments');
      }
      add_action('wp_before_admin_bar_render', 'remove_admin_bar_links', 0);
    }
    
    /*
    * Remove Menu Items
    */
    function sht_remove_menu_items()
    {
      global $settings;
      /*
      global $menu;
      global $submenu;
      print_r($submenu);
      print_r($menu);
      */
      if (isset($settings['remove_from_everywhere-comments']) && $settings['remove_from_everywhere-comments']) {
        remove_menu_page('edit-comments.php'); // Comments
      }
      if (isset($settings['remove_from_everywhere-categories']) && $settings['remove_from_everywhere-categories']) {
        remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=category' );
      }
      if (isset($settings['remove_from_everywhere-tags']) && $settings['remove_from_everywhere-tags']) {
        remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=post_tag' );
      }
      
      //global $current_user;
      $current_user = wp_get_current_user();
      $user = $current_user->user_login;
      if ($user != 'gedeminas')
      {
        if (isset($settings['remove_menu_items-media']) && $settings['remove_menu_items-media']) {
          remove_menu_page('upload.php'); // Media
        }
        if (isset($settings['remove_menu_items-tools']) && $settings['remove_menu_items-tools']) {
          remove_menu_page('tools.php'); // Tools
        }
        //remove_menu_page('themes.php'); // Išvaizda
        //remove_menu_page('plugins.php'); // Įskiepiai
        //remove_menu_page('options-general.php'); // Nuostatos
        //remove_menu_page('edit.php?post_type=acf'); // ACF
      }
    }
    add_action('admin_menu','sht_remove_menu_items', 999);

    /*
    * Filter columns from posts
    */
    function my_posts_filter( $columns ) {
      global $settings;
      if (isset($settings['remove_from_everywhere-categories']) && $settings['remove_from_everywhere-categories']) {
        unset($columns['categories']);
      }
      if (isset($settings['remove_from_everywhere-comments']) && $settings['remove_from_everywhere-comments']) {
        unset($columns['comments']);
      }
      if (isset($settings['remove_from_everywhere-tags']) && $settings['remove_from_everywhere-tags']) {
        unset($columns['tags']);
      }
      return $columns;
    }
    add_filter('manage_edit-post_columns', 'my_posts_filter');
    
    /*
    * Filter columns from pages
    */
    function my_pages_filter( $columns ) {
      global $settings;
      if (isset($settings['filter_page_columns-date']) && $settings['filter_page_columns-date']) {
        unset($columns['date']);
      }
      if (isset($settings['filter_page_columns-author']) && $settings['filter_page_columns-author']) {
        unset($columns['author']);
      }
      if (isset($settings['remove_from_everywhere-comments']) && $settings['remove_from_everywhere-comments']) {
        unset($columns['comments']);
      }
      return $columns;
    }
    add_filter('manage_edit-page_columns', 'my_pages_filter');

  }

  function admin_menu(){
    add_options_page( __('Styvas Hide Things', 'styvas-hide-things'), __('Styvas Hide Things', 'styvas-hide-things'), 'manage_options', 'sht-options', array( $this, 'sht_options_page' ) );
  }
  
  function admin_init(){
    register_setting( 'styvas_hide_things_options_group', 'styvas_hide_things_options' );
    add_settings_section( 'general-settings', false, false, 'sht-options' ); //__('General Settings', 'styvas-hide-things'), false
    
    $checkbox_groups = array(
      array(
        'title' => __('Dashboard', 'styvas-hide-things'),
        'slug' => 'dashboard',
        'checkboxes' => array (
          'name' => 'dashboard',
          'fields' => array(
            array( 'hide_activity', __('Hide Activity Box', 'styvas-hide-things'), false ),
            array( 'hide_other', __('Hide Other Boxes', 'styvas-hide-things'), true ),
            array( 'create_support', __('Create Support Box', 'styvas-hide-things'), true )
          )
        )
      ),
      array(
        'title' => __('Admin Bar', 'styvas-hide-things'),
        'slug' => 'admin-bar',
        'checkboxes' => array (
          'name' => 'admin_bar',
          'fields' => array(
            array( 'hide_front', __('Hide Admin Bar in Front', 'styvas-hide-things'), true ),
            array( 'minimal_back', __('Minimal Admin Bar in Back', 'styvas-hide-things'), true )
          )
        )
      ),
      array(
        'title' => __('Remove Menu Items', 'styvas-hide-things'),
        'slug' => 'remove-menu-items',
        'checkboxes' => array (
          'name' => 'remove_menu_items',
          'fields' => array(
            array( 'media', __('Media', 'styvas-hide-things'), true ),
            array( 'tools', __('Tools', 'styvas-hide-things'), true )
          )
        )
      ),
      array(
        'title' => __('Remove From Everywhere', 'styvas-hide-things'),
        'slug' => 'remove-from-everywhere',
        'checkboxes' => array (
          'name' => 'remove_from_everywhere',
          'fields' => array(
            array( 'comments', __('Comments', 'styvas-hide-things'), true ),
            array( 'categories', __('Categories', 'styvas-hide-things'), false ),
            array( 'tags', __('Tags', 'styvas-hide-things'), true )
          )
        )
      ),
      array(
        'title' => __('Filter Page Columns', 'styvas-hide-things'),
        'slug' => 'filter-page-columns',
        'checkboxes' => array (
          'name' => 'filter_page_columns',
          'fields' => array(
            array( 'date', __('Date', 'styvas-hide-things'), true ),
            array( 'author', __('Author', 'styvas-hide-things'), true )
          )
        )
      )
    );
    
    foreach ($checkbox_groups as $checkbox_group) {
      add_settings_field( $checkbox_group['slug'], $checkbox_group['title'], array($this, 'checkbox_fieldset_callback'), 'sht-options', 'general-settings', $checkbox_group['checkboxes'] );
    }

  }
  
  function checkbox_fieldset_callback($args) {
    // get stored options
    $settings = (array)get_option('styvas_hide_things_options');
    // get common name and fields
    $name = $args['name'];
    $fields = $args['fields'];
    // foreach through fields and make checkboxes
    echo '<fieldset>';
    foreach ($fields as $field) {
      // make full option name for storing (section name - option name)
      $option_full_name = $name . '-' .$field[0];
      // get its value from settings
      $value = 0;
      if (isset($settings[$option_full_name])) {
        $value = esc_attr( $settings[$option_full_name] );
      }
      // output the great checkbox
      echo '<label><input name="styvas_hide_things_options[' . $option_full_name . ']" type="checkbox" value="1" ' . checked( 1, $value, false ) . ' /> ' . $field[1] . '</label><br>';
    }
    echo '</fieldset>';
  }
  
  function checkbox_callback($args) {
    // get the field name
    $field = $args['field'];
    // gett all the plugin settings
    $settings = (array)get_option('styvas_hide_things_options');
    // get the value of this setting
    $value = esc_attr( $settings[$field] );
    echo '<input name="styvas_hide_things_options[' . $field . ']" type="checkbox" value="1" ' . checked( 1, $value, false ) . ' />';
  }

  function sht_options_page(){
    ?>
    <div class="wrap">
      <?php //echo "<pre>"; var_dump(get_option('styvas_hide_things_options')); echo "</pre>"; ?>
      <h2><?php _e('Styvas Hide Things', 'styvas-hide-things'); ?></h2>
      <form action="options.php" method="POST">
        <?php settings_fields('styvas_hide_things_options_group'); ?>
        <?php do_settings_sections('sht-options'); ?>
        <?php submit_button(); ?>
    </div>
    <?php
  }
  
  private function sht_check_options() {
    // the default options
    $sht_options_default = array(
      'admin_bar_front_hide' => true,
      'admin_bar_back_minimal' => true,
      'comments_hide' => true,
      'tags_hide' => true
    );
    // check to see if present already
    if( !get_option('styvas_hide_things_options') ) {
      add_option('styvas_hide_things_options', $sht_options_default);
    }
    else {
      // if already in the database we get the stored value and merge it with default
      $sht_options_old = get_option('styvas_hide_things_options');
      $sht_options_new = wp_parse_args($sht_options_old, $sht_options_default);
      update_option('styvas_hide_things_options', $sht_options_new);
    }
  }
  
  public function restore_op() {
    delete_option('styvas_hide_things_options');
    $this->sht_check_options();
  }
}

$Styvas_Hide_Things = new Styvas_Hide_Things();
?>