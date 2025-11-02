<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    /** @use HasFactory<\Database\Factories\SignatureFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'journal_id',
        'signer_id',
        'signer_role',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signer_id');
    }
}
