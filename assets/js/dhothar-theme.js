/**
 * Dhothar theme toggle + sidebar L→R wave hover.
 */
(function () {
  var KEY = "dhothar-theme";
  var WAVE_MS = 560;

  function preferred() {
    try {
      var saved = localStorage.getItem(KEY);
      if (saved === "light" || saved === "dark") return saved;
    } catch (e) {}
    if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
      return "dark";
    }
    return "light";
  }

  function apply(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    try {
      localStorage.setItem(KEY, theme);
    } catch (e) {}
    if (window.Chart && Chart.defaults) {
      Chart.defaults.color = theme === "dark" ? "#a8bdd0" : "#5a6f82";
    }
    var btn = document.getElementById("dh-theme-toggle");
    if (btn) {
      var nextLabel = theme === "dark" ? "Switch to light mode" : "Switch to dark mode";
      btn.setAttribute("aria-label", nextLabel);
      btn.setAttribute("title", nextLabel);
    }
  }

  apply(preferred());

  function bindToggle() {
    var btn = document.getElementById("dh-theme-toggle");
    if (!btn || btn.getAttribute("data-bound")) return;
    btn.setAttribute("data-bound", "1");
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      var next = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
      apply(next);
    });
  }

  /**
   * Wave: hover in L→R (−101%→0), hover out L→R (0→101%).
   * Idle links never keep the wave layer visible (fixes leftover gradient lines).
   */
  function bindSidebarSweep() {
    var menu = document.getElementById("main-menu");
    if (!menu || menu.getAttribute("data-dh-sweep")) return;
    menu.setAttribute("data-dh-sweep", "1");

    function clearTimer(link) {
      if (link._dhWaveTimer) {
        clearTimeout(link._dhWaveTimer);
        link._dhWaveTimer = null;
      }
    }

    function resetWave(link) {
      clearTimer(link);
      link.classList.remove("nav-sweep-in", "nav-sweep-out");
    }

    function onEnter(link) {
      // Active gold pill — no wave (matches DIG)
      var li = link.parentNode;
      if (li && li.classList && li.classList.contains("active") && link === li.children[0]) {
        return;
      }

      clearTimer(link);
      link.classList.remove("nav-sweep-out");
      // Restart animation
      void link.offsetWidth;
      link.classList.add("nav-sweep-in");
    }

    function onLeave(link) {
      clearTimer(link);

      if (!link.classList.contains("nav-sweep-in")) {
        resetWave(link);
        return;
      }

      // Continue wave left → right off the right edge
      link.classList.remove("nav-sweep-in");
      void link.offsetWidth;
      link.classList.add("nav-sweep-out");

      link._dhWaveTimer = setTimeout(function () {
        resetWave(link);
      }, WAVE_MS);
    }

    var links = menu.querySelectorAll("a");
    for (var i = 0; i < links.length; i++) {
      (function (link) {
        link.addEventListener("mouseenter", function () {
          onEnter(link);
        });
        link.addEventListener("mouseleave", function () {
          onLeave(link);
        });
      })(links[i]);
    }
  }

  function init() {
    bindToggle();
    bindSidebarSweep();
    // Parent menu "go to related page" lives in assets/js/sidebar-nav.js
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
