"use client";

import type { FormEvent } from "react";
import Link from "next/link";
import { Button, Form } from "@/components";
import { TICKETS_PATH } from "@/constants/paths";
import { TICKET_PRIORITY_OPTIONS, type Option } from "@/constants/tickets";
import { attachmentApi } from "@/services";
import type { FieldErrors } from "@/types";
import type { NewTicketForm } from "../useNewTicket";

type TicketFormProps = {
  form: NewTicketForm;
  errors: FieldErrors;
  categoryOptions: Option[];
  isSubmitting: boolean;
  onFieldChange: <K extends keyof NewTicketForm>(field: K, value: NewTicketForm[K]) => void;
  onSubmit: (event: FormEvent) => void;
};

export default function TicketForm({
  form,
  errors,
  categoryOptions,
  isSubmitting,
  onFieldChange,
  onSubmit,
}: TicketFormProps) {
  return (
    <form onSubmit={onSubmit} className="space-y-5">
      <Form.TextField
        label="Subject"
        value={form.subject}
        placeholder="Short summary of the problem"
        error={errors.subject?.[0]}
        onChange={(event) => onFieldChange("subject", event.target.value)}
      />
      <Form.TextAreaField
        label="Description"
        value={form.description}
        placeholder="What happened, what you expected, and anything you already tried"
        error={errors.description?.[0]}
        onChange={(event) => onFieldChange("description", event.target.value)}
      />
      <div className="grid gap-5 sm:grid-cols-2">
        <Form.SelectField
          label="Priority"
          value={form.priority}
          options={TICKET_PRIORITY_OPTIONS}
          error={errors.priority?.[0]}
          onChange={(event) =>
            onFieldChange("priority", event.target.value as NewTicketForm["priority"])
          }
        />
        <Form.SelectField
          label="Category"
          value={form.category_id}
          placeholder="Choose a category"
          options={categoryOptions}
          error={errors.category_id?.[0]}
          onChange={(event) => onFieldChange("category_id", event.target.value)}
        />
      </div>
      <Form.FileField
        label="Attachments"
        files={form.files}
        accept={attachmentApi.ACCEPTED_FILE_TYPES}
        maxFiles={attachmentApi.MAX_FILES}
        maxBytes={attachmentApi.MAX_FILE_BYTES}
        error={Object.entries(errors).find(([key]) => key.startsWith("files"))?.[1]?.[0]}
        onChange={(files) => onFieldChange("files", files)}
      />
      <div className="flex justify-end gap-2">
        <Link href={TICKETS_PATH}>
          <Button.Primary type="button" tone="ghost">
            Cancel
          </Button.Primary>
        </Link>
        <Button.Primary type="submit" isLoading={isSubmitting}>
          Create ticket
        </Button.Primary>
      </div>
    </form>
  );
}
