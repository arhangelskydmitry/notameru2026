<?php

namespace App\Models\WordPress;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * Подключение к базе данных WordPress
     */
    protected $connection = 'wordpress';
}







