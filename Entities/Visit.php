<?php

namespace Modules\Dorm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = 'dormitory_guest';
    protected $appends = ['visitplacename'];
    protected static function newFactory()
    {
        return \Modules\Dorm\Database\factories\VisitFactory::new();
    }

    public function getvisitplacenameAttribute()
    {
        $this->visit_place = explode(',', $this->visit_place);
        $buildName = DormitoryGroup::whereIn('id', $this->visit_place)->pluck('title')->toArray();
        return implode(',', $buildName);
    }
}
