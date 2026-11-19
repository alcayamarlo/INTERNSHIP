<?php

namespace App\Models;

use App\Support\Avatar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An office or building picture of a company, stored on the public disk (storage/app/public/companies). */
class CompanyPhoto extends Model
{
    protected $fillable = ['employer_id', 'image_path', 'caption'];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /** Public URL, or null when the file is gone (the gallery then leaves the picture out). */
    public function url(): ?string
    {
        return Avatar::url($this->image_path);
    }
}
