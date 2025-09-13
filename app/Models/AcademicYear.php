<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Grade;

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

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public static function setActive($yearId)
    {
        // Menonaktifkan semua tahun ajaran
        self::query()->update(['active' => false]);

        // Mengaktifkan tahun ajaran yang diberikan
        self::where('id', $yearId)->update(['active' => true]);

        return self::where('active', true)->first();
    }
}
