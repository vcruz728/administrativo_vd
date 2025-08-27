<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('oficios', function (Blueprint $table) {
            if (!Schema::hasColumn('oficios', 'informativo')) {
                $table->boolean('informativo')->default(false)->index();
            }
            if (!Schema::hasColumn('oficios', 'requiere_atencion')) {
                $table->boolean('requiere_atencion')->nullable()->index();
            }
        });
    }
    public function down(): void
    {
        Schema::table('oficios', function (Blueprint $table) {
            $table->dropColumn(['informativo', 'requiere_atencion']);
        });
    }
};
