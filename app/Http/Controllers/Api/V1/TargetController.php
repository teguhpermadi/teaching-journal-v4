<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TargetResource;
use App\Models\Target;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    public function index()
    {
        return TargetResource::collection(Target::all());
    }

    public function show(Target $target)
    {
        return new TargetResource($target);
    }
}
