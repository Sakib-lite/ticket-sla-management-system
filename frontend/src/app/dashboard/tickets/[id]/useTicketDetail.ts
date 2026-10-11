"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import { STATUS_ACTION_LABELS, isFinishedStatus } from "@/constants/tickets";
import { confirmAction } from "@/hooks/useConfirm";
import { useFetch } from "@/hooks/useFetch";
import { notify } from "@/hooks/useNotify";
import { attachmentApi, ticketApi, type TicketUpdatePayload } from "@/services";
import { useAuthStore } from "@/stores/useAuthStore";
import type { Attachment, RequestFailure, Ticket, TicketStatus } from "@/types";

const CONFIRMED_STATUSES: TicketStatus[] = ["resolved", "closed"];

export const useTicketDetail = () => {
  const { id } = useParams<{ id: string }>();
  const user = useAuthStore((state) => state.user);
  const {
    data: ticket,
    error,
    isLoading,
    setData,
    refresh,
  } = useFetch(() => ticketApi.show(id), `ticket:${id}`);
  const [isEditing, setIsEditing] = useState(false);
  const [activityVersion, setActivityVersion] = useState(0);
  const touch = () => setActivityVersion((current) => current + 1);

  const isStaff = user?.role !== "customer";
  const isOwner = ticket?.creator.id === user?.id;
  const isActive = ticket ? !isFinishedStatus(ticket.status) : false;

  const permissions = {
    canEdit: isStaff || (isOwner && isActive),
    canAttach: isActive && (isStaff || isOwner),
    canDeleteAttachment: (attachment: Attachment) =>
      isActive && (user?.role === "administrator" || attachment.uploaded_by === user?.id),
    canChangePriority: isStaff,
    canChangeStatus: isStaff,
    canAssign: isStaff,
    canClaim: user?.role === "agent" && isActive,
  };

  const apply = async (action: () => Promise<Ticket>, successMessage: string) => {
    try {
      setData(await action());
      touch();
      notify.success(successMessage);
    } catch (failure) {
      const { status, message } = failure as RequestFailure;
      notify.error(message);
      if (status === 409) {
        refresh();
      }
      throw failure;
    }
  };

  const update = (payload: TicketUpdatePayload) =>
    ticket
      ? apply(() => ticketApi.update(ticket.id, payload), "Ticket updated.")
      : Promise.resolve();

  const assign = (agentId: number) =>
    ticket
      ? apply(() => ticketApi.assign(ticket.id, agentId), "Ticket assigned.")
      : Promise.resolve();

  const claim = () =>
    ticket ? apply(() => ticketApi.claim(ticket.id), "Ticket is now yours.") : Promise.resolve();

  const unassign = () =>
    ticket ? apply(() => ticketApi.unassign(ticket.id), "Ticket unassigned.") : Promise.resolve();

  const uploadAttachments = async (files: File[]) => {
    if (!ticket) {
      return;
    }
    try {
      const uploaded = await attachmentApi.upload(ticket.id, files);
      setData({ ...ticket, attachments: [...(ticket.attachments ?? []), ...uploaded] });
      touch();
      notify.success(`${uploaded.length} file${uploaded.length === 1 ? "" : "s"} attached.`);
    } catch (failure) {
      notify.error((failure as RequestFailure).message);
      throw failure;
    }
  };

  const removeAttachment = async (attachmentId: number) => {
    if (!ticket) {
      return;
    }
    try {
      await attachmentApi.remove(attachmentId);
      setData({
        ...ticket,
        attachments: (ticket.attachments ?? []).filter((item) => item.id !== attachmentId),
      });
      notify.success("Attachment removed.");
    } catch (failure) {
      notify.error((failure as RequestFailure).message);
    }
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

    await apply(
      () => ticketApi.changeStatus(ticket.id, status),
      `Ticket marked as ${STATUS_ACTION_LABELS[status].toLowerCase()}.`
    ).catch(() => undefined);
  };

  return {
    ticket,
    error,
    isLoading,
    refresh,
    activityVersion,
    touch,
    permissions,
    isEditing,
    openEditor: () => setIsEditing(true),
    closeEditor: () => setIsEditing(false),
    update,
    assign,
    claim,
    unassign,
    uploadAttachments,
    removeAttachment,
    changeStatus,
  };
};
