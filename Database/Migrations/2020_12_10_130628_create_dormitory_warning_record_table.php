<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryWarningRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_dorm')->create('dormitory_warning_record', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('pass_time')->comment('通行时间');
            $table->string('pass_location')->comment('识别地点');
            $table->string('pass_way')->comment('识别设备');
            $table->string('note')->nullable()->comment('详情');
            $table->string('cover')->nullable()->comment('图片');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
        \Illuminate\Support\Facades\DB::connection('mysql_dorm')->statement("ALTER TABLE `dormitory_warning_record` comment '告警记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_dorm')->drop('dormitory_warning_record', function (Blueprint $table) {
            
            
            
            
            
            
            
        });
    }
}
