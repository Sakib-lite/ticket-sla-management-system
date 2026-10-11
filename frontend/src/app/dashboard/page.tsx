"use client";

import Link from "next/link";
import { Button, PageHeader } from "@/components";
import { TICKETS_PATH } from "@/constants/paths";
import { ROLE_LABELS } from "@/constants/tickets";
import { useAuthStore } from "@/stores/useAuthStore";

export default function DashboardPage() {
  const user = useAuthStore((state) => state.user);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Dashboard"
        description={user ? `Signed in as ${user.name} · ${ROLE_LABELS[user.role]}` : undefined}
        actions={
          <Link href={TICKETS_PATH}>
            <Button.Primary tone="ghost">View tickets</Button.Primary>
          </Link>
        }
      />
      <div className="rounded-lg border border-slate-200 bg-white p-6">
        <p className="text-sm text-slate-500">Ticket metrics will appear here.</p>
      </div>
    </div>
  );
}
