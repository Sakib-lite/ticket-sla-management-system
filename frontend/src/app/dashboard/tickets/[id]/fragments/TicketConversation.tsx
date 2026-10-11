"use client";

import { LockSimple } from "@phosphor-icons/react";
import { AttachmentList, Loader } from "@/components";
import { useAuthStore } from "@/stores/useAuthStore";
import type { Reply, Ticket } from "@/types";
import { classNames, formatDateTime } from "@/utils";
import ReplyComposer from "./ReplyComposer";
import { useTicketReplies } from "../useTicketReplies";

type TicketConversationProps = {
  ticket: Ticket;
  onReplied: () => void;
};

function ReplyBubble({ reply, isMine }: { reply: Reply; isMine: boolean }) {
  const isStaff = reply.author.role !== "customer";

  return (
    <article
      className={classNames(
        "rounded-lg border p-4",
        reply.is_internal
          ? "border-amber-200 bg-amber-50"
          : isStaff
            ? "border-slate-200 bg-slate-50"
            : "border-slate-200 bg-white"
      )}
    >
      <header className="mb-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
        <span className="font-medium text-slate-800">
          {reply.author.name}
          {isMine && " (you)"}
        </span>
        <span>·</span>
        <time dateTime={reply.created_at}>{formatDateTime(reply.created_at)}</time>
        {reply.is_internal && (
          <span className="inline-flex items-center gap-1 rounded-full bg-amber-200 px-2 py-0.5 text-amber-900">
            <LockSimple size={12} /> Internal note
          </span>
        )}
      </header>
      <p className="text-sm leading-relaxed whitespace-pre-wrap text-slate-800">{reply.body}</p>
      {reply.attachments && reply.attachments.length > 0 && (
        <div className="mt-3">
          <AttachmentList attachments={reply.attachments} />
        </div>
      )}
    </article>
  );
}

export default function TicketConversation({ ticket, onReplied }: TicketConversationProps) {
  const user = useAuthStore((state) => state.user);
  const { replies, isLoading, add } = useTicketReplies(ticket.id);
  const canWriteInternal = user?.role !== "customer";
  const isClosed = ticket.status === "closed";

  return (
    <section className="space-y-4">
      <h2 className="text-sm font-semibold text-slate-900">
        Conversation{" "}
        {replies.length > 0 && <span className="text-slate-400">({replies.length})</span>}
      </h2>
      {isLoading && replies.length === 0 ? (
        <div className="flex justify-center py-6 text-slate-400">
          <Loader size={20} />
        </div>
      ) : replies.length === 0 ? (
        <p className="text-sm text-slate-500">No replies yet.</p>
      ) : (
        <div className="space-y-3">
          {replies.map((reply) => (
            <ReplyBubble key={reply.id} reply={reply} isMine={reply.author.id === user?.id} />
          ))}
        </div>
      )}
      {isClosed ? (
        <p className="rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
          This ticket is closed and no longer accepts replies.
        </p>
      ) : (
        <ReplyComposer
          canWriteInternal={canWriteInternal}
          onSubmit={async (payload, files) => {
            await add(payload, files);
            if (!payload.is_internal) {
              onReplied();
            }
          }}
        />
      )}
    </section>
  );
}
