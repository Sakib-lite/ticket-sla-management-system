"use client";

import { List } from "@phosphor-icons/react";
import UserMenu from "./UserMenu";

type TopbarProps = {
  onToggleSidebar: () => void;
};

export default function Topbar({ onToggleSidebar }: TopbarProps) {
  return (
    <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
      <button
        type="button"
        onClick={onToggleSidebar}
        aria-label="Toggle navigation"
        className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
      >
        <List size={20} />
      </button>
      <div className="ml-auto">
        <UserMenu />
      </div>
    </header>
  );
}
