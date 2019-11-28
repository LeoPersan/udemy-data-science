<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Aluno extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','estudante','ano','formacao','sexo'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class)->withPivot(['inscricao','ult_acesso','data_avaliacao','avaliacao','comentario','progresso','perguntas_feitas','perguntas_respondidas','diploma']);
    }

    public function getMinutosAssistidosAttribute()
    {
        if (!$this->pivot or !$this->pivot->pivotParent->carga_horaria) return false;
        return $this->pivot->pivotParent->carga_horaria/100*$this->pivot->progresso;
    }

    public function getAvaliacaoPonderadaAttribute()
    {
        if (!$this->pivot or !$this->pivot->avaliacao) return false;
        return $this->pivot->avaliacao/100*$this->pivot->progresso;
    }

    public function getMesesAttribute()
    {
        if (!$this->pivot) return false;
        if (in_array(null,[$this->pivot->inscricao,$this->pivot->ult_acesso])) return [];

        $mes = date('Y-m-01',strtotime('-1 month',strtotime($this->pivot->inscricao)));
        $mes_fim = date('Y-m-01',strtotime($this->pivot->ult_acesso));
        $meses = [];
        do {
            $mes = date('Y-m-01',strtotime('+1 month',strtotime($mes)));
            $meses[] = date('Y-m',strtotime($mes));
        } while ($mes != $mes_fim);

        return $meses;
    }

    public function getMesesAtualAttribute()
    {
        if (!$this->pivot) return false;
        if (is_null($this->pivot->inscricao)) return [];

        $mes = date('Y-m-01',strtotime('-1 month',strtotime($this->pivot->inscricao)));
        $mes_fim = date('Y-m-01');
        $meses = [];
        do {
            $mes = date('Y-m-01',strtotime('+1 month',strtotime($mes)));
            $meses[] = date('Y-m',strtotime($mes));
        } while ($mes != $mes_fim);

        return $meses;
    }

    public function getPerguntasAttribute()
    {
        if (!$this->pivot) return false;
        return $this->pivot->perguntas_feitas+$this->pivot->perguntas_respondidas;
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('permission', function (Builder $builder) {
            return $builder->whereUserId(auth()->user()->id);
        });
    }
}
