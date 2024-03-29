<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryUsersBuildingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_users_building', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('idnum', 32)->index('username')->comment('教师工号');
            $table->integer('buildid')->comment('楼宇id');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->softDeletes()->comment('删除时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_users_building` comment '教职工关联用户组'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_users_building');
    }
}
