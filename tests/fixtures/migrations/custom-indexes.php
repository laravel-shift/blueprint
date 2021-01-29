<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCooltablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('cooltables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coolcool')->constrained('coolcool')->index('custom_index_coolcool');
            $table->foreignId('foobar')->constrained('foobars')->index('custom_index_foobar');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cooltables');
    }
}
