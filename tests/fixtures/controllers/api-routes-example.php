<?php

namespace App\Http\Controllers\Api;

use App\Certificate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CertificateStoreRequest;
use App\Http\Requests\Api\CertificateUpdateRequest;
use App\Http\Resources\Api\CertificateCollection;
use App\Http\Resources\Api\CertificateResource;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\Api\CertificateCollection
     */
    public function index(Request $request)
    {
        $certificates = Certificate::all();

        return new CertificateCollection($certificates);
    }

    /**
     * @param \App\Http\Requests\Api\CertificateStoreRequest $request
     * @return \App\Http\Resources\Api\CertificateResource
     */
    public function store(CertificateStoreRequest $request)
    {
        $certificate = Certificate::create($request->validated());

        return new CertificateResource($certificate);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Certificate $certificate
     * @return \App\Http\Resources\Api\CertificateResource
     */
    public function show(Request $request, Certificate $certificate)
    {
        return new CertificateResource($certificate);
    }

    /**
     * @param \App\Http\Requests\Api\CertificateUpdateRequest $request
     * @param \App\Certificate $certificate
     * @return \App\Http\Resources\Api\CertificateResource
     */
    public function update(CertificateUpdateRequest $request, Certificate $certificate)
    {
        $certificate->update($request->validated());

        return new CertificateResource($certificate);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Certificate $certificate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Certificate $certificate)
    {
        $certificate->delete();

        return response()->noContent();
    }
}
