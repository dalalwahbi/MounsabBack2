<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sub_Category extends Model
{
    use HasFactory;
    protected $table = 'sub_categories';

    public function Sous_Category()
    {
        return $this->hasMany(Sous_Category::class, 'sub_category_id');
    }
    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'sub_category_id');
    }
}
