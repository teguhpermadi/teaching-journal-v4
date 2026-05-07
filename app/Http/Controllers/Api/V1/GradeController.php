<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\GradeResource;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        return GradeResource::collection(Grade::all());
    }

    public function show(Grade $grade)
    {
        return new GradeResource($grade);
    }
}
