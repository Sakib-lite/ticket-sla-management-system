<?php

namespace App\Services\Attachments;

use App\Enums\ActivityType;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\ActivityRecorder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class AttachmentService
{
    public function __construct(
        private ActivityRecorder $activities,
        private string $disk,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, Attachment>
     */
    public function attach(Ticket|TicketReply $attachable, User $uploader, array $files): Collection
    {
        if ($files === []) {
            return new Collection;
        }

        $ticket = $attachable instanceof Ticket ? $attachable : $attachable->ticket;

        return DB::transaction(function () use ($attachable, $ticket, $uploader, $files) {
            $attachments = new Collection;

            foreach ($files as $file) {
                $attachments->push($this->store($attachable, $ticket, $uploader, $file));
            }

            $this->activities->record($ticket, ActivityType::AttachmentAdded, $uploader, [
                'count' => $attachments->count(),
                'names' => $attachments->pluck('original_name')->all(),
                'reply_id' => $attachable instanceof TicketReply ? $attachable->id : null,
            ]);

            return $attachments;
        });
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function delete(Attachment $attachment): void
    {
        DB::transaction(function () use ($attachment) {
            $attachment->delete();
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    private function store(Ticket|TicketReply $attachable, Ticket $ticket, User $uploader, UploadedFile $file): Attachment
    {
        $path = $file->store("attachments/tickets/{$ticket->id}", $this->disk);

        return $attachable->attachments()->create([
            'uploaded_by' => $uploader->id,
            'disk' => $this->disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
