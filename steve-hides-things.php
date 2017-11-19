<?php
/*
Plugin Name: Steve Hides Things
Description: Hide some useless things in admin side and make client's life easier.
Version: 1.0.1
Author: Gediminas Čekanauskas
Author URI: http://styvas.lt
Text Domain: steve-hides-things
Domain Path: /languages
*/

if ( ! class_exists( 'Steve_Hides_Things' ) ) {
  class Steve_Hides_Things {
    private static $instance;
    public $plugin_url = '';
    public $plugin_path = '';

    public static function get_instance()
    {
      if ( ! self::$instance ) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    public function __construct() {
      $this->version = '1.0.1';
      $this->plugin_url = plugins_url( '/', __FILE__ );
      $this->plugin_path = plugin_dir_path( __FILE__ );
      $this->plugin_name = 'Steve Hides Things';
      $this->plugin_class = 'Steve_Hides_Things';
      $this->plugin_options = array();
      $this->menu_slug = 'stevehidesthings';
      $this->load_language( 'steve-hides-things' );
      if ( self::$instance ) {
        wp_die( sprintf( '<strong>%s:</strong> Please use the <code>%s::instance()</code> method for initialization.', $this->plugin_class, __CLASS__ ) );
      }
      add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
      add_action( 'admin_init', array( $this, 'settings_init' ) );
      add_action( 'plugins_loaded', array( $this, 'load_action_hooks' ) );
      //$this->load_action_hooks();
    }
    
    public function load_language( $domain ) {
      load_plugin_textdomain( $domain, false, $this->plugin_path . 'languages' );
    }

    public function load_action_hooks() {
      $options = $this->plugin_options = get_option('stevehidesthings_options');
      if (
        isset( $options['dashboard_hide_activity'] ) && $options['dashboard_hide_activity'] ||
        isset( $options['dashboard_hide_other'] ) && $options['dashboard_hide_other']
      ) {
        add_action( 'admin_init', array( $this, 'tidy_dashboard_widgets' ) );
      }
      if ( isset( $options['dashboard_hide_other'] ) && $options['dashboard_hide_other'] ) {
        add_action( 'load-index.php', array( $this, 'hide_welcome_panel' ) );
      }
      if ( isset( $options['dashboard_create_support'] ) && $options['dashboard_create_support'] ) {
        add_action( 'wp_dashboard_setup', array( $this, 'add_custom_support_widget' ) );
      }
      if ( isset( $options['admin_bar_hide_front'] ) && $options['admin_bar_hide_front'] ) {
        add_filter( 'show_admin_bar', '__return_false' );
      }
      if ( isset( $options['admin_bar_minimal_back'] ) && $options['admin_bar_minimal_back'] ) {
        add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar_links' ), 0 );
      }
      if (
        isset( $options['remove_menu_items_posts'] ) && $options['remove_menu_items_posts'] ||
        isset( $options['remove_menu_items_media'] ) && $options['remove_menu_items_media'] ||
        isset( $options['remove_menu_items_pages'] ) && $options['remove_menu_items_pages'] ||
        isset( $options['remove_menu_items_tools'] ) && $options['remove_menu_items_tools'] ||
        isset( $options['remove_everywhere_comments'] ) && $options['remove_everywhere_comments']
      ) {
        add_action( 'admin_menu', array( $this, 'remove_menu_items' ), 999 );
      }
      if (
        isset( $options['remove_everywhere_comments'] ) && $options['remove_everywhere_comments'] ||
        isset( $options['remove_everywhere_categories'] ) && $options['remove_everywhere_categories'] ||
        isset( $options['remove_everywhere_tags'] ) && $options['remove_everywhere_tags']
      ) {
        add_action( 'init', array( $this, 'disable_those_supports' ) );
      }
      if (
        isset( $options['filter_page_cols_date'] ) && $options['filter_page_cols_date'] ||
        isset( $options['filter_page_cols_author'] ) && $options['filter_page_cols_author']
      ) {
        add_action( 'manage_edit-page_columns', array( $this, 'filter_edit_page_columns' ) );
      }
    }

    public function tidy_dashboard_widgets() {
      $options = $this->plugin_options;
      if ( isset( $options['dashboard_hide_activity'] ) && $options['dashboard_hide_activity'] ) {
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
      }
      if ( isset( $options['dashboard_hide_other'] ) && $options['dashboard_hide_other'] ) {
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
        remove_meta_box('dashboard_primary', 'dashboard', 'normal');
        remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'normal');
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'normal');
      }
    }

    public function hide_welcome_panel() {
      $user_id = get_current_user_id();
      if ( 1 == get_user_meta( $user_id, 'show_welcome_panel', true ) ) {
        update_user_meta( $user_id, 'show_welcome_panel', 0 );
      }
    }

    public function add_custom_support_widget() {
      wp_add_dashboard_widget( 'custom_support_widget', __( 'Support', 'steve-hides-things' ), array( $this, 'custom_support_widget_insides' ) );
    }

    public function custom_support_widget_insides() {
      echo '<p>';
      printf( __( 'In case you have any technical questions, please call <a href="mailto:%1$s">this</a> number.', 'steve-hides-things' ), get_option( 'admin_email' ) );
      echo '</p>';
    }

    public function remove_admin_bar_links() {
      global $wp_admin_bar;
      $wp_admin_bar->remove_menu('wp-logo');
      $wp_admin_bar->remove_menu('new-content');
      $wp_admin_bar->remove_menu('comments');
    }

    public function remove_menu_items() {
      $options = $this->plugin_options;
      if ( ! current_user_can( 'administrator' ) ) {
        if ( isset( $options['remove_menu_items_posts'] ) && $options['remove_menu_items_posts'] ) {
          remove_menu_page('edit.php');
        }
        if ( isset( $options['remove_menu_items_media'] ) && $options['remove_menu_items_media'] ) {
          remove_menu_page('upload.php');
        }
        if ( isset( $options['remove_menu_items_pages'] ) && $options['remove_menu_items_pages'] ) {
          remove_menu_page('edit.php?post_type=page');
        }
        if ( isset( $options['remove_menu_items_tools'] ) && $options['remove_menu_items_tools'] ) {
          remove_menu_page('tools.php');
        }
      }
      if ( isset( $options['remove_everywhere_comments'] ) && $options['remove_everywhere_comments'] ) {
        remove_menu_page( 'edit-comments.php' );
      }
    }

    public function disable_those_supports() {
      $options = $this->plugin_options;
      if ( isset( $options['remove_everywhere_comments'] ) && $options['remove_everywhere_comments'] ) {
        $post_types = array( 'post', 'page' );
        foreach ( $post_types as $post_type ) {
          if ( post_type_supports( $post_type, 'comments' ) ) {
            remove_post_type_support( $post_type, 'comments' );
            remove_post_type_support( $post_type, 'trackbacks' );
          }
        }
      }
      if ( isset( $options['remove_everywhere_categories'] ) && $options['remove_everywhere_categories'] ) {
        unregister_taxonomy_for_object_type( 'category', 'post' );
      }
      if ( isset( $options['remove_everywhere_tags'] ) && $options['remove_everywhere_tags'] ) {
        unregister_taxonomy_for_object_type( 'post_tag', 'post' );
      }
    }

    public function filter_edit_page_columns( $columns ) {
      $options = $this->plugin_options;
      if ( isset( $options['filter_page_cols_date'] ) && $options['filter_page_cols_date'] ){
        unset( $columns['date'] );
      }
      if ( isset( $options['filter_page_cols_author'] ) && $options['filter_page_cols_author'] ){
        unset( $columns['author'] );
      }
      return $columns;
    }
    
    public function add_admin_menu() {
      add_options_page( $this->plugin_name, $this->plugin_name, 'manage_options', $this->menu_slug, array( $this, 'add_admin_menu_callback' ) );
    }

    public function add_admin_menu_callback() {
      if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'steve-hides-things' ) );
      }
      if ( isset( $_POST['action'] ) && ! wp_verify_nonce( $_POST['nonce'], $this->plugin_class ) ) {
        wp_die( __( 'Security check failed! Settings not saved.', 'steve-hides-things' ) );
      }
      ?>
      <div class="wrap">
        <h1><?php echo $this->plugin_name; ?> <?php _e( 'Settings', 'steve-hides-things' ); ?></h1>
        <form method="post" action="options.php">
          <?php settings_fields( 'stevehidesthings_options' ); ?>
          <?php do_settings_sections( 'stevehidesthings_options' ); ?>
          <?php submit_button(); ?>
        </form>
      </div>
      <?php
    }

    public function settings_init() {
      register_setting( 'stevehidesthings_options', 'stevehidesthings_options' );
      add_settings_section( 'stevehidesthings_options', null, null, 'stevehidesthings_options' );
      $checkbox_fieldsets = array(
        array(
          'title' => __( 'Dashboard', 'steve-hides-things' ),
          'name' => 'dashboard',
          'checkboxes' => array(
            array( 'hide_activity', __( 'Hide Activity Box', 'steve-hides-things' ) ),
            array( 'hide_other', __( 'Hide Other Boxes', 'steve-hides-things' ) ),
            array( 'create_support', __( 'Create Support Box', 'steve-hides-things' ) )
          )
        ),
        array(
          'title' => __( 'Admin Bar', 'steve-hides-things' ),
          'name' => 'admin_bar',
          'checkboxes' => array(
            array( 'hide_front', __( 'Hide Admin Bar in Front', 'steve-hides-things' ) ),
            array( 'minimal_back', __( 'Minimal Admin Bar in Back', 'steve-hides-things' ) )
          )
        ),
        array(
          'title' => __( 'Remove Menu Items', 'steve-hides-things' ),
          'name' => 'remove_menu_items',
          'checkboxes' => array(
            array( 'posts', __( 'Posts', 'steve-hides-things' ) ),
            array( 'media', __( 'Media', 'steve-hides-things' ) ),
            array( 'pages', __( 'Pages', 'steve-hides-things' ) ),
            array( 'tools', __( 'Tools', 'steve-hides-things' ), __( 'Menu items will not be removed for administrators.', 'steve-hides-things' ) )
          )
        ),
        array(
          'title' => __( 'Remove Everywhere' ),
          'name' => 'remove_everywhere',
          'checkboxes' => array(
            array( 'comments', __( 'Comments', 'steve-hides-things' ) ),
            array( 'categories', __( 'Categories', 'steve-hides-things' ) ),
            array( 'tags', __( 'Tags', 'steve-hides-things' ) )
          )
        ),
        array(
          'title' => __( 'Filter Page Columns', 'steve-hides-things' ),
          'name' => 'filter_page_cols',
          'checkboxes' => array(
            array( 'date', __( 'Date', 'steve-hides-things' ) ),
            array( 'author', __( 'Author', 'steve-hides-things' ) )
          )
        )
      );
      foreach ( $checkbox_fieldsets as $fieldset ) {
        $this->add_whatever_settings_field( 'stevehidesthings_options', $fieldset['name'], $fieldset['title'], 'field_render_checkbox_fieldset', array( 'checkboxes' => $fieldset['checkboxes'] ) );
      }
    }

    public function add_whatever_settings_field( $group, $name, $title, $fn, $args = array() ) {
      $default_args = array( 'group' => $group, 'name' => $name );
      $args = array_merge( $default_args, $args );
      add_settings_field(
        $name,
        $title,
        array( $this, $fn ),
        $group,
        $group,
        $args
      );
    }

    public function field_render_checkbox_fieldset( $args ) {
      $options_name = $args['group'];
      $plugin_options = get_option( $options_name ) ? get_option( $options_name ) : null;
      ?>
      <fieldset>
        <?php
        $total = count( $args['checkboxes'] );
        $i = 0;
        foreach ( $args['checkboxes'] as $checkbox ) {
          $field_name = $args['name'] . '_' . $checkbox[0];
          $checked = isset( $plugin_options[$field_name] ) ? $plugin_options[$field_name] : 0;
          $this->print_html_checkbox($options_name, $field_name, $checkbox[1], $checked);
          $desc = isset( $checkbox[2] ) ? $checkbox[2] : false;
          if ( $desc ) {
            ?>
            <p class="description"><?php echo $desc; ?></p>
            <?php
          }
          else if ( $i < $total - 1 ) echo '<br>';
          $i++;
        }
        ?>
      </fieldset>
      <?php
    }

    public function field_render_checkbox( $args ) {
      $options_name = $args['group'];
      $field_name = $args['name'];
      $plugin_options = get_option( $options_name ) ? get_option( $options_name ) : null;
      $checked = isset( $plugin_options[$field_name] ) ? $plugin_options[$field_name] : 0;
      $this->print_html_checkbox($options_name, $field_name, $args['label'], $checked);
    }

    public function print_html_checkbox( $options_name, $field_name, $label, $checked ) {
      ?>
      <label for="<?php echo $field_name; ?>"><input id="<?php echo $field_name; ?>" name="<?php echo $options_name; ?>[<?php echo $field_name; ?>]" type="checkbox" value="1" <?php checked( $checked, 1 ); ?>> <?php _e( $label, 'steve-hides-things' ); ?></label>
      <?php
    }

  }
  Steve_Hides_Things::get_instance();
}