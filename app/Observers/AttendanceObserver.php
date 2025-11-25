<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Journal;

class AttendanceObserver
{
    /**
     * Handle the Attendance "saved" event.
     */
    public function saved(Attendance $attendance): void
    {


        $this->syncAttendance($attendance);
    }

    /**
     * Handle the Attendance "deleted" event.
     */
    public function deleted(Attendance $attendance): void
    {


        $this->syncDeletion($attendance);
    }

    protected function syncAttendance(Attendance $sourceAttendance)
    {
        $studentId = $sourceAttendance->student_id;
        $date = $sourceAttendance->date;
        $status = $sourceAttendance->status;

        // Since we now have unique constraint on (student_id, date),
        // there should only be one attendance record per student per day.
        // No need to sync across multiple journals anymore.

        // This method is kept for potential future use but does nothing now
        // as the unique constraint ensures only one record exists.
    }

    protected function syncDeletion(Attendance $sourceAttendance)
    {
        // Since we now have unique constraint on (student_id, date),
        // there's only one attendance record per student per day.
        // No need to delete other records as they don't exist.

        // This method is kept for potential future use but does nothing now.
    }
}
