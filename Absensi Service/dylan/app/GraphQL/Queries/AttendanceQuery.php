<?php

namespace App\GraphQL\Queries;

use App\Models\Attendance;

final class AttendanceQuery
{
    /**
     * Return all attendances with optional date range filter.
     *
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $query = Attendance::query();

        if (isset($args['start_date'])) {
            $query->where('date', '>=', $args['start_date']);
        }

        if (isset($args['end_date'])) {
            $query->where('date', '<=', $args['end_date']);
        }

        if (isset($args['employee_id'])) {
            $query->where('employee_id', $args['employee_id']);
        }

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }

        return $query->orderBy('date', 'desc')->get();
    }
}
