"use client";

import { ChartBar, Gear, Ticket, UploadSimple, Users } from "@phosphor-icons/react";
import {
  DASHBOARD_PATH,
  IMPORTS_PATH,
  SETTINGS_PATH,
  TICKETS_PATH,
  USERS_PATH,
} from "@/constants/paths";
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
    href: DASHBOARD_PATH,
    icon: ChartBar,
    allowedRoles: ["administrator", "agent", "customer"],
  },
  {
    label: "Tickets",
    href: TICKETS_PATH,
    icon: Ticket,
    allowedRoles: ["administrator", "agent", "customer"],
  },
  {
    label: "Imports",
    href: IMPORTS_PATH,
    icon: UploadSimple,
    allowedRoles: ["administrator", "agent"],
  },
  {
    label: "Users",
    href: USERS_PATH,
    icon: Users,
    allowedRoles: ["administrator"],
  },
  {
    label: "Settings",
    href: SETTINGS_PATH,
    icon: Gear,
    allowedRoles: ["administrator"],
  },
];

export const navigationFor = (role: Role | undefined) =>
  navigationItems.filter((item) => (role ? item.allowedRoles.includes(role) : false));

const matchesPathname = (pathname: string, href: string) =>
  pathname === href || pathname.startsWith(`${href}/`);

export const activeNavigationHref = (pathname: string, items: NavigationItem[]) =>
  items
    .filter((item) => matchesPathname(pathname, item.href))
    .reduce((longest, item) => (item.href.length > longest.length ? item.href : longest), "");
