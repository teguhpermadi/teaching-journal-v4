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

        Attendance::withoutEvents(function () use ($sourceAttendance, $studentId, $date, $status) {
            // 1. Update existing other attendance records
            Attendance::where('student_id', $studentId)
                ->where('date', $date)
                ->where('id', '!=', $sourceAttendance->id)
                ->update(['status' => $status]);

            // 2. Create missing attendance records for other journals
            // Find journals on this date that don't have attendance for this student
            $journals = Journal::where('date', $date)
                ->where('id', '!=', $sourceAttendance->journal_id)
                ->whereDoesntHave('attendance', function ($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                })
                ->get();

            foreach ($journals as $journal) {
                // Check if student is in this grade
                if ($journal->grade->students()->where('students.id', $studentId)->exists()) {
                    Attendance::create([
                        'journal_id' => $journal->id,
                        'student_id' => $studentId,
                        'date' => $date,
                        'status' => $status,
                    ]);
                }
            }
        });
    }

    protected function syncDeletion(Attendance $sourceAttendance)
    {
        $studentId = $sourceAttendance->student_id;
        $date = $sourceAttendance->date;

        // Delete other attendance records for the same student and date
        // Using builder delete() to avoid firing events recursively
        Attendance::where('student_id', $studentId)
            ->where('date', $date)
            ->where('id', '!=', $sourceAttendance->id)
            ->delete();
    }
}
