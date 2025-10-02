<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class TenantAssetsController extends Controller
{
    public static $tenancyMiddleware = 'Stancl\Tenancy\Middleware\InitializeTenancyByPath';

    public function __construct()
    {
        $this->middleware(static::$tenancyMiddleware);
    }

    public function asset($path = null)
    {
        if (!isset($path)) abort(404);
        $this->validatePath($path);

        try {
            return response()->file(storage_path("app/$path"));
        } catch (Throwable $th) {
            abort(404);
        }
    }

    /**
     * Prevent path traversal attacks. This is generally a non-issue on modern
     * webservers but it's still worth handling on the application level as well.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validatePath(string &$path): void
    {
        $allowedRoot = realpath(storage_path('app'));
        // `storage_path('app/public')` doesn't exist, so it cannot contain files
        $this->abortIf($allowedRoot === false, "Storage root doesn't exist");

        $path = $this->decryptName($path);
        $attemptedPath = realpath("{$allowedRoot}/{$path}");
        // User is attempting to access a nonexistent file
        $this->abortIf($attemptedPath === false, 'Accessing a nonexistent file');
        // User is attempting to access a file outside the $allowedRoot folder
        $this->abortIf(! str($attemptedPath)->startsWith($allowedRoot), 'Accessing a file outside the storage root');
    }

    private function decryptName(string $name){
        $name = $this->base64url_decode($name);
        return Crypt::decryptString($name);
    }

    private function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    protected function abortIf($condition, $exceptionMessage): void
    {
        if ($condition) {
            if (app()->runningUnitTests()) {
                // Makes testing the cause of the failure in validatePath() easier
                throw new Exception($exceptionMessage);
            } else {
                // We always use 404 to avoid leaking information about the cause of the error
                // e.g. when someone is trying to access a nonexistent file outside of the allowed
                // root folder, we don't want to let the user know whether such a file exists or not.
                abort(404);
            }
        }
    }
}
