"use client";

import { CheckCircle, Info, Warning, XCircle, X } from "@phosphor-icons/react";
import type { Notification, NotifyTone } from "@/hooks/useNotify";
import { classNames } from "@/utils";

const toneStyles: Record<NotifyTone, string> = {
  success: "border-emerald-200 bg-emerald-50 text-emerald-900",
  info: "border-sky-200 bg-sky-50 text-sky-900",
  warning: "border-amber-200 bg-amber-50 text-amber-900",
  error: "border-red-200 bg-red-50 text-red-900",
};

const toneIcons: Record<NotifyTone, typeof Info> = {
  success: CheckCircle,
  info: Info,
  warning: Warning,
  error: XCircle,
};

type NotificationItemProps = {
  notification: Notification;
  onDismiss: (id: number) => void;
};

export default function NotificationItem({ notification, onDismiss }: NotificationItemProps) {
  const Icon = toneIcons[notification.tone];

  return (
    <div
      className={classNames(
        "flex w-80 items-start gap-3 rounded-lg border px-4 py-3 shadow-sm",
        toneStyles[notification.tone]
      )}
    >
      <Icon size={20} weight="fill" className="mt-0.5 shrink-0" />
      <p className="flex-1 text-sm">{notification.message}</p>
      <button
        type="button"
        onClick={() => onDismiss(notification.id)}
        aria-label="Dismiss notification"
        className="shrink-0 rounded p-0.5 hover:bg-black/5"
      >
        <X size={16} />
      </button>
    </div>
  );
}
