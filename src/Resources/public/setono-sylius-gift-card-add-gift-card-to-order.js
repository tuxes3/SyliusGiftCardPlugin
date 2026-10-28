(function () {
  'use strict';

  const formId = 'setono-sylius-gift-card-add-gift-card-to-order';

  // Delegated, so the form keeps working after its block is replaced with the returned errors
  document.addEventListener('submit', async function (event) {
    const form = event.target;

    // Skip submits another script already handles, e.g. an application's own copy of this script
    if (!(form instanceof HTMLFormElement) || form.id !== formId || event.defaultPrevented) {
      return;
    }

    event.preventDefault();

    const response = await fetch(form.dataset.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    if (response.ok) {
      window.location.href = form.dataset.redirect;

      return;
    }

    // A rejected code comes back as 400 with the form and its errors
    const block = form.closest('.setono-sylius-gift-card-gift-card-block');
    if (response.status === 400 && block !== null) {
      block.outerHTML = await response.text();

      return;
    }

    form.submit();
  });
})();
