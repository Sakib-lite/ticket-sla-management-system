"use client";

import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import { MagnifyingGlass } from "@phosphor-icons/react";
import { TICKETS_PATH } from "@/constants/paths";

export default function GlobalSearch() {
  const router = useRouter();
  const [term, setTerm] = useState("");

  const submit = (event: FormEvent) => {
    event.preventDefault();
    const query = term.trim();
    if (!query) {
      return;
    }
    router.push(`${TICKETS_PATH}?view=all&search=${encodeURIComponent(query)}`);
    setTerm("");
  };

  return (
    <form onSubmit={submit} role="search" className="relative hidden sm:block">
      <MagnifyingGlass size={16} className="absolute top-2.5 left-3 text-slate-400" />
      <input
        type="search"
        value={term}
        onChange={(event) => setTerm(event.target.value)}
        placeholder="Search tickets…"
        aria-label="Search tickets"
        className="w-64 rounded-md border border-slate-300 bg-slate-50 py-2 pr-3 pl-9 text-sm outline-none focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200"
      />
    </form>
  );
}
