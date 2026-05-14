<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Campos de seguridad y autorización
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('user_type')->default('user'); // user, api_client, system
            
            // Campos de seguridad adicionales
            $table->json('allowed_ips')->nullable(); // IPs permitidas para este usuario
            $table->json('permissions')->nullable(); // Permisos específicos adicionales
            $table->json('restrictions')->nullable(); // Restricciones específicas
            
            // Campos de sesión y seguridad
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            
            // Estados del usuario
            $table->boolean('active')->default(true);
            $table->boolean('force_password_change')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable(); // Información adicional flexible
            
            $table->index(['company_id', 'active']);
            $table->index(['user_type', 'active']);
            $table->index(['last_login_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'role_id',
                'company_id', 
                'user_type',
                'allowed_ips',
                'permissions',
                'restrictions',
                'last_login_at',
                'last_login_ip',
                'failed_login_attempts',
                'locked_until',
                'active',
                'force_password_change',
                'password_changed_at',
                'metadata'
            ]);
        });
    }
};
