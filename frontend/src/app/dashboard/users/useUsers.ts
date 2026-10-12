"use client";

import { useState } from "react";
import { useFetch } from "@/hooks/useFetch";
import { notify } from "@/hooks/useNotify";
import {
  userApi,
  type CreateUserPayload,
  type UpdateUserPayload,
  type UserListParams,
} from "@/services";
import type { RequestFailure, User } from "@/types";

export const useUsers = () => {
  const [params, setParams] = useState<UserListParams>({});
  const { data, isLoading, setData, refresh } = useFetch(
    () => userApi.list(params),
    JSON.stringify(params)
  );
  const [editing, setEditing] = useState<User | "new" | null>(null);

  const setFilter = (changes: UserListParams) =>
    setParams((current) => ({ ...current, ...changes, page: changes.page ?? 1 }));

  const replace = (user: User) =>
    data &&
    setData({ ...data, data: data.data.map((item) => (item.id === user.id ? user : item)) });

  const create = async (payload: CreateUserPayload) => {
    const user = await userApi.create(payload);
    notify.success(`${user.name} added.`);
    refresh();
  };

  const update = async (user: User, payload: UpdateUserPayload) => {
    try {
      replace(await userApi.update(user.id, payload));
      notify.success(`${user.name} updated.`);
    } catch (failure) {
      notify.error((failure as RequestFailure).message);
      throw failure;
    }
  };

  return {
    users: data?.data ?? [],
    meta: data?.meta ?? null,
    params,
    isLoading,
    editing,
    setFilter,
    openCreate: () => setEditing("new"),
    openEdit: (user: User) => setEditing(user),
    closeEditor: () => setEditing(null),
    create,
    update,
  };
};
