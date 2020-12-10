<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryRoomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_dorm')->create('dormitory_room', function (Blueprint $table) {
            $table->increments('id');
            $table->string('roomnum')->default('')->comment('房间号');
            $table->Integer('buildtype')->comment('楼的类型');
            $table->tinyInteger('floornum')->comment('所在楼层');
            $table->tinyInteger('bedsnum')->comment('床位数');
            $table->Integer('buildid')->comment('所在楼id');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::connection('mysql_dorm')->statement("ALTER TABLE `dormitory_room` comment '房间表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_dorm')->drop('dormitory_room', function (Blueprint $table) {






        });
    }
}
