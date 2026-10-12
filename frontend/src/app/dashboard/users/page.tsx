"use client";

import { Plus } from "@phosphor-icons/react";
import { Button, Form, PageHeader, Pagination } from "@/components";
import { ROLE_OPTIONS } from "@/constants/tickets";
import { useAuthStore } from "@/stores/useAuthStore";
import type { Role } from "@/types";
import UserFormModal from "./fragments/UserFormModal";
import UserTable from "./fragments/UserTable";
import { useUsers } from "./useUsers";

export default function UsersPage() {
  const currentUserId = useAuthStore((state) => state.user?.id);
  const users = useUsers();

  return (
    <div className="space-y-6">
      <PageHeader
        title="Users"
        description={users.meta ? `${users.meta.total} accounts` : undefined}
        actions={
          <Button.Primary onClick={users.openCreate}>
            <Plus size={16} /> Add user
          </Button.Primary>
        }
      />
      <div className="grid gap-3 sm:grid-cols-2 lg:w-2/3">
        <Form.TextField
          label="Search"
          type="search"
          placeholder="Name or email"
          value={users.params.search ?? ""}
          onChange={(event) => users.setFilter({ search: event.target.value || undefined })}
        />
        <Form.SelectField
          label="Role"
          value={users.params.role ?? ""}
          placeholder="Any role"
          options={ROLE_OPTIONS}
          onChange={(event) => users.setFilter({ role: (event.target.value || undefined) as Role })}
        />
      </div>
      <UserTable
        users={users.users}
        isLoading={users.isLoading}
        onEdit={users.openEdit}
        onToggleAvailability={(user, isAvailable) =>
          users.update(user, { is_available: isAvailable })
        }
      />
      {users.meta && (
        <Pagination meta={users.meta} onPageChange={(page) => users.setFilter({ page })} />
      )}
      {users.editing && (
        <UserFormModal
          user={users.editing}
          currentUserId={currentUserId}
          onClose={users.closeEditor}
          onCreate={users.create}
          onUpdate={users.update}
        />
      )}
    </div>
  );
}
