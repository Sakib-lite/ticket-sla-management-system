import { create } from "zustand";

export type ConfirmTone = "danger" | "warning" | "info";

export type ConfirmOptions = {
  title: string;
  description?: string;
  confirmText?: string;
  cancelText?: string;
  tone?: ConfirmTone;
  onConfirm?: () => void | Promise<void>;
};

type ConfirmStore = {
  isOpen: boolean;
  isLoading: boolean;
  errorMessage: string;
  options: ConfirmOptions | null;
  resolve: ((confirmed: boolean) => void) | null;
  accept: () => Promise<void>;
  cancel: () => void;
};

export const useConfirmStore = create<ConfirmStore>((set, get) => ({
  isOpen: false,
  isLoading: false,
  errorMessage: "",
  options: null,
  resolve: null,
  accept: async () => {
    const { isLoading, options, resolve } = get();
    if (isLoading) {
      return;
    }

    set({ isLoading: true, errorMessage: "" });
    try {
      await options?.onConfirm?.();
      resolve?.(true);
      set({ isOpen: false, isLoading: false, options: null, resolve: null });
    } catch (failure) {
      const message = (failure as { message?: string })?.message;
      set({ isLoading: false, errorMessage: message || "Unable to complete this action." });
    }
  },
  cancel: () => {
    const { isLoading, resolve } = get();
    if (isLoading) {
      return;
    }
    resolve?.(false);
    set({ isOpen: false, errorMessage: "", options: null, resolve: null });
  },
}));

export const confirmAction = (options: ConfirmOptions) =>
  new Promise<boolean>((resolve) => {
    useConfirmStore.setState({
      isOpen: true,
      isLoading: false,
      errorMessage: "",
      options,
      resolve,
    });
  });
