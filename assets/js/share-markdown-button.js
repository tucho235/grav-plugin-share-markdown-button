(function () {
  'use strict';

  function copyToClipboard(text) {
    // Modern API
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    // Fallback for HTTP or older browsers
    return new Promise(function (resolve, reject) {
      var textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.left = '-9999px';
      textarea.style.top = '-9999px';
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();

      try {
        var ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        ok ? resolve() : reject(new Error('execCommand copy failed'));
      } catch (err) {
        document.body.removeChild(textarea);
        reject(err);
      }
    });
  }

  function initButtons() {
    var buttons = document.querySelectorAll('.smb-button');

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var sourceId = btn.getAttribute('data-smb-source');
        var source   = document.getElementById(sourceId);

        if (!source) return;

        var markdown     = source.value;
        var copiedText   = btn.getAttribute('data-smb-copied-text')   || 'Copied!';
        var originalText = btn.getAttribute('data-smb-original-text') || btn.querySelector('.smb-button-text').textContent;
        var textSpan     = btn.querySelector('.smb-button-text');

        copyToClipboard(markdown)
          .then(function () {
            btn.classList.add('smb-button--copied');
            if (textSpan) textSpan.textContent = copiedText;

            setTimeout(function () {
              btn.classList.remove('smb-button--copied');
              if (textSpan) textSpan.textContent = originalText;
            }, 2000);
          })
          .catch(function (err) {
            console.error('[share-markdown-button] Could not copy text:', err);
          });
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initButtons);
  } else {
    initButtons();
  }
})();
