"use client";

import type { InputHTMLAttributes } from "react";
import { classNames } from "@/utils";

type TextFieldProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  error?: string;
};

export default function TextField({ label, error, className, ...rest }: TextFieldProps) {
  return (
    <label className="block space-y-1.5">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <input
        {...rest}
        className={classNames(
          "w-full rounded-md border px-3 py-2 text-sm transition outline-none focus:ring-2",
          error
            ? "border-red-400 focus:ring-red-200"
            : "border-slate-300 focus:border-slate-400 focus:ring-slate-200",
          className
        )}
      />
      {error && <span className="text-xs text-red-600">{error}</span>}
    </label>
  );
}
