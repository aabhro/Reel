
(function (global) {
  "use strict";

  var reduceQuery = global.matchMedia ? global.matchMedia("(prefers-reduced-motion: reduce)") : null;
  var reduced = function () { return !!(reduceQuery && reduceQuery.matches); };

  function parseTime(value) {
    var number = parseFloat(value) || 0;
    return value && value.indexOf("ms") !== -1 ? number : number * 1000;
  }

  function maxMotionTime(element) {
    if (!element || reduced()) return 0;
    var nodes = [element].concat(Array.prototype.slice.call(element.querySelectorAll("*")));
    var max = 0;
    nodes.forEach(function (node) {
      var style = global.getComputedStyle(node);
      var durations = style.transitionDuration.split(",").map(parseTime);
      var delays = style.transitionDelay.split(",").map(parseTime);
      for (var i = 0; i < durations.length; i++) {
        max = Math.max(max, durations[i] + (delays[i % delays.length] || 0));
      }
    });
    return max;
  }

  function waitForMotion(element) {
    var duration = maxMotionTime(element);
    return new Promise(function (resolve) {
      global.setTimeout(resolve, duration > 1 ? duration + 30 : 0);
    });
  }

  async function setOpen(element, open, trigger, restoreFocus) {
    if (!element) return;
    var motionVersion = (element._motionVersion || 0) + 1;
    element._motionVersion = motionVersion;
    if (trigger && trigger.hasAttribute("aria-expanded")) trigger.setAttribute("aria-expanded", open ? "true" : "false");

    if (open) {
      element._motionRestoreFocus = false;
      if (element._motionTimer) {
        global.clearTimeout(element._motionTimer);
        element._motionTimer = null;
      }
      if (trigger) element._motionTrigger = trigger;
      element.hidden = false;
      element.removeAttribute("inert");
      element.setAttribute("aria-hidden", "false");
      element.dataset.state = "opening";
      void element.offsetWidth;
      requestAnimationFrame(function () {
        if (element._motionVersion !== motionVersion || element.dataset.state !== "opening") return;
        element.dataset.state = "open";
        if (element.classList.contains("m-dialog")) {
          var first = element.querySelector("button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex='-1'])");
          if (first) first.focus();
        } else if (element.getAttribute("role") === "menu" && element._motionKeyboardOpen) {
          var firstItem = element.querySelector('[role="menuitem"]:not([disabled])');
          if (firstItem) firstItem.focus();
          element._motionKeyboardOpen = false;
        }
      });
      return;
    }

    element._motionRestoreFocus = !!restoreFocus;
    if (element._motionTimer) {
      global.clearTimeout(element._motionTimer);
      element._motionTimer = null;
    }
    element.dataset.state = "closing";
    element.setAttribute("aria-hidden", "true");
    element.setAttribute("inert", "");
    await waitForMotion(element);
    if (element._motionVersion === motionVersion && element.dataset.state === "closing") {
      element.hidden = true;
      element.dataset.state = "closed";
      if (element._motionRestoreFocus && element._motionTrigger && !element.classList.contains("m-toast")) element._motionTrigger.focus();
      element._motionRestoreFocus = false;
    }
  }

  function replay(element, className) {
    if (!element) return;
    className = className || "m-icon-swap";
    element.classList.remove(className);
    void element.offsetWidth;
    element.classList.add(className);
    element.addEventListener("animationend", function () {
      element.classList.remove(className);
    }, { once: true });
  }

  function swapText(element, value) {
    if (!element || element.textContent === String(value)) return;
    element.textContent = String(value);
    replay(element, "m-text-swap");
  }

  function showToast(element, duration) {
    if (!element) return;
    duration = typeof duration === "number" ? duration : 3200;
    if (element._motionTimer) global.clearTimeout(element._motionTimer);
    element.hidden = false;
    element.removeAttribute("inert");
    element.setAttribute("aria-hidden", "false");
    element.dataset.state = "opening";
    requestAnimationFrame(function () { element.dataset.state = "open"; });
    element._motionTimer = global.setTimeout(function () {
      setOpen(element, false);
    }, duration);
  }


  function clearText(element) {
    if (!element) return;
    element.classList.remove("is-clearing");
    void element.offsetWidth;
    element.classList.add("is-clearing");
    element.addEventListener("animationend", function () {
      if ("value" in element) element.value = "";
      else element.textContent = "";
      element.classList.remove("is-clearing");
    }, { once: true });
  }

  function measureTextWidth(element, value) {
    var probe = element.cloneNode(false);
    var computed = global.getComputedStyle(element);
    var inheritedMetrics = [
      "display", "box-sizing", "font-family", "font-size", "font-weight",
      "font-style", "font-stretch", "letter-spacing", "line-height",
      "text-transform", "text-indent", "white-space",
      "padding-left", "padding-right", "border-left-width", "border-right-width",
      "border-left-style", "border-right-style"
    ];
    probe.removeAttribute("id");
    probe.textContent = String(value);
    inheritedMetrics.forEach(function (property) {
      probe.style.setProperty(property, computed.getPropertyValue(property));
    });
    probe.style.position = "fixed";
    probe.style.left = "-10000px";
    probe.style.top = "0";
    probe.style.width = "max-content";
    probe.style.maxWidth = "none";
    probe.style.minWidth = "0";
    probe.style.visibility = "hidden";
    probe.style.pointerEvents = "none";
    probe.style.transition = "none";
    probe.style.animation = "none";
    document.body.appendChild(probe);
    var width = probe.getBoundingClientRect().width;
    probe.remove();
    return width;
  }

  function morphText(element, value) {
    if (!element || element.textContent === String(value)) return;
    if (element._morphCleanup) element._morphCleanup();

    var originalWidth = element.getBoundingClientRect().width;
    var targetWidth = measureTextWidth(element, value);
    element.classList.add("m-text-morph");
    element.style.maxWidth = "none";
    element.style.width = originalWidth + "px";
    element.textContent = String(value);
    void element.offsetWidth;

    var finished = false;
    function cleanup() {
      if (finished) return;
      finished = true;
      if (element._morphTimer) global.clearTimeout(element._morphTimer);
      element.removeEventListener("transitionend", onEnd);
      element.style.width = "";
      element.style.maxWidth = "";
      element.classList.remove("m-text-morph");
      element._morphTimer = null;
      element._morphCleanup = null;
    }
    function onEnd(event) {
      if (event.target === element && event.propertyName === "width") cleanup();
    }
    element._morphCleanup = cleanup;

    if (reduced()) {
      cleanup();
      replay(element, "m-text-swap-soft");
      return;
    }

    element.addEventListener("transitionend", onEnd);
    requestAnimationFrame(function () {
      if (!finished) element.style.width = targetWidth + "px";
    });
    element._morphTimer = global.setTimeout(cleanup, 360);
    replay(element, "m-text-swap-soft");
  }

  function selectTab(tab, group) {
    if (!tab) return;
    group = group || tab.closest(".m-tabs");
    if (!group) return;
    var tabs = Array.prototype.slice.call(group.querySelectorAll('[role="tab"]'));
    tabs.forEach(function (item) {
      var active = item === tab;
      item.setAttribute("aria-selected", active ? "true" : "false");
      item.tabIndex = active ? 0 : -1;
    });
    var indicator = group.querySelector(".m-tabs__indicator");
    if (indicator) {
      indicator.style.setProperty("--m-tab-left", tab.offsetLeft + "px");
      indicator.style.setProperty("--m-tab-width", tab.offsetWidth + "px");
    }
    var panels = group.querySelectorAll('[role="tabpanel"]');
    panels.forEach(function (panel) {
      var active = panel.id === tab.getAttribute("aria-controls");
      panel.hidden = !active;
    });
  }

  function refreshTabIndicators() {
    document.querySelectorAll(".m-tabs").forEach(function (group) {
      var active = group.querySelector('[role="tab"][aria-selected="true"]');
      var indicator = group.querySelector(".m-tabs__indicator");
      if (!active || !indicator) return;
      indicator.style.setProperty("--m-tab-left", active.offsetLeft + "px");
      indicator.style.setProperty("--m-tab-width", active.offsetWidth + "px");
    });
  }

  function bindTilt(root) {
    if (reduced() || !global.matchMedia || !global.matchMedia("(hover: hover) and (pointer: fine)").matches) return;
    root.querySelectorAll("[data-motion-tilt]").forEach(function (element) {
      if (element.dataset.motionTiltBound) return;
      element.dataset.motionTiltBound = "true";
      element.classList.add("m-tilt");
      element.addEventListener("pointermove", function (event) {
        var rect = element.getBoundingClientRect();
        var x = (event.clientX - rect.left) / rect.width;
        var y = (event.clientY - rect.top) / rect.height;
        element.style.setProperty("--m-tilt-x", ((0.5 - y) * 5).toFixed(2) + "deg");
        element.style.setProperty("--m-tilt-y", ((x - 0.5) * 6).toFixed(2) + "deg");
      });
      element.addEventListener("pointerleave", function () {
        element.style.setProperty("--m-tilt-x", "0deg");
        element.style.setProperty("--m-tilt-y", "0deg");
      });
    });
  }

  function bind(root) {
    root = root || document;
    bindTilt(root);
    root.addEventListener("click", function (event) {
      if (!event.target.closest) return;
      var openTrigger = event.target.closest("[data-motion-open]");
      if (openTrigger) {
        var selector = openTrigger.getAttribute("data-motion-open");
        var target = selector && document.querySelector(selector);
        if (target) {
          target._motionKeyboardOpen = event.detail === 0;
          var alreadyOpen = target.dataset.state === "open" || target.dataset.state === "opening";
          setOpen(target, !alreadyOpen, openTrigger);
        }
      }

      var closeTrigger = event.target.closest("[data-motion-close]");
      if (closeTrigger) {
        var closeSelector = closeTrigger.getAttribute("data-motion-close");
        var closeTarget = closeSelector ? document.querySelector(closeSelector) : closeTrigger.closest(".m-dialog, .m-dropdown, .m-panel, .m-toast");
        if (closeTarget) {
          var opener = closeTarget._motionTrigger || (closeTarget.id ? document.querySelector('[data-motion-open="#' + closeTarget.id + '"]') : null);
          setOpen(closeTarget, false, opener, true);
        }
      }

      document.querySelectorAll('.m-dropdown[data-state="open"]').forEach(function (menu) {
        var menuTrigger = menu._motionTrigger;
        var requestedSurface = openTrigger && document.querySelector(openTrigger.getAttribute("data-motion-open"));
        if (!menu.contains(event.target) && requestedSurface !== menu && !closeTrigger) {
          setOpen(menu, false, menuTrigger);
        }
      });

      var clearTrigger = event.target.closest("[data-motion-clear]");
      if (clearTrigger) {
        var clearTarget = document.querySelector(clearTrigger.getAttribute("data-motion-clear"));
        if (clearTarget) clearText(clearTarget);
      }

      var morphTrigger = event.target.closest("[data-motion-morph]");
      if (morphTrigger) {
        var morphTarget = document.querySelector(morphTrigger.getAttribute("data-motion-morph"));
        if (morphTarget) morphText(morphTarget, morphTrigger.getAttribute("data-motion-text") || morphTrigger.textContent);
      }

      var swapTrigger = event.target.closest("[data-motion-swap]");
      if (swapTrigger) {
        var selectorSwap = swapTrigger.getAttribute("data-motion-swap");
        var targetSwap = selectorSwap && document.querySelector(selectorSwap);
        if (targetSwap) swapText(targetSwap, swapTrigger.getAttribute("data-motion-text") || swapTrigger.textContent);
      }

      var replayTrigger = event.target.closest("[data-motion-replay]");
      if (replayTrigger) {
        var replaySelector = replayTrigger.getAttribute("data-motion-replay");
        var replayTarget = replaySelector ? document.querySelector(replaySelector) : replayTrigger;
        if (replayTarget) replay(replayTarget, replayTrigger.getAttribute("data-motion-class") || "m-icon-swap");
      }

      var toastTrigger = event.target.closest("[data-motion-toast]");
      if (toastTrigger) {
        var toastTarget = document.querySelector(toastTrigger.getAttribute("data-motion-toast"));
        if (toastTarget) showToast(toastTarget, Number(toastTrigger.getAttribute("data-motion-duration")) || 3200);
      }

      var tab = event.target.closest('.m-tabs [role="tab"]');
      if (tab) selectTab(tab);
    });

    root.addEventListener("keydown", function (event) {
      var activeDialog = document.querySelector('.m-dialog[data-state="open"]');
      if (activeDialog && event.key === "Tab") {
        var focusables = Array.prototype.slice.call(activeDialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter(function (node) {
          return !node.hidden && node.getAttribute("aria-hidden") !== "true" && !node.closest("[hidden]");
        });
        if (!focusables.length) {
          event.preventDefault();
          var dialogPanel = activeDialog.querySelector(".m-dialog__panel");
          if (dialogPanel) {
            dialogPanel.setAttribute("tabindex", "-1");
            dialogPanel.focus();
          }
        } else if (!activeDialog.contains(document.activeElement)) {
          event.preventDefault();
          (event.shiftKey ? focusables[focusables.length - 1] : focusables[0]).focus();
        } else if (event.shiftKey && document.activeElement === focusables[0]) {
          event.preventDefault();
          focusables[focusables.length - 1].focus();
        } else if (!event.shiftKey && document.activeElement === focusables[focusables.length - 1]) {
          event.preventDefault();
          focusables[0].focus();
        }
      }

      if (event.key === "Escape") {
        var dialog = document.querySelector('.m-dialog[data-state="open"]');
        if (dialog) {
          var opener = dialog._motionTrigger || document.querySelector('[data-motion-open="#' + dialog.id + '"]');
          setOpen(dialog, false, opener, true);
          if (opener) opener.focus();
          return;
        }
        var surface = document.querySelector('.m-dropdown[data-state="open"], .m-panel[data-state="open"]');
        if (surface) setOpen(surface, false, surface._motionTrigger, true);
      }

      var menuItem = event.target.closest && event.target.closest('.m-dropdown[role="menu"] [role="menuitem"]');
      if (menuItem && ["ArrowDown", "ArrowUp", "Home", "End"].indexOf(event.key) !== -1) {
        var menuItems = Array.prototype.slice.call(menuItem.closest('[role="menu"]').querySelectorAll('[role="menuitem"]:not([disabled])'));
        var menuIndex = menuItems.indexOf(menuItem);
        if (event.key === "ArrowDown") menuIndex = (menuIndex + 1) % menuItems.length;
        if (event.key === "ArrowUp") menuIndex = (menuIndex - 1 + menuItems.length) % menuItems.length;
        if (event.key === "Home") menuIndex = 0;
        if (event.key === "End") menuIndex = menuItems.length - 1;
        event.preventDefault();
        if (menuItems[menuIndex]) menuItems[menuIndex].focus();
      }

      var tab = event.target.closest && event.target.closest('.m-tabs [role="tab"]');
      if (tab && ["ArrowLeft", "ArrowRight", "Home", "End"].indexOf(event.key) !== -1) {
        var tabs = Array.prototype.slice.call(tab.closest(".m-tabs").querySelectorAll('[role="tab"]'));
        var index = tabs.indexOf(tab);
        if (event.key === "ArrowRight") index = (index + 1) % tabs.length;
        if (event.key === "ArrowLeft") index = (index - 1 + tabs.length) % tabs.length;
        if (event.key === "Home") index = 0;
        if (event.key === "End") index = tabs.length - 1;
        event.preventDefault();
        tabs[index].focus();
        selectTab(tabs[index]);
      }
    });
  }

  if (global.addEventListener) global.addEventListener("resize", refreshTabIndicators, { passive: true });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(refreshTabIndicators);

  global.Motion = {
    prefersReducedMotion: reduced,
    setOpen: setOpen,
    replay: replay,
    swapText: swapText,
    clearText: clearText,
    morphText: morphText,
    showToast: showToast,
    selectTab: selectTab,
    bind: bind
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () { bind(document); });
  } else {
    bind(document);
  }
})(window);

