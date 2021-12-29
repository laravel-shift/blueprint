<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'certificate_type_id',
        'reference',
        'document',
        'expiry_date',
        'remarks',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'certificate_type_id' => 'integer',
        'expiry_date' => 'date',
    ];

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }
}
