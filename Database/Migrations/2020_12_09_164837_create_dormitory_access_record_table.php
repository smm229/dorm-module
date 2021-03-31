<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryAccessRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_access_record', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('buildid')->comment('楼id');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型1学生2老师');
            $table->string('idnum', 50)->index('index_idnum')->comment('学号/工号');
            $table->string('username', 50)->index('index_username')->comment('姓名');
            $table->string('sex',10)->comment('性别');
            $table->string('college_name', 50)->nullable()->comment('学院');
            $table->string('major_name', 50)->nullable()->comment('专业');
            $table->string('grade_name', 50)->nullable()->comment('年级');
            $table->string('class_name', 50)->nullable()->comment('班级');
            $table->string('build_name', 50)->nullable()->comment('宿舍楼');
            $table->string('floor', 50)->nullable()->comment('楼层');
            $table->string('room_num', 50)->nullable()->comment('房间号');
            $table->string('bed_num', 50)->nullable()->comment('床位');
            $table->string('pass_location')->comment('通行地点');
            $table->string('pass_way')->comment('通道名称');
            $table->string('direction', 50)->nullable()->comment('方向');
            $table->string('abnormalType', 50)->nullable()->comment('类型');
            $table->tinyInteger('status')->nullable()->comment('状态,0正常1晚归');
            $table->dateTime('pass_time')->comment('通行时间');
            $table->string('cover')->nullable()->comment('识别照片');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_access_record` comment '通行记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dormitory_access_record', function (Blueprint $table) {

            
            
        });
    }
}
