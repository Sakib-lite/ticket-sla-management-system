<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentsRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Services\Attachments\AttachmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly AttachmentService $attachments) {}

    public function store(StoreAttachmentsRequest $request, Ticket $ticket): JsonResponse
    {
        $attachments = $this->attachments->attach($ticket, $request->user(), $request->files());

        return AttachmentResource::collection($attachments)->response()->setStatusCode(201);
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return $this->attachments->download($attachment);
    }

    public function destroy(Attachment $attachment): Response
    {
        $this->authorize('delete', $attachment);

        $this->attachments->delete($attachment);

        return response()->noContent();
    }
}
