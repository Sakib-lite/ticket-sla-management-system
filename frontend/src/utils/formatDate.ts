import { format, formatDistanceToNowStrict, isValid, parseISO } from "date-fns";

const toDate = (value: string | Date) => (typeof value === "string" ? parseISO(value) : value);

export const formatDateTime = (value: string | Date | null) => {
  if (!value) {
    return "";
  }
  const date = toDate(value);
  return isValid(date) ? format(date, "d MMM yyyy, h:mm a") : "";
};

export const formatDate = (value: string | Date | null) => {
  if (!value) {
    return "";
  }
  const date = toDate(value);
  return isValid(date) ? format(date, "d MMM yyyy") : "";
};

export const formatTimeAgo = (value: string | Date | null) => {
  if (!value) {
    return "";
  }
  const date = toDate(value);
  return isValid(date) ? `${formatDistanceToNowStrict(date)} ago` : "";
};
