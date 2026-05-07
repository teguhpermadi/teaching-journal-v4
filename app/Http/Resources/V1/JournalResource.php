<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'grade' => new GradeResource($this->whenLoaded('grade')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'user' => new UserResource($this->whenLoaded('user')),
            'date' => $this->date->format('Y-m-d'),
            'chapter' => $this->chapter,
            'activity' => $this->activity,
            'notes' => $this->notes,
            'status' => $this->status,
            'main_targets' => MainTargetResource::collection($this->mainTargets),
            'targets' => TargetResource::collection($this->targets),
            'is_fully_signed' => $this->isFullySigned(),
            'signatures' => [
                'owner' => $this->getOwnerSignature(),
                'headmaster' => $this->getHeadmasterSignature(),
            ],
            'media' => [
                'activity_photos' => $this->getMedia('activity_photos')->map(fn($m) => [
                    'id' => $m->id,
                    'url' => $m->getFullUrl(),
                    'thumb' => $m->getFullUrl('activity_photos_compressed'),
                ]),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
