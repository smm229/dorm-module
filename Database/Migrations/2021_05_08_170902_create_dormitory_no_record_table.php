<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryNoRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_no_record', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('buildid')->index('idx_buildid')->comment('楼id');
            $table->unsignedTinyInteger('type')->comment('类型1学生2老师');
            $table->string('idnum', 50)->index('index_idnum')->comment('学号/工号');
            $table->string('username', 50)->index('index_username')->comment('姓名');
            $table->string('sex', 10)->comment('性别');
            $table->string('college_name', 50)->nullable()->comment('学院');
            $table->string('major_name', 50)->nullable()->comment('专业');
            $table->string('grade_name', 50)->nullable()->comment('年级');
            $table->string('class_name', 50)->nullable()->comment('班级');
            $table->string('build_name', 20)->nullable()->comment('宿舍');
            $table->string('roomnum', 20)->comment('房间号');
            $table->string('bednum', 20)->comment('床位号');
            $table->date('begin_date')->comment('开始日期');
            $table->date('end_date')->nullable()->comment('截止日期');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_no_record` comment '多日无记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_no_record');
    }
}
