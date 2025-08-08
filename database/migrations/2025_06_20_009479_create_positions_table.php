<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('required_position_id')->nullable()->constrained()->onDelete('restrict');
            $table->foreignId('center_id')->constrained()->onDelete('restrict');
            $table->text('responsibilities');
            $table->integer('max_emp')->default(1);
            $table->integer('exist_emp')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('positions');
    }
};
