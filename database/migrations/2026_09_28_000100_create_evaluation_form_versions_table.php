<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evaluation_form_versions')) {
            Schema::create('evaluation_form_versions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('form_id');
                $table->unsignedInteger('version');
                $table->json('payload');
                $table->foreign('form_id')->references('id')->on('evaluation_forms')->onDelete('cascade');
                $table->unique(['form_id', 'version']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_form_versions');
    }
};
