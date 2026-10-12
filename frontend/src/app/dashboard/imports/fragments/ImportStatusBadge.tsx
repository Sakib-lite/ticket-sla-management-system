import { Badge } from "@/components";
import { IMPORT_STATUS_LABELS, IMPORT_STATUS_TONES } from "@/constants/imports";
import type { ImportStatus } from "@/types";

export default function ImportStatusBadge({ status }: { status: ImportStatus }) {
  return <Badge tone={IMPORT_STATUS_TONES[status]}>{IMPORT_STATUS_LABELS[status]}</Badge>;
}
