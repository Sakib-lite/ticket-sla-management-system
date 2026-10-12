import type { BusinessHours } from "@/types";

export default function BusinessHoursCard({ hours }: { hours: BusinessHours }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5">
      <h2 className="text-sm font-semibold text-slate-900">Business hours</h2>
      <p className="mb-3 text-sm text-slate-500">
        SLA clocks only run during these hours. Configured in the backend environment.
      </p>
      <dl className="grid gap-3 text-sm sm:grid-cols-3">
        <div>
          <dt className="text-xs font-medium tracking-wide text-slate-500 uppercase">Days</dt>
          <dd className="text-slate-800">
            {hours.working_days.map((day) => day.label).join(", ")}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-medium tracking-wide text-slate-500 uppercase">Hours</dt>
          <dd className="text-slate-800">
            {hours.opens_at} – {hours.closes_at}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-medium tracking-wide text-slate-500 uppercase">Timezone</dt>
          <dd className="text-slate-800">{hours.timezone}</dd>
        </div>
      </dl>
    </section>
  );
}
