<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\JournalResource;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Journal::query()
            ->with(['academicYear', 'grade', 'subject', 'user']);

        if ($request->has('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }

        if ($request->has('my_journals')) {
            $query->myJournals();
        }

        $journals = $query->paginate($request->get('per_page', 15));

        return JournalResource::collection($journals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'subject_id' => 'required|exists:subjects,id',
            'grade_id' => 'required|exists:grades,id',
            'date' => 'required|date',
            'chapter' => 'required|string|max:255',
            'activity' => 'required|string',
            'notes' => 'nullable|string',
            'status' => 'required',
            'main_target_id' => 'required|array',
            'target_id' => 'required|array',
        ]);

        $validated['user_id'] = Auth::id();

        $journal = Journal::create($validated);

        if ($request->hasFile('activity_photos')) {
            $journal->addMultipleMediaFromRequest(['activity_photos'])
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection('activity_photos');
                });
        }

        return new JournalResource($journal->load(['academicYear', 'grade', 'subject', 'user']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Journal $journal)
    {
        return new JournalResource($journal->load(['academicYear', 'grade', 'subject', 'user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journal $journal)
    {
        Gate::authorize('update', $journal);

        $validated = $request->validate([
            'chapter' => 'sometimes|required|string|max:255',
            'activity' => 'sometimes|required|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required',
            'main_target_id' => 'sometimes|required|array',
            'target_id' => 'sometimes|required|array',
        ]);

        $journal->update($validated);

        if ($request->hasFile('activity_photos')) {
            $journal->addMultipleMediaFromRequest(['activity_photos'])
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection('activity_photos');
                });
        }

        return new JournalResource($journal->load(['academicYear', 'grade', 'subject', 'user']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Journal $journal)
    {
        Gate::authorize('delete', $journal);

        $journal->delete();

        return response()->json(null, 204);
    }

    /**
     * Sign the journal as owner.
     */
    public function signOwner(Request $request, Journal $journal)
    {
        $request->validate([
            'signature_data' => 'required|string', // Base64
        ]);

        try {
            $signature = $journal->signAsOwner($request->signature_data);
            return response()->json([
                'message' => 'Journal signed as owner successfully',
                'signature' => $signature,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Sign the journal as headmaster.
     */
    public function signHeadmaster(Request $request, Journal $journal)
    {
        $request->validate([
            'signature_data' => 'required|string', // Base64
        ]);

        try {
            $signature = $journal->signAsHeadmaster($request->signature_data);
            return response()->json([
                'message' => 'Journal signed as headmaster successfully',
                'signature' => $signature,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Get calendar events.
     */
    public function calendar(Request $request)
    {
        $journals = Journal::myJournals()->get();
        $events = $journals->map(fn($j) => $j->toCalendarEvent());

        return response()->json($events);
    }
}
