<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QualificationRequest;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\DebitNoteController;
use App\Http\Controllers\Api\CreditNoteController;

class QualificationController extends Controller
{

	public function __construct(InvoiceController $invoice, CreditNoteController $credit, DebitNoteController $debit){
		$this->invoice = $invoice;
		$this->credit = $credit;
		$this->debit = $debit;
	}

    public function store(QualificationRequest $request)
    {
		$numberBill = intval($request->number_bill);
		$dataInvoice = [
			"number" => $request->number_bill,
			"type_document_id" => 1,
			"customer" => [
				"identification_number" => 22104505,
				"name" =>  "Flor Lopez",
				"email" =>  "test@test.com",
				"phone" => "3508249979",
				"address" => "Cl 40F",
				"type_document_identification_id" => 3,
				"merchant_registration" => "f"
			],
			"legal_monetary_totals" => [
				"line_extension_amount" => "11900.00", // Total con Impuestos
				"tax_exclusive_amount" => "10000.00", // Total sin impuestos pero con descuentos
				"tax_inclusive_amount" => "11900.00", // Total con Impuestos
				"allowance_total_amount" => "0.00", // Descuentos
				"charge_total_amount" => "0.00", // Cargos
				"payable_amount" => "11900.00" // Valor total a pagar
			],
			"invoice_lines" => [
				[
					"unit_measure_id" => 642, // Unidad de medida que se maneja
					"invoiced_quantity" => "1.000000", // Cantidad de productos
					"line_extension_amount" => "11900.00", // Total producto incluyento impuestos
					"free_of_charge_indicator" => false, // Indica si el producto es una muestra gratis
					"allowance_charges" => [ // cargos y descuentos
						[
							"charge_indicator" => false, //
							"allowance_charge_reason" => "Discount", // Razon del descuento o el cargo
							"amount" => "0.00", // Valor del descuento
							"base_amount" => "0.00" // Sobre que valor se hizo el descuento
						]
					],
					"tax_totals" => [ // Total de impuestos (IVA, RETENCION EN LA FUENTE y RETEIVA)
						[
							"tax_id" => 1, // Id del impuesto
							"tax_amount" => "1900.00", // Valor total del impuesto en el producto
							"taxable_amount" => "10000.00",// Sobre que valor se calcula el imupesto
							"percent" => "19.00" // Porcentaje aplicado del impuesto
						]
					],
					"description" => "XXXXXXXXXXX", // Descripcion del producto
					"code" => "1234567890", // (SKU) Codigo del producto
					"type_item_identification_id" => 3, //
					"price_amount" => "11900.00", // Precio total del producto incluyendo impuestos
					"base_quantity" => "1.000000" // unidad base
				]
			]
		];
		$dataCredit = [
			"number" => $request->number_credit_note,
			"type_document_id" => 4,
			"billing_reference" => [
				"number" => $request->number_bill,
				"uuid" => "ee685f33e07f9eba16b891b40d00c2464722b917e7cafd1c3f5c865c4c6ba5ba6dd78ecf256963daef43b717a0c9675f",
				"issue_date" => "2020-08-11"
			],
			"customer" => [
				"identification_number" => 22104505,
				"name" =>  "Flor Lopez",
				"email" =>  "test@test.com",
				"phone" => "3508249979",
				"address" => "Cl 40F",
				"type_document_identification_id" => 3,
				"merchant_registration" => "f"
			],
			"legal_monetary_totals" => [
				"line_extension_amount" => "11900.00", // Total con Impuestos
				"tax_exclusive_amount" => "10000.00", // Total sin impuestos pero con descuentos
				"tax_inclusive_amount" => "11900.00", // Total con Impuestos
				"allowance_total_amount" => "0.00", // Descuentos
				"charge_total_amount" => "0.00", // Cargos
				"payable_amount" => "11900.00" // Valor total a pagar
			],
			"credit_note_lines" => [
				[
					"unit_measure_id" => 642, // Unidad de medida que se maneja
					"invoiced_quantity" => "1.000000", // Cantidad de productos
					"line_extension_amount" => "11900.00", // Total producto incluyento impuestos
					"free_of_charge_indicator" => false, // Indica si el producto es una muestra gratis
					"allowance_charges" => [ // cargos y descuentos
						[
							"charge_indicator" => false, //
							"allowance_charge_reason" => "Discount", // Razon del descuento o el cargo
							"amount" => "0.00", // Valor del descuento
							"base_amount" => "0.00" // Sobre que valor se hizo el descuento
						]
					],
					"tax_totals" => [ // Total de impuestos (IVA, RETENCION EN LA FUENTE y RETEIVA)
						[
							"tax_id" => 1, // Id del impuesto
							"tax_amount" => "1900.00", // Valor total del impuesto en el producto
							"taxable_amount" => "10000.00",// Sobre que valor se calcula el imupesto
							"percent" => "19.00" // Porcentaje aplicado del impuesto
						]
					],
					"description" => "XXXXXXXXXXX", // Descripcion del producto
					"code" => "1234567890", // (SKU) Codigo del producto
					"type_item_identification_id" => 3, //
					"price_amount" => "11900.00", // Precio total del producto incluyendo impuestos
					"base_quantity" => "1.000000" // unidad base
				]
			]
		];
		$dataDebit = [
			"number" => $request->number_debit_note,
			"type_document_id" => 5,
			"billing_reference" => [
				"number" => $request->number_bill,
				"uuid" => "b4864a1005baa10217506b42c8e69a5a71766f8c1eac9727d3a15b0d0667f53fb1bc5aa03e13e941cd258c185a03381c",
				"issue_date" => "2020-08-11"
			],
			"customer" => [
				"identification_number" => 22104505,
				"name" =>  "Flor Lopez",
				"email" =>  "test@test.com",
				"phone" => "3508249979",
				"address" => "Cl 40F",
				"type_document_identification_id" => 3,
				"merchant_registration" => "f"
			],
			"requested_monetary_totals" => [
				"line_extension_amount" => "11900.00", // Total con Impuestos
				"tax_exclusive_amount" => "10000.00", // Total sin impuestos pero con descuentos
				"tax_inclusive_amount" => "11900.00", // Total con Impuestos
				"allowance_total_amount" => "0.00", // Descuentos
				"charge_total_amount" => "0.00", // Cargos
				"payable_amount" => "11900.00" // Valor total a pagar
			],
			"debit_note_lines" => [
				[
					"unit_measure_id" => 642, // Unidad de medida que se maneja
					"invoiced_quantity" => "1.000000", // Cantidad de productos
					"line_extension_amount" => "11900.00", // Total producto incluyento impuestos
					"free_of_charge_indicator" => false, // Indica si el producto es una muestra gratis
					"allowance_charges" => [ // cargos y descuentos
						[
							"charge_indicator" => false, //
							"allowance_charge_reason" => "Discount", // Razon del descuento o el cargo
							"amount" => "0.00", // Valor del descuento
							"base_amount" => "0.00" // Sobre que valor se hizo el descuento
						]
					],
					"tax_totals" => [ // Total de impuestos (IVA, RETENCION EN LA FUENTE y RETEIVA)
						[
							"tax_id" => 1, // Id del impuesto
							"tax_amount" => "1900.00", // Valor total del impuesto en el producto
							"taxable_amount" => "10000.00",// Sobre que valor se calcula el imupesto
							"percent" => "19.00" // Porcentaje aplicado del impuesto
						]
					],
					"description" => "XXXXXXXXXXX", // Descripcion del producto
					"code" => "1234567890", // (SKU) Codigo del producto
					"type_item_identification_id" => 3, //
					"price_amount" => "11900.00", // Precio total del producto incluyendo impuestos
					"base_quantity" => "1.000000" // unidad base
				]
			]
		];
		$factura;
		$allNumberBill = [];
		$numberInvoice = 0;
		$entro = 0;

		do {
			//FACTURA
			$allNumberBill[] = $numberBill;
			$dataInvoice['number'] = $numberBill;
			$requestCaptureInvoice = \App\Http\Requests\Api\InvoiceRequest::capture();
			$requestCaptureInvoice->merge($dataInvoice);
			$factura = $this->invoice->testSetStore($requestCaptureInvoice, $request->test_set_id);

			if($factura["success"]){
				if($numberInvoice==0){
					$entro++;
					//CREDITO
					$dataCredit['billing_reference']['uuid'] = $factura["data"]["cufe"];
					$requestCaptureCredit = \App\Http\Requests\Api\CreditNoteRequest::capture();
					$requestCaptureCredit->merge($dataCredit);
					$credito = $this->credit->testSetStore($requestCaptureCredit, $request->test_set_id);
					//DEBITO
					$dataDebit['billing_reference']['uuid'] = $factura["data"]["cufe"];
					$requestCaptureDebit = \App\Http\Requests\Api\DebitNoteRequest::capture();
					$requestCaptureDebit->merge($dataDebit);
					$debito = $this->debit->testSetStore($requestCaptureDebit, $request->test_set_id);
				}
			}else break;
			$numberBill++;
			$numberInvoice++;
		} while ($numberInvoice < 10);
		if($numberInvoice==10){
			return [
				'status' => 200,
				'success' => true,
			];
		}else{
			return [
				'status' => 400,
				'success' => false,
				'data' => [
					'debit_credit' => $entro,
					'total creadas' => $numberInvoice,
					'numero facturas' => $allNumberBill,
					'factura' => $factura,
				]
			];
		}
    }
}
