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

    const template = document.getElementById('memberful-metering-paywall');
    if (!template) {
      return;
    }

    // Every marked body on the page: a theme may render the queried post's content more than once. The markers come
    // in pairs, so the nth start goes with the nth end.
    const ends = document.querySelectorAll('.memberful-metering__end[data-memberful-metering="free"]');
    document.querySelectorAll('.memberful-metering__start[data-memberful-metering="free"]').forEach((start, index) => {
      const end = ends[index];
      const home = start.parentElement;
      if (!end || !home) {
        return;
      }

      // An unclosed tag in the body can fold the end marker into the last block; move it back beside the start. A
      // stray closing tag can push it out of the body's container; put it at the end of that container.
      if (end.parentElement !== home) {
        let top = end;
        while (top.parentElement && top.parentElement !== home) {
          top = top.parentElement;
        }
        if (top.parentElement === home) {
          top.after(end);
        } else {
          home.append(end);
        }
      }

      // Hide the body inline, so the swap holds without the stylesheet, and show the paywall after it.
      for (let node = start.nextElementSibling; node && node !== end; node = node.nextElementSibling) {
        node.style.setProperty('display', 'none', 'important');
      }
      end.after(document.importNode(template.content, true));
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
