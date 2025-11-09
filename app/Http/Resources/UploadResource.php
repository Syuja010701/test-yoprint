<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'file_path' => $this->file_path,
            'file_hash' => $this->file_hash,
            'status' => $this->status,
            'message' => $this->message,
            'uploaded_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
