import type { BadgeTone } from "@/components/data";
import type { ImportStatus } from "@/types";

export const IMPORT_STATUS_LABELS: Record<ImportStatus, string> = {
  pending: "Pending",
  processing: "Processing",
  completed: "Completed",
  failed: "Failed",
};

export const IMPORT_STATUS_TONES: Record<ImportStatus, BadgeTone> = {
  pending: "slate",
  processing: "blue",
  completed: "green",
  failed: "red",
};

export const isImportRunning = (status: ImportStatus) =>
  status === "pending" || status === "processing";
