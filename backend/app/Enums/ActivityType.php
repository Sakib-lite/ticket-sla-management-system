<?php

namespace App\Enums;

enum ActivityType: string
{
    case Created = 'created';
    case Imported = 'imported';
    case Assigned = 'assigned';
    case AutoAssigned = 'auto_assigned';
    case Claimed = 'claimed';
    case Unassigned = 'unassigned';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case CategoryChanged = 'category_changed';
    case SubjectChanged = 'subject_changed';
    case ReplyAdded = 'reply_added';
    case NoteAdded = 'note_added';
    case AttachmentAdded = 'attachment_added';
    case SlaApproaching = 'sla_approaching';
    case Escalated = 'escalated';

    public function isInternal(): bool
    {
        return $this === self::NoteAdded;
    }
}
