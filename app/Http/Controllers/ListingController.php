<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Language;
use App\Models\TypeRegime;
use App\Models\TypeDocument;
use App\Models\TypeCurrency;
use App\Models\Municipality;
use App\Models\TypeLiability;
use App\Models\TypeOperation;
use App\Models\TypeEnvironment;
use App\Models\TypeOrganization;
use App\Models\TypeDocumentIdentification;

class ListingController extends Controller
{
    /**
     * index.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return [
            'Country' => Country::all()->pluck('name', 'id'),
            'Language' => Language::all()->pluck('name', 'id'),
            'TypeRegime' => TypeRegime::all()->pluck('name', 'id'),
            'TypeDocument' => TypeDocument::all()->pluck('name', 'id'),
            'TypeCurrency' => TypeCurrency::all()->pluck('name', 'id'),
            'Municipality' => Municipality::all()->pluck('name', 'id'),
            'TypeLiability' => TypeLiability::all()->pluck('name', 'id'),
            'TypeOperation' => TypeOperation::all()->pluck('name', 'id'),
            'TypeEnvironment' => TypeEnvironment::all()->pluck('name', 'id'),
            'TypeOrganization' => TypeOrganization::all()->pluck('name', 'id'),
            'TypeDocumentIdentification' => TypeDocumentIdentification::all()->pluck('name', 'id'),
        ];
    }
}
