import axios, { AxiosError } from "axios";
import { LOGIN_PATH, TOKEN_KEY } from "@/constants/auth";
import type { RequestFailure } from "@/types";

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_BASE_URL,
  headers: {
    Accept: "application/json",
  },
});

export const readToken = () => {
  if (typeof window === "undefined") {
    return null;
  }
  return window.localStorage.getItem(TOKEN_KEY);
};

export const storeToken = (token: string) => {
  window.localStorage.setItem(TOKEN_KEY, token);
};

export const clearToken = () => {
  window.localStorage.removeItem(TOKEN_KEY);
};

api.interceptors.request.use((config) => {
  const token = readToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    const status = error.response?.status ?? 0;

    if (status === 401 && typeof window !== "undefined") {
      clearToken();
      if (window.location.pathname !== LOGIN_PATH) {
        window.location.assign(LOGIN_PATH);
      }
    }

    const failure: RequestFailure = {
      status,
      message: error.response?.data?.message || "Something went wrong. Please try again.",
      errors: error.response?.data?.errors || {},
    };

    return Promise.reject(failure);
  }
);

export default api;
