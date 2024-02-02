<?php
/*
Plugin Name: Custom Paid Membership Pro Extension
Plugin URI: http://origamifc.it
Description: Extends the functionality of Paid Membership Pro.
Version: 1.0
Author: Origami
Author URI: http://origamifc.it
License: GPLv2 or later
*/

// Add a field for the number of allowed views during membership level registration
function custom_add_views_field() {
    $level_id = $_REQUEST['edit'];
    $views_allowed = get_option( 'pmpro_custom_views_allowed_' . $level_id ); // Get the already saved value

    ?>
    <tr class="form-field">
        <th scope="row"><label for="views_allowed"><?php _e( 'Allowed Views', 'custom-plugin' ); ?></label></th>
        <td><input type="number" name="views_allowed" id="views_allowed" value="<?php echo esc_attr( $views_allowed ); ?>"></td>
    </tr>
    <?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'custom_add_views_field' );

// Save the value of the number of allowed views in the database
function custom_save_views_field( $level_id ) {
    if ( isset( $_REQUEST['views_allowed'] ) ) {
        $views_allowed = intval( $_REQUEST['views_allowed'] );
        update_option( 'pmpro_custom_views_allowed_' . $level_id, $views_allowed );
    }
}
add_action( 'pmpro_save_membership_level', 'custom_save_views_field' );


// Track post views and update the value in the user profile for selected posts and custom post types
function custom_track_post_views() {
    if ( is_single() && ! is_admin() && is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $post_views = get_user_meta( $user_id, 'custom_user_post_views', true );

        // Get the publication date of the current post or custom post type
        $post_id = get_the_ID();
        $post_date = get_the_date( 'Y-m-d', $post_id );
        $post_type = get_post_type( $post_id );

        // Calculate seven days ago
        $seven_days_ago = date( 'Y-m-d', strtotime( '-7 days' ) );

        // Check if the post or custom post type was published less than seven days ago
        if ( $post_date > $seven_days_ago || custom_is_cpt_access( $post_type ) ) {
            // The post or custom post type was published less than seven days ago, so we don't track views
            return;
        }
		
		// Check if the user has access to the current post
        if ( ! pmpro_has_membership_access( $post_id, $user_id ) ) {
            // Redirect the user to the subscription page if they don't have access
            wp_redirect( pmpro_url( 'levels' ) );
            exit;
        }  

        // The post was published more than seven days ago and is not a custom post type selected with the PMPro CPT Access extension, so we track views

        // User has reached the limit of allowed views
        $membership_level = pmpro_getMembershipLevelForUser( $user_id );
        $views_allowed = isset( $membership_level->id ) ? get_option( 'pmpro_custom_views_allowed_' . $membership_level->id ) : 0;
        if ( $post_views >= $views_allowed ) {
            // User has reached the limit of allowed views
            $redirect_url = pmpro_url( 'levels' ); // URL of the PMPro subscription levels page
            wp_redirect( $redirect_url );
            exit;
        }

        // Increment the views counter only if the post was not published less than seven days ago
        $post_views++;
        update_user_meta( $user_id, 'custom_user_post_views', $post_views );
    }
}
add_action( 'template_redirect', 'custom_track_post_views' );

// Check if a custom post type was published recently (less than seven days ago) and if it is among those selected with the PMPro CPT Access extension
function custom_is_cpt_access( $post_type ) {
    $cpt_access_settings = get_option( 'pmpro_cptaccess_settings' );
    $allowed_cpt_ids = isset( $cpt_access_settings['allowed_cpt'] ) ? $cpt_access_settings['allowed_cpt'] : array();

    // Check if the custom post type is among those selected with the PMPro CPT Access extension
    if ( ! in_array( $post_type, $allowed_cpt_ids ) ) {
        return false; // The custom post type is not among those selected with the PMPro CPT Access extension, so return false
    }

    // Execute the query to retrieve posts of the specified type published in the last seven days
    $args = array(
        'post_type' => $post_type,
        'date_query' => array(
            array(
                'after' => '-7 days',
                'inclusive' => true,
            ),
        ),
    );

    $recent_posts = get_posts( $args );

    return ! empty( $recent_posts );
}

// Function to reset the view counter when the user renews the membership
function custom_reset_post_views_on_membership_renewal( $level_id, $user_id ) {
    delete_user_meta( $user_id, 'custom_user_post_views' );
}
add_action( 'pmpro_after_change_membership_level', 'custom_reset_post_views_on_membership_renewal', 10, 2 );


// Add numeric field for post views and membership limit in user profile
function custom_user_profile_fields( $user ) {
    $post_views = get_user_meta( $user->ID, 'custom_user_post_views', true );
    $membership_level = pmpro_getMembershipLevelForUser( $user->ID );
    $views_allowed = isset( $membership_level->id ) ? get_option( 'pmpro_custom_views_allowed_' . $membership_level->id ) : 0;
    $current_user = wp_get_current_user();

    // Check if the current user is an administrator
    $is_admin = current_user_can( 'administrator' );

    ?>
    <h3><?php _e( 'Membership Details', 'custom-plugin' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="post_views"><?php _e( 'Post Views', 'custom-plugin' ); ?></label></th>
            <td>
                <?php if ( $is_admin ) : ?>
                    <input type="number" name="post_views" id="post_views" value="<?php echo esc_attr( $post_views ); ?>">
                <?php else : ?>
                    <input type="number" name="post_views" id="post_views" value="<?php echo esc_attr( $post_views ); ?>" readonly>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="views_allowed"><?php _e( 'Allowed Views Limit', 'custom-plugin' ); ?></label></th>
            <td><input type="number" name="views_allowed" id="views_allowed" value="<?php echo esc_attr( $views_allowed ); ?>" readonly></td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'custom_user_profile_fields' );
add_action( 'edit_user_profile', 'custom_user_profile_fields' );

// Save user profile data
function custom_save_user_profile_fields( $user_id ) {
    // Check if the current user can edit the profile
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    // Update the value of post views if submitted
    if ( isset( $_POST['post_views'] ) ) {
        $post_views = intval( $_POST['post_views'] );
        update_user_meta( $user_id, 'custom_user_post_views', $post_views );
    }
}
add_action( 'personal_options_update', 'custom_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'custom_save_user_profile_fields' );


// Add the view count box before the closing </body> tag
function custom_add_view_count_box() {
    if ( ( is_single() || ( function_exists( 'pmpro_has_membership_access' ) && pmpro_has_membership_access() ) ) && ! is_admin() ) {
        global $post;
        $post_type = get_post_type( $post );

        // Exclude pages
        if ( $post_type === 'page' ) {
            return;
        }

        $user_id = get_current_user_id();
        $post_views = get_user_meta( $user_id, 'custom_user_post_views', true );
        $membership_level = pmpro_getMembershipLevelForUser( $user_id );
        $views_allowed = isset( $membership_level->id ) ? get_option( 'pmpro_custom_views_allowed_' . $membership_level->id ) : 0;

        echo '<div id="custom-view-count" style="position: fixed; bottom: 10px; right: 10px; background-color: #555; padding: 5px; border-radius: 5px; z-index: 9999;">
                <span id="custom-view-counter">' . $post_views . '</span> / <span id="custom-views-allowed">' . $views_allowed . '</span>
              </div>';
    }
}
add_action( 'wp_footer', 'custom_add_view_count_box' );

?>
