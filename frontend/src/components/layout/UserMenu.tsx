"use client";

import { useRouter } from "next/navigation";
import { SignOut } from "@phosphor-icons/react";
import { confirmAction } from "@/hooks/useConfirm";
import { LOGIN_PATH } from "@/constants/auth";
import { authApi } from "@/services";
import { useAuthStore } from "@/stores/useAuthStore";

export default function UserMenu() {
  const router = useRouter();
  const user = useAuthStore((state) => state.user);
  const signOut = useAuthStore((state) => state.signOut);

  if (!user) {
    return null;
  }

  const leave = async () => {
    const confirmed = await confirmAction({
      title: "Sign out",
      description: "You will need to sign in again to continue.",
      confirmText: "Sign out",
      tone: "warning",
    });

    if (confirmed) {
      await authApi.logout().catch(() => null);
      signOut();
      router.replace(LOGIN_PATH);
    }
  };

  return (
    <div className="flex items-center gap-3">
      <div className="text-right">
        <p className="text-sm font-medium text-slate-900">{user.name}</p>
        <p className="text-xs text-slate-500 capitalize">{user.role}</p>
      </div>
      <button
        type="button"
        onClick={leave}
        aria-label="Sign out"
        className="rounded-md p-2 text-slate-500 hover:bg-slate-100"
      >
        <SignOut size={18} />
      </button>
    </div>
  );
}
