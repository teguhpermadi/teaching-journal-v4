<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Signature extends Model
{
    /** @use HasFactory<\Database\Factories\SignatureFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'journal_id',
        'signer_id',
        'signer_role',
        'signature_path',
        'signature_base64',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    protected $appends = ['signature_url', 'is_signed'];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    /**
     * Check if the signature is signed
     */
    public function getIsSignedAttribute(): bool
    {
        return !is_null($this->signed_at);
    }

    /**
     * Get the signature URL (if stored as file)
     */
    public function getSignatureUrlAttribute(): ?string
    {
        if (empty($this->signature_path)) {
            return null;
        }

        if (str_starts_with($this->signature_path, 'http')) {
            return $this->signature_path;
        }

        return asset('storage/' . $this->signature_path);
    }

    /**
     * Get the signature as base64 data URL
     */
    public function getSignatureDataUrl(): ?string
    {
        if (!empty($this->signature_base64)) {
            return $this->signature_base64;
        }

        if (empty($this->signature_path)) {
            return null;
        }

        try {
            $path = storage_path('app/public/' . $this->signature_path);
            if (file_exists($path)) {
                $mime = mime_content_type($path);
                $data = file_get_contents($path);
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        } catch (\Exception $e) {
            \Log::error('Error getting signature data URL: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Save the signature (hybrid approach)
     */
    public function saveSignature($signatureData, $filename = null): bool
    {
        try {
            // If it's a base64 string
            if (str_starts_with($signatureData, 'data:image')) {
                $this->signature_base64 = $signatureData;
                
                // Extract and save as file if filename is provided
                if ($filename) {
                    $path = 'signatures/' . uniqid() . '.png';
                    $filePath = storage_path('app/public/' . $path);
                    
                    // Ensure directory exists
                    if (!file_exists(dirname($filePath))) {
                        mkdir(dirname($filePath), 0755, true);
                    }
                    
                    // Save the file
                    $image = str_replace('data:image/png;base64,', '', $signatureData);
                    $image = str_replace(' ', '+', $image);
                    file_put_contents($filePath, base64_decode($image));
                    
                    $this->signature_path = $path;
                }
            } 
            // If it's a file path
            else {
                $this->signature_path = $signatureData;
                
                // Convert to base64 for the database
                $filePath = storage_path('app/public/' . $signatureData);
                if (file_exists($filePath)) {
                    $this->signature_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($filePath));
                }
            }
            
            $this->signed_at = now();
            return $this->save();
            
        } catch (\Exception $e) {
            \Log::error('Error saving signature: ' . $e->getMessage());
            return false;
        }
    }
}
