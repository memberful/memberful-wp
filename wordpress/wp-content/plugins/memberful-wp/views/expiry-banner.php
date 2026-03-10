<?php
/**
 * Global expiry banner view.
 *
 * @package Memberful
 */

?>
<div id="memberful-expiry-banner" role="<?php echo esc_attr( $aria_role ); ?>" aria-live="<?php echo esc_attr( $aria_live ); ?>" class="memberful-expiry-banner">
  <p class="memberful-expiry-banner__message"><?php echo wp_kses_post( $message ); ?></p>
  <button type="button" class="memberful-expiry-banner__dismiss" aria-label="<?php echo esc_attr__( 'Dismiss membership expiry banner', 'memberful' ); ?>">
    <span aria-hidden="true">&times;</span>
    <span class="screen-reader-text"><?php echo esc_html__( 'Dismiss', 'memberful' ); ?></span>
  </button>
</div>
<style>
.memberful-expiry-banner{position:fixed;top:var(--wp-admin--admin-bar--height,0);left:0;right:0;z-index:var(--memberful-expiry-banner-z-index,9999);display:flex;align-items:center;justify-content:center;gap:1em;padding:.5em 1em;margin:0;font:inherit;font-size:.875rem;line-height:1.4;background:var(--memberful-expiry-banner-background,#fef3cd);color:var(--memberful-expiry-banner-colour,#664d03);border-bottom:1px solid var(--memberful-expiry-banner-border-colour,#e0c882)}.memberful-expiry-banner a{color:inherit;text-decoration:underline}.memberful-expiry-banner__message{margin:0}.memberful-expiry-banner__dismiss{min-width:2rem;min-height:2rem;margin:0;padding:0;border:0;background:0 0;color:inherit;cursor:pointer;font:inherit;font-size:1.25rem;line-height:1}
</style>
<script async defer>
  !function(){var e="memberful_expiry_banner_dismissed",n=document.getElementById("memberful-expiry-banner");if(!n)return;var t="memberful-expiry-banner-visible",o=document.getElementById("memberful-expiry-banner-bump-style");o||(o=document.createElement("style"),o.id="memberful-expiry-banner-bump-style",document.head.appendChild(o));var i=function(){var e="none"===n.style.display?0:n.offsetHeight;document.body&&document.body.classList.toggle(t,e>0),o.textContent="@media screen { html { margin-top: calc(var(--wp-admin--admin-bar--height, 0px) + "+e+"px) !important; } }"},r=function(){if(!window.sessionStorage)return!1;try{return"1"===window.sessionStorage.getItem(e)}catch(n){return!1}},s=function(){if(!window.sessionStorage)return;try{window.sessionStorage.setItem(e,"1")}catch(n){}};if(r())n.style.display="none";else{var a=n.querySelector(".memberful-expiry-banner__dismiss");a&&a.addEventListener("click",(function(){s(),n.style.display="none",i()}))}window.addEventListener("resize",i),window.addEventListener("orientationchange",i),i()}();
</script>
