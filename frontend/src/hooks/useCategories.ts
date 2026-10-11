"use client";

import { categoryApi } from "@/services";
import { useFetch } from "./useFetch";

export const useCategories = () => {
  const { data, isLoading } = useFetch(() => categoryApi.list(), "categories");

  const options = (data ?? []).map((category) => ({
    label: category.name,
    value: String(category.id),
  }));

  return { categories: data ?? [], options, isLoading };
};
