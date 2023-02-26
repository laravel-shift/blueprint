<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CertificateStoreRequest;
use App\Http\Requests\Api\CertificateUpdateRequest;
use App\Http\Resources\Api\CertificateCollection;
use App\Http\Resources\Api\CertificateResource;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CertificateController extends Controller
{
    public function index(Request $request): CertificateCollection
    {
        $certificates = Certificate::all();

        return new CertificateCollection($certificates);
    }

    public function store(CertificateStoreRequest $request): CertificateResource
    {
        $certificate = Certificate::create($request->validated());

        return new CertificateResource($certificate);
    }

    public function show(Request $request, Certificate $certificate): CertificateResource
    {
        return new CertificateResource($certificate);
    }

    public function update(CertificateUpdateRequest $request, Certificate $certificate): CertificateResource
    {
        $certificate->update($request->validated());

        return new CertificateResource($certificate);
    }

    public function destroy(Request $request, Certificate $certificate): Response
    {
        $certificate->delete();

        return response()->noContent();
    }
}
