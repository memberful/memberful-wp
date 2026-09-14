(() => {
  const settings = window.memberful_expiry_banner || {};
  const hiddenClass = "memberful-expiry-banner--js-hidden";
  const visibleClass = "memberful-expiry-banner-visible";
  const banner = document.getElementById("memberful-expiry-banner");

  if (!banner) {
    return;
  }

  let bumpStyle = document.getElementById("memberful-expiry-banner-bump-style");

  if (!bumpStyle) {
    bumpStyle = document.createElement("style");
    bumpStyle.id = "memberful-expiry-banner-bump-style";
    document.head.appendChild(bumpStyle);
  }

  const refreshOffset = () => {
    const height = banner.classList.contains(hiddenClass) ? 0 : banner.offsetHeight;

    if (document.body) {
      document.body.classList.toggle(visibleClass, height > 0);
    }

    bumpStyle.textContent = `@media screen { html { margin-top: calc(var(--wp-admin--admin-bar--height, 0px) + ${height}px) !important; } }`;
  };

  const persistDismissal = () => {
    const { ajaxUrl, nonce, signature } = settings;

    if (!ajaxUrl || !nonce) {
      return;
    }

    const body = new URLSearchParams({
      action: "memberful_dismiss_expiry_banner",
      memberful_nonce: nonce,
      signature: signature || "",
    });

    fetch(ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body,
    }).catch(() => {});
  };

  banner.classList.remove(hiddenClass);

  const dismissButton = banner.querySelector(".memberful-expiry-banner__dismiss");

  if (dismissButton) {
    dismissButton.addEventListener("click", () => {
      banner.classList.add(hiddenClass);
      refreshOffset();
      persistDismissal();
    });
  }

  window.addEventListener("resize", refreshOffset);
  window.addEventListener("orientationchange", refreshOffset);
  refreshOffset();
})();
