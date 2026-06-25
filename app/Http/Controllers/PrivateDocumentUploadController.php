<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PrivateDocumentUploadController extends Controller
{
    public function index(Request $request, CurrentTenant $tenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', PrivateDocument::class);
        $query = PrivateDocument::forTenant($tenant->id())->select(['id', 'public_id', 'organizational_unit_id', 'original_name', 'mime_type', 'size_bytes', 'uploaded_by', 'created_at'])->with(['unit:id,name', 'uploader:id,name']);
        if ($request->filled('name')) {
            $query->where('original_name', 'ilike', '%'.$request->string('name').'%');
        }

        return view('documents.index', ['documents' => $query->latest()->paginate($pagination->resolve($request))]);
    }

    public function create(): View
    {
        $this->authorize('upload', PrivateDocument::class);

        return view('documents.create');
    }

    public function store(Request $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('upload', PrivateDocument::class);
        $data = $request->validate(['file' => ['required', 'file', 'max:'.config('taggo.private_document_max_size_kb', 10240), 'mimetypes:application/pdf,image/png,image/jpeg'], 'organizational_unit' => ['nullable', 'string', 'size:26']]);
        $tenant = $currentTenant->require();
        $file = $data['file'];
        $unitId = ! empty($data['organizational_unit']) ? OrganizationalUnit::forTenant($tenant->id)->where('public_id', $data['organizational_unit'])->value('id') : null;
        abort_if(! empty($data['organizational_unit']) && ! $unitId, 404);
        abort_unless($unitId === null || $request->user()->hasPermission($tenant, 'organizations.update') || $request->user()->organizationalUnits()->whereKey($unitId)->exists(), 403);
        $extension = $file->extension();
        $key = 'tenants/'.$tenant->public_id.'/documents/'.now()->format('Y/m').'/'.Str::ulid().'.'.$extension;
        if (! Storage::disk('private')->putFileAs(dirname($key), $file, basename($key))) {
            throw new RuntimeException('Unable to store private document.');
        }
        try {
            $document = DB::transaction(fn () => PrivateDocument::forceCreate(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unitId, 'uploaded_by' => $request->user()->id, 'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''), 'stored_name' => $key, 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', Storage::disk('private')->path($key)), 'disk' => 'private']));
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($key);
            throw $e;
        }
        $audit->record('document.uploaded', $document, [], ['name' => $document->original_name, 'mime_type' => $document->mime_type]);

        return redirect()->route('documents.index')->with('success', 'Documento enviado.');
    }
}
