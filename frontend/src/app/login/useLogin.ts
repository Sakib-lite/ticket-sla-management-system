"use client";

import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import { DASHBOARD_PATH } from "@/constants/auth";
import { notify } from "@/hooks/useNotify";
import { authApi } from "@/services";
import { useAuthStore } from "@/stores/useAuthStore";
import type { FieldErrors, RequestFailure } from "@/types";

export const useLogin = () => {
  const router = useRouter();
  const signIn = useAuthStore((state) => state.signIn);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [errors, setErrors] = useState<FieldErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setIsSubmitting(true);
    setErrors({});

    try {
      const { user, token } = await authApi.login({ email, password });
      signIn(user, token);
      notify.success(`Welcome back, ${user.name}.`);
      router.replace(DASHBOARD_PATH);
    } catch (failure) {
      const { message, errors: fieldErrors } = failure as RequestFailure;
      setErrors(fieldErrors);
      if (!Object.keys(fieldErrors).length) {
        notify.error(message);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return {
    email,
    setEmail,
    password,
    setPassword,
    errors,
    isSubmitting,
    submit,
  };
};
