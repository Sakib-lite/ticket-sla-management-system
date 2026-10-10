import { create } from "zustand";

export type NotifyTone = "success" | "info" | "warning" | "error";

export type Notification = {
  id: number;
  tone: NotifyTone;
  message: string;
};

type NotifyStore = {
  notifications: Notification[];
  push: (message: string, tone: NotifyTone) => void;
  dismiss: (id: number) => void;
};

let nextId = 1;
const dismissAfter = 6000;

export const useNotifyStore = create<NotifyStore>((set, get) => ({
  notifications: [],
  push: (message, tone) => {
    const id = nextId++;
    set((state) => ({ notifications: [...state.notifications, { id, tone, message }] }));
    setTimeout(() => get().dismiss(id), dismissAfter);
  },
  dismiss: (id) =>
    set((state) => ({ notifications: state.notifications.filter((item) => item.id !== id) })),
}));

export const notify = {
  success: (message: string) => useNotifyStore.getState().push(message, "success"),
  info: (message: string) => useNotifyStore.getState().push(message, "info"),
  warning: (message: string) => useNotifyStore.getState().push(message, "warning"),
  error: (message: string) => useNotifyStore.getState().push(message, "error"),
};
