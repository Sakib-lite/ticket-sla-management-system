"use client";

import { useEffect, useEffectEvent, useState } from "react";
import type { RequestFailure } from "@/types";

type Settled<T> = {
  key: string;
  data: T | null;
  error: RequestFailure | null;
};

export const useFetch = <T>(fetcher: () => Promise<T>, key: string) => {
  const [version, setVersion] = useState(0);
  const [settled, setSettled] = useState<Settled<T>>({ key: "", data: null, error: null });

  const requestKey = `${key}#${version}`;

  const load = useEffectEvent(async (isCancelled: () => boolean) => {
    try {
      const data = await fetcher();
      if (!isCancelled()) {
        setSettled({ key: requestKey, data, error: null });
      }
    } catch (failure) {
      if (!isCancelled()) {
        setSettled((current) => ({
          key: requestKey,
          data: current.data,
          error: failure as RequestFailure,
        }));
      }
    }
  });

  useEffect(() => {
    let cancelled = false;
    load(() => cancelled);
    return () => {
      cancelled = true;
    };
  }, [requestKey]);

  const refresh = () => setVersion((current) => current + 1);
  const setData = (data: T) => setSettled((current) => ({ ...current, data }));

  return {
    data: settled.data,
    error: settled.error,
    isLoading: settled.key !== requestKey,
    refresh,
    setData,
  };
};
