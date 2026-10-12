"use client";

import Link from "next/link";
import { ArrowLeft } from "@phosphor-icons/react";
import { Button, EmptyState, Loader, PageHeader, Pagination } from "@/components";
import { IMPORTS_PATH } from "@/constants/paths";
import { formatDateTime } from "@/utils";
import ImportProgressBar from "../fragments/ImportProgressBar";
import ImportStatusBadge from "../fragments/ImportStatusBadge";
import ImportErrorsTable from "./fragments/ImportErrorsTable";
import { useImportDetail } from "./useImportDetail";

export default function ImportDetailPage() {
  const { item, isLoading, error, errors, errorsMeta, isLoadingErrors, setErrorPage } =
    useImportDetail();

  if (isLoading && !item) {
    return (
      <div className="flex justify-center py-20 text-slate-400">
        <Loader size={24} />
      </div>
    );
  }

  if (!item) {
    return (
      <EmptyState
        title={error?.status === 403 ? "You cannot view this import" : "Import not found"}
        action={
          <Link href={IMPORTS_PATH}>
            <Button.Primary tone="ghost">Back to imports</Button.Primary>
          </Link>
        }
      />
    );
  }

  const counters = [
    { label: "Imported", value: item.imported_rows, className: "text-emerald-700" },
    { label: "Skipped as duplicates", value: item.skipped_rows, className: "text-slate-700" },
    { label: "Failed validation", value: item.failed_rows, className: "text-red-700" },
  ];

  return (
    <div className="space-y-6">
      <div className="space-y-3">
        <Link
          href={IMPORTS_PATH}
          className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900"
        >
          <ArrowLeft size={14} /> All imports
        </Link>
        <PageHeader
          title={item.original_name}
          description={
            <span className="flex flex-wrap items-center gap-2">
              <ImportStatusBadge status={item.status} />
              <span>
                uploaded by {item.user.name} · {formatDateTime(item.created_at)}
              </span>
            </span>
          }
        />
      </div>
      <section className="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
        <ImportProgressBar item={item} />
        {item.error_message && (
          <p className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            {item.error_message}
          </p>
        )}
        <dl className="grid gap-4 sm:grid-cols-3">
          {counters.map((counter) => (
            <div key={counter.label}>
              <dt className="text-xs font-medium tracking-wide text-slate-500 uppercase">
                {counter.label}
              </dt>
              <dd className={`text-2xl font-semibold tabular-nums ${counter.className}`}>
                {counter.value}
              </dd>
            </div>
          ))}
        </dl>
      </section>
      <section className="space-y-3">
        <h2 className="text-sm font-semibold text-slate-900">Rows that were not imported</h2>
        <ImportErrorsTable errors={errors} isLoading={isLoadingErrors} />
        {errorsMeta && <Pagination meta={errorsMeta} onPageChange={setErrorPage} />}
      </section>
    </div>
  );
}
