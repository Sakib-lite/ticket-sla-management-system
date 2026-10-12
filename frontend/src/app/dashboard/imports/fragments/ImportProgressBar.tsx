import { classNames } from "@/utils";
import type { Import } from "@/types";

export default function ImportProgressBar({ item }: { item: Import }) {
  const percent = item.progress ?? (item.status === "pending" ? 0 : 100);

  return (
    <div className="space-y-1">
      <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
        <div
          className={classNames(
            "h-full rounded-full transition-all",
            item.status === "failed" ? "bg-red-500" : "bg-emerald-500"
          )}
          style={{ width: `${percent}%` }}
        />
      </div>
      <p className="text-xs text-slate-500">
        {item.total_rows !== null
          ? `${item.processed_rows} / ${item.total_rows} rows`
          : "Waiting for a worker…"}
      </p>
    </div>
  );
}
