"use client";

import { useEffect, type ReactNode } from "react";
import { ConfirmDialog, NotificationStack } from "@/components";
import { useThemeStore } from "@/stores/useThemeStore";

export default function AppProviders({ children }: { children: ReactNode }) {
  const isDark = useThemeStore((state) => state.isDark);

  useEffect(() => {
    document.documentElement.classList.toggle("dark", isDark);
  }, [isDark]);

  return (
    <>
      {children}
      <NotificationStack />
      <ConfirmDialog />
    </>
  );
}
