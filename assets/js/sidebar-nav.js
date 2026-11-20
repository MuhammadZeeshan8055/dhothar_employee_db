/**
 * Sidebar navigation helper (beginner friendly)
 *
 * How parent menus work:
 *  1) First click  → Neon opens the submenu
 *  2) Second click (while open) → go to the main related page
 *     Example: Attendance (open) → Mark Attendance page
 */
(function () {
  function setupParentClick() {
    var menu = document.getElementById('main-menu');
    if (!menu) {
      return;
    }

    // Do not bind twice
    if (menu.getAttribute('data-simple-nav') === '1') {
      return;
    }
    menu.setAttribute('data-simple-nav', '1');

    menu.addEventListener('click', function (event) {
      // Find the clicked parent link (Employees, Delivery, Attendance, ...)
      var link = event.target.closest('#main-menu > li.has-sub > a');
      if (!link) {
        return;
      }

      var pageUrl = link.getAttribute('href');
      if (!pageUrl || pageUrl === '#') {
        return;
      }

      var parentItem = link.parentNode;

      // Only go to the page if the submenu is already open
      if (!parentItem.classList.contains('opened')) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      window.location.href = pageUrl;
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupParentClick);
  } else {
    setupParentClick();
  }
})();
