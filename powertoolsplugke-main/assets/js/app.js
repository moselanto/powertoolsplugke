/* PowerPlug front-end runtime — vanilla, dependency-free, deferred. */
(function () {
  'use strict';

  // Dark/light toggle with persistence.
  var KEY = 'pp-theme';
  var root = document.documentElement;
  try {
    var saved = localStorage.getItem(KEY);
    if (saved) { root.setAttribute('data-theme', saved); }
  } catch (e) {}

  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('[data-pp-theme-toggle]');
    if (!t) return;
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem(KEY, next); } catch (e) {}
  });

  // Sticky add-to-cart: reveal once the main add-to-cart scrolls off screen.
  var sticky = document.querySelector('.pp-sticky-atc');
  var mainBtn = document.querySelector('form.cart button[type="submit"], form.cart .single_add_to_cart_button');
  if (sticky && mainBtn && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      sticky.classList.toggle('is-visible', !entries[0].isIntersecting);
    }, { threshold: 0 });
    io.observe(mainBtn);
    sticky.addEventListener('click', function (ev) {
      if (ev.target.closest('[data-add-to-cart]')) { ev.preventDefault(); mainBtn.click(); }
    });
  }

  // Smooth back-to-top respecting reduced motion.
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('.pp-backtotop');
    if (!b) return;
    ev.preventDefault();
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
  });
})();


/* ===== v2.1 header: AJAX search, mini-cart drawer, mobile nav, sticky ===== */
(function () {
  'use strict';
  var cfg = window.PowerPlug || {};

  var sticky = document.querySelector('.pp-header__sticky');
  var topbar = document.querySelector('.pp-topbar');
  if (sticky) {
    var threshold = topbar ? topbar.offsetHeight : 0;
    var onScroll = function () { sticky.classList.toggle('is-stuck', window.scrollY > threshold); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('[data-pp-nav-toggle]');
    if (t === null) {
      var openNav = document.querySelector('.pp-primary.is-open');
      if (openNav === null) { return; }
      if (ev.target.closest('.pp-primary') === null) {
        openNav.classList.remove('is-open');
        document.body.classList.remove('pp-nav-open');
        var tg = document.querySelector('[data-pp-nav-toggle][aria-expanded="true"]');
        if (tg) { tg.setAttribute('aria-expanded', 'false'); }
      }
      return;
    }
    var nav = document.querySelector('.pp-primary');
    if (nav === null) { return; }
    var open = nav.classList.toggle('is-open');
    t.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('pp-nav-open', open);
  });

  var drawer = document.querySelector('[data-pp-minicart]');
  function openCart() { if (drawer) { drawer.hidden = false; document.body.classList.add('pp-minicart-open'); } }
  function closeCart() { if (drawer) { drawer.hidden = true; document.body.classList.remove('pp-minicart-open'); } }
  document.addEventListener('click', function (ev) {
    if (ev.target.closest('[data-pp-minicart-open]')) { ev.preventDefault(); openCart(); }
    else if (ev.target.closest('[data-pp-minicart-close]')) { ev.preventDefault(); closeCart(); }
  });
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { closeCart(); } });
  if (window.jQuery) { window.jQuery(document.body).on('added_to_cart', function () { openCart(); }); }

  var input = document.querySelector('[data-pp-search-input]');
  var panel = document.querySelector('[data-pp-search-panel]');
  if (input && panel && cfg.ajaxUrl) {
    var RKEY = 'pp-recent-searches';
    var timer = null;
    var getRecent = function () { try { return JSON.parse(localStorage.getItem(RKEY) || '[]'); } catch (e) { return []; } };
    var pushRecent = function (q) {
      try {
        var list = getRecent().filter(function (x) { return x !== q; });
        list.unshift(q); list = list.slice(0, 5);
        localStorage.setItem(RKEY, JSON.stringify(list));
      } catch (e) {}
    };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = (s === null || s === undefined) ? '' : String(s); return d.innerHTML; };
    var hide = function () { panel.hidden = true; input.setAttribute('aria-expanded', 'false'); };
    var show = function () { panel.hidden = false; input.setAttribute('aria-expanded', 'true'); };

    var renderRecent = function () {
      var recent = getRecent();
      var popular = ['Solar Panels', 'Power Tools', 'Generators', 'Inverters', 'Batteries'];
      var html = '';
      if (recent.length) {
        html += '<div class="pp-sr-group__title">Recent searches</div><div class="pp-sr-cats">';
        recent.forEach(function (q) { html += '<a class="pp-sr-chip" href="#" data-pp-term="' + esc(q) + '">' + esc(q) + '</a>'; });
        html += '</div>';
      }
      html += '<div class="pp-sr-group__title">Popular</div><div class="pp-sr-cats">';
      popular.forEach(function (q) { html += '<a class="pp-sr-chip" href="#" data-pp-term="' + esc(q) + '">' + esc(q) + '</a>'; });
      html += '</div>';
      panel.innerHTML = html; show();
    };

    var render = function (data) {
      var p = (data && data.products) || [];
      var c = (data && data.categories) || [];
      if (p.length === 0 && c.length === 0) { panel.innerHTML = '<div class="pp-sr-empty">No matches. Press Enter to search everything.</div>'; show(); return; }
      var html = '';
      if (c.length) {
        html += '<div class="pp-sr-group__title">Categories</div><div class="pp-sr-cats">';
        c.forEach(function (cat) { html += '<a class="pp-sr-chip" href="' + esc(cat.url) + '">' + esc(cat.name) + ' (' + esc(cat.count) + ')</a>'; });
        html += '</div>';
      }
      if (p.length) {
        html += '<div class="pp-sr-group__title">Products</div>';
        p.forEach(function (pr) {
          html += '<a class="pp-sr-item" href="' + esc(pr.url) + '"><img class="pp-sr-item__img" src="' + esc(pr.img) + '" alt="" loading="lazy" /><span class="pp-sr-item__t">' + esc(pr.title) + '</span><span class="pp-sr-item__p">' + esc(pr.price) + '</span></a>';
        });
      }
      panel.innerHTML = html; show();
    };

    var doSearch = function (q) {
      var url = cfg.ajaxUrl + '?action=pp_search&nonce=' + encodeURIComponent(cfg.nonce || '') + '&q=' + encodeURIComponent(q);
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) { render(res && res.data ? res.data : {}); })
        .catch(function () {});
    };

    input.addEventListener('input', function () {
      var q = input.value.trim();
      if (timer) { clearTimeout(timer); }
      if (q.length < 2) { renderRecent(); return; }
      timer = setTimeout(function () { doSearch(q); }, 250);
    });
    input.addEventListener('focus', function () { if (input.value.trim().length < 2) { renderRecent(); } });
    var form = input.closest('form');
    if (form) { form.addEventListener('submit', function () { var q = input.value.trim(); if (q) { pushRecent(q); } }); }
    panel.addEventListener('click', function (ev) {
      var chip = ev.target.closest('[data-pp-term]');
      if (chip) { ev.preventDefault(); input.value = chip.getAttribute('data-pp-term'); input.focus(); var q = input.value.trim(); if (q.length >= 2) { doSearch(q); } }
    });
    document.addEventListener('click', function (ev) { if (ev.target.closest('.pp-search') === null) { hide(); } });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { hide(); } });
  }
})();


/* ===== v2.2 hero slider: arrows, dots, keyboard, touch, autoplay ===== */
(function () {
  'use strict';
  var box = document.querySelector('[data-pp-slider]');
  if (box === null) { return; }
  var slides = box.querySelectorAll('.pp-heroslider__slide');
  if (slides.length < 1) { return; }
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var idx = 0;
  var timer = null;
  var dotsWrap = box.querySelector('[data-pp-slide-dots]');
  var dots = [];

  function go(n) {
    slides[idx].classList.remove('is-on');
    if (dots[idx]) { dots[idx].setAttribute('aria-current', 'false'); }
    idx = (n + slides.length) % slides.length;
    slides[idx].classList.add('is-on');
    if (dots[idx]) { dots[idx].setAttribute('aria-current', 'true'); }
  }
  function next() { go(idx + 1); }
  function prev() { go(idx - 1); }
  function stop() { if (timer) { clearInterval(timer); timer = null; } }
  function start() { if (reduce === false && slides.length > 1) { timer = setInterval(next, 6000); } }
  function reset() { stop(); start(); }

  if (dotsWrap && slides.length > 1) {
    for (var d = 0; d < slides.length; d++) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'pp-heroslider__dot';
      b.setAttribute('aria-label', 'Go to slide ' + (d + 1));
      (function (n) { b.addEventListener('click', function () { go(n); reset(); }); })(d);
      dotsWrap.appendChild(b);
      dots.push(b);
    }
  }

  slides[0].classList.add('is-on');
  if (dots[0]) { dots[0].setAttribute('aria-current', 'true'); }

  var pn = box.querySelector('[data-pp-slide-next]');
  var pp = box.querySelector('[data-pp-slide-prev]');
  if (pn) { pn.addEventListener('click', function () { next(); reset(); }); }
  if (pp) { pp.addEventListener('click', function () { prev(); reset(); }); }

  box.addEventListener('mouseenter', stop);
  box.addEventListener('mouseleave', start);
  box.addEventListener('focusin', stop);
  box.addEventListener('focusout', start);
  box.addEventListener('keydown', function (ev) {
    if (ev.key === 'ArrowRight') { next(); reset(); }
    else if (ev.key === 'ArrowLeft') { prev(); reset(); }
  });

  var x0 = null;
  box.addEventListener('touchstart', function (ev) { x0 = ev.touches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', function (ev) {
    if (x0 === null) { return; }
    var dx = ev.changedTouches[0].clientX - x0;
    if (Math.abs(dx) > 40) { if (dx < 0) { next(); } else { prev(); } reset(); }
    x0 = null;
  }, { passive: true });

  start();
})();


/* ===== v2.4 featured category horizontal scroll ===== */
(function () {
  'use strict';
  var box = document.querySelector('[data-pp-catscroll]');
  if (box === null) { return; }
  var track = box.querySelector('.pp-catscroll__track');
  if (track === null) { return; }
  function step(dir) { track.scrollBy({ left: dir * Math.max(240, track.clientWidth * 0.8), behavior: 'smooth' }); }
  var prev = document.querySelector('[data-pp-cats-prev]');
  var next = document.querySelector('[data-pp-cats-next]');
  if (prev) { prev.addEventListener('click', function () { step(-1); }); }
  if (next) { next.addEventListener('click', function () { step(1); }); }
})();


/* ===== v2.6 nav drawer escape + quick view modal ===== */
(function () {
  'use strict';
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') {
      var n = document.querySelector('.pp-primary.is-open');
      if (n) { n.classList.remove('is-open'); document.body.classList.remove('pp-nav-open'); }
    }
  });
})();

(function () {
  'use strict';
  var cfg = window.PowerPlug || {};
  var modal = null;
  function build() {
    if (modal) { return modal; }
    modal = document.createElement('div');
    modal.className = 'pp-qv';
    modal.hidden = true;
    modal.innerHTML = '<div class="pp-qv__overlay" data-pp-qv-close></div><div class="pp-qv__panel" role="dialog" aria-modal="true"><button class="pp-qv__close" type="button" data-pp-qv-close aria-label="Close">&times;</button><div class="pp-qv__body"></div></div>';
    document.body.appendChild(modal);
    return modal;
  }
  function shut() { if (modal) { modal.hidden = true; document.body.classList.remove('pp-qv-open'); } }
  function openQv(id) {
    if (cfg.ajaxUrl) {
      var m = build();
      var body = m.querySelector('.pp-qv__body');
      body.innerHTML = '<div class="pp-qv__loading">Loading...</div>';
      m.hidden = false;
      document.body.classList.add('pp-qv-open');
      fetch(cfg.ajaxUrl + '?action=pp_quickview&id=' + encodeURIComponent(id), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          var ok = res && res.success === true;
          if (ok === false) { body.innerHTML = '<p class="pp-qv__err">Unable to load product.</p>'; return; }
          var d = res.data;
          var buyable = (d.purchasable === true && d.in_stock === true);
          var atc = buyable
            ? '<a href="' + d.add_url + '" class="button pp-qv__atc add_to_cart_button ajax_add_to_cart" data-product_id="' + d.id + '" data-quantity="1" rel="nofollow">' + (d.add_text || 'Add to cart') + '</a>'
            : '<a href="' + d.permalink + '" class="button">' + (d.add_text || 'Read more') + '</a>';
          body.innerHTML = '<div class="pp-qv__grid"><div class="pp-qv__media"><img src="' + d.image + '" alt="" /></div><div class="pp-qv__info"><h2 class="pp-qv__title">' + d.title + '</h2><div class="pp-qv__price">' + (d.price || '') + '</div><div class="pp-qv__desc">' + (d.excerpt || '') + '</div><div class="pp-qv__actions">' + atc + ' <a class="button button-ghost" href="' + d.permalink + '">View details</a></div></div></div>';
        })
        .catch(function () { body.innerHTML = '<p class="pp-qv__err">Unable to load product.</p>'; });
    }
  }
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-pp-quickview]');
    if (b) { ev.preventDefault(); openQv(b.getAttribute('data-pp-quickview')); return; }
    if (ev.target.closest('[data-pp-qv-close]')) { shut(); }
  });
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { shut(); } });
})();


/* ===== v2.8 filter drawer + load more ===== */
(function () {
  'use strict';
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('[data-pp-filters-toggle]');
    var panel = document.querySelector('[data-pp-filters]');
    if (t) {
      if (panel) {
        var open = panel.classList.toggle('is-open');
        document.body.classList.toggle('pp-filters-open', open);
        var tg = document.querySelector('[data-pp-filters-toggle]');
        if (tg) { tg.setAttribute('aria-expanded', open ? 'true' : 'false'); }
      }
      return;
    }
    if (panel && panel.classList.contains('is-open')) {
      if (ev.target.closest('[data-pp-filters]') === null && ev.target.closest('[data-pp-filters-toggle]') === null) {
        panel.classList.remove('is-open');
        document.body.classList.remove('pp-filters-open');
      }
    }
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') {
      var p = document.querySelector('[data-pp-filters].is-open');
      if (p) { p.classList.remove('is-open'); document.body.classList.remove('pp-filters-open'); }
    }
  });
})();

(function () {
  'use strict';
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-pp-loadmore]');
    if (btn === null) { return; }
    ev.preventDefault();
    var next = btn.getAttribute('data-next');
    if (next) {
      btn.classList.add('is-loading');
      var label = btn.textContent;
      btn.textContent = 'Loading...';
      fetch(next, { credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var list = document.querySelector('ul.products');
          var incoming = doc.querySelectorAll('ul.products li.product');
          if (list && incoming.length > 0) {
            incoming.forEach(function (li) { list.appendChild(document.importNode(li, true)); });
          }
          var nb = doc.querySelector('[data-pp-loadmore]');
          btn.classList.remove('is-loading');
          if (nb) { btn.setAttribute('data-next', nb.getAttribute('data-next')); btn.textContent = label; }
          else if (btn.parentNode) { btn.parentNode.removeChild(btn); }
        })
        .catch(function () { btn.classList.remove('is-loading'); btn.textContent = label; });
    }
  });
})();


/* ===== v2.17.0: AJAX add-to-cart on single product pages ===== */
(function () {
  'use strict';
  var cfg = window.PowerPlug || {};
  var jq = window.jQuery;

  function endpoint() {
    return (cfg.wcAjax && cfg.wcAjax.length > 0) ? cfg.wcAjax : '/?wc-ajax=add_to_cart';
  }

  function applyFragments(fragments) {
    if (fragments === undefined || fragments === null) { return; }
    Object.keys(fragments).forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (n) {
        var tmp = document.createElement('div');
        tmp.innerHTML = fragments[sel];
        var repl = tmp.firstElementChild;
        if (repl && n.parentNode) { n.parentNode.replaceChild(repl, n); }
      });
    });
  }

  function submitAjax(form, btn) {
    var pid = '';
    var addBtn = form.querySelector('button[name="add-to-cart"], input[name="add-to-cart"]');
    if (addBtn && addBtn.value) { pid = addBtn.value; }
    if (pid === '') { var dp = form.querySelector('[name="product_id"]'); if (dp && dp.value) { pid = dp.value; } }
    if (form.classList.contains('variations_form') || form.classList.contains('grouped_form')) { return false; }
    if (pid === '') { return false; }

    var data = new FormData(form);
    data.append('product_id', pid);

    if (btn) { btn.classList.add('loading'); }
    fetch(endpoint(), { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (btn) { btn.classList.remove('loading'); btn.classList.add('added'); }
        if (res && res.error === true && res.product_url) { window.location = res.product_url; return; }
        applyFragments(res ? res.fragments : null);
        if (jq) { jq(document.body).trigger('added_to_cart', [res ? res.fragments : {}, res ? res.cart_hash : '', jq(btn || form)]); }
        else {
          var drawer = document.querySelector('[data-pp-minicart]');
          if (drawer) { drawer.hidden = false; document.body.classList.add('pp-minicart-open'); }
        }
      })
      .catch(function () { if (btn) { btn.classList.remove('loading'); } form.submit(); });
    return true;
  }

  document.querySelectorAll('.single-product form.cart').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      var handled = submitAjax(form, form.querySelector('.single_add_to_cart_button'));
      if (handled === true) { ev.preventDefault(); }
    });
  });
})();


/* ===== v2.18.4 homepage category rows: client-side shuffle (cache-friendly variety) ===== */
(function () {
  'use strict';
  var blocks = document.querySelectorAll('[data-pp-shuffle]');
  if (blocks.length === 0) { return; }
  Array.prototype.forEach.call(blocks, function (block) {
    var list = block.querySelector('ul.products');
    if (list === null) { return; }
    var items = Array.prototype.slice.call(list.querySelectorAll('li.product'));
    if (items.length < 2) { block.setAttribute('data-pp-shuffled', '1'); return; }
    var show = parseInt(block.getAttribute('data-pp-show'), 10);
    if (isNaN(show) || show < 1) { show = items.length; }
    for (var i = items.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = items[i]; items[i] = items[j]; items[j] = tmp;
    }
    for (var k = 0; k < items.length; k++) {
      list.appendChild(items[k]);
      items[k].hidden = k >= show;
    }
    block.setAttribute('data-pp-shuffled', '1');
  });
})();
