import api from "@/api";
import type { Import, ImportError, Paginated } from "@/types";

export const list = async (page = 1) => {
  const response = await api.get<Paginated<Import>>("/imports", { params: { page } });
  return response.data;
};

export const show = async (id: number) => {
  const response = await api.get<Import>(`/imports/${id}`);
  return response.data;
};

export const errors = async (id: number, page = 1) => {
  const response = await api.get<Paginated<ImportError>>(`/imports/${id}/errors`, {
    params: { page },
  });
  return response.data;
};

export const upload = async (file: File) => {
  const form = new FormData();
  form.append("file", file);
  const response = await api.post<Import>("/imports", form);
  return response.data;
};

export const downloadTemplate = async () => {
  const response = await api.get<Blob>("/imports/template", { responseType: "blob" });
  const url = URL.createObjectURL(response.data);
  const link = document.createElement("a");
  link.href = url;
  link.download = "ticket-import-template.csv";
  link.click();
  URL.revokeObjectURL(url);
};
