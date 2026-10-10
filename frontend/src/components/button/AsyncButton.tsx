"use client";

import { useState, type ButtonHTMLAttributes, type ReactNode } from "react";
import PrimaryButton from "./PrimaryButton";

type AsyncButtonProps = Omit<ButtonHTMLAttributes<HTMLButtonElement>, "onClick"> & {
  tone?: "primary" | "danger" | "ghost";
  onClick: () => Promise<unknown>;
  children: ReactNode;
};

export default function AsyncButton({ onClick, children, ...rest }: AsyncButtonProps) {
  const [isLoading, setIsLoading] = useState(false);

  const run = async () => {
    setIsLoading(true);
    try {
      await onClick();
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <PrimaryButton {...rest} isLoading={isLoading} onClick={run}>
      {children}
    </PrimaryButton>
  );
}
