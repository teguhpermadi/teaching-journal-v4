<?php

namespace App\Livewire;

use App\Models\Journal;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class RecapJournal extends Component
{
    public $selectedMonth;
    public $selectedYear;

    public $recapData = [];

    public function mount()
    {
        // Default to current month and year
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;
        
        $this->calculateData();
    }

    public function updatedSelectedMonth()
    {
        $this->calculateData();
    }

    public function updatedSelectedYear()
    {
        $this->calculateData();
    }

    public function calculateData()
    {
        // Get all subjects with their owners and grades
        $subjects = \App\Models\Subject::with(['user', 'grade'])
            ->get();

        $this->recapData = [];

        // Define start and end of the selected month
        $startDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        // Pre-fetch journals for the selected period
        $journals = Journal::withoutGlobalScope('sort')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['signatures'])
            ->get()
            ->groupBy('subject_id');

        foreach ($subjects as $subject) {
            if (!$subject->user) continue;

            // Get journals for this subject
            $subjectJournals = $journals->get($subject->id, collect());

            $total = $subjectJournals->count();
            $signed = $subjectJournals->filter(function ($journal) {
                return $journal->signatures->isNotEmpty();
            })->count();
            $unsigned = $total - $signed;

            $this->recapData[] = [
                'user_name' => $subject->user->name,
                'subject_name' => $subject->name,
                'grade_name' => $subject->grade->name ?? 'N/A',
                'total' => $total,
                'signed' => $signed,
                'unsigned' => $unsigned,
            ];
        }

        // Sort by user name, then subject name
        usort($this->recapData, function ($a, $b) {
            $userCompare = strcmp($a['user_name'], $b['user_name']);
            if ($userCompare !== 0) {
                return $userCompare;
            }
            return strcmp($a['subject_name'], $b['subject_name']);
        });
    }

    public function render()
    {
        return view('livewire.recap-journal', [
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ],
            'years' => range(Carbon::now()->year - 2, Carbon::now()->year + 1),
        ]);
    }
}
