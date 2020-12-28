<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryBlackAccessRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_black_access_record', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('buildid')->comment('楼id');
            $table->string('username', 50)->index('index_username')->comment('姓名');
            $table->string('sex',10)->comment('性别');
            $table->string('pass_location')->comment('通行地点');
            $table->string('pass_way')->comment('通道名称');
            $table->string('direction', 50)->nullable()->comment('方向');
            $table->dateTime('pass_time')->comment('通行时间');
            $table->string('cover')->nullable()->comment('识别照片');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_black_access_record` comment '黑名单通行记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dormitory_black_access_record', function (Blueprint $table) {
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
        });
    }
}
