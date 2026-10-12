"use client";

import { useEffect, useEffectEvent } from "react";

export const usePolling = (callback: () => void, intervalMs: number, isActive: boolean) => {
  const tick = useEffectEvent(callback);

  useEffect(() => {
    if (!isActive) {
      return;
    }
    const timer = setInterval(() => tick(), intervalMs);
    return () => clearInterval(timer);
  }, [intervalMs, isActive]);
};
