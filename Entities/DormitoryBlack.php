<?php

namespace Modules\Dorm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DormitoryBlack extends Model
{
    use HasFactory;

    protected $table = 'dormitory_black';

    protected $appends = ['sex_name','blacklist_type_name','blacklist_class_name','blacklist_reason_name','type_name'];


    public function getSexNameAttribute()
    {
        $sex = ['1'=>'男', '2'=>'女'];
        return $this->sex ? $sex[$this->sex] : '保密';
    }

    public function getblacklistTypeNameAttribute(){

       return DormitoryCategory::whereId($this->blacklist_type)->value('name');
    }

    public function getblacklistClassNameAttribute(){

        return DormitoryCategory::whereId($this->blacklist_class)->value('name');
    }

    public function getblacklistReasonNameAttribute(){

        return DormitoryCategory::whereId($this->blacklist_reason)->value('name');
    }

    public function getTypeNameAttribute()
    {
        $type = ['1'=>'学生', '2'=>'教职工','3'=>'维修工','4'=>'访客','5'=>'社会人员'];
        return $type[$this->type];
    }
}
