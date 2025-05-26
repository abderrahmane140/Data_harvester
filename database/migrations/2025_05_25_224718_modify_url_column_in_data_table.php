<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('data', function (Blueprint $table) {
            $table->text('url')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('data', function (Blueprint $table) {
            $table->string('url', 255)->nullable()->change();
        });
    }
};
