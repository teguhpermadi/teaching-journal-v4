<?php

namespace App\Observers;

use App\Models\Transcript;
use App\Models\TranscriptStudent;

class TranscriptObserver
{
    /**
     * Handle the Transcript "created" event.
     */
    public function created(Transcript $transcript): void
    {
        $data = [
            'transcript_id' => $transcript->id,
            'academic_year_id' => $transcript->academic_year_id,
            'subject_id' => $transcript->subject_id,
            'grade_id' => $transcript->grade_id,
            'score' => 0,
        ];

        $students = $transcript->grade()->first()->students()->pluck('id');

        foreach ($students as $student) {
            TranscriptStudent::create([
                'transcript_id' => $transcript->id,
                'academic_year_id' => $transcript->academic_year_id,
                'subject_id' => $transcript->subject_id,
                'grade_id' => $transcript->grade_id,
                'student_id' => $student,
                'score' => 0,
            ]);
        }
    }

    /**
     * Handle the Transcript "updated" event.
     */
    public function updated(Transcript $transcript): void
    {
        //
    }

    /**
     * Handle the Transcript "deleted" event.
     */
    public function deleted(Transcript $transcript): void
    {
        TranscriptStudent::where('transcript_id', $transcript->id)->delete();
    }

    /**
     * Handle the Transcript "restored" event.
     */
    public function restored(Transcript $transcript): void
    {
        //
    }

    /**
     * Handle the Transcript "force deleted" event.
     */
    public function forceDeleted(Transcript $transcript): void
    {
        //
    }
}
