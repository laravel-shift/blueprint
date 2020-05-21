<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('countries_id')->constrained('countries')->cascadeOnDelete();
            $table->string('country_code');
            $table->foreign('country_code')->references('code')->on('countries')->onDelete('cascade');
            $table->string('ccid');
            $table->foreign('ccid')->references('ccid')->on('countries')->onDelete('cascade');
            $table->string('c_code');
            $table->foreign('c_code')->references('code')->on('countries')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('states');
    }
}
