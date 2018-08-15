<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('regi_no',20)->unique();
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('class_id');
            $table->unsignedInteger('section_id');
            $table->unsignedInteger('academic_year_id');
            $table->string('roll_no',20)->nullable();
            $table->string('group',15)->nullable();
            $table->string('shift',15)->nullable();
            $table->string('card_no',50)->nullable();
            $table->string('board_regi_no',50)->nullable();


            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('class_id')->references('id')->on('i_classes');
            $table->foreign('section_id')->references('id')->on('sections');
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->index('regi_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registrations');
    }
}
