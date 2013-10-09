(function($) {
  var doc = $(document);


  //--------------------------------------------------------
  // Search products
  //
  // This function will make an AJAX request to a php file that
  // search through a json files that contains a bunch of products
  //--------------------------------------------------------
  function ajaxSearchProducts() {
    doc.on('keyup', '.search', function(e) {
      var keyCode = (window.event) ? e.which : e.keyCode;
      var resultList = $('.search-results');

      //--------------------------------------------------------
      // Check if the user press a key with number or letters or
      // backspace
      //--------------------------------------------------------
      if(keyCode <= 90 && keyCode >= 48 || keyCode == 8) {
        var value = 's=' + $(this).val();

        $.ajax({
          url: 'loadProducts.php',
          data: value,
          type: 'POST',
          dataType: 'json',
          success: function(data) {
            var results = [];
            var oddEven;

            $.each(data, function(key, info) {
              if(key % 2 == 0) {
                oddEven = 'even';
              } else {
                oddEven = 'odd';
              }

              results.push('<li class="'+oddEven+'" data-id="'+info.produkt_id+'"><a href="#">'+info.produkt_namn+' <span>('+info.kategori_namn+')</span></a></li>');
            });
            resultList.html(results);
            resultList.animate({opacity: 'show'}, {duration: 200, queue: false});
          }
        });

      //--------------------------------------------------------
      // Else, if they hit escape; empty the result list and hide it
      //--------------------------------------------------------
      } else if(keyCode == 27) {
        $(this).val('');
        resultList.animate({opacity: 'hide'}, {duration: 200}).queue(function() {
          $(this).html('');
          $(this).dequeue();
        });
      }
    });

  }


  //--------------------------------------------------------
  // Run on document ready
  //--------------------------------------------------------
  $(function() {
    ajaxSearchProducts();
  });


}(jQuery));