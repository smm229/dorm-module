<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDormitoryStayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_stay', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('buildid')->comment('楼id');
            $table->string('idnum', 50)->index('index_idnum')->comment('学号/工号');
            $table->string('username', 50)->index('index_username')->comment('姓名');
            $table->string('college_name', 50)->nullable()->comment('学院');
            $table->string('major_name', 50)->nullable()->comment('专业');
            $table->string('grade_name', 50)->nullable()->comment('年级');
            $table->string('class_name', 50)->nullable()->comment('班级');
            $table->string('build_name', 100)->nullable()->comment('宿舍楼');
            $table->string('floornum', 100)->nullable()->comment('楼层');
            $table->string('roomnum', 100)->nullable()->comment('房间');
            $table->string('bednum', 100)->nullable()->comment('床位');
            $table->string('begin_time')->comment('开始时间');
            $table->string('end_time')->comment('结束时间');
            $table->string('note')->nullable()->comment('备注');
            $table->string('refuse_note', 200)->nullable()->comment('拒绝理由');
            $table->tinyInteger('status')->nullable()->comment('状态1审批中2通过3拒绝');
            $table->dateTime('confirm_time')->comment('审核时间');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_stay` comment '留宿申请'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_stay');
    }
}
