"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { Loader } from "@/components";
import { DASHBOARD_PATH } from "@/constants/paths";
import { useSession } from "@/hooks/useSession";
import LoginForm from "./fragments/LoginForm";
import { useLogin } from "./useLogin";

export default function LoginPage() {
  const router = useRouter();
  const { user, isReady } = useSession();
  const form = useLogin();

  useEffect(() => {
    if (isReady && user) {
      router.replace(DASHBOARD_PATH);
    }
  }, [isReady, user, router]);

  if (!isReady || user) {
    return (
      <div className="flex h-dvh items-center justify-center text-slate-400">
        <Loader size={24} />
      </div>
    );
  }

  return (
    <main className="flex min-h-dvh items-center justify-center bg-slate-50 px-6">
      <div className="w-full max-w-sm space-y-6 rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <div className="space-y-1">
          <h1 className="text-xl font-semibold text-slate-900">Sign in</h1>
          <p className="text-sm text-slate-500">Access your support desk.</p>
        </div>
        <LoginForm
          email={form.email}
          password={form.password}
          errors={form.errors}
          isSubmitting={form.isSubmitting}
          onEmailChange={form.setEmail}
          onPasswordChange={form.setPassword}
          onSubmit={form.submit}
        />
      </div>
    </main>
  );
}
