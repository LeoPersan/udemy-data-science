<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','curso','carga_horaria','preco'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alunos()
    {
        return $this->belongsToMany(Aluno::class)->withPivot(['inscricao','ult_acesso','data_avaliacao','avaliacao','comentario','progresso','perguntas_feitas','perguntas_respondidas','diploma']);
    }

    public function getHorasAttribute()
    {
        $horas =  floor($this->attributes['carga_horaria']/60);
        $this->attributes['carga_horaria'] -= $horas*60;
        $min = $this->attributes['carga_horaria'] > 0 ? $this->attributes['carga_horaria'].'m' : '';
        return $horas.'H '.$min;
    }

    public function instrutores()
    {
        return $this->belongsToMany(Instrutor::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('permission', function (Builder $builder) {
            return $builder->whereUserId(auth()->user()->id);
        });
    }
}
