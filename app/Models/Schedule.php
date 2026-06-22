<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'days',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'array',
        ];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
