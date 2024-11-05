<?php

namespace Lasntg\Admin\EnrolmentLog;

use DateTimeImmutable;

class LogEntry {

	// wp_posts
	public int $post_id;
	public int $author_id;
	public string $status; // publish, cancelled
	public DateTimeImmutable $created;
	public DateTimeImmutable $modified;

	// wp_enrolment_log
	public int $course_id;
	public int $order_id;
	public int $attendee_id;
	public string $comment;

	public function toArray(): array {
		$data = get_object_vars( $this );
		return $data;
	}

}
