<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::paginate($request->get('per_page', 15));
        return StudentResource::collection($students);
    }

    public function show(Student $student)
    {
        return new StudentResource($student);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'name' => 'required|string|max:255',
            'nis' => 'nullable|string|max:255',
            'nisn' => 'nullable|string|max:255',
            'gender' => 'required|in:L,P',
        ]);

        $student = Student::create($validated);
        return new StudentResource($student);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'nis' => 'nullable|string|max:255',
            'nisn' => 'nullable|string|max:255',
            'gender' => 'sometimes|required|in:L,P',
        ]);

        $student->update($validated);
        return new StudentResource($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(null, 204);
    }
}
