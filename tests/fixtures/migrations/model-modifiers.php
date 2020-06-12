<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modifiers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('name', 1000)->unique()->charset('utf8');
            $table->string('content')->default('');
            $table->float('amount', 9, 3);
            $table->double('total', 10, 2);
            $table->decimal('overflow', 99, 99);
            $table->char('ssn', 11);
            $table->enum('role', ["user","admin","owner"]);
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
        Schema::dropIfExists('modifiers');
    }
}
