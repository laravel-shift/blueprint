<?php

namespace App\Models\Screening;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'report_id',
        'appointment_type_id',
        'question_type_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'report_id' => 'integer',
        'appointment_type_id' => 'integer',
        'question_type_id' => 'integer',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Appointment\AppointmentType::class);
    }

    public function questionType(): BelongsTo
    {
        return $this->belongsTo(\App\Models\QuestionType::class);
    }
}
