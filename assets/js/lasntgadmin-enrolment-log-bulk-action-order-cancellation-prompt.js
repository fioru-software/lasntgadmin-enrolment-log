

/**
 * Prompt for order cancellation reason and add to the order's enrolment log entries.
 */
(function ($) {

  $('form#posts-filter').on('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    let valid = true;
    const formData = new FormData(form);
    const action = formData.get('action');
    if( action === 'mark_cancelled' ) {
      const orderIds = formData.getAll('post[]');
      if( orderIds.length > 0 ) {
        let cancellationReasons = {};
        for( let i=0; i<orderIds.length; i++ ) {
          let orderId = orderIds[i];
          const isOrderCompleted = $(`#post-${orderId}`).hasClass('status-wc-completed');
          if( isOrderCompleted ) {
            // Prompt for reason.
            const reason = prompt(`Please provide a reason for cancelling order ${orderId}?`);
            // Ensure reason is not empty
            if( /^\w{3,}/.test(reason) ) {
              cancellationReasons[ parseInt(orderId) ] = reason.trim();
            } else {
              valid = false;
              alert("Reasons are required for cancelling completed enrolments.");
              $('div.notice').remove();
              $('hr.wp-header-end').after('<div class="notice notice-error"><p>Reasons are required when cancelling completed enrolments.</p></div>');
              break;
            }
          }
        }
        // Add reason to post body.
        $(form).prepend(`<input type='hidden' name='enrolment_log_order_cancellations' value='${ JSON.stringify( cancellationReasons ) }' />`);
        // Use action hook to intercept the post.
        // Update the enrolment log entries by adding the reason as a comment to each.
      }
    }
    if( valid ) {
      form.submit();
    }
  });

})(jQuery);
