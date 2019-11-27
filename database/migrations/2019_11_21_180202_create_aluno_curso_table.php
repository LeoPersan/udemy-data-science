<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlunoCursoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aluno_curso', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('aluno_id');
            $table->unsignedBigInteger('curso_id');
            $table->date('inscricao')->nullable();
            $table->date('ult_acesso')->nullable();
            $table->date('data_avaliacao')->nullable();
            $table->tinyInteger('avaliacao')->nullable();
            $table->string('comentario')->nullable();
            $table->decimal('progresso',4,2)->nullable();
            $table->smallInteger('perguntas_feitas')->nullable();
            $table->smallInteger('perguntas_respondidas')->nullable();
            $table->date('diploma')->nullable();
            $table->timestamps();
            $table->foreign('aluno_id')->on('alunos')->references('id')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('curso_id')->on('cursos')->references('id')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aluno_curso');
    }
}
