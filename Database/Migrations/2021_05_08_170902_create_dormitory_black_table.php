<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDormitoryBlackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dormitory_black', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('senselink_id')->nullable()->comment('linkid');
            $table->string('username', 255)->nullable()->comment('姓名');
            $table->string('headimg', 255)->nullable()->comment('头像');
            $table->unsignedTinyInteger('sex')->nullable()->default(1)->comment('1男2女');
            $table->string('author', 50)->nullable()->comment('添加者');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `dormitory_black` comment '黑名单'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dormitory_black');
    }
}
