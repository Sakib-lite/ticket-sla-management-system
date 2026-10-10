"use client";

import { useEffect, type ReactNode } from "react";
import { useRouter } from "next/navigation";
import { LOGIN_PATH } from "@/constants/auth";
import { useSession } from "@/hooks/useSession";
import { Loader } from "@/components/feedback";

export default function RequireAuth({ children }: { children: ReactNode }) {
  const router = useRouter();
  const { user, isReady } = useSession();

  useEffect(() => {
    if (isReady && !user) {
      router.replace(LOGIN_PATH);
    }
  }, [isReady, user, router]);

  if (!isReady || !user) {
    return (
      <div className="flex h-dvh items-center justify-center text-slate-400">
        <Loader size={24} />
      </div>
    );
  }

  return <>{children}</>;
}
