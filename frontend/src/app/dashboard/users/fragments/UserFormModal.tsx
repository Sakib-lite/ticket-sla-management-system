"use client";

import { useState, type FormEvent } from "react";
import { Button, Form, Modal } from "@/components";
import { ROLE_OPTIONS } from "@/constants/tickets";
import type { CreateUserPayload, UpdateUserPayload } from "@/services";
import type { FieldErrors, RequestFailure, Role, User } from "@/types";

type UserFormModalProps = {
  user: User | "new";
  currentUserId: number | undefined;
  onClose: () => void;
  onCreate: (payload: CreateUserPayload) => Promise<void>;
  onUpdate: (user: User, payload: UpdateUserPayload) => Promise<void>;
};

export default function UserFormModal({
  user,
  currentUserId,
  onClose,
  onCreate,
  onUpdate,
}: UserFormModalProps) {
  const isNew = user === "new";
  const [name, setName] = useState(isNew ? "" : user.name);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [role, setRole] = useState<Role>(isNew ? "agent" : user.role);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [isSaving, setIsSaving] = useState(false);

  const isSelf = !isNew && user.id === currentUserId;

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setIsSaving(true);
    setErrors({});
    try {
      if (isNew) {
        await onCreate({ name, email, password, role });
      } else {
        await onUpdate(user, isSelf ? { name } : { name, role });
      }
      onClose();
    } catch (failure) {
      setErrors((failure as RequestFailure).errors);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <Modal isOpen title={isNew ? "Add user" : `Edit ${user.name}`} size="sm" onClose={onClose}>
      <form onSubmit={submit} className="space-y-4">
        <Form.TextField
          label="Name"
          value={name}
          error={errors.name?.[0]}
          onChange={(event) => setName(event.target.value)}
        />
        {isNew && (
          <>
            <Form.TextField
              label="Email"
              type="email"
              value={email}
              error={errors.email?.[0]}
              onChange={(event) => setEmail(event.target.value)}
            />
            <Form.TextField
              label="Temporary password"
              type="password"
              value={password}
              autoComplete="new-password"
              error={errors.password?.[0]}
              onChange={(event) => setPassword(event.target.value)}
            />
          </>
        )}
        <Form.SelectField
          label="Role"
          value={role}
          options={ROLE_OPTIONS}
          disabled={isSelf}
          error={errors.role?.[0]}
          onChange={(event) => setRole(event.target.value as Role)}
        />
        {isSelf && <p className="text-xs text-slate-500">You cannot change your own role.</p>}
        <div className="flex justify-end gap-2">
          <Button.Primary type="button" tone="ghost" onClick={onClose} disabled={isSaving}>
            Cancel
          </Button.Primary>
          <Button.Primary type="submit" isLoading={isSaving}>
            {isNew ? "Add user" : "Save changes"}
          </Button.Primary>
        </div>
      </form>
    </Modal>
  );
}
