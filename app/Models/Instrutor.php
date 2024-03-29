<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Instrutor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','instrutor'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('permission', function (Builder $builder) {
            return $builder->whereUserId(auth()->user()->id);
        });
    }

}
