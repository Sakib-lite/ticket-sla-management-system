"use client";

import { useState, type FormEvent } from "react";
import { Button, Form, Modal } from "@/components";
import type { TicketUpdatePayload } from "@/services";
import type { FieldErrors, RequestFailure, Ticket } from "@/types";

type EditTicketModalProps = {
  ticket: Ticket;
  isOpen: boolean;
  onClose: () => void;
  onSave: (payload: TicketUpdatePayload) => Promise<void>;
};

export default function EditTicketModal({ ticket, isOpen, onClose, onSave }: EditTicketModalProps) {
  const [subject, setSubject] = useState(ticket.subject);
  const [description, setDescription] = useState(ticket.description);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [isSaving, setIsSaving] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setIsSaving(true);
    setErrors({});
    try {
      await onSave({ subject, description });
      onClose();
    } catch (failure) {
      setErrors((failure as RequestFailure).errors);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <Modal isOpen={isOpen} title={`Edit ${ticket.ticket_number}`} onClose={onClose}>
      <form onSubmit={submit} className="space-y-4">
        <Form.TextField
          label="Subject"
          value={subject}
          error={errors.subject?.[0]}
          onChange={(event) => setSubject(event.target.value)}
        />
        <Form.TextAreaField
          label="Description"
          value={description}
          error={errors.description?.[0]}
          onChange={(event) => setDescription(event.target.value)}
        />
        <div className="flex justify-end gap-2">
          <Button.Primary type="button" tone="ghost" onClick={onClose} disabled={isSaving}>
            Cancel
          </Button.Primary>
          <Button.Primary type="submit" isLoading={isSaving}>
            Save changes
          </Button.Primary>
        </div>
      </form>
    </Modal>
  );
}
