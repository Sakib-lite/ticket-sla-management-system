"use client";

import { Button, Form, Modal } from "@/components";

type DraftTicketModalProps = {
  isOpen: boolean;
  subject: string;
  priority: string;
  onSubjectChange: (value: string) => void;
  onPriorityChange: (value: string) => void;
  onClose: () => void;
  onSave: () => Promise<void>;
};

const priorityOptions = [
  { label: "Low", value: "low" },
  { label: "Medium", value: "medium" },
  { label: "High", value: "high" },
  { label: "Critical", value: "critical" },
];

export default function DraftTicketModal({
  isOpen,
  subject,
  priority,
  onSubjectChange,
  onPriorityChange,
  onClose,
  onSave,
}: DraftTicketModalProps) {
  return (
    <Modal
      isOpen={isOpen}
      title="Draft ticket"
      size="sm"
      onClose={onClose}
      footer={
        <div className="flex justify-end gap-2">
          <Button.Primary tone="ghost" onClick={onClose}>
            Cancel
          </Button.Primary>
          <Button.Async onClick={onSave}>Save draft</Button.Async>
        </div>
      }
    >
      <div className="space-y-4">
        <Form.TextField
          label="Subject"
          value={subject}
          placeholder="Printer stopped responding"
          onChange={(event) => onSubjectChange(event.target.value)}
        />
        <Form.SelectField
          label="Priority"
          value={priority}
          placeholder="Select a priority"
          options={priorityOptions}
          onChange={(event) => onPriorityChange(event.target.value)}
        />
      </div>
    </Modal>
  );
}
