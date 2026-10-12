"use client";

import { useState, type FormEvent } from "react";
import { Plus } from "@phosphor-icons/react";
import { Button, Form } from "@/components";
import type { Category } from "@/types";

type CategoryManagerProps = {
  categories: Category[];
  onAdd: (name: string) => Promise<void>;
  onUpdate: (category: Category, payload: { name?: string; is_active?: boolean }) => Promise<void>;
};

function CategoryRow({
  category,
  onUpdate,
}: {
  category: Category;
  onUpdate: CategoryManagerProps["onUpdate"];
}) {
  const [name, setName] = useState(category.name);

  return (
    <li className="flex items-center gap-3 py-2">
      <input
        value={name}
        aria-label={`${category.name} name`}
        onChange={(event) => setName(event.target.value)}
        onBlur={() =>
          name.trim() && name !== category.name && onUpdate(category, { name: name.trim() })
        }
        className="flex-1 rounded-md border border-transparent px-2 py-1.5 text-sm hover:border-slate-300 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:outline-none"
      />
      <Form.Switch
        checked={category.is_active}
        label={`${category.name} active`}
        onChange={(checked) => onUpdate(category, { is_active: checked })}
      />
    </li>
  );
}

export default function CategoryManager({ categories, onAdd, onUpdate }: CategoryManagerProps) {
  const [name, setName] = useState("");
  const [isAdding, setIsAdding] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    if (!name.trim()) {
      return;
    }
    setIsAdding(true);
    try {
      await onAdd(name.trim());
      setName("");
    } catch {
    } finally {
      setIsAdding(false);
    }
  };

  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5">
      <h2 className="text-sm font-semibold text-slate-900">Categories</h2>
      <p className="mb-3 text-sm text-slate-500">
        Inactive categories stay on existing tickets but cannot be chosen for new ones.
      </p>
      <ul className="divide-y divide-slate-100">
        {categories.map((category) => (
          <CategoryRow key={category.id} category={category} onUpdate={onUpdate} />
        ))}
      </ul>
      <form onSubmit={submit} className="mt-3 flex gap-2">
        <input
          value={name}
          placeholder="New category"
          aria-label="New category name"
          onChange={(event) => setName(event.target.value)}
          className="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
        />
        <Button.Primary type="submit" tone="ghost" isLoading={isAdding} disabled={!name.trim()}>
          <Plus size={16} /> Add
        </Button.Primary>
      </form>
    </section>
  );
}
