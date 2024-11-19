

/**
 * Prompt for order cancellation reason and add to the order's enrolment log entries.
 */
(function ($) {

  $('form#posts-filter').on('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const action = formData.get('action');
    if( action === 'mark_cancelled' ) {
      const orderIds = formData.getAll('post[]');
      console.log(orderIds);
      if( orderIds.length > 0 ) {
        let cancellationReasons = {};
        orderIds.forEach( ( orderId ) => {
          const isOrderCompleted = $(`#post-${orderId}`).hasClass('status-wc-completed');
          if( isOrderCompleted ) {
            // Prompt for reason.
            const reason = prompt(`Please provide a reason for cancelling order ${orderId}?`);
            if( reason !== null && reason !== '' && reason !== ' ' ) {
              cancellationReasons[ parseInt(orderId) ] = reason;
            }
          }
        });
        // Add reason to post body.
        $(form).prepend(`<input type='hidden' name='enrolment_log_order_cancellations' value='${ JSON.stringify( cancellationReasons ) }' />`);
        // Use action hook to intercept the post.
        // Update the enrolment log entries by adding the reason as a comment to each.
      }
    }
    form.submit();
  });

})(jQuery);
