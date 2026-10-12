"use client";

import { useState } from "react";
import { useParams } from "next/navigation";
import { isImportRunning } from "@/constants/imports";
import { useFetch } from "@/hooks/useFetch";
import { usePolling } from "@/hooks/usePolling";
import { importApi } from "@/services";

const POLL_INTERVAL_MS = 2000;

export const useImportDetail = () => {
  const { id } = useParams<{ id: string }>();
  const importId = Number(id);
  const [errorPage, setErrorPage] = useState(1);

  const detail = useFetch(() => importApi.show(importId), `import:${importId}`);
  const isRunning = detail.data ? isImportRunning(detail.data.status) : false;
  usePolling(detail.refresh, POLL_INTERVAL_MS, isRunning);

  const errorsKey = `import:${importId}:errors:${errorPage}:${detail.data?.failed_rows ?? 0}:${detail.data?.skipped_rows ?? 0}`;
  const errors = useFetch(() => importApi.errors(importId, errorPage), errorsKey);

  return {
    item: detail.data,
    isLoading: detail.isLoading,
    error: detail.error,
    errors: errors.data?.data ?? [],
    errorsMeta: errors.data?.meta ?? null,
    isLoadingErrors: errors.isLoading,
    setErrorPage,
  };
};
