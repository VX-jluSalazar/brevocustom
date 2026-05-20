(function () {
  function each(nodes, callback) {
    Array.prototype.forEach.call(nodes, callback);
  }

  function boot() {
    var root = document.querySelector('.brevocustom-admin');
    if (!root || root.getAttribute('data-brevocustom-ready') === '1') {
      return;
    }

    root.setAttribute('data-brevocustom-ready', '1');

    var storageKey = 'brevocustom_active_tab';

    function activate(tabName) {
      var tabs = root.querySelectorAll('[data-brevocustom-tab]');
      var panels = root.querySelectorAll('[data-brevocustom-panel]');

      each(tabs, function (tab) {
        var isActive = tab.getAttribute('data-brevocustom-tab') === tabName;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });

      each(panels, function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-brevocustom-panel') === tabName);
      });

      try {
        window.localStorage.setItem(storageKey, tabName);
      } catch (error) {
        return;
      }
    }

    root.addEventListener('click', function (event) {
      var tab = event.target.closest('[data-brevocustom-tab]');
      var addUrlButton = event.target.closest('[data-brevocustom-add-url]');
      var removeUrlButton = event.target.closest('[data-brevocustom-remove-url]');

      if (tab && root.contains(tab)) {
        event.preventDefault();
        activate(tab.getAttribute('data-brevocustom-tab'));
        return;
      }

      if (addUrlButton && root.contains(addUrlButton)) {
        var customUrlsRoot = root.querySelector('[data-brevocustom-custom-urls]');
        var list = root.querySelector('[data-brevocustom-custom-url-list]');
        var template = root.querySelector('[data-brevocustom-custom-url-template]');

        if (customUrlsRoot && list && template && template.content) {
          event.preventDefault();
          list.appendChild(template.content.cloneNode(true));
        }
        return;
      }

      if (removeUrlButton && root.contains(removeUrlButton)) {
        var row = removeUrlButton.closest('[data-brevocustom-custom-url-row]');
        if (row) {
          event.preventDefault();
          row.parentNode.removeChild(row);
        }
      }
    });

    try {
      var remembered = window.localStorage.getItem(storageKey);
      if (remembered && root.querySelector('[data-brevocustom-tab="' + remembered + '"]')) {
        activate(remembered);
      } else {
        activate('overview');
      }
    } catch (error) {
      activate('overview');
      return;
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
