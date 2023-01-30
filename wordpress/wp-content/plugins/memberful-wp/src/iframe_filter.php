<?php

# add_filter( 'wp_kses_allowed_html', 'wp_kses_allow_iframes', 10, 2 );
# 
# function wp_kses_allow_iframes( $allowedposttags, $context ) {
#   if ($context === 'post' && current_user_can( 'publish_posts' )) {
#     $allowedposttags['iframe'] = array(
#       'allow' => true,
#       'allowfullscreen' => true,
#       'frameborder' => true,
#       'height' => true,
#       'src' => true,
#       'title' => true,
#       'width' => true
#     );
#   }
# 
#   return $allowedposttags;
# }
# 
# add_action( 'init', 'register_video_embed_handlers' );
# 
# function register_video_embed_handlers() {
# 
#   wp_embed_register_handler(
#     'youtube',
#     '#(?:https?:\/\/)?(?:youtu\.be\/|(?:www\.|m\.)?youtube\.com\/(?:watch|v|embed)?(?:\?.*v=|\/))([a-zA-Z0-9\-_]+)#i',
#     'youtube_embeds_handler'
#   );
# 
#   # wp_embed_register_handler(
#   #   'vimeo',
#   #   '#^((?:https?:)?\/\/)?((?:www|m)\.)?((?:vimeo\.com))#i',
#   #   'vimeo_embed_handler'
#   # );
# }
# 
# function youtube_embeds_handler( $matches, $attr, $url, $rawattr ) {
#   $embed = sprintf(
#     '<iframe width="560" height="315" src="https://www.youtube.com/embed/%1$s" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
#     esc_attr($matches[1]),
#   );
# 
#   return apply_filters( 'embed_youtube', $embed, $matches, $attr, $url, $rawattr );
# }
#
# function vimeo_embed_handler( $matches, $attr, $url, $rawattr ) {
#   error_log( 'registering vimeo embed handler' );
#
#   $embed = sprintf(
#     '<iframe src="https://www.vimeo.com/video/embed/embed.html?show=%1$s&format=frame&height=%2$s&width=%3$s&video=%4$s&mode=render&quot; width="%3$spx" height="%2$spx" frameborder="0" scrolling="no" marginwidth="0" marginheight="0"></iframe>',
#     esc_attr($matches[1]),
#     esc_attr($matches[2]),
#     esc_attr($matches[3]),
#     esc_attr($matches[4])
#   );
#
#   return $embed;
# }
