<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;
use Illuminate\Support\Facades\Log;

ApiAccess::secure(function(){
    Route::apiResource('token',ApiAccessController::class)
        ->only('store','destroy')
        ->parameters(['token' => 'uuid']);

    Route::get('/logModel',function(){
        $models = ['ADL','AdministrationVitaminA','Allergy','Alloanamnese','AMT','ANCTerpadu','Anthropometry','Assessment','AudiometriTest','BloodSugarTest','ChildAndPregnancyHistory','ChildGrowth','EarExamination','EyeExamination','EyeRefractionExamination','EyeVisionColor','FamilyPlanningService','FinalConclusionLabor','FoodHandlerExamination','GCS','GDS4','HearingFunction','HearingLossHistory','HemoglobinTest','HIV','HIVAntibodyTest','ImmunizationHistory','ISKJ','KalaIExamination','KalaIIExamination','KalaIIIExamination','KalaIVExamination','LarynxExamination','MCUExamSummary','MCUMedicalHistory','MCUPackageSummary','MCUPresentMedicalHistory',
        'MNA','MorceFallScale','MouthCavity','MouthCavityOther','NeonatalEsential','NewBornCheckUp','NoseExamination','Odontogram','PainScale','PARQ','PhysicalActivity','POPMHistory','PostpartumObservation','PUMA','RocportTest','SNST','SOAP','SPPB','Symptom','TBContactHistory','TBRiskFactor','TetanusImmunization','ThoraxExamination','ThroatExamination','Triage','TTDExamination','Vaccine','VisualImpairmentTest','VitalSign',
        'WastExamination'];

        foreach ($models as $model) {
            $model = app(config('database.models.'.$model));
            Log::info($model->specific);
        }
        return true;
    });
});
LaravelSupport::callRoutes(__DIR__.'/api');