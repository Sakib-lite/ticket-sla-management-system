"use client";

import Link from "next/link";
import type { ReactNode } from "react";
import { classNames } from "@/utils";

type StatTileProps = {
  label: string;
  value: number;
  href: string;
  icon: ReactNode;
  tone?: "neutral" | "warning" | "danger" | "success";
};

const toneStyles = {
  neutral: "text-slate-900",
  warning: "text-amber-700",
  danger: "text-red-700",
  success: "text-emerald-700",
};

export default function StatTile({ label, value, href, icon, tone = "neutral" }: StatTileProps) {
  return (
    <Link
      href={href}
      className="flex items-center gap-4 rounded-lg border border-slate-200 bg-white p-5 transition hover:border-slate-300 hover:shadow-sm"
    >
      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-600">
        {icon}
      </span>
      <span className="min-w-0">
        <span className={classNames("block text-2xl font-semibold tabular-nums", toneStyles[tone])}>
          {value}
        </span>
        <span className="block truncate text-sm text-slate-500">{label}</span>
      </span>
    </Link>
  );
}
