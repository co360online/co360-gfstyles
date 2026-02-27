(function ($) {
  'use strict';

  function initColorPickers(context) {
    $(context || document).find('.gfsk-color').wpColorPicker();
  }

  function togglePanels(ids) {
    const selected = ids || [];
    const $panels = $('.gfsk-panel');
    const $empty = $('#gfsk-empty-state');

    $panels.hide();

    if (!selected.length) {
      $empty.show();
      return;
    }

    $empty.hide();

    selected.forEach((id) => {
      $panels.filter('[data-form-id="' + id + '"]').show();
    });

    initColorPickers('#gfsk-form-panels');
  }

  function initFormFilter() {
    const $filter = $('#gfsk-form-filter');
    if (!$filter.length) {
      return;
    }

    $filter.on('change', function () {
      const selected = $(this).val() || [];
      togglePanels(selected);
    });

    togglePanels([]);
  }

  $(function () {
    initColorPickers(document);
    initFormFilter();
  });
})(jQuery);
