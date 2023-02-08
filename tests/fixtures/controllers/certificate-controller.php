<?php

namespace App\Http\Controllers;

use App\Http\Requests\CertificateStoreRequest;
use App\Http\Requests\CertificateUpdateRequest;
use App\Http\Resources\CertificateCollection;
use App\Http\Resources\CertificateResource;
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
