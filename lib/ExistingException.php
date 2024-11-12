<?php

namespace Lasntg\Admin\EnrolmentLog;

use Exception;

class ExistingException extends Exception {

	/**
	 * Redefine the exception so message isn't optional.
	 */
	public function __construct( $message, $code = 0, Throwable $previous = null ) {
		// Make sure everything is assigned properly.
		parent::__construct( $message, $code, $previous );
	}
}
