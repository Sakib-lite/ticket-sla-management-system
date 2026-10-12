import api from "@/api";
import type { User } from "@/types";

type Credentials = {
  email: string;
  password: string;
};

type LoginResponse = {
  token: string;
  user: User;
};

export const login = async (credentials: Credentials) => {
  const response = await api.post<LoginResponse>("/login", credentials);
  return response.data;
};

export const logout = async () => {
  await api.post("/logout");
};

export const fetchCurrentUser = async () => {
  const response = await api.get<User>("/me");
  return response.data;
};

export const updateAvailability = async (isAvailable: boolean) => {
  const response = await api.patch<User>("/me/availability", { is_available: isAvailable });
  return response.data;
};
