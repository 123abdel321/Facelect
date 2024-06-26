<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Exception;
use App\Resolution;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfigurationRequest;
use App\Http\Controllers\Api\SOAP\GetResolution;
use App\Http\Requests\Api\GetResolutionsRequest;
use App\Http\Requests\Api\ConfigurationSoftwareRequest;
use App\Http\Requests\Api\ConfigurationResolutionRequest;
use App\Http\Requests\Api\ConfigurationCertificateRequest;
use App\Http\Requests\Api\ConfigurationEnvironmentRequest;

class ConfigurationController extends Controller
{
    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\ConfigurationRequest $request
     * @param int                                         $nit
     * @param int                                         $dv
     *
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigurationRequest $request, $nit, $dv = null)
    {
        DB::beginTransaction();

        try {
            $password = Str::random(80);

            $user = User::create([
                'name' => $request->business_name,
                'email' => $request->email,
                'password' => bcrypt($password),
            ]);

            $user->api_token = hash('sha256', $password);

            $user->company()->create([
                'user_id' => $user->id,
                'identification_number' => $nit,
                'dv' => $dv,
                'language_id' => $request->language_id ?? 79,
                'tax_id' => $request->tax_id ?? 1,
                'type_environment_id' => $request->type_environment_id ?? 2,
                'type_operation_id' => $request->type_operation_id ?? 10,
                'type_document_identification_id' => $request->type_document_identification_id,
                'country_id' => $request->country_id ?? 46,
                'type_currency_id' => $request->type_currency_id ?? 35,
                'type_organization_id' => $request->type_organization_id,
                'type_regime_id' => $request->type_regime_id,
                'type_liability_id' => $request->type_liability_id,
                'municipality_id' => $request->municipality_id,
                'merchant_registration' => $request->merchant_registration,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            $user->save();

            DB::commit();

            return [
                'message' => 'Empresa creada con éxito',
                'password' => $password,
                'token' => $password,
                'company' => $user->company,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return response([
                'message' => 'Internal Server Error',
                'payload' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store software.
     *
     * @param \App\Http\Requests\Api\ConfigurationSoftwareRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function storeSoftware(ConfigurationSoftwareRequest $request)
    {
        DB::beginTransaction();

        try {
            auth()->user()->company->software()->delete();

            $software = auth()->user()->company->software()->create([
                'identifier' => $request->id,
                'pin' => $request->pin,
                'url' => $request->url ?? 'https://vpfe-hab.dian.gov.co/WcfDianCustomerServices.svc',
            ]);

            DB::commit();

            return [
                'message' => 'Software creado con éxito',
                'software' => $software,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return response([
                'message' => 'Internal Server Error',
                'payload' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store certificate.
     *
     * @param \App\Http\Requests\Api\ConfigurationCertificateRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function storeCertificate(ConfigurationCertificateRequest $request)
    {
        try {
            if (!base64_decode($request->certificate, true)) {
                throw new Exception('The given data was invalid.');
            }
            if (!openssl_pkcs12_read($certificateBinary = base64_decode($request->certificate), $certificate, $request->password)) {
                throw new Exception('The certificate could not be read.');
            }
        } catch (Exception $e) {
            if (false == ($error = openssl_error_string())) {
                return response([
                    'message' => $e->getMessage(),
                    'errors' => [
                        'certificate' => 'The base64 encoding is not valid.',
                    ],
                ], 422);
            }

            return response([
                'message' => $e->getMessage(),
                'errors' => [
                    'certificate' => $error,
                    'password' => $error,
                ],
            ], 422);
        }
        
        DB::connection('clientes')->beginTransaction();

        try {
            auth()->user()->company->certificate()->delete();

            $company = auth()->user()->company;
            $name = "{$company->identification_number}{$company->dv}.p12";
            
			Storage::put("certificates/{$name}", $certificateBinary);

			Storage::disk('do_spaces')->put("certificates/{$name}", $certificateBinary, 'public');
            
            $certificate = auth()->user()->company->certificate()->create([
                'name' => $name,
                'password' => $request->password,
            ]);
            
            DB::connection('clientes')->commit();

            return [
                'message' => 'Certificado creado con éxito',
                'certificado' => $certificate,
            ];
        } catch (Exception $e) {
            DB::connection('clientes')->rollback();

            return response([
                'message' => 'Internal Server Error',
                'payload' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store resolution.
     *
     * @param \App\Http\Requests\Api\ConfigurationResolutionRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function storeResolution(ConfigurationResolutionRequest $request)
    {
        DB::beginTransaction();

        try {
            $resolution = auth()->user()->company->resolutions()->updateOrCreate([
                'type_document_id' => $request->type_document_id,
            ], [
                'prefix' => $request->prefix,
                'resolution' => $request->resolution,
                'resolution_date' => $request->resolution_date,
                'technical_key' => $request->technical_key,
                'from' => $request->from,
                'to' => $request->to,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ]);

            DB::commit();

            return [
                'message' => 'Resolución creada con éxito',
                'resolution' => $resolution,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return response([
                'message' => 'Internal Server Error',
                'payload' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store environment.
     *
     * @param \App\Http\Requests\Api\ConfigurationEnvironmentRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function storeEnvironment(ConfigurationEnvironmentRequest $request)
    {
        auth()->user()->company->update([
            'type_environment_id' => $request->type_environment_id,
        ]);

        return [
            'message' => 'Ambiente actualizado con éxito',
            'company' => auth()->user()->company,
        ];
	}

	public function getResolutions(GetResolutionsRequest $request){
		// User
        $user = auth()->user();

        $getResolution = new GetResolution($user->company->certificate->path, $user->company->certificate->password);
		$getResolution->company = $user->company;
		$getResolution->software_id = $user->company->software->identifier;
		$getResolution->To = $user->company->software->url;
		//RECORRER RESPUESTA Y AGREGAR EN RESOLUTION
		$response = $getResolution->signToSend()->getResponseToObject();
        dd($response->Envelope->Body);
		$resolutions = $response->Envelope->Body->GetNumberingRangeResponse->GetNumberingRangeResult->ResponseList->NumberRangeResponse;
		foreach ($resolutions as $key => $resolution) {
			// DELETE RESOLUCIONS WITH SAME RESOLITION NUMBER
			Resolution::whereResolution($resolution->ResolutionNumber)->delete();
			// CREATE RESOLUTION FACTURA
			$setpResolution = new Resolution();
			$setpResolution->type_document_id = 1;
			$setpResolution->prefix = $resolution->Prefix;
			$setpResolution->company_id = $user->company->id;
			$setpResolution->resolution = $resolution->ResolutionNumber;
			$setpResolution->resolution_date = $resolution->ResolutionDate;
			$setpResolution->technical_key = $resolution->TechnicalKey;
			$setpResolution->date_from = $resolution->ValidDateFrom;
			$setpResolution->date_to = $resolution->ValidDateTo;
			$setpResolution->from = $resolution->FromNumber;
			$setpResolution->to = $resolution->ToNumber;
			$setpResolution->save();
			// CREATE RESOLUTION CREDITO
			$setpResolution = new Resolution();
			$setpResolution->type_document_id = 4;
			$setpResolution->prefix = 'NC';
			$setpResolution->company_id = $user->company->id;
			$setpResolution->resolution = $resolution->ResolutionNumber;
			$setpResolution->resolution_date = $resolution->ResolutionDate;
			$setpResolution->technical_key = $resolution->TechnicalKey;
			$setpResolution->date_from = $resolution->ValidDateFrom;
			$setpResolution->date_to = $resolution->ValidDateTo;
			$setpResolution->from = 1;
			$setpResolution->to = 100000000;
			$setpResolution->save();
			// CREATE RESOLUTION DEBITO
			$setpResolution = new Resolution();
			$setpResolution->type_document_id = 5;
			$setpResolution->prefix = 'ND';
			$setpResolution->company_id = $user->company->id;
			$setpResolution->resolution = $resolution->ResolutionNumber;
			$setpResolution->resolution_date = $resolution->ResolutionDate;
			$setpResolution->technical_key = $resolution->TechnicalKey;
			$setpResolution->date_from = $resolution->ValidDateFrom;
			$setpResolution->date_to = $resolution->ValidDateTo;
			$setpResolution->from = 1;
			$setpResolution->to = 100000000;
			$setpResolution->save();
		}
        return [
            'message' => 'Consulta generada con éxito',
            'ResponseDian' => $getResolution->signToSend()->getResponseToObject(),
        ];
	}
}
