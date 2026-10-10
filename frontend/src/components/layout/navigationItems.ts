import { ChartBar, Gear, Ticket, Users } from "@phosphor-icons/react";
import type { Role } from "@/types";

export type NavigationItem = {
  label: string;
  href: string;
  icon: typeof Ticket;
  allowedRoles: Role[];
};

export const navigationItems: NavigationItem[] = [
  {
    label: "Dashboard",
    href: "/dashboard",
    icon: ChartBar,
    allowedRoles: ["administrator", "agent", "customer"],
  },
  {
    label: "Tickets",
    href: "/tickets",
    icon: Ticket,
    allowedRoles: ["administrator", "agent", "customer"],
  },
  {
    label: "Users",
    href: "/users",
    icon: Users,
    allowedRoles: ["administrator"],
  },
  {
    label: "Settings",
    href: "/settings",
    icon: Gear,
    allowedRoles: ["administrator"],
  },
];

export const navigationFor = (role: Role | undefined) =>
  navigationItems.filter((item) => (role ? item.allowedRoles.includes(role) : false));
