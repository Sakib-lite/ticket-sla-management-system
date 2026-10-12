import { Loader } from "@/components/feedback";

export default function DashboardLoading() {
  return (
    <div className="flex justify-center py-20 text-slate-400">
      <Loader size={24} />
    </div>
  );
}
