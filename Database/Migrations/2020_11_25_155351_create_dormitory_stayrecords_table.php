<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryStayrecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_stayrecords', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->comment('人员姓名');
            $table->string('idnum')->comment('学号/工号');
            $table->string('sex',10)->comment('性别');
            $table->string('gradeName')->nullable()->comment('年级');
            $table->string('collegeName')->nullable()->comment('学院');
            $table->string('majorName')->nullable()->comment('专业');
            $table->string('className')->nullable()->comment('班级');
            $table->string('buildName')->comment('寝室楼名称');
            $table->string('floor')->comment('楼层');
            $table->string('roomnum')->comment('寝室编号');
            $table->string('bednum')->comment('床位编号');
            $table->timestamp('created_at')->useCurrent()->comment('住宿时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_stayrecords` comment '住宿历史表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dormitory_stayrecords', function (Blueprint $table) {
















        });
    }
}
