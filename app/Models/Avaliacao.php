<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','curso','estudante','data','avaliacao','comentario'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('permission', function (Builder $builder) {
            return $builder->whereUserId(auth()->user()->id);
        });
    }
}
