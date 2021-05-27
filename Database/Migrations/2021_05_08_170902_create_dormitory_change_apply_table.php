<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryChangeApplyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_change_apply', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('idnum')->comment('学号');
            $table->string('username')->comment('姓名');
            $table->integer('buildid')->comment('楼宇id');
            $table->integer('floornum')->comment('楼层');
            $table->integer('roomid')->nullable()->comment('宿舍id');
            $table->string('roomnum')->comment('房间号');
            $table->string('bednum')->comment('床位号');
            $table->tinyInteger('status')->default(1)->comment('状态 1待审核2已通过3已拒绝');
            $table->string('note', 255)->nullable()->comment('拒绝理由');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_change_apply` comment '调宿申请'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_change_apply');
    }
}
