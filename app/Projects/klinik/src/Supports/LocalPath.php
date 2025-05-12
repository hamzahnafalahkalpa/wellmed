<?php 

namespace Projects\Klinik\Supports;

use Hanafalah\LaravelSupport\Concerns\Support\HasCall;
use Illuminate\Support\Str;

trait LocalPath {
    use HasCall;

    public function __callGetGeneratorPath(string $path = ''){
        $method = $this->getCallMethod();
        if (Str::startsWith($method, 'get') && Str::endsWith($method, 'Path')) {
            $key  = Str::lower(Str::between($method, 'get', 'Path'));
            $path = __DIR__.'\\..\\'.$this->getConfig("libs.$key").'/'.$path;
            return $this->getClassPath($path);
        }
    }

    public function getConfig(string $root = ''){
        return config('klinik'.(
            $root == '' ? '' : '.'.$root
        ));
    }
}