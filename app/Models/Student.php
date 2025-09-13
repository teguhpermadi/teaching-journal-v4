<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'gender',
        'nisn',
        'nis',
        'photo',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
