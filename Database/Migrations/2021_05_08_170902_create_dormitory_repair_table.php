<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryRepairTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_repair', function (Blueprint $table) {
            $table->increments('id');
            $table->string('orderno', 255)->comment('工单号');
            $table->unsignedInteger('buildid')->comment('楼宇id');
            $table->unsignedInteger('floornum')->comment('楼层');
            $table->string('roomnum', 255)->nullable()->comment('宿舍号');
            $table->string('type', 255)->nullable()->comment('故障类型');
            $table->string('intro', 255)->nullable()->comment('故障详情');
            $table->string('idnum', 255)->comment('学员编号');
            $table->string('username', 50)->comment('姓名');
            $table->dateTime('time')->nullable()->comment('期望维修时间');
            $table->string('phone', 20)->comment('手机号');
            $table->text('covers')->nullable()->comment('图片');
            $table->boolean('status')->nullable()->default(1)->comment('状态1待接单2已接单3维修中4已完成');
            $table->string('teacher_idnum', 255)->nullable()->comment('维修工编号');
            $table->dateTime('take_time')->nullable()->comment('接单时间');
            $table->dateTime('repair_time')->nullable()->comment('维修时间');
            $table->text('confirm_covers')->nullable()->comment('完成图片');
            $table->dateTime('confirm_time')->nullable()->comment('完成时间');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_repair` comment '宿舍报修'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_repair');
    }
}
