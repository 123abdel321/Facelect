<?php

namespace App\Http\Controllers\Api\SOAP;

use Stenfrank\UBL21dian\Templates\Template;
use Stenfrank\UBL21dian\Templates\CreateTemplate;

/**
 * Get status.
 */
class GetResolution extends Template implements CreateTemplate
{
    /**
     * Action.
     *
     * @var string
     */
    public $Action = 'http://wcf.dian.colombia/IWcfDianCustomerServices/GetNumberingRange';

    /**
     * Required properties.
     *
     * @var array
     */
    protected $requiredProperties = [
		'software_id',
		'company'
    ];

    /**
     * Construct.
     *
     * @param string $pathCertificate
     * @param string $passwors
     */
    public function __construct($pathCertificate, $passwors)
    {
        parent::__construct($pathCertificate, $passwors);
    }

    /**
     * Create template.
     *
     * @return string
     */
    public function createTemplate()
    {
        return $this->templateXMLSOAP = <<<XML
		<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:wcf="http://wcf.dian.colombia">
			<soap:Body>
				<wcf:GetNumberingRange>
				<!--Optional:-->
				<wcf:accountCode>{$this->company->identification_number}</wcf:accountCode>
				<!--Optional:-->
				<wcf:accountCodeT>{$this->company->identification_number}</wcf:accountCodeT>
				<!--Optional:-->
				<wcf:softwareCode>9e9c2e9d-ba0f-4fcd-b8f8-421de2136e6b</wcf:softwareCode>
				</wcf:GetNumberingRange>
			</soap:Body>
		</soap:Envelope>
XML;
    }
}
