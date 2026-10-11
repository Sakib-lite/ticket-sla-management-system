"use client";

import { PageHeader } from "@/components";
import { useCategories } from "@/hooks/useCategories";
import TicketForm from "./fragments/TicketForm";
import { useNewTicket } from "./useNewTicket";

export default function NewTicketPage() {
  const { form, setField, errors, isSubmitting, submit } = useNewTicket();
  const { options: categoryOptions } = useCategories();

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <PageHeader
        title="New ticket"
        description="Tell us what went wrong and we will get it to the right person."
      />
      <div className="rounded-lg border border-slate-200 bg-white p-6">
        <TicketForm
          form={form}
          errors={errors}
          categoryOptions={categoryOptions}
          isSubmitting={isSubmitting}
          onFieldChange={setField}
          onSubmit={submit}
        />
      </div>
    </div>
  );
}
