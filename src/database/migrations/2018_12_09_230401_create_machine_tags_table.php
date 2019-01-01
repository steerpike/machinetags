<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachineTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machine_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->index(['namespace', 'predicate', 'value']);
            $table->string('namespace');
            $table->string('predicate');
            $table->string('value');
            $table->timestamps();
        });
        Schema::create('machine_taggables', function (Blueprint $table) {
            $table->integer('machine_tag_id')->unsigned();
            $table->integer('machine_taggables_id')->unsigned();
            $table->string('machine_taggables_type');

            $table->foreign('machine_tag_id')->references('id')
                ->on('machine_tags')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('machine_tags');
        Schema::dropIfExists('machine_taggables');
    }
}
