import api from "@/api";
import type { Category } from "@/types";

export const list = async (includeInactive = false) => {
  const response = await api.get<Category[]>("/categories", {
    params: includeInactive ? { include_inactive: 1 } : undefined,
  });
  return response.data;
};

export const create = async (name: string) => {
  const response = await api.post<Category>("/categories", { name });
  return response.data;
};

export const update = async (id: number, payload: { name?: string; is_active?: boolean }) => {
  const response = await api.patch<Category>(`/categories/${id}`, payload);
  return response.data;
};
