import api from "@/api";
import type { BusinessHours, SlaPolicy } from "@/types";

export type SlaPolicyPayload = {
  resolution_hours: number;
  approaching_threshold_percent: number;
};

export const slaPolicies = async () => {
  const response = await api.get<SlaPolicy[]>("/settings/sla-policies");
  return response.data;
};

export const updateSlaPolicy = async (id: number, payload: SlaPolicyPayload) => {
  const response = await api.patch<SlaPolicy>(`/settings/sla-policies/${id}`, payload);
  return response.data;
};

export const businessHours = async () => {
  const response = await api.get<BusinessHours>("/settings/business-hours");
  return response.data;
};
