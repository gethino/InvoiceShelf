<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Contacts\Models\Country;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountriesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request)
    {
        $countries = Country::all();

        return CountryResource::collection($countries);
    }
}
