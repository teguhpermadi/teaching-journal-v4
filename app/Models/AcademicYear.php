<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicYearFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'year',
        'semester',
        'headmaster_name',
        'headmaster_nip',
        'date_start',
        'date_end',
        'active',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'active' => 'boolean',
    ];
}
