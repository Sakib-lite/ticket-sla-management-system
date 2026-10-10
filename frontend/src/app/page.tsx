"use client";

import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { DASHBOARD_PATH, LOGIN_PATH } from "@/constants/auth";
import { useSession } from "@/hooks/useSession";
import { Loader } from "@/components/feedback";

export default function HomePage() {
  const router = useRouter();
  const { user, isReady } = useSession();

  useEffect(() => {
    if (isReady) {
      router.replace(user ? DASHBOARD_PATH : LOGIN_PATH);
    }
  }, [isReady, user, router]);

  return (
    <div className="flex h-dvh items-center justify-center text-slate-400">
      <Loader size={24} />
    </div>
  );
}
