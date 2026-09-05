(function () {
  'use strict';
  function registerFilters() {
    if (!window.wc || !window.wc.blocksCheckout || !window.aituCheckoutTerms) { return; }
    window.wc.blocksCheckout.registerCheckoutFilters('aitu-customer-terms', {
      placeOrderButtonLabel: function () { return window.aituCheckoutTerms.label; },
      totalValue: function (value, extensions, args) {
        var totals = args && args.cart && args.cart.cartTotals;
        // WooPayments adds the ISO code; AITU already uses EUR as the currency symbol.
        return totals && totals.currency_code === totals.currency_symbol &&
          value === '<price/> ' + totals.currency_code ? '<price/>' : value;
      }
    });
  }
  // Payment integrations register their filters later in the footer. Register after those scripts.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerFilters, { once: true });
  } else {
    registerFilters();
  }
}());
