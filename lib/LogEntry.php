<?php

namespace Lasntg\Admin\EnrolmentLog;

use DateTimeImmutable;

class LogEntry {

	/**
	 * From the wp_posts table.
	 */
	public int $post_id;
	public int $author_id;
	public string $status; // Either enrolled, cancelled, amended or pending. Prefixed with lasntgadmin-enrolment-log-.
	public DateTimeImmutable $created;
	public DateTimeImmutable $modified;

	/**
	 * From the wp_enrolment_log table.
	 */
	public int $course_id;
	public int $order_id;
	public int $attendee_id;
	public string $comment;

	/**
	 * From wp_postmeta table.
	 */
	public int $group_id;

	public function to_array(): array {
		$data = get_object_vars( $this );
		return $data;
	}
}
