<?php
// This is used strictly by the private_user_feed.php

// We want to make sure nobody influences our results.
remove_all_actions('pre_get_posts');
remove_all_filters('the_excerpt');
remove_all_filters('the_excerpt_rss');

// For the content parse, we want to remove only the memberful part.
remove_filter('the_content', 'memberful_wp_protect_content', 100);

$post_types = array("post");
$category = ( isset( $_GET['category'] ) ) ? sanitize_term_field( 'slug', $_GET['category'], 0, 'category', 'db' ) : '';

query_posts(array(
  'category__in'    => apply_filters( 'memberful_private_rss_category_ids', array() ),
  'category_name'   => $category,
  'post_type'       => apply_filters( 'memberful_private_rss_post_types', $post_types ),
  'posts_per_page'  => get_option( 'posts_per_rss', 10 )
));

echo '<?xml version="1.0" encoding="'.esc_attr(get_option('blog_charset')).'"?'.'>';

do_action( 'rss_tag_pre', 'rss2' );
?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
    <?php if(get_option('memberful_add_block_tags_to_rss_feed')): ?>
      xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
      xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
    <?php endif; ?>
    <?php do_action('rss2_ns'); ?>>
  <channel>
    <title><?php echo esc_html(memberful_private_user_feed_title()); ?></title>
    <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
    <link><?php bloginfo_rss('url') ?></link>
    <description><?php echo esc_html(memberful_private_user_feed_description()); ?></description>
    <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
    <language><?php bloginfo_rss( 'language' ); ?></language>
    <?php if (get_option('memberful_add_block_tags_to_rss_feed')): ?>
      <itunes:block>Yes</itunes:block>
      <googleplay:block>yes</googleplay:block>
    <?php endif; ?>
    <sy:updatePeriod><?php
$duration = 'hourly';

/**
 * Filter how often to update the RSS feed.
 *
 * @since 2.1.0
 *
 * @param string $duration The update period. Accepts 'hourly', 'daily', 'weekly', 'monthly',
 *                         'yearly'. Default 'hourly'.
 */
echo esc_html(apply_filters( 'rss_update_period', $duration ));
?></sy:updatePeriod>
  <sy:updateFrequency><?php
$frequency = '1';

/**
 * Filter the RSS update frequency.
 *
 * @since 2.1.0
 *
 * @param string $frequency An integer passed as a string representing the frequency
 *                          of RSS updates within the update period. Default '1'.
 */
echo esc_html(apply_filters( 'rss_update_frequency', $frequency ));
?></sy:updateFrequency>
<?php
/**
 * Fires at the end of the RSS2 Feed Header.
 *
 * @since 2.0.0
 */
do_action( 'rss2_head');

while( have_posts()) : the_post();

if (!memberful_can_user_access_rss_post( $user_id, get_the_id() )) {
  continue;
}

global $more;
$more = 1;
?>
      <item>
        <title><?php the_title_rss() ?></title>
        <link><?php the_permalink_rss() ?></link>
        <comments><?php comments_link_feed(); ?></comments>
        <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
        <dc:creator><![CDATA[<?php the_author() ?>]]></dc:creator>
        <?php the_category_rss('rss2') ?>

        <guid isPermaLink="false"><?php the_guid(); ?></guid>
        <?php if (get_option('rss_use_excerpt')) : ?>
          <description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
        <?php else : ?>
          <description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
          <?php $content = get_the_content_feed('rss2'); ?>
          <?php if ( strlen( $content ) > 0 ) : ?>
            <content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
          <?php else : ?>
            <content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded>
          <?php endif; ?>
        <?php endif; ?>
        <wfw:commentRss><?php echo esc_url( get_post_comments_feed_link(null, 'rss2') ); ?></wfw:commentRss>
        <slash:comments><?php echo get_comments_number(); ?></slash:comments>
        <?php rss_enclosure(); ?>
<?php
/**
 * Fires at the end of each RSS2 feed item.
 *
 * @since 2.0.0
 */
do_action( 'rss2_item' );
?>
      </item>
    <?php endwhile; ?>
  </channel>
</rss>
