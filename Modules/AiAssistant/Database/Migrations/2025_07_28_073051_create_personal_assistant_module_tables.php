<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. personal_assistants
        if (! Schema::hasTable('personal_assistants')) {
            Schema::create('personal_assistants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('name');
                $table->text('instruction')->nullable();
                $table->string('openai_assistant_id')->nullable();
                $table->string('openai_vector_store_id')->nullable();
                $table->string('model')->default('gpt-4o-mini');
                $table->decimal('temperature', 2, 1)->default(0.7);
                $table->text('system_prompt')->nullable();
                $table->json('tools')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'is_active']);
                $table->index(['user_id', 'name']);
            });
        }

        // 2. assistant_documents
        if (! Schema::hasTable('assistant_documents')) {
            Schema::create('assistant_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('assistant_id');
                $table->string('original_filename');
                $table->string('stored_filename');
                $table->string('file_path');
                $table->string('file_type');
                $table->string('openai_file_id')->nullable();
                $table->string('openai_vector_store_id')->nullable();
                $table->text('processed_content')->nullable();
                $table->timestamp('uploaded_to_openai_at')->nullable();
                $table->json('openai_metadata')->nullable();
                $table->boolean('is_processed')->default(false);
                $table->json('embedding_data')->nullable();
                $table->timestamps();

                $table->foreign('assistant_id')->references('id')->on('personal_assistants')->onDelete('cascade');
                $table->index(['assistant_id', 'is_processed']);
                $table->index(['assistant_id', 'file_type']);
                $table->index(['assistant_id', 'created_at']);
            });
        }

        // 3. assistant_conversations
        if (! Schema::hasTable('assistant_conversations')) {
            Schema::create('assistant_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('assistant_id');
                $table->unsignedBigInteger('user_id');
                $table->string('name')->nullable();
                $table->string('chat_type')->default('assistant');
                $table->timestamp('time_sent')->nullable();
                $table->json('message')->nullable();
                $table->string('openai_thread_id')->nullable();
                $table->string('title')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                $table->foreign('assistant_id')->references('id')->on('personal_assistants')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['assistant_id', 'user_id']);
                $table->index(['user_id', 'last_message_at']);
            });
        }

        // 4. ai_qa_cache
        if (! Schema::hasTable('ai_qa_cache')) {
            Schema::create('ai_qa_cache', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('assistant_id');
                $table->text('question');
                $table->string('question_normalized', 500);
                $table->text('answer');
                $table->decimal('similarity_score', 3, 2)->default(1.00);
                $table->integer('usage_count')->default(1);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['assistant_id', 'question_normalized'], 'ai_qa_cache_assistant_normalized_index');
                $table->index(['usage_count', 'last_used_at'], 'ai_qa_cache_usage_index');
            });
        }

        // 5. message_bots modification
        if (Schema::hasTable('message_bots') && ! Schema::hasColumn('message_bots', 'assistant_id')) {
            Schema::table('message_bots', function (Blueprint $table) {
                $table->unsignedBigInteger('assistant_id')->nullable()->after('filename');
            });
        }

        // 6. add feature for personal assistants
        if (! DB::table('features')->where('slug', 'ai_personal_assistants')->exists()) {
            DB::table('features')->insert([
                'name' => 'AI Personal Assistants',
                'slug' => 'ai_personal_assistants',
                'description' => 'Allow users to create and manage AI personal assistants',
                'type' => 'limit',
                'display_order' => 50,
                'default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 7. add permissions for personal assistants
        $permissions = [
            'tenant.personal_assistant.create',
            'tenant.personal_assistant.edit',
            'tenant.personal_assistant.view',
            'tenant.personal_assistant.delete',
            'tenant.personal_assistant.chat',
        ];

        foreach ($permissions as $permission) {
            if (! DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists()) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'scope' => 'tenant',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_qa_cache');
        Schema::dropIfExists('assistant_conversations');
        Schema::dropIfExists('assistant_documents');
        Schema::dropIfExists('personal_assistants');

        if (Schema::hasTable('message_bots') && Schema::hasColumn('message_bots', 'assistant_id')) {
            Schema::table('message_bots', function (Blueprint $table) {
                $table->dropColumn('assistant_id');
            });
        }
    }
};
