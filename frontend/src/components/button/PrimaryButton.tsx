"use client";

import type { ButtonHTMLAttributes, ReactNode } from "react";
import Loader from "@/components/feedback/Loader";
import { classNames } from "@/utils";

type Tone = "primary" | "danger" | "ghost";

type PrimaryButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  tone?: Tone;
  isLoading?: boolean;
  children: ReactNode;
};

const toneStyles: Record<Tone, string> = {
  primary: "bg-slate-900 text-white hover:bg-slate-700",
  danger: "bg-red-600 text-white hover:bg-red-500",
  ghost: "border border-slate-300 text-slate-700 hover:bg-slate-100",
};

export default function PrimaryButton({
  tone = "primary",
  isLoading = false,
  disabled,
  className,
  children,
  ...rest
}: PrimaryButtonProps) {
  return (
    <button
      {...rest}
      disabled={disabled || isLoading}
      className={classNames(
        "inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50",
        toneStyles[tone],
        className
      )}
    >
      {isLoading && <Loader size={14} />}
      {children}
    </button>
  );
}
