<?php

namespace Database\Factories\Screening;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Appointment\AppointmentType;
use App\Models\QuestionType;
use App\Models\Screening\Report;
use App\Models\Screening\ScreeningQuestion;

class ScreeningQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScreeningQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'report_id' => Report::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'question_type_id' => QuestionType::factory(),
        ];
    }
}
