<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\ModuleUser\Models\User\User;
use Hanafalah\ModuleUser\Models\User\UserReference;

return new class extends Migration
{
    use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;

    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.UserReference', UserReference::class));
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $table_name = $this->__table->getTable();
        if (!$this->isTableExists()) {
            Schema::create($table_name, function (Blueprint $table) {
                $user = app(config('database.models.User', User::class));

                $table->id();
                $table->string('uuid', 255)->unique()->nullable(false);
                $table->string('reference_type', 50)->nullable(false);
                $table->string('reference_id', 36)->nullable(false);
                $table->foreignIdFor($user::class)->nullable(true)->index()
                    ->constrained()->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('workspace_type',50)->nullable();
                $table->string('workspace_id',36)->nullable();
                $table->unsignedTinyInteger('current')->default(1);
                $table->json('props')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['reference_type', 'reference_id'],'ur_ref');
                $table->index(['workspace_type', 'workspace_id'],'ur_workspace');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->__table->getTable());
    }
};
