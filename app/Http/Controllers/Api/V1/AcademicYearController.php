<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        return AcademicYearResource::collection(AcademicYear::all());
    }

    public function show(AcademicYear $academicYear)
    {
        return new AcademicYearResource($academicYear);
    }
}
