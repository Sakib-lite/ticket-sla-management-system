"use client";

import Link from "next/link";
import { Button, EmptyState, Loader } from "@/components";
import { TICKETS_PATH } from "@/constants/paths";
import { useAgents } from "@/hooks/useAgents";
import { useCategories } from "@/hooks/useCategories";
import EditTicketModal from "./fragments/EditTicketModal";
import TicketAssignmentPanel from "./fragments/TicketAssignmentPanel";
import TicketConversation from "./fragments/TicketConversation";
import TicketHeader from "./fragments/TicketHeader";
import TicketMetaPanel from "./fragments/TicketMetaPanel";
import TicketStatusActions from "./fragments/TicketStatusActions";
import { useTicketDetail } from "./useTicketDetail";

export default function TicketDetailPage() {
  const detail = useTicketDetail();
  const { options: categoryOptions } = useCategories();
  const { options: agentOptions, refresh: refreshAgents } = useAgents();

  if (detail.isLoading) {
    return (
      <div className="flex justify-center py-20 text-slate-400">
        <Loader size={24} />
      </div>
    );
  }

  if (!detail.ticket) {
    return (
      <EmptyState
        title={detail.error?.status === 403 ? "You cannot view this ticket" : "Ticket not found"}
        description={detail.error?.message}
        action={
          <Link href={TICKETS_PATH}>
            <Button.Primary tone="ghost">Back to tickets</Button.Primary>
          </Link>
        }
      />
    );
  }

  const { ticket, permissions } = detail;

  return (
    <div className="space-y-6">
      <TicketHeader ticket={ticket} canEdit={permissions.canEdit} onEdit={detail.openEditor} />
      <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div className="space-y-6">
          <section className="rounded-lg border border-slate-200 bg-white p-5">
            <h2 className="mb-3 text-sm font-semibold text-slate-900">Description</h2>
            <p className="text-sm leading-relaxed whitespace-pre-wrap text-slate-700">
              {ticket.description}
            </p>
          </section>
          {permissions.canChangeStatus && (
            <section className="rounded-lg border border-slate-200 bg-white p-5">
              <h2 className="mb-3 text-sm font-semibold text-slate-900">Update status</h2>
              <TicketStatusActions status={ticket.status} onChange={detail.changeStatus} />
            </section>
          )}
          <TicketConversation ticket={ticket} onReplied={detail.refresh} />
        </div>
        <div className="space-y-6">
          <TicketAssignmentPanel
            ticket={ticket}
            agentOptions={agentOptions}
            canAssign={permissions.canAssign}
            canClaim={permissions.canClaim}
            onAssign={(agentId) => detail.assign(agentId).then(refreshAgents)}
            onClaim={() => detail.claim().then(refreshAgents)}
            onUnassign={() => detail.unassign().then(refreshAgents)}
          />
          <TicketMetaPanel
            ticket={ticket}
            categoryOptions={categoryOptions}
            canChangePriority={permissions.canChangePriority}
            onPriorityChange={(priority) => detail.update({ priority })}
            onCategoryChange={(category_id) => detail.update({ category_id })}
          />
        </div>
      </div>
      {detail.isEditing && (
        <EditTicketModal
          ticket={ticket}
          isOpen={detail.isEditing}
          onClose={detail.closeEditor}
          onSave={detail.update}
        />
      )}
    </div>
  );
}
