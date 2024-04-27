(function ($, Drupal, drupalSettings, once) {
  "use strict";

  Drupal.behaviors.main_header = {
    attach: function (context, settings) {
      once('main_header', '.main-header', context).forEach(function (element) {
        window.initMapStyleOnDesktop = function () {
          // get the map
          const searchResultMap = document.getElementById('search-result-map');
          const mainHeader = document.querySelector('.main-header');
          if (searchResultMap) {
            // Prendre la hauteur du header
            let mapTopValue = mainHeader ? mainHeader.offsetTop + mainHeader.offsetHeight : 0;

            // Si on est en mode admin, on prend en compte la hauteur de la toolbad admin
            const toolbarAdmin = document.getElementById('toolbar-administration');
            if (toolbarAdmin) {
              mapTopValue += toolbarAdmin.offsetTop - 1;
            }

            searchResultMap.style.top = `${mapTopValue}px`;
          }
        };

        const stickyHeader = function () {
          const wrapperHeader = document.querySelector('.wrapper-header-toolbar');
          wrapperHeader.classList.add('sticky-header');
          const headerHeight = wrapperHeader.offsetHeight;
          wrapperHeader.style.top = getComputedStyle(document.body).paddingTop;
          const containerMain = document.getElementById('container-main');
          containerMain.style.paddingTop = headerHeight + 'px';
        };

        window.fixFooter = function () {
          const footer = document.querySelector('footer');
          const blockFilters = document.getElementById('filters-fixed');

          if (blockFilters && footer) {
            const blockFiltersBounding = blockFilters.getBoundingClientRect();

            const top_of_element = $(footer).offset().top;
            const bottom_of_element = $(footer).offset().top + $(footer).outerHeight();
            const bottom_of_screen = $(window).scrollTop() + $(window).innerHeight();
            const top_of_screen = $(window).scrollTop();

            if ((bottom_of_screen > top_of_element) && (top_of_screen < bottom_of_element)){
              const filterHeight = document.documentElement.clientHeight - blockFiltersBounding.top;
              const topFooterVisible = document.documentElement.clientHeight - footer.getBoundingClientRect().top;
              const newFilterHeight = filterHeight - topFooterVisible;
              blockFilters.style.height = newFilterHeight + 'px';
            } else {
              const filterTop = blockFilters.getBoundingClientRect().top;
              const newFilterHeight = document.documentElement.clientHeight - filterTop;
              blockFilters.style.height = `${newFilterHeight}px`;            }
          }
        };

        const headerFrontPage = function () {
          if (document.body.classList.contains('path-frontpage')) {
            const mainHeader = document.querySelector('.main-header');

            if (!isMobile) {
              mainHeader.classList.add('no-scrolled');
            }
          }
        };

        // gestion de la navigation mobile
        const toggleButton = function() {
          const toggles = document.querySelectorAll('.btn-toggle');
          const mobileToggle = document.querySelector('.mobile-toggle');
        
          toggles.forEach(button => {
            const target = button.getAttribute('aria-controls');
            const collapseTarget = document.getElementById(target);
            if (collapseTarget) {
              button.classList.add('show-btn');
              button.addEventListener('click', function(event) {
                if (collapseTarget.classList.contains("open")) {
                  collapseTarget.classList.remove("open");
                  this.classList.remove("header-btn-active");
                  this.setAttribute("aria-expanded", "false");
                  mobileToggle.classList.remove("active");
                } else {
                  const openedPanel = document.querySelector('.open');
                  const activeBtn = document.querySelector('.header-btn-active');
                  if (openedPanel) {
                    openedPanel.classList.remove('open');
                  }
                  if (activeBtn) {
                    activeBtn.setAttribute("aria-expanded", "false");
                    activeBtn.classList.remove('header-btn-active');
                  }
                  collapseTarget.classList.add("open");
                  this.classList.add("header-btn-active");
                  this.setAttribute("aria-expanded", "true");
                  mobileToggle.classList.add("active");
                }
              });
            } else {
              button.remove();
            }
          });
        };
        

        const outilsMenu = function () {
          const $dropdownOutils = $('.dropdown-outils');

          if ($dropdownOutils.length) {
            $dropdownOutils.each(function () {
              const $parent = $(this).parents('.wrapper-btn-outils');
              const $button = $(this).siblings('.btn-outils').attr('aria-expanded', false);
              let shareHoverTrig = false;

              $button.on('mouseover focus', function (e) {
                if (!$parent.hasClass('outils-open')) {
                  document.activeElement.blur();
                }

                outilsParamsOpened();
              });

              $button.keydown(function (e) {
                // open/close popin by Space or Enter keydown
                if (e.keyCode === 32 || e.keyCode === 13) {
                  e.preventDefault();

                  $parent.hasClass('outils-open') ? outilsParamsClosed() : outilsParamsOpened();
                }

                //close popin by Esc keydown
                if (e.keyCode === 27) {
                  e.preventDefault();

                  outilsParamsClosed();
                }
              });

              $parent.find('.dropdown-outils').keydown(function (e) {
                //close popin by Esc keydown
                if (e.keyCode === 27) {
                  e.preventDefault();

                  outilsParamsClosed();
                }
              });

              $(document).keyup(function (e) {
                if (e.keyCode === 27) {
                  outilsParamsClosed();
                }
              });

              $parent.focusin(function () {
                $parent.addClass('focus');
              });

              //clear class and focus by blur share panel and btn
              $(document).on('focusin mouseover', function (e) {
                const target = e.target;
                let focusLoopTrig = false;

                //set Share trigger for mouse events
                if (e.type === 'mouseover') {
                  shareHoverTrig = outilsIsHovered();
                }

                //focus loop if Share hovered by mouse
                if (!targetIsOutils(target) && shareHoverTrig) {
                  $button.focus();
                  focusLoopTrig = true;
                }

                //clear attributes and classes if Share not in focus
                if (!targetIsOutils(target) && !shareHoverTrig && !focusLoopTrig) {
                  $parent.removeClass('focus outils-open');
                  $button.attr('aria-expanded', false);
                }
              });

              //set class & attr to opened element
              function outilsParamsOpened() {
                $dropdownOutils.parents('.wrapper-btn-outils').removeClass('outils-open');
                $dropdownOutils.siblings('.btn-outils').attr('aria-expanded', false);
                $parent.addClass('outils-open');
                $button.attr('aria-expanded', true);
              }

              //unset class & attr to opened element
              function outilsParamsClosed() {
                $parent.removeClass('outils-open');
                $button.attr('aria-expanded', false);
              }

              //check share is hovered
              function outilsIsHovered() {
                return $parent.is(':hover') || !!$parent.find('.dropdown-outils').is(':hover');
              }

              //check that target is Share elements
              function targetIsOutils(target) {
                return $(target).hasClass('btn-outils') || !!$(target).closest('.dropdown-outils').length;
              }

            });
          }
        };

        outilsMenu();

        setTimeout(function () {
          stickyHeader();
          headerFrontPage();
          initMapStyleOnDesktop();
          fixFooter();
          toggleButton();
        }, 300);

        window.onscroll = function () {
          stickyHeader();
          headerFrontPage();
          fixFooter();
        };
        window.onresize = function () {
          stickyHeader();
          headerFrontPage();
          initMapStyleOnDesktop();
          fixFooter();

        };

        // Select the node that will be observed for mutations
        const targetNodeAdminTray = document.getElementById('toolbar-item-administration-tray');
        const targetNodeUserTray = document.getElementById('toolbar-item-user-tray');

        // Options for the observer (which mutations to observe)
        const config = {attributes: true};

        // Create an observer instance linked to the callback function
        const observer = new MutationObserver(() => {
          stickyHeader();
          headerFrontPage();
          initMapStyleOnDesktop();
          fixFooter();
        });

        if (targetNodeAdminTray && targetNodeUserTray) {
          // Start observing the target node for configured mutations
          observer.observe(targetNodeAdminTray, config);
          observer.observe(targetNodeUserTray, config);
        }
      });
    }
  };
}(jQuery, Drupal, drupalSettings, once));

