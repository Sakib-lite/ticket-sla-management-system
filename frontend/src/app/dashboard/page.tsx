"use client";

import { useAuthStore } from "@/stores/useAuthStore";

export default function DashboardPage() {
  const user = useAuthStore((state) => state.user);

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold text-slate-900">Dashboard</h1>
        <p className="text-slate-600">
          Signed in as {user?.name} with the {user?.role} role.
        </p>
      </div>
      <div className="rounded-lg border border-slate-200 bg-white p-6">
        <p className="text-sm text-slate-500">Ticket metrics will appear here.</p>
      </div>
    </div>
  );
}
