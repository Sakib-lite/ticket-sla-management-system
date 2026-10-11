"use client";

import { DownloadSimple, File as FileIcon, Trash } from "@phosphor-icons/react";
import { attachmentApi } from "@/services";
import type { Attachment } from "@/types";
import { formatBytes } from "@/utils";

type AttachmentListProps = {
  attachments: Attachment[];
  canDelete?: (attachment: Attachment) => boolean;
  onDelete?: (attachmentId: number) => Promise<void>;
};

export default function AttachmentList({ attachments, canDelete, onDelete }: AttachmentListProps) {
  if (attachments.length === 0) {
    return null;
  }

  return (
    <ul className="flex flex-wrap gap-2">
      {attachments.map((attachment) => (
        <li
          key={attachment.id}
          className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-sm"
        >
          <FileIcon size={16} className="text-slate-400" />
          <button
            type="button"
            onClick={() => attachmentApi.download(attachment)}
            className="inline-flex items-center gap-1 text-slate-800 hover:underline"
          >
            <span className="max-w-48 truncate">{attachment.original_name}</span>
            <DownloadSimple size={14} className="text-slate-400" />
          </button>
          <span className="text-xs text-slate-400">{formatBytes(attachment.size)}</span>
          {onDelete && canDelete?.(attachment) && (
            <button
              type="button"
              aria-label={`Delete ${attachment.original_name}`}
              onClick={() => onDelete(attachment.id)}
              className="rounded p-0.5 text-slate-400 hover:bg-red-50 hover:text-red-600"
            >
              <Trash size={14} />
            </button>
          )}
        </li>
      ))}
    </ul>
  );
}
