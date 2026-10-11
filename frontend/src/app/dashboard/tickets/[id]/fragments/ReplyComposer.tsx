"use client";

import { useState, type FormEvent } from "react";
import { LockSimple, PaperPlaneRight } from "@phosphor-icons/react";
import { Button, Form } from "@/components";
import { attachmentApi, type ReplyPayload } from "@/services";
import type { FieldErrors, RequestFailure } from "@/types";
import { classNames } from "@/utils";

type ReplyComposerProps = {
  canWriteInternal: boolean;
  onSubmit: (payload: ReplyPayload, files: File[]) => Promise<void>;
};

export default function ReplyComposer({ canWriteInternal, onSubmit }: ReplyComposerProps) {
  const [body, setBody] = useState("");
  const [isInternal, setIsInternal] = useState(false);
  const [files, setFiles] = useState<File[]>([]);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [isSending, setIsSending] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setIsSending(true);
    setErrors({});
    try {
      await onSubmit({ body, is_internal: isInternal }, files);
      setBody("");
      setIsInternal(false);
      setFiles([]);
    } catch (failure) {
      setErrors((failure as RequestFailure).errors);
    } finally {
      setIsSending(false);
    }
  };

  const fileError = Object.entries(errors).find(([key]) => key.startsWith("files"))?.[1]?.[0];

  return (
    <form
      onSubmit={submit}
      className={classNames(
        "space-y-3 rounded-lg border p-4 transition",
        isInternal ? "border-amber-300 bg-amber-50" : "border-slate-200 bg-white"
      )}
    >
      <Form.TextAreaField
        label={isInternal ? "Internal note (customers never see this)" : "Reply"}
        value={body}
        placeholder={isInternal ? "Notes for the team…" : "Write a reply…"}
        error={errors.body?.[0]}
        onChange={(event) => setBody(event.target.value)}
      />
      <Form.FileField
        label="Attachments"
        files={files}
        accept={attachmentApi.ACCEPTED_FILE_TYPES}
        maxFiles={attachmentApi.MAX_FILES}
        maxBytes={attachmentApi.MAX_FILE_BYTES}
        error={fileError}
        onChange={setFiles}
      />
      <div className="flex flex-wrap items-center justify-between gap-3">
        {canWriteInternal ? (
          <label className="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
            <input
              type="checkbox"
              checked={isInternal}
              onChange={(event) => setIsInternal(event.target.checked)}
              className="h-4 w-4 rounded border-slate-300"
            />
            <LockSimple size={14} /> Internal note
          </label>
        ) : (
          <span />
        )}
        <Button.Primary type="submit" isLoading={isSending} disabled={!body.trim()}>
          <PaperPlaneRight size={16} /> {isInternal ? "Add note" : "Send reply"}
        </Button.Primary>
      </div>
    </form>
  );
}
