<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Send;
use App\Company;
use App\TaxTotal;
use App\PaymentForm;
use App\TypeDocument;
use App\PaymentMethod;
use App\AllowanceCharge;
use App\BillingReference;
use App\LegalMonetaryTotal;
use Illuminate\Http\Request;
use App\Traits\DocumentTrait;
use App\Http\Controllers\Controller;
use App\InvoiceLine as CreditNoteLine;
use App\Http\Requests\Api\CreditNoteRequest;
use App\Http\Controllers\Api\SOAP\XADES\SignCreditNote;
use Stenfrank\UBL21dian\Templates\SOAP\GetStatusZip;
use Stenfrank\UBL21dian\Templates\SOAP\SendBillAsync;
use Stenfrank\UBL21dian\Templates\SOAP\SendTestSetAsync;

class CreditNoteController extends Controller
{
    use DocumentTrait;

    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\CreditNoteRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CreditNoteRequest $request)
    {
        // User
        $user = auth()->user();

        // User company
        $company = $user->company;

        // Type document
        $typeDocument = TypeDocument::findOrFail($request->type_document_id);

        // Customer
        $customerAll = collect($request->customer);
        $customer = new User($customerAll->toArray());

        // Customer company
        $customer->company = new Company($customerAll->toArray());

        // Resolution
        $request->resolution->number = $request->number;
        $resolution = $request->resolution;

        // Date time
        $date = $request->date;
        $time = $request->time;

        // Payment form default
        $paymentFormAll = (object) array_merge($this->paymentFormDefault, $request->payment_form ?? []);
        $paymentForm = PaymentForm::findOrFail($paymentFormAll->payment_form_id);
        $paymentForm->payment_method_code = PaymentMethod::findOrFail($paymentFormAll->payment_method_id)->code;
        $paymentForm->payment_due_date = $paymentFormAll->payment_due_date ?? null;
        $paymentForm->duration_measure = $paymentFormAll->duration_measure ?? null;

        // Allowance charges
        $allowanceCharges = collect();
        foreach ($request->allowance_charges ?? [] as $allowanceCharge) {
            $allowanceCharges->push(new AllowanceCharge($allowanceCharge));
        }

        // Tax totals
        $taxTotals = collect();
        foreach ($request->tax_totals ?? [] as $taxTotal) {
            $taxTotals->push(new TaxTotal($taxTotal));
        }

        // Legal monetary totals
        $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);

        // Credit note lines
        $creditNoteLines = collect();
        foreach ($request->credit_note_lines as $creditNoteLine) {
            $creditNoteLines->push(new CreditNoteLine($creditNoteLine));
        }

        // Billing reference
        $billingReference = new BillingReference($request->billing_reference);

        // Create XML
        $crediNote = $this->createXML(compact('user', 'company', 'customer', 'taxTotals', 'resolution', 'paymentForm', 'typeDocument', 'creditNoteLines', 'allowanceCharges', 'legalMonetaryTotals', 'billingReference', 'date', 'time'));

        // Signature XML
        $signCreditNote = new SignCreditNote($company->certificate->path, $company->certificate->password);
        $signCreditNote->softwareID = $company->software->identifier;
        $signCreditNote->pin = $company->software->pin;

        $sendBillAsync = new SendBillAsync($company->certificate->path, $company->certificate->password);
        $sendBillAsync->To = $company->software->url;
        $sendBillAsync->fileName = "{$resolution->next_consecutive}.xml";
        $sendBillAsync->contentFile = $this->zipBase64($company, $resolution, $signCreditNote->sign($crediNote));

        // Invoice status
		$getResponse = $sendTestSetAsync->signToSend()->getResponseToObject();
		$zipKey = $getResponse->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
		$time = 0.2;
		sleep(1);
		do {
			sleep($time);
			$getStatus = new GetStatusZip($company->certificate->path, $company->certificate->password);
			$getStatus->trackId = $zipKey;
			$getStatus = $getStatus->signToSend()->getResponseToObject();

			$time+=0.1;
			if($time>=10)break;
		} while (!$getStatus);
		return [
			'getStatus' => $getStatus,
		];
		if($getStatus){
			$isValid = $getStatus->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid;
			if($isValid=="true"){
				return [
					'status' => 200,
					'success' => true,
					'data' => [
						'StatusDescription' => "{$typeDocument->name} #{$resolution->next_consecutive} generada con éxito",
						'ResponseDian' => $getResponse,
						'ZipBase64Bytes' => base64_encode($this->getZIP()),
					]
				];
			}else{
				$sendRecords = Send::whereConsecutive($resolution->prefix.$resolution->number)->whereCompanyId($company->id)->delete();
				return [
					'status' => 400,
					'success' => false,
					'data' => [
						'StatusDescription' => $getStatus->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->StatusDescription,
						'ErrorMessage' => $getStatus->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage
					]
				];
			}
		}else{
			return [
				'status' => 523,
				'success' => false,
				'data' => [
					'StatusDescription' => 'Time out',
					'ErrorMessage' => 'Time out'
				]
			];
		}
    }

    /**
     * Test set store description].
     *
     * @param \App\Http\Requests\Api\CreditNoteRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function testSetStore(CreditNoteRequest $request, $testSetId)
    {
        // User
        $user = auth()->user();

        // User company
        $company = $user->company;

        // Type document
		$typeDocument = TypeDocument::findOrFail($request->type_document_id);

        // Customer
        $customerAll = collect($request->customer);
        $customer = new User($customerAll->toArray());

        // Customer company
        $customer->company = new Company($customerAll->toArray());

		// Resolution
		if(!isset($request->resolution)){
			$request->resolution = auth()->user()->company->resolutions->where('type_document_id', $request->type_document_id)->first();
		}
        $request->resolution->number = $request->number;
        $resolution = $request->resolution;

        // Date time
        $date = $request->date;
        $time = $request->time;

        // Payment form default
        $paymentFormAll = (object) array_merge($this->paymentFormDefault, $request->payment_form ?? []);
        $paymentForm = PaymentForm::findOrFail($paymentFormAll->payment_form_id);
        $paymentForm->payment_method_code = PaymentMethod::findOrFail($paymentFormAll->payment_method_id)->code;
        $paymentForm->payment_due_date = $paymentFormAll->payment_due_date ?? null;
        $paymentForm->duration_measure = $paymentFormAll->duration_measure ?? null;

        // Allowance charges
        $allowanceCharges = collect();
        foreach ($request->allowance_charges ?? [] as $allowanceCharge) {
            $allowanceCharges->push(new AllowanceCharge($allowanceCharge));
        }

        // Tax totals
        $taxTotals = collect();
        foreach ($request->tax_totals ?? [] as $taxTotal) {
            $taxTotals->push(new TaxTotal($taxTotal));
        }

        // Legal monetary totals
        $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);

        // Credit note lines
        $creditNoteLines = collect();
        foreach ($request->credit_note_lines as $creditNoteLine) {
            $creditNoteLines->push(new CreditNoteLine($creditNoteLine));
        }
        // Billing reference
        $billingReference = new BillingReference($request->billing_reference);

        // Create XML
		$crediNote = $this->createXML(compact('user', 'company', 'customer', 'taxTotals', 'resolution', 'paymentForm', 'typeDocument', 'creditNoteLines', 'allowanceCharges', 'legalMonetaryTotals', 'billingReference', 'date', 'time'));

        // Signature XML
		$signCreditNote = new SignCreditNote($company->certificate->path, $company->certificate->password);
		$signCreditNote->technicalKey = null;
        $signCreditNote->softwareID = $company->software->identifier;
    	$signCreditNote->pin = $company->software->pin;

        $sendTestSetAsync = new SendTestSetAsync($company->certificate->path, $company->certificate->password);
        $sendTestSetAsync->To = $company->software->url;
        $sendTestSetAsync->fileName = "{$resolution->next_consecutive}.xml";
        $sendTestSetAsync->contentFile = $this->zipBase64($company, $resolution, $signCreditNote->sign($crediNote));
        $sendTestSetAsync->testSetId = $testSetId;

		// Invoice status
		$getResponse = $sendTestSetAsync->signToSend()->getResponseToObject();
		$zipKey = $getResponse->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
		$time = 0.2;
		sleep(1);
		do {
			sleep($time);
			$getStatus = new GetStatusZip($company->certificate->path, $company->certificate->password);
			$getStatus->trackId = $zipKey;
			$getStatus = $getStatus->signToSend()->getResponseToObject();
			$time+=0.1;
			if($time>=10)break;
		} while (!$getStatus);

		if($getStatus){
			$isValid = $getStatus->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid;
			if($isValid=="true"){
				return [
					'status' => 200,
					'success' => true,
					'data' => [
						"cude"=>$signCreditNote->cude,
						'StatusDescription' => "{$typeDocument->name} #{$resolution->next_consecutive} generada con éxito",
						'ResponseDian' => $getResponse,
						'ZipBase64Bytes' => base64_encode($this->getZIP()),
					]
				];
			}else{
				$sendRecords = Send::whereConsecutive($resolution->prefix.$resolution->number)->whereCompanyId($company->id)->delete();
				return [
					'status' => 400,
					'success' => false,
					'data' => [
						'cufe' => $signCreditNote->cufe,
						'StatusDescription' => $getStatus->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->StatusDescription,
						'ErrorMessage' => $getStatus->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage
					]
				];
			}
		}else{
			return [
				'status' => 523,
				'success' => false,
				'data' => [
					'cufe' => $signCreditNote->cufe,
					'StatusDescription' => 'Time out',
					'ErrorMessage' => 'Time out'
				]
			];
		}
    }
}
