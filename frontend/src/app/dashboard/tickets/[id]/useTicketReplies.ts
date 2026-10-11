"use client";

import { useFetch } from "@/hooks/useFetch";
import { notify } from "@/hooks/useNotify";
import { replyApi, type ReplyPayload } from "@/services";
import type { RequestFailure } from "@/types";

export const useTicketReplies = (ticketId: number) => {
  const { data, isLoading, setData } = useFetch(
    () => replyApi.list(ticketId),
    `ticket:${ticketId}:replies`
  );

  const add = async (payload: ReplyPayload, files: File[]) => {
    try {
      const reply = await replyApi.create(ticketId, payload, files);
      setData([...(data ?? []), reply]);
      notify.success(payload.is_internal ? "Internal note added." : "Reply sent.");
    } catch (failure) {
      notify.error((failure as RequestFailure).message);
      throw failure;
    }
  };

  return { replies: data ?? [], isLoading, add };
};
