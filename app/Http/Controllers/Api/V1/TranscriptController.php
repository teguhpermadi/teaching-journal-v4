<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TranscriptResource;
use App\Models\Transcript;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    public function index(Request $request)
    {
        $transcripts = Transcript::paginate($request->get('per_page', 15));
        return TranscriptResource::collection($transcripts);
    }

    public function show(Transcript $transcript)
    {
        return new TranscriptResource($transcript);
    }
}
