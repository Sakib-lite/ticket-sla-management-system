"use client";

import { classNames } from "@/utils";

type SwitchProps = {
  checked: boolean;
  label: string;
  disabled?: boolean;
  onChange: (checked: boolean) => void;
};

export default function Switch({ checked, label, disabled = false, onChange }: SwitchProps) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      aria-label={label}
      disabled={disabled}
      onClick={() => onChange(!checked)}
      className={classNames(
        "relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition disabled:cursor-not-allowed disabled:opacity-50",
        checked ? "bg-emerald-500" : "bg-slate-300"
      )}
    >
      <span
        className={classNames(
          "inline-block h-5 w-5 rounded-full bg-white shadow transition",
          checked ? "translate-x-5.5" : "translate-x-0.5"
        )}
      />
    </button>
  );
}
