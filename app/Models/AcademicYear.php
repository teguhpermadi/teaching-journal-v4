<?php

namespace App\Models;

use App\SemesterEnum;
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
        'saturday_is_active',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'active' => 'boolean',
        'saturday_is_active' => 'boolean',
        'semester' => SemesterEnum::class,
    ];

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function academicEvents()
    {
        return $this->hasMany(AcademicEvent::class);
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
