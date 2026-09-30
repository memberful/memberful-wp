/**
 * Cache-safe metering runtime.
 *
 * The page HTML is a count-agnostic, cacheable default; this script applies the per-visitor decision for a public
 * metered post from the views kept in localStorage.
 */
(() => {
  const cfg = window.memberfulMetering;
  if (!cfg || !cfg.postId) {
    return;
  }

  const MAX_VIEWS = 100;
  const nowSeconds = () => Math.floor(Date.now() / 1000);

  const readViews = () => {
    try {
      const parsed = JSON.parse(window.localStorage.getItem(cfg.storageKey) || '{}');
      return parsed && parsed.views && typeof parsed.views === 'object' ? parsed.views : {};
    } catch (e) {
      return {};
    }
  };

  const prune = (views) => {
    const cutoff = nowSeconds() - cfg.periodDays * 86400;
    const kept = {};
    Object.keys(views).forEach((id) => {
      if (Number(views[id]) >= cutoff) {
        kept[id] = Number(views[id]);
      }
    });
    return kept;
  };

  const persist = (views) => {
    const ids = Object.keys(views);
    if (ids.length > MAX_VIEWS) {
      ids
        .sort((a, b) => views[a] - views[b])
        .slice(0, ids.length - MAX_VIEWS)
        .forEach((id) => {
          delete views[id];
        });
    }
    try {
      window.localStorage.setItem(cfg.storageKey, JSON.stringify({ views }));
    } catch (e) {
      // localStorage unavailable (private mode): the meter falls back to the cached HTML state.
    }

    return views;
  };

  const setTripped = () => {
    document.documentElement.classList.add('memberful-metering-tripped');

    // Hide the body's top-level blocks inline, so the swap holds without the stylesheet, and show the paywall. Every
    // free paywall on the page: a theme may render the queried post's content more than once.
    document.querySelectorAll('.memberful-metering__paywall[data-memberful-metering="free"]').forEach((paywall) => {
      // An unclosed tag in the body can fold the paywall into the last block; move it back beside the body.
      const home = paywall.closest('.wp-block-post-content, .entry-content');
      if (home && paywall.parentElement !== home) {
        let top = paywall;
        while (top.parentElement !== home) {
          top = top.parentElement;
        }
        top.after(paywall);
      }
      for (let node = paywall.previousElementSibling; node; node = node.previousElementSibling) {
        node.style.setProperty('display', 'none', 'important');
      }
      paywall.hidden = false;
    });
  };

  // Hydrate every placeholder on the page: the block may sit in the theme template, the post body, or both.
  const hydrateCountdown = (remaining) => {
    const count = Math.max(0, remaining);
    document.querySelectorAll('[data-memberful-countdown]').forEach((node) => {
      let template;
      if (count === 0) {
        template = node.getAttribute('data-memberful-template-last') || '';
      } else if (count === 1) {
        template = node.getAttribute('data-memberful-template-singular') || '';
      } else {
        template = node.getAttribute('data-memberful-template') || '';
      }
      if (template.trim() === '') {
        return;
      }
      node.textContent = template.replace(/\{count\}/g, String(count));
      node.hidden = false;
    });
  };

  const runFree = () => {
    const views = persist(prune(readViews()));

    if (cfg.limit <= 0) {
      setTripped();
      return;
    }

    const alreadyCounted = Object.prototype.hasOwnProperty.call(views, cfg.postId);

    if (!alreadyCounted && Object.keys(views).length >= cfg.limit) {
      setTripped();
      return;
    }

    if (!alreadyCounted) {
      views[cfg.postId] = nowSeconds();
      persist(views);
    }

    hydrateCountdown(cfg.limit - Object.keys(views).length);
  };

  const start = () => {
    if (cfg.mode === 'free_meter') {
      runFree();
    }
  };

  // A prerendered page runs its scripts before the reader has opened it. Wait until it is shown before counting.
  if (document.prerendering) {
    document.addEventListener('prerenderingchange', start, { once: true });
  } else {
    start();
  }
})();
