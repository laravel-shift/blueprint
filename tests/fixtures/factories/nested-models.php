<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Appointment\AppointmentType;
use App\QuestionType;
use App\Screening\Report;
use App\Screening\ScreeningQuestion;

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
