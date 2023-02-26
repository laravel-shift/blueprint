<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'document' => $this->document,
            'expiry_date' => $this->expiry_date,
            'remarks' => $this->remarks,
            'certificate' => CertificateResource::make($this->whenLoaded('certificate')),
            'certificates' => CertificateCollection::make($this->whenLoaded('certificates')),
        ];
    }
}
