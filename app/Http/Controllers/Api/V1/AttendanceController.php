<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AttendanceResource;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Attendance::query()->with('student');

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $attendances = $query->paginate($request->get('per_page', 15));

        return AttendanceResource::collection($attendances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required',
        ]);

        $attendance = Attendance::create($validated);

        return new AttendanceResource($attendance->load('student'));
    }

    /**
     * Store multiple attendance records at once.
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required',
        ]);

        $results = [];
        foreach ($validated['attendances'] as $item) {
            $attendance = Attendance::updateOrCreate(
                ['student_id' => $item['student_id'], 'date' => $validated['date']],
                ['status' => $item['status']]
            );
            $results[] = $attendance;
        }

        return AttendanceResource::collection(collect($results)->load('student'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        return new AttendanceResource($attendance->load('student'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'sometimes|required',
        ]);

        $attendance->update($validated);

        return new AttendanceResource($attendance->load('student'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json(null, 204);
    }

    /**
     * Get attendance report/statistics.
     */
    public function report(Request $request)
    {
        $query = Attendance::query();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $stats = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json($stats);
    }
}
