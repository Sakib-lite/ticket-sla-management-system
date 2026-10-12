export const DASHBOARD_PATH = "/dashboard";
export const TICKETS_PATH = "/dashboard/tickets";
export const NEW_TICKET_PATH = "/dashboard/tickets/new";
export const IMPORTS_PATH = "/dashboard/imports";
export const USERS_PATH = "/dashboard/users";
export const SETTINGS_PATH = "/dashboard/settings";

export const ticketPath = (idOrNumber: number | string) => `${TICKETS_PATH}/${idOrNumber}`;
export const importPath = (id: number) => `${IMPORTS_PATH}/${id}`;
