<div class="wrap">
  <?php memberful_wp_render('option_tabs', array('active' => 'bulk_protect')); ?>
  <?php memberful_wp_render('flash'); ?>

  <?php if( isset( $_GET['success'] ) && $_GET['success'] == 'bulk' ) :  ?>
    <div class="updated notice">
      <p><?php _e( "Bulk restrictions have been applied successfully.", 'memberful' ); ?></p>
    </div>
  <?php else : ?>
    <div class="update-nag">
      <?php _e( "<strong>Be careful:</strong> When you bulk apply these restrict access settings we will <strong>overwrite and replace</strong> any specified individual Post or Page restrict access settings.", 'memberful' ); ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?php echo $form_target ?>">
    <div class="memberful-bulk-apply-box">
      <h3><?php _e( "Bulk apply restrict access settings", 'memberful' ); ?></h3>
      <fieldset>
        <label><?php _e( "Apply the restrict access settings specified below to:", 'memberful' ); ?></label>
        <select name="target_for_restriction" id="global-restrict-target" class="postform">
          <option value="all_pages_and_posts" selected="selected"><?php _e( "All Pages and Posts", 'memberful' ); ?></option>
          <option value="all_pages"><?php _e( "All Pages", 'memberful' ); ?></option>
          <option value="all_posts"><?php _e( "All Posts", 'memberful' ); ?></option>
          <option value="all_posts_from_category"><?php _e( "All Posts from a category or categories", 'memberful' ); ?></option>
        </select>
        <ul data-depends-on="global-restrict-target" data-depends-value="all_posts_from_category" class="memberful-global-restrict-access-category-list">
          <?php foreach(get_categories() as $category): ?>
            <li><label><input type="checkbox"  name="memberful_protect_categories[]" value="<?php echo $category->cat_ID ?>"><?php echo $category->cat_name; ?></option></label></li>
      <?php endforeach; ?>
        </ul>
          <p>
            <input type="submit" class="button button-secondary" value="<?php _e( "Bulk apply restrict access settings", 'memberful' ); ?>" />
          </p>
      </fieldset>
    </div>
    <div>
    <?php memberful_wp_render( 'metabox', compact( 'subscriptions', 'products', 'marketing_content' ) ); ?>
  </div>
    <?php memberful_wp_nonce_field( 'memberful_options' ); ?>
  </form>
</div>
