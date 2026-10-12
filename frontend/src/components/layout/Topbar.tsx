"use client";

import { List } from "@phosphor-icons/react";
import GlobalSearch from "./GlobalSearch";
import NotificationMenu from "./NotificationMenu";
import UserMenu from "./UserMenu";

type TopbarProps = {
  onToggleSidebar: () => void;
};

export default function Topbar({ onToggleSidebar }: TopbarProps) {
  return (
    <header className="flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
      <button
        type="button"
        onClick={onToggleSidebar}
        aria-label="Toggle navigation"
        className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
      >
        <List size={20} />
      </button>
      <div className="ml-auto flex items-center gap-3">
        <GlobalSearch />
        <div className="flex items-center gap-1">
          <NotificationMenu />
          <UserMenu />
        </div>
      </div>
    </header>
  );
}
