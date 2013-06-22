<?php
/*
 * @package Featured Posts
 * @author Nando Pappalardo e Giustino Borzacchiello
 * @version 1.5.2
 */
/*
  Plugin Name: Featured Post with thumbnail
  Plugin URI: http://www.yourinspirationweb.com/en/wordpress-plugin-featured-posts-with-thumbnails-highlighting-your-best-articles/
  Description: This widget allows you to add in your blog's sidebar a list of featured post with thumbanil.
  Author: Nando Pappalardo e Giustino Borzacchiello
  Version: 1.5.2
  Author URI: http://en.yourinspirationweb.com/

  USAGE:

  LICENCE:

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */


/**
 * Load Widget
 */
require_once 'includes/yiw-featured-post-widget.php';

/*==============================================================================
 * Definizioni costanti
 *============================================================================*/

/* Constants definitions for WP < 2.6
 * http://codex.wordpress.org/Determining_Plugin_and_Content_Directories
 */
if ( !defined('WP_CONTENT_URL') ) {
    define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}

if ( !defined('WP_CONTENT_DIR') ) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if ( !defined('WP_PLUGIN_URL') ) {
    define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');
}

if ( !defined('WP_PLUGIN_DIR') ) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

define('YIW_TEXT_DOMAIN', 'featured-post');
define('YIW_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

/*
* add translate language
*/
$language_files_path = dirname(plugin_basename(__FILE__)) . '/language';
load_plugin_textdomain(YIW_TEXT_DOMAIN, false, $language_files_path);

/**
 * Determine plugin path
 */
$featured_post_plugin_path = plugins_url('featured-posts');

/**
 * Add thumbnail support to the theme, if wordpress version is appropriate
 */
if ( function_exists('add_theme_support') ) {
    add_theme_support('post-thumbnails');
}

/**
 * Enqueue plugin CSS file
 */
function YIW_featured_post_css() {
	global $featured_post_plugin_path;
	wp_enqueue_style('featured-post', $featured_post_plugin_path . '/featured-post.css');
}
add_action('wp_print_styles', 'YIW_featured_post_css');

/**
 * Mostra i post in evidenza
 * Show featured posts using unordered list
 *
 * @param mixed $args
 *
 * $args:
 * 		title => the title displayed
 * 		numberposts => number of featured posts shown
 * 		orderby => order type: http://codex.wordpress.org/Template_Tags/get_posts
 * 		widththumb => width of post's thumbnail
 * 		heightthumb => height of post's thumbnail
 * 		beforetitle => opening tag before for title
 * 		aftertittle => closing tag for title
 */
function featured_posts_YIW($args = null) {

	global $featured_post_plugin_path;
	$defaults = array(
		 'title' => 'Featured Posts',
		 'numberposts' => 5,
		 'orderby' => 'DESC',
		 'widththumb' => 73,
		 'heightthumb' => 73,
		 'beforetitle' => '<h3>',
		 'aftertitle' => '</h3>',
		 'show' => 'featured',
		 'category' => 'uncategorized'
	);

	/**
	 *  Merging default values with user selected settings
	 */
	$fp = wp_parse_args($args, $defaults);
	$title = $fp['title'];
	$showposts = $fp['numberposts'];
	$orderby = $fp['orderby'];
	$width_thumb = $fp['widththumb'];
	$height_thumb = $fp['heightthumb'];
	$before_title = $fp['beforetitle'];
	$after_title = $fp['aftertitle'];
	$show = $fp['show'];
	$cat_ID = $fp['category'];

	/* List's title */
	if ( !empty($title) ) {
		echo $before_title . $title . $after_title;
	}

	/*
	 * Modificare i parametri di questa query per mostrare/escludere
	 * categorie, pagine.
	 * If you want to exclude categories and/or pages modify this query
	 * properly
	 * Info: http://codex.wordpress.org/Template_Tags/get_posts
	 *
	 */
	global $post;
	if ( (strcmp($show, 'category') == 0 ) && ($cat_ID)) {
		$get_posts_query = 'category=' . $cat_ID;
		$get_posts_query .= '&numberposts=' . $showposts;
		$get_posts_query .= '&orderby=' . $orderby;
	} else {
		$get_posts_query = 'meta_key=featured&meta_value=1';
		$get_posts_query .= '&numberposts=' . $showposts;
		$get_posts_query .= '&orderby=' . $orderby;
	}
	$featured_posts = get_posts($get_posts_query);
    include( plugin_dir_path(__FILE__) . '/views/featured-posts-list.php');
}

/* END featured_posts_YIW */




/*
 * aggiunge colonna nella pagina modifica dei post
 *
 * Il filtro 'manage_posts_columns' permette di aggiungere o rimuovere una
 * colonna dalla sezione "Modifica Post".
 * Per aggiungerla, basta fare come sotto,
 * ovvero aggiungere un elemento all'array $defaults, che ha come valore
 * l'intestazione della colonna.
 * Per rimuoverla si può usare unset($defaults['nomeColonna'])
 *
 * È molto importante ritornare l'array $defaults, come per tutti i filter
 */
add_filter('manage_posts_columns', 'yiw_add_column');

function yiw_add_column($defaults) {
	$defaults['yiw-featured'] = __('Featured', YIW_TEXT_DOMAIN);
	return $defaults;
}

/*
 * Recupera dal database tutti i post che hanno il custom field featured
 * attivato
 * FIXME mi sono accorto che il nome del nostro custom field, featured, è
 * veramente troppo comune. Bisognerebbe cambiarlo e metterlo in una variabile
 * però così facendo, bisognerebbe aggiornare tutti i post
 */
add_action('manage_posts_custom_column', 'yiw_featured_column', 10, 2);

function yiw_featured_column($column_name, $id) {
	if ( $column_name == 'yiw-featured' ) {
		global $wpdb;
		$queryStr = 'SELECT meta_value FROM ' . $wpdb->prefix . 'postmeta ';
		$queryStr .= 'WHERE meta_key="featured" and post_id=' . $id;
		$result = $wpdb->get_results($queryStr, ARRAY_A);
		if ( isset($result[0]) && ($result[0]['meta_value'] == '1') ) {
			_e("Yes", YIW_TEXT_DOMAIN);
		} else {
			_e("No", YIW_TEXT_DOMAIN);
		}
	}
}

function yiw_add_widget_script(){
	global $featured_post_plugin_path;
    wp_enqueue_script('yiw_widget_script', $featured_post_plugin_path . 'js/yiw_widget_script.js', 
        array('jquery'),
        false,
        true);
}
add_action('admin_head', 'yiw_add_widget_script');
?>
