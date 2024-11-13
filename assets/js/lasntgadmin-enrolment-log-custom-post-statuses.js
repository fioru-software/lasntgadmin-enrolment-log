/**
 * Customize edit post Publish metabox
 */
(function ($) {

  const savedStatus = $("input#hidden_post_status").val();
  const dropdown = $('div#post-status-select select');

  $('a.edit-post-status').click( function () {
    const publishedOption = dropdown.find("option[value='publish']");
    publishedOption.text("Enrolled");
  });

  $('input#publish').attr("name", "save").val("Update");

  const targetNode = $('a.edit-post-status').get(0);
  const observer = new MutationObserver( function( mutationList, observer ) {
    for (const mutation of mutationList) {
      if (mutation.type === "attributes") {
        $('input#publish').attr("name", "save").val("Update");
        const isStatusEditLinkVisible = $(mutation.target).is(':visible');
        const isDropdownHidden = ! isStatusEditLinkVisible;
        if( isStatusEditLinkVisible ) {
          const displayedStatus = $('span#post-status-display')
          const selectedOption =  dropdown.val();

          // change draft button text
          $('input#save-post').val(`Save ${ displayedStatus.text() }`);

          // fix dropdown's publish option
          if( selectedOption === 'publish') {
            displayedStatus.text('Enrolled');
          }
        }
      }
    }
  });
  observer.observe( targetNode, { attributes: true, childList: true, subtree: true } );


  dropdown.find("option[value='pending']").remove();
  dropdown.find("option[value='draft']").remove();

  let publishedOption = dropdown.find("option[value='publish']");
  let cancelledOption = dropdown.find("option[value='cancelled']");
  let pendingOption = dropdown.find("option[value='pending']");
  let closedOption = dropdown.find("option[value='closed']"); // removed

  if( ! publishedOption.length ) {
    publishedOption = $("<option></option>").val('publish').text('Enrolled');
    dropdown.append( publishedOption );
  }

  if( ! closedOption.length ) {
    closedOption = $("<option></option>").val('closed').text('Removed');
    dropdown.append( closedOption );
  }

  if( ! cancelledOption.length ) {
    cancelledOption = $("<option></option>").val('cancelled').text('Cancelled');
    dropdown.append( cancelledOption );
  }

  if( ! pendingOption.length ) {
    pendingOption = $("<option></option>").val('pending').text('Pending');
    dropdown.append( pendingOption );
  }

  if( 'cancelled' === savedStatus ) {
    $('span#post-status-display').text('Cancelled');
    cancelledOption.attr('selected', 'selected' );
  }

  if( 'publish' === savedStatus ) {
    $('span#post-status-display').text('Enrolled');
    publishedOption.attr('selected', 'selected' );
  }

  if( 'closed' === savedStatus ) {
    $('span#post-status-display').text('Removed');
    closedOption.attr('selected', 'selected' );
  }

  if( 'pending' === savedStatus ) {
    $('span#post-status-display').text('Pending');
    pendingOption.attr('selected', 'selected' );
  }

})(jQuery);
