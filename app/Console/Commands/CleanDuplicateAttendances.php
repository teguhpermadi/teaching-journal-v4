<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateAttendances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:clean-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate attendance records, keeping the latest one per student per date.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting attendance cleanup...');

        $duplicates = Attendance::select('student_id', 'date', DB::raw('count(*) as count'))
            ->groupBy('student_id', 'date')
            ->having('count', '>', 1)
            ->get();

        $this->info('Found ' . $duplicates->count() . ' groups of duplicates.');

        foreach ($duplicates as $duplicate) {
            $this->info("Processing student {$duplicate->student_id} on {$duplicate->date->format('Y-m-d')}...");

            $attendances = Attendance::where('student_id', $duplicate->student_id)
                ->where('date', $duplicate->date)
                ->orderBy('updated_at', 'desc')
                ->get();

            // Keep the first one (latest updated_at), delete the rest
            $toKeep = $attendances->shift();

            foreach ($attendances as $attendance) {
                $attendance->delete();
            }

            $this->info("Kept attendance ID: {$toKeep->id}, deleted " . $attendances->count() . " duplicates.");
        }

        $this->info('Cleanup completed.');
    }
}
