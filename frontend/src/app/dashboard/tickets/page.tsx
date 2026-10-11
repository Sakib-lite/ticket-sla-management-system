"use client";

import { Suspense } from "react";
import Link from "next/link";
import { Plus } from "@phosphor-icons/react";
import { Button, Loader, PageHeader, Pagination } from "@/components";
import { NEW_TICKET_PATH } from "@/constants/paths";
import { useAgents } from "@/hooks/useAgents";
import { useCategories } from "@/hooks/useCategories";
import { useAuthStore } from "@/stores/useAuthStore";
import TicketFilterBar from "./fragments/TicketFilterBar";
import TicketTable from "./fragments/TicketTable";
import { useTicketFilters } from "./useTicketFilters";
import { useTickets } from "./useTickets";

function TicketsPage() {
  const { filters, update, reset, hasActiveFilters } = useTicketFilters();
  const { tickets, meta, isLoading } = useTickets(filters);
  const { options: categoryOptions } = useCategories();
  const { agents } = useAgents();
  const isStaff = useAuthStore((state) => state.user?.role !== "customer");
  const agentOptions = isStaff
    ? agents.map((agent) => ({ label: agent.name, value: String(agent.id) }))
    : null;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Tickets"
        description={meta ? `${meta.total} ticket${meta.total === 1 ? "" : "s"}` : undefined}
        actions={
          <Link href={NEW_TICKET_PATH}>
            <Button.Primary>
              <Plus size={16} /> New ticket
            </Button.Primary>
          </Link>
        }
      />
      <TicketFilterBar
        filters={filters}
        categoryOptions={categoryOptions}
        agentOptions={agentOptions}
        hasActiveFilters={hasActiveFilters}
        onChange={update}
        onReset={reset}
      />
      <TicketTable tickets={tickets} isLoading={isLoading} hasActiveFilters={hasActiveFilters} />
      {meta && <Pagination meta={meta} onPageChange={(page) => update({ page })} />}
    </div>
  );
}

export default function TicketsRoute() {
  return (
    <Suspense
      fallback={
        <div className="flex justify-center py-20 text-slate-400">
          <Loader size={24} />
        </div>
      }
    >
      <TicketsPage />
    </Suspense>
  );
}
