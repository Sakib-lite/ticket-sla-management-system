<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportErrorResource;
use App\Http\Resources\ImportResource;
use App\Models\Import;
use App\Services\Imports\ImportService;
use App\Support\Imports\TicketCsvReader;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    use AuthorizesRequests;

    private const PER_PAGE = 20;

    public function __construct(private readonly ImportService $imports) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Import::class);

        $imports = Import::query()
            ->when(! $request->user()->isAdministrator(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('user')
            ->latest()
            ->paginate(self::PER_PAGE);

        return ImportResource::collection($imports);
    }

    public function store(StoreImportRequest $request): ImportResource
    {
        $import = $this->imports->start($request->user(), $request->csv());

        return ImportResource::make($import->load('user'));
    }

    public function show(Import $import): ImportResource
    {
        $this->authorize('view', $import);

        return ImportResource::make($import->load('user'));
    }

    public function errors(Request $request, Import $import): AnonymousResourceCollection
    {
        $this->authorize('view', $import);

        $validated = $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);

        return ImportErrorResource::collection(
            $import->errors()->orderBy('row_number')->paginate($validated['per_page'] ?? self::PER_PAGE),
        );
    }

    public function template(): StreamedResponse
    {
        $this->authorize('create', Import::class);

        $columns = [...TicketCsvReader::OPTIONAL_COLUMNS, ...TicketCsvReader::REQUIRED_COLUMNS];
        $example = [
            'LEGACY-1001',
            '2026-01-15 10:30:00',
            'Printer on floor 3 stopped working',
            'Shows a paper jam but there is no paper inside.',
            'high',
            'Hardware',
            'customer@supportdesk.test',
        ];

        return response()->streamDownload(function () use ($columns, $example) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns, ',', '"', '');
            fputcsv($handle, $example, ',', '"', '');
            fclose($handle);
        }, 'ticket-import-template.csv', ['Content-Type' => 'text/csv']);
    }
}
