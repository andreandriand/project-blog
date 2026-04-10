<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE posts DROP CONSTRAINT IF EXISTS posts_status_check');
        DB::statement("ALTER TABLE posts ADD CONSTRAINT posts_status_check CHECK (status::text = ANY (ARRAY['draft'::text, 'pending'::text, 'published'::text, 'rejected'::text]))");

        Schema::table('posts', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        DB::table('posts')->whereIn('status', ['pending', 'rejected'])->update(['status' => 'draft']);

        DB::statement('ALTER TABLE posts DROP CONSTRAINT IF EXISTS posts_status_check');
        DB::statement("ALTER TABLE posts ADD CONSTRAINT posts_status_check CHECK (status::text = ANY (ARRAY['draft'::text, 'published'::text]))");

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
