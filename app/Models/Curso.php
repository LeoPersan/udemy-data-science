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

    public function getSlugAttribute()
    {
        return str_slug($this->attributes['curso']);
    }

    public function getMinutosAssistidosAttribute()
    {
        return $this->alunos->sum(function ($aluno) {
            return $aluno->minutosAssistidos;
        });
    }

    public function getComentariosAttribute()
    {
        return $this->alunos()->whereNotNull('comentario')->get();
    }

    public function getAvaliacoesAttribute()
    {
        return $this->alunos()->whereNotNull('avaliacao')->get();
    }

    public function getAvaliacoesPonderadasMesesAttribute()
    {
        $avaliacoes = [];
        foreach ($this->avaliacoes as $avaliacao) {
            $mes = date('Y-m',strtotime($avaliacao->pivot->data_avaliacao));
            if (!isset($avaliacoes[$mes])) $avaliacoes[$mes] = ['avaliacoes'=>[],'comentarios'=>[]];
            if (!is_null($avaliacao->pivot->comentario)) $avaliacoes[$mes]['comentarios'][] = $avaliacao->pivot->comentario;
            $avaliacoes[$mes]['avaliacoes'][] = $avaliacao->pivot->avaliacaoPonderada;
        }
        return $avaliacoes;
    }

    public function getAvaliacoesMesesAttribute()
    {
        $avaliacoes = [];
        foreach ($this->avaliacoes as $avaliacao) {
            $mes = date('Y-m',strtotime($avaliacao->pivot->data_avaliacao));
            if (!isset($avaliacoes[$mes])) $avaliacoes[$mes] = ['avaliacoes'=>[],'comentarios'=>[]];
            if (!is_null($avaliacao->pivot->comentario)) $avaliacoes[$mes]['comentarios'][] = $avaliacao->pivot->comentario;
            $avaliacoes[$mes]['avaliacoes'][] = $avaliacao->pivot->avaliacao;
        }
        return $avaliacoes;
    }

    public function getPerguntasAttribute()
    {
        return $this->alunos->sum(function ($aluno) {
            return $aluno->perguntas;
        });
    }

    public function getPerguntasMesesAttribute()
    {
        $qtde_perguntas = [];
        foreach ($this->alunos as $aluno) {
            $meses = $aluno->meses;
            if (empty($meses)) continue;
            $perguntas = $aluno->perguntas;
            while ($perguntas > 0) {
                $media_perguntas = floor($perguntas/(count($meses)?:1));
                $perguntas -= $media_perguntas;
                if (!isset($qtde_perguntas[array_shift($meses)]))
                    $qtde_perguntas[array_shift($meses)] = 0;
                $qtde_perguntas[array_shift($meses)] += $media_perguntas;
            }
        };
        return $qtde_perguntas;
    }

    public function getMediaProgressoAttribute()
    {
        return $this->alunos->sum(function ($aluno) {
            return $aluno->pivot->progresso;
        })/($this->alunos->count()?:1);
    }

    public function getProgressoMesesAttribute()
    {
        $qtde_progressos = [];
        foreach ($this->alunos as $aluno) {
            $meses = $aluno->meses;
            if (empty($meses)) continue;
            $progresso = $aluno->pivot->progresso;
            $media_progresso = $progresso/count($meses);
            foreach ($meses as $mes) {
                if (!isset($qtde_progressos[$mes]))
                    $qtde_progressos[$mes] = 0;
                $qtde_progressos[$mes] += $media_progresso;
            }
        }
        return $qtde_progressos;
    }

    public function getMediaAttribute()
    {
        return $this->avaliacoes->sum(function ($avaliacao) {
            return $avaliacao->pivot->avaliacao;
        })/($this->avaliacoes->count()?:1);
    }

    public function getMediaPonderadaAttribute()
    {
        return $this->avaliacoes->sum(function ($avaliacao) {
            return $avaliacao->pivot->avaliacaoPonderada;
        })/($this->avaliacoes->count()?:1);
    }

    public function getHorasAttribute()
    {
        $horas =  floor($this->attributes['carga_horaria']/60);
        $this->attributes['carga_horaria'] -= $horas*60;
        $min = $this->attributes['carga_horaria'] > 0 ? $this->attributes['carga_horaria'].'m' : '';
        return $horas.'H '.$min;
    }

    public function getDiplomadosAttribute()
    {
        return $this->alunos()->whereNotNull('diploma')->get();
    }

    public function getCompletosAttribute()
    {
        return $this->alunos()->whereNotNull('diploma')->get();
    }

    public function getIncompletosAttribute()
    {
        return $this->alunos()->whereNotNull('diploma')->get();
    }

    public function getDesistentesAttribute()
    {
        return $this->alunos()->whereNotNull('diploma')->get();
    }

    public function getMesesAttribute()
    {
        $meses = [];
        foreach ($this->alunos as $aluno) {
            if (is_null($aluno->pivot->diploma)) continue;
            $mes = date('Y-m',strtotime($aluno->pivot->diploma));
            if (!isset($meses[$mes])) $meses[$mes] = [
                    'desistentes' => 0,
                    'incompletos' => 0,
                    'completos' => 0,
                    'diplomados' => 0,
                ];
            $meses[$mes]['desistentes']++;
            $meses[$mes]['incompletos']++;
            $meses[$mes]['completos']++;
            $meses[$mes]['diplomados']++;
        }
        return $meses;
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
