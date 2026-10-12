import { create } from "zustand";
import { clearToken, storeToken } from "@/api";
import type { User } from "@/types";

type AuthStore = {
  user: User | null;
  isReady: boolean;
  signIn: (user: User, token: string) => void;
  signOut: () => void;
  setUser: (user: User | null) => void;
  updateUser: (changes: Partial<User>) => void;
};

export const useAuthStore = create<AuthStore>((set) => ({
  user: null,
  isReady: false,
  signIn: (user, token) => {
    storeToken(token);
    set({ user, isReady: true });
  },
  signOut: () => {
    clearToken();
    set({ user: null, isReady: true });
  },
  setUser: (user) => set({ user, isReady: true }),
  updateUser: (changes) =>
    set((state) => ({ user: state.user ? { ...state.user, ...changes } : state.user })),
}));

export const isAdministrator = (user: User | null) => user?.role === "administrator";
export const isAgent = (user: User | null) => user?.role === "agent";
export const isCustomer = (user: User | null) => user?.role === "customer";
