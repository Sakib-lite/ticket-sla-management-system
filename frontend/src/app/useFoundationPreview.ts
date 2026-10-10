"use client";

import { useState } from "react";
import { confirmAction } from "@/hooks/useConfirm";
import { notify } from "@/hooks/useNotify";

export const useFoundationPreview = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [subject, setSubject] = useState("");
  const [priority, setPriority] = useState("");

  const openModal = () => setIsModalOpen(true);
  const closeModal = () => setIsModalOpen(false);

  const saveDraft = async () => {
    if (!subject.trim()) {
      notify.warning("Add a subject before saving.");
      return;
    }
    notify.success("Draft saved.");
    closeModal();
  };

  const discardDraft = async () => {
    const confirmed = await confirmAction({
      title: "Discard draft",
      description: "This will clear everything you typed.",
      confirmText: "Discard",
      tone: "danger",
    });

    if (confirmed) {
      setSubject("");
      setPriority("");
      notify.info("Draft discarded.");
    }
  };

  return {
    isModalOpen,
    openModal,
    closeModal,
    subject,
    setSubject,
    priority,
    setPriority,
    saveDraft,
    discardDraft,
  };
};
