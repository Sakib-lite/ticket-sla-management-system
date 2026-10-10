import type { ReactNode } from "react";
import { AppShell, RequireAuth } from "@/components";

export default function DashboardLayout({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <AppShell>{children}</AppShell>
    </RequireAuth>
  );
}
