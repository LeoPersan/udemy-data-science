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
    protected $fillable = ['user_id','curso','preco'];

    public function user()
    {
        return $this->belongsTo(User::class);
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
