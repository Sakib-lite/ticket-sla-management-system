"use client";

import { usePathname, useRouter, useSearchParams } from "next/navigation";
import type { TicketListParams, TicketSort, TicketView } from "@/services";
import type { SlaStatus, TicketPriority, TicketStatus } from "@/types";

const DEFAULT_VIEW: TicketView = "active";

const readNumber = (value: string | null) => {
  const parsed = Number(value);
  return value && Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
};

export const useTicketFilters = () => {
  const searchParams = useSearchParams();
  const router = useRouter();
  const pathname = usePathname();

  const filters: TicketListParams = {
    view: (searchParams.get("view") as TicketView | null) ?? DEFAULT_VIEW,
    search: searchParams.get("search") ?? undefined,
    status: (searchParams.get("status") as TicketStatus | null) ?? undefined,
    priority: (searchParams.get("priority") as TicketPriority | null) ?? undefined,
    sla_status: (searchParams.get("sla_status") as SlaStatus | null) ?? undefined,
    category_id: readNumber(searchParams.get("category_id")),
    assignee: searchParams.get("assignee") ?? undefined,
    sort: (searchParams.get("sort") as TicketSort | null) ?? undefined,
    direction: (searchParams.get("direction") as "asc" | "desc" | null) ?? undefined,
    page: readNumber(searchParams.get("page")),
  };

  const update = (changes: Partial<TicketListParams>) => {
    const next = new URLSearchParams(searchParams);

    Object.entries(changes).forEach(([key, value]) => {
      if (value === undefined || value === null || value === "") {
        next.delete(key);
      } else {
        next.set(key, String(value));
      }
    });

    if (!("page" in changes)) {
      next.delete("page");
    }

    const query = next.toString();
    router.replace(query ? `${pathname}?${query}` : pathname);
  };

  const reset = () => router.replace(pathname);

  const hasActiveFilters = Boolean(
    filters.search ||
    filters.status ||
    filters.priority ||
    filters.sla_status ||
    filters.category_id ||
    filters.assignee
  );

  return { filters, update, reset, hasActiveFilters };
};
