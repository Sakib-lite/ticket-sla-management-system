"use client";

import { useState } from "react";
import { AttachmentList, Button, Form } from "@/components";
import { attachmentApi } from "@/services";
import type { Attachment } from "@/types";

type TicketAttachmentsProps = {
  attachments: Attachment[];
  canAttach: boolean;
  canDelete: (attachment: Attachment) => boolean;
  onUpload: (files: File[]) => Promise<void>;
  onDelete: (attachmentId: number) => Promise<void>;
};

export default function TicketAttachments({
  attachments,
  canAttach,
  canDelete,
  onUpload,
  onDelete,
}: TicketAttachmentsProps) {
  const [pending, setPending] = useState<File[]>([]);

  if (attachments.length === 0 && !canAttach) {
    return null;
  }

  return (
    <section className="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
      <h2 className="text-sm font-semibold text-slate-900">
        Attachments{" "}
        {attachments.length > 0 && <span className="text-slate-400">({attachments.length})</span>}
      </h2>
      {attachments.length > 0 ? (
        <AttachmentList attachments={attachments} canDelete={canDelete} onDelete={onDelete} />
      ) : (
        <p className="text-sm text-slate-500">No files attached.</p>
      )}
      {canAttach && (
        <div className="space-y-3 border-t border-slate-100 pt-4">
          <Form.FileField
            label="Add files"
            files={pending}
            accept={attachmentApi.ACCEPTED_FILE_TYPES}
            maxFiles={attachmentApi.MAX_FILES}
            maxBytes={attachmentApi.MAX_FILE_BYTES}
            onChange={setPending}
          />
          {pending.length > 0 && (
            <Button.Async
              tone="ghost"
              onClick={async () => {
                await onUpload(pending);
                setPending([]);
              }}
            >
              Upload {pending.length} file{pending.length === 1 ? "" : "s"}
            </Button.Async>
          )}
        </div>
      )}
    </section>
  );
}
