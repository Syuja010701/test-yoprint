<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UploadResource;
use App\Jobs\ProcessCsvUpload;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('file');

        // ✅ Hitung hash dari file sementara (sebelum disimpan)
        $hash = hash_file('sha256', $file->getRealPath());

        // ✅ Baru simpan ke storage/app/uploads
        $path = $file->store('uploads');

        $existing = Upload::where('file_hash', $hash)->first();

        $upload = Upload::create([
            'filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_hash' => $hash,
            'status' => $existing ? 'skipped' : 'pending',
            'message' => $existing ? 'File identical to previous upload; skipped processing.' : null,
        ]);
            
        if (! $existing) {
            ProcessCsvUpload::dispatch($upload->id);
        }

        return redirect()->back()->with('status', 'File uploaded. Processing in background.');
    }


    // API endpoint to return uploads list (transformer used)
    public function listApi()
    {
        $uploads = Upload::latest()->take(50)->get();
        return UploadResource::collection($uploads);
    }
}
