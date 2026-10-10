"use client";

import { useEffect } from "react";
import { readToken } from "@/api";
import { authApi } from "@/services";
import { useAuthStore } from "@/stores/useAuthStore";

export const useSession = () => {
  const { user, isReady, setUser } = useAuthStore();

  useEffect(() => {
    if (isReady) {
      return;
    }

    if (!readToken()) {
      setUser(null);
      return;
    }

    authApi
      .fetchCurrentUser()
      .then(setUser)
      .catch(() => setUser(null));
  }, [isReady, setUser]);

  return { user, isReady };
};
