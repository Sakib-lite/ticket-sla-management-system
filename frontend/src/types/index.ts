export type Role = "administrator" | "agent" | "customer";

export type User = {
  id: number;
  name: string;
  email: string;
  role: Role;
};

export type Paginated<T> = {
  data: T[];
  meta: {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
  };
};

export type FieldErrors = Record<string, string[]>;

export type RequestFailure = {
  status: number;
  message: string;
  errors: FieldErrors;
};
