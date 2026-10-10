"use client";

import { Button } from "@/components";
import { notify } from "@/hooks/useNotify";
import DraftTicketModal from "./fragments/DraftTicketModal";
import { useFoundationPreview } from "./useFoundationPreview";

export default function HomePage() {
  const preview = useFoundationPreview();

  return (
    <main className="mx-auto flex min-h-dvh max-w-2xl flex-col justify-center gap-8 px-6">
      <div className="space-y-2">
        <h1 className="text-3xl font-semibold text-slate-900">Support Desk</h1>
        <p className="text-slate-600">Ticket and SLA management for support teams.</p>
      </div>

      <div className="flex flex-wrap gap-3">
        <Button.Primary onClick={preview.openModal}>New draft</Button.Primary>
        <Button.Primary tone="ghost" onClick={() => notify.success("Notifications are wired up.")}>
          Show notification
        </Button.Primary>
        <Button.Async tone="danger" onClick={preview.discardDraft}>
          Discard draft
        </Button.Async>
      </div>

      {preview.subject && (
        <p className="text-sm text-slate-600">
          Current draft: <span className="font-medium text-slate-900">{preview.subject}</span>
        </p>
      )}

      <DraftTicketModal
        isOpen={preview.isModalOpen}
        subject={preview.subject}
        priority={preview.priority}
        onSubjectChange={preview.setSubject}
        onPriorityChange={preview.setPriority}
        onClose={preview.closeModal}
        onSave={preview.saveDraft}
      />
    </main>
  );
}
