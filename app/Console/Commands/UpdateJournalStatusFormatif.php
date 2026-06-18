<?php

namespace App\Console\Commands;

use App\Models\Journal;
use Illuminate\Console\Command;

class UpdateJournalStatusFormatif extends Command
{
    protected $signature = 'journal:update-status-formatif';

    protected $description = 'Update journal status from Penilaian to Formatif';

    public function handle()
    {
        $count = Journal::where('status', 'Penilaian')->count();
        Journal::where('status', 'Penilaian')->update(['status' => 'Formatif']);
        $this->info("Updated {$count} journal(s) from 'Penilaian' to 'Formatif'.");
    }
}
