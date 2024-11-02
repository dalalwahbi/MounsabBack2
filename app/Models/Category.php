<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public function annonces_()
    {
        return $this->hasMany(Annonce::class);
    }
    public function annonces()
    {
        return $this->hasManyThrough(Annonce::class, Sub_Category::class, 'category_id', 'sub_category_id');
    }
    
    public function Sub_Category()
    {
        return $this->hasMany(Sub_Category::class, 'category_id');
    }
}
