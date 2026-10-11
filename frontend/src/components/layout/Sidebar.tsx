"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useAuthStore } from "@/stores/useAuthStore";
import { classNames } from "@/utils";
import { isNavigationItemActive, navigationFor } from "./navigationItems";

type SidebarProps = {
  isOpen: boolean;
  onNavigate: () => void;
};

export default function Sidebar({ isOpen, onNavigate }: SidebarProps) {
  const pathname = usePathname();
  const user = useAuthStore((state) => state.user);
  const items = navigationFor(user?.role);

  return (
    <aside
      className={classNames(
        "fixed inset-y-0 left-0 z-30 w-64 border-r border-slate-200 bg-white transition-transform lg:static lg:translate-x-0",
        isOpen ? "translate-x-0" : "-translate-x-full"
      )}
    >
      <div className="flex h-16 items-center border-b border-slate-200 px-5">
        <span className="text-lg font-semibold text-slate-900">Support Desk</span>
      </div>
      <nav className="space-y-1 p-3">
        {items.map((item) => {
          const isActive = isNavigationItemActive(pathname, item.href);
          return (
            <Link
              key={item.href}
              href={item.href}
              onClick={onNavigate}
              className={classNames(
                "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition",
                isActive ? "bg-slate-900 text-white" : "text-slate-600 hover:bg-slate-100"
              )}
            >
              <item.icon size={18} />
              {item.label}
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}
