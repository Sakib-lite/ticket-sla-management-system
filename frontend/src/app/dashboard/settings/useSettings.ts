"use client";

import { useFetch } from "@/hooks/useFetch";
import { notify } from "@/hooks/useNotify";
import { categoryApi, settingsApi, type SlaPolicyPayload } from "@/services";
import type { Category, RequestFailure, SlaPolicy } from "@/types";

export const useSettings = () => {
  const policies = useFetch(() => settingsApi.slaPolicies(), "settings:sla");
  const categories = useFetch(() => categoryApi.list(true), "settings:categories");
  const hours = useFetch(() => settingsApi.businessHours(), "settings:hours");

  const report = (failure: unknown) => {
    notify.error((failure as RequestFailure).message);
    throw failure;
  };

  const savePolicy = async (policy: SlaPolicy, payload: SlaPolicyPayload) => {
    try {
      const updated = await settingsApi.updateSlaPolicy(policy.id, payload);
      policies.setData(
        (policies.data ?? []).map((item) => (item.id === updated.id ? updated : item))
      );
      notify.success(`${updated.label} SLA saved.`);
    } catch (failure) {
      report(failure);
    }
  };

  const addCategory = async (name: string) => {
    try {
      const created = await categoryApi.create(name);
      categories.setData(
        [...(categories.data ?? []), created].sort((a, b) => a.name.localeCompare(b.name))
      );
      notify.success(`${created.name} added.`);
    } catch (failure) {
      report(failure);
    }
  };

  const updateCategory = async (
    category: Category,
    payload: { name?: string; is_active?: boolean }
  ) => {
    try {
      const updated = await categoryApi.update(category.id, payload);
      categories.setData(
        (categories.data ?? []).map((item) => (item.id === updated.id ? updated : item))
      );
      notify.success(`${updated.name} updated.`);
    } catch (failure) {
      report(failure);
    }
  };

  return {
    policies: policies.data ?? [],
    categories: categories.data ?? [],
    businessHours: hours.data,
    isLoading: policies.isLoading || categories.isLoading || hours.isLoading,
    savePolicy,
    addCategory,
    updateCategory,
  };
};
