(() => {
  const dismissStorageKey = "memberful_expiry_banner_dismissed";
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

  const isDismissed = () => {
    if (!window.sessionStorage) {
      return false;
    }

    try {
      return window.sessionStorage.getItem(dismissStorageKey) === "1";
    } catch (error) {
      return false;
    }
  };

  const markDismissed = () => {
    if (!window.sessionStorage) {
      return;
    }

    try {
      window.sessionStorage.setItem(dismissStorageKey, "1");
    } catch (error) {
      // Ignore session storage write failures.
    }
  };

  if (isDismissed()) {
    banner.classList.add(hiddenClass);
  } else {
    banner.classList.remove(hiddenClass);
    const dismissButton = banner.querySelector(".memberful-expiry-banner__dismiss");

    if (dismissButton) {
      dismissButton.addEventListener("click", () => {
        markDismissed();
        banner.classList.add(hiddenClass);
        refreshOffset();
      });
    }
  }

  window.addEventListener("resize", refreshOffset);
  window.addEventListener("orientationchange", refreshOffset);
  refreshOffset();
})();
