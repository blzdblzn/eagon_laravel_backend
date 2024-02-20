<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public function employee() {
        return $this->hasMany('App\Employee');
    }
}
