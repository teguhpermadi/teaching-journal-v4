<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MainTargetResource;
use App\Models\MainTarget;
use Illuminate\Http\Request;

class MainTargetController extends Controller
{
    public function index()
    {
        return MainTargetResource::collection(MainTarget::all());
    }

    public function show(MainTarget $mainTarget)
    {
        return new MainTargetResource($mainTarget);
    }
}
