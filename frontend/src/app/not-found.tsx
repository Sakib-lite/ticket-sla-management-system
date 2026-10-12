import Link from "next/link";
import { DASHBOARD_PATH } from "@/constants/paths";

export default function NotFoundPage() {
  return (
    <main className="flex min-h-dvh flex-col items-center justify-center gap-4 px-6 text-center">
      <p className="font-mono text-sm text-slate-400">404</p>
      <h1 className="text-xl font-semibold text-slate-900">That page does not exist</h1>
      <Link href={DASHBOARD_PATH} className="text-sm text-slate-600 underline hover:text-slate-900">
        Back to the dashboard
      </Link>
    </main>
  );
}
