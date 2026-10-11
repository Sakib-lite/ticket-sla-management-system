import api from "@/api";
import type { Attachment } from "@/types";

export const ACCEPTED_FILE_TYPES = ".pdf,.png,.jpg,.jpeg,.gif,.webp,.txt,.csv,.docx,.xlsx";
export const MAX_FILES = 5;
export const MAX_FILE_BYTES = 5 * 1024 * 1024;

export const appendFiles = (form: FormData, files: File[]) => {
  files.forEach((file) => form.append("files[]", file));
  return form;
};

export const upload = async (ticketId: number, files: File[]) => {
  const response = await api.post<Attachment[]>(
    `/tickets/${ticketId}/attachments`,
    appendFiles(new FormData(), files)
  );
  return response.data;
};

export const download = async (attachment: Attachment) => {
  const response = await api.get<Blob>(`/attachments/${attachment.id}/download`, {
    responseType: "blob",
  });
  const url = URL.createObjectURL(response.data);
  const link = document.createElement("a");
  link.href = url;
  link.download = attachment.original_name;
  link.click();
  URL.revokeObjectURL(url);
};

export const remove = async (attachmentId: number) => {
  await api.delete(`/attachments/${attachmentId}`);
};
