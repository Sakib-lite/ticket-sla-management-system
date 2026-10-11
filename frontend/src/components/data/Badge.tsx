import type { ReactNode } from "react";
import { classNames } from "@/utils";

export type BadgeTone = "slate" | "blue" | "violet" | "amber" | "red" | "green";

type BadgeProps = {
  tone?: BadgeTone;
  className?: string;
  children: ReactNode;
};

const toneStyles: Record<BadgeTone, string> = {
  slate: "bg-slate-100 text-slate-700",
  blue: "bg-sky-100 text-sky-800",
  violet: "bg-violet-100 text-violet-800",
  amber: "bg-amber-100 text-amber-800",
  red: "bg-red-100 text-red-800",
  green: "bg-emerald-100 text-emerald-800",
};

export default function Badge({ tone = "slate", className, children }: BadgeProps) {
  return (
    <span
      className={classNames(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap",
        toneStyles[tone],
        className
      )}
    >
      {children}
    </span>
  );
}
