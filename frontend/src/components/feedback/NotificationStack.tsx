"use client";

import { useNotifyStore } from "@/hooks/useNotify";
import NotificationItem from "./NotificationItem";

export default function NotificationStack() {
  const notifications = useNotifyStore((state) => state.notifications);
  const dismiss = useNotifyStore((state) => state.dismiss);

  if (!notifications.length) {
    return null;
  }

  return (
    <div className="fixed top-4 right-4 z-50 flex flex-col gap-2">
      {notifications.map((notification) => (
        <NotificationItem key={notification.id} notification={notification} onDismiss={dismiss} />
      ))}
    </div>
  );
}
