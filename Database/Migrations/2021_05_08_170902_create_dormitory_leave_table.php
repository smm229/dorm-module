<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryLeaveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_leave', function (Blueprint $table) {
            $table->increments('id');
            $table->string('idnum', 50)->comment('学号');
            $table->string('username', 255)->nullable()->comment('姓名');
            $table->string('type', 50)->comment('请假类型');
            $table->string('content')->comment('请假事由');
            $table->string('start_time', 20)->comment('开始时间');
            $table->string('end_time', 20)->comment('结束时间');
            $table->tinyInteger('status')->default(1)->comment('申请状态1审核中2已通过3已拒绝');
            $table->string('refuse_note')->nullable()->comment('拒绝理由');
            $table->dateTime('examine_time')->nullable()->comment('审核时间');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_leave` comment '请假'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_leave');
    }
}
