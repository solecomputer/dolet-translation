(function () {
  'use strict';

  var disclosures = document.querySelectorAll('.dolet-floating-disclosure');
  if (disclosures.length === 0) {
    return;
  }

  function viewportBounds() {
    var viewport = window.visualViewport;
    if (viewport) {
      return {
        top: viewport.offsetTop,
        bottom: viewport.offsetTop + viewport.height,
        height: viewport.height
      };
    }

    var height = document.documentElement.clientHeight || window.innerHeight;
    return { top: 0, bottom: height, height: height };
  }

  function place(disclosure) {
    if (!disclosure.open) {
      return;
    }

    var summary = disclosure.querySelector('.dolet-floating-summary');
    var menu = disclosure.querySelector('.dolet-floating-menu');
    if (!summary || !menu) {
      return;
    }

    var viewport = viewportBounds();
    var summaryRect = summary.getBoundingClientRect();
    var gap = 8;
    var spaceAbove = Math.max(0, summaryRect.top - viewport.top - gap);
    var spaceBelow = Math.max(0, viewport.bottom - summaryRect.bottom - gap);
    var boundedMenuHeight = Math.min(menu.scrollHeight, viewport.height * 0.6, 384);
    var direction = spaceBelow >= boundedMenuHeight ? 'down' : 'up';
    var available = direction === 'down' ? spaceBelow : spaceAbove;

    disclosure.setAttribute('data-dolet-opening', direction);
    disclosure.style.setProperty('--dolet-menu-space', Math.max(0, available) + 'px');
  }

  function placeOpenDisclosures() {
    for (var index = 0; index < disclosures.length; index += 1) {
      place(disclosures[index]);
    }
  }

  for (var index = 0; index < disclosures.length; index += 1) {
    disclosures[index].addEventListener('toggle', function (event) {
      var disclosure = event.currentTarget;
      if (disclosure && disclosure.open) {
        window.requestAnimationFrame(function () {
          place(disclosure);
        });
      }
    });
  }

  window.addEventListener('resize', placeOpenDisclosures);
  window.addEventListener('orientationchange', placeOpenDisclosures);
  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', placeOpenDisclosures);
    window.visualViewport.addEventListener('scroll', placeOpenDisclosures);
  }
}());
