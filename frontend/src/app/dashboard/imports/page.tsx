"use client";

import { PageHeader, Pagination } from "@/components";
import ImportTable from "./fragments/ImportTable";
import ImportUploadCard from "./fragments/ImportUploadCard";
import { useImports } from "./useImports";

export default function ImportsPage() {
  const { imports, meta, isLoading, isUploading, uploadError, upload, setPage } = useImports();

  return (
    <div className="space-y-6">
      <PageHeader
        title="Imports"
        description="Bulk-load tickets from a CSV file. Processing happens in the background."
      />
      <ImportUploadCard isUploading={isUploading} error={uploadError} onUpload={upload} />
      <ImportTable imports={imports} isLoading={isLoading} />
      {meta && <Pagination meta={meta} onPageChange={setPage} />}
    </div>
  );
}
