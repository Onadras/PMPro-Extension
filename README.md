# PMPro-Post-View-Limit

Custom wordpress plugin to extend PMPro functionality in order to setup a post view based subscription system. Each subscription level should allow for a set number of post views. Once the users reach the view limit, they are redirected to the subscription plans page.

The plugin currently extends PMPro with the following features:

1. Add a custom field in order to define the number of views allowed for each membership level during setup
2. Track post and custom post type views, and redirect the users to the membership levels page once they reach the limit of allowed views.
3. Display two fields in the wordpress user profile page, one with the current post views and one with the views limit of the membership level. Admins can edit the former field.
4. Work in tandem with the PMPro recipe open_new_posts_to_non_members in order to avoid conflicts and let users freely access all the newer posts without increasing their view count.
5. Display a view counter on the frontend to let users keep track of their membership limit.

The following part explains the main blocks of functions of the plugin.

<strong>Add Views Field Function (custom_add_views_field):</strong>

This function creates an additional field for setting the allowed number of views in the membership level options. It inserts a input field where administrators can specify the maximum number of views allowed for users with that membership level.

<strong>Track Post Views Function (custom_track_post_views):</strong>

This function tracks the number of views for posts accessed by users. It also tracks the views for the custom post types included in the addon CPT Access with the function custom_is_cpt_access. 

Moreover it excludes counting views for posts and custom post types published within the last 7 days. This is due to my need of having the newsest content freely available for everyone. It works together with the PMPro recipe open_new_posts_to_non_members. 

When the number of views reaches the view limit of the user membership level, the function redirects the user to the membership level page. It also keeps into account guests and users without membership, redirecting them to the membership level page whenever they try to access restricted content. Another check involves users with membership that try to access posts not allowed by their level, they are redirected to the membership level page without increasing their view count (they didn't view the post after all). 

Finally, the function custom_reset_post_views_on_membership_renewal makes sure to reset the view counter when the users renew their membership.

<strong>User Profile Fields Function (custom_user_profile_fields):</strong>

This should be useful for administrators. This function adds two additional fields to the user profile page: one for displaying the number of post views and another for displaying the maximum views allowed based on the user's membership level. Administrators can edit the number of post views if needed for whatever reason.

<strong>Add View Count Box Function (custom_add_view_count_box):</strong>

This function adds a view count box to the frontend of the website. It displays the current number of views and the maximum views allowed for the user's membership level. The view count box is only shown on single post pages and custom post types, excluding pages.
