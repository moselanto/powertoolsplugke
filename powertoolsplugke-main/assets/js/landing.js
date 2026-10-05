/**
 * PowerPlug landing funnels (template-lp-category.php / /lp-{category}/).
 * - Reveals .pp-lp-reveal blocks (product cards, stats, benefit cards) as they scroll in.
 * - Counts up .pp-lp-stat__num[data-count] numbers.
 * Framework-free. Content is visible without JS: cards are only hidden once the
 * <html> element carries the pp-lp-js class, which this script adds.
 */
(function () {
  'use strict';

  var root = document.documentElement;
  root.classList.add('pp-lp-js');

  function countUp(el) {
    var target = parseInt(el.getAttribute('data-count'), 10) || 0;
    var suffix = el.getAttribute('data-suffix') || '';
    if (target <= 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      el.textContent = target + suffix;
      return;
    }
    var start = null;
    var dur = 1200;
    function step(ts) {
      if (start === null) { start = ts; }
      var p = Math.min((ts - start) / dur, 1);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))) + suffix;
      if (p < 1) { window.requestAnimationFrame(step); }
    }
    window.requestAnimationFrame(step);
  }

  function show(el) {
    if (el.classList.contains('in')) { return; }
    el.classList.add('in');
    var nums = el.matches('[data-count]') ? [el] : el.querySelectorAll('[data-count]');
    Array.prototype.forEach.call(nums, countUp);
  }

  function init() {
    var items = document.querySelectorAll('.pp-lp-reveal');
    if (!('IntersectionObserver' in window)) {
      Array.prototype.forEach.call(items, show);
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { show(e.target); io.unobserve(e.target); }
      });
    }, { rootMargin: '0px 0px -40px 0px', threshold: 0.05 });
    Array.prototype.forEach.call(items, function (el) { io.observe(el); });

    // Safety net: never leave products hidden if the observer misses them.
    window.setTimeout(function () {
      Array.prototype.forEach.call(document.querySelectorAll('.pp-lp-reveal:not(.in)'), show);
    }, 2500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
