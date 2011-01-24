<?php
/*
Plugin Name: Please Login
Plugin URI: https://github.com/stas/please-login
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

define( 'PLEASE_LOGIN', '0.1' );

/**
 * pl_textdomain()
 *
 * Loads 
 */
function pl_textdomain() {
    load_plugin_textdomain( 'please_login', false, dirname( plugin_basename( __FILE__ ) ).'/languages' );
}

/**
 * pl_content( $content )
 *
 * Hides the content of posts/pages if admin marked pages with login required
 * @param String $content, the page/post content
 * @return String
 */
function pl_content( $content ) {
    global $post;
    $pl_enabled = get_post_meta( $post->ID, 'please_login', true );
    $pl_message = false;
    
    if( $pl_enabled )
        $pl_message = get_post_meta( $post->ID, 'please_login_message', true );
    
    if( $pl_message && !is_user_logged_in() )
        return '<p><a href="' . wp_login_url( get_permalink() ) .'">' . $pl_message. '</p>';
    else
        return $content;
}

/**
 * pl_settings()
 *
 * Adds metaboxes to post and page editor screens
 */
function pl_settings() {
    add_meta_box( 'please_login', __( 'Content Visibility', 'please_login' ), 'pl_box', 'post', 'side' );
    add_meta_box( 'please_login', __( 'Content Visibility', 'please_login' ), 'pl_box', 'page', 'side' );
    
    if( !get_option( 'pl_message' ) )
        add_option( 'pl_message', __( 'Please login to access this post/page content', 'please_login' ), null, true );
}

/**
 * pl_box()
 *
 * Renders the metabox content
 * @param Obj $post, the page/post object
 */
function pl_box( $post ) {
    $pl_enabled = (bool) get_post_meta( $post->ID, 'please_login', true );
    $pl_message = get_post_meta( $post->ID, 'please_login_message', true );
    $pl_message = empty( $pl_message ) ? get_option( 'pl_message' ) : $pl_message;
    
    wp_nonce_field( 'please_login', 'please_login' );
    
    $toggler = $pl_enabled ? '' : 'onclick="javascript:jQuery(\'.pl_toggle\').toggle();"';
    $toggler_css = $pl_enabled ? '' : 'style="display: none;"';
    
    echo '<p>';
        echo '<label for="pl_enabled">' . __("Hide content to visitors", 'please_login' ) . '</label>';
        echo '<input type="checkbox" id="pl_enabled" name="pl_enabled"' . checked( $pl_enabled, 1, false ) . $toggler . ' />';
    echo '</p>';
    
    echo '<p class="pl_toggle form-field" ' . $toggler_css . '>';
        echo '<label for="pl_message" >' . __("Show this message", 'please_login' ) . '</label>';
        echo '<input type="text" id="pl_message" name="pl_message" class="long-text" value="' . $pl_message . '" />';
    echo '</p>';
}

/**
 * pl_setting_save( $post_id )
 *
 * This will process and save options for current post/page
 * @param Int $post_id, post/page ID
 * @return Int $post_id
 */
function pl_setting_save( $post_id ) {
    if ( !wp_verify_nonce( $_POST['please_login'], 'please_login') )
        return $post_id;
    
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
        return $post_id;
    
    if ( 'page' == $_POST['post_type'] )
        if ( !current_user_can( 'edit_page', $post_id ) )
            return $post_id;
    else
        if ( !current_user_can( 'edit_post', $post_id ) )
            return $post_id;
    
    if( isset( $_POST['pl_enabled'] ) )
        $pl_enabled = ( 'on' == $_POST['pl_enabled'] ) ? 1 : 0;
    
    if( isset( $_POST['pl_message'] ) && !empty( $_POST['pl_message'] ) )
        $pl_message = sanitize_text_field( $_POST['pl_message'] );
    
    if( isset( $pl_enabled ) )
        update_post_meta( $post_id, 'please_login', $pl_enabled );
    else
        delete_post_meta( $post_id, 'please_login' );
    
    if( isset( $pl_message ) )
        update_post_meta( $post_id, 'please_login_message', $pl_message );
    
    return $post_id;
}

add_filter( 'the_content', 'pl_content' );
add_action( 'admin_init', 'pl_settings' );
add_action( 'save_post', 'pl_setting_save');
add_action( 'admin_init', 'pl_textdomain'); // We need localization only there!
?>
