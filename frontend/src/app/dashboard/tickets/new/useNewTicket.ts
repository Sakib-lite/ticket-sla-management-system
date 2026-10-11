"use client";

import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import { ticketPath } from "@/constants/paths";
import { notify } from "@/hooks/useNotify";
import { ticketApi } from "@/services";
import type { FieldErrors, RequestFailure, TicketPriority } from "@/types";

export type NewTicketForm = {
  subject: string;
  description: string;
  priority: TicketPriority | "";
  category_id: string;
};

const emptyForm: NewTicketForm = {
  subject: "",
  description: "",
  priority: "medium",
  category_id: "",
};

export const useNewTicket = () => {
  const router = useRouter();
  const [form, setForm] = useState<NewTicketForm>(emptyForm);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const setField = <K extends keyof NewTicketForm>(field: K, value: NewTicketForm[K]) =>
    setForm((current) => ({ ...current, [field]: value }));

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setIsSubmitting(true);
    setErrors({});

    try {
      const ticket = await ticketApi.create({
        subject: form.subject,
        description: form.description,
        priority: form.priority as TicketPriority,
        category_id: Number(form.category_id),
      });
      notify.success(`Ticket ${ticket.ticket_number} created.`);
      router.push(ticketPath(ticket.id));
    } catch (failure) {
      const { message, errors: fieldErrors } = failure as RequestFailure;
      setErrors(fieldErrors);
      if (!Object.keys(fieldErrors).length) {
        notify.error(message);
      }
      setIsSubmitting(false);
    }
  };

  return { form, setField, errors, isSubmitting, submit };
};
