<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\LogEntry;

class LogQuery {

	/**
	 * From the wp_enrolment_log table.
	 */
	public int $course_id;
	public int $order_id;
	public int $attendee_id;
	public array $status; // publish, cancelled, closed, pending

}
