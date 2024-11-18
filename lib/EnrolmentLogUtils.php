<?php

namespace Lasntg\Admin\EnrolmentLog;

class EnrolmentLogUtils {

	public static function get_translated_status_name( string $status ): string {
		switch ( $status ) {
			case 'publish':
				return 'Enrolled';
				break;
			case 'pending':
				return 'Pending';
				break;
			case 'closed':
				return 'Removed';
				break;
			case 'cancelled':
				return 'Cancelled';
				break;
			default:
				return $status;
		}
	}
}
