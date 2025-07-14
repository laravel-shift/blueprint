<?php

namespace Database\Factories\Screening;

use App\Models\Appointment\AppointmentType;
use App\Models\QuestionType;
use App\Models\Screening\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScreeningQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'question_type_id' => QuestionType::factory(),
        ];
    }
}
