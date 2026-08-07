<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // SQLite no soporta ALTER COLUMN para cambiar enums.
        // La solución es recrear la constraint usando una columna string
        // con check constraint manual.
        Schema::disableForeignKeyConstraints();

        DB::statement('
            CREATE TABLE users_new AS SELECT * FROM users
        ');

        DB::statement('DROP TABLE users');

        DB::statement("
            CREATE TABLE users (
                id TEXT PRIMARY KEY,
                tenant_id TEXT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                email_verified_at TEXT,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('super_admin','admin','user','cashier')),
                active INTEGER NOT NULL DEFAULT 1,
                avatar_url TEXT,
                last_login_at TEXT,
                remember_token TEXT,
                created_at TEXT,
                updated_at TEXT,
                deleted_at TEXT
            )
        ");

        DB::statement('INSERT INTO users SELECT * FROM users_new');
        DB::statement('DROP TABLE users_new');

        DB::statement('CREATE INDEX IF NOT EXISTS users_tenant_id_index ON users(tenant_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_role_index ON users(role)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_active_index ON users(active)');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('CREATE TABLE users_new AS SELECT * FROM users');
        DB::statement('DROP TABLE users');

        DB::statement("
            CREATE TABLE users (
                id TEXT PRIMARY KEY,
                tenant_id TEXT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                email_verified_at TEXT,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('super_admin','admin','user')),
                active INTEGER NOT NULL DEFAULT 1,
                avatar_url TEXT,
                last_login_at TEXT,
                remember_token TEXT,
                created_at TEXT,
                updated_at TEXT,
                deleted_at TEXT
            )
        ");

        DB::statement('INSERT INTO users SELECT * FROM users_new');
        DB::statement('DROP TABLE users_new');

        Schema::enableForeignKeyConstraints();
    }
};
