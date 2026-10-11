"use client";

import { useId } from "react";
import { Paperclip, X } from "@phosphor-icons/react";
import { formatBytes } from "@/utils";

type FileFieldProps = {
  label: string;
  files: File[];
  accept: string;
  maxFiles: number;
  maxBytes: number;
  error?: string;
  onChange: (files: File[]) => void;
};

export default function FileField({
  label,
  files,
  accept,
  maxFiles,
  maxBytes,
  error,
  onChange,
}: FileFieldProps) {
  const inputId = useId();
  const remaining = maxFiles - files.length;

  const addFiles = (incoming: FileList | null) => {
    if (!incoming) {
      return;
    }
    onChange([...files, ...Array.from(incoming)].slice(0, maxFiles));
  };

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <span className="text-sm font-medium text-slate-700">{label}</span>
        <span className="text-xs text-slate-500">
          Up to {maxFiles} files, {formatBytes(maxBytes)} each
        </span>
      </div>
      {files.length > 0 && (
        <ul className="space-y-1">
          {files.map((file, index) => (
            <li
              key={`${file.name}-${index}`}
              className="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm"
            >
              <span className="truncate text-slate-700">
                {file.name} <span className="text-slate-400">· {formatBytes(file.size)}</span>
              </span>
              <button
                type="button"
                aria-label={`Remove ${file.name}`}
                onClick={() => onChange(files.filter((_, i) => i !== index))}
                className="rounded p-0.5 text-slate-500 hover:bg-slate-200"
              >
                <X size={14} />
              </button>
            </li>
          ))}
        </ul>
      )}
      {remaining > 0 && (
        <label
          htmlFor={inputId}
          className="inline-flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
        >
          <Paperclip size={16} /> Attach files
          <input
            id={inputId}
            type="file"
            multiple
            accept={accept}
            className="sr-only"
            onChange={(event) => {
              addFiles(event.target.files);
              event.target.value = "";
            }}
          />
        </label>
      )}
      {error && <p className="text-xs text-red-600">{error}</p>}
    </div>
  );
}
