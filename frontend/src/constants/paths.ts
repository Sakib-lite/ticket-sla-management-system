export const DASHBOARD_PATH = "/dashboard";
export const TICKETS_PATH = "/dashboard/tickets";
export const NEW_TICKET_PATH = "/dashboard/tickets/new";
export const USERS_PATH = "/dashboard/users";
export const SETTINGS_PATH = "/dashboard/settings";

export const ticketPath = (idOrNumber: number | string) => `${TICKETS_PATH}/${idOrNumber}`;
