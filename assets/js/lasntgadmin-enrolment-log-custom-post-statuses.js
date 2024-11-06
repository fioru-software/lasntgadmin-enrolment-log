/**
 * Add cancelled option to dropdown.
 */
(function ($) {

  const dropdown = $('div#post-status-select select');

  dropdown.find("option[value='pending']").remove();
  dropdown.find("option[value='draft']").remove();

  let publishedOption = dropdown.find("option[value='publish']");
  let cancelledOption = dropdown.find("option[value='cancelled']");

  const selectedValue = $("input#hidden_post_status").val();

  if( ! publishedOption.length ) {
    publishedOption = $("<option></option>").val('publish').text('Published');
    dropdown.append( publishedOption );
  }

  if( ! cancelledOption.length ) {
    cancelledOption = $("<option></option>").val('cancelled').text('Cancelled');
    dropdown.append( cancelledOption );
  }

  if( 'cancelled' === selectedValue ) {
    $('span#post-status-display').text('Cancelled');
    cancelledOption.attr('selected', 'selected' );
  }

  if( 'publish' === selectedValue ) {
    $('span#post-status-display').text('Published');
    publishedOption.attr('selected', 'selected' );
  }


})(jQuery);
