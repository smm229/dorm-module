<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDormitoryNoRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dormitory_no_record', function (Blueprint $table) {
            $table->string('build_name', 50)->after('class_name')->comment('宿舍楼名称');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dormitory_no_record', function (Blueprint $table) {

        });
    }
}
