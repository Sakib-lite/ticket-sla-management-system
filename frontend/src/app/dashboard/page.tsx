"use client";

import Link from "next/link";
import {
  CheckCircle,
  Fire,
  HourglassHigh,
  Plus,
  Tray,
  UserCircleMinus,
  Warning,
} from "@phosphor-icons/react";
import { Button, Loader, PageHeader } from "@/components";
import { NEW_TICKET_PATH, TICKETS_PATH } from "@/constants/paths";
import { ROLE_LABELS } from "@/constants/tickets";
import { useAuthStore } from "@/stores/useAuthStore";
import { formatDateTime } from "@/utils";
import AgentWorkload from "./fragments/AgentWorkload";
import PriorityBreakdown from "./fragments/PriorityBreakdown";
import StatTile from "./fragments/StatTile";
import { useDashboard } from "./useDashboard";

export default function DashboardPage() {
  const user = useAuthStore((state) => state.user);
  const { dashboard, isLoading } = useDashboard();

  return (
    <div className="space-y-6">
      <PageHeader
        title="Dashboard"
        description={
          user
            ? `${user.name} · ${ROLE_LABELS[user.role]}${
                dashboard ? ` · updated ${formatDateTime(dashboard.generated_at)}` : ""
              }`
            : undefined
        }
        actions={
          <Link href={NEW_TICKET_PATH}>
            <Button.Primary>
              <Plus size={16} /> New ticket
            </Button.Primary>
          </Link>
        }
      />
      {!dashboard && isLoading ? (
        <div className="flex justify-center py-20 text-slate-400">
          <Loader size={24} />
        </div>
      ) : dashboard ? (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <StatTile
              label="Open tickets"
              value={dashboard.metrics.open}
              href={`${TICKETS_PATH}?view=active`}
              icon={<Tray size={20} />}
            />
            <StatTile
              label="Unassigned"
              value={dashboard.metrics.unassigned}
              href={`${TICKETS_PATH}?view=active&assignee=unassigned`}
              icon={<UserCircleMinus size={20} />}
              tone={dashboard.metrics.unassigned > 0 ? "warning" : "neutral"}
            />
            <StatTile
              label="Critical"
              value={dashboard.metrics.critical}
              href={`${TICKETS_PATH}?view=active&priority=critical`}
              icon={<Fire size={20} />}
              tone={dashboard.metrics.critical > 0 ? "danger" : "neutral"}
            />
            <StatTile
              label="SLA breached"
              value={dashboard.metrics.breached}
              href={`${TICKETS_PATH}?view=active&sla_status=breached`}
              icon={<Warning size={20} />}
              tone={dashboard.metrics.breached > 0 ? "danger" : "neutral"}
            />
            <StatTile
              label="Approaching SLA"
              value={dashboard.metrics.approaching}
              href={`${TICKETS_PATH}?view=active&sla_status=approaching`}
              icon={<HourglassHigh size={20} />}
              tone={dashboard.metrics.approaching > 0 ? "warning" : "neutral"}
            />
            <StatTile
              label="Resolved in the last 7 days"
              value={dashboard.metrics.resolved_last_7_days}
              href={`${TICKETS_PATH}?view=resolved`}
              icon={<CheckCircle size={20} />}
              tone="success"
            />
          </div>
          <div className="grid gap-6 lg:grid-cols-2">
            <PriorityBreakdown rows={dashboard.by_priority} />
            {dashboard.by_agent && <AgentWorkload rows={dashboard.by_agent} />}
          </div>
        </>
      ) : null}
    </div>
  );
}
