<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\MicroTenant\Models\Tenant\Tenant;
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
        $tenant = app(config('database.models.Tenant', Tenant::class));
        if (!Schema::hasColumn($table_name, $tenant->getForeignKey())) {
            Schema::table($table_name, function (Blueprint $table) use ($tenant) {

                $table->foreignIdFor($tenant::class)->nullable(true)
                    ->after('reference_id')->index()->constrained()
                    ->cascadeOnUpdate()->restrictOnDelete();

                $table->foreignIdFor($tenant::class, 'central_tenant_id')->nullable(true)
                    ->after($tenant->getForeignKey())->index()->constrained($tenant->getTable())
                    ->cascadeOnUpdate()->restrictOnDelete();
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
