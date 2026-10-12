"use client";

import { useState } from "react";
import { isImportRunning } from "@/constants/imports";
import { useFetch } from "@/hooks/useFetch";
import { notify } from "@/hooks/useNotify";
import { usePolling } from "@/hooks/usePolling";
import { importApi } from "@/services";
import type { RequestFailure } from "@/types";

const POLL_INTERVAL_MS = 3000;

export const useImports = () => {
  const [page, setPage] = useState(1);
  const { data, isLoading, refresh, setData } = useFetch(
    () => importApi.list(page),
    `imports:${page}`
  );
  const [isUploading, setIsUploading] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);

  const hasRunning = (data?.data ?? []).some((item) => isImportRunning(item.status));
  usePolling(refresh, POLL_INTERVAL_MS, hasRunning);

  const upload = async (file: File) => {
    setIsUploading(true);
    setUploadError(null);
    try {
      const created = await importApi.upload(file);
      if (data) {
        setData({ ...data, data: [created, ...data.data] });
      } else {
        refresh();
      }
      notify.success(`${file.name} queued for import.`);
    } catch (failure) {
      const { message, errors } = failure as RequestFailure;
      setUploadError(errors.file?.[0] ?? message);
    } finally {
      setIsUploading(false);
    }
  };

  return {
    imports: data?.data ?? [],
    meta: data?.meta ?? null,
    isLoading,
    isUploading,
    uploadError,
    upload,
    setPage,
  };
};
