<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name'];

    public function user() {
        return $this->belongsToMany(User::class);
    }

    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}
