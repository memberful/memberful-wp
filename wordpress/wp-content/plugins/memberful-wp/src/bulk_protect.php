<?php

function memberful_additional_post_types_to_protect() {
  $post_types = get_post_types( array( "public" => true, "show_in_menu" => true ), "objects" );

  unset(
    $post_types["attachment"],
    $post_types["page"],
    $post_types["post"]
  );

  return $post_types;
}
