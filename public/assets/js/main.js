(function ($) {
  "use strict";

  // ==========================================
  //      Start Document Ready function
  // ==========================================
  $(document).ready(function () {
    // ========================= Custom Select Js Start =====================
    $(".select-dropdown-wrapper").each(function () {
      const $wrapper = $(this);
      const $selectButton = $wrapper.find(".select-button");
      const $selectButtonArrow = $wrapper.find(".select-button__arrow");
      const $selectButtonText = $wrapper.find(".select-button__text");
      const $selectButtonFlag = $wrapper.find(".select-dropdown__flag img").first();
      const $selectDropdown = $wrapper.find(".select-dropdown");
      const $selectDropdownItems = $wrapper.find(".select-dropdown__item");

      // Toggle dropdown on button click
      $selectButton.on("click", function (e) {
        e.stopPropagation();
        $selectDropdown.toggleClass("active invisible opacity-0");
        const isActive = $selectDropdown.hasClass("active");
        $selectButtonArrow.css("transform", isActive ? "rotate(180deg)" : "rotate(0deg)");
      });

      // Handle selection
      $selectDropdownItems.on("click", function (e) {
        e.stopPropagation();

        const selectedText = $(this).find(".select-dropdown__text").text();
        const selectedFlagSrc = $(this).find(".select-dropdown__flag img").attr("src");

        $selectButtonText.text(selectedText);
        $selectButtonFlag.attr("src", selectedFlagSrc);

        $selectDropdown.removeClass("active").addClass("invisible opacity-0");
        $selectButtonArrow.css("transform", "rotate(0deg)");
      });

      // Close dropdown if clicking outside
      $(document).on("click", function (e) {
        if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
          $selectDropdown.removeClass("active").addClass("invisible opacity-0");
          $selectButtonArrow.css("transform", "rotate(0deg)");
        }
      });
    });
    // ========================= Custom Select Js Start =====================

    // ============================= inner mobail js start ====================
    $(".dashboard-toggle-button").on("click", function () {
      $(".dashboard-slidebar").toggleClass("active");
    });
    $(".dashboard-close-button").on("click", function () {
      $(".dashboard-slidebar").removeClass("active");
    });
    // ============================= inner mobail js end ====================

    /*================= banner brand js start =========================*/
    var swiper = new Swiper(".banner-brand-swiper", {
      slidesPerView: 7,
      loop: true,
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      breakpoints: {
        320: {
          slidesPerView: 3,
        },
        524: {
          slidesPerView: 3,
        },
        640: {
          slidesPerView: 4,
        },
        768: {
          slidesPerView: 5,
        },
        1024: {
          slidesPerView: 6,
        },
        1199: {
          slidesPerView: 7,
        },
      },
    });
    /*================= banner brand js end =========================*/

    /*===================testimonial js start======================*/
    var swiper = new Swiper(".mySwiper", {
      slidesPerView: 2,
      loop: true,
      autoplay: {
        delay: 2000,
        disableOnInteraction: false,
      },
      navigation: {
        nextEl: ".testimonial-button-next",
        prevEl: ".testimonial-button-prev",
      },
      breakpoints: {
        640: {
          slidesPerView: 2,
        },
        768: {
          slidesPerView: 2,
        },
        1024: {
          slidesPerView: 2,
        },
      },
    });
    /*===================testimonial js end======================*/

    /*================= img uplode js start ========================*/
    function readURL(input) {
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
          $("#imagePreview").css(
            "background-image",
            "url(" + e.target.result + ")"
          );
          $("#imagePreview").hide();
          $("#imagePreview").fadeIn(650);
        };
        reader.readAsDataURL(input.files[0]);
      }
    }
    $("#imageUpload").change(function () {
      readURL(this);
    });
    /*================= img uplode js end ========================*/

    // ========================= Counter Up Js Start ===================
    const counterUp = window.counterUp.default;
    const callback = (entries) => {
      entries.forEach((entry) => {
        const el = entry.target;
        if (entry.isIntersecting && !el.classList.contains("is-visible")) {
          counterUp(el, {
            duration: 3500,
            delay: 16,
          });
          el.classList.add("is-visible");
        }
      });
    };
    const IO = new IntersectionObserver(callback, { threshold: 1 });

    // Banner statistics Counter
    const statisticsCounter = document.querySelectorAll(".counter");
    if (statisticsCounter.length > 0) {
      statisticsCounter.forEach((counterNumber) => {
        IO.observe(counterNumber);
      });
    }

    // performance Count
    const performanceCount = document.querySelectorAll(".counter");
    if (performanceCount.length > 0) {
      performanceCount.forEach((counterNumber) => {
        IO.observe(counterNumber);
      });
    }
    // ========================= Counter Up Js End ===================



    // ====================== home four brand slider js start ======================
    var swiper = new Swiper(".brand-four__slider", {
      slidesPerView: 5,
      loop: true,
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        499: {
          slidesPerView: 2,
        },
        640: {
          slidesPerView: 2,
        },
        768: {
          slidesPerView: 3,
        },
        992: {
          slidesPerView: 4,
        },
        1199: {
          slidesPerView: 5,
        },
      },
    });
    // ====================== home four brand slider js end ======================



    // =========================== Dropdown menu Js Start =======================
    $(".dropdown-menu").on("click", function (event) {
      event.stopPropagation();
    });

    // Remove Dropdown Menu
    $(".close-dropdown").on("click", function () {
      $(".dropdown-menu").removeClass("show");
      $(".dropdown-btn").removeClass("show");
      $(".dropdown-btn").setAttribute("aria-expanded", "false");
    });
    // =========================== Dropdown menu Js End =======================

    /*=================== payment method js start======================*/
    const multiplier = {
      translate: 0.1,
      rotate: 0.02,
    };
    var PaymentSwiper = new Swiper(".payment-mathod-slider", {
      slidesPerView: 7,
      spaceBetween: 24,
      loop: true,
      autoplay: false,
      grabCursor: true,
      navigation: {
        nextEl: ".payment-method-button-prev",
        prevEl: ".payment-method-button-next",
      },
      breakpoints: {
        320: { slidesPerView: 2 },
        640: { slidesPerView: 2 },
        768: { slidesPerView: 4 },
        1024: { slidesPerView: 6 },
        1199: { slidesPerView: 7 },
      },
    });
    function calculateWheel() {
      const slides = document.querySelectorAll(".single");
      slides.forEach((slide, i) => {
        const rect = slide.getBoundingClientRect();
        const r = window.innerWidth * 0.5 - (rect.x + rect.width * 0.5);
        let ty =
          Math.abs(r) * multiplier.translate -
          rect.width * multiplier.translate;

        if (ty < 0) {
          ty = 0;
        }
        const transformOrigin = r < 0 ? "left top" : "right top";
        slide.style.transform = `translate(0, ${ty}px) rotate(${
          -r * multiplier.rotate
        }deg)`;
        slide.style.transformOrigin = transformOrigin;
      });
    }
    function raf() {
      requestAnimationFrame(raf);
      calculateWheel();
    }
    raf();
    /*=================== payment method js end======================*/



    // ========================== Settings Panel Js Start =====================
    $(".settings-button").on("click", function () {
      $(".settings-panel").toggleClass("active");
      $(this).toggleClass("active");
    });

    $(document).on(
      "click",
      ".settings-panel__buttons .settings-panel__button",
      function () {
        $(this).siblings().removeClass("active");
        $(this).addClass("active");
      }
    );

    // Cursor start
    $(".cursor-animate").on("click", function () {
      $("body").removeClass("remove-animate-cursor");
    });

    $(".cursor-default").on("click", function () {
      $("body").addClass("remove-animate-cursor");
    });
    // Cursor end

    // Direction start
    $(".direction-ltr").on("click", function () {
      $("html").attr("dir", "ltr");
    });

    $(".direction-rtl").on("click", function () {
      $("html").attr("dir", "rtl");
    });
    // Direction end
    // ========================== Settings Panel Js End =====================




    /*=================== our services two js start======================*/
    var swiper = new Swiper(".our-services-two-slider", {
      slidesPerView: 3,
      spaceBetween: 20,
      loop: true,
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        412: {
          slidesPerView: 2,
        },
        640: {
          slidesPerView: 2,
        },
        768: {
          slidesPerView: 3,
        },
        1024: {
          slidesPerView: 4,
        },
        1199: {
          slidesPerView: 5,
        },
      },
    });
    /*=================== our services two js end======================*/

    /*=================== our services js start======================*/
    var swiper = new Swiper(".servics-slider", {
      slidesPerView: 3,
      spaceBetween: 20,
      loop: true,
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        640: {
          slidesPerView: 1,
        },
        768: {
          slidesPerView: 2,
        },
        1499: {
          slidesPerView: 3,
        },
      },
    });
    /*=================== our services js end======================*/

    /*=================== aos animation start =====================*/
    AOS.init();
    /*=================== aos animation end =====================*/

    // ============== Mobile Nav Menu Dropdown Js Start =======================
    function toggleSubMenu() {
      if ($(window).width() <= 991) {
        $(".has-submenu")
          .off("click")
          .on("click", function () {
            $(this)
              .toggleClass("active")
              .siblings(".has-submenu")
              .removeClass("active")
              .find(".nav-submenu")
              .slideUp(300);
            $(this).find(".nav-submenu").stop(true, true).slideToggle(300);
          });
      } else {
        $(".has-submenu").off("click");
      }
    }

    toggleSubMenu();
    $(window).resize(toggleSubMenu);
    // ============== Mobile Nav Menu Dropdown Js End =======================

    // ===================== Scroll Back to Top Js Start ======================
    var progressPath = document.querySelector(".progress-wrap path");
    var pathLength = progressPath.getTotalLength();
    progressPath.style.transition = progressPath.style.WebkitTransition =
      "none";
    progressPath.style.strokeDasharray = pathLength + " " + pathLength;
    progressPath.style.strokeDashoffset = pathLength;
    progressPath.getBoundingClientRect();
    progressPath.style.transition = progressPath.style.WebkitTransition =
      "stroke-dashoffset 10ms linear";
    var updateProgress = function () {
      var scroll = $(window).scrollTop();
      var height = $(document).height() - $(window).height();
      var progress = pathLength - (scroll * pathLength) / height;
      progressPath.style.strokeDashoffset = progress;
    };
    updateProgress();
    $(window).scroll(updateProgress);
    var offset = 50;
    var duration = 550;
    jQuery(window).on("scroll", function () {
      if (jQuery(this).scrollTop() > offset) {
        jQuery(".progress-wrap").addClass("active-progress");
      } else {
        jQuery(".progress-wrap").removeClass("active-progress");
      }
    });
    jQuery(".progress-wrap").on("click", function (event) {
      event.preventDefault();
      jQuery("html, body").animate({ scrollTop: 0 }, duration);
      return false;
    });
    // ===================== Scroll Back to Top Js End ======================

    // ========================== add active class to navbar menu current page Js Start =====================
    function dynamicActiveMenuClass(selector) {
      let FileName = window.location.pathname.split("/").reverse()[0];

      if (FileName === "" || FileName === "index.html") {
        selector
          .find("li.nav-menu__item.has-submenu")
          .eq(0)
          .addClass("activePage");
      } else {
        selector.find("li").removeClass("activePage");

        selector.find("li").each(function () {
          let anchor = $(this).find("a");
          if ($(anchor).attr("href") == FileName) {
            $(this).addClass("activePage");
          }
        });

        selector.children("li").each(function () {
          if ($(this).find(".activePage").length) {
            $(this).addClass("activePage");
          }
        });
      }
    }

    if ($("ul").length) {
      dynamicActiveMenuClass($("ul"));
    }
    // ========================== add active class to navbar menu current page Js End =====================

    // =========================  Feature 9 Js Start ==============
    $(".payment-solutions__item").on("click", function () {
      $(".payment-solutions__item").removeClass("active");
      $(this).toggleClass("active");
    });
    // =========================  Feature 9 Brand Js End ==============

    /*================================== join js start ==========================*/
    var swiper = new Swiper(".brand-siper", {
      slidesPerView: 1,
      spaceBetween: 10,
      loop: true,
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      breakpoints: {
        640: {
          slidesPerView: 2,
          spaceBetween: 20,
        },
        768: {
          slidesPerView: 4,
          spaceBetween: 40,
        },
        1024: {
          slidesPerView: 6,
          spaceBetween: 50,
        },
      },
    });
    /*================================== join js end ==========================*/

    // ================================= Brand slider Start =========================
    var brandThreeSlider = new Swiper(".brand-three-slider", {
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      autoplay: true,
      speed: 1500,
      grabCursor: true,
      loop: true,
      slidesPerView: 7,
      breakpoints: {
        300: {
          slidesPerView: 2,
        },
        575: {
          slidesPerView: 3,
        },
        768: {
          slidesPerView: 4,
        },
        992: {
          slidesPerView: 5,
        },
        1200: {
          slidesPerView: 6,
        },
        1400: {
          slidesPerView: 7,
        },
      },
    });
    // ================================= Brand slider End =========================

    /*===========================================
	=         Marquee Active         =
    =============================================*/
    if ($(".marquee_mode").length) {
      $(".marquee_mode").marquee({
        speed: 100,
        gap: 0,
        delayBeforeStart: 0,
        direction: $("html").attr("dir") === "rtl" ? "left" : "right",
        duplicated: true,
        pauseOnHover: true,
        startVisible: true,
        direction: "left",
      });
    }

    /*===========================================
=         Marquee Active         =
  =============================================*/

    /*===================testimonial two js start======================*/
    var swiper = new Swiper(".testimonial-two-slider", {
      slidesPerView: 2,
      loop: true,
      spaceBetween: 20,
      navigation: {
        nextEl: ".testimonial-button-next",
        prevEl: ".testimonial-button-prev",
      },
      breakpoints: {
        0: {
          slidesPerView: 1, // Mobile default
        },
        640: {
          slidesPerView: 1,
        },
        768: {
          slidesPerView: 2,
        },
        1024: {
          slidesPerView: 2,
        },
      },
    });
    /*===================testimonial two js end======================*/

    /*===================testimonial js start======================*/
    var swiper = new Swiper(".testimonial-slider", {
      slidesPerView: 3,
      loop: true,
      spaceBetween: 20,
      navigation: {
        nextEl: ".testimonial-button-next",
        prevEl: ".testimonial-button-prev",
      },
      breakpoints: {
        0: {
          slidesPerView: 1, // Mobile default
        },
        640: {
          slidesPerView: 1,
        },
        768: {
          slidesPerView: 2,
        },
        1024: {
          slidesPerView: 2,
        },
        1199: {
          slidesPerView: 3,
        },
      },
    });
    /*===================testimonial js end======================*/

    // ================== Password Show Hide Js Start ==========
    $(".toggle-password").on("click", function () {
      $(this).toggleClass("active");
      var input = $($(this).attr("id"));
      if (input.attr("type") == "password") {
        input.attr("type", "text");
        $(this).removeClass("ph-bold ph-eye-slash");
        $(this).addClass("ph-bold ph-eye");
      } else {
        input.attr("type", "password");
        $(this).addClass("ph-bold ph-eye-slash");
      }
    });
    // ========================= Password Show Hide Js End ===========================

    // ================================= Brand slider Start =========================
    var swiper = new Swiper(".brand-swiper", {
      loop: true,
      spaceBetween: 20,
      slidesPerView: 1,

      breakpoints: {
        640: {
          slidesPerView: 2,
        },
        768: {
          slidesPerView: 3,
        },
        1024: {
          slidesPerView: 5,
        },
        1280: {
          slidesPerView: 7,
        },
      },
    });
    // ================================= Brand slider End =========================

    // ========================= magnific Popup Js Start =====================
    $(".play-button").magnificPopup({
      type: "iframe",
      removalDelay: 300,
      mainClass: "mfp-fade",
    });
    // ========================= magnific Popup Js End =====================
  });
  // ==========================================
  //      End Document Ready function
  // ==========================================

  // ========================= Preloader Js Start =====================
  $(window).on("load", function () {
    $(".preloder").fadeOut();
  });
  // ========================= Preloader Js End=====================

  // ========================= Header Sticky Js Start ==============
  $(window).on("scroll", function () {
    if ($(window).scrollTop() >= 260) {
      $(".header").addClass("fixed-header");
    } else {
      $(".header").removeClass("fixed-header");
    }
  });
  // ========================= Header Sticky Js End===================
})(jQuery);
