"use client";

import { useState } from "react";
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

const initialsFor = (name: string) =>
  name
    .trim()
    .split(/\s+/)
    .map((part) => part[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();

export default function UserMenu() {
  const router = useRouter();
  const [isOpen, setIsOpen] = useState(false);
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
    setIsOpen(false);
    const confirmed = await confirmAction({
      title: "Log out",
      description: "You will need to log in again to continue.",
      confirmText: "Log out",
      tone: "warning",
    });

    if (confirmed) {
      await authApi.logout().catch(() => null);
      signOut();
      router.replace(LOGIN_PATH);
    }
  };

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setIsOpen((current) => !current)}
        aria-label="Account menu"
        className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-sm font-medium text-white transition hover:bg-slate-700"
      >
        {initialsFor(user.name)}
      </button>
      {isOpen && (
        <>
          <div className="fixed inset-0 z-30" onClick={() => setIsOpen(false)} />
          <div className="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
            <div className="px-4 py-3">
              <p className="text-sm font-medium text-slate-900">{user.name}</p>
              <p className="text-xs text-slate-500">{ROLE_LABELS[user.role]}</p>
            </div>
            {user.role === "agent" && (
              <div className="flex items-center justify-between border-t border-slate-100 px-4 py-3">
                <span className="text-sm text-slate-700">
                  {user.is_available ? "Available" : "Away"}
                </span>
                <Form.Switch
                  checked={user.is_available ?? true}
                  label="Available for new tickets"
                  onChange={toggleAvailability}
                />
              </div>
            )}
            <button
              type="button"
              onClick={leave}
              className="flex w-full items-center gap-2 border-t border-slate-100 px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50"
            >
              <SignOut size={16} /> Log out
            </button>
          </div>
        </>
      )}
    </div>
  );
}
