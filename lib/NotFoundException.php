<?php

namespace Lasntg\Admin\EnrolmentLog;

use Exception;

class NotFoundException extends Exception {

	// Redefine the exception so message isn't optional
	public function __construct($message, $code = 0, Throwable $previous = null) {
		// some code

		// make sure everything is assigned properly
		parent::__construct($message, $code, $previous);
	}

}
