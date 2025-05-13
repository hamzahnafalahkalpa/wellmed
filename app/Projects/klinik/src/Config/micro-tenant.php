<?php

return [
    'database' => [
        'model_connections' => [
            "central"        => [
                "ApiAccess",
                "Cache",
                "CacheLock",
                "Country",
                "District",
                "Domain",
                "Encoding",
                "FailedJob",
                "JobBatch",
                "Job",
                "ModelHasEncoding",
                "ModelHasRole",
                "PasswordResetToken",
                "PayloadMonitoring",
                "Permission",
                "PersonalAccessToken",
                "Province",
                "Subdistrict",
                "Tenant",
                "UserReference",
                "User",
                "Village",
                "Workspace"
            ],
            "central_tenant" => []
        ]
    ],
];