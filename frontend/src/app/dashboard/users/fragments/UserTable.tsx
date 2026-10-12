"use client";

import { PencilSimple } from "@phosphor-icons/react";
import { Badge, DataTable, EmptyState, Form, type BadgeTone, type Column } from "@/components";
import { ROLE_LABELS } from "@/constants/tickets";
import type { Role, User } from "@/types";
import { formatDate } from "@/utils";

const roleTones: Record<Role, BadgeTone> = {
  administrator: "violet",
  agent: "blue",
  customer: "slate",
};

type UserTableProps = {
  users: User[];
  isLoading: boolean;
  onEdit: (user: User) => void;
  onToggleAvailability: (user: User, isAvailable: boolean) => Promise<void>;
};

export default function UserTable({
  users,
  isLoading,
  onEdit,
  onToggleAvailability,
}: UserTableProps) {
  const columns: Column<User>[] = [
    {
      key: "user",
      header: "User",
      render: (user) => (
        <div>
          <p className="font-medium text-slate-900">{user.name}</p>
          <p className="text-xs text-slate-500">{user.email}</p>
        </div>
      ),
    },
    {
      key: "role",
      header: "Role",
      render: (user) => <Badge tone={roleTones[user.role]}>{ROLE_LABELS[user.role]}</Badge>,
    },
    {
      key: "availability",
      header: "Takes new tickets",
      render: (user) =>
        user.role === "agent" ? (
          <Form.Switch
            checked={user.is_available ?? false}
            label={`${user.name} availability`}
            onChange={(checked) => onToggleAvailability(user, checked)}
          />
        ) : (
          <span className="text-slate-400">—</span>
        ),
    },
    {
      key: "since",
      header: "Since",
      render: (user) => (
        <span className="text-slate-500">{formatDate(user.created_at ?? null)}</span>
      ),
    },
    {
      key: "actions",
      header: "",
      className: "text-right",
      render: (user) => (
        <button
          type="button"
          onClick={() => onEdit(user)}
          aria-label={`Edit ${user.name}`}
          className="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900"
        >
          <PencilSimple size={16} />
        </button>
      ),
    },
  ];

  return (
    <DataTable
      columns={columns}
      rows={users}
      rowKey={(user) => user.id}
      isLoading={isLoading}
      emptyState={
        <EmptyState title="No users match" description="Try a different search or role." />
      }
    />
  );
}
