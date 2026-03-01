(function ($) {
  'use strict';

  function ensureWrapperFlags(formId) {
    const $wrapper = $('#gform_wrapper_' + formId);
    if (!$wrapper.length) {
      return;
    }
    if (!$wrapper.hasClass('gfsk-form')) {
      $wrapper.addClass('gfsk-form');
    }
    $wrapper.attr('data-gfsk', 'on');
    $wrapper.attr('data-gfsk-form', String(formId));
  }

  $(document).on('gform_post_render', function (_event, formId) {
    if (!formId) {
      return;
    }
    ensureWrapperFlags(formId);
  });
})(jQuery);
