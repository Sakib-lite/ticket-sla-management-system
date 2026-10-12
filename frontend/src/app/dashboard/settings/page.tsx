"use client";

import { Loader, PageHeader } from "@/components";
import BusinessHoursCard from "./fragments/BusinessHoursCard";
import CategoryManager from "./fragments/CategoryManager";
import SlaPolicyTable from "./fragments/SlaPolicyTable";
import { useSettings } from "./useSettings";

export default function SettingsPage() {
  const settings = useSettings();

  return (
    <div className="space-y-6">
      <PageHeader title="Settings" description="SLA rules, ticket categories and working hours." />
      {settings.isLoading && settings.policies.length === 0 ? (
        <div className="flex justify-center py-20 text-slate-400">
          <Loader size={24} />
        </div>
      ) : (
        <div className="grid gap-6 lg:grid-cols-2">
          <div className="space-y-6">
            <SlaPolicyTable policies={settings.policies} onSave={settings.savePolicy} />
            {settings.businessHours && <BusinessHoursCard hours={settings.businessHours} />}
          </div>
          <CategoryManager
            categories={settings.categories}
            onAdd={settings.addCategory}
            onUpdate={settings.updateCategory}
          />
        </div>
      )}
    </div>
  );
}
