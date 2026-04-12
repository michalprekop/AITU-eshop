(function ($) {
  "use strict";

  function initNavbarSearchOverlay() {
    var $toggle = $(".aitu-navbar-search-toggle");
    var $overlay = $(".aitu-search-overlay");

    if (!$toggle.length || !$overlay.length) {
      return;
    }

    var $dialog = $overlay.find(".aitu-search-overlay-inner").first();
    var $close = $overlay.find(".aitu-search-close").first();
    var $form = $overlay.find(".aitu-search-form").first();
    var $input = $overlay.find(".aitu-search-input").first();

    function isOpen() {
      return $overlay.hasClass("is-open");
    }

    function openOverlay() {
      $overlay.addClass("is-open").attr("aria-hidden", "false");
      $("body").addClass("aitu-search-open");
      window.setTimeout(function () {
        if ($input.length) {
          $input.trigger("focus");
        }
      }, 20);
    }

    function closeOverlay() {
      $overlay.removeClass("is-open").attr("aria-hidden", "true");
      $("body").removeClass("aitu-search-open");
    }

    $toggle
      .off("click.aituSearchOverlay")
      .on("click.aituSearchOverlay", function (event) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
          return;
        }
        event.preventDefault();
        openOverlay();
      });

    $close
      .off("click.aituSearchOverlay")
      .on("click.aituSearchOverlay", function (event) {
        event.preventDefault();
        closeOverlay();
      });

    $overlay
      .off("click.aituSearchOverlay")
      .on("click.aituSearchOverlay", function (event) {
        if (event.target === this) {
          closeOverlay();
        }
      });

    $dialog
      .off("click.aituSearchOverlay")
      .on("click.aituSearchOverlay", function (event) {
        event.stopPropagation();
      });

    $form
      .off("submit.aituSearchOverlay")
      .on("submit.aituSearchOverlay", function () {
        var term = $.trim($input.val());
        if (!term) {
          if ($input.length) {
            $input.trigger("focus");
          }
          return false;
        }
        return true;
      });

    $(document)
      .off("keydown.aituSearchOverlay")
      .on("keydown.aituSearchOverlay", function (event) {
        if (!isOpen()) {
          return;
        }
        if (event.key === "Escape") {
          event.preventDefault();
          closeOverlay();
        }
      });
  }

  function syncActive($swatches, value) {
    var normalizedValue = value == null ? "" : String(value);
    $swatches.find(".aitu-size-swatch").removeClass("is-active");
    $swatches.find(".aitu-size-swatch").each(function () {
      if (String($(this).attr("data-value") || "") === normalizedValue) {
        $(this).addClass("is-active");
      }
    });
  }

  function getStaticAvailabilityMap($form, $select) {
    var map = {};
    var variations = $form.data("product_variations");
    var attributeName = $select.attr("name");

    if (!Array.isArray(variations) || !attributeName) {
      return map;
    }

    variations.forEach(function (variation) {
      if (!variation || typeof variation !== "object" || !variation.attributes) {
        return;
      }

      var value = variation.attributes[attributeName];
      if (!value) {
        return;
      }

      var inStock = variation.is_in_stock !== false;
      var purchasable = variation.is_purchasable !== false;
      var active = variation.variation_is_active !== false;
      var available = inStock && purchasable && active;

      if (!Object.prototype.hasOwnProperty.call(map, value)) {
        map[value] = available;
      } else {
        map[value] = map[value] || available;
      }
    });

    return map;
  }

  function getRealVariantValues($form, $select) {
    var values = {};
    var variations = $form.data("product_variations");
    var attributeName = $select.attr("name");

    if (!Array.isArray(variations) || !attributeName) {
      return values;
    }

    variations.forEach(function (variation) {
      if (!variation || typeof variation !== "object" || !variation.attributes) {
        return;
      }

      var value = variation.attributes[attributeName];
      if (!value) {
        return;
      }

      values[String(value)] = true;
    });

    return values;
  }

  function syncDisabled($swatches, availabilityMap) {
    $swatches.find(".aitu-size-swatch").each(function () {
      var $swatch = $(this);
      var value = $swatch.data("value");
      var isDisabled = false;

      if (
        Object.prototype.hasOwnProperty.call(availabilityMap, value) &&
        !availabilityMap[value]
      ) {
        isDisabled = true;
      }

      $swatch
        .toggleClass("is-disabled", isDisabled)
        .prop("disabled", isDisabled)
        .attr("aria-disabled", isDisabled ? "true" : "false");
    });
  }

  function buildSizeSwatches($form) {
    if (!$form.length || $form.data("aituSwatchesReady")) {
      return;
    }

    var $select = $form.find('select[name*="size"], select[name*="pa_size"]').first();
    if (!$select.length) {
      $select = $form.find(".variations select").first();
    }
    if (!$select.length) {
      return;
    }

    var $swatches = $('<div class="aitu-size-swatches" aria-label="Size options"></div>');
    var availabilityMap = getStaticAvailabilityMap($form, $select);
    var realVariantValues = getRealVariantValues($form, $select);
    var hasRealVariantValues = Object.keys(realVariantValues).length > 0;

    function getOptionByValue(value) {
      return $select
        .find("option")
        .filter(function () {
          return String($(this).val() || "") === String(value || "");
        })
        .first();
    }

    function triggerVariationSync() {
      if ($select.length && $select[0]) {
        var nativeChangeEvent = new Event("change", { bubbles: true });
        $select[0].dispatchEvent(nativeChangeEvent);
      } else {
        $select.trigger("change");
      }
      $form.trigger("woocommerce_variation_select_change");
      $form.trigger("check_variations");
    }

    function syncDisabledState() {
      $swatches.find(".aitu-size-swatch").each(function () {
        var $swatch = $(this);
        var value = $swatch.attr("data-value");
        var $option = getOptionByValue(value);
        var isDisabled = !$option.length || $option.is(":disabled");

        if (
          Object.prototype.hasOwnProperty.call(availabilityMap, value) &&
          !availabilityMap[value]
        ) {
          isDisabled = true;
        }

        $swatch
          .toggleClass("is-disabled", isDisabled)
          .prop("disabled", isDisabled)
          .attr("aria-disabled", isDisabled ? "true" : "false");
      });
    }

    function getFirstAvailableValue() {
      var firstValue = "";
      $swatches.find(".aitu-size-swatch").each(function () {
        var $swatch = $(this);
        if (!$swatch.prop("disabled") && !$swatch.hasClass("is-disabled")) {
          firstValue = $swatch.attr("data-value");
          return false;
        }
      });
      return firstValue;
    }
    $select.find("option").each(function () {
      var value = String($(this).attr("value") || "").trim();
      if (!value) {
        return;
      }
      if (
        hasRealVariantValues &&
        !Object.prototype.hasOwnProperty.call(realVariantValues, value)
      ) {
        return;
      }

      var $swatch = $('<button type="button" class="aitu-size-swatch"></button>');
      $swatch.text($(this).text().trim()).attr("data-value", value);
      if ($(this).is(":disabled")) {
        $swatch.addClass("is-disabled").prop("disabled", true);
      }
      $swatches.append($swatch);
    });

    if (!$swatches.children().length) {
      return;
    }

    var $sizeRow = $select.closest("tr");
    if ($sizeRow.length) {
      $sizeRow.addClass("aitu-hidden-variation");
    }
    $form.find(".reset_variations").hide();

    var $label = $form.closest(".aitu-add-to-cart-wrap").prev(".aitu-size-label");
    if ($label.length) {
      $swatches.insertAfter($label);
    } else {
      $swatches.prependTo($form);
    }

    $swatches.on("click", ".aitu-size-swatch", function (event) {
      event.preventDefault();
      event.stopPropagation();

      if ($(this).prop("disabled") || $(this).hasClass("is-disabled")) {
        return;
      }

      var selectedValue = $(this).attr("data-value");
      syncActive($swatches, selectedValue);

      if ($select.val() !== selectedValue) {
        $select.val(selectedValue);
        triggerVariationSync();
      }
    });

    $select.on("change.aituSwatches woocommerce_variation_has_changed.aituSwatches", function () {
      syncActive($swatches, $select.val());
    });

    $form.on("woocommerce_update_variation_values found_variation reset_data", function () {
      syncDisabledState();
      syncActive($swatches, $select.val());
    });

    syncDisabledState();
    syncActive($swatches, $select.val());

    if (!$select.val()) {
      var firstAvailableValue = getFirstAvailableValue();
      if (firstAvailableValue) {
        $select.val(firstAvailableValue);
        triggerVariationSync();
        syncActive($swatches, firstAvailableValue);
      }
    }

    $form.data("aituSwatchesReady", true);
  }

  function initProductLightbox() {
    var $singlePage = $(".aitu-single-product-page");
    if (!$singlePage.length || $singlePage.data("aituLightboxReady")) {
      return;
    }

    var images = [];
    var sourceToIndex = {};
    var $triggers = $();

    function addImage(source, alt) {
      if (!source) {
        return -1;
      }
      if (Object.prototype.hasOwnProperty.call(sourceToIndex, source)) {
        return sourceToIndex[source];
      }
      var index = images.length;
      sourceToIndex[source] = index;
      images.push({ src: source, alt: alt || "" });
      return index;
    }

    var $mainImage = $(".aitu-product-main-image img").first();
    if ($mainImage.length) {
      var mainIndex = addImage($mainImage.attr("src"), $mainImage.attr("alt"));
      if (mainIndex >= 0) {
        $mainImage.attr("data-aitu-lightbox-index", mainIndex);
        $triggers = $triggers.add($mainImage);
      }
    }

    $(".aitu-product-gallery-item img").each(function () {
      var $img = $(this);
      var imageIndex = addImage($img.attr("src"), $img.attr("alt"));
      if (imageIndex >= 0) {
        $img.attr("data-aitu-lightbox-index", imageIndex);
        $triggers = $triggers.add($img);
      }
    });

    if (!images.length) {
      $singlePage.data("aituLightboxReady", true);
      return;
    }

    var $overlay = $(
      '<div class="aitu-lightbox" aria-hidden="true">' +
        '<button type="button" class="aitu-lightbox-close" aria-label="Close gallery">X</button>' +
        '<button type="button" class="aitu-lightbox-nav aitu-lightbox-nav--prev" aria-label="Previous image">&lt;</button>' +
        '<div class="aitu-lightbox-stage"><img src="" alt="" class="aitu-lightbox-image"></div>' +
        '<button type="button" class="aitu-lightbox-nav aitu-lightbox-nav--next" aria-label="Next image">&gt;</button>' +
        '<div class="aitu-lightbox-thumbs"></div>' +
      "</div>"
    );

    $("body").append($overlay);

    var $lightboxImage = $overlay.find(".aitu-lightbox-image");
    var $thumbsWrap = $overlay.find(".aitu-lightbox-thumbs");
    var currentIndex = 0;
    var $plusCursor = $(".aitu-plus-cursor");

    function showPlusCursor() {
      $("body").addClass("aitu-plus-cursor-active");
    }

    function hidePlusCursor() {
      $("body").removeClass("aitu-plus-cursor-active");
    }

    function movePlusCursor(event) {
      if (!$plusCursor.length || typeof event.clientX !== "number" || typeof event.clientY !== "number") {
        return;
      }
      $plusCursor.css({
        left: event.clientX + "px",
        top: event.clientY + "px"
      });
    }

    function initPlusCursor() {
      if (window.matchMedia && window.matchMedia("(hover: none), (pointer: coarse)").matches) {
        return;
      }

      if (!$plusCursor.length) {
        $plusCursor = $('<div class="aitu-plus-cursor" aria-hidden="true"></div>');
        $("body").append($plusCursor);
      }

      $(document)
        .off("mouseenter.aituPlusCursor", ".aitu-lightbox-trigger")
        .on("mouseenter.aituPlusCursor", ".aitu-lightbox-trigger", function (event) {
          showPlusCursor();
          movePlusCursor(event);
        })
        .off("mousemove.aituPlusCursor", ".aitu-lightbox-trigger")
        .on("mousemove.aituPlusCursor", ".aitu-lightbox-trigger", function (event) {
          showPlusCursor();
          movePlusCursor(event);
        })
        .off("mouseleave.aituPlusCursor", ".aitu-lightbox-trigger")
        .on("mouseleave.aituPlusCursor", ".aitu-lightbox-trigger", function () {
          hidePlusCursor();
        })
        .off("mouseleave.aituPlusCursorDocument")
        .on("mouseleave.aituPlusCursorDocument", function () {
          hidePlusCursor();
        })
        .off("scroll.aituPlusCursor resize.aituPlusCursor")
        .on("scroll.aituPlusCursor resize.aituPlusCursor", function () {
          hidePlusCursor();
        });

    }

    function renderThumbs() {
      var thumbsHtml = "";
      images.forEach(function (image, index) {
        thumbsHtml +=
          '<button type="button" class="aitu-lightbox-thumb" data-index="' +
          index +
          '" aria-label="Open image ' +
          (index + 1) +
          '">' +
          '<img src="' +
          image.src +
          '" alt="' +
          (image.alt || "") +
          '">' +
          "</button>";
      });
      $thumbsWrap.html(thumbsHtml);
    }

    function setCurrent(index) {
      if (!images.length) {
        return;
      }

      if (index < 0) {
        currentIndex = images.length - 1;
      } else if (index >= images.length) {
        currentIndex = 0;
      } else {
        currentIndex = index;
      }

      var current = images[currentIndex];
      $lightboxImage.attr("src", current.src);
      $lightboxImage.attr("alt", current.alt || "");

      $thumbsWrap.find(".aitu-lightbox-thumb").removeClass("is-active");
      $thumbsWrap
        .find('.aitu-lightbox-thumb[data-index="' + currentIndex + '"]')
        .addClass("is-active");
    }

    function openLightbox(index) {
      hidePlusCursor();
      setCurrent(index);
      $overlay.addClass("is-open").attr("aria-hidden", "false");
      $("body").addClass("aitu-lightbox-open");
    }

    function closeLightbox() {
      $overlay.removeClass("is-open").attr("aria-hidden", "true");
      $("body").removeClass("aitu-lightbox-open");
    }

    function isOpen() {
      return $overlay.hasClass("is-open");
    }

    renderThumbs();
    setCurrent(0);

    $triggers.addClass("aitu-lightbox-trigger").on("click", function (event) {
      event.preventDefault();
      var index = parseInt($(this).attr("data-aitu-lightbox-index"), 10);
      if (Number.isNaN(index)) {
        index = 0;
      }
      openLightbox(index);
    });

    $overlay.on("click", ".aitu-lightbox-close", function () {
      closeLightbox();
    });

    $overlay.on("click", ".aitu-lightbox-nav--prev", function () {
      setCurrent(currentIndex - 1);
    });

    $overlay.on("click", ".aitu-lightbox-nav--next", function () {
      setCurrent(currentIndex + 1);
    });

    $overlay.on("click", ".aitu-lightbox-thumb", function () {
      var index = parseInt($(this).attr("data-index"), 10);
      if (!Number.isNaN(index)) {
        setCurrent(index);
      }
    });

    $overlay.on("click", function (event) {
      if (event.target === this) {
        closeLightbox();
      }
    });

    $(document)
      .off("keydown.aituLightbox")
      .on("keydown.aituLightbox", function (event) {
        if (!isOpen()) {
          return;
        }
        if (event.key === "Escape") {
          closeLightbox();
        } else if (event.key === "ArrowLeft") {
          setCurrent(currentIndex - 1);
        } else if (event.key === "ArrowRight") {
          setCurrent(currentIndex + 1);
        }
      });

    try {
      initPlusCursor();
    } catch (error) {
      hidePlusCursor();
    }

    $singlePage.data("aituLightboxReady", true);
  }

  function initSingleProductUI() {
    var addToBasketLabel = "add to basket";
    if (window.aituThemeI18n && typeof window.aituThemeI18n.addToBasket === "string" && window.aituThemeI18n.addToBasket.length) {
      addToBasketLabel = window.aituThemeI18n.addToBasket;
    }

    $(".aitu-product-info form.variations_form").each(function () {
      buildSizeSwatches($(this));
    });

    $(".aitu-product-info .single_add_to_cart_button").each(function () {
      $(this).text(addToBasketLabel);
    });

    initProductLightbox();
  }

  function initAddToCartToast() {
    var addedToCartLabel = "added to basket";
    var viewCartLabel = "View cart →";
    var cartUrl = "/cart";
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.addedToCart === "string" &&
      window.aituThemeI18n.addedToCart.length
    ) {
      addedToCartLabel = window.aituThemeI18n.addedToCart;
    }
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.viewCart === "string" &&
      window.aituThemeI18n.viewCart.length
    ) {
      viewCartLabel = window.aituThemeI18n.viewCart;
    }
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.cartUrl === "string" &&
      window.aituThemeI18n.cartUrl.length
    ) {
      cartUrl = window.aituThemeI18n.cartUrl;
    }

    var $toast = $(".aitu-cart-toast");
    if (!$toast.length) {
      $toast = $(
        '<div class="aitu-cart-toast" role="status" aria-live="polite">' +
          '<div class="aitu-cart-toast__message"></div>' +
          '<a class="aitu-cart-toast__action" href="#"></a>' +
        "</div>"
      );
      $("body").append($toast);
    }
    var $message = $toast.find(".aitu-cart-toast__message");
    var $action = $toast.find(".aitu-cart-toast__action");

    var hideTimeout = null;

    function showToast(message) {
      var text = typeof message === "string" ? $.trim(message) : "";
      if (!text) {
        text = addedToCartLabel;
      }

      if (hideTimeout) {
        window.clearTimeout(hideTimeout);
      }

      $message.text(text);

      if (cartUrl) {
        $action.attr("href", cartUrl).text(viewCartLabel).show();
      } else {
        $action.hide();
      }

      $toast.addClass("is-visible");

      hideTimeout = window.setTimeout(function () {
        $toast.removeClass("is-visible");
      }, 4500);
    }

    if ($(".aitu-woocommerce-notices .woocommerce-message").length) {
      showToast(addedToCartLabel);
      $(".aitu-woocommerce-notices").empty();
    }

    $(document.body)
      .off("added_to_cart.aituToast")
      .on("added_to_cart.aituToast", function () {
        showToast(addedToCartLabel);
      });

    // Optimistic toast for instant UI feedback (especially in Safari),
    // then Woo event keeps behavior consistent after AJAX add-to-cart completes.
    $(document)
      .off("click.aituToastImmediate", ".single_add_to_cart_button, .add_to_cart_button")
      .on("click.aituToastImmediate", ".single_add_to_cart_button, .add_to_cart_button", function () {
        var $button = $(this);
        if (
          $button.prop("disabled") ||
          $button.hasClass("disabled") ||
          $button.hasClass("loading") ||
          $button.attr("aria-disabled") === "true"
        ) {
          return;
        }
        showToast(addedToCartLabel);
      });
  }

  function initCartTotalLabel() {
    var customTotalLabel = "TOTAL:";
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.cartTotalLabel === "string" &&
      window.aituThemeI18n.cartTotalLabel.length
    ) {
      customTotalLabel = window.aituThemeI18n.cartTotalLabel;
    }

    function applyLabel(context) {
      var $scope = context ? $(context) : $(document);
      $scope
        .find(".wc-block-components-totals-footer-item .wc-block-components-totals-item__label")
        .each(function () {
          var $label = $(this);
          if ($.trim($label.text()) !== customTotalLabel) {
            $label.text(customTotalLabel);
          }
        });
    }

    applyLabel(document);

    var observerTarget = document.querySelector(
      ".wc-block-cart, .wp-block-woocommerce-cart, .wc-block-components-sidebar"
    );
    if (!observerTarget || typeof MutationObserver === "undefined") {
      return;
    }

    var observer = new MutationObserver(function () {
      applyLabel(observerTarget);
    });

    observer.observe(observerTarget, {
      childList: true,
      subtree: true,
      characterData: true
    });
  }

  function initCartHeadingLabel() {
    var cartHeadingLabel = "CART";
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.cartHeadingLabel === "string" &&
      window.aituThemeI18n.cartHeadingLabel.length
    ) {
      cartHeadingLabel = window.aituThemeI18n.cartHeadingLabel;
    }

    var headingPattern = /^(cart|košík)$/i;

    function applyLabel(context) {
      var $scope = context ? $(context) : $(document);

      $scope
        .find(
          ".wp-block-woocommerce-cart .wc-block-cart__title, .wp-block-woocommerce-cart h1, .wp-block-woocommerce-cart .wc-block-components-title"
        )
        .each(function () {
          var $heading = $(this);
          var currentText = $.trim($heading.text());
          if (!currentText || !headingPattern.test(currentText)) {
            return;
          }
          if (currentText !== cartHeadingLabel) {
            $heading.text(cartHeadingLabel);
          }
        });
    }

    applyLabel(document);

    var observerTarget = document.querySelector(
      ".wp-block-woocommerce-cart, .woocommerce-cart"
    );
    if (!observerTarget || typeof MutationObserver === "undefined") {
      return;
    }

    var observer = new MutationObserver(function () {
      applyLabel(observerTarget);
    });

    observer.observe(observerTarget, {
      childList: true,
      subtree: true,
      characterData: true
    });
  }

  function initCartEmptyLabel() {
    var cartEmptyLabel = "";
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.cartEmptyLabel === "string" &&
      window.aituThemeI18n.cartEmptyLabel.length
    ) {
      cartEmptyLabel = window.aituThemeI18n.cartEmptyLabel;
    }

    if (!cartEmptyLabel) {
      return;
    }

    var emptyPattern = /^(your cart is currently empty!?|váš košík je momentálne prázdny!?)$/i;

    function applyLabel(context) {
      var $scope = context ? $(context) : $(document);

      $scope
        .find(".wc-block-cart__empty-cart__title, .cart-empty, .wc-empty-cart-message")
        .each(function () {
          var $el = $(this);
          var currentText = $.trim($el.text()).replace(/\s+/g, " ");
          if (!currentText || !emptyPattern.test(currentText)) {
            return;
          }
          if (currentText !== cartEmptyLabel) {
            $el.text(cartEmptyLabel);
          }
        });
    }

    applyLabel(document);

    var observerTarget = document.querySelector(
      ".wp-block-woocommerce-cart, .wc-block-cart, .woocommerce-cart"
    );
    if (!observerTarget || typeof MutationObserver === "undefined") {
      return;
    }

    var observer = new MutationObserver(function () {
      applyLabel(observerTarget);
    });

    observer.observe(observerTarget, {
      childList: true,
      subtree: true,
      characterData: true
    });
  }

  function initCartCheckoutUrlLocalization() {
    var checkoutUrl = "";
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.checkoutUrl === "string" &&
      window.aituThemeI18n.checkoutUrl.length
    ) {
      checkoutUrl = window.aituThemeI18n.checkoutUrl;
    }

    if (!checkoutUrl) {
      return;
    }

    function applyTargetLinks(context) {
      var $scope = context ? $(context) : $(document);

      $scope
        .find(
          ".wc-proceed-to-checkout a[href], .wp-block-woocommerce-cart a[href], .wc-block-cart a[href], .wc-block-components-sidebar a[href]"
        )
        .each(function () {
          var $link = $(this);
          var href = $link.attr("href");

          if (!href || href.indexOf("/checkout") === -1) {
            return;
          }

          if (href.indexOf("/order-received/") !== -1 || href.indexOf("/order-pay/") !== -1) {
            return;
          }

          if (href !== checkoutUrl) {
            $link.attr("href", checkoutUrl);
          }
        });

      $("link[rel='prerender'][href*='/checkout']").attr("href", checkoutUrl);
    }

    applyTargetLinks(document);

    var observerTarget = document.querySelector(
      ".wp-block-woocommerce-cart, .wc-block-cart, .woocommerce-cart, .wc-block-components-sidebar"
    );
    if (!observerTarget || typeof MutationObserver === "undefined") {
      return;
    }

    var observer = new MutationObserver(function () {
      applyTargetLinks(observerTarget);
    });

    observer.observe(observerTarget, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ["href"]
    });
  }

  function initCartNewInStoreDedup() {
    if (!$(".wp-block-woocommerce-cart, .wc-block-cart, .woocommerce-cart").length) {
      return;
    }

    var titlePattern = /^(new in store|novinky v obchode)$/i;

    function getClosestSection($heading) {
      var $section = $heading.closest(
        ".aitu-related-section, .wp-block-woocommerce-cart-cross-sells-block, .wc-block-cart__cross-sells, .wp-block-woocommerce-product-collection, .wp-block-group, section, div"
      );
      if (!$section.length) {
        return $();
      }
      return $section.first();
    }

    function dedupSections(context) {
      var $scope = context ? $(context) : $(document);

      var sections = [];
      $scope
        .find(".aitu-default-content h2, .aitu-default-content h3, .aitu-default-content .wc-block-components-title")
        .each(function () {
          var $heading = $(this);
          var title = $.trim($heading.text()).replace(/\s+/g, " ");
          if (!titlePattern.test(title)) {
            return;
          }

          var $section = getClosestSection($heading);
          if (!$section.length) {
            return;
          }
          if ($section.closest(".aitu-cart-related-section").length && !$section.hasClass("aitu-cart-related-section")) {
            $section = $section.closest(".aitu-cart-related-section").first();
          }
          if (!$section.length) {
            return;
          }
          if (sections.indexOf($section[0]) === -1) {
            sections.push($section[0]);
          }
        });

      if (sections.length <= 1) {
        return;
      }

      var $keep = $(".aitu-default-content .aitu-cart-related-section").last();
      if (!$keep.length) {
        $keep = $(sections[sections.length - 1]);
      }
      if (!$keep.length) {
        return;
      }

      sections.forEach(function (sectionNode) {
        if (!sectionNode || sectionNode === $keep[0]) {
          return;
        }
        if ($keep[0].contains(sectionNode) || sectionNode.contains($keep[0])) {
          return;
        }
        $(sectionNode).remove();
      });
    }

    dedupSections(document);

    var observerTarget = document.querySelector(".wp-block-woocommerce-cart, .wc-block-cart, .aitu-default-content");
    if (!observerTarget || typeof MutationObserver === "undefined") {
      return;
    }

    var observer = new MutationObserver(function () {
      dedupSections(observerTarget);
    });

    observer.observe(observerTarget, {
      childList: true,
      subtree: true
    });
  }

  function initCheckoutButtonLabel() {
    var checkoutButtonLabel = "CHECKOUT";
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.checkoutButtonLabel === "string" &&
      window.aituThemeI18n.checkoutButtonLabel.length
    ) {
      checkoutButtonLabel = window.aituThemeI18n.checkoutButtonLabel;
    }

    function applyLabel(context) {
      var $scope = context ? $(context) : $(document);

      $scope.find("button#place_order").each(function () {
        var $button = $(this);
        if ($.trim($button.text()) !== checkoutButtonLabel) {
          $button.text(checkoutButtonLabel);
        }
      });

      $scope
        .find(".wc-block-components-checkout-place-order-button .wc-block-components-button__text")
        .each(function () {
          var $text = $(this);
          if ($.trim($text.text()) !== checkoutButtonLabel) {
            $text.text(checkoutButtonLabel);
          }
        });

      $scope.find(".wc-block-components-checkout-place-order-button").each(function () {
        var $button = $(this);
        if (!$button.find(".wc-block-components-button__text").length && $.trim($button.text()) !== checkoutButtonLabel) {
          $button.text(checkoutButtonLabel);
        }
      });
    }

    var attempts = 0;
    var maxAttempts = 24;
    var timer = window.setInterval(function () {
      applyLabel(document);
      attempts += 1;
      if (attempts >= maxAttempts) {
        window.clearInterval(timer);
      }
    }, 250);

    applyLabel(document);
  }

  function initCheckoutHeadingLabel() {
    var checkoutHeadingLabel = "CHECKOUT";
    if (
      window.aituThemeI18n &&
      typeof window.aituThemeI18n.checkoutHeadingLabel === "string" &&
      window.aituThemeI18n.checkoutHeadingLabel.length
    ) {
      checkoutHeadingLabel = window.aituThemeI18n.checkoutHeadingLabel;
    }

    var headingPattern = /^(checkout|pokladňa)$/i;

    function applyLabel(context) {
      var $scope = context ? $(context) : $(document);

      $scope
        .find(
          ".wp-block-woocommerce-checkout .wc-block-checkout__title, .wp-block-woocommerce-checkout h1.wp-block-woocommerce-checkout__title, .wp-block-woocommerce-checkout .wc-block-components-title"
        )
        .each(function () {
          var $heading = $(this);
          var currentText = $.trim($heading.text());
          if (!currentText || !headingPattern.test(currentText)) {
            return;
          }
          if (currentText !== checkoutHeadingLabel) {
            $heading.text(checkoutHeadingLabel);
          }
        });
    }

    var attempts = 0;
    var maxAttempts = 24;
    var timer = window.setInterval(function () {
      applyLabel(document);
      attempts += 1;
      if (attempts >= maxAttempts) {
        window.clearInterval(timer);
      }
    }, 250);

    applyLabel(document);
  }

  function initCheckoutButtonPlacement() {
    function moveCheckoutActions() {
      var $checkoutRoot = $(".wp-block-woocommerce-checkout, .wc-block-checkout").first();
      if (!$checkoutRoot.length) {
        return false;
      }

      var $sidebar = $checkoutRoot.find(".wc-block-checkout__sidebar, .wc-block-components-sidebar").first();
      var $summaryBlock = $sidebar.find(".wp-block-woocommerce-checkout-order-summary-block").first();
      if (!$sidebar.length || !$summaryBlock.length) {
        return false;
      }

      var $target = $sidebar.find(".aitu-checkout-sidebar-actions").first();
      if (!$target.length) {
        $target = $('<div class="aitu-checkout-sidebar-actions"></div>');
        $summaryBlock.after($target);
      } else if ($target.prev()[0] !== $summaryBlock[0]) {
        $summaryBlock.after($target);
      }

      var $actionsRow = $checkoutRoot.find(".wc-block-checkout__actions_row").first();
      if ($actionsRow.length) {
        if (!$target[0].contains($actionsRow[0])) {
          $target.append($actionsRow);
        }
        return true;
      }

      var $button = $checkoutRoot.find(".wc-block-components-checkout-place-order-button").first();
      if ($button.length && !$target[0].contains($button[0])) {
        $target.append($button);
      }
      return $button.length > 0;
    }

    window.setTimeout(function () {
      var attempts = 0;
      var maxAttempts = 40;
      var timer = window.setInterval(function () {
        attempts += 1;
        var moved = moveCheckoutActions();
        if (moved || attempts >= maxAttempts) {
          window.clearInterval(timer);
        }
      }, 250);
    }, 1200);
  }

  function initGridHoverPlusCursor() {
    if (window.matchMedia && window.matchMedia("(hover: none), (pointer: coarse)").matches) {
      return;
    }

    var selector = ".aitu-product-card-image, .aitu-related-card";
    var $targets = $(selector);
    if (!$targets.length) {
      return;
    }

    var $plusCursor = $(".aitu-plus-cursor");

    function showPlusCursor() {
      $("body").addClass("aitu-plus-cursor-active");
    }

    function hidePlusCursor() {
      $("body").removeClass("aitu-plus-cursor-active");
    }

    function movePlusCursor(event) {
      if (!$plusCursor.length || typeof event.clientX !== "number" || typeof event.clientY !== "number") {
        return;
      }
      $plusCursor.css({
        left: event.clientX + "px",
        top: event.clientY + "px"
      });
    }

    if (!$plusCursor.length) {
      $plusCursor = $('<div class="aitu-plus-cursor" aria-hidden="true"></div>');
      $("body").append($plusCursor);
    }

    $(document)
      .off("mouseenter.aituGridPlusCursor", selector)
      .on("mouseenter.aituGridPlusCursor", selector, function (event) {
        if ($("body").hasClass("aitu-lightbox-open")) {
          hidePlusCursor();
          return;
        }
        showPlusCursor();
        movePlusCursor(event);
      })
      .off("mousemove.aituGridPlusCursor", selector)
      .on("mousemove.aituGridPlusCursor", selector, function (event) {
        if ($("body").hasClass("aitu-lightbox-open")) {
          hidePlusCursor();
          return;
        }
        showPlusCursor();
        movePlusCursor(event);
      })
      .off("mouseleave.aituGridPlusCursor", selector)
      .on("mouseleave.aituGridPlusCursor", selector, function () {
        hidePlusCursor();
      })
      .off("mouseleave.aituGridPlusCursorDocument")
      .on("mouseleave.aituGridPlusCursorDocument", function () {
        hidePlusCursor();
      })
      .off("scroll.aituGridPlusCursor resize.aituGridPlusCursor")
      .on("scroll.aituGridPlusCursor resize.aituGridPlusCursor", function () {
        hidePlusCursor();
      });

  }

  $(document).ready(function () {
    var isCheckoutPage = $(".wp-block-woocommerce-checkout, .wc-block-checkout").length > 0;

    initNavbarSearchOverlay();
    initSingleProductUI();
    initAddToCartToast();
    initCartTotalLabel();
    initCartHeadingLabel();
    initCartEmptyLabel();
    initCartNewInStoreDedup();
    initCartCheckoutUrlLocalization();
    if (!isCheckoutPage) {
      initCheckoutButtonPlacement();
      initCheckoutButtonLabel();
      initCheckoutHeadingLabel();
    }
    initGridHoverPlusCursor();
  });

  $(document.body).on("wc_variation_form", function () {
    initSingleProductUI();
  });
})(jQuery);
