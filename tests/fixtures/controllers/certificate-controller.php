<?php

namespace App\Http\Controllers;

use App\Certificate;
use App\Http\Requests\CertificateStoreRequest;
use App\Http\Requests\CertificateUpdateRequest;
use App\Http\Resources\Certificate as CertificateResource;
use App\Http\Resources\CertificateCollection;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\CertificateCollection
     */
    public function index(Request $request)
    {
        $certificates = Certificate::all();

        return new CertificateCollection($certificates);
    }

    /**
     * @param \App\Http\Requests\CertificateStoreRequest $request
     * @return \App\Http\Resources\Certificate
     */
    public function store(CertificateStoreRequest $request)
    {
        $certificate = Certificate::create($request->all());

        return new CertificateResource($certificate);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Certificate $certificate
     * @return \App\Http\Resources\Certificate
     */
    public function show(Request $request, Certificate $certificate)
    {
        return new CertificateResource($certificate);
    }

    /**
     * @param \App\Http\Requests\CertificateUpdateRequest $request
     * @param \App\Certificate $certificate
     * @return \App\Http\Resources\Certificate
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

        return response()->noContent(200);
    }
}
