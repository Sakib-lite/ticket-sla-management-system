"use client";

import { useRouter } from "next/navigation";
import { SignOut } from "@phosphor-icons/react";
import { Form } from "@/components";
import { LOGIN_PATH } from "@/constants/auth";
import { ROLE_LABELS } from "@/constants/tickets";
import { confirmAction } from "@/hooks/useConfirm";
import { notify } from "@/hooks/useNotify";
import { authApi } from "@/services";
import { useAuthStore } from "@/stores/useAuthStore";
import type { RequestFailure } from "@/types";

export default function UserMenu() {
  const router = useRouter();
  const user = useAuthStore((state) => state.user);
  const signOut = useAuthStore((state) => state.signOut);
  const updateUser = useAuthStore((state) => state.updateUser);

  if (!user) {
    return null;
  }

  const toggleAvailability = async (isAvailable: boolean) => {
    updateUser({ is_available: isAvailable });
    try {
      await authApi.updateAvailability(isAvailable);
      notify.info(
        isAvailable ? "You will receive new tickets." : "New tickets will go to other agents."
      );
    } catch (failure) {
      updateUser({ is_available: !isAvailable });
      notify.error((failure as RequestFailure).message);
    }
  };

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
      {user.role === "agent" && (
        <div className="hidden items-center gap-2 text-xs text-slate-600 md:flex">
          <span>{user.is_available ? "Available" : "Away"}</span>
          <Form.Switch
            checked={user.is_available ?? true}
            label="Available for new tickets"
            onChange={toggleAvailability}
          />
        </div>
      )}
      <div className="text-right">
        <p className="text-sm font-medium text-slate-900">{user.name}</p>
        <p className="text-xs text-slate-500">{ROLE_LABELS[user.role]}</p>
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
