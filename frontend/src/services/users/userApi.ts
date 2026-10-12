import api from "@/api";
import type { Paginated, Role, User } from "@/types";

export type UserListParams = {
  role?: Role;
  search?: string;
  page?: number;
};

export type CreateUserPayload = {
  name: string;
  email: string;
  password: string;
  role: Role;
};

export type UpdateUserPayload = {
  name?: string;
  role?: Role;
  is_available?: boolean;
};

export const list = async (params: UserListParams) => {
  const response = await api.get<Paginated<User>>("/users", { params });
  return response.data;
};

export const create = async (payload: CreateUserPayload) => {
  const response = await api.post<User>("/users", payload);
  return response.data;
};

export const update = async (id: number, payload: UpdateUserPayload) => {
  const response = await api.patch<User>(`/users/${id}`, payload);
  return response.data;
};
