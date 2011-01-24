<?php
/*
Plugin Name: Simple Content Restriction
Plugin URI: https://github.com/stas/your-id-please
Description: Asks visitors to login if the post is marked as visible only to blog users.
Version: 0.1
Author: Stas Sușcov
Author URI: http://stas.nerd.ro/
*/
?>
<?php
/*  Copyright 2011  Stas Sușcov <stas@nerd.ro>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php

/**
 * yidp_textdomain()
 *
 * Loads 
 */
function yidp_textdomain() {
    load_plugin_textdomain( 'your-id-please', false, dirname( plugin_basename( __FILE__ ) ).'/languages' );
}

/**
 * yidp_content( $content )
 *
 * Hides the content of posts/pages if admin marked pages with login required
 * @param String $content, the page/post content
 * @return String
 */
function yidp_content( $content ) {
    global $post;
    $yidp_enabled = get_post_meta( $post->ID, 'your-id-please', true );
    $yidp_message = false;
    
    if( $yidp_enabled )
        $yidp_message = get_post_meta( $post->ID, 'your-id-please_message', true );
    
    if( $yidp_message && !is_user_logged_in() )
        return '<p><a href="' . wp_login_url( get_permalink() ) .'">' . $yidp_message. '</p>';
    else
        return $content;
}

/**
 * yidp_settings()
 *
 * Adds metaboxes to post and page editor screens
 */
function yidp_settings() {
    add_meta_box( 'your-id-please', __( 'Content Visibility', 'your-id-please' ), 'yidp_box', 'post', 'side' );
    add_meta_box( 'your-id-please', __( 'Content Visibility', 'your-id-please' ), 'yidp_box', 'page', 'side' );
    
    if( !get_option( 'your-id-please_message' ) )
        add_option( 'your-id-please_message', __( 'Please login to access this post/page content', 'your-id-please' ), null, true );
}

/**
 * yidp_box()
 *
 * Renders the metabox content
 * @param Obj $post, the page/post object
 */
function yidp_box( $post ) {
    $yidp_enabled = (bool) get_post_meta( $post->ID, 'your-id-please', true );
    $yidp_message = get_post_meta( $post->ID, 'your-id-please_message', true );
    $yidp_message = empty( $yidp_message ) ? get_option( 'your-id-please_message' ) : $yidp_message;
    
    wp_nonce_field( 'your-id-please', 'your-id-please' );
    
    $toggler = $yidp_enabled ? '' : 'onclick="javascript:jQuery(\'.yidp_toggle\').toggle();"';
    $toggler_css = $yidp_enabled ? '' : 'style="display: none;"';
    
    echo '<p>';
        echo '<label for="yidp_enabled">' . __("Hide content to visitors", 'your-id-please' ) . '</label>';
        echo '<input type="checkbox" id="yidp_enabled" name="yidp_enabled"' . checked( $yidp_enabled, 1, false ) . $toggler . ' />';
    echo '</p>';
    
    echo '<p class="yidp_toggle form-field" ' . $toggler_css . '>';
        echo '<label for="yidp_message" >' . __("Show this message", 'your-id-please' ) . '</label>';
        echo '<input type="text" id="yidp_message" name="yidp_message" class="long-text" value="' . $yidp_message . '" />';
    echo '</p>';
}

/**
 * yidp_setting_save( $post_id )
 *
 * This will process and save options for current post/page
 * @param Int $post_id, post/page ID
 * @return Int $post_id
 */
function yidp_setting_save( $post_id ) {
    if ( !wp_verify_nonce( $_POST['your-id-please'], 'your-id-please') )
        return $post_id;
    
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
        return $post_id;
    
    if ( 'page' == $_POST['post_type'] )
        if ( !current_user_can( 'edit_page', $post_id ) )
            return $post_id;
    else
        if ( !current_user_can( 'edit_post', $post_id ) )
            return $post_id;
    
    if( isset( $_POST['yidp_enabled'] ) )
        $yidp_enabled = ( 'on' == $_POST['yidp_enabled'] ) ? 1 : 0;
    
    if( isset( $_POST['yidp_message'] ) && !empty( $_POST['yidp_message'] ) )
        $yidp_message = sanitize_text_field( $_POST['yidp_message'] );
    
    if( isset( $yidp_enabled ) )
        update_post_meta( $post_id, 'your-id-please', $yidp_enabled );
    else
        delete_post_meta( $post_id, 'your-id-please' );
    
    if( isset( $yidp_message ) )
        update_post_meta( $post_id, 'your-id-please_message', $yidp_message );
    
    return $post_id;
}

add_filter( 'the_content', 'yidp_content' );
add_action( 'admin_init', 'yidp_settings' );
add_action( 'save_post', 'yidp_setting_save');
add_action( 'admin_init', 'yidp_textdomain'); // We need localization only there!
?>
