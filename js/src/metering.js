/**
 * Cache-safe metering runtime.
 *
 * The page HTML is a count-agnostic, cacheable default; this script applies the per-visitor decision:
 *   - free posts are enforced immediately via localStorage and mirrored to the server outside the critical path, and
 *   - protected samples are released only by the uncacheable admin-ajax endpoint against the shared server ledger.
 */
(() => {
  const cfg = window.memberfulMetering;
  if (!cfg || !cfg.postId) {
    return;
  }

  const MAX_VIEWS = 100;
  const nowSeconds = () => Math.floor(Date.now() / 1000);

  const readState = () => {
    try {
      const parsed = JSON.parse(window.localStorage.getItem(cfg.storageKey) || '{}');
      const views = parsed && parsed.views && typeof parsed.views === 'object' ? parsed.views : {};
      const pending =
        parsed && parsed.pending && typeof parsed.pending === 'object' ? parsed.pending : {};

      return { views, pending };
    } catch (e) {
      return { views: {}, pending: {} };
    }
  };

  const prune = (items) => {
    const cutoff = nowSeconds() - cfg.periodDays * 86400;
    const kept = {};
    Object.keys(items).forEach((id) => {
      if (Number(items[id]) >= cutoff) {
        kept[id] = Number(items[id]);
      }
    });
    return kept;
  };

  const pruneState = (state) => {
    const views = prune(state.views);
    const pending = prune(state.pending);

    Object.keys(pending).forEach((id) => {
      if (!Object.prototype.hasOwnProperty.call(views, id)) {
        delete pending[id];
      }
    });

    return { views, pending };
  };

  const persist = (state) => {
    const ids = Object.keys(state.views);
    if (ids.length > MAX_VIEWS) {
      ids
        .sort((a, b) => state.views[a] - state.views[b])
        .slice(0, ids.length - MAX_VIEWS)
        .forEach((id) => {
          delete state.views[id];
          delete state.pending[id];
        });
    }
    try {
      window.localStorage.setItem(
        cfg.storageKey,
        JSON.stringify({
          views: state.views,
          pending: state.pending,
        }),
      );
    } catch (e) {
      // localStorage unavailable (private mode): the meter falls back to the cached HTML state.
    }

    return state;
  };

  const record = (state, needsSync) => {
    if (!Object.prototype.hasOwnProperty.call(state.views, cfg.postId)) {
      const timestamp = nowSeconds();
      state.views[cfg.postId] = timestamp;
      if (needsSync) {
        state.pending[cfg.postId] = timestamp;
      }
      persist(state);
    }

    return state;
  };

  const pendingIds = (state) =>
    Object.keys(state.pending).sort(
      (a, b) => state.pending[a] - state.pending[b] || Number(a) - Number(b),
    );
  const viewIds = (state) =>
    Object.keys(state.views).sort(
      (a, b) => state.views[a] - state.views[b] || Number(a) - Number(b),
    );

  const endpointPost = (op, keepalive, ids = []) => {
    const body = new window.URLSearchParams();
    body.set('action', cfg.action);
    body.set('op', op);
    body.set('post_id', String(cfg.postId));
    const field = op === 'sample' ? 'public_post_ids[]' : 'post_ids[]';
    ids.forEach((id) => body.append(field, id));

    return window.fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
      keepalive: Boolean(keepalive),
    });
  };

  const acknowledge = (ids) => {
    const state = pruneState(readState());
    ids.forEach((id) => delete state.pending[id]);
    persist(state);
  };

  const syncPending = (state) => {
    const ids = pendingIds(state);
    if (!ids.length) {
      return Promise.resolve();
    }

    return endpointPost('record_public', true, ids)
      .then((response) => response.json())
      .then((payload) => {
        const data = payload && payload.success ? payload.data : null;
        if (!data) {
          throw new Error('Public meter synchronization failed');
        }
        acknowledge(Array.isArray(data.synced) ? data.synced.map(String) : []);
      });
  };

  const setTripped = () => {
    document.documentElement.classList.add('memberful-metering-tripped');
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
    let state = persist(pruneState(readState()));

    if (cfg.limit <= 0) {
      syncPending(state).catch(() => {});
      setTripped();
      return;
    }

    const alreadyCounted = Object.prototype.hasOwnProperty.call(state.views, cfg.postId);

    if (!alreadyCounted && Object.keys(state.views).length >= cfg.limit) {
      syncPending(state).catch(() => {});
      setTripped();
      return;
    }

    if (!alreadyCounted) {
      state = record(state, true);
    }

    syncPending(state).catch(() => {});
    hydrateCountdown(cfg.limit - Object.keys(state.views).length);
  };

  const runProtected = () => {
    const container = document.querySelector('.memberful-metering');
    const state = persist(pruneState(readState()));

    endpointPost('sample', false, viewIds(state))
      .then((response) => response.json())
      .then((payload) => {
        const data = payload && payload.success ? payload.data : null;
        if (!data) {
          return;
        }

        acknowledge(Array.isArray(data.synced) ? data.synced.map(String) : []);

        if (!data.released || !container) {
          return;
        }

        const content = container.querySelector('.memberful-metering__content');
        const paywall = container.querySelector('.memberful-metering__paywall');
        if (content) {
          content.innerHTML = data.html || '';
          content.hidden = false;
        }
        if (paywall) {
          paywall.hidden = true;
        }

        record(persist(pruneState(readState())), false);
        hydrateCountdown(data.remaining || 0);
      })
      .catch(() => {
        // Endpoint/network failure leaves the cached paywall in place - fail-closed for protected content.
      });
  };

  if (cfg.mode === 'free_meter') {
    runFree();
  } else if (cfg.mode === 'protected_sample') {
    runProtected();
  }
})();
