"use client";

import { useState } from "react";
import { DownloadSimple, UploadSimple } from "@phosphor-icons/react";
import { Button, Form } from "@/components";
import { importApi } from "@/services";

type ImportUploadCardProps = {
  isUploading: boolean;
  error: string | null;
  onUpload: (file: File) => Promise<void>;
};

const MAX_IMPORT_BYTES = 20 * 1024 * 1024;

export default function ImportUploadCard({ isUploading, error, onUpload }: ImportUploadCardProps) {
  const [files, setFiles] = useState<File[]>([]);

  return (
    <section className="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="text-sm font-semibold text-slate-900">Import tickets from CSV</h2>
          <p className="text-sm text-slate-500">
            Columns: subject, description, priority, category, customer_email, plus optional
            external_reference and created_at. Rows with a known external_reference are skipped.
          </p>
        </div>
        <Button.Async tone="ghost" onClick={importApi.downloadTemplate}>
          <DownloadSimple size={16} /> Template
        </Button.Async>
      </div>
      <Form.FileField
        label="CSV file"
        files={files}
        accept=".csv,text/csv"
        maxFiles={1}
        maxBytes={MAX_IMPORT_BYTES}
        error={error ?? undefined}
        onChange={setFiles}
      />
      <Button.Primary
        isLoading={isUploading}
        disabled={files.length === 0}
        onClick={async () => {
          await onUpload(files[0]);
          setFiles([]);
        }}
      >
        <UploadSimple size={16} /> Start import
      </Button.Primary>
    </section>
  );
}
