var infinitePostLoader = (function () {
  function throttle(t,u,a){var e,i,r,o=null,c=0;a=a||{};function p(){c=!1===a.leading?0:Date.now(),o=null,r=t.apply(e,i),o||(e=i=null)}return function(){var n=Date.now();c||!1!==a.leading||(c=n);var l=u-(n-c);return e=this,i=arguments,l<=0||u<l?(o&&(clearTimeout(o),o=null),c=n,r=t.apply(e,i),o||(e=i=null)):o||!1===a.trailing||(o=setTimeout(p,l)),r}}

  var $document = $(document);
  var body = $('html, body');
  var isLoading = false;
  var $postsContainer = $('#fcipl-posts');
  var ajaxCall;
  var isLoadingOnScroll = $postsContainer.data('is-loading-on-scroll');
  var callBeforeLoading = [];
  var callAfterLoading = [];
  var params = {
    ppp: undefined,
    ppp_ajax: undefined,
    pageNumber: undefined,
    search_key: undefined,
    cat: undefined,
    year: undefined,
    monthnum: undefined,
    day: undefined,
    author: undefined,
    front_page: undefined,
  };

  function parseData() {
    params.ppp = $postsContainer.data('ppp');
    params.ppp_ajax = $postsContainer.data('ppp-ajax');
    if (params.pageNumber == undefined) {
      params.pageNumber = (params.ppp / params.ppp_ajax) + 1;
    }
    params.search_key = $postsContainer.data('search-key');
    params.cat = $postsContainer.data('cat');
    params.year = $postsContainer.data('year');
    params.monthnum = $postsContainer.data('monthnum');
    params.day = $postsContainer.data('day');
    params.author = $postsContainer.data('author');
    params.front_page = $postsContainer.data('front-page');
  }

  function callFromArray(arr) {
    for (var i = 0; i < arr.length; i += 1) {
      arr[i]();
    }
  }

  function beforeLoad() {
    callFromArray(callBeforeLoading);

    isLoading = true;
    $('[data-load="loading"]').addClass('is-visible');
  }

  function afterLoad() {
    isLoading = false;

    if ($('[data-load="loading"]').hasClass('is-off')) {
      $('.js-load-more').addClass('is-off');
    } else {
      $('.js-load-more').removeClass('is-off');
    }

    callFromArray(callAfterLoading);
  }

  function loadPosts(isReplace) {
    if (isReplace == null) {
      isReplace = false;
    }

    beforeLoad();

    if (ajaxCall != undefined) {
      ajaxCall.abort();
    }
    parseData();
    var str = '&pageNumber=' + params.pageNumber + '&ppp_ajax=' + params.ppp_ajax + '&action=fcipl_more_post&template_ajax=' + $postsContainer.data( 'template-ajax' ) + 
              '&template_ajax_js=' + $postsContainer.data( 'template-ajax-js' ) + ( typeof params.search_key !== 'undefined' ? '&search_key=' + params.search_key : '' ) + 
              ( typeof params.cat !== 'undefined' ? '&cat=' + params.cat : '' ) + ( typeof params.year !== 'undefined' ? '&year=' + params.year : '' ) + 
              ( typeof params.monthnum !== 'undefined' ? '&monthnum=' + params.monthnum : '' ) + ( typeof params.day !== 'undefined' ? '&day=' + params.day : '' ) +
              ( typeof params.author !== 'undefined' ? '&author=' + params.author : '' ) + ( typeof params.front_page !== 'undefined' ? '&front_page=' + params.front_page : '' );
    ajaxCall = $.ajax({
      type: 'POST',
      dataType: 'html',
      url: fcipl_posts.ajaxurl,
      data: str,
      success: function( res ) {
        params.pageNumber++;
        $( '#more_posts' ).removeClass( 'is-visible' );
        var $res = $(res);
        if ( $res.length ) {
          if (isReplace) {
            $postsContainer.html($res);
          } else {
            $postsContainer.append($res);
          }
          $( '[data-load="loading"]' ).removeClass( 'is-visible' );
        } else {
          $( '[data-load="loading"]' ).removeClass( 'is-visible' );
        }
        afterLoad();
      },
      error: function( jqXHR, textStatus, errorThrown ) {
        console.error(jqXHR + ' :: ' + textStatus + ' :: ' + errorThrown);
        afterLoad();
      }
    });

    return false;
  }

  function checkScroll() {
    if (
      !isLoading
      && document.URL.indexOf('difference') === -1
      && $postsContainer.length
      && $postsContainer.get(0).getBoundingClientRect().bottom - window.innerHeight <= -20
    ) {
      loadPosts();
    }
  };

  var checkScrollThrottled = throttle(checkScroll, 200);

  if (isLoadingOnScroll) {
    $document.on('scroll', checkScrollThrottled);
    $('body').bind('touchmove', checkScrollThrottled);

    $document.ready(checkScrollThrottled);
  }

  return {
    setParam: function (key, value) {
      $postsContainer.data(key, value);
    },
    registerBeforeLoading: function (func) {
      callBeforeLoading.push(func);

      return function () {
        callBeforeLoading.splice(callBeforeLoading.indexOf(func), 1);
      };
    },
    registerAfterLoading: function (func) {
      callAfterLoading.push(func);

      return function () {
        callAfterLoading.splice(callAfterLoading.indexOf(func), 1);
      };
    },
    nextPage: function () {
      if (isLoading) {
        return;
      }
      loadPosts();
    },
    reload: function () {
      params.pageNumber = 1;
      $('[data-load="loading"]').removeClass('is-off');
      loadPosts(true);
    },
    scrollToTop: function (offset) {
      if (offset == null) {
        offset = 0;
      }

      body.stop().animate({ scrollTop: $postsContainer.position().top + offset }, 500, 'swing');
    },
  };
})();
