(function () {
  function applyFormClass() {
    var forms = document.querySelectorAll('.gform_wrapper form');

    forms.forEach(function (form) {
      form.classList.add('co360-gfstyles-form');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyFormClass);
  } else {
    applyFormClass();
  }

  document.addEventListener('gform_post_render', applyFormClass);
})();
