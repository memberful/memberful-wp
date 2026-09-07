<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'bulk_protect')); ?>
  <?php memberful_wp_render('flash'); ?>

  <?php if( isset( $_GET['success'] ) && $_GET['success'] == 'bulk' ) { ?>
    <div class="updated notice">
      <p><?php _e( "Bulk restrictions have been applied successfully.", 'memberful' ); ?></p>
    </div>
  <?php } elseif( isset( $_GET['error'] ) ) { ?>
    <div class="error notice">
      <p><?php echo esc_html( $_GET['error'] ); ?></p>
    </div>
  <?php } else { ?>
    <div class="update-nag">
      <?php _e( "<strong>Be careful:</strong> When you bulk apply these restrict access settings we will <strong>overwrite and replace</strong> any specified individual Post or Page restrict access settings.", 'memberful' ); ?>
    </div>
  <?php } ?>

  <form method="POST" action="<?php echo esc_url($form_target); ?>">
    <div class="memberful-bulk-apply-box">
      <h3><?php _e( "Bulk apply restrict access settings", 'memberful' ); ?></h3>
      <p>
        The Bulk Restrict Access Tool can be used to update multiple pages or posts <strong>one-time</strong>. e.g. You're a new Memberful user and you need to quickly restrict <strong>all</strong> of your posts.
        <br /><br />
        If you want to broadly control access to posts <strong>based on a tag or category</strong>, navigate to <em>Posts → Category or Posts → Tags</em>, and select a Category or Tag from the list.
      </p>
      <fieldset>
        <label><?php _e( "Apply the restrict access settings specified below to:", 'memberful' ); ?></label>
        <select name="target_for_restriction" id="global-restrict-target" class="postform">
          <option value="all_pages_and_posts" selected="selected"><?php _e( "All Pages and Posts", 'memberful' ); ?></option>
          <option value="all_pages"><?php _e( "All Pages", 'memberful' ); ?></option>
          <option value="all_posts"><?php _e( "All Posts", 'memberful' ); ?></option>
          <option value="all_posts_from_category"><?php _e( "All Posts from a category or categories", 'memberful' ); ?></option>
          <?php foreach(memberful_additional_post_types_to_protect() as $post_type): ?>
            <option value="<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->labels->all_items); ?></option>
          <?php endforeach; ?>
        </select>
        <ul data-depends-on="global-restrict-target" data-depends-value="all_posts_from_category" class="memberful-global-restrict-access-category-list">
          <?php foreach(get_categories() as $category): ?>
            <li><label><input type="checkbox"  name="memberful_protect_categories[]" value="<?php echo esc_attr($category->cat_ID); ?>"><?php echo esc_html($category->cat_name); ?></label></li>
      <?php endforeach; ?>
        </ul>
          <p>
            <input type="submit" class="button button-secondary" value="<?php _e( "Bulk apply restrict access settings", 'memberful' ); ?>" />
          </p>
      </fieldset>
    </div>
    <div>
    <?php memberful_wp_render( 'metabox', compact( 'subscriptions', 'products', 'marketing_content', 'viewable_by_any_registered_users', 'viewable_by_anybody_subscribed_to_a_plan' ) ); ?>
  </div>
    <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
  </form>
</div>
