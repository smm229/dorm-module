<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDormitoryBlackGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_black_group', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type')->nullable()->default(1)->comment('类型1学生2教职工3维修工4访客5社会人员');
            $table->integer('senselink_id')->comment('senselink用户id');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_black_group` comment '当前黑名单link表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_black_group');
    }
}
