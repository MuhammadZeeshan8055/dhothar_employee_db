<?php
/**
 * Early theme attribute — prevents light/dark flash.
 * Visual CSS comes from assets/css/custom.css → dhothar-theme.css
 */
?>
<script>
  (function () {
    try {
      var t = localStorage.getItem("dhothar-theme");
      if (t !== "light" && t !== "dark") {
        t = (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) ? "dark" : "light";
      }
      document.documentElement.setAttribute("data-theme", t);
    } catch (e) {
      document.documentElement.setAttribute("data-theme", "light");
    }
  })();
</script>
