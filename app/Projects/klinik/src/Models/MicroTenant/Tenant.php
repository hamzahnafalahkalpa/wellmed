<?php

namespace Projects\Klinik\Models\MicroTenant;

use Hanafalah\MicroTenant\Models\Tenant\Tenant as TenantTenant;

class Tenant extends TenantTenant{
    protected $db_name = 'central';
}