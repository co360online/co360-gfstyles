(function ($) {
  'use strict';

  function initColorPickers() {
    $('.gfsk-color').wpColorPicker();
  }

  function initFormFilter() {
    const $filter = $('#gfsk-form-filter');
    if (!$filter.length) {
      return;
    }

    $filter.on('change', function () {
      const selected = $(this).val() || [];
      $('.gfsk-row, .gfsk-panel').hide();

      if (!selected.length) {
        $('.gfsk-row, .gfsk-panel').show();
        return;
      }

      selected.forEach((id) => {
        $('.gfsk-row[data-form-id="' + id + '"]').show();
        $('.gfsk-panel[data-form-id="' + id + '"]').show();
      });
    }).trigger('change');
  }

  $(function () {
    initColorPickers();
    initFormFilter();
  });
})(jQuery);
