"use client";

import { useEffect } from "react";
import { Warning } from "@phosphor-icons/react";
import { Button } from "@/components";

export default function ErrorPage({ error, reset }: { error: Error; reset: () => void }) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <main className="flex min-h-dvh flex-col items-center justify-center gap-4 px-6 text-center">
      <Warning size={40} className="text-amber-500" />
      <div className="space-y-1">
        <h1 className="text-xl font-semibold text-slate-900">Something went wrong</h1>
        <p className="max-w-md text-sm text-slate-600">
          The page hit an unexpected error. You can try again, and if it keeps happening let the
          support team know.
        </p>
      </div>
      <Button.Primary onClick={reset}>Try again</Button.Primary>
    </main>
  );
}
