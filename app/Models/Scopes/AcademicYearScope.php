<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\AcademicYear;

class AcademicYearScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if(AcademicYear::active()->exists()) {
            $builder->where('academic_year_id', AcademicYear::active()->first()->id);
        }
    }
}
