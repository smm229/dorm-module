<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryBedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_beds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('buildid')->index('idx_buildid')->comment('楼宇id');
            $table->integer('roomid')->comment('房间id');
            $table->string('room_num', 100)->nullable()->comment('房间号');
            $table->unsignedTinyInteger('is_in')->comment('是否在宿舍0未分配1在2不在');
            $table->string('bednum')->comment('床位号');
            $table->string('idnum')->nullable()->index('idnum')->comment('床位的所属人编号');
            $table->timestamp('created_at')->nullable()->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->index(['roomid', 'bednum'], 'roomid');
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_beds` comment '床位'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_beds');
    }
}
