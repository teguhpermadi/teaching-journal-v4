<?php

namespace App\Models;

use App\StatusAttendanceEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'student_id',
        'journal_id',
        'date',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => StatusAttendanceEnum::class,
    ];

    protected static function booted() : void
    {
        // add global scope to filter attendance by academic year active
        static::addGlobalScope('academicYearActive', function (Builder $builder) {
            $academicYearActive = AcademicYear::active()->first();
            $builder->where('date', '>=', $academicYearActive->date_start)
                ->where('date', '<=', $academicYearActive->date_end);
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function scopeMyStudents(Builder $builder)
    {
        // get my subjects
        $subjects = Subject::mySubjects()->with('grade')->get();
        $students = $subjects->pluck('grade.students')->flatten();
        $journals = Journal::myJournals()->get();
        $builder->whereHas('student', function ($query) use ($students) {
            $query->whereIn('students.id', $students->pluck('id'));
        });
        $builder->whereHas('journal', function ($query) use ($journals) {
            $query->whereIn('journals.id', $journals->pluck('id'));
        });
        return $builder;
    }
}
