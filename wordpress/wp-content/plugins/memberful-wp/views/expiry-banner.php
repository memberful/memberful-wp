<?php
/**
 * Global expiry banner view.
 *
 * @package Memberful
 */

?>
<div
  id="memberful-expiry-banner"
  role="alert"
  style="position:fixed;top:var(--wp-admin--admin-bar--height,0px);left:0;right:0;z-index:99999;display:flex;align-items:center;justify-content:center;gap:1em;padding:0.5em 1em;margin:0;font:inherit;font-size:0.875rem;line-height:1.4;background:#fef3cd;color:#664d03;border-bottom:1px solid #e0c882;"
>
  <p style="margin:0;"><?php echo wp_kses_post( $message ); ?></p>
  <button
    type="button"
    aria-label="<?php echo esc_attr__( 'Dismiss membership expiry banner', 'memberful' ); ?>"
    style="background:transparent;border:0;color:inherit;cursor:pointer;font:inherit;font-size:1rem;line-height:1;padding:0;"
  >x</button>
</div>
<script async defer>
  !function(){var e="memberful_expiry_banner_dismissed",n=document.getElementById("memberful-expiry-banner");if(n)if(window.sessionStorage&&"1"===window.sessionStorage.getItem(e))n.style.display="none";else{var s=n.querySelector("button");s&&s.addEventListener("click",(function(){window.sessionStorage&&window.sessionStorage.setItem(e,"1"),n.style.display="none"}))}}();
</script>
