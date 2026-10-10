"use client";

import type { FormEvent } from "react";
import { Button, Form } from "@/components";
import type { FieldErrors } from "@/types";

type LoginFormProps = {
  email: string;
  password: string;
  errors: FieldErrors;
  isSubmitting: boolean;
  onEmailChange: (value: string) => void;
  onPasswordChange: (value: string) => void;
  onSubmit: (event: FormEvent) => void;
};

export default function LoginForm({
  email,
  password,
  errors,
  isSubmitting,
  onEmailChange,
  onPasswordChange,
  onSubmit,
}: LoginFormProps) {
  return (
    <form onSubmit={onSubmit} className="space-y-4">
      <Form.TextField
        label="Email"
        type="email"
        value={email}
        autoComplete="email"
        error={errors.email?.[0]}
        onChange={(event) => onEmailChange(event.target.value)}
      />
      <Form.TextField
        label="Password"
        type="password"
        value={password}
        autoComplete="current-password"
        error={errors.password?.[0]}
        onChange={(event) => onPasswordChange(event.target.value)}
      />
      <Button.Primary type="submit" isLoading={isSubmitting} className="w-full">
        Sign in
      </Button.Primary>
    </form>
  );
}
