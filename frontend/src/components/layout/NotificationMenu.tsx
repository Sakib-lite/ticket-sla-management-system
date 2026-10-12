"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Bell, Checks } from "@phosphor-icons/react";
import { ticketPath } from "@/constants/paths";
import { useNotifications } from "@/hooks/useNotifications";
import type { AppNotification } from "@/types";
import { classNames, formatRelativeTime } from "@/utils";

export default function NotificationMenu() {
  const router = useRouter();
  const [isOpen, setIsOpen] = useState(false);
  const { notifications, unreadCount, markRead, markAllRead } = useNotifications();

  const open = async (notification: AppNotification) => {
    setIsOpen(false);
    router.push(ticketPath(notification.data.ticket_id));
    await markRead(notification.id);
  };

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setIsOpen((current) => !current)}
        aria-label={unreadCount > 0 ? `${unreadCount} unread notifications` : "Notifications"}
        className="relative rounded-md p-2 text-slate-600 hover:bg-slate-100"
      >
        <Bell size={20} />
        {unreadCount > 0 && (
          <span className="absolute -top-0.5 -right-0.5 min-w-4.5 rounded-full bg-red-600 px-1 text-center text-[10px] leading-4 font-semibold text-white">
            {unreadCount > 99 ? "99+" : unreadCount}
          </span>
        )}
      </button>
      {isOpen && (
        <>
          <div className="fixed inset-0 z-30" onClick={() => setIsOpen(false)} />
          <div className="absolute right-0 z-40 mt-2 w-80 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg sm:w-96">
            <header className="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
              <span className="text-sm font-semibold text-slate-900">Notifications</span>
              {unreadCount > 0 && (
                <button
                  type="button"
                  onClick={markAllRead}
                  className="inline-flex items-center gap-1 text-xs text-slate-600 hover:text-slate-900"
                >
                  <Checks size={14} /> Mark all read
                </button>
              )}
            </header>
            <ul className="max-h-96 divide-y divide-slate-100 overflow-y-auto">
              {notifications.length === 0 && (
                <li className="px-4 py-8 text-center text-sm text-slate-500">
                  You are all caught up.
                </li>
              )}
              {notifications.map((notification) => (
                <li key={notification.id}>
                  <button
                    type="button"
                    onClick={() => open(notification)}
                    className={classNames(
                      "flex w-full flex-col gap-0.5 px-4 py-3 text-left transition hover:bg-slate-50",
                      !notification.read_at && "bg-sky-50/60"
                    )}
                  >
                    <span className="flex items-center gap-2 text-xs text-slate-500">
                      <span className="font-mono">{notification.data.ticket_number}</span>
                      <span>·</span>
                      <span>{formatRelativeTime(notification.created_at)}</span>
                      {!notification.read_at && (
                        <span className="ml-auto h-2 w-2 rounded-full bg-sky-500" aria-hidden />
                      )}
                    </span>
                    <span className="line-clamp-2 text-sm text-slate-800">
                      {notification.data.message}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          </div>
        </>
      )}
    </div>
  );
}
