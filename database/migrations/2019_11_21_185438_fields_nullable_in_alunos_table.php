<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FieldsNullableInAlunosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->integer('ano')->nullable()->change();
            $table->string('formacao')->nullable()->change();
            $table->string('sexo')->nullable()->change();
            $table->integer('contagem')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->integer('ano')->change();
            $table->string('formacao')->change();
            $table->string('sexo')->change();
            $table->dropColumn('contagem');
        });
    }
}
