"use client";

import type { SelectHTMLAttributes } from "react";
import { classNames } from "@/utils";

export type SelectOption = {
  label: string;
  value: string;
};

type SelectFieldProps = SelectHTMLAttributes<HTMLSelectElement> & {
  label: string;
  options: SelectOption[];
  placeholder?: string;
  error?: string;
};

export default function SelectField({
  label,
  options,
  placeholder,
  error,
  className,
  ...rest
}: SelectFieldProps) {
  return (
    <label className="block space-y-1.5">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <select
        {...rest}
        className={classNames(
          "w-full rounded-md border bg-white px-3 py-2 text-sm transition outline-none focus:ring-2",
          error
            ? "border-red-400 focus:ring-red-200"
            : "border-slate-300 focus:border-slate-400 focus:ring-slate-200",
          className
        )}
      >
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error && <span className="text-xs text-red-600">{error}</span>}
    </label>
  );
}
