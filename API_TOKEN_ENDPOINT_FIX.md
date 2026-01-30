# API Token Endpoint Authorization Fix

## Overview
This document describes the fixes applied to resolve authorization issues with the `/api/token` endpoint when using JWT Bearer tokens with `AppCode` header.

## Problems Identified and Fixed

### 1. Missing Columns in Database Query
**Issue:** The code was attempting to SELECT columns (`algorithm`, `secret`, `public_key`, `private_key`) that don't exist in the `api_accesses` table.

**Root Cause:** Database schema was refactored to use a `props` JSON column with the `HasProps` trait, but the SELECT queries weren't updated.

**Fix:** Updated three methods in `/var/www/projects/wellmed/repositories/api-helper/src/Supports/BaseApiAccess.php`:
- `setApiAccessByAppCode()` (lines 65-81)
- `setApiAccessByUsername()` (lines 89-106)
- `setApiAccessByToken()` (lines 114-130)

Removed the non-existent columns from the SELECT statements. The `HasProps` trait now provides access to these fields from the JSON `props` column.

### 2. Null Access Token Handling
**Issue:** When a JWT Bearer token (without `|` character) was provided along with an `AppCode` header, the system tried to call `initByToken()` which expected `$this->__access_token` to be set, but it was null for JWT tokens.

**Root Cause:** The initialization priority was: Username > Token > AppCode. For JWT auth with AppCode, the system should use `initByAppCode()` instead of `initByToken()`.

**Fix:** Modified `/var/www/projects/wellmed/repositories/api-helper/src/Concerns/HasInit.php` `initByToken()` method (lines 18-32) to check if `__access_token` is null, and if so, fall back to `initByAppCode()` if an AppCode header is present.

### 3. Payload Not Passed to Encryption Instance
**Issue:** The `chooseAlgorithm()` method set the payload on the current instance but returned a NEW encryption instance from the service container that didn't have the payload, causing a type error in JWT::decode().

**Root Cause:** When `app($this->encryption())` creates a new JWTEncryptor instance, it doesn't inherit the `__payload` from the calling instance.

**Fix:** Modified `/var/www/projects/wellmed/repositories/api-helper/src/Concerns/HasAlgorithm.php` `chooseAlgorithm()` method (lines 22-29) to explicitly pass the payload to the new encryption instance after creation.

## How to Use the Endpoint

### Generate JWT Token
Use the secret from the `api_accesses` table for your `app_code`:

```bash
# Get the secret for app_code 2
docker exec wellmed-backbone php artisan tinker --execute="
\$api = \Hanafalah\ApiHelper\Models\ApiAccess::where('app_code', 2)->first();
echo \$api->secret;
"

# Output: YXYlGIbJ65VGjQnETWXoOiCvqpXg7PJu

# Generate JWT token with PHP
docker exec wellmed-backbone php -r "
require '/app/vendor/autoload.php';
use Firebase\JWT\JWT;

\$secret = 'YXYlGIbJ65VGjQnETWXoOiCvqpXg7PJu';
\$payload = [
    'iat' => time(),
    'data' => [
        'username' => 'admin',
        'password' => 'password'
    ]
];

\$jwt = JWT::encode(\$payload, \$secret, 'HS256');
echo \$jwt . PHP_EOL;
"
```

### Make API Request
```bash
curl -X POST http://localhost:9000/api/token \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE" \
  -H "AppCode: 2" \
  -H "Content-Type: application/json"
```

### Expected Response
If successful, the endpoint will return a new access token for the authenticated user with their profile information.

## Remaining Issues

### Token Generation Schema Issue
There's still an architectural issue where the Token schema is instantiated fresh during token generation and doesn't inherit the decoded authentication data from the parent context. This causes an "Auth data is missing" error during token generation.

**Current Status:** The JWT decryption and user authentication work correctly. The issue occurs specifically during the new token generation phase.

**Workaround:** The error handling currently returns a 401 response. To fully resolve this, the Token schema initialization needs to be refactored to properly inherit authentication context.

**Next Steps:**
1. Refactor the Token schema to receive authentication context via constructor or setter methods
2. Update the `useSchema()` method to pass necessary context to schema instances
3. Consider using dependency injection or a service locator pattern for better state management

## Files Modified

1. `/var/www/projects/wellmed/repositories/api-helper/src/Supports/BaseApiAccess.php`
   - Removed non-existent columns from SELECT queries

2. `/var/www/projects/wellmed/repositories/api-helper/src/Concerns/HasInit.php`
   - Added null check and fallback logic in `initByToken()`

3. `/var/www/projects/wellmed/repositories/api-helper/src/Concerns/HasAlgorithm.php`
   - Pass payload to new encryption instance

4. `/var/www/projects/wellmed/repositories/api-helper/src/Encryptions/JWTEncryptor.php`
   - Added error logging for JWT encryption failures

## Testing

To verify the fixes work correctly:

1. Generate a properly signed JWT token using the secret for your app_code
2. Make a POST request to `/api/token` with the JWT in Authorization header and AppCode header
3. The JWT should be successfully decrypted and the user authenticated
4. Note: Token generation still needs additional work (see Remaining Issues)

## Related Documentation

- `CLAUDE.md` - Project overview and development commands
- Laravel Octane documentation for worker management
- Firebase JWT library documentation for token handling
