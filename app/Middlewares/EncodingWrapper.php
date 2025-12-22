<?php

namespace App\Middlewares;

use Closure;
use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\LaravelSupport\Facades\SupportCache;
use Hanafalah\ModuleSupport\Schemas\Support;

class EncodingWrapper
{
    use HasArray, HasCache;

    public function __construct()
    {
        $this->__cache = config('laravel-support.encoding_cache_data');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        if (isset(tenancy()->tenant) && tenancy()->tenant->flag == 'TENANT'){
            $this->setupEncodingCache();
            $response = $next($request);
            if ($response->getStatusCode() < 400) {
                $model_has_encodings = SupportCache::getSavedCache('model_has_encoding_configs');
                foreach ($model_has_encodings['model_has_encodings'] as &$model_has_encoding) {
                    if ($model_has_encoding->isDirty()){
                        $model_has_encoding->save();
                    }
            }
                $this->setCache($this->__cache['model_has_encoding'], function() use ($model_has_encodings) {
                    return $model_has_encodings;
                }, false, true);
            }else{
                $this->forgetTags($this->__cache['model_has_encoding']['tags']);
                $this->forgetTags($this->__cache['encoding']['tags']);
            }
        }else{
            $response = $next($request);
        }

        return $response;
    }

    private function setupEncodingCache(){
        $encoding_config = $this->setCache($this->__cache['encoding'], function() {
            $encodings = app(config('database.models.Encoding'))->get();
            $config_encodings = [];
            foreach ($encodings as $encoding) {
                $config_encodings[$encoding->label] = $encoding->getKey();
            }
            return $config_encodings;
        },false);

        SupportCache::saveCache('encoding_config', $encoding_config);

        $model_has_encoding_configs = $this->setCache($this->__cache['model_has_encoding'], function() {
            $model_has_encodings = app(config('database.models.ModelHasEncoding'))->where('reference_type','Workspace')
                ->where('reference_id',tenancy()->tenant->reference_id)
                ->get();
            return [
                'model_has_encodings' => $model_has_encodings,
                'model_has_encoding_ids' => $model_has_encodings->pluck('encoding_id')->toArray()
            ];
        },false);
        SupportCache::saveCache('model_has_encoding_configs', $model_has_encoding_configs);

    }
}
