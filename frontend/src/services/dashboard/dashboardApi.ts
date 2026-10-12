import api from "@/api";
import type { DashboardData } from "@/types";

export const get = async () => {
  const response = await api.get<DashboardData>("/dashboard");
  return response.data;
};
