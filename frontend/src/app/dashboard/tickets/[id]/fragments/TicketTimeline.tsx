"use client";

import {
  ArrowsClockwise,
  ChatCircle,
  Clock,
  Flag,
  LockSimple,
  Paperclip,
  PencilSimple,
  PlusCircle,
  Tag,
  UploadSimple,
  UserCircle,
  UserMinus,
  Warning,
} from "@phosphor-icons/react";
import { Button, Loader } from "@/components";
import type { Activity, ActivityType } from "@/types";
import { classNames, formatDate, formatDateTime, formatRelativeTime } from "@/utils";
import { useTicketActivities } from "../useTicketActivities";

type TicketTimelineProps = {
  ticketId: number;
  version: number;
};

const icons: Record<ActivityType, typeof Clock> = {
  created: PlusCircle,
  imported: UploadSimple,
  assigned: UserCircle,
  auto_assigned: UserCircle,
  claimed: UserCircle,
  unassigned: UserMinus,
  status_changed: ArrowsClockwise,
  priority_changed: Flag,
  category_changed: Tag,
  subject_changed: PencilSimple,
  reply_added: ChatCircle,
  note_added: LockSimple,
  attachment_added: Paperclip,
  sla_approaching: Clock,
  escalated: Warning,
};

const tones: Partial<Record<ActivityType, string>> = {
  escalated: "bg-red-100 text-red-700",
  sla_approaching: "bg-amber-100 text-amber-700",
  note_added: "bg-amber-100 text-amber-700",
  created: "bg-emerald-100 text-emerald-700",
};

const groupByDay = (activities: Activity[]) =>
  activities.reduce<{ day: string; items: Activity[] }[]>((groups, activity) => {
    const day = formatDate(activity.created_at);
    const group = groups.at(-1);
    if (group?.day === day) {
      group.items.push(activity);
    } else {
      groups.push({ day, items: [activity] });
    }
    return groups;
  }, []);

export default function TicketTimeline({ ticketId, version }: TicketTimelineProps) {
  const { activities, isLoading, hasMore, isLoadingMore, loadMore } = useTicketActivities(
    ticketId,
    version
  );

  return (
    <section className="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
      <h2 className="text-sm font-semibold text-slate-900">History</h2>
      {isLoading && activities.length === 0 ? (
        <div className="flex justify-center py-6 text-slate-400">
          <Loader size={20} />
        </div>
      ) : (
        <div className="space-y-5">
          {groupByDay(activities).map((group) => (
            <div key={group.day} className="space-y-3">
              <p className="text-xs font-medium tracking-wide text-slate-400 uppercase">
                {group.day}
              </p>
              <ol className="space-y-3 border-l border-slate-200 pl-4">
                {group.items.map((activity) => {
                  const Icon = icons[activity.type];
                  return (
                    <li key={activity.id} className="relative flex items-start gap-3">
                      <span
                        className={classNames(
                          "absolute -left-[25px] flex h-5 w-5 items-center justify-center rounded-full",
                          tones[activity.type] ?? "bg-slate-100 text-slate-600"
                        )}
                      >
                        <Icon size={12} weight="bold" />
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="text-sm text-slate-800">{activity.description}</p>
                        <time
                          dateTime={activity.created_at}
                          title={formatDateTime(activity.created_at)}
                          className="text-xs text-slate-500"
                        >
                          {formatRelativeTime(activity.created_at)}
                        </time>
                      </div>
                    </li>
                  );
                })}
              </ol>
            </div>
          ))}
        </div>
      )}
      {hasMore && (
        <Button.Primary
          tone="ghost"
          isLoading={isLoadingMore}
          onClick={loadMore}
          className="w-full"
        >
          Show older activity
        </Button.Primary>
      )}
    </section>
  );
}
