"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import { STATUS_ACTION_LABELS, isFinishedStatus } from "@/constants/tickets";
import { confirmAction } from "@/hooks/useConfirm";
import { useFetch } from "@/hooks/useFetch";
import { notify } from "@/hooks/useNotify";
import { ticketApi, type TicketUpdatePayload } from "@/services";
import { useAuthStore } from "@/stores/useAuthStore";
import type { RequestFailure, TicketStatus } from "@/types";

const CONFIRMED_STATUSES: TicketStatus[] = ["resolved", "closed"];

export const useTicketDetail = () => {
  const { id } = useParams<{ id: string }>();
  const user = useAuthStore((state) => state.user);
  const {
    data: ticket,
    error,
    isLoading,
    setData,
  } = useFetch(() => ticketApi.show(id), `ticket:${id}`);
  const [isEditing, setIsEditing] = useState(false);

  const isStaff = user?.role !== "customer";
  const isOwner = ticket?.creator.id === user?.id;
  const isActive = ticket ? !isFinishedStatus(ticket.status) : false;

  const permissions = {
    canEdit: isStaff || (isOwner && isActive),
    canChangePriority: isStaff,
    canChangeStatus: isStaff,
  };

  const update = async (payload: TicketUpdatePayload) => {
    if (!ticket) {
      return;
    }
    setData(await ticketApi.update(ticket.id, payload));
    notify.success("Ticket updated.");
  };

  const changeStatus = async (status: TicketStatus) => {
    if (!ticket) {
      return;
    }

    if (CONFIRMED_STATUSES.includes(status)) {
      const confirmed = await confirmAction({
        title: `${STATUS_ACTION_LABELS[status]} ${ticket.ticket_number}?`,
        description:
          status === "closed"
            ? "Closed tickets are final and cannot be reopened."
            : "The customer will be notified that their issue has been resolved.",
        confirmText: STATUS_ACTION_LABELS[status],
        tone: status === "closed" ? "danger" : "warning",
      });
      if (!confirmed) {
        return;
      }
    }

    try {
      setData(await ticketApi.changeStatus(ticket.id, status));
      notify.success(`Ticket marked as ${STATUS_ACTION_LABELS[status].toLowerCase()}.`);
    } catch (failure) {
      notify.error((failure as RequestFailure).message);
    }
  };

  return {
    ticket,
    error,
    isLoading,
    permissions,
    isEditing,
    openEditor: () => setIsEditing(true),
    closeEditor: () => setIsEditing(false),
    update,
    changeStatus,
  };
};
